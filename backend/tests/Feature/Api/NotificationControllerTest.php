<?php

namespace Tests\Feature\Api;

use App\Models\Keyword;
use App\Models\Notification;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $site = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
            'name' => 'Test Site',
        ]);

        $this->keyword = Keyword::create([
            'user_id' => $this->user->id,
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);
    }

    protected function createNotification(array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'keyword_id' => $this->keyword->id,
            'data' => ['keyword' => 'test keyword', 'change' => 5],
        ], $overrides));
    }

    public function test_index_returns_notifications(): void
    {
        $this->createNotification();
        $this->createNotification(['type' => 'position_gain']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'keyword',
                        'data',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'unread_count',
                ],
            ]);
    }

    public function test_index_filters_unread_only(): void
    {
        $this->createNotification();
        $this->createNotification(['read_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications?unread_only=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_includes_unread_count(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification(['read_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'unread_count' => 2,
                ],
            ]);
    }

    public function test_index_paginates_results(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createNotification();
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(20, count($response->json('data')));
    }

    public function test_show_returns_notification(): void
    {
        $notification = $this->createNotification();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $notification->id,
                    'type' => 'position_drop',
                ],
            ]);
    }

    public function test_mark_as_read(): void
    {
        $notification = $this->createNotification();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'marked_count' => 3,
            ]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_destroy_notification(): void
    {
        $notification = $this->createNotification();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_unread_count(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification(['read_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson([
                'unread_count' => 2,
            ]);
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $notification = $this->createNotification();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_mark_as_read(): void
    {
        $notification = $this->createNotification();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete(): void
    {
        $notification = $this->createNotification();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertUnauthorized();
    }

    public function test_index_orders_by_created_at_desc(): void
    {
        $old = $this->createNotification();
        // Use direct property assignment to bypass mass assignment protection
        $old->created_at = now()->subDay();
        $old->save();

        $new = $this->createNotification(['type' => 'position_gain']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }

    public function test_notification_includes_keyword_details(): void
    {
        $notification = $this->createNotification();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'keyword' => [
                        'id' => $this->keyword->id,
                        'keyword' => 'test keyword',
                    ],
                ],
            ]);
    }

    public function test_notification_without_keyword(): void
    {
        $notification = $this->createNotification(['keyword_id' => null]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'keyword' => null,
                ],
            ]);
    }

    public function test_mark_as_read_idempotent(): void
    {
        $notification = $this->createNotification(['read_at' => now()]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_mark_all_as_read_only_affects_current_user(): void
    {
        $otherUser = User::factory()->create();
        Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'position_drop',
            'data' => [],
        ]);

        $this->createNotification();
        $this->createNotification();

        $this->actingAs($this->user)
            ->postJson('/api/v1/notifications/read-all');

        // Other user's notification should remain unread
        $this->assertEquals(1, Notification::where('user_id', $otherUser->id)->whereNull('read_at')->count());
    }
}
