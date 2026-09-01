<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_read_profile(): void
    {
        $permission = Permission::query()->create([
            'key' => 'view_dashboard',
            'name' => 'View dashboard',
        ]);

        $role = Role::query()->create([
            'key' => 'admin',
            'name' => 'Administrador',
        ]);

        $role->permissions()->attach($permission);

        $tenant = Tenant::query()->create([
            'name' => 'Example School',
            'slug' => 'example-school',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'employee',
            'status' => 'active',
            'current_tenant_id' => $tenant->id,
        ]);

        $user->tenants()->attach($tenant, ['is_owner' => true]);

        Employee::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'position' => 'Administrator',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.current_tenant.slug', 'example-school')
            ->assertJsonPath('data.user.tenants.0.slug', 'example-school')
            ->assertJsonPath('data.user.role.key', 'admin')
            ->assertJsonPath('data.user.role.permissions.0', 'view_dashboard')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'user'],
            ]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'employee',
            'status' => 'active',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }
}
