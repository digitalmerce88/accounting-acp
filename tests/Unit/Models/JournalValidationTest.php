<?php

namespace Tests\Unit\Models;

use App\Domain\Accounting\Services\JournalService;
use App\Models\{Business,Account,JournalEntry};
use Exception;
use Tests\TestCase;

class JournalValidationTest extends TestCase
{
    public function test_unbalanced_entry_cannot_post(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz B']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'CASH'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $rev = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'REV'],['name'=>'Revenue','type'=>'revenue','normal_balance'=>'credit']);
        $svc = new JournalService();
        $e = $svc->createDraft($biz->id, date('Y-m-d'), 'Test');
        $svc->upsertLine($e->id, $cash->id, debit: 100);
        // Missing credit line
        $this->expectException(Exception::class);
        $svc->post($e->id);
    }

    public function test_negative_amount_not_allowed(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz C']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'CASH2'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $svc = new JournalService();
        $e = $svc->createDraft($biz->id, date('Y-m-d'), 'Test');
        $this->expectException(Exception::class);
        $svc->upsertLine($e->id, $cash->id, debit: -1);
    }

    public function test_cannot_have_both_debit_and_credit_in_line(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz D']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'CASH3'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $svc = new JournalService();
        $e = $svc->createDraft($biz->id, date('Y-m-d'), 'Test');
        $this->expectException(Exception::class);
        $svc->upsertLine($e->id, $cash->id, debit: 10, credit: 10);
    }

    public function test_balanced_entry_posts_successfully(): void
    {
        $biz = Business::firstOrCreate(['name'=>'Biz E']);
        $cash = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'CASH4'],['name'=>'Cash','type'=>'asset','normal_balance'=>'debit']);
        $rev = Account::firstOrCreate(['business_id'=>$biz->id,'code'=>'REV4'],['name'=>'Revenue','type'=>'revenue','normal_balance'=>'credit']);
        $svc = new JournalService();
        $e = $svc->createDraft($biz->id, date('Y-m-d'), 'Test');
        $svc->upsertLine($e->id, $cash->id, debit: 123.45);
        $svc->upsertLine($e->id, $rev->id, credit: 123.45);
        $posted = $svc->post($e->id);
        $this->assertEquals('posted', $posted->status);
    }
}
