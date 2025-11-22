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
            // Provide richer auth info (user + roles + is_admin) so client can
            // reliably make role-based UI decisions. This keeps middleware as
            // the authoritative final share for 'auth'.
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
            // expose application/base currency code if configured
            try {
                $baseCur = \App\Models\Currency::where('is_base', true)->value('code');
                if ($baseCur) $shared['app']['base_currency'] = $baseCur;
            } catch (\Throwable $e) {
                // noop
            }
        } catch (\Throwable $e) {
            // noop - don't break Inertia if company fetch fails
        }

        // Compute roles and is_admin and attach into the auth share so the final
        // Inertia payload includes these values (the parent middleware/share may
        // overwrite earlier shares, so ensure they are present here).
        try {
            $roles = [];
            $isAdmin = false;
            if ($request->user()) {
                $roles = \Illuminate\Support\Facades\DB::table('user_role')
                    ->join('roles','roles.id','=','user_role.role_id')
                    ->where('user_role.user_id', $request->user()->id)
                    ->pluck('roles.slug')
                    ->all();
                $isAdmin = in_array('admin', $roles, true);
            }
            // Merge into shared auth (overwriting previous keys if present)
            $shared['auth'] = array_merge($shared['auth'] ?? [], [
                'roles' => $roles,
                'is_admin' => $isAdmin,
            ]);
        } catch (\Throwable $e) {
            // noop - don't break Inertia if DB read fails
        }

        return $shared;
    }
}
