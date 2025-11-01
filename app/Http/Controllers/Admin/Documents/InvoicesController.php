<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Domain\Documents\SalesService;
use App\Domain\Documents\Numbering;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows = Invoice::where('business_id',$bizId)->latest('issue_date')->paginate(12);
        return Inertia::render('Admin/Documents/Invoices/Index', ['rows'=>$rows]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Documents/Invoices/Create');
    }

    public function store(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data = $request->validate([
            'issue_date' => ['required','date'],
            'due_date' => ['nullable','date'],
            'number' => ['nullable','string','max:50'],
            'is_tax_invoice' => ['nullable','boolean'],
            'customer_id' => ['nullable','integer'],
            'customer' => ['nullable','array'],
            'customer.name' => ['nullable','string','max:200'],
            // National ID removed; tax_id optional
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
        ]);

        $items = $data['items'];
        unset($data['items']);

        // normalize booleans
        $data['is_tax_invoice'] = (bool)($data['is_tax_invoice'] ?? false);

        // compute totals
        $subtotal = 0.0; $vat = 0.0;
        foreach ($items as $it) {
            $line = (float)$it['qty_decimal'] * (float)$it['unit_price_decimal'];
            $subtotal += $line;
            $vat += $line * ((float)($it['vat_rate_decimal'] ?? 0) / 100.0);
        }

        $inv = new Invoice();
        $inv->fill(array_merge($data, [
            'business_id' => $bizId,
            'subtotal' => round($subtotal, 2),
            'vat_decimal' => round($vat, 2),
            'total' => round($subtotal + $vat, 2),
            'status' => 'draft',
        ]));
        // resolve or create customer
        if (empty($data['customer_id']) && !empty($data['customer'])) {
            $c = $data['customer'];
            $existing = null;
            if (!empty($c['tax_id']) || !empty($c['phone'])) {
                $existing = \App\Models\Customer::where('business_id',$bizId)
                    ->when(!empty($c['tax_id']), fn($q)=>$q->orWhere('tax_id',$c['tax_id']))
                    ->when(!empty($c['phone']), fn($q)=>$q->orWhere('phone',$c['phone']))
                    ->first();
            }
            if ($existing) {
                $inv->customer_id = $existing->id;
            } elseif (!empty($c['name'])) {
                $created = \App\Models\Customer::create([
                    'business_id' => $bizId,
                    'name' => $c['name'],
                    'tax_id' => $c['tax_id'] ?? null,
                    'phone' => $c['phone'] ?? null,
                    'email' => $c['email'] ?? null,
                    'address' => $c['address'] ?? null,
                ]);
                $inv->customer_id = $created->id;
            }
        }
        // basic auto numbering if not provided: INV-YYYYMM-#### per business per month
        if (empty($inv->number)) {
            $inv->number = Numbering::next('invoice', $bizId, $inv->issue_date);
        }
        $inv->save();

        foreach ($items as $it) {
            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'name' => $it['name'],
                'qty_decimal' => $it['qty_decimal'],
                'unit_price_decimal' => $it['unit_price_decimal'],
                'vat_rate_decimal' => $it['vat_rate_decimal'] ?? 0,
            ]);
        }

        return redirect()->route('admin.documents.invoices.show', $inv->id)->with('success','สร้างใบแจ้งหนี้แล้ว');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        return Inertia::render('Admin/Documents/Invoices/Show', ['item'=>$item]);
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        if (in_array($item->status, ['paid','void'])) {
            return redirect()->back()->with('error','ไม่สามารถแก้ไขเอกสารที่ชำระแล้ว/ยกเลิกแล้ว');
        }
        return Inertia::render('Admin/Documents/Invoices/Edit', ['item'=>$item]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        if (in_array($inv->status, ['paid','void'])) {
            return redirect()->back()->with('error','ไม่สามารถแก้ไขเอกสารที่ชำระแล้ว/ยกเลิกแล้ว');
        }

        $data = $request->validate([
            'issue_date' => ['required','date'],
            'due_date' => ['nullable','date'],
            'number' => ['nullable','string','max:50'],
            'is_tax_invoice' => ['nullable','boolean'],
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
        ]);
        $items = $data['items'];
        unset($data['items']);
        $data['is_tax_invoice'] = (bool)($data['is_tax_invoice'] ?? false);

        $subtotal = 0.0; $vat = 0.0;
        foreach ($items as $it) {
            $line = (float)$it['qty_decimal'] * (float)$it['unit_price_decimal'];
            $subtotal += $line;
            $vat += $line * ((float)($it['vat_rate_decimal'] ?? 0) / 100.0);
        }

        $inv->fill(array_merge($data, [
            'subtotal' => round($subtotal, 2),
            'vat_decimal' => round($vat, 2),
            'total' => round($subtotal + $vat, 2),
        ]));
        $inv->save();

        // update or set customer
        if (!empty($data['customer'])) {
            $c = $data['customer'];
            if (!empty($inv->customer_id)) {
                $inv->customer->fill([
                    'name' => $c['name'] ?? $inv->customer->name,
                    'tax_id' => $c['tax_id'] ?? $inv->customer->tax_id,
                    'phone' => $c['phone'] ?? $inv->customer->phone,
                    'email' => $c['email'] ?? $inv->customer->email,
                    'address' => $c['address'] ?? $inv->customer->address,
                ])->save();
            } elseif (!empty($c['name'])) {
                $created = \App\Models\Customer::create([
                    'business_id' => $bizId,
                    'name' => $c['name'],
                    'tax_id' => $c['tax_id'] ?? null,
                    'phone' => $c['phone'] ?? null,
                    'email' => $c['email'] ?? null,
                    'address' => $c['address'] ?? null,
                ]);
                $inv->customer_id = $created->id;
                $inv->save();
            }
        }

        // replace items
        InvoiceItem::where('invoice_id', $inv->id)->delete();
        foreach ($items as $it) {
            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'name' => $it['name'],
                'qty_decimal' => $it['qty_decimal'],
                'unit_price_decimal' => $it['unit_price_decimal'],
                'vat_rate_decimal' => $it['vat_rate_decimal'] ?? 0,
            ]);
        }

        return redirect()->route('admin.documents.invoices.show', $inv->id)->with('success','บันทึกการแก้ไขแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->findOrFail($id);
        if ($inv->status !== 'draft') {
            return redirect()->back()->with('error','ลบได้เฉพาะสถานะฉบับร่าง');
        }
        InvoiceItem::where('invoice_id', $inv->id)->delete();
        $inv->delete();
        return redirect()->route('admin.documents.invoices.index')->with('success','ลบใบแจ้งหนี้แล้ว');
    }

    public function pay(Request $request, int $id, SalesService $svc)
    {
        $data = $request->validate(['date'=>['nullable','date'],'method'=>['nullable','in:cash,bank']]);
        $bizId = (int) ($request->user()->business_id ?? 1);
        $svc->markPaid($id, $bizId, $data['date'] ?? now()->toDateString(), $data['method'] ?? 'bank');
        return back()->with('success','Invoice marked as paid');
    }

    public function pdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
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
        $filename = 'invoice-'.($item->number ?? $item->id).'.pdf';
        // Engine selection: query string overrides config default
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('documents.invoice_pdf', ['inv' => $item, 'company' => $companyArr, 'engine' => 'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf');
            if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'tempDir' => $tmpDir,
                'format' => 'A4',
                'default_font_size' => 13,
                'default_font' => 'garuda',
            ]);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"'
                ]);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"'
            ]);
        }
        // Default: Dompdf
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('documents.invoice_pdf', [ 'inv' => $item, 'company' => $companyArr ]);
        if ($request->boolean('dl') || $request->boolean('download')) {
            return $pdf->download($filename);
        }
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    public function receipt(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
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
        $filename = 'receipt-'.($item->number ?? $item->id).'.pdf';
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        $paidDate = $request->get('date', now()->toDateString());
        $paymentMethod = $request->get('method', 'bank');
        if ($engine === 'mpdf') {
            $html = view('documents.receipt_pdf', ['inv' => $item, 'company' => $companyArr, 'paid_date' => $paidDate, 'payment_method' => $paymentMethod, 'engine' => 'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','tempDir'=>$tmpDir,'format'=>'A4','default_font_size'=>13,'default_font'=>'garuda']);
            $mpdf->autoScriptToLang = true; $mpdf->autoLangToFont = true; $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('documents.receipt_pdf', [ 'inv' => $item, 'company' => $companyArr, 'paid_date'=>$paidDate, 'payment_method'=>$paymentMethod ]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
