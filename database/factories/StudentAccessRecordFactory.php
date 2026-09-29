<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentAccessRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAccessRecord>
 */
class StudentAccessRecordFactory extends Factory
{
    public function definition(): array
    {
        $enteredAt = now()->subMinutes(fake()->numberBetween(5, 240));

        return [
            'student_id' => Student::factory(),
            'access_date' => $enteredAt->toDateString(),
            'entered_at' => $enteredAt,
            'exited_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'exited_at' => now(),
        ]);
    }
}
