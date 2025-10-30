<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Business;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class AccountTest extends TestCase
{
    public function test_unique_code_per_business(): void
    {
        $biz = Business::firstOrCreate(['name' => 'Biz A']);
        Account::create(['business_id'=>$biz->id,'code'=>'A001','name'=>'Acc1','type'=>'asset','normal_balance'=>'debit']);
        $this->expectException(QueryException::class);
        Account::create(['business_id'=>$biz->id,'code'=>'A001','name'=>'Acc2','type'=>'asset','normal_balance'=>'debit']);
    }
}
