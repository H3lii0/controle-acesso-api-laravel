<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'escola_id',
    'unidade_escolar_id',
    'periodo_letivo_id',
    'turno_escolar_id',
    'nome',
    'serie',
    'status',
])]
class Turma extends Model
{
    use HasFactory;

    protected $table = 'turmas';

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class);
    }

    public function unidadeEscolar(): BelongsTo
    {
        return $this->belongsTo(UnidadeEscolar::class);
    }

    public function periodoLetivo(): BelongsTo
    {
        return $this->belongsTo(PeriodoLetivo::class);
    }

    public function turnoEscolar(): BelongsTo
    {
        return $this->belongsTo(TurnoEscolar::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioTurma::class);
    }
}
