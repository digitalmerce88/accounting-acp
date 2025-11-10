<!doctype html>
<html>

<head>
  <meta charset="utf-8" />
  <style>
    @if (empty($engine) || $engine !== 'mpdf')
      @font-face {
        font-family: 'THSarabunNew';
        src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew.ttf'))) }}') format('truetype');
        font-weight: normal;
        font-style: normal;
      }

      @font-face {
        font-family: 'THSarabunNew';
        src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew Bold.ttf'))) }}') format('truetype');
        font-weight: bold;
        font-style: normal;
      }

      @font-face {
        font-family: 'THSarabunNew';
        src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew Italic.ttf'))) }}') format('truetype');
        font-weight: normal;
        font-style: italic;
      }

      @font-face {
        font-family: 'THSarabunNew';
        src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew BoldItalic.ttf'))) }}') format('truetype');
        font-weight: bold;
        font-style: italic;
      }
    @endif
    @page {
      margin: 16mm 14mm 18mm 14mm;
    }

    body {
      font-family: {{ isset($engine) && $engine === 'mpdf' ? 'Garuda, DejaVu Sans, sans-serif' : 'THSarabunNew, DejaVu Sans, sans-serif' }};
      font-size: 13px;
      line-height: 1.5;
      color: #111;
    }

    h1 {
      font-size: 20px;
      margin: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #ccc;
      padding: 6px;
      vertical-align: top;
      line-height: 1.5;
    }

    th {
      background: #f5f7fa;
    }

    .right {
      text-align: right;
    }

    .muted {
      color: #666;
    }

    .header {
      margin-bottom: 10px;
    }

    .brand {
      font-size: 14px;
      background: #0ea5a8;
      color: white;
      padding: 8px 10px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 6px;
    }

    .stack>div {
      margin: 3px 0;
    }

    .topgrid td {
      border: 0;
      padding: 2px 0;
    }

    .box {
      border: 1px solid #e5e7eb;
      border-radius: 4px;
      padding: 8px;
    }

    .totals td {
      border: 0;
    }

    .totals .label {
      text-align: right;
      padding-right: 10px;
    }

    .logo {
      height: 40px;
    }

    .stack>div {
      margin: 6px 0;
    }
    .empty-row td {
      border: none;
      height: 18px;
    }
  </style>
</head>

