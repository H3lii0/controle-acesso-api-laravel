<?php

namespace Database\Factories;

use App\Enums\SchoolShift;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('#º Ano ?'),
            'shift' => fake()->randomElement(SchoolShift::cases()),
            'is_active' => true,
        ];
    }
}
