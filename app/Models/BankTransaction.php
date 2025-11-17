<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','bank_account_id','date','amount_decimal','description','reference','raw_payload','matched'
    ];

    protected $casts = [
        'date' => 'date',
        'matched' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function account() { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
}
