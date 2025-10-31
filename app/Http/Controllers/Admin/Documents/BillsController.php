<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Bill;
use App\Domain\Documents\PurchaseService;

class BillsController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows = Bill::where('business_id',$bizId)->latest('bill_date')->paginate(12);
        return Inertia::render('Admin/Documents/Bills/Index', ['rows'=>$rows]);
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Bill::where('business_id',$bizId)->with('items')->findOrFail($id);
        return Inertia::render('Admin/Documents/Bills/Show', ['item'=>$item]);
    }

    public function pay(Request $request, int $id, PurchaseService $svc)
    {
        $data = $request->validate(['date'=>['nullable','date'],'method'=>['nullable','in:cash,bank']]);
        $bizId = (int) ($request->user()->business_id ?? 1);
        $svc->markPaid($id, $bizId, $data['date'] ?? now()->toDateString(), $data['method'] ?? 'bank');
        return back()->with('success','Bill marked as paid');
    }
}
