<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','vendor_id','bill_date','due_date','number','subtotal','vat_decimal','total','wht_rate_decimal','wht_amount_decimal','status','approval_status','note',
        'discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal',
        'submitted_by','submitted_at','approved_by','approved_at','locked_by','locked_at'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function items() { return $this->hasMany(BillItem::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
}
