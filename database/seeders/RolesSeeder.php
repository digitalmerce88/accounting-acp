<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Accountant', 'slug' => 'accountant'],
            ['name' => 'Viewer', 'slug' => 'viewer'],
        ];

        foreach ($roles as $data) {
            Role::query()->updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
