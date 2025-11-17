<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','vendor_id','issue_date','number','subtotal','vat_decimal','total','status','approval_status','note',
        'discount_type','discount_value_decimal','discount_amount_decimal','deposit_type','deposit_value_decimal','deposit_amount_decimal',
        'submitted_by','submitted_at','approved_by','approved_at','locked_by','locked_at'
    ];

    protected $casts = [ 'issue_date' => 'date', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'locked_at' => 'datetime' ];

    public function items() { return $this->hasMany(PoItem::class); }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
