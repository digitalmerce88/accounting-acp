<?php

namespace Tests\Unit\Services;

use App\Domain\Accounting\Reports\Ledger;
use App\Models\{Business,Account,JournalEntry,JournalLine};
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    public function test_ledger_running_balance(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz LD']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'LD_CASH'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $e1 = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-01'),'memo'=>'in','status'=>'posted']);
        $e2 = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-02'),'memo'=>'out','status'=>'posted']);
        JournalLine::create(['entry_id'=>$e1->id,'account_id'=>$cash->id,'debit'=>100]);
        JournalLine::create(['entry_id'=>$e2->id,'account_id'=>$cash->id,'credit'=>40]);
        $svc = new Ledger();
        $rows = $svc->run($cash->id);
        $last = end($rows);
        $this->assertEquals(60.0, $last[5]);
    }
}
