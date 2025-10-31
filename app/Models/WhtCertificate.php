<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhtCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','vendor_id','period_month','period_year','total_paid','wht_rate_decimal','wht_amount','form_type','number','issued_at'
    ];

    protected $casts = [ 'issued_at' => 'date' ];
}
