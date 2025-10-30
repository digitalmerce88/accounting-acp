<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosingPeriod extends Model
{
    use HasFactory;
    protected $fillable = ['business_id','period_month','period_year','closed_at','note'];
}
