<?php

return [
    'numbering' => [
        'quote' => 'Q-YYYYMM-####',
        'invoice' => 'INV-YYYYMM-####',
        'bill' => 'BILL-YYYYMM-####',
        'po' => 'PO-YYYYMM-####',
        'wht' => 'WHT-YYYYMM-####',
        'receipt' => 'RC-YYYYMM-####',
    ],
    'vat_rate_default' => 0.07,
    'withhold_affects_cash' => true,
    // Default PDF engine: 'dompdf' or 'mpdf'. Can be overridden per-request via ?engine=mpdf
    'pdf_engine' => env('PDF_ENGINE', 'mpdf'),
];
