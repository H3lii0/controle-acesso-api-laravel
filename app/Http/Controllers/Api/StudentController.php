<?php

namespace App\Http\Controllers\Api;

use App\Actions\IssueAccountActivation;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\IndexStudentRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Requests\Student\UpdateStudentStatusRequest;
use App\Http\Resources\StudentResource;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function __construct(private readonly IssueAccountActivation $issueAccountActivation) {}

    public function index(IndexStudentRequest $request): AnonymousResourceCollection
    {
        $query = Student::query()->with(['schoolClass', 'guardian', 'biometricCredential']);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereLike('full_name', $search, caseSensitive: false)
                    ->orWhereLike('enrollment_number', $search, caseSensitive: false)
                    ->orWhereHas('guardian', function (Builder $guardianQuery) use ($search): void {
                        $guardianQuery->whereLike('full_name', $search, caseSensitive: false);
                    });
            });
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->integer('school_class_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return StudentResource::collection(
            $query
                ->orderBy('full_name')
                ->orderBy('id')
                ->paginate($request->integer('per_page', 15))
                ->withQueryString(),
        );
    }

    public function show(Student $student): StudentResource
    {
        return new StudentResource($student->load(['schoolClass', 'guardian', 'biometricCredential']));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->ensureActiveSchoolClass((int) $validated['student']['school_class_id']);

        [$student, $newGuardian] = DB::transaction(function () use ($validated): array {
            [$guardian, $newGuardian] = $this->resolveGuardian($validated['guardian']);

            $student = Student::query()->create([
                ...collect($validated['student'])->except('biometric_captured')->all(),
                'guardian_user_id' => $guardian->id,
                'is_active' => true,
            ]);

            if ((bool) ($validated['student']['biometric_captured'] ?? false)) {
                $student->biometricCredential()->create([
                    'identifier' => (string) Str::uuid(),
                    'captured_at' => now(),
                ]);
            }

            return [$student, $newGuardian];
        });

        if ($newGuardian instanceof User) {
            $this->issueAccountActivation->handle($newGuardian);
        }

        return response()->json([
            'message' => $newGuardian instanceof User
                ? 'Aluno e responsável cadastrados. O convite de ativação foi enviado.'
                : 'Aluno cadastrado e vinculado ao responsável existente.',
            'data' => new StudentResource($student->load(['schoolClass', 'guardian', 'biometricCredential'])),
        ], 201);
    }

    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $validated = $request->validated();
        $this->ensureActiveSchoolClass(
            (int) $validated['student']['school_class_id'],
            $student,
        );

        $newGuardian = DB::transaction(function () use ($student, $validated): ?User {
            [$guardian, $newGuardian] = $this->resolveGuardian($validated['guardian']);

            $student->update([
                ...collect($validated['student'])->except('biometric_captured')->all(),
                'guardian_user_id' => $guardian->id,
            ]);

            if ((bool) ($validated['student']['biometric_captured'] ?? false)
                && ! $student->biometricCredential()->exists()) {
                $student->biometricCredential()->create([
                    'identifier' => (string) Str::uuid(),
                    'captured_at' => now(),
                ]);
            }

            return $newGuardian;
        });

        if ($newGuardian instanceof User) {
            $this->issueAccountActivation->handle($newGuardian);
        }

        return (new StudentResource($student->load(['schoolClass', 'guardian', 'biometricCredential'])))
            ->additional([
                'message' => $newGuardian instanceof User
                    ? 'Aluno atualizado. O novo responsável recebeu o convite de ativação.'
                    : 'Aluno atualizado com sucesso.',
            ]);
    }

    public function updateStatus(UpdateStudentStatusRequest $request, Student $student): StudentResource
    {
        $student->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return (new StudentResource($student->load(['schoolClass', 'guardian', 'biometricCredential'])))
            ->additional([
                'message' => $student->is_active
                    ? 'Aluno ativado com sucesso.'
                    : 'Aluno desativado com sucesso.',
            ]);
    }

    private function ensureActiveSchoolClass(int $schoolClassId, ?Student $student = null): void
    {
        $canKeepCurrentInactiveClass = $student?->school_class_id === $schoolClassId;
        $schoolClassExists = SchoolClass::query()
            ->whereKey($schoolClassId)
            ->when(
                ! $canKeepCurrentInactiveClass,
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->exists();

        if (! $schoolClassExists) {
            throw ValidationException::withMessages([
                'student.school_class_id' => ['Selecione uma turma ativa.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $guardianData
     * @return array{0: User, 1: User|null}
     */
    private function resolveGuardian(array $guardianData): array
    {
        if ($guardianData['mode'] === 'new') {
            $guardian = User::query()->create([
                'full_name' => $guardianData['full_name'],
                'email' => $guardianData['email'],
                'phone' => $guardianData['phone'] ?? null,
                'password' => null,
                'account_type' => AccountType::Guardian,
                'account_status' => AccountStatus::PendingActivation,
                'email_verified_at' => null,
            ]);

            return [$guardian, $guardian];
        }

        $guardian = User::query()
            ->lockForUpdate()
            ->findOrFail((int) $guardianData['id']);

        if ($guardian->account_type !== AccountType::Guardian) {
            throw ValidationException::withMessages([
                'guardian.id' => ['A conta selecionada não pertence a um responsável.'],
            ]);
        }

        if ($guardian->account_status === AccountStatus::Disabled) {
            throw ValidationException::withMessages([
                'guardian.id' => ['O responsável selecionado está com a conta desativada.'],
            ]);
        }

        return [$guardian, null];
    }
}
