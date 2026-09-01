<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'example-school'],
            [
                'name' => 'Example School',
                'status' => 'active',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Development Admin',
                'password' => Hash::make('password'),
                'type' => 'employee',
                'status' => 'active',
                'current_tenant_id' => $tenant->id,
            ],
        );

        $admin->tenants()->syncWithoutDetaching([
            $tenant->id => ['is_owner' => true],
        ]);

        Employee::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'tenant_id' => $tenant->id,
                'role_id' => Role::query()->where('key', 'admin')->value('id'),
                'position' => 'Development administrator',
                'status' => 'active',
            ],
        );
    }
}
