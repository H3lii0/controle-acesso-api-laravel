<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterStudentAccess;
use App\Enums\AccessReadingResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\IndexAccessRecordRequest;
use App\Http\Requests\Access\ReadStudentAccessRequest;
use App\Http\Resources\StudentAccessRecordResource;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\StudentBiometricCredential;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccessRecordController extends Controller
{
    public function __construct(private readonly RegisterStudentAccess $registerStudentAccess) {}

    public function read(ReadStudentAccessRequest $request): JsonResponse
    {
        $student = $request->filled('credential_identifier')
            ? StudentBiometricCredential::query()
                ->where('identifier', $request->string('credential_identifier')->toString())
                ->with('student')
                ->first()?->student
            : Student::query()
                ->where('enrollment_number', $request->string('enrollment_number')->toString())
                ->first();

        if (! $student instanceof Student) {
            return response()->json([
                'code' => 'student_not_found',
                'message' => 'Aluno não encontrado para a matrícula informada.',
            ], 404);
        }

        if (! $student->is_active) {
            return response()->json([
                'code' => 'student_inactive',
                'message' => 'O aluno está inativo e não pode registrar acesso.',
            ], 409);
        }

        $reading = $this->registerStudentAccess->handle($student);
        $record = $reading['record']->load('student.schoolClass');
        $result = $reading['result'];

        return response()->json([
            'code' => $result->value,
            'message' => $this->messageFor($result),
            'retry_after_seconds' => $reading['retry_after_seconds'],
            'data' => new StudentAccessRecordResource($record),
        ], $this->statusFor($result));
    }

    public function index(IndexAccessRecordRequest $request): AnonymousResourceCollection
    {
        return StudentAccessRecordResource::collection(
            $this->filteredQuery($request)
                ->with('student.schoolClass')
                ->orderByDesc('entered_at')
                ->orderByDesc('id')
                ->paginate($request->integer('per_page', 15))
                ->withQueryString(),
        );
    }

    public function summary(IndexAccessRecordRequest $request): JsonResponse
    {
        $query = $this->filteredQuery($request);

        return response()->json([
            'data' => [
                'date' => $this->requestedDate($request),
                'entries' => (clone $query)->count(),
                'exits' => (clone $query)->whereNotNull('exited_at')->count(),
                'inside' => (clone $query)->whereNull('exited_at')->count(),
            ],
        ]);
    }

    /** @return Builder<StudentAccessRecord> */
    private function filteredQuery(IndexAccessRecordRequest $request): Builder
    {
        $query = StudentAccessRecord::query()
            ->whereDate('access_date', $this->requestedDate($request));

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';

            $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                $studentQuery
                    ->whereLike('full_name', $search, caseSensitive: false)
                    ->orWhereLike('enrollment_number', $search, caseSensitive: false);
            });
        }

        if ($request->filled('school_class_id')) {
            $query->whereHas('student', function (Builder $studentQuery) use ($request): void {
                $studentQuery->where('school_class_id', $request->integer('school_class_id'));
            });
        }

        if ($request->string('status')->toString() === 'inside') {
            $query->whereNull('exited_at');
        }

        if ($request->string('status')->toString() === 'completed') {
            $query->whereNotNull('exited_at');
        }

        return $query;
    }

    private function requestedDate(IndexAccessRecordRequest $request): string
    {
        return $request->filled('date')
            ? $request->string('date')->toString()
            : now(config('school.timezone'))->toDateString();
    }

    private function messageFor(AccessReadingResult $result): string
    {
        return match ($result) {
            AccessReadingResult::EntryRegistered => 'Entrada registrada com sucesso.',
            AccessReadingResult::ExitRegistered => 'Saída registrada com sucesso.',
            AccessReadingResult::ExitTooSoon => 'A saída ainda não pode ser registrada. Aguarde o intervalo mínimo.',
            AccessReadingResult::DailyAccessCompleted => 'A entrada e a saída deste aluno já foram registradas hoje.',
        };
    }

    private function statusFor(AccessReadingResult $result): int
    {
        return match ($result) {
            AccessReadingResult::EntryRegistered,
            AccessReadingResult::ExitRegistered => 200,
            AccessReadingResult::ExitTooSoon,
            AccessReadingResult::DailyAccessCompleted => 409,
        };
    }
}
