<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','kind','date','memo','amount','vat','wht','category_id','customer_id','vendor_id',
        'payment_method','bank_account_id','price_input_mode','vat_applicable','wht_rate','status','journal_entry_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'vat_applicable' => 'boolean',
        'amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'wht' => 'decimal:2',
        'wht_rate' => 'decimal:4',
        //'attachments_json' => 'array',
    ];

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
