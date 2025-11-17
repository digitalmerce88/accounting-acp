<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','bank_account_id','period_start','period_end','statement_balance_decimal','calculated_balance_decimal','difference_decimal','status'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function account() { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
    public function matches() { return $this->hasMany(ReconciliationMatch::class); }
}
