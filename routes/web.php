
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
    use App\Http\Controllers\Api\CurrencyController;

    // Redirect root to login page so users land on authentication first
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $menus = [];
        if ($user && method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin')) {
                $menus[] = ['label' => 'Admin', 'route' => 'admin.home'];
            }
            if ($user->hasRole('accountant')) {
                $menus[] = ['label' => 'Income/Expense', 'route' => 'admin.accounting.income.index'];
            }
            if ($user->hasRole('viewer')) {
                $menus[] = ['label' => 'Reports', 'route' => 'admin.accounting.reports.overview'];
            }
        }
        // Always include Dashboard link as first item
        array_unshift($menus, ['label' => 'Dashboard', 'route' => 'dashboard']);

        return Inertia::render('Dashboard', ['menus' => $menus]);
    })->middleware(['auth', 'verified'])->name('dashboard');

    // Lightweight debug route to test logging and request flow (no auth)
    Route::get('/_debug/logtest', function () {
        \Illuminate\Support\Facades\Log::info('debug/logtest hit', ['ts' => now()->toDateTimeString()]);
        return response()->json(['ok' => true]);
    });

    // Authenticated debug: return current user's roles (useful to debug 403/role issues)
    Route::get('/_debug/user-roles', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (! $user) { return response()->json(['ok' => false, 'message' => 'not authenticated'], 401); }
        $roles = [];
        try { $roles = $user->roles()->pluck('slug')->all(); } catch (\Throwable $e) { $roles = []; }
        return response()->json(['ok' => true, 'id' => $user->id, 'email' => $user->email, 'roles' => $roles]);
    })->middleware('auth');

        // Debug route: return the same auth payload that Inertia shares to the client.
        // Useful to confirm server-side computation of is_admin without needing to inspect logs.
        Route::get('/_debug/inertia-auth', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            $isAdmin = false; $roles = [];
            try {
                if ($user) {
                    $roles = \Illuminate\Support\Facades\DB::table('user_role')
                        ->join('roles','roles.id','=','user_role.role_id')
                        ->where('user_role.user_id', $user->id)
                        ->pluck('roles.slug')
                        ->all();
                    $isAdmin = in_array('admin', $roles, true);
                }
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
            }
            return response()->json(['ok'=>true, 'user' => $user ? ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email] : null, 'is_admin'=>$isAdmin, 'roles'=>$roles]);
        })->middleware('auth');

        // Debug helper: authenticate as user id 1 (demo admin) and render a page
        // so Blade will inject the Inertia $page and our debug writer will persist it.
        Route::get('/_debug/emit-page', function () {
            try {
                \Illuminate\Support\Facades\Auth::loginUsingId(1);
                return \Inertia\Inertia::render('Admin/Documents/PO/Index');
            } catch (\Throwable $e) {
                return response('error: ' . $e->getMessage(), 500);
            }
        });

    // Lightweight currency API (no auth needed for form helpers)
    Route::get('/api/currencies', [CurrencyController::class, 'list']);
    Route::get('/api/currency-rate', [CurrencyController::class, 'latestRate']);

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Debug: compute the same 'auth' share for an arbitrary user id and return it.
    // This is unauthenticated and intended for debugging only — remove afterwards.
    Route::get('/_debug/inspect-auth/{id}', function ($id) {
        try {
            $id = (int) $id;
            $user = \App\Models\User::find($id);
            $roles = [];
            $isAdmin = false;
            if ($user) {
                $roles = \Illuminate\Support\Facades\DB::table('user_role')
                    ->join('roles','roles.id','=','user_role.role_id')
                    ->where('user_role.user_id', $user->id)
                    ->pluck('roles.slug')
                    ->all();
                $isAdmin = in_array('admin', $roles, true);
            }
            return response()->json([ 'ok' => true, 'user' => $user ? ['id'=>$user->id,'email'=>$user->email,'name'=>$user->name] : null, 'roles' => $roles, 'is_admin' => $isAdmin ]);
        } catch (\Throwable $e) {
            return response()->json([ 'ok' => false, 'error' => $e->getMessage() ], 500);
        }
    });

    require __DIR__ . '/auth.php';

    // Admin area protected by auth + role
    Route::prefix('admin')->middleware(['auth', EnsureRole::class . ':admin,accountant'])->group(function () {
        // Admin landing: dashboard
        Route::get('/', [AdminDashboard::class, 'index'])->name('admin.home');

        // Old backup and simple journal endpoint (keep for compatibility)
        Route::get('/settings/backup', [BackupController::class, 'download'])->name('settings.backup');
        Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    // Company settings
    Route::get('/settings/company', [\App\Http\Controllers\Admin\Settings\CompanyController::class, 'edit'])->name('admin.settings.company.edit');
    Route::put('/settings/company', [\App\Http\Controllers\Admin\Settings\CompanyController::class, 'update'])->name('admin.settings.company.update');

    // Exchange rates management
    Route::prefix('settings/exchange-rates')->name('admin.settings.exchange-rates.')->group(function() {
        $ctrl = \App\Http\Controllers\Admin\Settings\ExchangeRatesController::class;
        Route::get('/', [$ctrl, 'index'])->name('index');
        Route::get('/create', [$ctrl, 'create'])->name('create');
        Route::post('/', [$ctrl, 'store'])->name('store');
        Route::get('/{id}/edit', [$ctrl, 'edit'])->name('edit');
        Route::put('/{id}', [$ctrl, 'update'])->name('update');
        Route::delete('/{id}', [$ctrl, 'destroy'])->name('destroy');
    });

    // Admin Accounting routes
        Route::prefix('accounting')->name('admin.accounting.')->group(function () {
            // Accounts CRUD
            Route::get('/accounts', [AdminAccounts::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [AdminAccounts::class, 'store'])->name('accounts.store');
            Route::get('/accounts/{id}/edit', [AdminAccounts::class, 'edit'])->name('accounts.edit');
            Route::put('/accounts/{id}', [AdminAccounts::class, 'update'])->name('accounts.update');
            Route::delete('/accounts/{id}', [AdminAccounts::class, 'destroy'])->name('accounts.destroy');

            // Categories (AJAX create)
            Route::post('/categories', [\App\Http\Controllers\Admin\Accounting\CategoriesController::class, 'store'])->name('categories.store');

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

            // Bank Reconciliation
            Route::get('/bank-accounts', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'accounts'])->name('bank.accounts');
            Route::get('/bank-accounts/{id}/transactions', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'transactions'])->name('bank.transactions');
            Route::post('/bank-accounts/{id}/import', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'import'])->name('bank.import');
            Route::post('/bank/reconcile/start', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'start'])->name('bank.reconcile.start');
            Route::get('/bank/reconcile/{id}', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'show'])->name('bank.reconcile.show');
            Route::post('/bank/reconcile/{id}/auto', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'autoMatch'])->name('bank.reconcile.auto');
            Route::post('/bank/reconcile/{id}/match', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'manualMatch'])->name('bank.reconcile.match');
            Route::delete('/bank/reconcile/{id}/match/{matchId}', [\App\Http\Controllers\Admin\Accounting\BankReconciliationController::class, 'unmatch'])->name('bank.reconcile.unmatch');

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

        // Admin assets routes
        Route::prefix('assets')->name('admin.assets.')->group(function() {
            $cat = \App\Http\Controllers\Admin\Assets\AssetCategoriesController::class;
            $as  = \App\Http\Controllers\Admin\Assets\AssetsController::class;
            // Categories
            Route::get('/categories', [$cat,'index'])->name('categories.index');
            Route::get('/categories/create', [$cat,'create'])->name('categories.create');
            Route::post('/categories', [$cat,'store'])->name('categories.store');
            Route::get('/categories/{id}/edit', [$cat,'edit'])->name('categories.edit');
            Route::put('/categories/{id}', [$cat,'update'])->name('categories.update');
            Route::delete('/categories/{id}', [$cat,'destroy'])->name('categories.destroy');
            // Assets
            Route::get('/assets', [$as,'index'])->name('assets.index');
            Route::get('/assets/create', [$as,'create'])->name('assets.create');
            Route::post('/assets', [$as,'store'])->name('assets.store');
            Route::get('/assets/{id}', [$as,'show'])->name('assets.show');
            Route::get('/assets/{id}/edit', [$as,'edit'])->name('assets.edit');
            Route::put('/assets/{id}', [$as,'update'])->name('assets.update');
            Route::post('/assets/{id}/dispose', [$as,'dispose'])->name('assets.dispose');
            Route::delete('/assets/{id}', [$as,'destroy'])->name('assets.destroy');
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
            Route::get('/payroll/{id}/summary.pdf', [$pay, 'summaryPdf'])->name('payroll.summary.pdf');
            Route::get('/payroll/{id}/payslips.pdf', [$pay, 'payslipsPdf'])->name('payroll.payslips.pdf');
            Route::delete('/payroll/{id}', [$pay, 'destroy'])->name('payroll.destroy');
        });

        // Admin Documents routes
        Route::prefix('documents')->name('admin.documents.')->group(function () {
            $hist = \App\Http\Controllers\Admin\Documents\HistoryController::class;
            $inv = \App\Http\Controllers\Admin\Documents\InvoicesController::class;
            $bill = \App\Http\Controllers\Admin\Documents\BillsController::class;
            $quo = \App\Http\Controllers\Admin\Documents\QuotesController::class;
            $po  = \App\Http\Controllers\Admin\Documents\PurchaseOrdersController::class;
            $cus = \App\Http\Controllers\Admin\Documents\CustomersController::class;
            $ven = \App\Http\Controllers\Admin\Documents\VendorsController::class;

            Route::get('/invoices', [$inv,'index'])->name('invoices.index');
            Route::get('/invoices/create', [$inv,'create'])->name('invoices.create');
            Route::post('/invoices', [$inv,'store'])->name('invoices.store');
            Route::get('/invoices/{id}', [$inv,'show'])->name('invoices.show');
            Route::get('/invoices/{id}/pdf', [$inv,'pdf'])->name('invoices.pdf');
            Route::get('/invoices/{id}/receipt.pdf', [$inv,'receipt'])->name('invoices.receipt');
            Route::get('/invoices/{id}/edit', [$inv,'edit'])->name('invoices.edit');
            Route::put('/invoices/{id}', [$inv,'update'])->name('invoices.update');
            Route::delete('/invoices/{id}', [$inv,'destroy'])->name('invoices.destroy');
            Route::post('/invoices/{id}/pay', [$inv,'pay'])->name('invoices.pay');
            // Approval workflow
            Route::post('/invoices/{id}/submit', [$inv,'submit'])->name('invoices.submit');
            Route::post('/invoices/{id}/approve', [$inv,'approve'])->name('invoices.approve');
            Route::post('/invoices/{id}/lock', [$inv,'lock'])->name('invoices.lock');
            Route::post('/invoices/{id}/unlock', [$inv,'unlock'])->name('invoices.unlock');

            // customers quick search
            Route::get('/customers/search', [$cus,'search'])->name('customers.search');
            Route::post('/customers', [$cus,'store'])->name('customers.store');

            Route::get('/bills', [$bill,'index'])->name('bills.index');
            Route::get('/bills/create', [$bill,'create'])->name('bills.create');
            Route::post('/bills', [$bill,'store'])->name('bills.store');
            Route::get('/bills/{id}', [$bill,'show'])->name('bills.show');
            Route::get('/bills/{id}/pdf', [$bill,'pdf'])->name('bills.pdf');
            Route::get('/bills/{id}/wht.pdf', [$bill,'whtPdf'])->name('bills.wht.pdf');
            Route::get('/bills/{id}/edit', [$bill,'edit'])->name('bills.edit');
            Route::put('/bills/{id}', [$bill,'update'])->name('bills.update');
            Route::delete('/bills/{id}', [$bill,'destroy'])->name('bills.destroy');
            Route::post('/bills/{id}/pay', [$bill,'pay'])->name('bills.pay');
            // Approval workflow
            Route::post('/bills/{id}/submit', [$bill,'submit'])->name('bills.submit');
            Route::post('/bills/{id}/approve', [$bill,'approve'])->name('bills.approve');
            Route::post('/bills/{id}/lock', [$bill,'lock'])->name('bills.lock');
            Route::post('/bills/{id}/unlock', [$bill,'unlock'])->name('bills.unlock');

            // vendors quick search
            Route::get('/vendors/search', [$ven,'search'])->name('vendors.search');
            Route::post('/vendors', [$ven,'store'])->name('vendors.store');

            // Quotes CRUD
            Route::get('/quotes', [$quo,'index'])->name('quotes.index');
            Route::get('/quotes/create', [$quo,'create'])->name('quotes.create');
            Route::post('/quotes', [$quo,'store'])->name('quotes.store');
            Route::delete('/quotes/{id}', [$quo,'destroy'])->name('quotes.destroy');
            Route::get('/quotes/{id}/edit', [$quo,'edit'])->name('quotes.edit');
            Route::put('/quotes/{id}', [$quo,'update'])->name('quotes.update');
            Route::get('/quotes/{id}', [$quo,'show'])->name('quotes.show');
            // Quote & PO PDFs
            Route::get('/quotes/{id}/pdf', [$quo,'pdf'])->name('quotes.pdf');
            // Approval workflow
            Route::post('/quotes/{id}/submit', [$quo,'submit'])->name('quotes.submit');
            Route::post('/quotes/{id}/approve', [$quo,'approve'])->name('quotes.approve');
            Route::post('/quotes/{id}/lock', [$quo,'lock'])->name('quotes.lock');
            Route::post('/quotes/{id}/unlock', [$quo,'unlock'])->name('quotes.unlock');

            // Purchase Orders CRUD
            Route::get('/po', [$po,'index'])->name('po.index');
            Route::get('/po/create', [$po,'create'])->name('po.create');
            Route::post('/po', [$po,'store'])->name('po.store');
            Route::delete('/po/{id}', [$po,'destroy'])->name('po.destroy');
            Route::get('/po/{id}/edit', [$po,'edit'])->name('po.edit');
            Route::put('/po/{id}', [$po,'update'])->name('po.update');
            Route::get('/po/{id}', [$po,'show'])->name('po.show');
            Route::get('/po/{id}/pdf', [$po,'pdf'])->name('po.pdf');
            // Approval workflow
            Route::post('/po/{id}/submit', [$po,'submit'])->name('po.submit');
            Route::post('/po/{id}/approve', [$po,'approve'])->name('po.approve');
            Route::post('/po/{id}/lock', [$po,'lock'])->name('po.lock');
            Route::post('/po/{id}/unlock', [$po,'unlock'])->name('po.unlock');

            // Document History (approval + audit)
            Route::get('/{type}/{id}/history', [$hist,'show'])->name('history.show');
        });

        // Admin Users management (admin only)
        Route::middleware([EnsureRole::class . ':admin'])->prefix('users')->name('admin.users.')->group(function () {
            Route::get('/', [AdminUsers::class, 'index'])->name('index');
            Route::patch('/{user}/roles', [AdminUsers::class, 'updateRoles'])->name('roles.update');
            // User management CRUD
            Route::post('/', [AdminUsers::class, 'store'])->name('store');
            Route::get('/{user}/edit', [AdminUsers::class, 'edit'] ?? [AdminUsers::class, 'index'])->name('edit');
            Route::put('/{user}', [AdminUsers::class, 'update'])->name('update');
            Route::delete('/{user}', [AdminUsers::class, 'destroy'])->name('destroy');
        });
});
