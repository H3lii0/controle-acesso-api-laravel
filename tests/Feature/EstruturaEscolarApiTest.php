<?php

namespace Tests\Feature;

use App\Models\Escola;
use App\Models\HorarioTurma;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\TurnoEscolar;
use App\Models\UnidadeEscolar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EstruturaEscolarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_ler_estrutura_da_escola_atual(): void
    {
        $escola = Escola::query()->create([
            'nome' => 'Escola Modelo',
            'slug' => 'escola-modelo',
            'status' => 'ativo',
        ]);

        $outraEscola = Escola::query()->create([
            'nome' => 'Outra Escola',
            'slug' => 'outra-escola',
            'status' => 'ativo',
        ]);

        $unidade = UnidadeEscolar::query()->create([
            'escola_id' => $escola->id,
            'nome' => 'Unidade Principal',
            'codigo' => 'PRINCIPAL',
            'status' => 'ativo',
        ]);

        UnidadeEscolar::query()->create([
            'escola_id' => $outraEscola->id,
            'nome' => 'Unidade de Outra Escola',
            'codigo' => 'OUTRA',
            'status' => 'ativo',
        ]);

        $periodo = PeriodoLetivo::query()->create([
            'escola_id' => $escola->id,
            'nome' => 'Ano letivo 2026',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'status' => 'ativo',
        ]);

        $turno = TurnoEscolar::query()->create([
            'escola_id' => $escola->id,
            'nome' => 'Manhã',
            'codigo' => 'manha',
            'inicio' => '07:00',
            'fim' => '12:00',
            'status' => 'ativo',
        ]);

        $turma = Turma::query()->create([
            'escola_id' => $escola->id,
            'unidade_escolar_id' => $unidade->id,
            'periodo_letivo_id' => $periodo->id,
            'turno_escolar_id' => $turno->id,
            'nome' => '6º Ano A',
            'serie' => '6º Ano',
            'status' => 'ativo',
        ]);

        HorarioTurma::query()->create([
            'escola_id' => $escola->id,
            'turma_id' => $turma->id,
            'dia_semana' => 1,
            'entrada_inicio' => '06:40',
            'entrada_fim' => '07:15',
            'saida_inicio' => '11:50',
            'saida_fim' => '12:20',
            'status' => 'ativo',
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'password',
            'type' => 'funcionario',
            'status' => 'ativo',
            'escola_atual_id' => $escola->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/estrutura-escolar')
            ->assertOk()
            ->assertJsonPath('data.escola.slug', 'escola-modelo')
            ->assertJsonPath('data.unidades.0.codigo', 'PRINCIPAL')
            ->assertJsonPath('data.periodos_letivos.0.nome', 'Ano letivo 2026')
            ->assertJsonPath('data.turnos.0.codigo', 'manha')
            ->assertJsonPath('data.turmas.0.nome', '6º Ano A')
            ->assertJsonPath('data.turmas.0.horarios.0.dia_semana', 1)
            ->assertJsonMissing(['nome' => 'Unidade de Outra Escola']);
    }

    public function test_estrutura_escolar_exige_escola_atual(): void
    {
        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'password',
            'type' => 'funcionario',
            'status' => 'ativo',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/estrutura-escolar')->assertUnprocessable();
    }
}
