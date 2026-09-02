<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\Funcionario;
use App\Models\HorarioTurma;
use App\Models\Perfil;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\TurnoEscolar;
use App\Models\UnidadeEscolar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DesenvolvimentoEscolaSeeder extends Seeder
{
    public function run(): void
    {
        $escola = Escola::query()->updateOrCreate(
            ['slug' => 'escola-modelo'],
            [
                'nome' => 'Escola Modelo',
                'status' => 'ativo',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Administrador de Desenvolvimento',
                'password' => Hash::make('password'),
                'type' => 'funcionario',
                'status' => 'ativo',
                'escola_atual_id' => $escola->id,
            ],
        );

        $admin->escolas()->syncWithoutDetaching([
            $escola->id => ['proprietario' => true],
        ]);

        Funcionario::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'escola_id' => $escola->id,
                'perfil_id' => Perfil::query()->where('chave', 'administrador')->value('id'),
                'cargo' => 'Administrador de desenvolvimento',
                'status' => 'ativo',
            ],
        );

        $unidadePrincipal = UnidadeEscolar::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'codigo' => 'PRINCIPAL',
            ],
            [
                'nome' => 'Unidade Principal',
                'endereco' => 'Endereço de desenvolvimento',
                'status' => 'ativo',
            ],
        );

        $periodoLetivo = PeriodoLetivo::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'nome' => 'Ano letivo 2026',
            ],
            [
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-12-31',
                'status' => 'ativo',
            ],
        );

        $turnoManha = TurnoEscolar::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'codigo' => 'manha',
            ],
            [
                'nome' => 'Manhã',
                'inicio' => '07:00',
                'fim' => '12:00',
                'status' => 'ativo',
            ],
        );

        $turnoTarde = TurnoEscolar::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'codigo' => 'tarde',
            ],
            [
                'nome' => 'Tarde',
                'inicio' => '13:00',
                'fim' => '17:30',
                'status' => 'ativo',
            ],
        );

        $sextaAno = Turma::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'periodo_letivo_id' => $periodoLetivo->id,
                'nome' => '6º Ano A',
            ],
            [
                'unidade_escolar_id' => $unidadePrincipal->id,
                'turno_escolar_id' => $turnoManha->id,
                'serie' => '6º Ano',
                'status' => 'ativo',
            ],
        );

        $setimoAno = Turma::query()->updateOrCreate(
            [
                'escola_id' => $escola->id,
                'periodo_letivo_id' => $periodoLetivo->id,
                'nome' => '7º Ano B',
            ],
            [
                'unidade_escolar_id' => $unidadePrincipal->id,
                'turno_escolar_id' => $turnoTarde->id,
                'serie' => '7º Ano',
                'status' => 'ativo',
            ],
        );

        foreach (range(1, 5) as $diaSemana) {
            HorarioTurma::query()->updateOrCreate(
                [
                    'turma_id' => $sextaAno->id,
                    'dia_semana' => $diaSemana,
                ],
                [
                    'escola_id' => $escola->id,
                    'entrada_inicio' => '06:40',
                    'entrada_fim' => '07:15',
                    'saida_inicio' => '11:50',
                    'saida_fim' => '12:20',
                    'status' => 'ativo',
                ],
            );

            HorarioTurma::query()->updateOrCreate(
                [
                    'turma_id' => $setimoAno->id,
                    'dia_semana' => $diaSemana,
                ],
                [
                    'escola_id' => $escola->id,
                    'entrada_inicio' => '12:40',
                    'entrada_fim' => '13:15',
                    'saida_inicio' => '17:20',
                    'saida_fim' => '17:50',
                    'status' => 'ativo',
                ],
            );
        }
    }
}
