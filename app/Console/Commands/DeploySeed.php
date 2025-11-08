<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class DeploySeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy-seed {--email= : Admin email} {--password= : Admin password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed essential deployment data (roles + admin user)';

    public function handle(): int
    {
        $this->info('Seeding roles...');
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Accountant', 'slug' => 'accountant'],
            ['name' => 'Viewer', 'slug' => 'viewer'],
        ];
        foreach ($roles as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        $this->info('Creating admin user...');
        $email = $this->option('email') ?? env('DEPLOY_ADMIN_EMAIL', 'admin@example.com');
        $password = $this->option('password') ?? env('DEPLOY_ADMIN_PASSWORD', 'changeme');

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Administrator', 'password' => Hash::make($password)]
        );

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Seed Chart of Accounts (idempotent)
        $this->info('Seeding Chart of Accounts...');
        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoASeeder', '--force' => true]);
            $this->info('Chart of Accounts seeded.');
        } catch (\Throwable $e) {
            $this->error('Failed to seed Chart of Accounts: ' . $e->getMessage());
        }

        $this->info("Admin user created/updated: {$user->email}");
        $this->info('Done.');
        return 0;
    }
}
