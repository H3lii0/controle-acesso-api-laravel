<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\IndexGuardianAccessRecordRequest;
use App\Http\Resources\StudentAccessRecordResource;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuardianAccessController extends Controller
{
    public function students(Request $request): AnonymousResourceCollection
    {
        /** @var User $guardian */
        $guardian = $request->user();

        return StudentResource::collection(
            $guardian->guardedStudents()
                ->with('schoolClass')
                ->orderBy('full_name')
                ->get(),
        );
    }

    public function accessRecords(
        IndexGuardianAccessRecordRequest $request,
        Student $student,
    ): AnonymousResourceCollection|JsonResponse {
        /** @var User $guardian */
        $guardian = $request->user();

        if ($student->guardian_user_id !== $guardian->id) {
            return response()->json([
                'code' => 'student_not_found',
                'message' => 'Aluno não encontrado.',
            ], 404);
        }

        $query = StudentAccessRecord::query()
            ->where('student_id', $student->id)
            ->with('student.schoolClass')
            ->when(
                $request->filled('date_from'),
                fn (Builder $query): Builder => $query->whereDate(
                    'access_date',
                    '>=',
                    $request->string('date_from')->toString(),
                ),
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $query): Builder => $query->whereDate(
                    'access_date',
                    '<=',
                    $request->string('date_to')->toString(),
                ),
            )
            ->orderByDesc('access_date')
            ->orderByDesc('entered_at');

        return StudentAccessRecordResource::collection(
            $query
                ->paginate($request->integer('per_page', 15))
                ->withQueryString(),
        );
    }
}
