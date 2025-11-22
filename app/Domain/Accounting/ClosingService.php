<?php

namespace App\Domain\Accounting;

use App\Models\{Account, JournalEntry, JournalLine, ClosingPeriod};
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ClosingService
{
    public function closeMonth(int $businessId, int $year, int $month, string $equityCode = '301'): JournalEntry
    {
        // idempotent: if already closed, throw
        if (ClosingPeriod::where(['business_id' => $businessId, 'period_year' => $year, 'period_month' => $month])->exists()) {
            throw new \RuntimeException('Period already closed');
        }
        $date = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return DB::transaction(function () use ($businessId, $year, $month, $equityCode, $date) {
            $entry = JournalEntry::create([
                'business_id' => $businessId,
                'date' => $date,
                'memo' => sprintf('ปิดงวด %04d-%02d', $year, $month),
                'status' => 'posted',
                'is_closing' => Schema::hasColumn('journal_entries', 'is_closing') ? true : false,
            ]);

            // Sum balances per account in period
            $revenueTotal = 0.0; $expenseTotal = 0.0;
            $accountIds = Account::where('business_id',$businessId)->pluck('id','id');
            foreach (Account::where('business_id',$businessId)->get(['id','type']) as $acc) {
                [$debit, $credit] = $this->sumAccountForMonth($acc->id, $year, $month);
                if ($acc->type === 'revenue') {
                    $bal = round($credit - $debit, 2);
                    if ($bal > 0) { // close by debiting revenue
                        $this->line($entry, $acc->id, $bal, 0);
                        $revenueTotal += $bal;
                    }
                } elseif ($acc->type === 'expense') {
                    $bal = round($debit - $credit, 2);
                    if ($bal > 0) { // close by crediting expense
                        $this->line($entry, $acc->id, 0, $bal);
                        $expenseTotal += $bal;
                    }
                }
            }

            $net = round($revenueTotal - $expenseTotal, 2);
            $equityId = Account::where(['business_id'=>$businessId,'code'=>$equityCode])->value('id');
            if ($net > 0) {
                // Profit: credit equity to balance (debits > credits)
                $this->line($entry, $equityId, 0, $net);
            } elseif ($net < 0) {
                // Loss: debit equity
                $this->line($entry, $equityId, -$net, 0);
            }

            ClosingPeriod::create([
                'business_id' => $businessId,
                'period_month' => $month,
                'period_year' => $year,
                'closed_at' => now(),
            ]);

            return $entry;
        });
    }

    private function sumAccountForMonth(int $accountId, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        $row = DB::table('journal_lines as jl')
            ->join('journal_entries as je','je.id','=','jl.entry_id')
            ->whereBetween('je.date', [$start, $end])
            ->where('jl.account_id', $accountId);

        if (Schema::hasColumn('journal_entries', 'is_closing')) {
            $row->where('je.is_closing', false);
        }

        $row = $row->selectRaw('COALESCE(SUM(jl.debit),0) as deb, COALESCE(SUM(jl.credit),0) as cred')
            ->first();
        return [ (float)($row->deb ?? 0), (float)($row->cred ?? 0) ];
    }

    private function line(JournalEntry $e, int $accountId, float $debit, float $credit): void
    {
        JournalLine::create([
            'entry_id' => $e->id,
            'account_id' => $accountId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ]);
    }
}
