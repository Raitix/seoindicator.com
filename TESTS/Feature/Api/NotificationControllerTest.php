<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\Keyword;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
    }

    // ==================== GET Endpoint ====================

    /** @test */
    public function list_all_notifications(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'keyword_id' => $this->keyword->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'keyword_id', 'data', 'read_at', 'created_at'],
                ],
                'meta' => ['unread_count'],
            ]);
    }

    /** @test */
    public function filter_unread_only(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?unread_only=true');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function include_unread_count(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJson(['meta' => ['unread_count' => 3]]);
    }

    /** @test */
    public function pagination_works(): void
    {
        Notification::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    /** @test */
    public function only_own_notifications_returned(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==================== PATCH Endpoint (Mark Read) ====================

    /** @test */
    public function mark_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function already_read_still_success(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();
    }

    /** @test */
    public function other_users_notification_returns_403(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    /** @test */
    public function non_existent_notification_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/notifications/99999/read');

        $response->assertNotFound();
    }

    /** @test */
    public function mark_read_unauthenticated_returns_401(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertUnauthorized();
    }

    // ==================== POST Endpoint (Mark All Read) ====================

    /** @test */
    public function mark_all_as_read(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    /** @test */
    public function mark_all_no_notifications_success(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function mark_all_mixed_read_unread(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        $response->assertOk();

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    /** @test */
    public function mark_all_only_affects_own_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        // Other user's notifications should still be unread
        $this->assertEquals(2, Notification::where('user_id', $this->otherUser->id)->whereNull('read_at')->count());
    }

    /** @test */
    public function mark_all_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/notifications/read-all');

        $response->assertUnauthorized();
    }
}
