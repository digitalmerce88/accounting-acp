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
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'remove_logo' => ['nullable','boolean'],
        ]);
        $item = CompanyProfile::firstOrCreate(['business_id'=>$bizId], ['name'=>$data['name']]);
        $item->fill($data);

        // handle logo upload/remove
        if (($data['remove_logo'] ?? false) && $item->logo_path) {
            \Storage::disk('public')->delete($item->logo_path);
            $item->logo_path = null;
        }
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company-logos','public');
            if ($item->logo_path && $item->logo_path !== $path) {
                \Storage::disk('public')->delete($item->logo_path);
            }
            $item->logo_path = $path;
        }
        $item->business_id = $bizId;
        $item->save();
        return redirect()->route('admin.settings.company.edit')->with('success','บันทึกข้อมูลบริษัทแล้ว');
    }
}
