<?php

namespace Tests\Unit\Services;

use App\Domain\Accounting\Reports\TrialBalance;
use App\Models\{Business,Account,JournalEntry,JournalLine};
use Tests\TestCase;

class TrialBalanceServiceTest extends TestCase
{
    public function test_trial_balance_sums_debits_and_credits(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz TB']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'TB_CASH'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $rev = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'TB_REV'],['name'=>'Revenue','type'=>'revenue','normal_balance'=>'credit']);
        $e = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-d'),'memo'=>'TB','status'=>'posted']);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$cash->id,'debit'=>200]);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$rev->id,'credit'=>200]);

        $svc = new TrialBalance();
        $rows = $svc->run();
        $flat = collect($rows)->keyBy(fn($r)=>$r[0]);
        $this->assertEquals(200.0, $flat['TB_CASH'][3]);
        $this->assertEquals(200.0, $flat['TB_REV'][4]);
    }
}
