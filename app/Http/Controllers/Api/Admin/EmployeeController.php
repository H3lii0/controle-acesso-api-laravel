<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $employee = DB::transaction(function () use ($validated): User {
            $employee = User::query()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => null,
                'account_type' => AccountType::Employee,
                'account_status' => AccountStatus::PendingActivation,
                'email_verified_at' => null,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('key', $validated['permissions'])
                ->pluck('id');

            $employee->permissions()->sync($permissionIds);

            return $employee;
        });

        $this->sendActivationInvitation($employee);

        return response()->json([
            'message' => 'Funcionário cadastrado. O convite de ativação foi enviado.',
            'data' => new EmployeeResource($employee->load('permissions')),
        ], 201);
    }

    public function resendInvitation(User $employee): JsonResponse
    {
        if ($employee->account_type !== AccountType::Employee) {
            return response()->json([
                'code' => 'employee_not_found',
                'message' => 'Funcionário não encontrado.',
            ], 404);
        }

        if ($employee->account_status !== AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'employee_not_pending_activation',
                'message' => 'Somente contas pendentes podem receber um novo convite.',
            ], 409);
        }

        $this->sendActivationInvitation($employee);

        return response()->json([
            'message' => 'Um novo convite de ativação foi enviado.',
        ]);
    }

    private function sendActivationInvitation(User $employee): void
    {
        $plainToken = Str::random(64);
        $expiresAt = now()->addHours(
            max(1, (int) config('auth.activation.expiration_hours', 24)),
        );

        $employee->activationToken()->updateOrCreate(
            [],
            [
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ],
        );

        $employee->notify(new AccountActivationNotification($plainToken, $expiresAt));
    }
}
