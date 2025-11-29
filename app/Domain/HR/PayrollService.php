<?php

namespace App\Domain\HR;

use App\Models\{Employee, PayrollRun, PayrollItem};
use App\Domain\Accounting\PostingService;

class PayrollService
{
    public function createRun(int $businessId, int $year, int $month): PayrollRun
    {
        $run = PayrollRun::create([
            'business_id' => $businessId,
            'period_year' => $year,
            'period_month' => $month,
            'status' => 'draft',
        ]);
        // seed items from active employees
        $emps = Employee::where('business_id',$businessId)->where('active',true)->get();
        foreach ($emps as $e) {
            $calc = $this->calcForEmployee($e);
            PayrollItem::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $e->id,
                'earning_basic_decimal' => $e->base_salary_decimal ?? 0,
                'earning_other_decimal' => 0,
                'sso_employee_decimal' => $calc['sso_employee'],
                'sso_employer_decimal' => $calc['sso_employer'],
                'wht_decimal' => $calc['wht'],
                'net_pay_decimal' => $calc['net'],
                'meta_json' => null,
            ]);
        }
        return $run->fresh(['items']);
    }

    public function lock(int $runId): PayrollRun
    {
        $run = PayrollRun::findOrFail($runId);
        $run->status = 'locked';
        $run->save();
        return $run;
    }

    public function pay(int $runId, int $businessId, string $payDate): PayrollRun
    {
        $run = PayrollRun::with('items')->findOrFail($runId);
        // Safety: only allow pay when locked
        if ($run->status !== 'locked') {
            throw new \RuntimeException('Payroll run must be locked before paying');
        }

        // Idempotent: if already paid, return existing
        if ($run->status === 'paid') {
            return $run;
        }

        $svc = new PostingService();

        $sumBasicOther = (float) $run->items->sum(fn($i)=> ($i->earning_basic_decimal + $i->earning_other_decimal));
        $sumSsoEmployer = (float) $run->items->sum('sso_employer_decimal');

        // Post summarized journal for the run
        $entry = $svc->postExpense([
            'business_id' => $businessId,
            'date' => $payDate,
            'memo' => sprintf('Payroll %04d-%02d', $run->period_year, $run->period_month),
            'amount' => $sumBasicOther + $sumSsoEmployer,
            'price_input_mode' => 'novat',
            'vat_applicable' => false,
            'wht_rate' => 0,
            'payment_method' => 'bank',
            'category_id' => null,
            'vendor_id' => null,
        ]);

        // persist posting id for traceability
        $run->posting_entry_id = $entry->id ?? null;
        $run->status = 'paid';
        $run->processed_at = now();
        $run->save();
        return $run;
    }

    public function calcForEmployee(Employee $emp): array
    {
        $cfg = config('hr');
        $baseSalary = (float) ($emp->base_salary_decimal ?? 0);
        $base = min($baseSalary, $cfg['sso_wage_ceiling']);
        $ssoEmp = round($base * $cfg['sso_employee_rate'], 2);
        $ssoEr = round($base * $cfg['sso_employer_rate'], 2);
        $taxable = max(0, $baseSalary - $ssoEmp);
        $override = (float) ($emp->tax_profile_json['wht_fixed_decimal'] ?? 0);
        if ($override > 0) {
            // clamp not to exceed taxable
            $wht = round(min($override, $taxable), 2);
        } else {
            $wht = round($this->applyBracket($taxable, $cfg['wht_table']), 2);
        }
        $net = round($baseSalary - $ssoEmp - $wht, 2);
        return [
            'sso_employee' => $ssoEmp,
            'sso_employer' => $ssoEr,
            'wht' => $wht,
            'net' => $net,
        ];
    }

    private function applyBracket(float $amount, array $table): float
    {
        foreach ($table as $row) {
            [$min, $max, $rate] = $row;
            if ($amount >= $min && $amount <= $max) {
                return $amount * (float)$rate;
            }
        }
        return 0.0;
    }
}
