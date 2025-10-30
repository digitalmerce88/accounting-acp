<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;
use App\Domain\Accounting\Reports\Ledger as LedgerReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function trialBalance(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        if ($request->wantsJson()) {
            $rows = (new TrialBalanceReport())->run($from, $to);
            return response()->json(['data'=>$rows]);
        }
        return Inertia::render('Admin/Accounting/Reports/TrialBalance');
    }

    public function ledger(Request $request)
    {
        $accountId = (int) $request->query('account_id');
        $from = $request->query('from');
        $to = $request->query('to');
        if ($request->wantsJson()) {
            $rows = (new LedgerReport())->run($accountId, $from, $to);
            return response()->json(['data'=>$rows]);
        }
        return Inertia::render('Admin/Accounting/Reports/Ledger');
    }

    public function trialBalanceCsv(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $rows = (new TrialBalanceReport())->run($from, $to);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code','name','type','dr','cr']);
            foreach ($rows as $r) { fputcsv($out, $r); }
            fclose($out);
        }, 'trial-balance.csv', ['Content-Type' => 'text/csv']);
    }

    public function ledgerCsv(Request $request)
    {
        $accountId = (int) $request->query('account_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $rows = (new LedgerReport())->run($accountId, $from, $to);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date','entry_id','memo','dr','cr','balance']);
            foreach ($rows as $r) { fputcsv($out, $r); }
            fclose($out);
        }, 'ledger.csv', ['Content-Type' => 'text/csv']);
    }
}
