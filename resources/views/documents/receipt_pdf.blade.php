<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8" />
<title>Receipt</title>
<style>
@page { margin: 22mm 16mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.85; color: #111; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #ddd; padding: 6px; }
.header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.logo { width: 72px; height: 72px; object-fit: contain; }
.h1 { font-size: 22px; font-weight: 700; }
.small { color: #444; }
.right { text-align: right; }
.stack > div { margin-bottom: 4px; }
</style>
</head>
<body>
  <div class="header">
    @if(!empty($company['logo_abs_path']))
      <img class="logo" src="{{ $company['logo_abs_path'] }}" alt="logo" />
    @endif
    <div>
      <div class="h1">ใบรับเงิน / Receipt</div>
      <div class="small">{{ $company['name'] ?? '' }}</div>
      <div class="small">เลขผู้เสียภาษี: {{ $company['tax_id'] ?? '-' }}</div>
      <div class="small">{{ $company['address']['line1'] ?? '' }} {{ $company['address']['line2'] ?? '' }} {{ $company['address']['province'] ?? '' }} {{ $company['address']['postcode'] ?? '' }}</div>
      <div class="small">โทร {{ $company['phone'] ?? '-' }} | {{ $company['email'] ?? '' }}</div>
    </div>
  </div>

  <table class="table" style="margin-top:8px; margin-bottom:12px">
    <tr>
      <th style="width:50%">ลูกค้า</th>
      <th style="width:25%">เลขที่เอกสาร</th>
      <th style="width:25%">วันที่รับเงิน</th>
    </tr>
    <tr>
      <td>
        <div class="stack">
          <div>{{ $inv->customer->name ?? '-' }}</div>
          <div>เลขผู้เสียภาษี: {{ $inv->customer->tax_id ?? '-' }}</div>
          <div>{{ $inv->customer->address ?? '' }}</div>
          <div>โทร {{ $inv->customer->phone ?? '-' }}</div>
        </div>
      </td>
      <td>{{ $inv->number ?? $inv->id }}</td>
      <td>{{ \Carbon\Carbon::parse($paid_date)->format('d/m/Y') }}</td>
    </tr>
  </table>

  <table class="table">
    <thead>
      <tr>
        <th>รายละเอียด</th>
        <th class="right" style="width:30%">จำนวนเงิน</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>รับชำระค่าสินค้า/บริการ ตามใบแจ้งหนี้เลขที่ {{ $inv->number ?? $inv->id }} (วิธีชำระ: {{ $payment_method==='cash' ? 'เงินสด' : 'โอน/เช็ค' }})</td>
        <td class="right">{{ number_format((float)($inv->total ?? 0),2) }}</td>
      </tr>
      <tr>
        <td class="right"><strong>รวมทั้งสิ้น</strong></td>
        <td class="right"><strong>{{ number_format((float)($inv->total ?? 0),2) }}</strong></td>
      </tr>
    </tbody>
  </table>

  <div style="margin-top:22px; text-align:right">
    ลงชื่อ __________________________ ผู้รับเงิน
    <div class="small">( {{ $company['name'] ?? '' }} )</div>
  </div>
</body>
</html>
