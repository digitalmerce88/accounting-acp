<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $currencies = [
            ['code' => 'THB', 'name' => 'Thai Baht', 'minor_unit' => 2, 'is_base' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'minor_unit' => 2, 'is_base' => false],
            ['code' => 'EUR', 'name' => 'Euro', 'minor_unit' => 2, 'is_base' => false],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'minor_unit' => 0, 'is_base' => false],
            ['code' => 'GBP', 'name' => 'British Pound', 'minor_unit' => 2, 'is_base' => false],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'minor_unit' => 2, 'is_base' => false],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'minor_unit' => 2, 'is_base' => false],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'minor_unit' => 2, 'is_base' => false],
        ];
        foreach ($currencies as $c) {
            $exists = DB::table('currencies')->where('code', $c['code'])->exists();
            if (!$exists) {
                DB::table('currencies')->insert(array_merge($c, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}
