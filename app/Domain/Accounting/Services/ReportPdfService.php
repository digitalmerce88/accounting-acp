<?php

namespace App\Domain\Accounting\Services;

use RuntimeException;

class ReportPdfService
{
    /**
     * Render Trial Balance PDF placeholder.
     * Intentionally not configured at P0; throws until a PDF solution is decided.
     */
    public function trialBalance(array $payload): never
    {
        throw new RuntimeException('PDF export not configured');
    }

    /**
     * Render Ledger PDF placeholder.
     * Intentionally not configured at P0; throws until a PDF solution is decided.
     */
    public function ledger(array $payload): never
    {
        throw new RuntimeException('PDF export not configured');
    }
}
