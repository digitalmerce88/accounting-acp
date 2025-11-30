<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Invoice;
use Illuminate\Support\Facades\Schema;
use App\Models\InvoiceItem;
use App\Domain\Documents\SalesService;
use App\Domain\Documents\Numbering;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Domain\Documents\DocumentCalculator;
use App\Domain\Documents\ApprovalService;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
    $rows = Invoice::where('business_id',$bizId)->with(['customer'])->latest('issue_date')->paginate(12);
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
            'currency_code' => ['nullable','string','size:3'],
            'fx_rate_decimal' => ['nullable','numeric','min:0'],
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
            'discount_type' => ['nullable','in:none,amount,percent'],
            'discount_value_decimal' => ['nullable','numeric','min:0'],
            'deposit_type' => ['nullable','in:none,amount,percent'],
            'deposit_value_decimal' => ['nullable','numeric','min:0'],
        ]);

        $items = $data['items'];
        unset($data['items']);

        // normalize booleans
        $data['is_tax_invoice'] = (bool)($data['is_tax_invoice'] ?? false);

        // compute totals via service
        $calc = DocumentCalculator::compute(
            $items,
            $data['discount_type'] ?? 'none',
            (float)($data['discount_value_decimal'] ?? 0),
            $data['deposit_type'] ?? 'none',
            (float)($data['deposit_value_decimal'] ?? 0)
        );

        $inv = new Invoice();
        // base_total_decimal: แปลงเป็นสกุลฐานแบบง่าย ๆจาก total / fx_rate (ถ้า fx > 0)
        $fx = (float)($data['fx_rate_decimal'] ?? 1);
        $fill = array_merge($data, [
            'business_id' => $bizId,
            'subtotal' => $calc['subtotal'],
            'discount_amount_decimal' => $calc['discount_amount_decimal'],
            'discount_value_decimal' => $calc['discount_value_decimal'],
            'discount_type' => $calc['discount_type'],
            'vat_decimal' => $calc['vat_decimal'],
            'total' => $calc['total'],
            'deposit_amount_decimal' => $calc['deposit_amount_decimal'],
            'deposit_value_decimal' => $calc['deposit_value_decimal'],
            'deposit_type' => $calc['deposit_type'],
            'status' => 'draft',
        ]);
        if (Schema::hasColumn('invoices','base_total_decimal')) {
            $fill['base_total_decimal'] = $fx > 0 ? round($calc['total'] / $fx, 2) : $calc['total'];
        }
        // ensure deposit fields and status included
        // fill now
        $inv->fill($fill);
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
        if ($request->wantsJson()) {
            return response()->json(['item' => $item]);
        }
        return Inertia::render('Admin/Documents/Invoices/Show', ['item'=>$item]);
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        if (in_array($item->status, ['paid','void']) || in_array($item->approval_status, ['approved','locked'])) {
            return redirect()->back()->with('error','ไม่สามารถแก้ไขเอกสารที่ชำระแล้ว/ยกเลิกแล้ว');
        }
        return Inertia::render('Admin/Documents/Invoices/Edit', ['item'=>$item]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->with(['items','customer'])->findOrFail($id);
        if (in_array($inv->status, ['paid','void']) || in_array($inv->approval_status, ['approved','locked'])) {
            return redirect()->back()->with('error','ไม่สามารถแก้ไขเอกสารที่ชำระแล้ว/ยกเลิกแล้ว');
        }

        $data = $request->validate([
            'issue_date' => ['required','date'],
            'due_date' => ['nullable','date'],
            'number' => ['nullable','string','max:50'],
            'is_tax_invoice' => ['nullable','boolean'],
            'currency_code' => ['nullable','string','size:3'],
            'fx_rate_decimal' => ['nullable','numeric','min:0'],
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
        $items = $data['items'];
        unset($data['items']);
        $data['is_tax_invoice'] = (bool)($data['is_tax_invoice'] ?? false);

        // compute totals via service
        $calc = DocumentCalculator::compute(
            $items,
            $data['discount_type'] ?? 'none',
            (float)($data['discount_value_decimal'] ?? 0),
            $data['deposit_type'] ?? 'none',
            (float)($data['deposit_value_decimal'] ?? 0)
        );

        $fx = (float)($data['fx_rate_decimal'] ?? ($inv->fx_rate_decimal ?? 1));
        $inv->fill(array_merge($data, [
            'subtotal' => $calc['subtotal'],
            'discount_amount_decimal' => $calc['discount_amount_decimal'],
            'discount_value_decimal' => $calc['discount_value_decimal'],
            'discount_type' => $calc['discount_type'],
            'vat_decimal' => $calc['vat_decimal'],
            'total' => $calc['total'],
            'base_total_decimal' => $fx > 0 ? round($calc['total'] / $fx, 2) : $calc['total'],
            'deposit_amount_decimal' => $calc['deposit_amount_decimal'],
            'deposit_value_decimal' => $calc['deposit_value_decimal'],
            'deposit_type' => $calc['deposit_type'],
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
        try {
            $svc->markPaid($id, $bizId, $data['date'] ?? now()->toDateString(), $data['method'] ?? 'bank');
            return back()->with('success', __('messages.invoice_paid'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
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

    public function submit(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->findOrFail($id);
        if ($inv->approval_status !== 'draft') {
            return back()->with('error','สถานะไม่ถูกต้องสำหรับการส่งอนุมัติ');
        }
        $comment = (string)($request->get('comment') ?? null);
        ApprovalService::submit($inv, $bizId, (int)$request->user()->id, $comment);
        return back()->with('success','ส่งอนุมัติแล้ว');
    }

    public function approve(Request $request, int $id)
    {
        $user = $request->user();
        if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403, 'ต้องเป็นผู้ดูแลระบบจึงจะอนุมัติได้');
        }
        $bizId = (int) ($user->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->findOrFail($id);
        if ($inv->approval_status !== 'submitted') {
            return back()->with('error','สถานะไม่ถูกต้องสำหรับการอนุมัติ');
        }
        $comment = (string)($request->get('comment') ?? null);
        ApprovalService::approve($inv, $bizId, (int)$user->id, $comment);
        return back()->with('success','อนุมัติแล้ว');
    }

    public function lock(Request $request, int $id)
    {
        $user = $request->user();
        if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) { abort(403); }
        $bizId = (int) ($user->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->findOrFail($id);
        if ($inv->approval_status !== 'approved') { return back()->with('error','ล็อกได้เฉพาะเอกสารที่อนุมัติแล้ว'); }
        ApprovalService::lock($inv, $bizId, (int)$user->id, (string)($request->get('comment') ?? null));
        return back()->with('success','ล็อกเอกสารแล้ว');
    }

    public function unlock(Request $request, int $id)
    {
        $user = $request->user();
        if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) { abort(403); }
        $bizId = (int) ($user->business_id ?? 1);
        $inv = Invoice::where('business_id',$bizId)->findOrFail($id);
        if ($inv->approval_status !== 'locked') { return back()->with('error','ปลดล็อกได้เฉพาะเอกสารที่ถูกล็อก'); }
        ApprovalService::unlock($inv, $bizId, (int)$user->id, (string)($request->get('comment') ?? null));
        return back()->with('success','ปลดล็อกเอกสารแล้ว');
    }
}
