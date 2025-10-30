<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{Business, BusinessProfile, BankAccount, Category};

class SimpleSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_business_profile_bank_and_categories(): void
    {
        $this->seed();

        $biz = Business::first();
        $this->assertNotNull($biz);

        $profile = BusinessProfile::where('business_id', $biz->id)->first();
        $this->assertNotNull($profile);

        $bank = BankAccount::where('business_id', $biz->id)->first();
        $this->assertNotNull($bank);

        $catCount = Category::where('business_id', $biz->id)->count();
        $this->assertGreaterThanOrEqual(8, $catCount);
    }
}
