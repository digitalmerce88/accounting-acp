<?php

namespace App\Domain\Documents;

use App\Models\Invoice;
use App\Domain\Accounting\PostingService;

class SalesService
{
    public function markPaid(int $invoiceId, int $businessId, string $date, string $paymentMethod = 'bank'): Invoice
    {
        $inv = Invoice::where('business_id', $businessId)->findOrFail($invoiceId);

        // derive VAT applicability from invoice fields
        $vatApplicable = ($inv->vat_decimal ?? 0) > 0 || ($inv->is_tax_invoice ?? false);
        $whtRate = 0.0; // simple: invoices generally WHT 0 unless specified in UI later

        (new PostingService())->postIncome([
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

        $inv->status = 'paid';
        $inv->save();
        return $inv;
    }
}
