<?php

return [
    'sso_employee_rate' => 0.05,
    'sso_employer_rate' => 0.05,
    'sso_wage_ceiling' => 15000,
    // Simplified monthly withholding brackets: [min, max, rate]
    'wht_table' => [
        [0, 150000, 0.00],
        [150001, 300000, 0.05],
        [300001, 500000, 0.10],
        [500001, 750000, 0.15],
        [750001, 1000000, 0.20],
        [1000001, PHP_INT_MAX, 0.25],
    ],

    // Major Thai banks (code => label)
    'th_banks' => [
        ['code' => 'BBL', 'name' => 'Bangkok Bank (BBL)'],
        ['code' => 'KBANK', 'name' => 'Kasikornbank (KBank)'],
        ['code' => 'SCB', 'name' => 'Siam Commercial Bank (SCB)'],
        ['code' => 'KTB', 'name' => 'Krungthai Bank (KTB)'],
        ['code' => 'BAY', 'name' => 'Bank of Ayudhya (Krungsri/BAY)'],
        ['code' => 'TTB', 'name' => 'TMBThanachart Bank (ttb)'],
        ['code' => 'GSB', 'name' => 'Government Savings Bank (GSB)'],
        ['code' => 'GHB', 'name' => 'Government Housing Bank (GHB)'],
        ['code' => 'UOB', 'name' => 'United Overseas Bank (UOB)'],
        ['code' => 'CIMBT', 'name' => 'CIMB Thai Bank'],
        ['code' => 'LHB', 'name' => 'Land and Houses Bank (LH Bank)'],
        ['code' => 'KKP', 'name' => 'Kiatnakin Phatra Bank (KKP)'],
        ['code' => 'BAAC', 'name' => 'Bank for Agriculture and Agricultural Cooperatives (BAAC)'],
    ],
];
