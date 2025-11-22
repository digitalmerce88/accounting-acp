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
        // Asset related
        'depreciation_expense_code' => '503',
        'accumulated_depreciation_code' => '159',
        'asset_disposal_gain_code' => '480',
        'asset_disposal_loss_code' => '530',
        'asset_cost_code' => '150', // existing equipment account
        // FX gain/loss
        'fx_gain_code' => '561',
        'fx_loss_code' => '751',
    ],
];
