<?php
namespace App\Http\Controllers\Admin\Documents;

use App\Domain\Documents\PurchaseService;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Domain\Documents\Numbering;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillsController extends Controller
{
    public function index(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows  = Bill::where('business_id', $bizId)->latest('bill_date')->paginate(12);
        return Inertia::render('Admin/Documents/Bills/Index', ['rows' => $rows]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Documents/Bills/Create');
    }

    public function store(Request $request)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $data  = $request->validate([
            'bill_date'                  => ['required', 'date'],
            'due_date'                   => ['nullable', 'date'],
            'number'                     => ['nullable', 'string', 'max:50'],
            'vendor_id'                  => ['nullable', 'integer'],
            'vendor'                     => ['nullable', 'array'],
            'vendor.name'                => ['nullable', 'string', 'max:200'],
            // National ID removed; tax_id optional
            'vendor.tax_id'              => ['nullable', 'string', 'max:30'],
            'vendor.phone'               => ['nullable', 'string', 'max:30'],
            'vendor.email'               => ['nullable', 'string', 'max:120'],
            'vendor.address'             => ['nullable', 'string', 'max:500'],
            'wht_rate_decimal'           => ['nullable', 'numeric', 'min:0'],
            'wht_amount_decimal'         => ['nullable', 'numeric', 'min:0'],
            'note'                       => ['nullable', 'string', 'max:500'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.name'               => ['required', 'string', 'max:200'],
            'items.*.qty_decimal'        => ['required', 'numeric', 'min:0'],
            'items.*.unit_price_decimal' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate_decimal'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $data['items'];
        unset($data['items']);

        $subtotal = 0.0;
        $vat      = 0.0;
        foreach ($items as $it) {
            $line = (float) $it['qty_decimal'] * (float) $it['unit_price_decimal'];
            $subtotal += $line;
            $vat += $line * ((float) ($it['vat_rate_decimal'] ?? 0) / 100.0);
        }

        $bill = new Bill();
        $bill->fill(array_merge($data, [
            'business_id' => $bizId,
            'subtotal'    => round($subtotal, 2),
            'vat_decimal' => round($vat, 2),
            'total'       => round($subtotal + $vat, 2),
            'status'      => 'draft',
        ]));
        // resolve or create vendor
        if (empty($data['vendor_id']) && ! empty($data['vendor'])) {
            $v        = $data['vendor'];
            $existing = null;
            if (! empty($v['tax_id']) || ! empty($v['phone'])) {
                $existing = \App\Models\Vendor::where('business_id', $bizId)
                    ->when(! empty($v['tax_id']), fn($q) => $q->orWhere('tax_id', $v['tax_id']))
                    ->when(! empty($v['phone']), fn($q) => $q->orWhere('phone', $v['phone']))
                    ->first();
            }
            if ($existing) {
                $bill->vendor_id = $existing->id;
            } elseif (! empty($v['name'])) {
                $created = \App\Models\Vendor::create([
                    'business_id' => $bizId,
                    'name'        => $v['name'],
                    'tax_id'      => $v['tax_id'] ?? null,
                    'phone'       => $v['phone'] ?? null,
                    'email'       => $v['email'] ?? null,
                    'address'     => $v['address'] ?? null,
                ]);
                $bill->vendor_id = $created->id;
            }
        }
        if (empty($bill->number)) {
            $bill->number = Numbering::next('bill', $bizId, $bill->bill_date);
        }
        $bill->save();

        foreach ($items as $it) {
            BillItem::create([
                'bill_id'            => $bill->id,
                'name'               => $it['name'],
                'qty_decimal'        => $it['qty_decimal'],
                'unit_price_decimal' => $it['unit_price_decimal'],
                'vat_rate_decimal'   => $it['vat_rate_decimal'] ?? 0,
            ]);
        }

        return redirect()->route('admin.documents.bills.show', $bill->id)->with('success', 'สร้างบิลแล้ว');
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item  = Bill::where('business_id', $bizId)->with(['items', 'vendor'])->findOrFail($id);
        return Inertia::render('Admin/Documents/Bills/Show', ['item' => $item]);
    }

    public function edit(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $item  = Bill::where('business_id', $bizId)->with(['items', 'vendor'])->findOrFail($id);
        if (in_array($item->status, ['paid', 'void'])) {
            return redirect()->back()->with('error', 'ไม่สามารถแก้ไขเอกสารที่จ่ายแล้ว/ยกเลิกแล้ว');
        }
        return Inertia::render('Admin/Documents/Bills/Edit', ['item' => $item]);
    }

    public function update(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $bill  = Bill::where('business_id', $bizId)->with(['items', 'vendor'])->findOrFail($id);
        if (in_array($bill->status, ['paid', 'void'])) {
            return redirect()->back()->with('error', 'ไม่สามารถแก้ไขเอกสารที่จ่ายแล้ว/ยกเลิกแล้ว');
        }

        $data = $request->validate([
            'bill_date'                  => ['required', 'date'],
            'due_date'                   => ['nullable', 'date'],
            'number'                     => ['nullable', 'string', 'max:50'],
            'vendor_id'                  => ['nullable', 'integer'],
            'vendor'                     => ['nullable', 'array'],
            'vendor.name'                => ['nullable', 'string', 'max:200'],
            'vendor.tax_id'              => ['nullable', 'string', 'max:30'],
            'vendor.phone'               => ['nullable', 'string', 'max:30'],
            'vendor.email'               => ['nullable', 'string', 'max:120'],
            'vendor.address'             => ['nullable', 'string', 'max:500'],
            'wht_rate_decimal'           => ['nullable', 'numeric', 'min:0'],
            'wht_amount_decimal'         => ['nullable', 'numeric', 'min:0'],
            'note'                       => ['nullable', 'string', 'max:500'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.name'               => ['required', 'string', 'max:200'],
            'items.*.qty_decimal'        => ['required', 'numeric', 'min:0'],
            'items.*.unit_price_decimal' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate_decimal'   => ['nullable', 'numeric', 'min:0'],
        ]);
        $items = $data['items'];
        unset($data['items']);

        $subtotal = 0.0;
        $vat      = 0.0;
        foreach ($items as $it) {
            $line = (float) $it['qty_decimal'] * (float) $it['unit_price_decimal'];
            $subtotal += $line;
            $vat += $line * ((float) ($it['vat_rate_decimal'] ?? 0) / 100.0);
        }

        $bill->fill(array_merge($data, [
            'subtotal'    => round($subtotal, 2),
            'vat_decimal' => round($vat, 2),
            'total'       => round($subtotal + $vat, 2),
        ]));
        $bill->save();

        if (! empty($data['vendor'])) {
            $v = $data['vendor'];
            if (! empty($bill->vendor_id)) {
                $bill->vendor->fill([
                    'name'        => $v['name'] ?? $bill->vendor->name,
                    'tax_id'      => $v['tax_id'] ?? $bill->vendor->tax_id,
                    'phone'       => $v['phone'] ?? $bill->vendor->phone,
                    'email'       => $v['email'] ?? $bill->vendor->email,
                    'address'     => $v['address'] ?? $bill->vendor->address,
                ])->save();
            } elseif (! empty($v['name'])) {
                $created = \App\Models\Vendor::create([
                    'business_id' => $bizId,
                    'name'        => $v['name'],
                    'tax_id'      => $v['tax_id'] ?? null,
                    'phone'       => $v['phone'] ?? null,
                    'email'       => $v['email'] ?? null,
                    'address'     => $v['address'] ?? null,
                ]);
                $bill->vendor_id = $created->id;
                $bill->save();
            }
        }

        BillItem::where('bill_id', $bill->id)->delete();
        foreach ($items as $it) {
            BillItem::create([
                'bill_id'            => $bill->id,
                'name'               => $it['name'],
                'qty_decimal'        => $it['qty_decimal'],
                'unit_price_decimal' => $it['unit_price_decimal'],
                'vat_rate_decimal'   => $it['vat_rate_decimal'] ?? 0,
            ]);
        }

        return redirect()->route('admin.documents.bills.show', $bill->id)->with('success', 'บันทึกการแก้ไขแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $bill  = Bill::where('business_id', $bizId)->findOrFail($id);
        if ($bill->status !== 'draft') {
            return redirect()->back()->with('error', 'ลบได้เฉพาะสถานะฉบับร่าง');
        }
        BillItem::where('bill_id', $bill->id)->delete();
        $bill->delete();
        return redirect()->route('admin.documents.bills.index')->with('success', 'ลบบิลแล้ว');
    }

    public function pay(Request $request, int $id, PurchaseService $svc)
    {
        $data  = $request->validate(['date' => ['nullable', 'date'], 'method' => ['nullable', 'in:cash,bank']]);
        $bizId = (int) ($request->user()->business_id ?? 1);
        $svc->markPaid($id, $bizId, $data['date'] ?? now()->toDateString(), $data['method'] ?? 'bank');
        return back()->with('success', 'Bill marked as paid');
    }

    public function pdf(Request $request, int $id)
    {
        $bizId      = (int) ($request->user()->business_id ?? 1);
        $item       = Bill::where('business_id', $bizId)->with(['items', 'vendor'])->findOrFail($id);
        $company    = \App\Models\CompanyProfile::where('business_id', $bizId)->first();
        $companyArr = $company ? [
            'name'          => $company->name,
            'tax_id'        => $company->tax_id,
            'phone'         => $company->phone,
            'email'         => $company->email,
            'address'       => [
                'line1'    => $company->address_line1,
                'line2'    => $company->address_line2,
                'province' => $company->province,
                'postcode' => $company->postcode,
            ],
            'logo_abs_path' => $company->logo_path ? public_path('storage/' . $company->logo_path) : null,
        ] : config('company');
        $filename = 'bill-' . ($item->number ?? $item->id) . '.pdf';
        // Engine selection: query string overrides config default
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('documents.bill_pdf', ['bill' => $item, 'company' => $companyArr, 'engine' => 'mpdf'])->render();
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
                'Content-Disposition' => 'inline; filename="'.$filename+'"'
            ]);
        }
        // Default: Dompdf
        $pdf = Pdf::loadView('documents.bill_pdf', [ 'bill' => $item, 'company' => $companyArr ])
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);
        if ($request->boolean('dl') || $request->boolean('download')) {
            return $pdf->download($filename);
        }
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    public function whtPdf(Request $request, int $id)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $bill = Bill::where('business_id', $bizId)->with('vendor')->findOrFail($id);
        $company = \App\Models\CompanyProfile::where('business_id', $bizId)->first();
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
        // find latest cert for this bill's vendor and period (simple heuristic)
        $date = $request->get('date', $bill->bill_date);
        $periodYear = (int)date('Y', strtotime($date));
        $periodMonth = (int)date('n', strtotime($date));
        $cert = \App\Models\WhtCertificate::where('business_id', $bizId)
            ->where('vendor_id', $bill->vendor_id)
            ->where('period_year', $periodYear)
            ->where('period_month', $periodMonth)
            ->orderByDesc('id')
            ->first();
        if (!$cert) {
            // derive on the fly if not exists
            $cert = new \App\Models\WhtCertificate([
                'business_id' => $bizId,
                'vendor_id' => $bill->vendor_id,
                'period_year' => $periodYear,
                'period_month' => $periodMonth,
                'total_paid' => (float)($bill->total ?? 0),
                'wht_rate_decimal' => (float)($bill->wht_rate_decimal ?? 0),
                'wht_amount' => (float)($bill->wht_amount_decimal ?? 0),
                'form_type' => '3',
                'number' => \App\Domain\Documents\Numbering::next('wht', $bizId, $date),
                'issued_at' => $date,
            ]);
        }
        $vendor = $bill->vendor;
        $filename = 'wht-'.($cert->number ?? ($vendor->id.'-'.$periodYear.$periodMonth)).'.pdf';
        $engine = $request->get('engine', config('documents.pdf_engine', 'dompdf'));
        if ($engine === 'mpdf') {
            $html = view('documents.wht_pdf', ['cert' => $cert, 'vendor'=>$vendor, 'company'=>$companyArr, 'engine'=>'mpdf'])->render();
            $tmpDir = storage_path('app/mpdf'); if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }
            $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','tempDir'=>$tmpDir,'format'=>'A4','default_font_size'=>13,'default_font'=>'garuda']);
            $mpdf->autoScriptToLang = true; $mpdf->autoLangToFont = true; $mpdf->WriteHTML($html);
            if ($request->boolean('dl') || $request->boolean('download')) {
                return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
            }
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$filename.'"']);
        }
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled'=>true,'isRemoteEnabled'=>true])->loadView('documents.wht_pdf', ['cert'=>$cert, 'vendor'=>$vendor, 'company'=>$companyArr]);
        if ($request->boolean('dl') || $request->boolean('download')) { return $pdf->download($filename); }
        return $pdf->stream($filename, ['Attachment'=>false]);
    }
}
