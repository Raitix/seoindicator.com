<?php

namespace Tests\Unit\Services;

use App\Models\AlertSettings;
use App\Models\Keyword;
use App\Models\Notification;
use App\Models\Site;
use App\Models\User;
use App\Services\AlertEvaluator;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationDispatcher $dispatcher;
    protected AlertEvaluator $evaluator;
    protected User $user;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new NotificationDispatcher();
        $this->evaluator = new AlertEvaluator();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

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

        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
        ]);
    }

    public function test_dispatch_no_events(): void
    {
        $result = $this->dispatcher->dispatch($this->user, $this->keyword, []);

        $this->assertFalse($result['sent']);
        $this->assertEquals('no_events', $result['reason']);
    }

    public function test_dispatch_creates_in_app_notification(): void
    {
        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $this->assertTrue($result['sent']);
        $this->assertTrue($result['channels']['in_app']['sent']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'keyword_id' => $this->keyword->id,
        ]);
    }

    public function test_dispatch_queues_email(): void
    {
        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $this->assertTrue($result['sent']);
        $this->assertTrue($result['channels']['email']['sent']);

        Mail::assertQueued(\App\Mail\RankingAlertEmail::class);
    }

    public function test_dispatch_multiple_events_creates_multiple_notifications(): void
    {
        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
            ['event' => 'left_top_10', 'old_position' => 5, 'new_position' => 10],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $this->assertCount(2, $result['channels']['in_app']['notification_ids']);
        $this->assertEquals(2, Notification::where('user_id', $this->user->id)->count());
    }

    public function test_dispatch_respects_quiet_hours(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'quiet_hours_start' => '00:00:00',
            'quiet_hours_end' => '23:59:59',
        ]);

        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        // Email should be skipped due to quiet hours
        $this->assertFalse($result['channels']['email']['sent']);
        $this->assertEquals('quiet_hours', $result['channels']['email']['reason']);

        // In-app should still work
        $this->assertTrue($result['channels']['in_app']['sent']);
    }

    public function test_dispatch_respects_digest_mode(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'digest_mode' => 'daily',
        ]);

        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        // Email should be queued for digest
        $this->assertFalse($result['channels']['email']['sent']);
        $this->assertEquals('digest_mode', $result['channels']['email']['reason']);
        $this->assertEquals('daily', $result['channels']['email']['digest_mode']);

        // In-app should still work
        $this->assertTrue($result['channels']['in_app']['sent']);
    }

    public function test_get_unread_count(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'data' => [],
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_gain',
            'data' => [],
            'read_at' => now(),
        ]);

        $count = $this->dispatcher->getUnreadCount($this->user);

        $this->assertEquals(1, $count);
    }

    public function test_mark_all_as_read(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_drop',
            'data' => [],
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'position_gain',
            'data' => [],
        ]);

        $count = $this->dispatcher->markAllAsRead($this->user);

        $this->assertEquals(2, $count);
        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_notification_data_structure(): void
    {
        $events = [
            [
                'event' => 'position_drop',
                'old_position' => 5,
                'new_position' => 10,
                'change' => 5,
                'threshold' => 3,
            ],
        ];

        Mail::fake();

        $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $notification = Notification::first();

        $this->assertEquals('test keyword', $notification->data['keyword']);
        $this->assertEquals(5, $notification->data['old_position']);
        $this->assertEquals(10, $notification->data['new_position']);
        $this->assertEquals(5, $notification->data['change']);
        $this->assertEquals(3, $notification->data['threshold']);
    }

    public function test_serp_feature_notification_data(): void
    {
        $events = [
            [
                'event' => 'serp_feature_gained',
                'feature_type' => 'featured_snippet',
            ],
        ];

        Mail::fake();

        $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $notification = Notification::first();

        $this->assertEquals('featured_snippet', $notification->data['feature_type']);
        $this->assertEquals('serp_feature_gained', $notification->type);
    }

    public function test_dispatcher_handles_user_without_settings(): void
    {
        // Create a new user without alert settings
        $newUser = User::factory()->create();

        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
        ];

        Mail::fake();

        // Should create default settings and proceed
        $result = $this->dispatcher->dispatch($newUser, $this->keyword, $events);

        $this->assertTrue($result['sent']);
        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $newUser->id,
        ]);
    }

    public function test_events_count_in_result(): void
    {
        $events = [
            ['event' => 'position_drop', 'old_position' => 5, 'new_position' => 10, 'change' => 5],
            ['event' => 'left_top_10', 'old_position' => 5, 'new_position' => 10],
        ];

        Mail::fake();

        $result = $this->dispatcher->dispatch($this->user, $this->keyword, $events);

        $this->assertEquals(2, $result['events_count']);
    }
}
