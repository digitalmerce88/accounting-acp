<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;

class TrialBalanceService
{
    /**
     * Build trial balance rows between dates
     * Returns array of rows [code,name,type,dr,cr]
     */
    public function build(?string $from = null, ?string $to = null): array
    {
        $report = new TrialBalanceReport();
        return $report->run($from, $to);
    }
}
