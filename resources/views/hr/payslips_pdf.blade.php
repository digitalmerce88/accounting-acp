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
        @endif
        @page {
            margin: 8mm;
        }

        body {
            font-family: {{ isset($engine) && $engine === 'mpdf' ? 'Garuda, DejaVu Sans, sans-serif' : 'THSarabunNew, DejaVu Sans, sans-serif' }};
            font-size: 13px;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f5f7fa;
            font-weight: 700;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #666;
            font-size: 11px;
        }

        .logo {
            height: 50px;
        }

        .pagebreak {
            page-break-after: always;
        }

        .noborder {
            border: 0 !important;
        }
    </style>
    @php
        function th_date($iso)
        {
            if (!$iso) {
                return '-';
            }
            $d = \Carbon\Carbon::parse($iso);
            return $d->format('d/m') . '/' . ($d->year + 543);
        }
        function th_period($y, $m)
        {
            return str_pad($m, 2, '0', STR_PAD_LEFT) . '/' . ($y + 543);
        }
    @endphp
</head>

<body>
    @foreach ($items as $idx => $it)
        @php
            $docNo = 'PR-' . str_pad($run->id, 8, '0', STR_PAD_LEFT) . '-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
            $period = th_period($run->period_year, $run->period_month);
            $dateStr = th_date($asOfDate ?? now());
            $incomeBasic = (float) ($it->earning_basic_decimal ?? 0);
            $incomeOther = (float) ($it->earning_other_decimal ?? 0);
            $deductSso = (float) ($it->sso_employee_decimal ?? 0);
            $deductTax = (float) ($it->wht_decimal ?? 0);
            $y = $ytd[$it->employee_id] ?? null;
            $yIncome = $y ? (float) $y->ytd_income : $incomeBasic + $incomeOther;
            $yDeduct = $y ? (float) $y->ytd_deduction : $deductSso + $deductTax;
            $yTax = $y ? (float) $y->ytd_tax : $deductTax;
            $ySSF = $y ? (float) $y->ytd_ssf : $deductSso;
        @endphp

        <!-- Header: logo, company info, doc no/period -->
        <table style="border:0; margin-bottom:10px;">
            <tr>
                <td class="noborder" style="width:15%; vertical-align:middle;">
                    @if (!empty($companyArr['logo_abs_path']) && file_exists($companyArr['logo_abs_path']))
                        <img class="logo" src="{{ $companyArr['logo_abs_path'] }}" />
                    @endif
                </td>
                <td class="noborder" style="width:55%;">
                    <div style="font-weight:700; font-size:15px;">{{ $companyArr['name'] ?? '' }}</div>
                    <div class="muted">{{ $companyArr['address']['line1'] ?? '' }}
                        {{ $companyArr['address']['line2'] ?? '' }} {{ $companyArr['address']['province'] ?? '' }}
                        {{ $companyArr['address']['postcode'] ?? '' }}</div>
                    <div class="muted">เลขผู้เสียภาษี {{ $companyArr['tax_id'] ?? '-' }}</div>
                </td>
                <td class="noborder" style="width:30%; text-align:right;">
                    <h2>สลิปเงินเดือน / Payslip</h2>
                    <div><strong>งวดที่จ่าย/Period</strong> {{ $period }}</div>
                </td>
            </tr>
        </table>

        <!-- Employee info row -->
        <table style="border:0; margin-bottom:8px;">
            <tr>
                <td class="noborder" style="width:50%;">
                    <strong>ชื่อพนักงาน(รหัส):</strong>
                    {{ $it->employee->name ?? '-' }}({{ $it->employee->emp_code ?? '-' }})<br />
                    <strong>ตำแหน่ง: {{ $it->employee->position ?? 'พนักงาน' }}</strong><br />
                    <strong>บัญชี: {{ $it->employee->bank_account_json['number'] ?? '-' }} ({{ $it->employee->bank_account_json['name'] }}) </strong>
                </td>
                <td class="noborder" style="width:50%; text-align:right;">
                    <!-- Date and Net Pay boxes on the right -->
                    <table style="border:0; margin-bottom:8px;">
                        <tr>
                            <td class="noborder" style="width:60%;"></td>
                            <td style="width:40%; text-align:center;">
                                <div style="font-weight:700;">วันที่จ่าย<br />Payslip Date</div>
                                <div style="font-size:14px;">{{ $dateStr }}</div>
                            </td>
                            <td style="width:40%; text-align:center;">
                                <div style="font-weight:700;">เงินได้สุทธิ<br />Net to Pay</div>
                                <div style="font-size:16px; font-weight:700;">
                                    {{ number_format($it->net_pay_decimal, 2) }}</div>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

        <!-- Main table: 4 columns -->
        <table style="margin-bottom:0;">
            <tr>
                <th style="width:40%">รายได้<br />Income</th>
                <th style="width:15%" class="right">จำนวนเงิน<br />Amount</th>
                <th style="width:30%">รายการหัก<br />Deduction</th>
                <th style="width:15%" class="right">จำนวนเงิน<br />Amount</th>
            </tr>
            <tr>
                <td>อัตราเงินเดือน</td>
                <td class="right">{{ number_format($incomeBasic, 2) }}</td>
                <td>เงินสมทบกองทุนประกันสังคม</td>
                <td class="right">{{ number_format($deductSso, 2) }}</td>
            </tr>
            @if ($incomeOther > 0)
                <tr>
                    <td>รายได้อื่นๆ</td>
                    <td class="right">{{ number_format($incomeOther, 2) }}</td>
                    <td>ภาษีเงินได้</td>
                    <td class="right">{{ number_format($deductTax, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td>&nbsp;</td>
                    <td class="right">&nbsp;</td>
                    <td>ภาษีเงินได้</td>
                    <td class="right">{{ number_format($deductTax, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="2" class="right" style="font-weight:700;">รวมรายได้ (Total Income)</td>
                <td colspan="2" class="right" style="font-weight:700;">รวมรายการหัก (Total Deduction)</td>
            </tr>
            <tr>
                <td colspan="2" class="right" style="font-weight:700;">
                    {{ number_format($incomeBasic + $incomeOther, 2) }}</td>
                <td colspan="2" class="right" style="font-weight:700;">
                    {{ number_format($deductSso + $deductTax, 2) }}</td>
            </tr>
        </table>


        <!-- YTD row (single row, 4 columns) -->
        <table style="margin-bottom:8px;">
            <tr>
                <th class="center" style="width:25%">เงินได้สะสม<br />(YTD Income)</th>
                <th class="center" style="width:25%">เงินหักสะสม<br />(YTD Deduction)</th>
                <th class="center" style="width:25%">ภาษีสะสม<br />(YTD TAX)</th>
                <th class="center" style="width:25%">ประกันสังคมสะสม<br />(YTD SSF)</th>
            </tr>
            <tr>
                <td class="right">{{ number_format($yIncome, 2) }}</td>
                <td class="right">{{ number_format($yDeduct, 2) }}</td>
                <td class="right">{{ number_format($yTax, 2) }}</td>
                <td class="right">{{ number_format($ySSF, 2) }}</td>
            </tr>
        </table>

        <!-- Employee signature box on bottom right -->
        <table style="border:0;">
            <tr>
                <td class="noborder" style="width:70%;"></td>
                <td class="center" style="width:30%; height:60px;">
                    <br /><br />
                    <div style="font-weight:700;">ลงชื่อพนักงาน<br />Employee Signature</div>
                </td>
            </tr>
        </table>

        @if ($idx < count($items) - 1)
            <div class="pagebreak"></div>
        @endif
    @endforeach
</body>

</html>
