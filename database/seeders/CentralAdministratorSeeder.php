<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class CentralAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('school.central_administrator.name');
        $email = config('school.central_administrator.email');
        $password = config('school.central_administrator.password');

        if (! is_string($name) || ! is_string($email) || ! is_string($password) || $password === '') {
            throw new RuntimeException('Configure CENTRAL_ADMIN_NAME, CENTRAL_ADMIN_EMAIL e CENTRAL_ADMIN_PASSWORD.');
        }

        $administrator = User::query()
            ->where('account_type', AccountType::CentralAdministrator)
            ->firstOrNew();

        $administrator->fill([
            'full_name' => trim($name),
            'email' => Str::lower(trim($email)),
            'password' => $password,
            'account_type' => AccountType::CentralAdministrator,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => $administrator->email_verified_at ?? now(),
        ])->save();
    }
}
