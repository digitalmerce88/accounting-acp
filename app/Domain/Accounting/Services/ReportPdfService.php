<?php

namespace App\Domain\Accounting\Services;

use Dompdf\Dompdf;

class ReportPdfService
{
    /**
     * Render Trial Balance PDF from payload ['rows'=>[], 'from'=>?, 'to'=>?]
     * Returns binary PDF content.
     */
    public function trialBalance(array $payload): string
    {
        $rows = $payload['rows'] ?? [];
        $from = $payload['from'] ?? '';
        $to = $payload['to'] ?? '';

        $html = '<h2>งบทดลอง</h2>';
        $html .= '<div>ช่วง: ' . htmlentities($from) . ' - ' . htmlentities($to) . '</div>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
        $html .= '<thead><tr><th>เลขที่บัญชี</th><th>ชื่อบัญชี</th><th>ประเภท</th><th style="text-align:right">เดบิต</th><th style="text-align:right">เครดิต</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($rows as $r) {
            $code = htmlentities($r[0] ?? '');
            $name = htmlentities($r[1] ?? '');
            $type = htmlentities($r[2] ?? '');
            $dr = number_format($r[3] ?? 0, 2);
            $cr = number_format($r[4] ?? 0, 2);
            $html .= "<tr><td>{$code}</td><td>{$name}</td><td>{$type}</td><td style='text-align:right'>{$dr}</td><td style='text-align:right'>{$cr}</td></tr>";
        }
        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * Render Ledger PDF from payload ['rows'=>[], 'account_id'=>?, 'from'=>?, 'to'=>?]
     */
    public function ledger(array $payload): string
    {
        $rows = $payload['rows'] ?? [];
        $account = $payload['account_id'] ?? '';
        $from = $payload['from'] ?? '';
        $to = $payload['to'] ?? '';

        $html = '<h2>สมุดบัญชีแยกประเภท</h2>';
        $html .= '<div>บัญชี: ' . htmlentities($account) . '</div>';
        $html .= '<div>ช่วง: ' . htmlentities($from) . ' - ' . htmlentities($to) . '</div>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
        $html .= '<thead><tr><th>วันที่</th><th>เลขที่รายการ</th><th>บันทึก</th><th style="text-align:right">เดบิต</th><th style="text-align:right">เครดิต</th><th style="text-align:right">ยอดคงเหลือ</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($rows as $r) {
            $date = htmlentities($r[0] ?? '');
            $entry = htmlentities($r[1] ?? '');
            $memo = htmlentities($r[2] ?? '');
            $dr = number_format($r[3] ?? 0, 2);
            $cr = number_format($r[4] ?? 0, 2);
            $bal = number_format($r[5] ?? 0, 2);
            $html .= "<tr><td>{$date}</td><td>{$entry}</td><td>{$memo}</td><td style='text-align:right'>{$dr}</td><td style='text-align:right'>{$cr}</td><td style='text-align:right'>{$bal}</td></tr>";
        }
        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
