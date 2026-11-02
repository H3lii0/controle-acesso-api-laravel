<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prune_removes_only_read_notifications_older_than_seven_days(): void
    {
        $guardian = User::factory()->guardian()->create();
        Carbon::setTestNow(Carbon::parse('2026-10-30 03:00:00'));

        $oldRead = $guardian->notifications()->create($this->notificationData([
            'read_at' => Carbon::now()->subDays(8),
            'created_at' => Carbon::now()->subDays(8),
        ]));
        $oldUnread = $guardian->notifications()->create($this->notificationData([
            'created_at' => Carbon::now()->subDays(8),
        ]));
        $recentRead = $guardian->notifications()->create($this->notificationData([
            'read_at' => Carbon::now()->subDays(6),
            'created_at' => Carbon::now()->subDays(6),
        ]));

        Artisan::call('notifications:prune');

        $this->assertDatabaseMissing('notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $oldUnread->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead->id]);
    }

    /** @return array<string, mixed> */
    private function notificationData(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\StudentAccessNotification',
            'data' => ['title' => 'Entrada registrada'],
        ], $overrides);
    }
}
