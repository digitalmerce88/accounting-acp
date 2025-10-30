<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounting\ReportController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Accounting\JournalController;

Route::get('/', fn()=> redirect('/admin'));
Route::prefix('admin')->group(function () {
    // Admin landing: redirect to default report
    Route::get('/', fn () => redirect()->route('reports.trial'))->name('admin.home');
    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial');
    Route::get('/reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');
    Route::get('/reports/pnl', [ReportController::class, 'pnl'])->name('reports.pnl');
    Route::get('/reports/bs', [ReportController::class, 'balanceSheet'])->name('reports.bs');
    Route::get('/reports/trial-balance.csv', [ReportController::class, 'trialCsv'])->name('reports.trial.csv');
    Route::get('/reports/ledger.csv', [ReportController::class, 'ledgerCsv'])->name('reports.ledger.csv');
    Route::get('/reports/pnl.csv', [ReportController::class, 'pnlCsv'])->name('reports.pnl.csv');
    Route::get('/reports/bs.csv', [ReportController::class, 'bsCsv'])->name('reports.bs.csv');
    Route::get('/settings/backup', [BackupController::class, 'download'])->name('settings.backup');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
});
