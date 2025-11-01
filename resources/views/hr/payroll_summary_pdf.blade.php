<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <style>
    @if (empty($engine) || $engine !== 'mpdf')
      @font-face { font-family: 'THSarabunNew'; src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew.ttf'))) }}') format('truetype'); font-weight: normal; font-style: normal; }
      @font-face { font-family: 'THSarabunNew'; src: url('data:font/ttf;base64,{{ base64_encode(file_get_contents(public_path('fonts/THSarabunNew Bold.ttf'))) }}') format('truetype'); font-weight: bold; font-style: normal; }
    @endif
    @page { margin: 16mm 14mm 18mm 14mm; }
    body { font-family: {{ isset($engine) && $engine==='mpdf' ? 'Garuda, DejaVu Sans, sans-serif' : 'THSarabunNew, DejaVu Sans, sans-serif' }}; font-size:13px; color:#111; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #ccc; padding:6px; }
    th { background: #f5f7fa; }
    .right { text-align:right; }
    .muted { color:#666; }
    .brand { font-size: 14px; background:#0ea5a8; color:#fff; display:inline-block; padding:6px 8px; border-radius:4px; margin-bottom:6px; }
    .logo { height: 40px; }
    .stack > div { margin:4px 0; }
  </style>
</head>
<body>
  <table style="border:0; margin-bottom:6px;">
    <tr>
      <td style="border:0; width:60%;">
        <div class="brand">สรุปเงินเดือน / Payroll Summary</div>
        <div style="font-weight:700;">{{ $companyArr['name'] ?? '' }}</div>
        <div class="muted">เลขผู้เสียภาษี: {{ $companyArr['tax_id'] ?? '-' }}</div>
        <div class="muted">งวด: {{ sprintf('%04d-%02d',$run->period_year,$run->period_month) }}</div>
      </td>
      <td style="border:0; width:40%; text-align:right;">
        @if(!empty($companyArr['logo_abs_path']) && file_exists($companyArr['logo_abs_path']))
          <img class="logo" src="{{ $companyArr['logo_abs_path'] }}" />
        @endif
      </td>
    </tr>
  </table>

  @php
    $sumSalary = (float) $items->sum(fn($i)=> ($i->earning_basic_decimal + ($i->earning_other_decimal ?? 0)));
    $sumSsoEmp = (float) $items->sum('sso_employee_decimal');
    $sumSsoEr  = (float) $items->sum('sso_employer_decimal');
    $sumWht    = (float) $items->sum('wht_decimal');
    $sumNet    = (float) $items->sum('net_pay_decimal');
  @endphp

  <table>
    <thead>
      <tr>
        <th>พนักงาน</th>
        <th class="right" style="width:14%">เงินเดือน</th>
        <th class="right" style="width:14%">SSO (ล)</th>
        <th class="right" style="width:14%">SSO (น)</th>
        <th class="right" style="width:14%">WHT</th>
        <th class="right" style="width:14%">สุทธิ</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $it)
      <tr>
        <td>{{ $it->employee->name ?? '-' }}</td>
        <td class="right">{{ number_format(($it->earning_basic_decimal + ($it->earning_other_decimal ?? 0)),2) }}</td>
        <td class="right">{{ number_format($it->sso_employee_decimal,2) }}</td>
        <td class="right">{{ number_format($it->sso_employer_decimal,2) }}</td>
        <td class="right">{{ number_format($it->wht_decimal,2) }}</td>
        <td class="right">{{ number_format($it->net_pay_decimal,2) }}</td>
      </tr>
      @endforeach
      <tr>
        <td class="right" style="font-weight:700;">รวม</td>
        <td class="right" style="font-weight:700;">{{ number_format($sumSalary,2) }}</td>
        <td class="right" style="font-weight:700;">{{ number_format($sumSsoEmp,2) }}</td>
        <td class="right" style="font-weight:700;">{{ number_format($sumSsoEr,2) }}</td>
        <td class="right" style="font-weight:700;">{{ number_format($sumWht,2) }}</td>
        <td class="right" style="font-weight:700;">{{ number_format($sumNet,2) }}</td>
      </tr>
    </tbody>
  </table>
</body>
</html>
