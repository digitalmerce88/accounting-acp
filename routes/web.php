
<?php

    use App\Http\Controllers\Accounting\JournalController;
    use App\Http\Controllers\Admin\Accounting\AccountsController as AdminAccounts;
    use App\Http\Controllers\Admin\Accounting\CloseController as AdminClose;
    use App\Http\Controllers\Admin\Accounting\JournalsController as AdminJournals;
    use App\Http\Controllers\Admin\Accounting\ReportsController as AdminReports;
    use App\Http\Controllers\Admin\Accounting\TransactionsController as AdminTransactions;
    use App\Http\Controllers\Admin\BackupController;
    use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
    use App\Http\Controllers\Admin\UsersController as AdminUsers;
    use App\Http\Controllers\ProfileController;
    use App\Http\Middleware\EnsureRole;
    use Illuminate\Foundation\Application;
    use Illuminate\Support\Facades\Route;
    use Inertia\Inertia;

    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
        ]);
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    // Lightweight debug route to test logging and request flow (no auth)
    Route::get('/_debug/logtest', function () {
        \Illuminate\Support\Facades\Log::info('debug/logtest hit', ['ts' => now()->toDateTimeString()]);
        return response()->json(['ok' => true]);
    });

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__ . '/auth.php';

    // Admin area protected by auth + role
    Route::prefix('admin')->middleware(['auth', EnsureRole::class . ':admin,accountant'])->group(function () {
        // Admin landing: dashboard
        Route::get('/', [AdminDashboard::class, 'index'])->name('admin.home');

        // Old backup and simple journal endpoint (keep for compatibility)
        Route::get('/settings/backup', [BackupController::class, 'download'])->name('settings.backup');
        Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');

    // Admin Accounting routes
        Route::prefix('accounting')->name('admin.accounting.')->group(function () {
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
            Route::get('/journals/{id}/edit', [AdminJournals::class, 'edit'])->name('journals.edit');
            Route::put('/journals/{id}', [AdminJournals::class, 'update'])->name('journals.update');
            Route::get('/journals/{id}', [AdminJournals::class, 'show'])->name('journals.show');
            Route::delete('/journals/{id}', [AdminJournals::class, 'destroy'])->name('journals.destroy');

            // Reports
            Route::get('/reports/trial-balance', [AdminReports::class, 'trialBalance'])->name('reports.trial-balance');
            Route::get('/reports/ledger', [AdminReports::class, 'ledger'])->name('reports.ledger');
            Route::get('/reports/profit-and-loss', [AdminReports::class, 'profitAndLoss'])->name('reports.pnl');
            Route::get('/reports/trial-balance.csv', [AdminReports::class, 'trialBalanceCsv'])->name('reports.trial-balance.csv');
            Route::get('/reports/trial-balance.pdf', [AdminReports::class, 'trialBalancePdf'])->name('reports.trial-balance.pdf');
            Route::get('/reports/ledger.csv', [AdminReports::class, 'ledgerCsv'])->name('reports.ledger.csv');
            Route::get('/reports/profit-and-loss.csv', [AdminReports::class, 'profitAndLossCsv'])->name('reports.pnl.csv');
            Route::get('/reports/ledger.pdf', [AdminReports::class, 'ledgerPdf'])->name('reports.ledger.pdf');

            // S3 Summary Reports
            Route::get('/reports/overview', [AdminReports::class, 'overview'])->name('reports.overview');
            Route::get('/reports/by-category', [AdminReports::class, 'byCategory'])->name('reports.by-category');
            Route::get('/reports/tax/purchase-vat', [AdminReports::class, 'taxPurchaseVat'])->name('reports.tax.purchase');
            Route::get('/reports/tax/sales-vat', [AdminReports::class, 'taxSalesVat'])->name('reports.tax.sales');
            Route::get('/reports/tax/wht-summary', [AdminReports::class, 'whtSummary'])->name('reports.tax.wht');

            // CSV
            Route::get('/reports/overview.csv', [AdminReports::class, 'overviewCsv'])->name('reports.overview.csv');
            Route::get('/reports/by-category.csv', [AdminReports::class, 'byCategoryCsv'])->name('reports.by-category.csv');
            Route::get('/reports/tax/purchase-vat.csv', [AdminReports::class, 'taxPurchaseVatCsv'])->name('reports.tax.purchase.csv');
            Route::get('/reports/tax/sales-vat.csv', [AdminReports::class, 'taxSalesVatCsv'])->name('reports.tax.sales.csv');
            Route::get('/reports/tax/wht-summary.csv', [AdminReports::class, 'whtSummaryCsv'])->name('reports.tax.wht.csv');

            // Close month
            Route::post('/close/month', [AdminClose::class, 'month'])->name('close.month');

            // S2 Income / Expense
            Route::get('/income', [AdminTransactions::class, 'index'])->defaults('kind', 'income')->name('income.index');
            Route::get('/income/create', [AdminTransactions::class, 'create'])->defaults('kind', 'income')->name('income.create');
            Route::post('/income', [AdminTransactions::class, 'store'])->defaults('kind', 'income')->name('income.store');
            Route::get('/income/{id}', [AdminTransactions::class, 'show'])->defaults('kind', 'income')->name('income.show');
            Route::get('/income/{id}/edit', [AdminTransactions::class, 'edit'])->defaults('kind', 'income')->name('income.edit');
            Route::put('/income/{id}', [AdminTransactions::class, 'update'])->defaults('kind', 'income')->name('income.update');
            Route::delete('/income/{id}', [AdminTransactions::class, 'destroy'])->defaults('kind', 'income')->name('income.destroy');

            Route::get('/expense', [AdminTransactions::class, 'index'])->defaults('kind', 'expense')->name('expense.index');
            Route::get('/expense/create', [AdminTransactions::class, 'create'])->defaults('kind', 'expense')->name('expense.create');
            Route::post('/expense', [AdminTransactions::class, 'store'])->defaults('kind', 'expense')->name('expense.store');
            Route::get('/expense/{id}', [AdminTransactions::class, 'show'])->defaults('kind', 'expense')->name('expense.show');
            Route::get('/expense/{id}/edit', [AdminTransactions::class, 'edit'])->defaults('kind', 'expense')->name('expense.edit');
            Route::put('/expense/{id}', [AdminTransactions::class, 'update'])->defaults('kind', 'expense')->name('expense.update');
            Route::delete('/expense/{id}', [AdminTransactions::class, 'destroy'])->defaults('kind', 'expense')->name('expense.destroy');
        });

        // Admin HR routes
        Route::prefix('hr')->name('admin.hr.')->group(function () {
            $emp = \App\Http\Controllers\Admin\HR\EmployeesController::class;
            $pay = \App\Http\Controllers\Admin\HR\PayrollController::class;

            Route::get('/employees', [$emp, 'index'])->name('employees.index');
            Route::get('/employees/create', [$emp, 'create'])->name('employees.create');
            Route::post('/employees', [$emp, 'store'])->name('employees.store');
            Route::get('/employees/{id}', [$emp, 'show'])->name('employees.show');
            Route::get('/employees/{id}/edit', [$emp, 'edit'])->name('employees.edit');
            Route::put('/employees/{id}', [$emp, 'update'])->name('employees.update');
            Route::delete('/employees/{id}', [$emp, 'destroy'])->name('employees.destroy');
            Route::post('/employees/{id}/restore', [$emp, 'restore'])->name('employees.restore');

            Route::get('/payroll', [$pay, 'index'])->name('payroll.index');
            Route::post('/payroll', [$pay, 'store'])->name('payroll.store');
            Route::get('/payroll/{id}', [$pay, 'show'])->name('payroll.show');
            Route::post('/payroll/{id}/lock', [$pay, 'lock'])->name('payroll.lock');
            Route::post('/payroll/{id}/unlock', [$pay, 'unlock'])->name('payroll.unlock');
            Route::post('/payroll/{id}/pay', [$pay, 'pay'])->name('payroll.pay');
            Route::delete('/payroll/{id}', [$pay, 'destroy'])->name('payroll.destroy');
        });

        // Admin Users management (admin only)
        Route::middleware([EnsureRole::class . ':admin'])->prefix('users')->name('admin.users.')->group(function () {
            Route::get('/', [AdminUsers::class, 'index'])->name('index');
            Route::patch('/{user}/roles', [AdminUsers::class, 'updateRoles'])->name('roles.update');
            // Debug JSON endpoint removed
        });
});
