<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;

class VendorsController extends Controller
{
    public function search(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $q = trim((string)$request->get('q'));
        if ($q === '') return response()->json(['found'=>false]);
        $item = Vendor::where('business_id',$bizId)
            ->where(function($w) use($q){
                $w->where('tax_id',$q)->orWhere('national_id',$q)->orWhere('phone',$q);
            })->first();
        return response()->json([
            'found' => (bool)$item,
            'item' => $item,
        ]);
    }
}
