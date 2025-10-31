<?php

namespace App\Http\Controllers\Admin\HR;

use App\Domain\HR\PayrollService;
use App\Http\Controllers\Controller;
use App\Models\{PayrollRun, PayrollItem, Employee};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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
}
