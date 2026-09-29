<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'key' => 'dashboard.view',
                'name' => 'Visualizar painel',
                'description' => 'Permite visualizar os indicadores do painel.',
                'category' => 'dashboard',
            ],
            [
                'key' => 'students.view',
                'name' => 'Visualizar alunos',
                'description' => 'Permite consultar alunos e seus dados básicos.',
                'category' => 'students',
            ],
            [
                'key' => 'students.create',
                'name' => 'Cadastrar alunos',
                'description' => 'Permite cadastrar alunos e responsáveis.',
                'category' => 'students',
            ],
            [
                'key' => 'students.update',
                'name' => 'Editar alunos',
                'description' => 'Permite alterar dados de alunos existentes.',
                'category' => 'students',
            ],
            [
                'key' => 'students.change_status',
                'name' => 'Alterar situação dos alunos',
                'description' => 'Permite ativar ou desativar alunos.',
                'category' => 'students',
            ],
            [
                'key' => 'access_records.view',
                'name' => 'Visualizar registros de acesso',
                'description' => 'Permite consultar entradas e saídas dos alunos.',
                'category' => 'access_records',
            ],
            [
                'key' => 'access_records.create',
                'name' => 'Registrar acessos',
                'description' => 'Permite registrar entradas e saídas dos alunos.',
                'category' => 'access_records',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['key' => $permission['key']],
                $permission,
            );
        }
    }
}
