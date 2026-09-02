<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['chave', 'nome', 'descricao', 'sistema'])]
class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfis';

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class);
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao');
    }

    protected function casts(): array
    {
        return [
            'sistema' => 'boolean',
        ];
    }
}
