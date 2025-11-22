<?php

namespace App\Domain\Documents;

use App\Models\{Bill, WhtCertificate};
use App\Domain\Accounting\PostingService;
use App\Domain\Accounting\Services\FxGainLossService;

class PurchaseService
{
    public function markPaid(int $billId, int $businessId, string $date, string $paymentMethod = 'bank'): Bill
    {
        $bill = Bill::where('business_id', $businessId)->findOrFail($billId);

        $vatApplicable = ($bill->vat_decimal ?? 0) > 0;
        $whtRate = (float) ($bill->wht_rate_decimal ?? 0);

        // Use subtotal+vat if total is missing
        $amount = $bill->total ?? (($bill->subtotal ?? 0) + ($bill->vat_decimal ?? 0));

        (new PostingService())->postExpense([
            'business_id' => $businessId,
            'date' => $date,
            'memo' => 'Bill ' . ($bill->number ?? $bill->id),
            'amount' => (float)$amount,
            'price_input_mode' => 'gross',
            'vat_applicable' => $vatApplicable,
            'wht_rate' => $whtRate,
            'payment_method' => $paymentMethod,
            'category_id' => null,
            'vendor_id' => $bill->vendor_id,
        ]);

        // FX Gain/Loss posting if currency != THB
        if (!empty($bill->currency_code) && strtoupper($bill->currency_code) !== 'THB') {
            $settlementRate = \App\Models\ExchangeRate::where('base_currency', strtoupper($bill->currency_code))
                ->where('quote_currency', 'THB')
                ->where('rate_date', '<=', $date)
                ->orderByDesc('rate_date')
                ->value('rate_decimal');

            if ($settlementRate && $settlementRate > 0) {
                (new FxGainLossService())->postBillSettlement($bill, (float) $settlementRate, $date);
            }
        }

        // Generate WHT certificate record (summary) if any
        $whtAmount = (float) ($bill->wht_amount_decimal ?? 0);
        if ($whtRate > 0 && $whtAmount <= 0) {
            // basic derive from subtotal when not provided
            $net = (float) ($bill->subtotal ?? 0);
            $whtAmount = round($net * $whtRate, 2);
        }
        if ($whtAmount > 0) {
            $number = \App\Domain\Documents\Numbering::next('wht', $businessId, $date);
            WhtCertificate::create([
                'business_id' => $businessId,
                'vendor_id' => $bill->vendor_id,
                'period_year' => (int)date('Y', strtotime($date)),
                'period_month' => (int)date('n', strtotime($date)),
                'total_paid' => (float)$amount,
                'wht_rate_decimal' => $whtRate,
                'wht_amount' => $whtAmount,
                'form_type' => '3',
                'number' => $number,
                'issued_at' => $date,
            ]);
        }

        $bill->status = 'paid';
        $bill->save();
        return $bill;
    }
}
