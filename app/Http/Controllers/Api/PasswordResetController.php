<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(
            [
                'email' => $request->string('email')->toString(),
                'account_status' => AccountStatus::Active->value,
            ],
            function (User $user, string $token): void {
                $user->notify(new ResetPasswordNotification($token));
            },
        );

        return response()->json([
            'message' => 'Se existir uma conta ativa com esse e-mail, enviaremos as instruções de recuperação.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            [
                'email' => $request->string('email')->toString(),
                'token' => $request->string('token')->toString(),
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password_confirmation')->toString(),
                'account_status' => AccountStatus::Active->value,
            ],
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $user->forceFill([
                        'password' => $password,
                        'remember_token' => Str::random(60),
                    ])->save();

                    DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->delete();
                });

                event(new PasswordResetEvent($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['Não foi possível redefinir a senha. O link é inválido ou expirou.'],
            ]);
        }

        return response()->json([
            'message' => 'Senha redefinida com sucesso. Entre novamente com a nova senha.',
        ]);
    }
}
