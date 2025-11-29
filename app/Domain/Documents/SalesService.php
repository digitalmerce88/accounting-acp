<?php

namespace App\Domain\Documents;

use App\Models\Invoice;
use App\Domain\Accounting\PostingService;
use App\Domain\Accounting\Services\FxGainLossService;
use Illuminate\Support\Carbon;

class SalesService
{
    public function markPaid(int $invoiceId, int $businessId, string $date, string $paymentMethod = 'bank'): Invoice
    {
        $inv = Invoice::where('business_id', $businessId)->findOrFail($invoiceId);

        // Idempotent: if already paid, return directly
        if ($inv->status === 'paid') {
            return $inv;
        }

        // derive VAT applicability from invoice fields
        $vatApplicable = ($inv->vat_decimal ?? 0) > 0 || ($inv->is_tax_invoice ?? false);
        $whtRate = 0.0; // simple: invoices generally WHT 0 unless specified in UI later

        $entry = (new PostingService())->postIncome([
            'business_id' => $businessId,
            'date' => $date,
            'memo' => 'Invoice ' . ($inv->number ?? $inv->id),
            'amount' => (float) ($inv->total ?? 0),
            'price_input_mode' => 'gross',
            'vat_applicable' => $vatApplicable,
            'wht_rate' => $whtRate,
            'payment_method' => $paymentMethod,
            'category_id' => null,
            'customer_id' => $inv->customer_id,
        ]);

        // FX Gain/Loss posting if currency != THB
        if (!empty($inv->currency_code) && strtoupper($inv->currency_code) !== 'THB') {
            // Fetch latest rate where base_currency = invoice currency, quote THB, rate_date <= settlement date
            $settlementRate = \App\Models\ExchangeRate::where('base_currency', strtoupper($inv->currency_code))
                ->where('quote_currency', 'THB')
                ->where('rate_date', '<=', $date)
                ->orderByDesc('rate_date')
                ->value('rate_decimal');

            if ($settlementRate && $settlementRate > 0) {
                (new FxGainLossService())->postInvoiceSettlement($inv, (float) $settlementRate, $date);
            }
        }

        // save posting id for traceability
        $inv->posting_entry_id = $entry->id ?? null;
        $inv->status = 'paid';
        $inv->save();
        return $inv;
    }
}
