<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_number' => fake()->unique()->bothify('MAT-####-??'),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-17 years', '-5 years'),
            'school_class_id' => SchoolClass::factory(),
            'guardian_user_id' => User::factory()->guardian(),
            'is_active' => true,
        ];
    }
}
