<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'escola_id',
    'turma_id',
    'dia_semana',
    'entrada_inicio',
    'entrada_fim',
    'saida_inicio',
    'saida_fim',
    'status',
])]
class HorarioTurma extends Model
{
    use HasFactory;

    protected $table = 'horarios_turma';

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    protected function casts(): array
    {
        return [
            'entrada_inicio' => 'datetime:H:i',
            'entrada_fim' => 'datetime:H:i',
            'saida_inicio' => 'datetime:H:i',
            'saida_fim' => 'datetime:H:i',
        ];
    }
}
