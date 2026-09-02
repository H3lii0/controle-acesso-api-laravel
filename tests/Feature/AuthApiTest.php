<?php

namespace Tests\Feature;

use App\Models\Escola;
use App\Models\Funcionario;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_read_profile(): void
    {
        $permissao = Permissao::query()->create([
            'chave' => 'ver_painel',
            'nome' => 'Ver painel',
        ]);

        $perfil = Perfil::query()->create([
            'chave' => 'administrador',
            'nome' => 'Administrador',
        ]);

        $perfil->permissoes()->attach($permissao);

        $escola = Escola::query()->create([
            'nome' => 'Escola Modelo',
            'slug' => 'escola-modelo',
            'status' => 'ativo',
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'funcionario',
            'status' => 'ativo',
            'escola_atual_id' => $escola->id,
        ]);

        $user->escolas()->attach($escola, ['proprietario' => true]);

        Funcionario::query()->create([
            'escola_id' => $escola->id,
            'user_id' => $user->id,
            'perfil_id' => $perfil->id,
            'cargo' => 'Administrador',
            'status' => 'ativo',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.nome', 'Admin User')
            ->assertJsonPath('data.user.tipo', 'funcionario')
            ->assertJsonPath('data.user.escola_atual.slug', 'escola-modelo')
            ->assertJsonPath('data.user.escolas.0.slug', 'escola-modelo')
            ->assertJsonPath('data.user.perfil.chave', 'administrador')
            ->assertJsonPath('data.user.perfil.permissoes.0', 'ver_painel')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'user'],
            ]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'funcionario',
            'status' => 'ativo',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }
}
