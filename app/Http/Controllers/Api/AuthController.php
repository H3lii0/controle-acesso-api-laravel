<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $loginKey = $this->loginKey($request);

        if (RateLimiter::tooManyAttempts($loginKey, $this->maximumLoginAttempts())) {
            return $this->loginBlockedResponse($loginKey);
        }

        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        if (!$user) {
            return $this->invalidCredentialsResponse($loginKey);
        }

        if ($user->account_status === AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'account_pending_activation',
                'message' => 'Esta conta ainda precisa ser ativada.',
            ], 403);
        }

        if ($user->account_status === AccountStatus::Disabled) {
            return response()->json([
                'code' => 'account_disabled',
                'message' => 'Esta conta está desativada.',
            ], 403);
        }

        if (!is_string($user->password) || !Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->invalidCredentialsResponse($loginKey);
        }

        RateLimiter::clear($loginKey);
        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Sessão iniciada com sucesso.',
            'data' => new AuthenticatedUserResource($user->load('permissions')),
        ]);
    }

    public function me(Request $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource(
            $request->user()->load('permissions'),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::forgetGuards();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    private function invalidCredentialsResponse(string $loginKey): JsonResponse
    {
        RateLimiter::hit($loginKey, $this->loginLockoutSeconds());

        if (RateLimiter::tooManyAttempts($loginKey, $this->maximumLoginAttempts())) {
            return $this->loginBlockedResponse($loginKey);
        }

        throw ValidationException::withMessages([
            'email' => ['As credenciais informadas são inválidas.'],
        ]);
    }

    private function loginBlockedResponse(string $loginKey): JsonResponse
    {
        $retryAfter = RateLimiter::availableIn($loginKey);

        return response()
            ->json([
                'code' => 'too_many_login_attempts',
                'message' => 'Muitas tentativas incorretas. Aguarde antes de tentar novamente.',
                'retry_after' => $retryAfter,
            ], 429)
            ->header('Retry-After', (string) $retryAfter);
    }

    private function loginKey(LoginRequest $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip(),
        );
    }

    private function maximumLoginAttempts(): int
    {
        return (int) config('auth.login_lockout.maximum_attempts', 5);
    }

    private function loginLockoutSeconds(): int
    {
        return (int) config('auth.login_lockout.seconds', 300);
    }
}
