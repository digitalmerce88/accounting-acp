<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','customer_id','issue_date','number','subject','subtotal','vat_decimal','total','status','note'
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function items() { return $this->hasMany(QuoteItem::class); }
}
