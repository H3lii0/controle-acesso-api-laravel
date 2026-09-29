<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\AccountActivationToken;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuardianStudentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_employee_can_create_student_with_new_guardian(): void
    {
        Notification::fake();

        $employee = $this->employeeWithPermissions(['students.create']);
        $schoolClass = SchoolClass::factory()->create(['is_active' => true]);

        $response = $this->actingAs($employee)
            ->postJson('/api/students', [
                'student' => [
                    'enrollment_number' => ' mat-2026-001 ',
                    'full_name' => ' João da Silva ',
                    'date_of_birth' => '2015-05-20',
                    'school_class_id' => $schoolClass->id,
                ],
                'guardian' => [
                    'mode' => 'new',
                    'full_name' => ' Maria da Silva ',
                    'email' => ' MARIA@EXAMPLE.TEST ',
                    'phone' => ' 85999999999 ',
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.enrollment_number', 'MAT-2026-001')
            ->assertJsonPath('data.full_name', 'João da Silva')
            ->assertJsonPath('data.guardian.email', 'maria@example.test')
            ->assertJsonPath('data.school_class.id', $schoolClass->id);

        $guardian = User::query()->where('email', 'maria@example.test')->firstOrFail();
        $student = Student::query()->where('enrollment_number', 'MAT-2026-001')->firstOrFail();

        $this->assertSame(AccountType::Guardian, $guardian->account_type);
        $this->assertSame(AccountStatus::PendingActivation, $guardian->account_status);
        $this->assertNull($guardian->password);
        $this->assertCount(0, $guardian->permissions);
        $this->assertSame($guardian->id, $student->guardian_user_id);
        $this->assertTrue($student->is_active);

        $plainToken = null;

        Notification::assertSentTo(
            $guardian,
            AccountActivationNotification::class,
            function (AccountActivationNotification $notification) use (&$plainToken): bool {
                $plainToken = $notification->token;

                return true;
            },
        );

        $this->assertDatabaseHas('account_activation_tokens', [
            'user_id' => $guardian->id,
            'token_hash' => hash('sha256', $plainToken),
        ]);
    }

    public function test_existing_guardian_can_be_linked_to_more_than_one_student_without_duplicate_account(): void
    {
        Notification::fake();

        $employee = $this->employeeWithPermissions(['students.create']);
        $guardian = User::factory()->guardian()->create();
        $schoolClass = SchoolClass::factory()->create(['is_active' => true]);
        Student::factory()->create([
            'guardian_user_id' => $guardian->id,
            'school_class_id' => $schoolClass->id,
        ]);

        $usersBefore = User::query()->count();

        $this->actingAs($employee)
            ->postJson('/api/students', [
                'student' => [
                    'enrollment_number' => 'MAT-SECOND-001',
                    'full_name' => 'Segundo Filho',
                    'date_of_birth' => '2016-06-10',
                    'school_class_id' => $schoolClass->id,
                ],
                'guardian' => [
                    'mode' => 'existing',
                    'id' => $guardian->id,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.guardian.id', $guardian->id);

        $this->assertSame($usersBefore, User::query()->count());
        $this->assertSame(2, $guardian->guardedStudents()->count());
        Notification::assertNothingSent();
    }

    public function test_student_creation_rejects_inactive_class_and_non_guardian_account(): void
    {
        Notification::fake();

        $employee = $this->employeeWithPermissions(['students.create']);
        $inactiveClass = SchoolClass::factory()->create(['is_active' => false]);

        $this->actingAs($employee)
            ->postJson('/api/students', [
                'student' => [
                    'enrollment_number' => 'MAT-INACTIVE-001',
                    'full_name' => 'Aluno sem Turma Ativa',
                    'date_of_birth' => '2015-05-20',
                    'school_class_id' => $inactiveClass->id,
                ],
                'guardian' => [
                    'mode' => 'new',
                    'full_name' => 'Responsável não salvo',
                    'email' => 'not-saved@example.test',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('student.school_class_id');

        $activeClass = SchoolClass::factory()->create(['is_active' => true]);

        $this->actingAs($employee)
            ->postJson('/api/students', [
                'student' => [
                    'enrollment_number' => 'MAT-WRONG-001',
                    'full_name' => 'Aluno com Conta Incorreta',
                    'date_of_birth' => '2015-05-20',
                    'school_class_id' => $activeClass->id,
                ],
                'guardian' => [
                    'mode' => 'existing',
                    'id' => $employee->id,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('guardian.id');

        $this->assertDatabaseMissing('users', ['email' => 'not-saved@example.test']);
        $this->assertDatabaseCount('students', 0);
        Notification::assertNothingSent();
    }

    public function test_authorized_employee_can_list_and_read_students_with_relationships(): void
    {
        $employee = $this->employeeWithPermissions(['students.view']);
        $targetGuardian = User::factory()->guardian()->create([
            'full_name' => 'Responsável Pesquisável',
        ]);
        $otherGuardian = User::factory()->guardian()->create();
        $targetClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $targetStudent = Student::factory()->create([
            'full_name' => 'Aluno Alvo',
            'school_class_id' => $targetClass->id,
            'guardian_user_id' => $targetGuardian->id,
        ]);
        Student::factory()->create([
            'school_class_id' => $otherClass->id,
            'guardian_user_id' => $otherGuardian->id,
        ]);

        $this->actingAs($employee)
            ->getJson("/api/students?search=Pesquisável&school_class_id={$targetClass->id}&is_active=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetStudent->id)
            ->assertJsonPath('data.0.guardian.id', $targetGuardian->id)
            ->assertJsonPath('data.0.school_class.id', $targetClass->id);

        $this->actingAs($employee)
            ->getJson("/api/students/{$targetStudent->id}")
            ->assertOk()
            ->assertJsonPath('data.enrollment_number', $targetStudent->enrollment_number);
    }

    public function test_student_can_be_updated_and_linked_to_another_existing_guardian(): void
    {
        Notification::fake();

        $employee = $this->employeeWithPermissions(['students.update']);
        $oldGuardian = User::factory()->guardian()->create();
        $newGuardian = User::factory()->guardian()->create();
        $oldClass = SchoolClass::factory()->create();
        $newClass = SchoolClass::factory()->create();
        $student = Student::factory()->create([
            'enrollment_number' => 'MAT-OLD-001',
            'guardian_user_id' => $oldGuardian->id,
            'school_class_id' => $oldClass->id,
        ]);

        $this->actingAs($employee)
            ->putJson("/api/students/{$student->id}", [
                'student' => [
                    'enrollment_number' => 'MAT-NEW-001',
                    'full_name' => 'Nome Atualizado',
                    'date_of_birth' => '2014-04-14',
                    'school_class_id' => $newClass->id,
                ],
                'guardian' => [
                    'mode' => 'existing',
                    'id' => $newGuardian->id,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.enrollment_number', 'MAT-NEW-001')
            ->assertJsonPath('data.guardian.id', $newGuardian->id)
            ->assertJsonPath('data.school_class.id', $newClass->id);

        $student->refresh();
        $this->assertSame($newGuardian->id, $student->guardian_user_id);
        $this->assertSame($newClass->id, $student->school_class_id);
        Notification::assertNothingSent();
    }

    public function test_authorized_employee_can_change_student_status(): void
    {
        $employee = $this->employeeWithPermissions(['students.change_status']);
        $student = Student::factory()->create(['is_active' => true]);

        $this->actingAs($employee)
            ->patchJson("/api/students/{$student->id}/status", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($student->fresh()->is_active);
    }

    public function test_guardian_can_be_searched_and_pending_email_change_renews_invitation(): void
    {
        Notification::fake();

        $employee = $this->employeeWithPermissions(['students.create', 'students.update']);
        $guardian = User::factory()->guardian()->pendingActivation()->create([
            'full_name' => 'Responsável Encontrável',
            'email' => 'old-guardian@example.test',
        ]);
        Student::factory()->create(['guardian_user_id' => $guardian->id]);
        $oldToken = AccountActivationToken::factory()->for($guardian)->create();

        $this->actingAs($employee)
            ->getJson('/api/guardians?search=Encontrável')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $guardian->id)
            ->assertJsonPath('data.0.students_count', 1);

        $this->actingAs($employee)
            ->putJson("/api/guardians/{$guardian->id}", [
                'full_name' => 'Responsável Atualizado',
                'email' => 'new-guardian@example.test',
                'phone' => '85977776666',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'new-guardian@example.test');

        $guardian->refresh();
        $this->assertNotSame(
            $oldToken->token_hash,
            $guardian->activationToken()->firstOrFail()->token_hash,
        );
        Notification::assertSentTo($guardian, AccountActivationNotification::class);
    }

    public function test_guardian_has_no_access_to_student_administration(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)
            ->getJson('/api/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');

        $this->actingAs($guardian)
            ->getJson('/api/guardians?search=test')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function employeeWithPermissions(array $permissionKeys): User
    {
        $employee = User::factory()->create();

        $permissionIds = collect($permissionKeys)
            ->map(fn (string $key): int => Permission::factory()->create(['key' => $key])->id);

        $employee->permissions()->sync($permissionIds);

        return $employee;
    }
}
