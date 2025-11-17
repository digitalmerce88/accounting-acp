<?php

namespace Tests\Unit;

use App\Domain\Banking\BankReconciliationService;
use App\Models\{BankTransaction, Reconciliation, Transaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_match_matches_by_amount_and_reference()
    {
        $bizId = 1;
        // Seed internal transaction
        $t = Transaction::create([
            'business_id' => $bizId,
            'kind' => 'income',
            'date' => '2025-11-01',
            'amount' => 100.00,
            'memo' => 'ABC123',
        ]);

        // Seed bank transaction
        $bt = BankTransaction::create([
            'business_id' => $bizId,
            'bank_account_id' => 1,
            'date' => '2025-11-01',
            'amount_decimal' => 100.00,
            'reference' => 'ABC123',
        ]);

        $svc = new BankReconciliationService();
        $count = $svc->autoMatch($bizId, 1, '2025-11-01', '2025-11-30');

        $this->assertEquals(1, $count);
        $this->assertTrue(BankTransaction::find($bt->id)->matched);
    }
}
