<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Domain\Accounting\Reports\TrialBalance;
use App\Domain\Accounting\Reports\Ledger;
use App\Domain\Accounting\Reports\FinancialStatements;

class ReportController extends Controller {
    public function trialBalance(Request $r) {
        $from = $r->query('from'); $to = $r->query('to');
        $data = (new TrialBalance)->run($from, $to);
        return view('reports.trial', compact('data','from','to'));
    }
    public function ledger(Request $r) {
        $accountId = $r->query('account_id'); $from = $r->query('from'); $to = $r->query('to');
        $data = (new Ledger)->run($accountId, $from, $to);
        return view('reports.ledger', compact('data','from','to'));
    }
    public function pnl(Request $r) {
        $from = $r->query('from'); $to = $r->query('to');
        $data = (new FinancialStatements)->incomeStatement($from, $to);
        return view('reports.pnl', compact('data','from','to'));
    }
    public function balanceSheet(Request $r) {
        $asOf = $r->query('as_of');
        $data = (new FinancialStatements)->balanceSheet($asOf);
        return view('reports.bs', compact('data','asOf'));
    }
    public function trialCsv(Request $r): StreamedResponse {
        $from = $r->query('from'); $to = $r->query('to');
        $rows = (new TrialBalance)->run($from, $to);
        return response()->streamDownload(function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code','name','type','debit','credit']);
            foreach ($rows as $row) { fputcsv($out, $row); }
            fclose($out);
        }, 'trial-balance.csv');
    }
    public function ledgerCsv(Request $r): StreamedResponse {
        $accountId = $r->query('account_id'); $from=$r->query('from'); $to=$r->query('to');
        $rows = (new Ledger)->run($accountId,$from,$to);
        return response()->streamDownload(function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date','entry_id','memo','debit','credit','balance']);
            foreach ($rows as $row) { fputcsv($out, $row); }
            fclose($out);
        }, 'ledger.csv');
    }
    public function pnlCsv(Request $r): StreamedResponse {
        $from=$r->query('from'); $to=$r->query('to');
        $data = (new FinancialStatements)->incomeStatement($from,$to);
        return response()->streamDownload(function() use ($data) {
            $out = fopen('php://output', 'w');
            foreach ($data as $k=>$v) { fputcsv($out, [$k, $v]); }
            fclose($out);
        }, 'pnl.csv');
    }
    public function bsCsv(Request $r): StreamedResponse {
        $asOf=$r->query('as_of');
        $data = (new FinancialStatements)->balanceSheet($asOf);
        return response()->streamDownload(function() use ($data) {
            $out = fopen('php://output', 'w');
            foreach ($data as $k=>$v) { fputcsv($out, [$k, $v]); }
            fclose($out);
        }, 'balance-sheet.csv');
    }
}
