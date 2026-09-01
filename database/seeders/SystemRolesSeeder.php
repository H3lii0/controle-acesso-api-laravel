<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SystemRolesSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::query()->get()->keyBy('key');

        collect([
            'admin' => [
                'name' => 'Administrador',
                'permissions' => $permissions->keys()->all(),
            ],
            'coordinator' => [
                'name' => 'Coordenador',
                'permissions' => [
                    'view_dashboard',
                    'manage_students',
                    'manage_guardians',
                    'manage_school_structure',
                    'export_reports',
                    'configure_notifications',
                ],
            ],
            'operator' => [
                'name' => 'Operador',
                'permissions' => [
                    'view_dashboard',
                    'manage_students',
                    'manage_terminals',
                    'enroll_biometrics',
                ],
            ],
            'read_only' => [
                'name' => 'Somente leitura',
                'permissions' => [
                    'view_dashboard',
                ],
            ],
        ])->each(function (array $role, string $key) use ($permissions): void {
            $model = Role::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $role['name'], 'is_system' => true],
            );

            $model->permissions()->sync(
                collect($role['permissions'])
                    ->map(fn (string $permission) => $permissions[$permission]->id)
                    ->all(),
            );
        });
    }
}
