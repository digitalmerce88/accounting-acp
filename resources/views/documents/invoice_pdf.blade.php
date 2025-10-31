<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1 { font-size: 18px; margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 6px; }
    th { background: #f2f2f2; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h1>ใบแจ้งหนี้ / Tax Invoice</h1>
  <div>เลขที่: {{ $inv->number ?? $inv->id }} | วันที่: {{ $inv->issue_date?->format('Y-m-d') }}</div>
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
