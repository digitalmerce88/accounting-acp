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
];
