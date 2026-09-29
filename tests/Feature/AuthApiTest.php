<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const SPA_ORIGIN = 'http://localhost:4200';

    public function test_active_user_can_login_and_read_the_authenticated_profile(): void
    {
        $permission = Permission::factory()->create([
            'key' => 'students.view',
        ]);
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => 'password',
        ]);
        $user->permissions()->attach($permission);

        $login = $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => ' EMPLOYEE@EXAMPLE.TEST ',
                'password' => 'password',
            ]);

        $login->assertOk()
            ->assertJsonPath('data.email', 'employee@example.test')
            ->assertJsonPath('data.permissions.0', 'students.view')
            ->assertCookie(config('session.cookie'));

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'employee@example.test');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'employee@example.test',
            'password' => 'password',
        ]);

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'employee@example.test',
                'password' => 'invalid-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_is_temporarily_blocked_after_repeated_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'blocked-by-attempts@example.test',
            'password' => 'password',
        ]);

        foreach (range(1, 4) as $attempt) {
            $this->withHeader('Origin', self::SPA_ORIGIN)
                ->postJson('/api/auth/login', [
                    'email' => 'blocked-by-attempts@example.test',
                    'password' => 'invalid-password',
                ])
                ->assertUnprocessable();
        }

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'blocked-by-attempts@example.test',
                'password' => 'invalid-password',
            ])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('code', 'too_many_login_attempts');

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'blocked-by-attempts@example.test',
                'password' => 'password',
            ])
            ->assertTooManyRequests();
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'attempts-cleared@example.test',
            'password' => 'password',
        ]);

        foreach (range(1, 4) as $attempt) {
            $this->withHeader('Origin', self::SPA_ORIGIN)
                ->postJson('/api/auth/login', [
                    'email' => 'attempts-cleared@example.test',
                    'password' => 'invalid-password',
                ])
                ->assertUnprocessable();
        }

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'attempts-cleared@example.test',
                'password' => 'password',
            ])
            ->assertOk();

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'attempts-cleared@example.test',
                'password' => 'invalid-password',
            ])
            ->assertUnprocessable();
    }

    public function test_pending_account_cannot_login(): void
    {
        User::factory()->pendingActivation()->create([
            'email' => 'pending@example.test',
        ]);

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'pending@example.test',
                'password' => 'any-password',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'account_pending_activation');
    }

    public function test_disabled_account_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'disabled@example.test',
            'password' => 'password',
            'account_status' => AccountStatus::Disabled,
        ]);

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => 'disabled@example.test',
                'password' => 'password',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'account_disabled');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => 'password',
        ]);

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertOk();

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_read_the_authenticated_profile(): void
    {
        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_disabled_user_cannot_read_the_authenticated_profile(): void
    {
        $user = User::factory()->disabled()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'account_disabled');
    }
}
