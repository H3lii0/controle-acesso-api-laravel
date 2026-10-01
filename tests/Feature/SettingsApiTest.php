<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_administrator_can_read_and_update_school_settings(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();

        $this->actingAs($administrator)
            ->getJson('/api/admin/settings/school')
            ->assertOk()
            ->assertJsonPath('data.name', config('school.name'));

        $this->actingAs($administrator)
            ->putJson('/api/admin/settings/school', [
                'name' => 'Colégio Horizonte',
                'contact_email' => 'contato@colegio.test',
                'phone' => '81999990000',
                'address' => 'Rua da Escola, 100',
                'timezone' => 'America/Recife',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Colégio Horizonte')
            ->assertJsonPath('data.timezone', 'America/Recife');

        $this->assertDatabaseHas('school_settings', [
            'id' => 1,
            'name' => 'Colégio Horizonte',
            'contact_email' => 'contato@colegio.test',
        ]);
    }

    public function test_non_administrator_cannot_manage_school_settings(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->getJson('/api/admin/settings/school')
            ->assertForbidden()
            ->assertJsonPath('code', 'central_administrator_required');
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'full_name' => 'Nome anterior',
            'phone' => null,
        ]);

        $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'full_name' => 'Nome atualizado',
                'phone' => '81988887777',
            ])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Nome atualizado')
            ->assertJsonPath('data.phone', '81988887777');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Nome atualizado',
            'phone' => '81988887777',
        ]);
    }

    public function test_school_settings_and_profile_validate_required_values(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();

        $this->actingAs($administrator)
            ->putJson('/api/admin/settings/school', [
                'name' => '',
                'timezone' => 'America/Sao_Paulo',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'timezone']);

        $this->actingAs($administrator)
            ->putJson('/api/auth/profile', ['full_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('full_name');
    }
}
