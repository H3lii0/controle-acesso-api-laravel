<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HorarioTurma;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\TurnoEscolar;
use App\Models\UnidadeEscolar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstruturaEscolarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $escola = $request->user()?->escolaAtual;

        if (! $escola) {
            return response()->json([
                'message' => 'Nenhuma escola selecionada para o usuário autenticado.',
            ], 422);
        }

        $unidades = UnidadeEscolar::query()
            ->whereBelongsTo($escola)
            ->withCount('turmas')
            ->orderBy('nome')
            ->get();

        $periodosLetivos = PeriodoLetivo::query()
            ->whereBelongsTo($escola)
            ->orderByDesc('data_inicio')
            ->get();

        $turnos = TurnoEscolar::query()
            ->whereBelongsTo($escola)
            ->orderBy('inicio')
            ->get();

        $turmas = Turma::query()
            ->whereBelongsTo($escola)
            ->with(['unidadeEscolar', 'periodoLetivo', 'turnoEscolar', 'horarios'])
            ->orderBy('nome')
            ->get();

        return response()->json([
            'data' => [
                'escola' => [
                    'id' => $escola->id,
                    'nome' => $escola->nome,
                    'slug' => $escola->slug,
                    'status' => $escola->status,
                ],
                'unidades' => $unidades->map(fn (UnidadeEscolar $unidade) => [
                    'id' => $unidade->id,
                    'nome' => $unidade->nome,
                    'codigo' => $unidade->codigo,
                    'endereco' => $unidade->endereco,
                    'status' => $unidade->status,
                    'total_turmas' => $unidade->turmas_count,
                ])->values(),
                'periodos_letivos' => $periodosLetivos->map(fn (PeriodoLetivo $periodo) => [
                    'id' => $periodo->id,
                    'nome' => $periodo->nome,
                    'data_inicio' => $periodo->data_inicio?->toDateString(),
                    'data_fim' => $periodo->data_fim?->toDateString(),
                    'status' => $periodo->status,
                ])->values(),
                'turnos' => $turnos->map(fn (TurnoEscolar $turno) => [
                    'id' => $turno->id,
                    'nome' => $turno->nome,
                    'codigo' => $turno->codigo,
                    'inicio' => $this->formatarHorario($turno->inicio),
                    'fim' => $this->formatarHorario($turno->fim),
                    'status' => $turno->status,
                ])->values(),
                'turmas' => $turmas->map(fn (Turma $turma) => [
                    'id' => $turma->id,
                    'nome' => $turma->nome,
                    'serie' => $turma->serie,
                    'status' => $turma->status,
                    'unidade' => [
                        'id' => $turma->unidadeEscolar->id,
                        'nome' => $turma->unidadeEscolar->nome,
                        'codigo' => $turma->unidadeEscolar->codigo,
                    ],
                    'periodo_letivo' => [
                        'id' => $turma->periodoLetivo->id,
                        'nome' => $turma->periodoLetivo->nome,
                    ],
                    'turno' => [
                        'id' => $turma->turnoEscolar->id,
                        'nome' => $turma->turnoEscolar->nome,
                        'codigo' => $turma->turnoEscolar->codigo,
                    ],
                    'horarios' => $turma->horarios
                        ->sortBy('dia_semana')
                        ->map(fn (HorarioTurma $horario) => [
                            'id' => $horario->id,
                            'dia_semana' => $horario->dia_semana,
                            'entrada_inicio' => $this->formatarHorario($horario->entrada_inicio),
                            'entrada_fim' => $this->formatarHorario($horario->entrada_fim),
                            'saida_inicio' => $this->formatarHorario($horario->saida_inicio),
                            'saida_fim' => $this->formatarHorario($horario->saida_fim),
                            'status' => $horario->status,
                        ])
                        ->values(),
                ])->values(),
            ],
        ]);
    }

    private function formatarHorario(mixed $valor): ?string
    {
        if (! $valor) {
            return null;
        }

        if (is_string($valor)) {
            return substr($valor, 0, 5);
        }

        return $valor->format('H:i');
    }
}
