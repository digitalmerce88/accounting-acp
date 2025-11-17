<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\{Invoice, Quote, PurchaseOrder, Bill, AuditLog};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force generated URLs to https in production to avoid mixed-content issues
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // Simple audit logging for major models (create/update/delete)
        $logFn = function ($model, string $action) {
            try {
                AuditLog::create([
                    'business_id' => $model->getAttribute('business_id'),
                    'user_id' => Auth::id(),
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'action' => $action,
                    'old_values' => $action === 'updated' ? ($model->getOriginal() ?? null) : null,
                    'new_values' => $model->getAttributes(),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Throwable $e) {
                // swallow errors to not block main flow
            }
        };

        foreach ([Invoice::class, Quote::class, PurchaseOrder::class, Bill::class] as $cls) {
            $cls::created(function ($m) use ($logFn) { $logFn($m, 'created'); });
            $cls::updated(function ($m) use ($logFn) { $logFn($m, 'updated'); });
            $cls::deleted(function ($m) use ($logFn) { $logFn($m, 'deleted'); });
        }
    }
}
