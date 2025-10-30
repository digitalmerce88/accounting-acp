<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounting\ReportController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Middleware\EnsureRole;
use App\Http\Controllers\Admin\Accounting\AccountsController as AdminAccounts;
use App\Http\Controllers\Admin\Accounting\JournalsController as AdminJournals;
use App\Http\Controllers\Admin\Accounting\ReportsController as AdminReports;

Route::get('/', fn()=> redirect('/admin'));
Route::prefix('admin')->middleware(['web', EnsureRole::class.':admin,accountant'])->group(function () {
    // Admin landing: redirect to default report
    Route::get('/', fn () => redirect()->route('reports.trial'))->name('admin.home');
    // Admin Accounting routes (new)
    Route::prefix('accounting')->name('admin.accounting.')->group(function(){
        // Accounts CRUD
        Route::get('/accounts', [AdminAccounts::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AdminAccounts::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{id}/edit', [AdminAccounts::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{id}', [AdminAccounts::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{id}', [AdminAccounts::class, 'destroy'])->name('accounts.destroy');

        // Journals
        Route::get('/journals', [AdminJournals::class, 'index'])->name('journals.index');
        Route::get('/journals/create', [AdminJournals::class, 'create'])->name('journals.create');
        Route::post('/journals', [AdminJournals::class, 'store'])->name('journals.store');
        Route::get('/journals/{id}', [AdminJournals::class, 'show'])->name('journals.show');
        Route::delete('/journals/{id}', [AdminJournals::class, 'destroy'])->name('journals.destroy');

        // Reports
        Route::get('/reports/trial-balance', [AdminReports::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/reports/ledger', [AdminReports::class, 'ledger'])->name('reports.ledger');
        Route::get('/reports/trial-balance.csv', [AdminReports::class, 'trialBalanceCsv'])->name('reports.trial-balance.csv');
        Route::get('/reports/ledger.csv', [AdminReports::class, 'ledgerCsv'])->name('reports.ledger.csv');
    });
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
