<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'slug', 'status'])]
class Escola extends Model
{
    use HasFactory;

    protected $table = 'escolas';

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'escola_user')
            ->withPivot(['proprietario'])
            ->withTimestamps();
    }

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class);
    }

    public function unidadesEscolares(): HasMany
    {
        return $this->hasMany(UnidadeEscolar::class);
    }

    public function periodosLetivos(): HasMany
    {
        return $this->hasMany(PeriodoLetivo::class);
    }

    public function turnosEscolares(): HasMany
    {
        return $this->hasMany(TurnoEscolar::class);
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }
}
