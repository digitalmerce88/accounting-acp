<?php
return [
    'withhold_affects_cash' => true,

    // Default account codes (by business CoA) used when category.default_account_id is null
    'defaults' => [
        'cash_code' => '101',
        'bank_code' => '102',
        'revenue_code' => '402',
        'expense_code' => '502',
        'vat_receivable_code' => '511', // ภาษีซื้อ
        'vat_payable_code' => '411',   // ภาษีขาย
        'wht_receivable_code' => '153',
        'wht_payable_code' => '231',
    ],
];
