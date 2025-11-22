<?php

namespace App\Domain\Documents;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Quote;
use App\Models\PurchaseOrder;
use App\Models\WhtCertificate;

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
            case 'quote':
                $seq = self::sequenceForModel(Quote::class, $businessId, $date, $pattern, 'issue_date');
                return self::format($pattern, $seq, $date);
            case 'po':
                $seq = self::sequenceForModel(PurchaseOrder::class, $businessId, $date, $pattern, 'issue_date');
                return self::format($pattern, $seq, $date);
            case 'wht':
                $seq = self::sequenceForModel(WhtCertificate::class, $businessId, $date, $pattern, 'issued_at');
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

    /**
     * Generic sequence for model classes where date field name may vary.
     */
    protected static function sequenceForModel(string $modelClass, int $bizId, Carbon $date, string $pattern, string $dateField = 'issue_date'): int
    {
        if (!class_exists($modelClass)) return 1;
        $q = $modelClass::where('business_id', $bizId);
        // apply year/month/day filters based on pattern tokens
        if (str_contains($pattern, 'YYYY') || str_contains($pattern, 'YY')) {
            $q->whereYear($dateField, (int)$date->format('Y'));
        }
        if (str_contains($pattern, 'MM')) {
            $q->whereMonth($dateField, (int)$date->format('n'));
        }
        if (str_contains($pattern, 'DD')) {
            $q->whereDay($dateField, (int)$date->format('j'));
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
