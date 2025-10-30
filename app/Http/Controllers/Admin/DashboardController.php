<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Accounting\Reports\TrialBalance as TrialBalanceReport;
use App\Models\{Account, JournalEntry};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Basic metrics
        $accountsCount = Account::count();
        $journalsCount = JournalEntry::count();
        $recentJournals = JournalEntry::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id','date','memo','status']);

        // Trial balance totals (sums of dr/cr)
        $tbRows = (new TrialBalanceReport())->run(null, null);
        $tbTotalDr = 0; $tbTotalCr = 0;
        foreach ($tbRows as $r) { // [code, name, type, dr, cr]
            $tbTotalDr += (float)($r[3] ?? 0);
            $tbTotalCr += (float)($r[4] ?? 0);
        }

        $payload = [
            'metrics' => [
                'accounts_count' => $accountsCount,
                'journals_count' => $journalsCount,
                'tb_total_dr' => $tbTotalDr,
                'tb_total_cr' => $tbTotalCr,
            ],
            'recent_journals' => $recentJournals,
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Admin/Dashboard', $payload);
    }
}
