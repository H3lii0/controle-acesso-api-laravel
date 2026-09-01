<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class SystemPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['key' => 'view_dashboard', 'name' => 'Ver painel e metricas'],
            ['key' => 'manage_students', 'name' => 'Gerenciar alunos'],
            ['key' => 'manage_guardians', 'name' => 'Gerenciar responsaveis'],
            ['key' => 'manage_employees', 'name' => 'Gerenciar funcionarios'],
            ['key' => 'manage_permissions', 'name' => 'Gerenciar permissoes'],
            ['key' => 'manage_school_structure', 'name' => 'Gerenciar estrutura escolar'],
            ['key' => 'manage_terminals', 'name' => 'Gerenciar terminais'],
            ['key' => 'enroll_biometrics', 'name' => 'Cadastrar biometria'],
            ['key' => 'revoke_biometrics', 'name' => 'Revogar biometria'],
            ['key' => 'export_reports', 'name' => 'Exportar relatorios'],
            ['key' => 'view_audit', 'name' => 'Ver auditoria'],
            ['key' => 'configure_notifications', 'name' => 'Configurar notificacoes'],
        ])->each(fn (array $permission) => Permission::query()->updateOrCreate(
            ['key' => $permission['key']],
            $permission,
        ));
    }
}
