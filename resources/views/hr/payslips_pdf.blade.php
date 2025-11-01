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
    .pagebreak { page-break-after: always; }
  </style>
</head>
<body>
@foreach($items as $idx=>$it)
  <table style="border:0; margin-bottom:6px;">
    <tr>
      <td style="border:0; width:60%;">
        <div class="brand">สลิปเงินเดือน / Payslip</div>
        <div style="font-weight:700;">{{ $companyArr['name'] ?? '' }}</div>
        <div class="muted">งวด: {{ sprintf('%04d-%02d',$run->period_year,$run->period_month) }}</div>
      </td>
      <td style="border:0; width:40%; text-align:right;">
        @if(!empty($companyArr['logo_abs_path']) && file_exists($companyArr['logo_abs_path']))
          <img class="logo" src="{{ $companyArr['logo_abs_path'] }}" />
        @endif
      </td>
    </tr>
  </table>

  <table style="border:0; margin-bottom:8px;">
    <tr>
      <td style="border:0; width:50%;" class="stack">
        <div><strong>พนักงาน:</strong> {{ $it->employee->name ?? '-' }}</div>
        <div class="muted">รหัส: {{ $it->employee->emp_code ?? '-' }}</div>
        <div class="muted">ตำแหน่ง: {{ $it->employee->position ?? '-' }}</div>
      </td>
      <td style="border:0; width:50%;" class="stack right">
        <div><strong>สุทธิ:</strong> {{ number_format($it->net_pay_decimal,2) }}</div>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>รายการ</th>
        <th class="right" style="width:20%">จำนวนเงิน</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>เงินเดือนพื้นฐาน</td>
        <td class="right">{{ number_format($it->earning_basic_decimal,2) }}</td>
      </tr>
      <tr>
        <td>รายได้อื่นๆ</td>
        <td class="right">{{ number_format($it->earning_other_decimal ?? 0,2) }}</td>
      </tr>
      <tr>
        <td>หัก: ประกันสังคม (ลูกจ้าง)</td>
        <td class="right">{{ number_format($it->sso_employee_decimal,2) }}</td>
      </tr>
      <tr>
        <td>หัก: ภาษีหัก ณ ที่จ่าย</td>
        <td class="right">{{ number_format($it->wht_decimal,2) }}</td>
      </tr>
      <tr>
        <td style="font-weight:700;">สุทธิรับ</td>
        <td class="right" style="font-weight:700;">{{ number_format($it->net_pay_decimal,2) }}</td>
      </tr>
    </tbody>
  </table>
  @if($idx < count($items)-1)
    <div class="pagebreak"></div>
  @endif
@endforeach
</body>
</html>
