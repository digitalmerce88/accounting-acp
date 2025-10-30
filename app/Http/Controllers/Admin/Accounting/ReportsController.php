<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;
use App\Domain\Accounting\Reports\Ledger as LedgerReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Domain\Accounting\Services\ReportPdfService;
use App\Domain\Accounting\Services\SummaryReportService;
use App\Domain\Accounting\Services\ProfitAndLossService;
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

    // S3 Reports
    public function overview(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->overview($request->all());
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/Overview', $data);
    }

    public function byCategory(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->byCategory($request->all());
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/Category', $data);
    }

    public function taxPurchaseVat(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->taxPurchaseVat($request->all());
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/TaxPurchase', $data);
    }

    public function taxSalesVat(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->taxSalesVat($request->all());
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/TaxSales', $data);
    }

    public function whtSummary(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->whtSummary($request->all());
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/WhtSummary', $data);
    }

    // CSV endpoints
    public function overviewCsv(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->overview($request->all());
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="overview.csv"',
        ];
        $callback = function() use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['เริ่ม','สิ้นสุด','รายรับ','รายจ่าย','กำไรสุทธิ']);
            fputcsv($out, [$data['start'],$data['end'], $data['income'], $data['expense'], $data['net']]);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function byCategoryCsv(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->byCategory($request->all());
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="by-category.csv"',
        ];
        $callback = function() use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['โค้ด','ชื่อบัญชี','ประเภท','จำนวน']);
            foreach ($data['rows'] as $r) {
                fputcsv($out, [$r['code'],$r['name'],$r['type'],$r['amount']]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function taxPurchaseVatCsv(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->taxPurchaseVat($request->all());
        return $this->singleAmountCsv('purchase-vat.csv', 'ภาษีซื้อ', $data);
    }

    public function taxSalesVatCsv(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->taxSalesVat($request->all());
        return $this->singleAmountCsv('sales-vat.csv', 'ภาษีขาย', $data);
    }

    public function whtSummaryCsv(Request $request)
    {
        $svc = new SummaryReportService();
        $data = $svc->whtSummary($request->all());
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="wht-summary.csv"',
        ];
        $callback = function() use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['เริ่ม','สิ้นสุด','WHT รับ','WHT จ่าย']);
            fputcsv($out, [$data['start'],$data['end'],$data['wht_received'],$data['wht_payable']]);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function singleAmountCsv(string $filename, string $label, array $data)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $callback = function() use ($data, $label) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['เริ่ม','สิ้นสุด',$label]);
            fputcsv($out, [$data['start'],$data['end'],$data['amount']]);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
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

    public function profitAndLoss(Request $request)
    {
        $svc = new ProfitAndLossService();
        $data = $svc->run($request->query('from'), $request->query('to'));
        if ($request->wantsJson()) return response()->json($data);
        return Inertia::render('Admin/Accounting/Reports/ProfitAndLoss', $data);
    }

    public function profitAndLossCsv(Request $request)
    {
        $svc = new ProfitAndLossService();
        $data = $svc->run($request->query('from'), $request->query('to'));
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="profit-and-loss.csv"',
        ];
        $callback = function() use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ตั้งแต่','ถึง','รายได้','ค่าใช้จ่าย','กำไรสุทธิ']);
            fputcsv($out, [$data['from'],$data['to'],$data['revenue'],$data['expense'],$data['net']]);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
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
