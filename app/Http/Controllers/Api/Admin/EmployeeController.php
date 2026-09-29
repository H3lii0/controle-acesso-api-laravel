<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexEmployeeRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeStatusRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(IndexEmployeeRequest $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->where('account_type', AccountType::Employee)
            ->with('permissions');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereLike('full_name', $search, caseSensitive: false)
                    ->orWhereLike('email', $search, caseSensitive: false);
            });
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->string('status')->toString());
        }

        return EmployeeResource::collection(
            $query
                ->orderBy('full_name')
                ->orderBy('id')
                ->paginate($request->integer('per_page', 15))
                ->withQueryString(),
        );
    }

    public function show(User $employee): EmployeeResource|JsonResponse
    {
        if (!$this->isEmployee($employee)) {
            return $this->employeeNotFoundResponse();
        }

        return new EmployeeResource($employee->load('permissions'));
    }

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

            $this->syncPermissions($employee, $validated['permissions']);

            return $employee;
        });

        $this->sendActivationInvitation($employee);

        return response()->json([
            'message' => 'Funcionário cadastrado. O convite de ativação foi enviado.',
            'data' => new EmployeeResource($employee->load('permissions')),
        ], 201);
    }

    public function update(UpdateEmployeeRequest $request, User $employee): EmployeeResource|JsonResponse
    {
        if (! $this->isEmployee($employee)) {
            return $this->employeeNotFoundResponse();
        }

        $validated = $request->validated();
        $emailChanged = $employee->email !== $validated['email'];

        if ($emailChanged && $employee->account_status !== AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'employee_email_change_requires_verification',
                'message' => 'O e-mail de uma conta já ativada exige um fluxo próprio de verificação.',
            ], 409);
        }

        DB::transaction(function () use ($employee, $validated): void {
            $employee->update([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $this->syncPermissions($employee, $validated['permissions']);
        });

        if ($emailChanged) {
            $this->sendActivationInvitation($employee);
        }

        return (new EmployeeResource($employee->load('permissions')))
            ->additional([
                'message' => $emailChanged
                    ? 'Funcionário atualizado. Um novo convite foi enviado ao novo e-mail.'
                    : 'Funcionário atualizado com sucesso.',
            ]);
    }

    public function updateStatus(UpdateEmployeeStatusRequest $request, User $employee): EmployeeResource|JsonResponse
    {
        if (! $this->isEmployee($employee)) {
            return $this->employeeNotFoundResponse();
        }

        if ($employee->account_status === AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'employee_activation_required',
                'message' => 'A conta pendente deve ser ativada pelo convite antes de ter sua situação alterada.',
            ], 409);
        }

        $newStatus = AccountStatus::from($request->string('status')->toString());

        DB::transaction(function () use ($employee, $newStatus): void {
            $employee->forceFill([
                'account_status' => $newStatus,
                'remember_token' => null,
            ])->save();

            if ($newStatus === AccountStatus::Disabled) {
                DB::table('sessions')
                    ->where('user_id', $employee->id)
                    ->delete();
            }
        });

        return (new EmployeeResource($employee->load('permissions')))
            ->additional([
                'message' => $newStatus === AccountStatus::Active
                    ? 'Conta do funcionário reativada com sucesso.'
                    : 'Conta do funcionário desativada com sucesso.',
            ]);
    }

    public function resendInvitation(User $employee): JsonResponse
    {
        if (! $this->isEmployee($employee)) {
            return $this->employeeNotFoundResponse();
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

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function syncPermissions(User $employee, array $permissionKeys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $permissionKeys)
            ->pluck('id');

        $employee->permissions()->sync($permissionIds);
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

    private function isEmployee(User $user): bool
    {
        return $user->account_type === AccountType::Employee;
    }

    private function employeeNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'code' => 'employee_not_found',
            'message' => 'Funcionário não encontrado.',
        ], 404);
    }
}
