<?php

namespace App\Http\Controllers\Admin\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\{Asset, AssetCategory};
use App\Services\AssetDisposalService;

class AssetsController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rows = Asset::where('business_id',$bizId)->with(['category'])->latest('purchase_date')->paginate(15);
        return Inertia::render('Admin/Assets/Assets/Index',[ 'rows'=>$rows ]);
    }

    public function create(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $categories = AssetCategory::where('business_id',$bizId)->orderBy('name')->get(['id','name']);
        return Inertia::render('Admin/Assets/Assets/Create',[ 'categories'=>$categories ]);
    }

    public function store(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $data = $request->validate([
            'asset_code' => ['required','string','max:50'],
            'name' => ['required','string','max:200'],
            'purchase_date' => ['required','date'],
            'purchase_cost_decimal' => ['required','numeric','min:0'],
            'salvage_value_decimal' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','integer','exists:asset_categories,id'],
            'useful_life_months' => ['required','integer','min:1','max:600'],
            'start_depreciation_date' => ['required','date'],
        ]);
        $asset = new Asset();
        $asset->fill($data + [
            'business_id'=>$bizId,
            'depreciation_method'=>'slm',
            'status'=>'active',
        ]);
        $asset->save();
        return redirect()->route('admin.assets.assets.show',$asset->id)->with('success','สร้างทรัพย์สินแล้ว');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = Asset::where('business_id',$bizId)->with(['category','depreciationEntries'])->findOrFail($id);
        return Inertia::render('Admin/Assets/Assets/Show',[ 'item'=>$item ]);
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = Asset::where('business_id',$bizId)->findOrFail($id);
        if ($item->status === 'disposed') { return back()->with('error','ทรัพย์สินจำหน่ายแล้ว ไม่สามารถแก้ไข'); }
        $categories = AssetCategory::where('business_id',$bizId)->orderBy('name')->get(['id','name']);
        return Inertia::render('Admin/Assets/Assets/Edit',[ 'item'=>$item,'categories'=>$categories ]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = Asset::where('business_id',$bizId)->findOrFail($id);
        if ($item->status === 'disposed') { return back()->with('error','ทรัพย์สินจำหน่ายแล้ว ไม่สามารถแก้ไข'); }
        $data = $request->validate([
            'asset_code' => ['required','string','max:50'],
            'name' => ['required','string','max:200'],
            'purchase_date' => ['required','date'],
            'purchase_cost_decimal' => ['required','numeric','min:0'],
            'salvage_value_decimal' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','integer','exists:asset_categories,id'],
            'useful_life_months' => ['required','integer','min:1','max:600'],
            'start_depreciation_date' => ['required','date'],
        ]);
        $item->fill($data);
        $item->save();
        return redirect()->route('admin.assets.assets.show',$item->id)->with('success','บันทึกการแก้ไขแล้ว');
    }

    public function dispose(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = Asset::where('business_id',$bizId)->findOrFail($id);
        if ($item->status === 'disposed') { return back()->with('error','จำหน่ายแล้ว'); }
        $data = $request->validate([
            'proceed_amount_decimal' => ['required','numeric','min:0'],
            'date' => ['nullable','date'],
        ]);
        $date = $data['date'] ?? now()->toDateString();
        $proceed = (float)$data['proceed_amount_decimal'];
        AssetDisposalService::dispose($item, new \DateTime($date), $proceed);
        return redirect()->route('admin.assets.assets.show',$item->id)->with('success','จำหน่ายทรัพย์สินแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $item = Asset::where('business_id',$bizId)->findOrFail($id);
        if ($item->depreciationEntries()->exists()) { return back()->with('error','มีค่าเสื่อมแล้ว ไม่สามารถลบ'); }
        $item->delete();
        return redirect()->route('admin.assets.assets.index')->with('success','ลบทรัพย์สินแล้ว');
    }
}
