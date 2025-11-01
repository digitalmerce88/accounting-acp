<?php

namespace App\Domain\Documents;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Invoice;
use App\Models\Bill;

class Numbering
{
    /**
     * Generate the next number for a given document type using the pattern in config/documents.php
     * Supported tokens:
     * - YYYYMM, YYYY, YY, MM, DD
     * - #...# (one or more #'s) for zero-padded sequence (length = number of #'s)
     */
    public static function next(string $type, int $businessId, $date): string
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $patterns = config('documents.numbering', []);
        $pattern = $patterns[$type] ?? ($type === 'invoice' ? 'INV-YYYYMM-####' : ($type === 'bill' ? 'BILL-YYYYMM-####' : 'DOC-YYYYMM-####'));

        switch ($type) {
            case 'invoice':
                $seq = self::sequenceForInvoice($businessId, $date, $pattern);
                return self::format($pattern, $seq, $date);
            case 'bill':
                $seq = self::sequenceForBill($businessId, $date, $pattern);
                return self::format($pattern, $seq, $date);
            default:
                $seq = 1;
                return self::format($pattern, $seq, $date);
        }
    }

    protected static function sequenceForInvoice(int $bizId, Carbon $date, string $pattern): int
    {
        $q = Invoice::where('business_id', $bizId);
        if (str_contains($pattern, 'YYYY') || str_contains($pattern, 'YY')) {
            $q->whereYear('issue_date', (int)$date->format('Y'));
        }
        if (str_contains($pattern, 'MM')) {
            $q->whereMonth('issue_date', (int)$date->format('n'));
        }
        if (str_contains($pattern, 'DD')) {
            $q->whereDay('issue_date', (int)$date->format('j'));
        }
        return $q->count() + 1;
    }

    protected static function sequenceForBill(int $bizId, Carbon $date, string $pattern): int
    {
        $q = Bill::where('business_id', $bizId);
        if (str_contains($pattern, 'YYYY') || str_contains($pattern, 'YY')) {
            $q->whereYear('bill_date', (int)$date->format('Y'));
        }
        if (str_contains($pattern, 'MM')) {
            $q->whereMonth('bill_date', (int)$date->format('n'));
        }
        if (str_contains($pattern, 'DD')) {
            $q->whereDay('bill_date', (int)$date->format('j'));
        }
        return $q->count() + 1;
    }

    protected static function format(string $pattern, int $seq, Carbon $date): string
    {
        // Replace date tokens (order matters to avoid partial overlaps)
        $out = $pattern;
        $out = str_replace('YYYYMM', $date->format('Ym'), $out);
        $out = str_replace('YYMM', $date->format('ym'), $out);
        $out = str_replace('YYYY', $date->format('Y'), $out);
        $out = str_replace('YY', $date->format('y'), $out);
        $out = str_replace('MM', $date->format('m'), $out);
        $out = str_replace('DD', $date->format('d'), $out);

        // Replace first group of consecutive #'s with padded sequence
        $out = preg_replace_callback('/(#+)/', function ($m) use ($seq) {
            $len = strlen($m[1]);
            return str_pad((string)$seq, $len, '0', STR_PAD_LEFT);
        }, $out, 1);

        return $out;
    }
}
