<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domain\Documents\DocumentCalculator;

class DocumentCalculatorTest extends TestCase
{
    public function test_basic_amounts_percent_discount_and_deposit()
    {
        $items = [
            ['qty_decimal'=>2,'unit_price_decimal'=>100,'vat_rate_decimal'=>7],
            ['qty_decimal'=>1,'unit_price_decimal'=>50,'vat_rate_decimal'=>0],
        ];
        $calc = DocumentCalculator::compute($items, 'percent', 10, 'percent', 20);
        $this->assertEquals(250.00, $calc['subtotal']);
        $this->assertEquals(25.00, $calc['discount_amount_decimal']);
        // VAT is prorated from the raw VAT after discount; expected value is 12.60
        $this->assertEquals(12.60, round($calc['vat_decimal'], 2));
        // Round both sides to 2 decimals to avoid floating point precision issues
        $this->assertEquals(round($calc['total'] - $calc['deposit_amount_decimal'], 2), round($calc['amount_due'], 2));
    }

    public function test_amount_discount_and_deposit_capping()
    {
        $items = [['qty_decimal'=>1,'unit_price_decimal'=>100,'vat_rate_decimal'=>7]];
        $calc = DocumentCalculator::compute($items, 'amount', 1000, 'amount', 1000);
        // discount cannot exceed subtotal, deposit cannot exceed total
        $this->assertEquals(100.00, $calc['subtotal']);
        $this->assertEquals(100.00, $calc['discount_amount_decimal']);
        $this->assertEquals(0.00, $calc['vat_decimal']);
        $this->assertEquals(0.00, $calc['total']);
        $this->assertEquals(0.00, $calc['deposit_amount_decimal']);
        $this->assertEquals(0.00, $calc['amount_due']);
    }
}
