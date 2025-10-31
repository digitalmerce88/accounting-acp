<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id','employee_id',
        'earning_basic_decimal','earning_other_decimal',
        'sso_employee_decimal','sso_employer_decimal',
        'wht_decimal','net_pay_decimal','meta_json','note',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
