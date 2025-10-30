<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id','name','tax_id','address_text','phone','email','vat_registered_at'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
