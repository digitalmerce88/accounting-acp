<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id','name','qty_decimal','unit_price_decimal','vat_rate_decimal'
    ];
}
