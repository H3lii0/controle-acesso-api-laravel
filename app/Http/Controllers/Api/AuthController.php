<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with(['escolaAtual', 'funcionario.perfil.permissoes', 'escolas'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Este usuário está inativo.'],
            ]);
        }

        $token = $user->createToken('web-angular')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['escolaAtual', 'funcionario.perfil.permissoes', 'escolas']);

        return response()->json([
            'data' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    private function userPayload(User $user): array
    {
        $perfil = $user->funcionario?->perfil;

        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'tipo' => $user->type,
            'status' => $user->status,
            'escola_atual' => $user->escolaAtual ? [
                'id' => $user->escolaAtual->id,
                'nome' => $user->escolaAtual->nome,
                'slug' => $user->escolaAtual->slug,
                'status' => $user->escolaAtual->status,
            ] : null,
            'escolas' => $user->escolas->map(fn ($escola) => [
                'id' => $escola->id,
                'nome' => $escola->nome,
                'slug' => $escola->slug,
                'proprietario' => (bool) $escola->pivot->proprietario,
            ])->values(),
            'perfil' => $perfil ? [
                'chave' => $perfil->chave,
                'nome' => $perfil->nome,
                'permissoes' => $perfil->permissoes->pluck('chave')->values(),
            ] : null,
        ];
    }
}
