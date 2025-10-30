<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Business, BusinessProfile, BankAccount, Category};

class SimpleSetupSeeder extends Seeder
{
    public function run(): void
    {
        $biz = Business::firstOrCreate(['name' => 'Demo Co., Ltd.'], ['country' => 'TH']);

        BusinessProfile::updateOrCreate(
            ['business_id' => $biz->id],
            [
                'name' => 'Demo Co., Ltd.',
                'tax_id' => '0105559999999',
                'address_text' => '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                'phone' => '02-123-4567',
                'email' => 'info@demo.local',
                'vat_registered_at' => now()->toDateString(),
            ]
        );

        BankAccount::firstOrCreate([
            'business_id' => $biz->id,
            'bank_code' => 'KBANK',
            'account_no' => '123-4-56789-0',
        ], [
            'account_name' => 'Demo Co., Ltd.',
            'is_default' => true,
            'opened_at' => now()->toDateString(),
        ]);

        $categories = [
            ['name' => 'รายได้จากการขาย', 'type' => 'income', 'vat_applicable' => true],
            ['name' => 'รายได้จากบริการ', 'type' => 'income', 'vat_applicable' => true],
            ['name' => 'ค่าเช่า', 'type' => 'expense', 'vat_applicable' => true],
            ['name' => 'ค่าน้ำ', 'type' => 'expense', 'vat_applicable' => true],
            ['name' => 'ค่าไฟ', 'type' => 'expense', 'vat_applicable' => true],
            ['name' => 'ค่าน้ำมัน', 'type' => 'expense', 'vat_applicable' => false],
            ['name' => 'ค่าขนส่ง', 'type' => 'expense', 'vat_applicable' => false],
            ['name' => 'ค่าโฆษณา', 'type' => 'expense', 'vat_applicable' => true],
        ];

        foreach ($categories as $c) {
            Category::firstOrCreate([
                'business_id' => $biz->id,
                'name' => $c['name'],
                'type' => $c['type'],
            ], [
                'vat_applicable' => $c['vat_applicable'],
            ]);
        }
    }
}
