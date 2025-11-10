<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','customer_id','issue_date','number','subject','subtotal','vat_decimal','total','status','note',
        'discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal'
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function items() { return $this->hasMany(QuoteItem::class); }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
