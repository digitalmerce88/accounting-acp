<?php

namespace App\Domain\Accounting\Services;

use App\Models\{JournalEntry, JournalLine, Account};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SummaryReportService
{
    // Sum credits on revenue accounts as income; debits on expense accounts as expense
    public function overview(array $filters = []): array
    {
        [$start, $end] = $this->range($filters);

        $income = $this->sumByType('revenue', 'credit', $start, $end);
        $expense = $this->sumByType('expense', 'debit', $start, $end);

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'income' => $income,
            'expense' => $expense,
            'net' => round($income - $expense, 2),
        ];
    }

    public function byCategory(array $filters = []): array
    {
        [$start, $end] = $this->range($filters);

        // Group by account for revenue and expense
        $rows = JournalLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_lines.entry_id')
            ->join('accounts as a', 'a.id', '=', 'journal_lines.account_id')
            ->whereBetween('je.date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('a.type', ['revenue','expense'])
            ->selectRaw("a.id, a.code, a.name, a.type,
                SUM(CASE WHEN a.type='revenue' THEN journal_lines.credit ELSE 0 END) as revenue,
                SUM(CASE WHEN a.type='expense' THEN journal_lines.debit ELSE 0 END) as expense")
            ->groupBy('a.id','a.code','a.name','a.type')
            ->orderBy('a.type')->orderBy('a.code')
            ->get()
            ->map(function($r){
                return [
                    'code' => $r->code,
                    'name' => $r->name,
                    'type' => $r->type,
                    'amount' => round(($r->type==='revenue' ? $r->revenue : $r->expense) + 0, 2),
                ];
            })
            ->all();

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'rows' => $rows,
        ];
    }

    public function taxPurchaseVat(array $filters = []): array
    {
        [$start, $end] = $this->range($filters);
        $accId = $this->accountByCode('511', $filters);
        $sum = $this->sumAccount($accId, 'debit', $start, $end);
        return ['start'=>$start->toDateString(), 'end'=>$end->toDateString(), 'amount'=>$sum];
    }

    public function taxSalesVat(array $filters = []): array
    {
        [$start, $end] = $this->range($filters);
        $accId = $this->accountByCode('411', $filters);
        $sum = $this->sumAccount($accId, 'credit', $start, $end);
        return ['start'=>$start->toDateString(), 'end'=>$end->toDateString(), 'amount'=>$sum];
    }

    public function whtSummary(array $filters = []): array
    {
        [$start, $end] = $this->range($filters);
        $recId = $this->accountByCode('153', $filters);
        $payId = $this->accountByCode('231', $filters);
        $received = $this->sumAccount($recId, 'debit', $start, $end);
        $payable = $this->sumAccount($payId, 'credit', $start, $end);
        return [
            'start'=>$start->toDateString(),
            'end'=>$end->toDateString(),
            'wht_received'=>$received,
            'wht_payable'=>$payable,
        ];
    }

    private function range(array $filters): array
    {
        $start = isset($filters['start']) ? Carbon::parse($filters['start']) : Carbon::now()->startOfMonth();
        $end = isset($filters['end']) ? Carbon::parse($filters['end']) : Carbon::now()->endOfMonth();
        return [$start, $end];
    }

    private function sumByType(string $accountType, string $polarity, $start, $end): float
    {
        $col = $polarity === 'debit' ? 'journal_lines.debit' : 'journal_lines.credit';
        return (float) JournalLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_lines.entry_id')
            ->join('accounts as a', 'a.id', '=', 'journal_lines.account_id')
            ->whereBetween('je.date', [$start->toDateString(), $end->toDateString()])
            ->where('a.type', $accountType)
            ->sum($col);
    }

    private function sumAccount(int $accountId = null, string $polarity, $start, $end): float
    {
        if (!$accountId) return 0.0;
        $col = $polarity === 'debit' ? 'journal_lines.debit' : 'journal_lines.credit';
        return (float) JournalLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_lines.entry_id')
            ->whereBetween('je.date', [$start->toDateString(), $end->toDateString()])
            ->where('journal_lines.account_id', $accountId)
            ->sum($col);
    }

    private function accountByCode(string $code, array $filters): ?int
    {
        $businessId = $filters['business_id'] ?? null; // optional scoping
        $q = Account::query()->where('code', $code);
        if ($businessId) $q->where('business_id', $businessId);
        return $q->value('id');
    }
}
