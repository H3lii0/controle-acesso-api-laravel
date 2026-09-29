<?php

namespace App\Actions;

use App\Enums\AccessReadingResult;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use Illuminate\Support\Facades\DB;

class RegisterStudentAccess
{
    /**
     * @return array{result: AccessReadingResult, record: StudentAccessRecord, retry_after_seconds: int|null}
     */
    public function handle(Student $student): array
    {
        return DB::transaction(function () use ($student): array {
            Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->firstOrFail();

            $occurredAt = now();
            $accessDate = $occurredAt
                ->copy()
                ->setTimezone(config('school.timezone'))
                ->toDateString();

            $record = StudentAccessRecord::query()
                ->where('student_id', $student->id)
                ->whereDate('access_date', $accessDate)
                ->lockForUpdate()
                ->first();

            if (! $record instanceof StudentAccessRecord) {
                $record = StudentAccessRecord::query()->create([
                    'student_id' => $student->id,
                    'access_date' => $accessDate,
                    'entered_at' => $occurredAt,
                    'exited_at' => null,
                ]);

                return [
                    'result' => AccessReadingResult::EntryRegistered,
                    'record' => $record,
                    'retry_after_seconds' => null,
                ];
            }

            if ($record->exited_at !== null) {
                return [
                    'result' => AccessReadingResult::DailyAccessCompleted,
                    'record' => $record,
                    'retry_after_seconds' => null,
                ];
            }

            $minimumIntervalMinutes = (int) config('access_control.minimum_exit_interval_minutes', 5);
            $earliestExitAt = $record->entered_at->addMinutes($minimumIntervalMinutes);

            if ($occurredAt->lt($earliestExitAt)) {
                return [
                    'result' => AccessReadingResult::ExitTooSoon,
                    'record' => $record,
                    'retry_after_seconds' => max(
                        1,
                        $earliestExitAt->getTimestamp() - $occurredAt->getTimestamp(),
                    ),
                ];
            }

            $record->update([
                'exited_at' => $occurredAt,
            ]);

            return [
                'result' => AccessReadingResult::ExitRegistered,
                'record' => $record->refresh(),
                'retry_after_seconds' => null,
            ];
        });
    }
}
