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
            ->with(['currentTenant', 'employee.role.permissions', 'tenants'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This user is inactive.'],
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
        $user = $request->user()->load(['currentTenant', 'employee.role.permissions', 'tenants']);

        return response()->json([
            'data' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function userPayload(User $user): array
    {
        $role = $user->employee?->role;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->type,
            'status' => $user->status,
            'current_tenant' => $user->currentTenant ? [
                'id' => $user->currentTenant->id,
                'name' => $user->currentTenant->name,
                'slug' => $user->currentTenant->slug,
                'status' => $user->currentTenant->status,
            ] : null,
            'tenants' => $user->tenants->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'is_owner' => (bool) $tenant->pivot->is_owner,
            ])->values(),
            'role' => $role ? [
                'key' => $role->key,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('key')->values(),
            ] : null,
        ];
    }
}
