<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccessDenialReason;
use App\Http\Controllers\Controller;
use App\Models\AccessDeniedAttempt;
use App\Models\StudentAccessRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $timezone = config('school.timezone');
        $since = now($timezone)->subDays(30);
        $type = $request->string('type')->toString() ?: 'all';

        $alerts = collect();

        if (in_array($type, ['all', 'success'], true)) {
            StudentAccessRecord::query()
                ->with('student.schoolClass')
                ->where('entered_at', '>=', $since->copy()->utc())
                ->orderByDesc('entered_at')
                ->get()
                ->each(function (StudentAccessRecord $record) use ($alerts, $timezone): void {
                    $student = $record->student;
                    $base = [
                        'type' => 'success',
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'enrollment' => $student->enrollment_number,
                        'class_name' => $student->schoolClass->name,
                    ];

                    $alerts->push([
                        ...$base,
                        'id' => "entry-{$record->id}",
                        'title' => 'Entrada registrada',
                        'message' => "{$student->full_name} entrou na escola.",
                        'occurred_at' => $record->entered_at->setTimezone($timezone)->toIso8601String(),
                    ]);

                    if ($record->exited_at) {
                        $alerts->push([
                            ...$base,
                            'id' => "exit-{$record->id}",
                            'title' => 'Saída registrada',
                            'message' => "{$student->full_name} saiu da escola.",
                            'occurred_at' => $record->exited_at->setTimezone($timezone)->toIso8601String(),
                        ]);
                    }
                });
        }

        if (in_array($type, ['all', 'denied'], true)) {
            AccessDeniedAttempt::query()
                ->with('student.schoolClass')
                ->where('attempted_at', '>=', $since->copy()->utc())
                ->orderByDesc('attempted_at')
                ->get()
                ->each(function (AccessDeniedAttempt $attempt) use ($alerts, $timezone): void {
                    $student = $attempt->student;
                    $reason = $attempt->reason instanceof AccessDenialReason
                        ? $attempt->reason
                        : AccessDenialReason::tryFrom((string) $attempt->reason);

                    $reasonLabel = match ($reason) {
                        AccessDenialReason::StudentNotFound => 'Aluno não encontrado',
                        AccessDenialReason::StudentInactive => 'Aluno inativo',
                        AccessDenialReason::ExitTooSoon => 'Saída bloqueada por intervalo mínimo',
                        AccessDenialReason::DailyAccessCompleted => 'Acesso diário já concluído',
                        default => 'Tentativa de acesso negada',
                    };

                    $alerts->push([
                        'id' => "denied-{$attempt->id}",
                        'type' => 'denied',
                        'student_id' => $student?->id,
                        'student_name' => $student?->full_name,
                        'enrollment' => $student?->enrollment_number ?? $attempt->identifier,
                        'class_name' => $student?->schoolClass?->name,
                        'title' => 'Acesso negado',
                        'message' => $reasonLabel.'.',
                        'occurred_at' => $attempt->attempted_at->setTimezone($timezone)->toIso8601String(),
                    ]);
                });
        }

        $ordered = $alerts->sortByDesc('occurred_at')->values();
        $perPage = min(max($request->integer('per_page', 50), 1), 100);

        return response()->json([
            'data' => $ordered->take($perPage)->values(),
            'meta' => [
                'total' => $ordered->count(),
                'type' => $type,
                'days' => 30,
            ],
        ]);
    }
}
