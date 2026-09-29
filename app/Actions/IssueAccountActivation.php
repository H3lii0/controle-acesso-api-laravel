<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Support\Str;

class IssueAccountActivation
{
    public function handle(User $user): void
    {
        $plainToken = Str::random(64);
        $expiresAt = now()->addHours(
            max(1, (int) config('auth.activation.expiration_hours', 24)),
        );

        $user->activationToken()->updateOrCreate(
            [],
            [
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ],
        );

        $user->notify(new AccountActivationNotification($plainToken, $expiresAt));
    }
}
