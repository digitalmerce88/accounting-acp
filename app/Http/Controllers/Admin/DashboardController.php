<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;
use App\Models\{Account, JournalEntry, Invoice, Bill, JournalLine};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Basic metrics
        $accountsCount = Account::count();
        $journalsCount = JournalEntry::count();
        $recentJournals = JournalEntry::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id','date','memo','status']);

        // Trial balance totals (sums of dr/cr)
        $tbRows = (new TrialBalanceReport())->run(null, null);
        $tbTotalDr = 0; $tbTotalCr = 0;
        foreach ($tbRows as $r) { // [code, name, type, dr, cr]
            $tbTotalDr += (float)($r[3] ?? 0);
            $tbTotalCr += (float)($r[4] ?? 0);
        }

        // Aging buckets (AR/AP) based on due dates
        $today = Carbon::today();
        $arBuckets = ['current'=>0,'1_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
        $arBaseBuckets = ['current'=>0,'1_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
        $invoiceSelect = ['due_date','total','deposit_amount_decimal'];
        if (Schema::hasColumn('invoices', 'currency_code')) $invoiceSelect[] = 'currency_code';
        if (Schema::hasColumn('invoices', 'fx_rate_decimal')) $invoiceSelect[] = 'fx_rate_decimal';
        $openInvoices = Invoice::query()->where('status','!=','paid')->get($invoiceSelect);
        foreach ($openInvoices as $inv) {
            $due = $inv->due_date ? Carbon::parse($inv->due_date) : $today;
            $daysPast = $due->diffInDays($today, false); // negative if not due yet
            $outstanding = (float)($inv->total - ($inv->deposit_amount_decimal ?? 0));
            // derive base outstanding: prefer stored base_total_decimal if present, otherwise compute
            if (isset($inv->base_total_decimal) && $inv->base_total_decimal !== null) {
                $baseOutstanding = (float)$inv->base_total_decimal;
            } else {
                // if fx_rate_decimal present and currency not THB, convert; otherwise assume same
                $fx = (float)($inv->fx_rate_decimal ?? 0);
                $cur = strtoupper($inv->currency_code ?? 'THB');
                if ($cur !== 'THB' && $fx > 0) {
                    $baseOutstanding = round($outstanding / $fx, 2);
                } else {
                    $baseOutstanding = $outstanding;
                }
            }
            $bucket = 'current';
            if ($daysPast > 0 && $daysPast <= 30) $bucket = '1_30';
            elseif ($daysPast > 30 && $daysPast <= 60) $bucket = '31_60';
            elseif ($daysPast > 60 && $daysPast <= 90) $bucket = '61_90';
            elseif ($daysPast > 90) $bucket = '90_plus';
            $arBuckets[$bucket] += $outstanding;
            $arBaseBuckets[$bucket] += $baseOutstanding;
        }

        $apBuckets = ['current'=>0,'1_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
        $apBaseBuckets = ['current'=>0,'1_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
        $billSelect = ['due_date','total','deposit_amount_decimal'];
        if (Schema::hasColumn('bills', 'currency_code')) $billSelect[] = 'currency_code';
        if (Schema::hasColumn('bills', 'fx_rate_decimal')) $billSelect[] = 'fx_rate_decimal';
        $openBills = Bill::query()->where('status','!=','paid')->get($billSelect);
        foreach ($openBills as $bill) {
            $due = $bill->due_date ? Carbon::parse($bill->due_date) : $today;
            $daysPast = $due->diffInDays($today, false);
            $outstanding = (float)($bill->total - ($bill->deposit_amount_decimal ?? 0));
            if (isset($bill->base_total_decimal) && $bill->base_total_decimal !== null) {
                $baseOutstanding = (float)$bill->base_total_decimal;
            } else {
                $fx = (float)($bill->fx_rate_decimal ?? 0);
                $cur = strtoupper($bill->currency_code ?? 'THB');
                if ($cur !== 'THB' && $fx > 0) {
                    $baseOutstanding = round($outstanding / $fx, 2);
                } else {
                    $baseOutstanding = $outstanding;
                }
            }
            $bucket = 'current';
            if ($daysPast > 0 && $daysPast <= 30) $bucket = '1_30';
            elseif ($daysPast > 30 && $daysPast <= 60) $bucket = '31_60';
            elseif ($daysPast > 60 && $daysPast <= 90) $bucket = '61_90';
            elseif ($daysPast > 90) $bucket = '90_plus';
            $apBuckets[$bucket] += $outstanding;
            $apBaseBuckets[$bucket] += $baseOutstanding;
        }

        // Cashflow summary last 6 months using journal lines (revenue credits - expense debits)
        $cashflow = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $income = (float) JournalLine::query()
                ->join('journal_entries as je','je.id','=','journal_lines.entry_id')
                ->join('accounts as a','a.id','=','journal_lines.account_id')
                ->whereBetween('je.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where('a.type','revenue')
                ->sum('journal_lines.credit');
            $expense = (float) JournalLine::query()
                ->join('journal_entries as je','je.id','=','journal_lines.entry_id')
                ->join('accounts as a','a.id','=','journal_lines.account_id')
                ->whereBetween('je.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where('a.type','expense')
                ->sum('journal_lines.debit');
            $cashflow[] = [
                'month' => $monthStart->format('Y-m'),
                'income' => round($income,2),
                'expense' => round($expense,2),
                'net' => round($income - $expense,2),
            ];
        }

        // Operating expenses by category (last 30 days)
        $expenseStart = Carbon::now()->subDays(30)->toDateString();
        $expenseEnd = Carbon::now()->toDateString();
        $expensesByCategory = JournalLine::query()
            ->join('journal_entries as je','je.id','=','journal_lines.entry_id')
            ->join('accounts as a','a.id','=','journal_lines.account_id')
            ->whereBetween('je.date', [$expenseStart, $expenseEnd])
            ->where('a.type','expense')
            ->selectRaw('a.code, a.name, SUM(journal_lines.debit) as total')
            ->groupBy('a.id','a.code','a.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['code'=>$r->code, 'name'=>$r->name, 'total'=>round($r->total,2)])
            ->all();

        // Revenue & Profit trend (last 12 months)
        $trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $revenue = (float) JournalLine::query()
                ->join('journal_entries as je','je.id','=','journal_lines.entry_id')
                ->join('accounts as a','a.id','=','journal_lines.account_id')
                ->whereBetween('je.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where('a.type','revenue')
                ->sum('journal_lines.credit');
            $expense = (float) JournalLine::query()
                ->join('journal_entries as je','je.id','=','journal_lines.entry_id')
                ->join('accounts as a','a.id','=','journal_lines.account_id')
                ->whereBetween('je.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->where('a.type','expense')
                ->sum('journal_lines.debit');
            $trends[] = [
                'month' => $monthStart->format('Y-m'),
                'revenue' => round($revenue,2),
                'expense' => round($expense,2),
                'profit' => round($revenue - $expense,2),
            ];
        }

        $payload = [
            'metrics' => [
                'accounts_count' => $accountsCount,
                'journals_count' => $journalsCount,
                'tb_total_dr' => $tbTotalDr,
                'tb_total_cr' => $tbTotalCr,
            ],
            'recent_journals' => $recentJournals,
            'aging' => [
                'ar' => $arBuckets,
                'ar_base' => $arBaseBuckets,
                'ap' => $apBuckets,
                'ap_base' => $apBaseBuckets,
            ],
            'cashflow' => $cashflow,
            'expenses_by_category' => $expensesByCategory,
            'trends' => $trends,
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Admin/Dashboard', $payload);
    }
}
