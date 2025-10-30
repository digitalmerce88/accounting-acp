<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Reports\Ledger as LedgerReport;

class LedgerService
{
    /**
     * Get ledger rows for an account between dates
     * Returns array of rows [date,entry_id,memo,dr,cr,balance]
     */
    public function forAccount(int $accountId, ?string $from = null, ?string $to = null): array
    {
        $report = new LedgerReport();
        return $report->run($accountId, $from, $to);
    }
}
