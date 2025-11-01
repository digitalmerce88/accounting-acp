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
            size: A5 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }

        body {
            font-family: {{ isset($engine) && $engine === 'mpdf' ? 'Garuda, DejaVu Sans, sans-serif' : 'THSarabunNew, DejaVu Sans, sans-serif' }};
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }

        th {
            background: #fff;
            font-weight: 700;
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .noborder {
            border: 0 !important;
        }

        .triangle {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 50px 50px 0;
            border-color: transparent #999 transparent transparent;
        }
    </style>
    @php
        function th_date($iso)
        {
            if (!$iso) {
                return '-';
            }
            $d = \Carbon\Carbon::parse($iso);
            return str_pad($d->day, 2, '0', STR_PAD_LEFT) . '-' . str_pad($d->month, 2, '0', STR_PAD_LEFT) . ' ค.ศ. ' . $d->year;
        }
        function th_period_range($y, $m)
        {
            $start = str_pad($m, 2, '0', STR_PAD_LEFT) . '-01';
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $m, $y);
            $end = str_pad($m, 2, '0', STR_PAD_LEFT) . '-' . $lastDay;
            return $start . ' ค.ศ. ' . $y;
        }
    @endphp
</head>

<body>
    @foreach ($items as $idx => $it)
        @php
            $period = th_period_range($run->period_year, $run->period_month);
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
            $netPay = $incomeBasic + $incomeOther - $deductSso - $deductTax;
            $totalEarnings = $incomeBasic + $incomeOther;
            $totalDeductions = $deductSso + $deductTax;
        @endphp

        <div style="position:relative; padding: 10px;">
            <!-- Triangle corner -->
            <div class="triangle"></div>

            <!-- Header -->
            <table style="border:0; margin-bottom:10px;">
                <tr>
                    <td class="noborder" style="width:50%;">
                        <div style="font-size:16px; font-weight:700;">{{ $companyArr['name'] ?? 'อัลฟ่าซิพผลิส' }}</div>
                    </td>
                    <td class="noborder" style="width:50%; text-align:right;">
                        <div style="font-size:14px; font-weight:700;">สลิปเงินเดือน / Pay Slip</div>
                    </td>
                </tr>
            </table>

            <!-- Employee Info & Period -->
            <table style="border:0; margin-bottom:8px; font-size:10px;">
                <tr>
                    <td class="noborder" style="width:50%;">
                        <div><strong>ชื่อนามสกุล(รหัส):</strong> {{ $it->employee->name ?? '-' }} ({{ $it->employee->emp_code ?? '0001' }})</div>
                        <div style="margin-top:3px;"><strong>Emp. name (Code)</strong></div>
                    </td>
                    <td class="noborder" style="width:50%; text-align:right;">
                        <div><strong>รอบเงินเดือน:</strong> {{ $period }}</div>
                        <div style="margin-top:3px;"><strong>Payroll Period</strong></div>
                    </td>
                </tr>
                <tr>
                    <td class="noborder" style="width:50%;">
                        <div style="margin-top:5px;"><strong>ตำแหน่ง:</strong> {{ $it->employee->position ?? 'Tech Dev/CTO' }}</div>
                        <div style="margin-top:3px;"><strong>Position</strong></div>
                    </td>
                    <td class="noborder" style="width:50%; text-align:right;">
                        <div style="margin-top:5px;"><strong>วันที่จ่าย:</strong> {{ $dateStr }}</div>
                        <div style="margin-top:3px;"><strong>Payment Date</strong></div>
                    </td>
                </tr>
                <tr>
                    <td class="noborder" style="width:50%;">
                    </td>
                    <td class="noborder" style="width:50%; text-align:right;">
                        <div style="margin-top:5px;"><strong>เลขที่บัญชี:</strong> {{ $it->employee->bank_account ?? '030-2-80306-4' }}</div>
                        <div style="margin-top:3px;"><strong>Bank Account</strong></div>
                    </td>
                </tr>
            </table>

            <!-- Main Table: 3 columns -->
            <table style="margin-bottom:8px;">
                <tr>
                    <th style="width:33%;">เงินได้<br/>Earnings</th>
                    <th style="width:34%;">รายการหัก<br/>Deductions</th>
                    <th style="width:33%;">ปี<br/>{{ $run->period_year }}</th>
                </tr>
                <tr>
                    <td>
                        <div>เงินเดือน/ค่าจ้าง</div>
                        <div style="font-size:9px;">Salary/Wage</div>
                    </td>
                    <td>
                        <div>ประกันสังคม</div>
                        <div style="font-size:9px;">Social Security Fund</div>
                    </td>
                    <td>
                        <div>เงินได้สะสม</div>
                        <div style="font-size:9px;">YTD earnings</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">{{ number_format($incomeBasic, 2) }}</td>
                    <td class="right">{{ number_format($deductSso, 2) }}</td>
                    <td class="right">{{ number_format($yIncome, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        <div>ค่าล่วงเวลา</div>
                        <div style="font-size:9px;">Overtime</div>
                    </td>
                    <td>
                        <div>ภาษีหัก ณ ที่จ่าย</div>
                        <div style="font-size:9px;">Withholding tax</div>
                    </td>
                    <td>
                        <div>ภาษีหัก ณ ที่จ่ายสะสม</div>
                        <div style="font-size:9px;">YTD Withholding tax</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">{{ number_format($incomeOther, 2) }}</td>
                    <td class="right">{{ number_format($deductTax, 2) }}</td>
                    <td class="right">{{ number_format($yTax, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        <div>ค่านายหน้า</div>
                        <div style="font-size:9px;">Commission</div>
                    </td>
                    <td>
                        <div>เงินกู้ยืม กยศ./กรอ.</div>
                        <div style="font-size:9px;">Student Loan Fund</div>
                    </td>
                    <td>
                        <div>เงินประกันสังคมสะสม</div>
                        <div style="font-size:9px;">Accumulated SSF</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">0.00</td>
                    <td class="right">0.00</td>
                    <td class="right">{{ number_format($ySSF, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        <div>ค่าเบี้ยเลี้ยง/ค่าครองชีพ</div>
                        <div style="font-size:9px;">Allowances/Cost of livings</div>
                    </td>
                    <td>
                        <div>เงินประกัน</div>
                        <div style="font-size:9px;">Deposit</div>
                    </td>
                    <td>
                        <div>รวมเงินได้</div>
                        <div style="font-size:9px;">Total earnings</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">0.00</td>
                    <td class="right">0.00</td>
                    <td class="right">{{ number_format($yIncome, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        <div>โบนัส</div>
                        <div style="font-size:9px;">Bonus</div>
                    </td>
                    <td>
                        <div>ขาด/ลา/มาสาย</div>
                        <div style="font-size:9px;">Absent/Leave/Late</div>
                    </td>
                    <td>
                        <div>รวมรายการหัก</div>
                        <div style="font-size:9px;">Total deductions</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">0.00</td>
                    <td class="right">0.00</td>
                    <td class="right">{{ number_format($totalDeductions, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        <div>เงินได้อื่นๆ</div>
                        <div style="font-size:9px;">Others</div>
                    </td>
                    <td>
                        <div>รายการหักอื่นๆ</div>
                        <div style="font-size:9px;">Others</div>
                    </td>
                    <td>
                        <div style="font-weight:700;">เงินได้สุทธิ</div>
                        <div style="font-size:9px;"><strong>Net pay</strong></div>
                    </td>
                </tr>
                <tr>
                    <td class="right">0.00</td>
                    <td class="right">0.00</td>
                    <td class="right" style="font-weight:700;">{{ number_format($netPay, 2) }}</td>
                </tr>
            </table>

            <!-- Footer: Remarks & Signature -->
            <table style="border:0;">
                <tr>
                    <td class="noborder" style="width:50%; vertical-align:top;">
                        <div style="font-size:10px;"><strong>หมายเหตุ:</strong></div>
                        <div style="font-size:9px;">Remarks</div>
                    </td>
                    <td class="noborder" style="width:50%; text-align:right; vertical-align:top;">
                        <div style="font-size:10px;"><strong>ลายเซ็นผู้จ่ายเงิน:</strong></div>
                        <div style="font-size:9px;">Employer's Signature</div>
                        <div style="border-bottom:1px solid #000; width:200px; margin:20px 0 0 auto;"></div>
                    </td>
                </tr>
            </table>

            <!-- Confidential Notice -->
            <div style="border-top:1px solid #ccc; margin-top:10px; padding-top:5px; font-size:8px; text-align:center; color:#666;">
                ข้อมูลเงินเดือนและค่าจ้างเป็นข้อมูลส่วนบุคคล ห้ามเปิดเผยโดยเด็ดขาดบริษัทฯ ห้ามเปิดเผยโดยเด็ดขาดเว้นแต่มีคำสั่งศาลหรือมีความจำเป็นตามวิธีประกันรับทำกับบริษัทในการกู้ยืมเงินของพนักงาน<br/>
                Salary and wages are confidential information. Disclosure is strictly prohibited. This document is only valid with an authorized signature and company stamp.<br/>
                เอกสารนี้ถูกออกจากใช้งานหลังจากนายจ้างโอนจ่ายเงินในบัญชี จ FLOWPAYROLL ในกรณีฉุกเฉินใช้
            </div>
        </div>

        @if ($idx < count($items) - 1)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
