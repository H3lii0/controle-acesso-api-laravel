<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('###########'),
            'password' => static::$password ??= Hash::make('password'),
            'account_type' => AccountType::Employee,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function pendingActivation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password' => null,
            'account_status' => AccountStatus::PendingActivation,
            'email_verified_at' => null,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_status' => AccountStatus::Disabled,
        ]);
    }

    public function guardian(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_type' => AccountType::Guardian,
        ]);
    }

    public function centralAdministrator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_type' => AccountType::CentralAdministrator,
        ]);
    }
}
