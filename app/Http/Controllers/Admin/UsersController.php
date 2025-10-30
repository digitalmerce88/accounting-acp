<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles:id,slug,name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        $roles = Role::query()->orderBy('id')->get(['id', 'slug', 'name']);

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'roles' => $roles,
            ]);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string'], // role slugs
        ]);

        $roleIds = Role::query()
            ->whereIn('slug', $data['roles'] ?? [])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Roles updated');
    }
}
