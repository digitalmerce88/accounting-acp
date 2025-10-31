<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <style>
    @font-face { font-family: 'THSarabunNew'; src: url('{{ public_path('fonts/THSarabunNew.ttf') }}') format('truetype'); font-weight: normal; font-style: normal; }
    body { font-family: THSarabunNew, DejaVu Sans, sans-serif; font-size: 13px; color:#111; }
    h1 { font-size: 20px; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px; vertical-align: top; }
    th { background: #f5f7fa; }
    .right { text-align: right; }
    .muted { color: #666; }
    .header { margin-bottom: 10px; }
    .brand { background: #12b886; color: white; padding: 8px 10px; border-radius: 4px; display:inline-block; }
    .topgrid td { border: 0; padding: 2px 0; }
    .box { border:1px solid #e5e7eb; border-radius:4px; padding:8px; }
    .totals td { border:0; }
    .totals .label { text-align:right; padding-right:10px; }
    .logo { height: 40px; }
  </style>
</head>
<body>
  @php($company = isset($company) && is_array($company) ? $company : config('company'))
  <table style="border:0; margin-bottom:6px;">
    <tr>
      <td style="border:0; width:60%;">
        <div style="display:flex; align-items:center; gap:10px;">
          @if(!empty($company['logo_abs_path']) && file_exists($company['logo_abs_path']))
            <img class="logo" src="{{ $company['logo_abs_path'] }}" />
          @endif
          <div>
            <div class="brand">ใบวางบิล / Supplier Bill</div>
            <div style="margin-top:6px; font-weight:700;">{{ $company['name'] }}</div>
            <div class="muted">เลขผู้เสียภาษี: {{ $company['tax_id'] }}</div>
            <div class="muted">{{ $company['address']['line1'] ?? '' }} {{ $company['address']['line2'] ?? '' }} {{ $company['address']['province'] ?? '' }} {{ $company['address']['postcode'] ?? '' }}</div>
            <div class="muted">โทร: {{ $company['phone'] }} | อีเมล: {{ $company['email'] }}</div>
          </div>
        </div>
      </td>
      <td style="border:0; width:40%;" class="box">
        <table class="topgrid">
          <tr><td>เลขที่เอกสาร</td><td class="right">{{ $bill->number ?? $bill->id }}</td></tr>
          <tr><td>วันที่</td><td class="right">{{ $bill->bill_date?->format('Y-m-d') }}</td></tr>
          <tr><td>ครบกำหนด</td><td class="right">{{ $bill->due_date?->format('Y-m-d') }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <table style="border:0; margin-bottom:8px;">
    <tr>
      <td class="box" style="width:60%;">
        <div style="font-weight:700; margin-bottom:4px;">ผู้ขาย/ผู้รับเงิน</div>
        <div>{{ $bill->vendor->name ?? '-' }}</div>
        <div class="muted">เลขผู้เสียภาษี/บัตรประชาชน: {{ $bill->vendor->tax_id ?? $bill->vendor->national_id ?? '-' }}</div>
        <div class="muted">โทร: {{ $bill->vendor->phone ?? '-' }}</div>
        <div class="muted">ที่อยู่: {{ $bill->vendor->address ?? '-' }}</div>
      </td>
      <td style="width:40%; border:0;"></td>
    </tr>
  </table>
  <br/>
  <table>
    <thead>
      <tr>
        <th>ชื่อรายการ</th>
        <th class="right" style="width:80px;">จำนวน</th>
        <th class="right" style="width:110px;">ราคาต่อหน่วย</th>
        <th class="right" style="width:70px;">VAT %</th>
        <th class="right" style="width:120px;">เป็นเงิน</th>
      </tr>
    </thead>
    <tbody>
      @foreach($bill->items as $it)
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
  <table class="totals" style="margin-top:10px;">
    <tr>
      <td class="label" style="width:85%;">Subtotal</td>
      <td class="right" style="width: 15%; border:1px solid #ccc; padding:6px;">{{ number_format($bill->subtotal,2) }}</td>
    </tr>
    <tr>
      <td class="label">VAT</td>
      <td class="right" style="border:1px solid #ccc; padding:6px;">{{ number_format($bill->vat_decimal,2) }}</td>
    </tr>
    <tr>
      <td class="label" style="font-weight:700;">Total</td>
      <td class="right" style="border:1px solid #ccc; padding:6px; font-weight:700;">{{ number_format($bill->total,2) }}</td>
    </tr>
  </table>
</body>
</html>
