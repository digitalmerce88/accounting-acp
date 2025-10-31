<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeesController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $status = $request->query('status', 'active');
        $q = Employee::where('business_id', $bizId);
        if ($status === 'inactive') $q->where('active', false); else $q->where('active', true);
        $rows = $q->orderBy('name')->paginate(15, ['id','emp_code','name','position','base_salary_decimal','active']);
        return Inertia::render('Admin/HR/Employees/Index', [
            'rows' => $rows,
            'status' => $status,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/HR/Employees/Create', [
            'today' => now()->toDateString(),
            'banks' => config('hr.th_banks'),
        ]);
    }

    public function store(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data = $request->validate([
            'emp_code' => ['nullable','string','max:50'],
            'name' => ['required','string','max:255'],
            'position' => ['nullable','string','max:100'],
            'citizen_id' => ['nullable','string','max:30'],
            'start_date' => ['nullable','date'],
            'base_salary_decimal' => ['required','numeric','min:0'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:50'],
            'sso_enabled' => ['required','boolean'],
            'bank' => ['nullable','array'],
            'bank.code' => ['nullable','string','max:20'],
            'bank.name' => ['nullable','string','max:100'],
            'bank.number' => ['nullable','string','max:100'],
            'tax' => ['nullable','array'],
            'tax.wht_fixed_decimal' => ['nullable','numeric','min:0'],
        ]);

        Employee::create([
            'business_id' => $bizId,
            'emp_code' => $data['emp_code'] ?? null,
            'name' => $data['name'],
            'position' => $data['position'] ?? null,
            'citizen_id' => $data['citizen_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'base_salary_decimal' => $data['base_salary_decimal'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'sso_enabled' => (bool)$data['sso_enabled'],
            'bank_account_json' => $data['bank'] ?? null,
            'tax_profile_json' => $data['tax'] ?? null,
            'active' => true,
        ]);

        return redirect()->route('admin.hr.employees.index')->with('success', 'เพิ่มพนักงานเรียบร้อย');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $e = Employee::where('business_id',$bizId)->findOrFail($id);
        return Inertia::render('Admin/HR/Employees/Show', [
            'item' => $e,
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $e = Employee::where('business_id',$bizId)->findOrFail($id);
        return Inertia::render('Admin/HR/Employees/Edit', [
            'item' => $e,
            'banks' => config('hr.th_banks'),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $e = Employee::where('business_id',$bizId)->findOrFail($id);
        $data = $request->validate([
            'emp_code' => ['nullable','string','max:50'],
            'name' => ['required','string','max:255'],
            'position' => ['nullable','string','max:100'],
            'citizen_id' => ['nullable','string','max:30'],
            'start_date' => ['nullable','date'],
            'base_salary_decimal' => ['required','numeric','min:0'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:50'],
            'sso_enabled' => ['required','boolean'],
            'bank' => ['nullable','array'],
            'bank.code' => ['nullable','string','max:20'],
            'bank.name' => ['nullable','string','max:100'],
            'bank.number' => ['nullable','string','max:100'],
            'active' => ['nullable','boolean'],
            'tax' => ['nullable','array'],
            'tax.wht_fixed_decimal' => ['nullable','numeric','min:0'],
        ]);
        $e->update([
            'emp_code' => $data['emp_code'] ?? null,
            'name' => $data['name'],
            'position' => $data['position'] ?? null,
            'citizen_id' => $data['citizen_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'base_salary_decimal' => $data['base_salary_decimal'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'sso_enabled' => (bool)$data['sso_enabled'],
            'bank_account_json' => $data['bank'] ?? null,
            'tax_profile_json' => $data['tax'] ?? null,
            'active' => array_key_exists('active', $data) ? (bool)$data['active'] : $e->active,
        ]);
        return redirect()->route('admin.hr.employees.index')->with('success', 'อัปเดตข้อมูลพนักงานเรียบร้อย');
    }

    public function destroy(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $e = Employee::where('business_id',$bizId)->findOrFail($id);
        $e->update(['active' => false]);
        return back()->with('success', 'ปิดใช้งานพนักงานแล้ว');
    }

    public function restore(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $e = Employee::where('business_id',$bizId)->findOrFail($id);
        $e->update(['active' => true]);
        return back()->with('success', 'เปิดใช้งานพนักงานแล้ว');
    }
}
