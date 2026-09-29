<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuardianAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_lists_only_own_students_and_reads_their_access_records(): void
    {
        $guardian = User::factory()->guardian()->create();
        $otherGuardian = User::factory()->guardian()->create();
        $firstStudent = Student::factory()->create([
            'full_name' => 'Primeiro Filho',
            'guardian_user_id' => $guardian->id,
        ]);
        $secondStudent = Student::factory()->create([
            'full_name' => 'Segundo Filho',
            'guardian_user_id' => $guardian->id,
        ]);
        Student::factory()->create([
            'full_name' => 'Aluno de Outro Responsável',
            'guardian_user_id' => $otherGuardian->id,
        ]);

        StudentAccessRecord::factory()->create([
            'student_id' => $firstStudent->id,
            'access_date' => '2026-09-28',
            'entered_at' => Carbon::parse('2026-09-28T07:00:00-03:00'),
        ]);
        $expectedRecord = StudentAccessRecord::factory()->completed()->create([
            'student_id' => $firstStudent->id,
            'access_date' => '2026-09-29',
            'entered_at' => Carbon::parse('2026-09-29T07:00:00-03:00'),
            'exited_at' => Carbon::parse('2026-09-29T12:00:00-03:00'),
        ]);
        StudentAccessRecord::factory()->create([
            'student_id' => $secondStudent->id,
            'access_date' => '2026-09-29',
        ]);

        $this->actingAs($guardian)
            ->getJson('/api/guardian/students')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstStudent->id)
            ->assertJsonPath('data.1.id', $secondStudent->id);

        $this->actingAs($guardian)
            ->getJson("/api/guardian/students/{$firstStudent->id}/access-records?date_from=2026-09-29&date_to=2026-09-29")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expectedRecord->id)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.student.id', $firstStudent->id);
    }

    public function test_guardian_cannot_read_access_records_of_another_guardians_student(): void
    {
        $guardian = User::factory()->guardian()->create();
        $otherStudent = Student::factory()->create();

        $this->actingAs($guardian)
            ->getJson("/api/guardian/students/{$otherStudent->id}/access-records")
            ->assertNotFound()
            ->assertJsonPath('code', 'student_not_found');
    }

    public function test_employee_and_inactive_guardian_cannot_access_guardian_area(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->getJson('/api/guardian/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'guardian_account_required');

        $disabledGuardian = User::factory()->guardian()->disabled()->create();

        $this->actingAs($disabledGuardian)
            ->getJson('/api/guardian/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'account_disabled');
    }
}
