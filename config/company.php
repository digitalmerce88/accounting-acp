<?php

return [
    // Company profile used in document headers and PDFs
    'name' => env('COMPANY_NAME', 'Demo Company Co., Ltd.'),
    'tax_id' => env('COMPANY_TAX_ID', '0105555000000'),
    'phone' => env('COMPANY_PHONE', '02-000-0000'),
    'email' => env('COMPANY_EMAIL', 'info@example.com'),
    'address' => [
        'line1' => env('COMPANY_ADDR_LINE1', '123 ถนนสุขุมวิท'),
        'line2' => env('COMPANY_ADDR_LINE2', 'แขวงคลองตัน เขตวัฒนา'),
        'province' => env('COMPANY_ADDR_PROVINCE', 'กรุงเทพมหานคร'),
        'postcode' => env('COMPANY_ADDR_POSTCODE', '10110'),
    ],
];
