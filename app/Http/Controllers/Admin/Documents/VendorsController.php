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
                $w->where('tax_id',$q)->orWhere('phone',$q);
            })->first();
        return response()->json([
            'found' => (bool)$item,
            'item' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:64',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
        ]);
        $bizId = $request->user()->business_id ?? 1;
        $v = \App\Models\Vendor::create(array_merge($data, ['business_id' => $bizId]));
        return response()->json(['ok' => true, 'item' => $v], 201);
    }
}
