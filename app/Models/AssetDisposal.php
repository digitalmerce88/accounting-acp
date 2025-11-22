<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','asset_id','disposal_date','proceed_amount_decimal','gain_loss_decimal','journal_entry_id'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class,'asset_id');
    }
}
