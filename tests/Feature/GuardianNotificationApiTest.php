<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuardianNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_list_own_notifications_and_mark_them_as_read(): void
    {
        $guardian = User::factory()->guardian()->create();
        $otherGuardian = User::factory()->guardian()->create();

        $notification = $guardian->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\StudentAccessNotification',
            'data' => [
                'kind' => 'student_access',
                'student_name' => 'Aluno do responsável',
                'title' => 'Entrada registrada',
                'message' => 'registrou entrada na escola.',
                'occurred_at' => '30/10/2026 às 08:00',
            ],
        ]);

        $otherGuardian->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\StudentAccessNotification',
            'data' => ['title' => 'Aviso de outro responsável'],
        ]);

        $this->actingAs($guardian)
            ->getJson('/api/guardian/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.data.student_name', 'Aluno do responsável')
            ->assertJsonPath('meta.unread_count', 1);

        $this->actingAs($guardian)
            ->patchJson("/api/guardian/notifications/{$notification->id}/read")
            ->assertOk();

        $this->actingAs($guardian)
            ->getJson('/api/guardian/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_guardian_cannot_mark_another_guardians_notification_as_read(): void
    {
        $guardian = User::factory()->guardian()->create();
        $otherGuardian = User::factory()->guardian()->create();
        $notification = $otherGuardian->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\StudentAccessNotification',
            'data' => ['title' => 'Aviso privado'],
        ]);

        $this->actingAs($guardian)
            ->patchJson("/api/guardian/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
