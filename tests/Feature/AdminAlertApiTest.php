<?php

namespace Tests\Feature;

use App\Enums\AccessDenialReason;
use App\Enums\SchoolShift;
use App\Models\AccessDeniedAttempt;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccessRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAlertApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_central_administrator_can_list_success_and_denied_alerts(): void
    {
        config()->set('school.timezone', 'America/Recife');
        Carbon::setTestNow(Carbon::parse('2026-10-30T12:00:00-03:00'));
        $administrator = User::factory()->centralAdministrator()->create();
        $schoolClass = SchoolClass::factory()->create(['shift' => SchoolShift::Morning]);
        $student = Student::factory()->create(['school_class_id' => $schoolClass->id]);

        StudentAccessRecord::factory()->create([
            'student_id' => $student->id,
            'access_date' => '2026-10-30',
            'entered_at' => Carbon::parse('2026-10-30T07:00:00-03:00'),
            'exited_at' => Carbon::parse('2026-10-30T12:00:00-03:00'),
        ]);
        AccessDeniedAttempt::query()->create([
            'student_id' => $student->id,
            'reason' => AccessDenialReason::DailyAccessCompleted,
            'identifier' => $student->enrollment_number,
            'attempted_at' => Carbon::parse('2026-10-30T12:05:00-03:00'),
        ]);

        $this->actingAs($administrator)
            ->getJson('/api/admin/alerts?type=all')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'denied')
            ->assertJsonPath('data.0.title', 'Acesso negado');
    }

    public function test_alerts_can_be_filtered_by_type(): void
    {
        config()->set('school.timezone', 'America/Recife');
        Carbon::setTestNow(Carbon::parse('2026-10-30T12:00:00-03:00'));
        $administrator = User::factory()->centralAdministrator()->create();
        $student = Student::factory()->create();

        StudentAccessRecord::factory()->create([
            'student_id' => $student->id,
            'access_date' => '2026-10-30',
            'entered_at' => Carbon::parse('2026-10-30T07:00:00-03:00'),
        ]);
        AccessDeniedAttempt::query()->create([
            'student_id' => $student->id,
            'reason' => AccessDenialReason::StudentInactive,
            'identifier' => $student->enrollment_number,
            'attempted_at' => Carbon::parse('2026-10-30T08:00:00-03:00'),
        ]);

        $this->actingAs($administrator)
            ->getJson('/api/admin/alerts?type=success')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'success');

        $this->actingAs($administrator)
            ->getJson('/api/admin/alerts?type=denied')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'denied');
    }

    public function test_alerts_are_restricted_to_central_administrator(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->getJson('/api/admin/alerts')
            ->assertForbidden()
            ->assertJsonPath('code', 'central_administrator_required');
    }
}
