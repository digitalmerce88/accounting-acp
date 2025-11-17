<?php

namespace App\Domain\Documents;

class DocumentCalculator
{
    public static function compute(array $items, string $discountType = 'none', float $discountValue = 0.0, string $depositType = 'none', float $depositValue = 0.0): array
    {
        $subtotal = 0.0; $rawVat = 0.0;
        foreach ($items as $it) {
            $qty = (float)($it['qty_decimal'] ?? 0);
            $price = (float)($it['unit_price_decimal'] ?? 0);
            $line = $qty * $price;
            $subtotal += $line;
            $rawVat += $line * ((float)($it['vat_rate_decimal'] ?? 0) / 100.0);
        }

        // Discount
        $discountType = $discountType ?: 'none';
        $discountValue = (float)$discountValue;
        if ($discountType === 'percent') {
            $discountValue = min(max($discountValue, 0), 100);
            $discountAmount = $subtotal * ($discountValue / 100.0);
        } elseif ($discountType === 'amount') {
            $discountAmount = min(max($discountValue, 0), $subtotal);
        } else {
            $discountAmount = 0.0; $discountValue = 0.0; $discountType = 'none';
        }

        $adjustedSubtotal = $subtotal - $discountAmount;
        $vat = $subtotal > 0 ? $rawVat * ($adjustedSubtotal / $subtotal) : 0.0;
        $total = $adjustedSubtotal + $vat;

        // Deposit
        $depositType = $depositType ?: 'none';
        $depositValue = (float)$depositValue;
        if ($depositType === 'percent') {
            $depositValue = min(max($depositValue, 0), 100);
            $depositAmount = $total * ($depositValue / 100.0);
        } elseif ($depositType === 'amount') {
            $depositAmount = min(max($depositValue, 0), $total);
        } else {
            $depositAmount = 0.0; $depositValue = 0.0; $depositType = 'none';
        }

        $amountDue = $total - $depositAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_type' => $discountType,
            'discount_value_decimal' => round($discountValue, 2),
            'discount_amount_decimal' => round($discountAmount, 2),
            'adjusted_subtotal' => round($adjustedSubtotal, 2),
            'vat_decimal' => round($vat, 2),
            'total' => round($total, 2),
            'deposit_type' => $depositType,
            'deposit_value_decimal' => round($depositValue, 2),
            'deposit_amount_decimal' => round($depositAmount, 2),
            'amount_due' => round($amountDue, 2),
        ];
    }
}
