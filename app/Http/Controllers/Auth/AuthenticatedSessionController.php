<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // After login, redirect user to the highest-priority page they have access to
        $user = $request->user();

        // Priority: admin -> accountant -> viewer
        if ($user && method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin')) {
                return redirect()->intended(route('admin.home', absolute: false));
            }
            if ($user->hasRole('accountant')) {
                return redirect()->intended(route('admin.accounting.income.index', absolute: false));
            }
            if ($user->hasRole('viewer')) {
                return redirect()->intended(route('admin.accounting.reports.overview', absolute: false));
            }
        }

        // Fallback
        return redirect()->intended(route('admin.home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
