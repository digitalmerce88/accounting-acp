<?php

namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AssetCategory;

class AssetCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rows = AssetCategory::where('business_id',$bizId)->orderBy('name')->paginate(15);
        return Inertia::render('Admin/Assets/Categories/Index',[ 'rows'=>$rows ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Assets/Categories/Create');
    }

    public function store(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $data = $request->validate([
            'name' => ['required','string','max:200'],
            'useful_life_months' => ['required','integer','min:1','max:600'],
            'depreciation_method' => ['nullable','in:slm'],
        ]);
        $cat = new AssetCategory();
        $cat->fill($data + ['business_id'=>$bizId,'depreciation_method'=>$data['depreciation_method'] ?? 'slm']);
        $cat->save();
        return redirect()->route('admin.assets.categories.index')->with('success','สร้างหมวดหมู่แล้ว');
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = AssetCategory::where('business_id',$bizId)->findOrFail($id);
        return Inertia::render('Admin/Assets/Categories/Edit',[ 'item'=>$item ]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = AssetCategory::where('business_id',$bizId)->findOrFail($id);
        $data = $request->validate([
            'name' => ['required','string','max:200'],
            'useful_life_months' => ['required','integer','min:1','max:600'],
            'depreciation_method' => ['nullable','in:slm'],
        ]);
        $item->fill($data + ['depreciation_method'=>$data['depreciation_method'] ?? 'slm']);
        $item->save();
        return redirect()->route('admin.assets.categories.index')->with('success','บันทึกการแก้ไขแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = AssetCategory::where('business_id',$bizId)->findOrFail($id);
        if ($item->assets()->exists()) {
            return back()->with('error','มีทรัพย์สินใช้อยู่ ไม่สามารถลบได้');
        }
        $item->delete();
        return redirect()->route('admin.assets.categories.index')->with('success','ลบแล้ว');
    }
}
