<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'type', 'status', 'escola_atual_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function funcionario(): HasOne
    {
        return $this->hasOne(Funcionario::class);
    }

    public function escolaAtual(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_atual_id');
    }

    public function escolas(): BelongsToMany
    {
        return $this->belongsToMany(Escola::class, 'escola_user')
            ->withPivot(['proprietario'])
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
