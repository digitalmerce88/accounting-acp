<?php
namespace App\Domain\Accounting\Reports;
use Illuminate\Support\Facades\DB;
class FinancialStatements {
    public function incomeStatement($from, $to): array {
        $rows = DB::table('journal_lines as l')
            ->join('journal_entries as e','e.id','=','l.entry_id')
            ->join('accounts as a','a.id','=','l.account_id')
            ->selectRaw('a.type, ROUND(SUM(l.debit),2) dr, ROUND(SUM(l.credit),2) cr')
            ->where('e.status','posted')
            ->whereBetween('e.date', [$from, $to])
            ->groupBy('a.type')->get();
        $income=0; $expense=0;
        foreach ($rows as $r) { if ($r->type==='income') $income+=($r->cr??0)-($r->dr??0); if ($r->type==='expense') $expense+=($r->dr??0)-($r->cr??0); }
        return ['income'=>$income,'expense'=>$expense,'profit'=>$income-$expense];
    }
    public function balanceSheet($asOf): array {
        $rows = DB::table('journal_lines as l')
            ->join('journal_entries as e','e.id','=','l.entry_id')
            ->join('accounts as a','a.id','=','l.account_id')
            ->selectRaw('a.type, ROUND(SUM(l.debit),2) dr, ROUND(SUM(l.credit),2) cr')
            ->where('e.status','posted')->where('e.date','<=',$asOf)
            ->groupBy('a.type')->get();
        $asset=$liab=$equity=$inc=$exp=0;
        foreach ($rows as $r) {
            if ($r->type==='asset') $asset+=($r->dr??0)-($r->cr??0);
            if ($r->type==='liability') $liab+=($r->cr??0)-($r->dr??0);
            if ($r->type==='equity') $equity+=($r->cr??0)-($r->dr??0);
            if ($r->type==='income') $inc+=($r->cr??0)-($r->dr??0);
            if ($r->type==='expense') $exp+=($r->dr??0)-($r->cr??0);
        }
        $ret = $inc - $exp; $eqTotal = $equity + $ret;
        return ['assets'=>$asset,'liabilities'=>$liab,'equity_incl_retained'=>$eqTotal,'retained_earnings'=>$ret,'balanced'=>abs($asset-($liab+$eqTotal))<0.01];
    }
}
