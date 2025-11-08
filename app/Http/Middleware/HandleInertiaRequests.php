<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'status' => session('status'),
                'success' => session('success'),
                'error' => session('error'),
            ],
        ];

        // Expose app name and company logo (if configured) for client UI
        try {
            $appName = config('app.name');
            $bizId = (int) ($request->user()->business_id ?? 1);
            $company = \App\Models\CompanyProfile::where('business_id', $bizId)->first();
            $logoUrl = null;
            if ($company && $company->logo_path) {
                $logoUrl = url('/storage/' . ltrim($company->logo_path, '/'));
            }
            $shared['app'] = [
                'name' => $appName,
                'logo' => $logoUrl,
            ];
        } catch (\Throwable $e) {
            // noop - don't break Inertia if company fetch fails
        }

        return $shared;
    }
}
