<?php

namespace Tests\Unit\Services;

use App\Domain\Accounting\Services\LedgerService;
use App\Models\{Business,Account,JournalEntry,JournalLine};
use Tests\TestCase;

class LedgerServiceUnitTest extends TestCase
{
    public function test_forAccount_returns_rows_with_running_balance()
    {
        $biz = Business::firstOrCreate(['name'=>'LD Service']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'LDS_CASH'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $e1 = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-01'),'memo'=>'in','status'=>'posted']);
        $e2 = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-02'),'memo'=>'out','status'=>'posted']);
        JournalLine::create(['entry_id'=>$e1->id,'account_id'=>$cash->id,'debit'=>200]);
        JournalLine::create(['entry_id'=>$e2->id,'account_id'=>$cash->id,'credit'=>75]);

        $svc = new LedgerService();
        $rows = $svc->forAccount($cash->id);
        $this->assertIsArray($rows);
        $this->assertGreaterThanOrEqual(1, count($rows));
        $this->assertEquals(125.0, end($rows)[5]);
    }
}
