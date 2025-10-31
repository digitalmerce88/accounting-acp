<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','code','name','type','default_amount_decimal','taxable','sso_applicable'
    ];

    protected $casts = [
        'taxable' => 'boolean',
        'sso_applicable' => 'boolean',
    ];
}
