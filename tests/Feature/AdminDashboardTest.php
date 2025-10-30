<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    protected function makeUserWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $roleId = Role::where('slug', $slug)->value('id');
        $user->roles()->sync([$roleId]);
        return $user;
    }

    public function test_admin_can_view_dashboard_json(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $res = $this->actingAs($admin)->getJson('/admin');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'metrics' => ['accounts_count','journals_count','tb_total_dr','tb_total_cr'],
                'recent_journals'
            ]);
    }
}
