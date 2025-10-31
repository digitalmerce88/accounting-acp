<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <style>
    /* Thai font embedding: place TTF at public/fonts/THSarabunNew.ttf */
    @font-face {
      font-family: 'THSarabunNew';
      src: url('{{ public_path('fonts/THSarabunNew.ttf') }}') format('truetype');
      font-weight: normal; font-style: normal;
    }
    body { font-family: THSarabunNew, DejaVu Sans, sans-serif; font-size: 14px; }
    h1 { font-size: 18px; margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 6px; }
    th { background: #f2f2f2; }
    .right { text-align: right; }
    .muted { color: #666; }
    .header { margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class="header">
  <h1>ใบแจ้งหนี้ / ใบกำกับภาษี</h1>
    @php($company = config('company'))
    <table style="border:0;">
      <tr>
        <td style="border:0; width:60%; vertical-align:top;">
          <div><strong>{{ $company['name'] }}</strong></div>
          <div class="muted">เลขผู้เสียภาษี: {{ $company['tax_id'] }}</div>
          <div class="muted">{{ $company['address']['line1'] ?? '' }} {{ $company['address']['line2'] ?? '' }} {{ $company['address']['province'] ?? '' }} {{ $company['address']['postcode'] ?? '' }}</div>
          <div class="muted">โทร: {{ $company['phone'] }} | อีเมล: {{ $company['email'] }}</div>
        </td>
        <td style="border:0; width:40%; vertical-align:top;">
          <div><strong>ลูกค้า</strong></div>
          <div>{{ $inv->customer->name ?? '-' }}</div>
          <div class="muted">เลขผู้เสียภาษี/บัตรประชาชน: {{ $inv->customer->tax_id ?? $inv->customer->national_id ?? '-' }}</div>
          <div class="muted">โทร: {{ $inv->customer->phone ?? '-' }}</div>
          <div class="muted">ที่อยู่: {{ $inv->customer->address ?? '-' }}</div>
        </td>
      </tr>
    </table>
    <div>เลขที่: {{ $inv->number ?? $inv->id }} | วันที่: {{ $inv->issue_date?->format('Y-m-d') }}</div>
  </div>
  <br/>
  <table>
    <thead>
      <tr>
        <th>ชื่อรายการ</th>
        <th class="right">จำนวน</th>
        <th class="right">ราคาต่อหน่วย</th>
        <th class="right">VAT %</th>
        <th class="right">เป็นเงิน</th>
      </tr>
    </thead>
    <tbody>
      @foreach($inv->items as $it)
      <tr>
        <td>{{ $it->name }}</td>
        <td class="right">{{ number_format($it->qty_decimal,2) }}</td>
        <td class="right">{{ number_format($it->unit_price_decimal,2) }}</td>
        <td class="right">{{ number_format($it->vat_rate_decimal,2) }}</td>
        <td class="right">{{ number_format($it->qty_decimal * $it->unit_price_decimal,2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <br/>
  <table>
    <tr>
      <td class="right">Subtotal</td>
      <td class="right" style="width: 120px;">{{ number_format($inv->subtotal,2) }}</td>
    </tr>
    <tr>
      <td class="right">VAT</td>
      <td class="right">{{ number_format($inv->vat_decimal,2) }}</td>
    </tr>
    <tr>
      <td class="right"><strong>Total</strong></td>
      <td class="right"><strong>{{ number_format($inv->total,2) }}</strong></td>
    </tr>
  </table>
</body>
</html>
