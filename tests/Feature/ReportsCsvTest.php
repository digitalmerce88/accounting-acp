<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\{RolesSeeder, DemoUsersSeeder, CoASeeder, DemoDataSeeder};
use App\Models\User;

class ReportsCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(CoASeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    protected function actingAsAdmin()
    {
        $admin = User::whereHas('roles', fn($q)=>$q->where('slug','admin'))->first();
        return $this->actingAs($admin);
    }

    public function test_overview_csv_downloads()
    {
        $res = $this->actingAsAdmin()->get('/admin/accounting/reports/overview.csv');
    $res->assertOk();
    $this->assertStringStartsWith('text/csv', strtolower($res->headers->get('content-type')));
    }

    public function test_by_category_csv_downloads()
    {
        $res = $this->actingAsAdmin()->get('/admin/accounting/reports/by-category.csv');
    $res->assertOk();
    $this->assertStringStartsWith('text/csv', strtolower($res->headers->get('content-type')));
    }

    public function test_tax_csv_downloads()
    {
        $this->actingAsAdmin()->get('/admin/accounting/reports/tax/purchase-vat.csv')->assertOk();
        $this->actingAsAdmin()->get('/admin/accounting/reports/tax/sales-vat.csv')->assertOk();
        $this->actingAsAdmin()->get('/admin/accounting/reports/tax/wht-summary.csv')->assertOk();
    }
}
