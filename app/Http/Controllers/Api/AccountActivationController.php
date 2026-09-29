<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateAccountRequest;
use App\Models\AccountActivationToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountActivationController extends Controller
{
    public function store(ActivateAccountRequest $request): JsonResponse
    {
        $tokenHash = hash('sha256', $request->string('token')->toString());

        $activatedUser = DB::transaction(function () use ($request, $tokenHash): ?User {
            $activationToken = AccountActivationToken::query()
                ->with('user')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $activationToken) {
                return null;
            }

            if ($activationToken->isExpired()) {
                $activationToken->delete();

                return null;
            }

            $user = $activationToken->user;

            if ($user->account_status !== AccountStatus::PendingActivation) {
                $activationToken->delete();

                return null;
            }

            $user->forceFill([
                'password' => $request->string('password')->toString(),
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ])->save();

            $activationToken->delete();

            return $user;
        });

        if (!$activatedUser) {
            throw ValidationException::withMessages([
                'token' => ['O convite é inválido, expirou ou já foi utilizado.'],
            ]);
        }

        return response()->json([
            'message' => 'Conta ativada com sucesso. Você já pode entrar no sistema.',
        ]);
    }
}
