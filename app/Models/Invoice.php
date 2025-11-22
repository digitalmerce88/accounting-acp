<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','customer_id','issue_date','due_date','number','is_tax_invoice','subtotal','vat_decimal','total','status','approval_status','note',
        'discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal',
        'submitted_by','submitted_at','approved_by','approved_at','locked_by','locked_at',
        // multi-currency
        'currency_code','fx_rate_decimal','base_total_decimal'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'is_tax_invoice' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
