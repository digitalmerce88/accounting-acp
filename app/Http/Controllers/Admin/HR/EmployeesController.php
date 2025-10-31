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
        $rows = Employee::where('business_id', $bizId)
            ->orderBy('name')
            ->paginate(15, ['id','emp_code','name','position','base_salary_decimal','active']);
        return Inertia::render('Admin/HR/Employees/Index', [
            'rows' => $rows,
        ]);
    }
}
