<?php

namespace App\Domain\Accounting;

use App\Models\{Account, JournalEntry, JournalLine, Category, Business};
use Illuminate\Support\Facades\Log;

class PostingService
{
    /**
     * Contract (common fields):
     * - business_id:int, date:string (Y-m-d), memo:string
     * - amount:float (2dp), price_input_mode:'gross'|'net'|'novat'
     * - vat_applicable:bool
     * - wht_rate:float (e.g. 0 or 0.03)
     * - payment_method:'cash'|'bank'
     * - category_id:int (for default posting account)
     *
     * Returns JournalEntry model
     */
    public function postIncome(array $data): JournalEntry
    {
        [$net, $vat, $total, $wht] = $this->calc($data['amount'], $data['price_input_mode'], $data['vat_applicable'] ?? false, $data['wht_rate'] ?? 0.0);

        $ids = $this->resolveAccounts((int)$data['business_id'], $data['payment_method'] ?? 'bank', 'income', (int)($data['category_id'] ?? 0));

        $entry = JournalEntry::create([
            'business_id' => $data['business_id'],
            'date' => $data['date'],
            'memo' => $data['memo'] ?? 'Income',
            'status' => 'posted',
        ]);

        // Dr Bank/Cash (total or total-WHT depending on config)
        $drCash = $this->withholdAffectsCash() ? ($total - $wht) : $total;
        $this->line($entry, $ids['cash'], $drCash, 0);

        // Dr WHT Receivable if WHT>0
        if ($wht > 0) {
            $this->line($entry, $ids['wht_receivable'], $wht, 0);
        }

        // Cr Revenue (net)
        $this->line($entry, $ids['revenue'], 0, $net);

        // Cr VAT Payable (vat)
        if ($vat > 0) {
            $this->line($entry, $ids['vat_payable'], 0, $vat);
        }

        return $entry;
    }

    public function postExpense(array $data): JournalEntry
    {
        [$net, $vat, $total, $wht] = $this->calc($data['amount'], $data['price_input_mode'], $data['vat_applicable'] ?? false, $data['wht_rate'] ?? 0.0);

        $ids = $this->resolveAccounts((int)$data['business_id'], $data['payment_method'] ?? 'bank', 'expense', (int)($data['category_id'] ?? 0));

        $entry = JournalEntry::create([
            'business_id' => $data['business_id'],
            'date' => $data['date'],
            'memo' => $data['memo'] ?? 'Expense',
            'status' => 'posted',
        ]);

        // Dr Expense (net)
        $this->line($entry, $ids['expense'], $net, 0);

        // Dr VAT Receivable (vat)
        if ($vat > 0) {
            $this->line($entry, $ids['vat_receivable'], $vat, 0);
        }

        // Cr Bank/Cash (total or total-WHT depending on config)
        $crCash = $this->withholdAffectsCash() ? ($total - $wht) : $total;
        $this->line($entry, $ids['cash'], 0, $crCash);

        // Cr WHT Payable if WHT>0
        if ($wht > 0) {
            $this->line($entry, $ids['wht_payable'], 0, $wht);
        }

        return $entry;
    }

    private function calc(float $amount, string $mode, bool $vatApplicable, float $whtRate): array
    {
        $amount = round($amount, 2);
        $whtRate = max(0.0, $whtRate);

        if (!$vatApplicable || $mode === 'novat') {
            $net = $mode === 'net' ? $amount : $amount; // in novat, net==total
            $vat = 0.0;
            $total = $amount;
        } elseif ($mode === 'gross') {
            $net = round($amount / 1.07, 2, PHP_ROUND_HALF_UP);
            $vat = round($amount - $net, 2, PHP_ROUND_HALF_UP);
            $total = $amount;
        } else { // net
            $net = $amount;
            $total = round($net * 1.07, 2, PHP_ROUND_HALF_UP);
            $vat = round($total - $net, 2, PHP_ROUND_HALF_UP);
        }

        // Assume WHT calculated on net
        $wht = $whtRate > 0 ? round($net * $whtRate, 2, PHP_ROUND_HALF_UP) : 0.0;

        return [$net, $vat, $total, $wht];
    }

    private function withholdAffectsCash(): bool
    {
        return (bool)config('accounting.withhold_affects_cash', true);
    }

    private function resolveAccounts(int $businessId, string $paymentMethod, string $kind, int $categoryId): array
    {
        $d = config('accounting.defaults');
        $byCode = fn(string $code) => Account::where(['business_id' => $businessId, 'code' => $code])->value('id');

        $cashId = $byCode($paymentMethod === 'cash' ? $d['cash_code'] : $d['bank_code']);

        $revenueId = null; $expenseId = null;
        if ($categoryId) {
            $cat = Category::find($categoryId);
            if ($cat && $cat->default_account_id) {
                if ($cat->type === 'income') $revenueId = $cat->default_account_id;
                if ($cat->type === 'expense') $expenseId = $cat->default_account_id;
            }
        }
        $revenueId = $revenueId ?: $byCode($d['revenue_code']);
        $expenseId = $expenseId ?: $byCode($d['expense_code']);

        $mapped = [
            'cash' => $cashId,
            'revenue' => $revenueId,
            'expense' => $expenseId,
            'vat_receivable' => $byCode($d['vat_receivable_code']),
            'vat_payable' => $byCode($d['vat_payable_code']),
            'wht_receivable' => $byCode($d['wht_receivable_code']),
            'wht_payable' => $byCode($d['wht_payable_code']),
        ];

        // If any account ids are missing, log and throw a descriptive exception
        $missing = [];
        foreach ($mapped as $key => $val) {
            if (empty($val)) {
                // find the configured code for nicer message
                $codeKey = match($key) {
                    'cash' => ($paymentMethod === 'cash' ? $d['cash_code'] : $d['bank_code']),
                    'revenue' => $d['revenue_code'],
                    'expense' => $d['expense_code'],
                    'vat_receivable' => $d['vat_receivable_code'],
                    'vat_payable' => $d['vat_payable_code'],
                    'wht_receivable' => $d['wht_receivable_code'],
                    'wht_payable' => $d['wht_payable_code'],
                    default => null,
                };
                $missing[] = [$key => $codeKey];
            }
        }

        if (!empty($missing)) {
            Log::error('PostingService.resolveAccounts missing account ids', ['business_id' => $businessId, 'missing' => $missing]);
            throw new \RuntimeException('Missing Chart of Accounts entries for: ' . json_encode($missing));
        }

        return $mapped;
    }

    private function line(JournalEntry $e, int $accountId, float $debit, float $credit): void
    {
        JournalLine::create([
            'entry_id' => $e->id,
            'account_id' => $accountId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ]);
    }
}
