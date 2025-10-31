<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','customer_id','issue_date','due_date','number','is_tax_invoice','subtotal','vat_decimal','total','status','note'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'is_tax_invoice' => 'boolean',
    ];

    public function items() { return $this->hasMany(InvoiceItem::class); }
}
