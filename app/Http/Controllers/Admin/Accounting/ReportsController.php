<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;
use App\Domain\Accounting\Reports\Ledger as LedgerReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Domain\Accounting\Services\ReportPdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            // Thai CSV headers for user-friendly export
            fputcsv($out, ['เลขที่บัญชี','ชื่อบัญชี','ประเภท','เดบิต','เครดิต']);
            foreach ($rows as $r) {
                // map account type to Thai for CSV
                if (isset($r[2])) {
                    $r[2] = (function($t){
                        return match ($t) {
                            'asset' => 'สินทรัพย์',
                            'liability' => 'หนี้สิน',
                            'equity' => 'ทุน',
                            'income', 'revenue' => 'รายได้',
                            'expense' => 'ค่าใช้จ่าย',
                            default => $t,
                        };
                    })($r[2]);
                }
                fputcsv($out, $r);
            }
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
            // Thai CSV headers for user-friendly export
            fputcsv($out, ['วันที่','เลขที่รายการ','บันทึก','เดบิต','เครดิต','ยอดคงเหลือ']);
            foreach ($rows as $r) { fputcsv($out, $r); }
            fclose($out);
        }, 'ledger.csv', ['Content-Type' => 'text/csv']);
    }

    public function trialBalancePdf(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $rows = (new TrialBalanceReport())->run($from, $to);
        // map types
        foreach ($rows as &$r) { if (isset($r[2])) $r[2] = $this->mapType($r[2]); }
        $pdfSvc = new ReportPdfService();
        $pdf = $pdfSvc->trialBalance(['rows' => $rows, 'from' => $from, 'to' => $to]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="งบทดลอง.pdf"',
        ]);
    }

    public function ledgerPdf(Request $request)
    {
        $accountId = (int) $request->query('account_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $rows = (new LedgerReport())->run($accountId, $from, $to);
        $pdfSvc = new ReportPdfService();
        $pdf = $pdfSvc->ledger(['rows' => $rows, 'account_id' => $accountId, 'from' => $from, 'to' => $to]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="สมุดบัญชีแยกประเภท.pdf"',
        ]);
    }

    private function mapType($t)
    {
        return match ($t) {
            'asset' => 'สินทรัพย์',
            'liability' => 'หนี้สิน',
            'equity' => 'ทุน',
            'income', 'revenue' => 'รายได้',
            'expense' => 'ค่าใช้จ่าย',
            default => $t,
        };
    }
}
