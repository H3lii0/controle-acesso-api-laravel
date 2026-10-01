<?php

namespace Tests\Feature;

use App\Enums\SchoolShift;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_central_administrator_can_read_real_dashboard_summary(): void
    {
        config()->set('school.timezone', 'America/Recife');
        Carbon::setTestNow(Carbon::parse('2026-10-01T10:00:00-03:00'));
        $administrator = User::factory()->centralAdministrator()->create();
        $morningClass = SchoolClass::factory()->create(['shift' => SchoolShift::Morning]);
        $afternoonClass = SchoolClass::factory()->create(['shift' => SchoolShift::Afternoon]);
        $insideStudent = Student::factory()->create(['school_class_id' => $morningClass->id]);
        $completedStudent = Student::factory()->create(['school_class_id' => $morningClass->id]);
        Student::factory()->create(['school_class_id' => $afternoonClass->id]);

        StudentAccessRecord::factory()->create([
            'student_id' => $insideStudent->id,
            'access_date' => '2026-10-01',
            'entered_at' => Carbon::parse('2026-10-01T07:00:00-03:00'),
        ]);
        StudentAccessRecord::factory()->create([
            'student_id' => $completedStudent->id,
            'access_date' => '2026-10-01',
            'entered_at' => Carbon::parse('2026-10-01T07:10:00-03:00'),
            'exited_at' => Carbon::parse('2026-10-01T12:00:00-03:00'),
        ]);

        $this->actingAs($administrator)
            ->getJson("/api/dashboard/summary?date=2026-10-01&school_class_id={$morningClass->id}")
            ->assertOk()
            ->assertJsonPath('data.students.total', 2)
            ->assertJsonPath('data.students.inside', 1)
            ->assertJsonPath('data.students.without_access', 0)
            ->assertJsonPath('data.accesses.readings', 3)
            ->assertJsonPath('data.accesses.entries', 2)
            ->assertJsonPath('data.accesses.exits', 1)
            ->assertJsonPath('data.delays', null)
            ->assertJsonPath('data.denied', null)
            ->assertJsonPath('data.flow.4.entries', 2)
            ->assertJsonPath('data.flow.9.exits', 1)
            ->assertJsonPath('data.recent_events.0.movement', 'Saída');
    }

    public function test_employee_requires_dashboard_permission(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->getJson('/api/dashboard/summary')
            ->assertForbidden()
            ->assertJsonPath('code', 'permission_required');
    }
}
