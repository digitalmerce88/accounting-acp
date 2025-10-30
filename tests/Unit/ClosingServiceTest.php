<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domain\Accounting\ClosingService;
use Database\Seeders\{RolesSeeder, CoASeeder, DemoDataSeeder};
use App\Models\{Business, JournalEntry, JournalLine, ClosingPeriod};

class ClosingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(CoASeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_close_month_creates_balanced_entry_and_period(): void
    {
        $biz = Business::first();
        $svc = new ClosingService();
        $y = (int)date('Y'); $m = (int)date('m');
        $entry = $svc->closeMonth($biz->id, $y, $m);

        $this->assertNotNull($entry->id);
        $lines = JournalLine::where('entry_id',$entry->id)->get();
        $this->assertTrue($lines->count() >= 1);
        $this->assertEquals((float)$lines->sum('debit'), (float)$lines->sum('credit'));

        $this->assertTrue(ClosingPeriod::where(['business_id'=>$biz->id,'period_month'=>$m,'period_year'=>$y])->exists());
    }

    public function test_cannot_close_twice(): void
    {
        $biz = Business::first();
        $svc = new ClosingService();
        $y = (int)date('Y'); $m = (int)date('m');
        $svc->closeMonth($biz->id, $y, $m);
        $this->expectException(\RuntimeException::class);
        $svc->closeMonth($biz->id, $y, $m);
    }
}
