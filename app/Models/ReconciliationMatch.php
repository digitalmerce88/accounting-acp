<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_id','bank_transaction_id','transaction_id','matched_amount_decimal','method'
    ];

    public function reconciliation() { return $this->belongsTo(Reconciliation::class); }
    public function bankTransaction() { return $this->belongsTo(BankTransaction::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
