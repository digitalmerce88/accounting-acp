<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Quote, QuoteItem};
use Inertia\Inertia;
use App\Domain\Documents\Numbering;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotesController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
    $rows = Quote::where('business_id',$bizId)->with(['customer'])->latest('issue_date')->paginate(12);
        return Inertia::render('Admin/Documents/Quotes/Index', ['rows'=>$rows]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Documents/Quotes/Create');
    }

    public function store(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data = $request->validate([
            'issue_date' => ['required','date'],
            'number' => ['nullable','string','max:50'],
            'subject' => ['nullable','string','max:200'],
            'customer_id' => ['nullable','integer'],
            'customer' => ['nullable','array'],
            'customer.name' => ['nullable','string','max:200'],
            'customer.tax_id' => ['nullable','string','max:30'],
            'customer.phone' => ['nullable','string','max:30'],
            'customer.email' => ['nullable','string','max:120'],
            'customer.address' => ['nullable','string','max:500'],
            'note' => ['nullable','string','max:500'],
            'items' => ['required','array','min:1'],
            'items.*.name' => ['required','string','max:200'],
            'items.*.qty_decimal' => ['required','numeric','min:0'],
            'items.*.unit_price_decimal' => ['required','numeric','min:0'],
            'items.*.vat_rate_decimal' => ['nullable','numeric','min:0'],
            'discount_type' => ['nullable','in:none,amount,percent'],
            'discount_value_decimal' => ['nullable','numeric','min:0'],
            'deposit_type' => ['nullable','in:none,amount,percent'],
            'deposit_value_decimal' => ['nullable','numeric','min:0'],
        ]);
        $items = $data['items']; unset($data['items']);
        $subtotal = 0.0; $raw_vat = 0.0; $lines = [];
        foreach($items as $it){ $line=(float)$it['qty_decimal']*(float)$it['unit_price_decimal']; $lines[]=$line; $subtotal+=$line; $raw_vat += $line * ((float)($it['vat_rate_decimal']??0)/100.0); }

        // discount
        $discount_type = $data['discount_type'] ?? 'none';
        $discount_value = (float)($data['discount_value_decimal'] ?? 0);
        if ($discount_type === 'percent') { $discount_value = min(max($discount_value,0),100); $discount_amount = $subtotal * ($discount_value/100.0); }
        elseif ($discount_type === 'amount') { $discount_amount = min(max($discount_value,0), $subtotal); }
        else { $discount_amount = 0.0; $discount_value = 0.0; }

        $adjusted_subtotal = $subtotal - $discount_amount;
        $vat = $subtotal > 0 ? $raw_vat * ($adjusted_subtotal / $subtotal) : 0.0;
        $total = $adjusted_subtotal + $vat;

        // deposit
        $deposit_type = $data['deposit_type'] ?? 'none';
        $deposit_value = (float)($data['deposit_value_decimal'] ?? 0);
        if ($deposit_type === 'percent') { $deposit_value = min(max($deposit_value,0),100); $deposit_amount = $total * ($deposit_value/100.0); }
        elseif ($deposit_type === 'amount') { $deposit_amount = min(max($deposit_value,0), $total); }
        else { $deposit_amount = 0.0; $deposit_value = 0.0; }
        $q = new Quote();
        $q->fill(array_merge($data,[
            'business_id'=>$bizId,
            'subtotal'=>round($subtotal,2),
            'discount_amount_decimal' => round($discount_amount,2),
            'discount_value_decimal' => round($discount_value,2),
            'discount_type' => $discount_type,
            'vat_decimal'=>round($vat,2),
            'total'=>round($total,2),
            'deposit_amount_decimal' => round($deposit_amount,2),
            'deposit_value_decimal' => round($deposit_value,2),
            'deposit_type' => $deposit_type,
            'status'=>'draft',
        ]));
        // customer resolve/create
        if (empty($data['customer_id']) && !empty($data['customer'])){
            $c = $data['customer'];
            $existing = null;
            if (!empty($c['tax_id']) || !empty($c['phone'])){
                $existing = \App\Models\Customer::where('business_id',$bizId)
                    ->when(!empty($c['tax_id']), fn($q)=>$q->orWhere('tax_id',$c['tax_id']))
                    ->when(!empty($c['phone']), fn($q)=>$q->orWhere('phone',$c['phone']))
                    ->first();
            }
            if ($existing) { $q->customer_id = $existing->id; }
            elseif (!empty($c['name'])){
                $created = \App\Models\Customer::create([
                    'business_id'=>$bizId,
                    'name'=>$c['name'],'tax_id'=>$c['tax_id']??null,'phone'=>$c['phone']??null,'email'=>$c['email']??null,'address'=>$c['address']??null,
                ]);
                $q->customer_id = $created->id;
            }
        }
        if (empty($q->number)) { $q->number = Numbering::next('quote', $bizId, $q->issue_date); }
        $q->save();
        foreach($items as $it){ QuoteItem::create(['quote_id'=>$q->id,'name'=>$it['name'],'qty_decimal'=>$it['qty_decimal'],'unit_price_decimal'=>$it['unit_price_decimal'],'vat_rate_decimal'=>$it['vat_rate_decimal']??0]); }
        return redirect()->route('admin.documents.quotes.show', $q->id)->with('success','สร้างใบเสนอราคาแล้ว');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Quote::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        if ($request->wantsJson()) {
            return response()->json(['item' => $item]);
        }
        return Inertia::render('Admin/Documents/Quotes/Show', ['item'=>$item]);
    }
    public function pdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Quote::where('business_id',$bizId)->with(['items'])->findOrFail($id);
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
        $filename = 'quote-'.($item->number ?? $item->id).'.pdf';
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('documents.quote_pdf', ['quote' => $item, 'company' => $companyArr, 'engine' => 'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','tempDir'=>$tmpDir,'format'=>'A4','default_font_size'=>13,'default_font'=>'garuda']);
            $mpdf->autoScriptToLang = true; $mpdf->autoLangToFont = true; $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('documents.quote_pdf', [ 'quote' => $item, 'company' => $companyArr ]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
