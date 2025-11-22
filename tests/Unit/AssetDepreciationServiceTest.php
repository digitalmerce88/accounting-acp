<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Services\AssetDepreciationService;
use Carbon\Carbon;

class AssetDepreciationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function slm_monthly_amount_basic()
    {
        $cat = AssetCategory::create([
            'business_id' => 1,
            'name' => 'คอมพิวเตอร์',
            'useful_life_months' => 36,
            'depreciation_method' => 'slm'
        ]);
        $asset = Asset::create([
            'business_id' => 1,
            'category_id' => $cat->id,
            'asset_code' => 'AS-001',
            'name' => 'Laptop',
            'purchase_date' => '2025-01-15',
            'purchase_cost_decimal' => 36000,
            'salvage_value_decimal' => 0,
            'useful_life_months' => 36,
            'depreciation_method' => 'slm',
            'start_depreciation_date' => '2025-02-01',
            'status' => 'active'
        ]);
        $amt = AssetDepreciationService::monthlyAmount($asset);
        $this->assertEquals(1000.00, $amt);
    }

    /** @test */
    public function generate_entry_skips_before_start()
    {
        $asset = Asset::create([
            'business_id' => 1,
            'asset_code' => 'AS-002',
            'name' => 'Printer',
            'purchase_date' => '2025-01-10',
            'purchase_cost_decimal' => 12000,
            'salvage_value_decimal' => 0,
            'useful_life_months' => 12,
            'depreciation_method' => 'slm',
            'start_depreciation_date' => '2025-03-01',
            'status' => 'active'
        ]);
        $entryFeb = AssetDepreciationService::generateForPeriod($asset, 2025, 2);
        $this->assertNull($entryFeb);
        $entryMar = AssetDepreciationService::generateForPeriod($asset, 2025, 3);
        $this->assertNotNull($entryMar);
        $this->assertEquals(1000.00, (float)$entryMar->amount_decimal);
    }

    /** @test */
    public function generate_all_active_creates_entries()
    {
        $asset = Asset::create([
            'business_id' => 1,
            'asset_code' => 'AS-003',
            'name' => 'Server',
            'purchase_date' => '2025-01-01',
            'purchase_cost_decimal' => 24000,
            'salvage_value_decimal' => 0,
            'useful_life_months' => 24,
            'depreciation_method' => 'slm',
            'start_depreciation_date' => '2025-01-01',
            'status' => 'active'
        ]);
        $count = AssetDepreciationService::generateForAllActive(2025,1);
        $this->assertEquals(1, $count);
        $asset->refresh();
        $this->assertDatabaseHas('asset_depreciation_entries',[ 'asset_id'=>$asset->id,'period_year'=>2025,'period_month'=>1 ]);
    }

    /** @test */
    public function disposal_calculates_gain_loss()
    {
        $asset = Asset::create([
            'business_id' => 1,
            'asset_code' => 'AS-004',
            'name' => 'Monitor',
            'purchase_date' => '2025-01-01',
            'purchase_cost_decimal' => 12000,
            'salvage_value_decimal' => 0,
            'useful_life_months' => 12,
            'depreciation_method' => 'slm',
            'start_depreciation_date' => '2025-01-01',
            'status' => 'active'
        ]);
        // create 3 months depreciation
        for($m=1;$m<=3;$m++){ AssetDepreciationService::generateForPeriod($asset,2025,$m); }
        // book value after 3 months: cost 12000 - (12000/12*3)=12000-3000=9000, proceed 9500 => gain 500
        $service = new \App\Services\AssetDisposalService();
        $disposal = $service::dispose($asset, Carbon::create(2025,4,1), 9500);
        $this->assertEquals(500.00, (float)$disposal->gain_loss_decimal);
        $asset->refresh();
        $this->assertEquals('disposed', $asset->status);
    }
}
