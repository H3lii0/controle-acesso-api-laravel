<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\AccountActivationToken;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_administrator_can_search_and_filter_paginated_employees(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        User::factory()->create([
            'full_name' => 'Ana Ferreira',
            'email' => 'ana@example.test',
        ]);
        User::factory()->pendingActivation()->create([
            'full_name' => 'Bruno Lima',
            'email' => 'bruno@example.test',
        ]);
        User::factory()->guardian()->create([
            'full_name' => 'Ana Responsável',
        ]);

        $this->actingAs($administrator)
            ->getJson('/api/admin/employees?search=ANA&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'ana@example.test')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($administrator)
            ->getJson('/api/admin/employees?status=pending_activation')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'bruno@example.test');
    }

    public function test_central_administrator_can_read_an_employee(): void
    {
        $permission = Permission::factory()->create();
        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->create();
        $employee->permissions()->attach($permission);

        $this->actingAs($administrator)
            ->getJson("/api/admin/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.permissions.0.key', $permission->key);
    }

    public function test_pending_employee_can_be_updated_and_receives_new_invitation_when_email_changes(): void
    {
        Notification::fake();

        $oldPermission = Permission::factory()->create();
        $newPermission = Permission::factory()->create();
        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->pendingActivation()->create([
            'email' => 'old-email@example.test',
        ]);
        $employee->permissions()->attach($oldPermission);
        $oldToken = AccountActivationToken::factory()->for($employee)->create();

        $this->actingAs($administrator)
            ->putJson("/api/admin/employees/{$employee->id}", [
                'full_name' => ' Nome Atualizado ',
                'email' => ' NEW-EMAIL@EXAMPLE.TEST ',
                'phone' => ' 85988887777 ',
                'permissions' => [$newPermission->key],
            ])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Nome Atualizado')
            ->assertJsonPath('data.email', 'new-email@example.test')
            ->assertJsonPath('data.permissions.0.key', $newPermission->key);

        $employee->refresh();
        $newToken = $employee->activationToken()->firstOrFail();

        $this->assertSame('new-email@example.test', $employee->email);
        $this->assertSame('85988887777', $employee->phone);
        $this->assertNotSame($oldToken->token_hash, $newToken->token_hash);
        $this->assertTrue($employee->permissions->contains($newPermission));
        $this->assertFalse($employee->permissions->contains($oldPermission));
        Notification::assertSentTo($employee, AccountActivationNotification::class);
    }

    public function test_email_of_activated_employee_cannot_change_without_verification_flow(): void
    {
        Notification::fake();

        $permission = Permission::factory()->create();
        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->create([
            'email' => 'active@example.test',
        ]);

        $this->actingAs($administrator)
            ->putJson("/api/admin/employees/{$employee->id}", [
                'full_name' => $employee->full_name,
                'email' => 'new-active@example.test',
                'phone' => $employee->phone,
                'permissions' => [$permission->key],
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'employee_email_change_requires_verification');

        $this->assertSame('active@example.test', $employee->fresh()->email);
        Notification::assertNothingSent();
    }

    public function test_central_administrator_can_disable_and_reactivate_employee(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'employee-session',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($administrator)
            ->patchJson("/api/admin/employees/{$employee->id}/status", [
                'status' => AccountStatus::Disabled->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Disabled->value);

        $this->assertDatabaseMissing('sessions', ['id' => 'employee-session']);
        $this->assertSame(AccountStatus::Disabled, $employee->fresh()->account_status);

        $this->actingAs($administrator)
            ->patchJson("/api/admin/employees/{$employee->id}/status", [
                'status' => AccountStatus::Active->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Active->value);

        $this->assertSame(AccountStatus::Active, $employee->fresh()->account_status);
    }

    public function test_pending_employee_status_cannot_be_changed_manually(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->pendingActivation()->create();

        $this->actingAs($administrator)
            ->patchJson("/api/admin/employees/{$employee->id}/status", [
                'status' => AccountStatus::Active->value,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'employee_activation_required');

        $this->assertSame(AccountStatus::PendingActivation, $employee->fresh()->account_status);
    }

    public function test_guardian_cannot_be_managed_through_employee_routes(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($administrator)
            ->getJson("/api/admin/employees/{$guardian->id}")
            ->assertNotFound()
            ->assertJsonPath('code', 'employee_not_found');
    }
}
