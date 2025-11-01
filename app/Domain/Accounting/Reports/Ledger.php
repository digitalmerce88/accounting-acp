<?php
namespace App\Domain\Accounting\Reports;
use App\Models\JournalLine;
class Ledger {
    public function run($accountId, $from=null, $to=null): array {
        $q = JournalLine::query()
            ->join('journal_entries as e','e.id','=','journal_lines.entry_id')
            ->join('accounts as a','a.id','=','journal_lines.account_id')
            ->selectRaw('e.date, e.id as entry_id, e.memo, journal_lines.debit, journal_lines.credit, a.name as account_name')
            ->where('e.status','posted')
            ->orderBy('e.date')->orderBy('e.id');
        if ($accountId) {
            $q->where('journal_lines.account_id', $accountId);
        }
        if ($from) $q->where('e.date','>=',$from);
        if ($to) $q->where('e.date','<=',$to);
        $rows = $q->get(); $bal=0.0; $out=[];
        foreach ($rows as $r) {
            $bal += ($r->debit ?? 0) - ($r->credit ?? 0);
            // show account name in place of entry id to make ledger rows clearer
            $label = $r->account_name ?? $r->entry_id;
            $out[] = [$r->date, $label, $r->memo, (float)$r->debit, (float)$r->credit, round($bal,2)];
        }
        return $out;
    }
}
