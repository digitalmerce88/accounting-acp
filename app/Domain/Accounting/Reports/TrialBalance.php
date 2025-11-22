<?php
namespace App\Domain\Accounting\Reports;

use App\Models\JournalLine;
use Illuminate\Support\Facades\Schema;

class TrialBalance {
    public function run($from=null, $to=null): array {
        $q = JournalLine::query()
            ->join('journal_entries as e','e.id','=','journal_lines.entry_id')
            ->join('accounts as a','a.id','=','journal_lines.account_id')
            ->selectRaw('a.code, a.name, a.type, ROUND(SUM(journal_lines.debit),2) dr, ROUND(SUM(journal_lines.credit),2) cr')
            ->where('e.status','posted')
            ->groupBy('a.id','a.code','a.name','a.type')
            ->orderBy('a.code');

        if (Schema::hasColumn('journal_entries', 'is_closing')) {
            $q->where('e.is_closing', false);
        }

        if ($from) $q->where('e.date','>=',$from);
        if ($to) $q->where('e.date','<=',$to);
        return $q->get()->map(fn($r)=>[$r->code,$r->name,$r->type,(float)$r->dr,(float)$r->cr])->all();
    }
}
