<?php

namespace Tests\Unit\Services;

use App\Domain\Accounting\Services\TrialBalanceService;
use App\Models\{Business,Account,JournalEntry,JournalLine};
use Tests\TestCase;

class TrialBalanceServiceUnitTest extends TestCase
{
    public function test_build_returns_rows()
    {
        $biz = Business::firstOrCreate(['name'=>'TB Service']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'TBS_CASH'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $rev = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'TBS_REV'],['name'=>'Revenue','type'=>'revenue','normal_balance'=>'credit']);
        $e = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-d'),'memo'=>'TB','status'=>'posted']);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$cash->id,'debit'=>500]);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$rev->id,'credit'=>500]);

        $svc = new TrialBalanceService();
        $rows = $svc->build();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }
}
