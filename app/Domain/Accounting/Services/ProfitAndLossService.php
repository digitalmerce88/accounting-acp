<?php

namespace App\Domain\Accounting\Services;

use App\Models\JournalLine;
use Illuminate\Support\Carbon;

class ProfitAndLossService
{
    public function run(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from) : Carbon::now()->startOfMonth();
        $end = $to ? Carbon::parse($to) : Carbon::now()->endOfMonth();

        $revenue = $this->sumType('revenue', 'credit', $start, $end);
        $expense = $this->sumType('expense', 'debit', $start, $end);
        $net = round($revenue - $expense, 2);

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'revenue' => $revenue,
            'expense' => $expense,
            'net' => $net,
        ];
    }

    private function sumType(string $type, string $polarity, $start, $end): float
    {
        $col = $polarity === 'debit' ? 'journal_lines.debit' : 'journal_lines.credit';
        return (float) JournalLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_lines.entry_id')
            ->join('accounts as a', 'a.id', '=', 'journal_lines.account_id')
            ->whereBetween('je.date', [$start->toDateString(), $end->toDateString()])
            ->where('a.type', $type)
            ->sum($col);
    }
}
