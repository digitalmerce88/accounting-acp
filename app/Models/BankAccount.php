<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','bank_code','account_no','account_name','is_default','opened_at'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'opened_at' => 'date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
