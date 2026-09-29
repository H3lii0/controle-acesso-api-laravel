<?php

namespace Database\Factories;

use App\Models\AccountActivationToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AccountActivationToken>
 */
class AccountActivationTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->pendingActivation(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ];
    }
}
