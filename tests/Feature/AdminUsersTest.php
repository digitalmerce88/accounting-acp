<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
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

    public function test_admin_can_view_users_index(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $user = User::factory()->create();

    $res = $this->actingAs($admin)->getJson('/admin/users');
    $res->assertStatus(200)->assertJsonStructure(['users', 'roles']);
    }

    public function test_admin_can_update_user_roles(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $target = User::factory()->create();

        $res = $this->actingAs($admin)->patchJson("/admin/users/{$target->id}/roles", [
            'roles' => ['accountant']
        ]);
        $res->assertStatus(200);

        $this->assertTrue($target->fresh()->hasRole('accountant'));
    }

    public function test_non_admin_cannot_access_users_index(): void
    {
        $accountant = $this->makeUserWithRole('accountant');
        $res = $this->actingAs($accountant)->get('/admin/users');
        $res->assertStatus(403);
    }
}
