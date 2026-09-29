<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\AccountActivationToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountActivationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_account_can_be_activated_and_use_its_new_password(): void
    {
        $plainToken = Str::random(64);
        $employee = User::factory()->pendingActivation()->create([
            'email' => 'invited@example.test',
        ]);

        AccountActivationToken::factory()->for($employee)->create([
            'token_hash' => hash('sha256', $plainToken),
        ]);

        $payload = [
            'token' => $plainToken,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ];

        $this->postJson('/api/auth/activate', $payload)
            ->assertOk();

        $employee->refresh();

        $this->assertSame(AccountStatus::Active, $employee->account_status);
        $this->assertNotNull($employee->email_verified_at);
        $this->assertTrue(Hash::check('new-password1', $employee->password));
        $this->assertDatabaseMissing('account_activation_tokens', [
            'user_id' => $employee->id,
        ]);

        $this->withHeader('Origin', 'http://localhost:4200')
            ->postJson('/api/auth/login', [
                'email' => $employee->email,
                'password' => 'new-password1',
            ])->assertOk();

        $this->postJson('/api/auth/activate', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_invalid_activation_token_is_rejected(): void
    {
        $this->postJson('/api/auth/activate', [
            'token' => Str::random(64),
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_expired_activation_token_is_rejected_and_removed(): void
    {
        $plainToken = Str::random(64);
        $employee = User::factory()->pendingActivation()->create();

        AccountActivationToken::factory()->for($employee)->create([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/auth/activate', [
            'token' => $plainToken,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertDatabaseMissing('account_activation_tokens', [
            'user_id' => $employee->id,
        ]);
        $this->assertSame(AccountStatus::PendingActivation, $employee->fresh()->account_status);
    }

    public function test_activation_requires_a_confirmed_strong_enough_password(): void
    {
        $this->postJson('/api/auth/activate', [
            'token' => Str::random(64),
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
