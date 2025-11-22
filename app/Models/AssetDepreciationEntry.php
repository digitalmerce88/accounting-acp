<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','asset_id','period_year','period_month','amount_decimal','posted_journal_entry_id'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class,'asset_id');
    }
}
