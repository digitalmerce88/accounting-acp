<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PurchaseOrder, PoItem};
use Inertia\Inertia;
use App\Domain\Documents\Numbering;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrdersController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows = PurchaseOrder::where('business_id',$bizId)->latest('issue_date')->paginate(12);
        return Inertia::render('Admin/Documents/PO/Index', ['rows'=>$rows]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Documents/PO/Create');
    }

    public function store(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data = $request->validate([
            'issue_date' => ['required','date'],
            'number' => ['nullable','string','max:50'],
            'vendor_id' => ['nullable','integer'],
            'vendor' => ['nullable','array'],
            'vendor.name' => ['nullable','string','max:200'],
            'vendor.tax_id' => ['nullable','string','max:30'],
            'vendor.phone' => ['nullable','string','max:30'],
            'vendor.email' => ['nullable','string','max:120'],
            'vendor.address' => ['nullable','string','max:500'],
            'note' => ['nullable','string','max:500'],
            'items' => ['required','array','min:1'],
            'items.*.name' => ['required','string','max:200'],
            'items.*.qty_decimal' => ['required','numeric','min:0'],
            'items.*.unit_price_decimal' => ['required','numeric','min:0'],
            'items.*.vat_rate_decimal' => ['nullable','numeric','min:0'],
        ]);
        $items = $data['items']; unset($data['items']);
        $subtotal=0.0; $vat=0.0; foreach($items as $it){ $line=(float)$it['qty_decimal']*(float)$it['unit_price_decimal']; $subtotal+=$line; $vat += $line * ((float)($it['vat_rate_decimal']??0)/100.0);}
        $po = new PurchaseOrder();
        $po->fill(array_merge($data,[
            'business_id'=>$bizId,
            'subtotal'=>round($subtotal,2),
            'vat_decimal'=>round($vat,2),
            'total'=>round($subtotal+$vat,2),
            'status'=>'draft',
        ]));
        // vendor resolve/create
        if (empty($data['vendor_id']) && !empty($data['vendor'])){
            $v = $data['vendor'];
            $existing = null;
            if (!empty($v['tax_id']) || !empty($v['phone'])){
                $existing = \App\Models\Vendor::where('business_id',$bizId)
                    ->when(!empty($v['tax_id']), fn($q)=>$q->orWhere('tax_id',$v['tax_id']))
                    ->when(!empty($v['phone']), fn($q)=>$q->orWhere('phone',$v['phone']))
                    ->first();
            }
            if ($existing) { $po->vendor_id = $existing->id; }
            elseif (!empty($v['name'])){
                $created = \App\Models\Vendor::create([
                    'business_id'=>$bizId,
                    'name'=>$v['name'],'tax_id'=>$v['tax_id']??null,'phone'=>$v['phone']??null,'email'=>$v['email']??null,'address'=>$v['address']??null,
                ]);
                $po->vendor_id = $created->id;
            }
        }
        if (empty($po->number)) { $po->number = Numbering::next('po', $bizId, $po->issue_date); }
        $po->save();
        foreach($items as $it){ PoItem::create(['purchase_order_id'=>$po->id,'name'=>$it['name'],'qty_decimal'=>$it['qty_decimal'],'unit_price_decimal'=>$it['unit_price_decimal'],'vat_rate_decimal'=>$it['vat_rate_decimal']??0]); }
        return redirect()->route('admin.documents.po.show', $po->id)->with('success','สร้างใบสั่งซื้อแล้ว');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = PurchaseOrder::where('business_id',$bizId)->with(['items','vendor'])->findOrFail($id);
        return Inertia::render('Admin/Documents/PO/Show', ['item'=>$item]);
    }
    public function pdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = PurchaseOrder::where('business_id',$bizId)->with(['items'])->findOrFail($id);
        $company = \App\Models\CompanyProfile::where('business_id',$bizId)->first();
        $companyArr = $company ? [
            'name' => $company->name,
            'tax_id' => $company->tax_id,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => [
                'line1' => $company->address_line1,
                'line2' => $company->address_line2,
                'province' => $company->province,
                'postcode' => $company->postcode,
            ],
            'logo_abs_path' => $company->logo_path ? public_path('storage/'.$company->logo_path) : null,
        ] : config('company');
        $filename = 'po-'.($item->number ?? $item->id).'.pdf';
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('documents.po_pdf', ['po' => $item, 'company' => $companyArr, 'engine' => 'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','tempDir'=>$tmpDir,'format'=>'A4','default_font_size'=>13,'default_font'=>'garuda']);
            $mpdf->autoScriptToLang = true; $mpdf->autoLangToFont = true; $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('documents.po_pdf', [ 'po' => $item, 'company' => $companyArr ]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
