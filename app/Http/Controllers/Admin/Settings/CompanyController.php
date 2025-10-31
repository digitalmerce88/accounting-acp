<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CompanyProfile;

class CompanyController extends Controller
{
    public function edit(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = CompanyProfile::where('business_id',$bizId)->first();
        return Inertia::render('Admin/Settings/Company/Edit', [ 'item' => $item ]);
    }

    public function update(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data = $request->validate([
            'name' => ['required','string','max:200'],
            'tax_id' => ['nullable','string','max:30'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','string','max:120'],
            'address_line1' => ['nullable','string','max:200'],
            'address_line2' => ['nullable','string','max:200'],
            'province' => ['nullable','string','max:120'],
            'postcode' => ['nullable','string','max:20'],
        ]);
        $item = CompanyProfile::updateOrCreate(
            ['business_id' => $bizId],
            array_merge($data, ['business_id'=>$bizId])
        );
        return redirect()->route('admin.settings.company.edit')->with('success','บันทึกข้อมูลบริษัทแล้ว');
    }
}
