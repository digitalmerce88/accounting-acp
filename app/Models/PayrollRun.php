<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','period_month','period_year','status','processed_at','note','posting_entry_id'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
