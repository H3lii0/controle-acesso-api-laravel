<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\StudentBiometricCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentAccessApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authorized_employee_registers_one_entry_and_one_exit_per_school_day(): void
    {
        config()->set('school.timezone', 'America/Recife');
        config()->set('access_control.minimum_exit_interval_minutes', 5);

        $employee = $this->employeeWithPermissions(['access_records.create']);
        $student = Student::factory()->create([
            'enrollment_number' => 'MAT-ACCESS-001',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-30T02:00:00+00:00'));

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => ' mat-access-001 ',
            ])
            ->assertOk()
            ->assertJsonPath('code', 'entry_registered')
            ->assertJsonPath('retry_after_seconds', null)
            ->assertJsonPath('data.access_date', '2026-09-29')
            ->assertJsonPath('data.status', 'inside')
            ->assertJsonPath('data.entered_at', '2026-09-29T23:00:00-03:00')
            ->assertJsonPath('data.student.id', $student->id);

        Carbon::setTestNow(Carbon::parse('2026-09-30T02:02:00+00:00'));

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => 'MAT-ACCESS-001',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'exit_too_soon')
            ->assertJsonPath('retry_after_seconds', 180)
            ->assertJsonPath('data.status', 'inside');

        Carbon::setTestNow(Carbon::parse('2026-09-30T02:05:00+00:00'));

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => 'MAT-ACCESS-001',
            ])
            ->assertOk()
            ->assertJsonPath('code', 'exit_registered')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.exited_at', '2026-09-29T23:05:00-03:00');

        Carbon::setTestNow(Carbon::parse('2026-09-30T02:06:00+00:00'));

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => 'MAT-ACCESS-001',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'daily_access_completed');

        $this->assertDatabaseCount('student_access_records', 1);
        $this->assertTrue(
            StudentAccessRecord::query()
                ->where('student_id', $student->id)
                ->whereDate('access_date', '2026-09-29')
                ->exists(),
        );
    }

    public function test_access_reading_rejects_unknown_inactive_and_unauthorized_students(): void
    {
        $employee = $this->employeeWithPermissions(['access_records.create']);
        $inactiveStudent = Student::factory()->create([
            'enrollment_number' => 'MAT-INACTIVE-ACCESS',
            'is_active' => false,
        ]);

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => 'DOES-NOT-EXIST',
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 'student_not_found');

        $this->actingAs($employee)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => $inactiveStudent->enrollment_number,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'student_inactive');

        $employeeWithoutPermission = User::factory()->create();

        $this->actingAs($employeeWithoutPermission)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => $inactiveStudent->enrollment_number,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');

        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => $inactiveStudent->enrollment_number,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');

        $this->assertDatabaseCount('student_access_records', 0);
    }

    public function test_authorized_employee_can_filter_access_records_and_read_daily_summary(): void
    {
        config()->set('school.timezone', 'America/Recife');
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00-03:00'));

        $employee = $this->employeeWithPermissions(['access_records.view']);
        $targetClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $insideStudent = Student::factory()->create([
            'full_name' => 'Aluno Alvo Interno',
            'school_class_id' => $targetClass->id,
        ]);
        $completedStudent = Student::factory()->create([
            'full_name' => 'Aluno Concluído',
            'school_class_id' => $targetClass->id,
        ]);
        $otherStudent = Student::factory()->create([
            'full_name' => 'Aluno Outro Dia',
            'school_class_id' => $otherClass->id,
        ]);

        StudentAccessRecord::factory()->create([
            'student_id' => $insideStudent->id,
            'access_date' => '2026-09-29',
            'entered_at' => Carbon::parse('2026-09-29T07:00:00-03:00'),
            'exited_at' => null,
        ]);
        StudentAccessRecord::factory()->create([
            'student_id' => $completedStudent->id,
            'access_date' => '2026-09-29',
            'entered_at' => Carbon::parse('2026-09-29T07:10:00-03:00'),
            'exited_at' => Carbon::parse('2026-09-29T12:00:00-03:00'),
        ]);
        StudentAccessRecord::factory()->create([
            'student_id' => $otherStudent->id,
            'access_date' => '2026-09-28',
            'entered_at' => Carbon::parse('2026-09-28T07:00:00-03:00'),
            'exited_at' => null,
        ]);

        $this->actingAs($employee)
            ->getJson("/api/access-records?search=Alvo&school_class_id={$targetClass->id}&status=inside")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student.id', $insideStudent->id)
            ->assertJsonPath('data.0.status', 'inside');

        $this->actingAs($employee)
            ->getJson('/api/access-records/summary')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-09-29')
            ->assertJsonPath('data.entries', 2)
            ->assertJsonPath('data.exits', 1)
            ->assertJsonPath('data.inside', 1);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/access-records')
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'access_records.view');
    }

    public function test_central_administrator_can_register_access_without_direct_permission(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $student = Student::factory()->create();

        $this->actingAs($administrator)
            ->postJson('/api/access-records/read', [
                'enrollment_number' => $student->enrollment_number,
            ])
            ->assertOk()
            ->assertJsonPath('code', 'entry_registered');
    }

    public function test_simulated_biometric_credential_is_created_once_and_registers_access(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $student = Student::factory()->create(['enrollment_number' => 'MAT-SIMULATED-001']);

        $response = $this->actingAs($administrator)
            ->postJson('/api/students', [
                'student' => [
                    'enrollment_number' => 'MAT-SIMULATED-CREATE',
                    'full_name' => 'Aluno de Teste Biométrico',
                    'date_of_birth' => '2015-05-12',
                    'school_class_id' => $student->school_class_id,
                    'biometric_captured' => true,
                ],
                'guardian' => [
                    'mode' => 'existing',
                    'id' => $student->guardian_user_id,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.biometric.captured', true);

        $createdStudent = Student::query()->where('enrollment_number', 'MAT-SIMULATED-CREATE')->firstOrFail();
        $credential = StudentBiometricCredential::query()->where('student_id', $createdStudent->id)->firstOrFail();

        $this->assertSame($credential->identifier, $response->json('data.biometric.identifier'));
        $this->assertDatabaseCount('student_biometric_credentials', 1);

        $this->actingAs($administrator)
            ->postJson('/api/access-records/read', ['credential_identifier' => $credential->identifier])
            ->assertOk()
            ->assertJsonPath('code', 'entry_registered')
            ->assertJsonPath('data.student.id', $createdStudent->id);
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
