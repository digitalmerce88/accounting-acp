<?php

namespace App\Domain\Accounting\Services;

use App\Models\{JournalLine, Invoice, Bill};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ProfitAndLossService
{
    public function run(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from) : Carbon::now()->startOfMonth();
        $end = $to ? Carbon::parse($to) : Carbon::now()->endOfMonth();

        $revenue = $this->sumType('revenue', 'credit', $start, $end);
        $expense = $this->sumType('expense', 'debit', $start, $end);
        $net = round($revenue - $expense, 2);

        // Base currency totals from invoices & bills (multi-currency normalization)
        $revenueBase = $this->sumDocumentBase(Invoice::class, 'issue_date', $start, $end);
        $expenseBase = $this->sumDocumentBase(Bill::class, 'bill_date', $start, $end);
        $netBase = round($revenueBase - $expenseBase, 2);

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'revenue' => $revenue,
            'expense' => $expense,
            'net' => $net,
            'revenue_base' => $revenueBase,
            'expense_base' => $expenseBase,
            'net_base' => $netBase,
        ];
    }

    /**
     * Sum the document's base total robustly - fallback if column missing.
     */
    private function sumDocumentBase(string $modelClass, string $dateColumn, $start, $end): float
    {
        $model = new $modelClass;
        $table = $model->getTable();

        if (Schema::hasColumn($table, 'base_total_decimal')) {
            return (float) $modelClass::query()
                ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'draft')
                ->sum('base_total_decimal');
        }

        // If fx exists, approximate base by total * fx_rate_decimal
        if (Schema::hasColumn($table, 'fx_rate_decimal') && Schema::hasColumn($table, 'total')) {
            $res = $modelClass::query()
                ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'draft')
                ->selectRaw('SUM(total * COALESCE(fx_rate_decimal,1)) as aggregate')
                ->value('aggregate');
            return (float) ($res ?? 0);
        }

        // Last resort - sum the total column if present
        if (Schema::hasColumn($table, 'total')) {
            return (float) $modelClass::query()
                ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'draft')
                ->sum('total');
        }

        return 0.0;
    }

    private function sumType(string $type, string $polarity, $start, $end): float
    {
        $col = $polarity === 'debit' ? 'journal_lines.debit' : 'journal_lines.credit';
        $q = JournalLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_lines.entry_id')
            ->join('accounts as a', 'a.id', '=', 'journal_lines.account_id')
            ->whereBetween('je.date', [$start->toDateString(), $end->toDateString()])
            ->where('a.type', $type);

        if (Schema::hasColumn('journal_entries', 'is_closing')) {
            $q->where('je.is_closing', false);
        }

        return (float) $q->sum($col);
    }
}
