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

    /**
     * Create a new user (admin UI / API)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if (! empty($data['roles'])) {
            $roleIds = Role::whereIn('slug', $data['roles'])->pluck('id')->all();
            $user->roles()->sync($roleIds);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'user' => $user]);
        }

        return back()->with('success', 'User created');
    }

    /**
     * Update user attributes (name/email/password)
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email,'.$user->id],
            'password' => ['nullable','string','min:6'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) { $user->password = $data['password']; }
        $user->save();

        if (isset($data['roles'])) {
            $roleIds = Role::whereIn('slug', $data['roles'])->pluck('id')->all();
            $user->roles()->sync($roleIds);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'user' => $user]);
        }

        return back()->with('success', 'User updated');
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent self-delete
        if ($request->user() && $request->user()->id === $user->id) {
            if ($request->wantsJson()) { return response()->json(['ok'=>false,'message'=>'Cannot delete yourself'], 422); }
            return back()->with('error', 'ไม่สามารถลบผู้ใช้ของตัวเองได้');
        }

        $user->roles()->detach();
        $user->delete();

        if ($request->wantsJson()) { return response()->json(['ok' => true]); }
        return back()->with('success', 'User deleted');
    }

    /**
     * Debug endpoint to return users and roles as JSON (bypasses Inertia)
     * Protected by the same admin middleware in routes.
     */
    public function debugJson(Request $request)
    {
        $users = User::query()
            ->with('roles:id,slug,name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        $roles = Role::query()->orderBy('id')->get(['id', 'slug', 'name']);

        return response()->json([
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