<body>
  @php($company = isset($company) && is_array($company) ? $company : config('company'))
  <table style="border:0; margin-bottom:6px;">
    <tr>
      <td style="border:0 width:50%;">
        <div style="display:flex; align-items:center; gap:10px;">
          @if (!empty($company['logo_abs_path']) && file_exists($company['logo_abs_path']))
            <img class="logo" src="{{ $company['logo_abs_path'] }}" style="width: 20%; height: auto;" />
          @endif
        </div>
      </td>
      <td style="border:0; width:10%;" class="box">
      </td>
      <td style="border:0; width:40%;" class="box">
        <table class="topgrid">
          <tr>
            <td>เลขที่เอกสาร</td>
            <td class="right">{{ $quote->number ?? $quote->id }}</td>
          </tr>
          <tr>
            <td>วันที่</td>
            <td class="right">{{ $quote->issue_date?->format('d/m/Y') }}</td>
          </tr>
          <tr>
            <td>ครบกำหนด</td>
            <td class="right">{{ $quote->due_date?->format('d/m/Y') ?? '-' }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <table style="border:0; margin-bottom:8px;">
    <tr>
      <td class="box" style="border:0 width:55%;">
        <div class="stack">
          <div class="brand">ใบเสนอราคา / Quotation</div>
          <div style="font-weight:700;">{{ $company['name'] }}</div>
          <div class="muted">เลขผู้เสียภาษี: {{ $company['tax_id'] }}</div>
          <div class="muted">{{ $company['address']['line1'] ?? '' }} {{ $company['address']['line2'] ?? '' }} {{ $company['address']['province'] ?? '' }} {{ $company['address']['postcode'] ?? '' }}</div>
          <div class="muted">โทร: {{ $company['phone'] }} | อีเมล: {{ $company['email'] }}</div>
        </div>

      </td>

      <td class="box" style="width:45%;">
        <div class="stack">
          <div style="font-weight:700;">ลูกค้า</div>
          <div>{{ $quote->customer->name ?? '-' }}</div>
          <div class="muted">เลขผู้เสียภาษี: {{ $quote->customer->tax_id ?? '-' }}</div>
          <div class="muted">โทร: {{ $quote->customer->phone ?? '-' }}</div>
          <div class="muted">ที่อยู่: {{ $quote->customer->address ?? '-' }}</div>
        </div>
      </td>

    </tr>
  </table>
  <br />
  @if(!empty($quote->subject))
  <div style="margin: 4px 0 10px 0"><strong>เรื่อง:</strong> {{ $quote->subject }}</div>
  @endif
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
      @foreach ($quote->items as $it)
        <tr>
          <td>{{ $it->name }}</td>
          <td class="right">{{ number_format($it->qty_decimal, 2) }}</td>
          <td class="right">{{ number_format($it->unit_price_decimal, 2) }}</td>
          <td class="right">{{ number_format($it->vat_rate_decimal, 2) }}</td>
          <td class="right">{{ number_format($it->qty_decimal * $it->unit_price_decimal, 2) }}</td>
        </tr>
      @endforeach
      @for ($i = is_countable($quote->items) ? count($quote->items) : 0; $i < 4; $i++)
        <tr >
          <td>&nbsp;</td>
          <td class="right">&nbsp;</td>
          <td class="right">&nbsp;</td>
          <td class="right">&nbsp;</td>
          <td class="right">&nbsp;</td>
        </tr>
      @endfor
    </tbody>
  </table>
  <br />
  <table class="totals" style="margin-top:10px;">
    <tr>
      <td class="label" style="width:85%;">Subtotal</td>
      <td class="right" style="width: 15%; border:1px solid #ccc; padding:6px;">{{ number_format($quote->subtotal ?? 0, 2) }}</td>
    </tr>
    @if(!empty($quote->discount_type) && $quote->discount_type !== 'none')
    <tr>
      <td class="label">Discount @if($quote->discount_type==='percent') ({{ number_format($quote->discount_value_decimal ?? 0,2) }}%) @endif</td>
      <td class="right" style="border:1px solid #ccc; padding:6px;">-{{ number_format($quote->discount_amount_decimal ?? 0, 2) }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">VAT</td>
      <td class="right" style="border:1px solid #ccc; padding:6px;">{{ number_format($quote->vat_decimal ?? 0, 2) }}</td>
    </tr>
    <tr>
      <td class="label" style="font-weight:700;">Total</td>
      <td class="right" style="border:1px solid #ccc; padding:6px; font-weight:700;">{{ number_format($quote->total ?? 0, 2) }}</td>
    </tr>
    @if(!empty($quote->deposit_type) && $quote->deposit_type !== 'none')
    <tr>
      <td class="label">Deposit @if($quote->deposit_type==='percent') ({{ number_format($quote->deposit_value_decimal ?? 0,2) }}%) @endif</td>
      <td class="right" style="border:1px solid #ccc; padding:6px;">-{{ number_format($quote->deposit_amount_decimal ?? 0, 2) }}</td>
    </tr>
    <tr>
      <td class="label" style="font-weight:700;">Amount due</td>
      <td class="right" style="border:1px solid #ccc; padding:6px; font-weight:700;">{{ number_format( (float)($quote->total ?? 0) - (float)($quote->deposit_amount_decimal ?? 0), 2) }}</td>
    </tr>
    @endif
  </table>

  @if(!empty($quote->note))
  <div style="margin-top:12px">หมายเหตุ: {{ $quote->note }}</div>
  @endif
</body>

</html>
