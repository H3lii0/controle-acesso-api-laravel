<?php

namespace Tests\Feature;

use App\Enums\SchoolShift;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolClassApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_administrator_can_manage_school_classes(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();

        $createResponse = $this->actingAs($administrator)
            ->postJson('/api/admin/school-classes', [
                'name' => ' 5º Ano A ',
                'shift' => SchoolShift::Morning->value,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', '5º Ano A')
            ->assertJsonPath('data.shift', SchoolShift::Morning->value)
            ->assertJsonPath('data.is_active', true);

        $schoolClassId = $createResponse->json('data.id');

        $this->actingAs($administrator)
            ->getJson('/api/admin/school-classes?search=5º&shift=morning&is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $schoolClassId);

        $this->actingAs($administrator)
            ->putJson("/api/admin/school-classes/{$schoolClassId}", [
                'name' => '5º Ano B',
                'shift' => SchoolShift::Afternoon->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', '5º Ano B')
            ->assertJsonPath('data.shift', SchoolShift::Afternoon->value);

        $this->actingAs($administrator)
            ->patchJson("/api/admin/school-classes/{$schoolClassId}/status", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClassId,
            'name' => '5º Ano B',
            'shift' => SchoolShift::Afternoon->value,
            'is_active' => false,
        ]);
    }

    public function test_same_class_name_cannot_repeat_in_same_shift_ignoring_case(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();

        SchoolClass::factory()->create([
            'name' => '6º Ano A',
            'shift' => SchoolShift::Morning,
        ]);

        $this->actingAs($administrator)
            ->postJson('/api/admin/school-classes', [
                'name' => '6º ANO A',
                'shift' => SchoolShift::Morning->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($administrator)
            ->postJson('/api/admin/school-classes', [
                'name' => '6º Ano A',
                'shift' => SchoolShift::Afternoon->value,
            ])
            ->assertCreated();
    }

    public function test_class_created_by_administrator_can_be_used_to_register_a_student(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($administrator)
            ->postJson('/api/admin/school-classes', [
                'name' => '7º Ano A',
                'shift' => SchoolShift::Morning->value,
            ])
            ->assertCreated();

        $schoolClassId = $response->json('data.id');

        $this->actingAs($administrator)
            ->getJson('/api/school-classes/options')
            ->assertOk()
            ->assertJsonPath('data.0.id', $schoolClassId);

        $this->actingAs($administrator)
            ->postJson('/api/students', [
                'student' => [
                    'full_name' => 'Aluno de Teste',
                    'enrollment_number' => 'MAT-2026-001',
                    'date_of_birth' => '2015-01-01',
                    'school_class_id' => $schoolClassId,
                ],
                'guardian' => ['mode' => 'existing', 'id' => $guardian->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.school_class.id', $schoolClassId);

        $this->assertDatabaseHas('students', [
            'enrollment_number' => 'MAT-2026-001',
            'school_class_id' => $schoolClassId,
            'guardian_user_id' => $guardian->id,
        ]);
    }

    #[DataProvider('studentOptionPermissions')]
    public function test_employee_with_student_permission_can_list_only_active_class_options(string $permissionKey): void
    {
        $permission = Permission::factory()->create([
            'key' => $permissionKey,
        ]);
        $employee = User::factory()->create();
        $employee->permissions()->attach($permission);

        $activeClass = SchoolClass::factory()->create([
            'name' => 'Turma Ativa',
            'is_active' => true,
        ]);
        SchoolClass::factory()->create([
            'name' => 'Turma Inativa',
            'is_active' => false,
        ]);

        $this->actingAs($employee)
            ->getJson('/api/school-classes/options')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeClass->id);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function studentOptionPermissions(): array
    {
        return [
            'view students' => ['students.view'],
            'create students' => ['students.create'],
            'update students' => ['students.update'],
        ];
    }

    public function test_deactivating_class_preserves_existing_student_link_and_status(): void
    {
        $administrator = User::factory()->centralAdministrator()->create();
        $schoolClass = SchoolClass::factory()->create();
        $student = Student::factory()->create(['school_class_id' => $schoolClass->id]);

        $this->actingAs($administrator)
            ->patchJson("/api/admin/school-classes/{$schoolClass->id}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->getJson('/api/school-classes/options')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_employee_without_permission_and_guardian_cannot_list_class_options(): void
    {
        $employee = User::factory()->create();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($employee)
            ->getJson('/api/school-classes/options')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');

        $this->actingAs($guardian)
            ->getJson('/api/school-classes/options')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');
    }

    public function test_employee_cannot_access_central_administration_of_classes(): void
    {
        $permission = Permission::factory()->create([
            'key' => 'students.create',
        ]);
        $employee = User::factory()->create();
        $employee->permissions()->attach($permission);
        $schoolClass = SchoolClass::factory()->create();

        $this->actingAs($employee)
            ->getJson('/api/admin/school-classes')
            ->assertForbidden()
            ->assertJsonPath('code', 'central_administrator_required');

        $this->actingAs($employee)
            ->postJson('/api/admin/school-classes', [
                'name' => '7º Ano A',
                'shift' => SchoolShift::Morning->value,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'central_administrator_required');

        $this->actingAs($employee)
            ->putJson("/api/admin/school-classes/{$schoolClass->id}", [
                'name' => '7º Ano B',
                'shift' => SchoolShift::Afternoon->value,
            ])
            ->assertForbidden();

        $this->actingAs($employee)
            ->patchJson("/api/admin/school-classes/{$schoolClass->id}/status", ['is_active' => false])
            ->assertForbidden();
    }
}
