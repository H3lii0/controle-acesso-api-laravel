<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['escola_id', 'nome', 'codigo', 'inicio', 'fim', 'status'])]
class TurnoEscolar extends Model
{
    use HasFactory;

    protected $table = 'turnos_escolares';

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class);
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime:H:i',
            'fim' => 'datetime:H:i',
        ];
    }
}
