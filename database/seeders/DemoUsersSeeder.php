<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $roles = Role::query()->pluck('id', 'slug');

        // Create or update demo users
        $users = [
            [
                'name' => 'Demo Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'roles' => ['admin'],
            ],
            [
                'name' => 'Demo Accountant',
                'email' => 'accountant@example.com',
                'password' => 'password',
                'roles' => ['accountant'],
            ],
            [
                'name' => 'Demo Viewer',
                'email' => 'viewer@example.com',
                'password' => 'password',
                'roles' => ['viewer'],
            ],
        ];

        foreach ($users as $u) {
            $user = User::query()->updateOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => $u['password']]
            );

            $roleIds = collect($u['roles'])
                ->map(fn ($slug) => $roles[$slug] ?? null)
                ->filter()
                ->values()
                ->all();

            if (!empty($roleIds)) {
                $user->roles()->sync($roleIds);
            }
        }
    }
}
