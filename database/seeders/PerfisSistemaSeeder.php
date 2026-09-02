<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PerfisSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = Permissao::query()->get()->keyBy('chave');

        collect([
            'administrador' => [
                'nome' => 'Administrador',
                'permissoes' => $permissoes->keys()->all(),
            ],
            'coordenador' => [
                'nome' => 'Coordenador',
                'permissoes' => [
                    'ver_painel',
                    'gerenciar_alunos',
                    'gerenciar_responsaveis',
                    'gerenciar_estrutura_escolar',
                    'exportar_relatorios',
                    'configurar_notificacoes',
                ],
            ],
            'operador' => [
                'nome' => 'Operador',
                'permissoes' => [
                    'ver_painel',
                    'gerenciar_alunos',
                    'gerenciar_terminais',
                    'cadastrar_biometria',
                ],
            ],
            'somente_leitura' => [
                'nome' => 'Somente leitura',
                'permissoes' => [
                    'ver_painel',
                ],
            ],
        ])->each(function (array $perfil, string $chave) use ($permissoes): void {
            $model = Perfil::query()->updateOrCreate(
                ['chave' => $chave],
                ['nome' => $perfil['nome'], 'sistema' => true],
            );

            $model->permissoes()->sync(
                collect($perfil['permissoes'])
                    ->map(fn (string $permissao) => $permissoes[$permissao]->id)
                    ->all(),
            );
        });
    }
}
