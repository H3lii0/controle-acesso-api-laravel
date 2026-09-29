<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAccessRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'access_date' => $this->access_date?->format('Y-m-d'),
            'status' => $this->exited_at === null ? 'inside' : 'completed',
            'entered_at' => $this->entered_at
                ?->setTimezone(config('school.timezone'))
                ->toIso8601String(),
            'exited_at' => $this->exited_at
                ?->setTimezone(config('school.timezone'))
                ->toIso8601String(),
            'student' => $this->whenLoaded('student', fn (): array => [
                'id' => $this->student->id,
                'enrollment_number' => $this->student->enrollment_number,
                'full_name' => $this->student->full_name,
                'is_active' => $this->student->is_active,
                'school_class' => new SchoolClassResource($this->student->schoolClass),
            ]),
        ];
    }
}
