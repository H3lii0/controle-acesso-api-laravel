<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\SchoolShift;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_contains_only_the_restructured_domain_tables(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'full_name',
            'email',
            'phone',
            'password',
            'account_type',
            'account_status',
            'email_verified_at',
        ]));
        $this->assertTrue(Schema::hasTable('account_activation_tokens'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('user_permission'));
        $this->assertTrue(Schema::hasTable('school_classes'));
        $this->assertTrue(Schema::hasTable('students'));
        $this->assertTrue(Schema::hasTable('student_access_records'));

        $this->assertFalse(Schema::hasTable('escolas'));
        $this->assertFalse(Schema::hasTable('funcionarios'));
        $this->assertFalse(Schema::hasTable('perfis'));
        $this->assertFalse(Schema::hasTable('unidades_escolares'));
        $this->assertFalse(Schema::hasTable('periodos_letivos'));
        $this->assertFalse(Schema::hasTable('turnos_escolares'));
        $this->assertFalse(Schema::hasTable('horarios_turma'));
    }

    public function test_a_guardian_can_be_linked_to_more_than_one_student(): void
    {
        $guardian = User::factory()->guardian()->create();
        $schoolClass = SchoolClass::factory()->create();

        Student::factory()->count(2)->create([
            'guardian_user_id' => $guardian->id,
            'school_class_id' => $schoolClass->id,
        ]);

        $this->assertCount(2, $guardian->guardedStudents);
        $this->assertSame(AccountType::Guardian, $guardian->account_type);
        $this->assertSame(AccountStatus::Active, $guardian->account_status);
    }

    public function test_enrollment_number_is_normalized_before_it_is_saved(): void
    {
        $student = Student::factory()->create([
            'enrollment_number' => '  mat-001/a  ',
        ]);

        $this->assertSame('MAT-001/A', $student->enrollment_number);
    }

    public function test_a_student_can_have_only_one_access_record_per_day(): void
    {
        $student = Student::factory()->create();

        StudentAccessRecord::factory()->create([
            'student_id' => $student->id,
            'access_date' => '2026-09-28',
        ]);

        $this->expectException(QueryException::class);

        StudentAccessRecord::factory()->create([
            'student_id' => $student->id,
            'access_date' => '2026-09-28',
        ]);
    }

    public function test_class_name_is_unique_per_shift_ignoring_case(): void
    {
        SchoolClass::factory()->create([
            'name' => '7º Ano B',
            'shift' => SchoolShift::Morning,
        ]);

        $this->expectException(QueryException::class);

        SchoolClass::factory()->create([
            'name' => '7º ano b',
            'shift' => SchoolShift::Morning,
        ]);
    }
}
