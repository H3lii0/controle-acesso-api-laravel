<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardSummaryRequest;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\SchoolClass;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(DashboardSummaryRequest $request): JsonResponse
    {
        $timezone = config('school.timezone');
        $date = CarbonImmutable::createFromFormat('Y-m-d', $request->input('date', now($timezone)->toDateString()), $timezone);
        $period = $request->input('period', 'today');
        $from = $period === 'last_7_days' ? $date->subDays(6) : $date;
        $studentsQuery = $this->studentsQuery($request);
        $recordsQuery = $this->recordsQuery($request, $from, $date);
        $todayRecords = $this->recordsQuery($request, $date, $date)->get(['student_id', 'exited_at']);
        $recentRecords = (clone $recordsQuery)->with('student.schoolClass')->orderByDesc('entered_at')->orderByDesc('id')->limit(10)->get();
        $events = $recentRecords->flatMap(fn (StudentAccessRecord $record): array => $this->eventsFor($record, $timezone))->sortByDesc('timestamp')->take(5)->values();
        $entries = (clone $recordsQuery)->count();
        $exits = (clone $recordsQuery)->whereNotNull('exited_at')->count();
        $inside = $todayRecords->whereNull('exited_at')->count();
        $totalStudents = (clone $studentsQuery)->count();

        return response()->json(['data' => [
            'date' => $date->toDateString(),
            'period' => $period,
            'filters' => ['school_class_id' => $request->integer('school_class_id') ?: null, 'shift' => $request->input('shift')],
            'classes' => SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'shift']),
            'students' => ['total' => $totalStudents, 'inside' => $inside, 'without_access' => max(0, $totalStudents - $todayRecords->pluck('student_id')->unique()->count())],
            'accesses' => ['readings' => $entries + $exits, 'entries' => $entries, 'exits' => $exits, 'inside' => $inside],
            'delays' => null,
            'denied' => null,
            'flow' => $this->flowFor($recordsQuery, $timezone),
            'recent_events' => $events,
            'terminals' => [['name' => 'Terminal simulado', 'status' => 'online', 'last_sync_at' => now($timezone)->toIso8601String()]],
        ]]);
    }

    private function studentsQuery(DashboardSummaryRequest $request): Builder
    {
        $query = Student::query()->where('is_active', true);
        if ($request->filled('school_class_id')) $query->where('school_class_id', $request->integer('school_class_id'));
        if ($request->filled('shift')) $query->whereHas('schoolClass', fn (Builder $classQuery) => $classQuery->where('shift', $request->input('shift')));
        return $query;
    }

    private function recordsQuery(DashboardSummaryRequest $request, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        $query = StudentAccessRecord::query()
            ->whereDate('access_date', '>=', $from->toDateString())
            ->whereDate('access_date', '<=', $to->toDateString());
        if ($request->filled('school_class_id')) $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('school_class_id', $request->integer('school_class_id')));
        if ($request->filled('shift')) $query->whereHas('student.schoolClass', fn (Builder $classQuery) => $classQuery->where('shift', $request->input('shift')));
        return $query;
    }

    private function eventsFor(StudentAccessRecord $record, string $timezone): array
    {
        $student = $record->student;
        $base = ['student_id' => $student->id, 'name' => $student->full_name, 'enrollment' => $student->enrollment_number, 'class_name' => $student->schoolClass->name];
        $events = [[...$base, 'timestamp' => $record->entered_at->setTimezone($timezone)->toIso8601String(), 'movement' => 'Entrada', 'status' => $record->exited_at ? 'Autorizado' : 'Na escola', 'tone' => 'success']];
        if ($record->exited_at) $events[] = [...$base, 'timestamp' => $record->exited_at->setTimezone($timezone)->toIso8601String(), 'movement' => 'Saída', 'status' => 'Autorizado', 'tone' => 'info'];
        return $events;
    }

    private function flowFor(Builder $recordsQuery, string $timezone): array
    {
        $records = (clone $recordsQuery)->get(['entered_at', 'exited_at']);
        // Keep the complete day visible so events outside the expected school
        // window are still represented in the operational dashboard.
        $hours = collect(range(0, 23))->mapWithKeys(fn (int $hour): array => [$hour => ['label' => sprintf('%02d:00', $hour), 'entries' => 0, 'exits' => 0]]);
        foreach ($records as $record) {
            $entryHour = (int) $record->entered_at->setTimezone($timezone)->format('G');
            if ($hours->has($entryHour)) { $bucket = $hours->get($entryHour); $bucket['entries']++; $hours->put($entryHour, $bucket); }
            if ($record->exited_at) { $exitHour = (int) $record->exited_at->setTimezone($timezone)->format('G'); if ($hours->has($exitHour)) { $bucket = $hours->get($exitHour); $bucket['exits']++; $hours->put($exitHour, $bucket); } }
        }
        return $hours->values()->all();
    }
}
