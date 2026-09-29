<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $resource = fake()->unique()->lexify('resource_????');

        return [
            'key' => $resource.'.view',
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'category' => $resource,
        ];
    }
}
