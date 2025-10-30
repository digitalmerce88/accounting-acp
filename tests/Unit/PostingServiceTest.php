<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domain\Accounting\PostingService;
use App\Models\{Business, Account, JournalEntry, JournalLine};
use Database\Seeders\{RolesSeeder, DemoUsersSeeder, CoASeeder};

class PostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(CoASeeder::class);
    }

    public function test_income_gross_vat_with_wht_affect_cash(): void
    {
        $biz = Business::first();
        $svc = new PostingService();

        $entry = $svc->postIncome([
            'business_id' => $biz->id,
            'date' => now()->toDateString(),
            'memo' => 'บริการ 1',
            'amount' => 1070.00, // gross
            'price_input_mode' => 'gross',
            'vat_applicable' => true,
            'wht_rate' => 0.03,
            'payment_method' => 'bank',
        ]);

        $lines = JournalLine::where('entry_id', $entry->id)->get();
        $debit = (float)$lines->sum('debit');
        $credit = (float)$lines->sum('credit');
        $this->assertEquals($debit, $credit, 'entry not balanced');

        // Expectations: net=1000, vat=70, wht=30; cash debit=1040
        $cashId = Account::where(['business_id'=>$biz->id,'code'=>'102'])->value('id');
        $revId = Account::where(['business_id'=>$biz->id,'code'=>'402'])->value('id');
        $vatPayId = Account::where(['business_id'=>$biz->id,'code'=>'411'])->value('id');
        $whtRecId = Account::where(['business_id'=>$biz->id,'code'=>'153'])->value('id');

        $this->assertEquals(1040.00, (float)$lines->firstWhere('account_id',$cashId)->debit);
        $this->assertEquals(1000.00, (float)$lines->firstWhere('account_id',$revId)->credit);
        $this->assertEquals(70.00, (float)$lines->firstWhere('account_id',$vatPayId)->credit);
        $this->assertEquals(30.00, (float)$lines->firstWhere('account_id',$whtRecId)->debit);
    }

    public function test_expense_gross_vat_with_wht_affect_cash(): void
    {
        $biz = Business::first();
        $svc = new PostingService();

        $entry = $svc->postExpense([
            'business_id' => $biz->id,
            'date' => now()->toDateString(),
            'memo' => 'ค่าใช้จ่าย 1',
            'amount' => 1070.00, // gross
            'price_input_mode' => 'gross',
            'vat_applicable' => true,
            'wht_rate' => 0.03,
            'payment_method' => 'cash',
        ]);

        $lines = JournalLine::where('entry_id', $entry->id)->get();
        $debit = (float)$lines->sum('debit');
        $credit = (float)$lines->sum('credit');
        $this->assertEquals($debit, $credit, 'entry not balanced');

        $cashId = Account::where(['business_id'=>$biz->id,'code'=>'101'])->value('id');
        $expId = Account::where(['business_id'=>$biz->id,'code'=>'502'])->value('id');
        $vatRecId = Account::where(['business_id'=>$biz->id,'code'=>'511'])->value('id');
        $whtPayId = Account::where(['business_id'=>$biz->id,'code'=>'231'])->value('id');

        $this->assertEquals(1000.00, (float)$lines->firstWhere('account_id',$expId)->debit);
        $this->assertEquals(70.00, (float)$lines->firstWhere('account_id',$vatRecId)->debit);
        $this->assertEquals(1040.00, (float)$lines->firstWhere('account_id',$cashId)->credit);
        $this->assertEquals(30.00, (float)$lines->firstWhere('account_id',$whtPayId)->credit);
    }
}
