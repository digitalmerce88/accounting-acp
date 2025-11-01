<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8" />
<title>WHT Certificate</title>
<style>
@page { margin: 22mm 16mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.85; color: #111; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #ddd; padding: 6px; }
.h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
.small { color: #444; }
.right { text-align: right; }
</style>
</head>
<body>
  <div class="h1">หนังสือรับรองการหักภาษี ณ ที่จ่าย (สรุป)</div>
  <div class="small">ผู้จ่ายเงิน: {{ $company['name'] ?? '' }} | เลขผู้เสียภาษี: {{ $company['tax_id'] ?? '-' }}</div>
  <div class="small">ผู้รับเงิน: {{ $vendor->name ?? '' }} | เลขผู้เสียภาษี: {{ $vendor->tax_id ?? '-' }}</div>
  <div class="small">งวด: {{ sprintf('%02d',$cert->period_month) }}/{{ $cert->period_year }} | เลขที่ใบรับรอง: {{ $cert->number ?? '-' }} | แบบฟอร์ม: ภ.ง.ด.{{ $cert->form_type }}</div>
  <div class="small">ออกให้เมื่อ: {{ \Carbon\Carbon::parse($cert->issued_at)->format('d/m/Y') }}</div>

  <table class="table" style="margin-top:12px">
    <thead>
      <tr>
        <th>ฐานภาษี</th>
        <th class="right">อัตราภาษี (%)</th>
        <th class="right">ภาษีที่หัก (บาท)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ number_format((float)$cert->total_paid,2) }}</td>
        <td class="right">{{ number_format((float)$cert->wht_rate_decimal,2) }}</td>
        <td class="right">{{ number_format((float)$cert->wht_amount,2) }}</td>
      </tr>
    </tbody>
  </table>

  <div style="margin-top:22px; text-align:right">
    ลงชื่อ __________________________ ผู้มีอำนาจลงนาม
    <div class="small">( {{ $company['name'] ?? '' }} )</div>
  </div>
</body>
</html>
