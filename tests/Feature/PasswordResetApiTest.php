<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_MESSAGE = 'Se existir uma conta ativa com esse e-mail, enviaremos as instruções de recuperação.';

    private const SPA_ORIGIN = 'http://localhost:4200';

    public function test_active_account_can_request_password_reset_with_hashed_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'active@example.test',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => ' ACTIVE@EXAMPLE.TEST ',
        ])
            ->assertOk()
            ->assertJsonPath('message', self::GENERIC_MESSAGE);

        $plainToken = null;
        $sentNotification = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$plainToken, &$sentNotification): bool {
                $plainToken = $notification->token;
                $sentNotification = $notification;

                return true;
            },
        );

        $storedToken = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->value('token');

        $this->assertIsString($plainToken);
        $this->assertIsString($storedToken);
        $this->assertNotSame($plainToken, $storedToken);
        $this->assertTrue(Hash::check($plainToken, $storedToken));
        $this->assertInstanceOf(ResetPasswordNotification::class, $sentNotification);
        $this->assertSame(
            'http://localhost:4200/redefinir-senha?token='.$plainToken.'&email=active%40example.test',
            $sentNotification->toMail($user)->actionUrl,
        );
    }

    public function test_unknown_pending_and_disabled_accounts_receive_same_generic_response_without_email(): void
    {
        Notification::fake();

        $pending = User::factory()->pendingActivation()->create();
        $disabled = User::factory()->disabled()->create();

        foreach (['unknown@example.test', $pending->email, $disabled->email] as $email) {
            $this->postJson('/api/auth/forgot-password', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('message', self::GENERIC_MESSAGE);
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_repeated_request_inside_throttle_window_does_not_send_another_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', self::GENERIC_MESSAGE);

        Notification::assertCount(1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_valid_token_resets_password_invalidates_token_and_closes_sessions(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => 'old-password1',
            'remember_token' => 'old-remember-token',
        ]);

        DB::table('sessions')->insert([
            'id' => 'old-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $plainToken = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$plainToken): bool {
                $plainToken = $notification->token;

                return true;
            },
        );

        $payload = [
            'email' => $user->email,
            'token' => $plainToken,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ];

        $this->postJson('/api/auth/reset-password', $payload)
            ->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password1', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);

        $this->postJson('/api/auth/reset-password', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'old-password1',
            ])
            ->assertUnprocessable();

        $this->withHeader('Origin', self::SPA_ORIGIN)
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'new-password1',
            ])
            ->assertOk();
    }

    public function test_expired_token_is_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $plainToken = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$plainToken): bool {
                $plainToken = $notification->token;

                return true;
            },
        );

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $plainToken,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_disabled_account_cannot_use_existing_reset_token(): void
    {
        $plainToken = Str::random(64);
        $user = User::factory()->disabled()->create([
            'password' => 'old-password1',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $plainToken,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('old-password1', $user->fresh()->password));
    }

    public function test_reset_requires_confirmed_strong_enough_password(): void
    {
        $this->postJson('/api/auth/reset-password', [
            'email' => 'user@example.test',
            'token' => Str::random(64),
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
