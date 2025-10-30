
<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Accounting\ReportController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Middleware\EnsureRole;
use App\Http\Controllers\Admin\Accounting\AccountsController as AdminAccounts;
use App\Http\Controllers\Admin\Accounting\JournalsController as AdminJournals;
use App\Http\Controllers\Admin\Accounting\ReportsController as AdminReports;
use App\Http\Controllers\Admin\UsersController as AdminUsers;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Admin area protected by auth + role
Route::prefix('admin')->middleware(['auth', EnsureRole::class.':admin,accountant'])->group(function () {
    // Admin landing: redirect to default report
    Route::get('/', fn () => redirect()->route('admin.accounting.reports.trial-balance'))->name('admin.home');

    // Old backup and simple journal endpoint (keep for compatibility)
    Route::get('/settings/backup', [BackupController::class, 'download'])->name('settings.backup');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');

    // Admin Accounting routes
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

    // Admin Users management (admin only)
    Route::middleware([EnsureRole::class.':admin'])->prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUsers::class, 'index'])->name('index');
        Route::patch('/{user}/roles', [AdminUsers::class, 'updateRoles'])->name('roles.update');
    });
});
