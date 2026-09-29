<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\AccountActivationToken;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_administrator_can_list_available_permissions(): void
    {
        Permission::factory()->create([
            'key' => 'students.view',
            'name' => 'Visualizar alunos',
            'category' => 'students',
        ]);

        $administrator = User::factory()->centralAdministrator()->create();

        $this->actingAs($administrator)
            ->getJson('/api/admin/permissions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'students.view')
            ->assertJsonPath('data.0.name', 'Visualizar alunos');
    }

    public function test_central_administrator_can_create_employee_with_individual_permissions(): void
    {
        Notification::fake();

        $permissions = Permission::factory()->count(2)->create();
        $administrator = User::factory()->centralAdministrator()->create();

        $response = $this->actingAs($administrator)
            ->postJson('/api/admin/employees', [
                'full_name' => ' Maria da Silva ',
                'email' => ' MARIA@EXAMPLE.TEST ',
                'phone' => ' 85999999999 ',
                'permissions' => $permissions->pluck('key')->all(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.full_name', 'Maria da Silva')
            ->assertJsonPath('data.email', 'maria@example.test')
            ->assertJsonCount(2, 'data.permissions');

        $employee = User::query()->where('email', 'maria@example.test')->firstOrFail();

        $this->assertSame(AccountType::Employee, $employee->account_type);
        $this->assertSame(AccountStatus::PendingActivation, $employee->account_status);
        $this->assertNull($employee->password);
        $this->assertNull($employee->email_verified_at);
        $this->assertCount(2, $employee->permissions);

        $plainToken = null;
        $sentNotification = null;

        Notification::assertSentTo(
            $employee,
            AccountActivationNotification::class,
            function (AccountActivationNotification $notification) use (&$plainToken, &$sentNotification): bool {
                $plainToken = $notification->token;
                $sentNotification = $notification;

                return true;
            },
        );

        $this->assertIsString($plainToken);
        $this->assertInstanceOf(AccountActivationNotification::class, $sentNotification);
        $this->assertSame(
            'http://localhost:4200/ativar-conta?token='.$plainToken,
            $sentNotification->toMail($employee)->actionUrl,
        );
        $this->assertDatabaseHas('account_activation_tokens', [
            'user_id' => $employee->id,
            'token_hash' => hash('sha256', $plainToken),
        ]);
        $this->assertDatabaseMissing('account_activation_tokens', [
            'token_hash' => $plainToken,
        ]);
    }

    public function test_only_central_administrator_can_manage_employees(): void
    {
        $permission = Permission::factory()->create();
        $employee = User::factory()->create();

        $payload = [
            'full_name' => 'Novo Funcionário',
            'email' => 'novo@example.test',
            'permissions' => [$permission->key],
        ];

        $this->actingAs($employee)
            ->postJson('/api/admin/employees', $payload)
            ->assertForbidden()
            ->assertJsonPath('code', 'central_administrator_required');

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/admin/employees', $payload)
            ->assertUnauthorized();
    }

    public function test_employee_creation_validates_unique_email_and_permissions(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        User::factory()->create(['email' => 'existing@example.test']);

        $this->actingAs($administrator)
            ->postJson('/api/admin/employees', [
                'full_name' => 'Funcionário Inválido',
                'email' => 'EXISTING@EXAMPLE.TEST',
                'permissions' => ['permission.that.does.not.exist'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'permissions.0']);
    }

    public function test_central_administrator_can_resend_invitation_to_pending_employee(): void
    {
        Notification::fake();

        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->pendingActivation()->create();
        $oldToken = AccountActivationToken::factory()->for($employee)->create();

        $this->actingAs($administrator)
            ->postJson("/api/admin/employees/{$employee->id}/resend-invitation")
            ->assertOk();

        $newToken = $employee->activationToken()->firstOrFail();

        $this->assertNotSame($oldToken->token_hash, $newToken->token_hash);
        $this->assertDatabaseCount('account_activation_tokens', 1);
        Notification::assertSentTo($employee, AccountActivationNotification::class);
    }

    public function test_invitation_cannot_be_resent_to_active_employee(): void
    {
        Notification::fake();

        $administrator = User::factory()->centralAdministrator()->create();
        $employee = User::factory()->create();

        $this->actingAs($administrator)
            ->postJson("/api/admin/employees/{$employee->id}/resend-invitation")
            ->assertConflict()
            ->assertJsonPath('code', 'employee_not_pending_activation');

        Notification::assertNothingSent();
    }
}
