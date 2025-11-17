<?php

namespace App\Domain\Banking;

use App\Models\{BankTransaction, Reconciliation, ReconciliationMatch, Transaction};
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    public function importCsv(int $businessId, int $bankAccountId, string $path, array $mapping = []): int
    {
        $fh = fopen($path, 'r');
        if (!$fh) { throw new \RuntimeException('Cannot open CSV'); }
        $count = 0;
        $header = fgetcsv($fh);
        if (!$header) { fclose($fh); return 0; }
        // Build column map
        $cols = array_map(fn($h)=>strtolower(trim($h)), $header);
        $idx = [
            'date' => $this->findCol($cols, $mapping['date'] ?? 'date'),
            'amount' => $this->findCol($cols, $mapping['amount'] ?? 'amount'),
            'description' => $this->findCol($cols, $mapping['description'] ?? 'description'),
            'reference' => $this->findCol($cols, $mapping['reference'] ?? 'reference'),
        ];
        while (($row = fgetcsv($fh)) !== false) {
            $date = $row[$idx['date']] ?? null;
            $amount = (float)($row[$idx['amount']] ?? 0);
            $description = $idx['description']!==null ? ($row[$idx['description']] ?? null) : null;
            $reference = $idx['reference']!==null ? ($row[$idx['reference']] ?? null) : null;
            if (!$date || $amount === 0.0) { continue; }
            BankTransaction::create([
                'business_id' => $businessId,
                'bank_account_id' => $bankAccountId,
                'date' => date('Y-m-d', strtotime($date)),
                'amount_decimal' => round($amount,2),
                'description' => $description,
                'reference' => $reference,
                'raw_payload' => json_encode($row),
                'matched' => false,
            ]);
            $count++;
        }
        fclose($fh);
        return $count;
    }

    private function findCol(array $cols, string $pref): ?int
    {
        $i = array_search(strtolower($pref), $cols, true);
        return $i === false ? null : $i;
    }

    public function autoMatch(int $businessId, int $bankAccountId, string $startDate, string $endDate, ?int $reconciliationId = null): int
    {
        return DB::transaction(function () use ($businessId, $bankAccountId, $startDate, $endDate, $reconciliationId) {
            $rec = $reconciliationId
                ? Reconciliation::where('business_id',$businessId)->where('bank_account_id',$bankAccountId)->findOrFail($reconciliationId)
                : Reconciliation::create([
                    'business_id'=>$businessId,
                    'bank_account_id'=>$bankAccountId,
                    'period_start'=>$startDate,
                    'period_end'=>$endDate,
                ]);

            $bankTxns = BankTransaction::where('business_id',$businessId)
                ->where('bank_account_id',$bankAccountId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('matched', false)
                ->orderBy('date')
                ->get();

            if ($bankTxns->isEmpty()) { return 0; }

            // Internal transactions table (income/expense) — already posted
            $internal = Transaction::where('business_id',$businessId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($t) { return sprintf('%0.2f', (float)$t->amount) . '|' . substr(trim((string)$t->memo ?? ''),0,20); });

            $matched = 0;
            foreach ($bankTxns as $bt) {
                $rx = (string)($bt->reference ?? $bt->description ?? '');
                $key = sprintf('%0.2f', (float)$bt->amount_decimal) . '|' . substr(trim($rx),0,20);
                $candidates = $internal->get($key) ?? collect();
                $t = $candidates->shift();
                if ($t) {
                    ReconciliationMatch::create([
                        'reconciliation_id' => $rec->id,
                        'bank_transaction_id' => $bt->id,
                        'transaction_id' => $t->id,
                        'matched_amount_decimal' => round((float)$bt->amount_decimal, 2),
                        'method' => 'auto',
                    ]);
                    $bt->matched = true; $bt->save();
                    $matched++;
                }
            }

            return $matched;
        });
    }
}
