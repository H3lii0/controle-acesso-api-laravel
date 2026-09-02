<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissoesSistemaSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['chave' => 'ver_painel', 'nome' => 'Ver painel e metricas'],
            ['chave' => 'gerenciar_alunos', 'nome' => 'Gerenciar alunos'],
            ['chave' => 'gerenciar_responsaveis', 'nome' => 'Gerenciar responsaveis'],
            ['chave' => 'gerenciar_funcionarios', 'nome' => 'Gerenciar funcionarios'],
            ['chave' => 'gerenciar_permissoes', 'nome' => 'Gerenciar permissoes'],
            ['chave' => 'gerenciar_estrutura_escolar', 'nome' => 'Gerenciar estrutura escolar'],
            ['chave' => 'gerenciar_terminais', 'nome' => 'Gerenciar terminais'],
            ['chave' => 'cadastrar_biometria', 'nome' => 'Cadastrar biometria'],
            ['chave' => 'revogar_biometria', 'nome' => 'Revogar biometria'],
            ['chave' => 'exportar_relatorios', 'nome' => 'Exportar relatorios'],
            ['chave' => 'ver_auditoria', 'nome' => 'Ver auditoria'],
            ['chave' => 'configurar_notificacoes', 'nome' => 'Configurar notificacoes'],
        ])->each(fn (array $permissao) => Permissao::query()->updateOrCreate(
            ['chave' => $permissao['chave']],
            $permissao,
        ));
    }
}
