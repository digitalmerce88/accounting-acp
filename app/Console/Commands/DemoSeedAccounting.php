<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DemoSeedAccounting extends Command
{
    protected $signature = 'demo:seed-accounting';
    protected $description = 'Seed demo accounting data (Chart of Accounts + sample journals)';

    public function handle(): int
    {
        $this->info('Seeding Roles, Chart of Accounts and Demo data...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolesSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoUsersSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoASeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]);
        $this->info('Demo accounting data seeded.');
        return 0;
    }
}
