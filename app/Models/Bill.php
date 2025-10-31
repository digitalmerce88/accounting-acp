<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','vendor_id','bill_date','due_date','number','subtotal','vat_decimal','total','wht_rate_decimal','wht_amount_decimal','status','note'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
    ];

    public function items() { return $this->hasMany(BillItem::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
}
