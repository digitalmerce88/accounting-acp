<?php

namespace App\Http\Controllers\Admin\HR;

use App\Domain\HR\PayrollService;
use App\Http\Controllers\Controller;
use App\Models\{PayrollRun, PayrollItem, Employee};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows = PayrollRun::where('business_id', $bizId)
            ->orderByDesc('period_year')->orderByDesc('period_month')
            ->paginate(12);
        return Inertia::render('Admin/HR/Payroll/Index', [
            'rows' => $rows,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, PayrollService $svc)
    {
        $data = $request->validate([
            'year' => ['nullable','integer','min:2000','max:2100'],
            'month' => ['nullable','integer','min:1','max:12'],
        ]);
        $bizId = (int) ($request->user()->business_id ?? 1);
        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);
        $run = $svc->createRun($bizId, $year, $month);
        return redirect()->to("/admin/hr/payroll/{$run->id}");
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $run = PayrollRun::where('business_id',$bizId)->findOrFail($id);
        $items = PayrollItem::where('payroll_run_id',$run->id)->with('employee:id,name,emp_code,position')
            ->orderBy('id')->get();
        if ($request->wantsJson()) {
            return response()->json(['run' => $run, 'items' => $items]);
        }
        return Inertia::render('Admin/HR/Payroll/Show', [
            'run' => $run,
            'items' => $items,
        ]);
    }

    public function lock(Request $request, int $id, PayrollService $svc)
    {
        $svc->lock($id);
        return back()->with('success', 'ล็อกรอบเงินเดือนแล้ว');
    }

    public function unlock(Request $request, int $id)
    {
        $run = PayrollRun::findOrFail($id);
        if ($run->status !== 'locked') {
            return back()->with('error', 'ปลดล็อคได้เฉพาะรอบที่ถูกล็อค');
        }
        $run->status = 'draft';
        $run->save();
        return back()->with('success', 'ปลดล็อครอบเงินเดือนแล้ว');
    }

    public function pay(Request $request, int $id, PayrollService $svc)
    {
        $date = $request->validate(['date' => ['nullable','date']]);
        $bizId = (int) ($request->user()->business_id ?? 1);
        $svc->pay($id, $bizId, $date['date'] ?? now()->toDateString());
        return back()->with('success', 'จ่ายเงินเดือนเรียบร้อย');
    }

    public function destroy(Request $request, int $id)
    {
        $run = PayrollRun::with('items')->findOrFail($id);
        if ($run->status !== 'draft') {
            return back()->with('error', 'ลบได้เฉพาะรอบที่เป็น draft');
        }
        DB::transaction(function() use ($run) {
            PayrollItem::where('payroll_run_id', $run->id)->delete();
            $run->delete();
        });
        return redirect()->route('admin.hr.payroll.index')->with('success', 'ลบรอบเงินเดือนแล้ว');
    }

    public function summaryPdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $run = PayrollRun::where('business_id',$bizId)->findOrFail($id);
        $items = PayrollItem::where('payroll_run_id',$run->id)->with('employee:id,name,emp_code,position')->orderBy('id')->get();
        $company = \App\Models\CompanyProfile::where('business_id',$bizId)->first();
        $companyArr = $company ? [
            'name' => $company->name,
            'tax_id' => $company->tax_id,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => [
                'line1' => $company->address_line1,
                'line2' => $company->address_line2,
                'province' => $company->province,
                'postcode' => $company->postcode,
            ],
            'logo_abs_path' => $company->logo_path ? public_path('storage/'.$company->logo_path) : null,
        ] : config('company');

        $filename = sprintf('payroll-%04d-%02d-summary.pdf', $run->period_year, $run->period_month);
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('hr.payroll_summary_pdf', compact('run','items','companyArr') + ['engine'=>'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','tempDir'=>$tmpDir,'format'=>'A4','default_font_size'=>13,'default_font'=>'garuda']);
            $mpdf->autoScriptToLang = true; $mpdf->autoLangToFont = true; $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('hr.payroll_summary_pdf', [ 'run'=>$run, 'items'=>$items, 'companyArr'=>$companyArr ]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    public function payslipsPdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $run = PayrollRun::where('business_id',$bizId)->findOrFail($id);
        $items = PayrollItem::where('payroll_run_id',$run->id)->with('employee')->orderBy('id')->get();
        $empIds = $items->pluck('employee_id')->filter()->unique()->values();
        // YTD up to current run month in the same year
        $ytdRows = \App\Models\PayrollItem::query()
            ->selectRaw('payroll_items.employee_id as employee_id, '
                . 'SUM(earning_basic_decimal + IFNULL(earning_other_decimal,0)) as ytd_income, '
                . 'SUM(sso_employee_decimal + wht_decimal) as ytd_deduction, '
                . 'SUM(wht_decimal) as ytd_tax, '
                . 'SUM(sso_employee_decimal) as ytd_ssf')
            ->join('payroll_runs','payroll_runs.id','=','payroll_items.payroll_run_id')
            ->where('payroll_runs.business_id',$bizId)
            ->where('payroll_runs.period_year',$run->period_year)
            ->where('payroll_runs.period_month','<=',$run->period_month)
            ->whereIn('payroll_items.employee_id',$empIds)
            ->groupBy('payroll_items.employee_id')
            ->get();
        $ytd = collect($ytdRows)->keyBy('employee_id');
        $company = \App\Models\CompanyProfile::where('business_id',$bizId)->first();
        $companyArr = $company ? [
            'name' => $company->name,
            'tax_id' => $company->tax_id,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => [
                'line1' => $company->address_line1,
                'line2' => $company->address_line2,
                'province' => $company->province,
                'postcode' => $company->postcode,
            ],
            'logo_abs_path' => $company->logo_path ? public_path('storage/'.$company->logo_path) : null,
        ] : config('company');
        $filename = sprintf('payroll-%04d-%02d-payslips.pdf', $run->period_year, $run->period_month);
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        $asOfDate = optional($run->processed_at)->toDateString() ?? now()->toDateString();
        if ($engine === 'mpdf') {
            $html = view('hr.payslips_pdf', compact('run','items','companyArr','ytd','asOfDate') + ['engine'=>'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            // Use separate format + orientation to avoid mPDF parsing issues with 'A5-L'
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'tempDir' => $tmpDir,
                'format' => 'A5',
                'orientation' => 'L',
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 12,
                'margin_right' => 12,
                'default_font_size' => 11,
                'default_font' => 'garuda',
            ]);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->setPaper('a5', 'landscape')->loadView('hr.payslips_pdf', [ 'run'=>$run, 'items'=>$items, 'companyArr'=>$companyArr, 'ytd'=>$ytd, 'asOfDate'=>$asOfDate ]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
