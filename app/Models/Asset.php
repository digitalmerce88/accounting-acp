<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','category_id','asset_code','name','purchase_date','purchase_cost_decimal','salvage_value_decimal','useful_life_months','depreciation_method','start_depreciation_date','status','disposal_date'
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class,'category_id');
    }

    public function depreciationEntries()
    {
        return $this->hasMany(AssetDepreciationEntry::class,'asset_id');
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class,'asset_id');
    }
}
