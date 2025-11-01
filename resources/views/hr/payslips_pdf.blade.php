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
    th, td { border:1px solid #ccc; padding:6px; vertical-align: top; }
    th { background: #f5f7fa; }
    .right { text-align:right; }
    .muted { color:#666; }
    .logo { height: 40px; }
    .stack > div { margin:4px 0; }
    .box { border:1px solid #e5e7eb; border-radius:4px; padding:6px; }
    .pagebreak { page-break-after: always; }
  </style>
  @php
    // Helpers for Thai date
    function th_date($iso){ if(!$iso) return '-'; $d=\Carbon\Carbon::parse($iso); return $d->format('d/m').'/'.($d->year+543); }
    function th_period($y,$m){ return str_pad($m,2,'0',STR_PAD_LEFT).'/'.($y+543); }
  @endphp
</head>
<body>
@foreach($items as $idx=>$it)
  @php
    $docNo = 'PR-'.str_pad($run->id,8,'0',STR_PAD_LEFT).'-'.str_pad($idx+1,2,'0',STR_PAD_LEFT);
    $period = th_period($run->period_year, $run->period_month);
    $dateStr = th_date($asOfDate ?? now());
    $incomeBasic = (float)($it->earning_basic_decimal ?? 0);
    $incomeOther = (float)($it->earning_other_decimal ?? 0);
    $deductSso = (float)($it->sso_employee_decimal ?? 0);
    $deductTax = (float)($it->wht_decimal ?? 0);
    $y = $ytd[$it->employee_id] ?? null;
    $yIncome = $y ? (float)$y->ytd_income : ($incomeBasic + $incomeOther);
    $yDeduct = $y ? (float)$y->ytd_deduction : ($deductSso + $deductTax);
    $yTax = $y ? (float)$y->ytd_tax : $deductTax;
    $ySSF = $y ? (float)$y->ytd_ssf : $deductSso;
  @endphp

  <!-- Header block -->
  <table style="border:0; margin-bottom:6px;">
    <tr>
      <td style="border:0; width:60%;">
        <div style="font-weight:700; font-size:16px;">{{ $companyArr['name'] ?? '' }}</div>
        <div class="muted">{{ $companyArr['address']['line1'] ?? '' }} {{ $companyArr['address']['line2'] ?? '' }} {{ $companyArr['address']['province'] ?? '' }} {{ $companyArr['address']['postcode'] ?? '' }}</div>
        <div class="muted">เลขผู้เสียภาษี {{ $companyArr['tax_id'] ?? '-' }}</div>
        <div class="stack" style="margin-top:6px;">
          <div><strong>ชื่อ/Name:</strong> {{ $it->employee->name ?? '-' }}</div>
          <div class="muted">แผนก/Dept.: {{ $it->employee->position ?? '-' }}</div>
        </div>
      </td>
      <td style="border:0; width:40%;">
        <table class="box" style="width:100%; border:0;">
          <tr>
            <td style="border:0; width:50%;">เลขที่/No.</td>
            <td style="border:0;" class="right">{{ $docNo }}</td>
          </tr>
          <tr>
            <td style="border:0;">งวดที่จ่าย/Period</td>
            <td style="border:0;" class="right">{{ $period }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Main grid: Income | Deduction and side boxes -->
  <table style="margin-bottom:6px;">
    <tr>
      <th style="width:40%">รายได้<br/>Income</th>
      <th style="width:15%" class="right">จำนวนเงิน<br/>Amount</th>
      <th style="width:30%">รายการหัก<br/>Deduction</th>
      <th style="width:15%" class="right">จำนวนเงิน<br/>Amount</th>
    </tr>
    <tr>
      <td>อัตราเงินเดือน</td>
      <td class="right">{{ number_format($incomeBasic + $incomeOther,2) }}</td>
      <td>เงินสมทบกองทุนประกันสังคม / ภาษีเงินได้</td>
      <td class="right">{{ number_format($deductSso + $deductTax,2) }}</td>
    </tr>
    <tr>
      <td colspan="2" class="right" style="font-weight:700;">รวมรายได้ (Total Income)</td>
      <td colspan="2" class="right" style="font-weight:700;">รวมรายการหัก (Total Deduction)</td>
    </tr>
    <tr>
      <td colspan="2" class="right">{{ number_format($incomeBasic + $incomeOther,2) }}</td>
      <td colspan="2" class="right">{{ number_format($deductSso + $deductTax,2) }}</td>
    </tr>
  </table>

  <table style="border:0; margin-bottom:8px;">
    <tr>
      <td style="border:0; width:60%;"></td>
      <td style="border:0; width:20%;" class="box">
        <div>วันที่จ่าย<br/>Payslip Date</div>
        <div class="right" style="font-weight:700;">{{ $dateStr }}</div>
      </td>
      <td style="border:0; width:20%;" class="box">
        <div>เงินได้สุทธิ<br/>Net to Pay</div>
        <div class="right" style="font-weight:700; font-size:16px;">{{ number_format($it->net_pay_decimal,2) }}</div>
      </td>
    </tr>
  </table>

  <!-- YTD row and signature -->
  <table>
    <tr>
      <th style="width:25%">เงินได้สะสม<br/>YTD Income</th>
      <th style="width:25%">เงินหักสะสม
        <br/>YTD Deduction</th>
      <th style="width:25%">ภาษีสะสม<br/>YTD TAX</th>
      <th style="width:25%">ประกันสังคมสะสม<br/>YTD SSF</th>
    </tr>
    <tr>
      <td class="right">{{ number_format($yIncome,2) }}</td>
      <td class="right">{{ number_format($yDeduct,2) }}</td>
      <td class="right">{{ number_format($yTax,2) }}</td>
      <td class="right">{{ number_format($ySSF,2) }}</td>
    </tr>
  </table>

  <table style="border:0; margin-top:6px;">
    <tr>
      <td style="border:0; width:75%"></td>
      <td style="border:0; width:25%;" class="box">ลงชื่อพนักงาน<br/>Employee Signature</td>
    </tr>
  </table>

  @if($idx < count($items)-1)
    <div class="pagebreak"></div>
  @endif
@endforeach
</body>
</html>
