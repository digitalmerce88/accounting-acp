<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','emp_code','name','citizen_id','start_date','position',
        'base_salary_decimal','bank_account_json','email','phone','tax_profile_json',
        'sso_enabled','active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'bank_account_json' => 'array',
        'tax_profile_json' => 'array',
        'sso_enabled' => 'boolean',
        'active' => 'boolean',
    ];
}
