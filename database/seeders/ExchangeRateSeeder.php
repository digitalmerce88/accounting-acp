<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Base currency assumed THB; seed simple, reasonable placeholder rates.
        // Schema: number of quote per base (e.g. 1 THB = 0.0275 USD)
        $today = now()->toDateString();
        $rows = [
            ['base_currency' => 'THB', 'quote_currency' => 'THB', 'rate_date' => $today, 'rate_decimal' => 1.00000000],
            ['base_currency' => 'THB', 'quote_currency' => 'USD', 'rate_date' => $today, 'rate_decimal' => 0.0275],
            ['base_currency' => 'THB', 'quote_currency' => 'EUR', 'rate_date' => $today, 'rate_decimal' => 0.0250],
            ['base_currency' => 'THB', 'quote_currency' => 'JPY', 'rate_date' => $today, 'rate_decimal' => 4.2000],
            ['base_currency' => 'THB', 'quote_currency' => 'GBP', 'rate_date' => $today, 'rate_decimal' => 0.0215],
            ['base_currency' => 'THB', 'quote_currency' => 'CNY', 'rate_date' => $today, 'rate_decimal' => 0.1980],
            ['base_currency' => 'THB', 'quote_currency' => 'SGD', 'rate_date' => $today, 'rate_decimal' => 0.0370],
            ['base_currency' => 'THB', 'quote_currency' => 'AUD', 'rate_date' => $today, 'rate_decimal' => 0.0410],
        ];

        foreach ($rows as $r) {
            $exists = DB::table('exchange_rates')->where([
                ['base_currency', '=', $r['base_currency']],
                ['quote_currency', '=', $r['quote_currency']],
                ['rate_date', '=', $r['rate_date']],
            ])->exists();
            if (!$exists) {
                DB::table('exchange_rates')->insert(array_merge($r, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
