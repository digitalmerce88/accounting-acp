<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','name','useful_life_months','depreciation_method'
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class,'category_id');
    }
}
