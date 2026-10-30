<?php

namespace Tests\Unit\Services;

use App\Services\NotificationDispatcher;
use App\Mail\RankingAlertEmail;
use App\Models\Notification;
use App\Models\Keyword;
use App\Models\KeywordAlertSetting;
use App\Models\AlertSetting;
use App\Models\User;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationDispatcher $dispatcher;
    protected User $user;
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new NotificationDispatcher();
        $this->user = User::factory()->create(['email' => 'test@example.com']);
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
        ]);
        
        AlertSetting::factory()->create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
            'digest_mode' => 'instant',
        ]);
        
        KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'enabled' => true,
            'notification_channels' => ['email', 'in_app'],
        ]);
    }

    // ==================== Core Dispatch ====================

    /** @test */
    public function email_channel_enabled_queues_email(): void
    {
        Mail::fake();
        
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['notification_channels' => ['email']]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function in_app_channel_creates_notification(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['notification_channels' => ['in_app']]);
        
        $events = [['event' => 'position_drop', 'change' => 5, 'old_position' => 5, 'new_position' => 10]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'keyword_id' => $this->keyword->id,
            'type' => 'position_drop',
        ]);
    }

    /** @test */
    public function both_channels_enabled_dispatches_both(): void
    {
        Mail::fake();
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'position_drop',
        ]);
    }

    /** @test */
    public function no_channels_enabled_no_dispatch(): void
    {
        Mail::fake();
        
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['notification_channels' => []]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertNotQueued(RankingAlertEmail::class);
        $this->assertEquals(0, Notification::count());
    }

    /** @test */
    public function multiple_events_combined_in_notification(): void
    {
        $events = [
            ['event' => 'position_drop', 'change' => 5, 'old_position' => 5, 'new_position' => 10],
            ['event' => 'left_top_10', 'old_position' => 5, 'new_position' => 10],
        ];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $notification = Notification::first();
        $this->assertNotNull($notification);
        $this->assertCount(2, $notification->data['events']);
    }

    // ==================== Quiet Hours ====================

    /** @test */
    public function during_quiet_hours_email_delayed(): void
    {
        Mail::fake();
        
        // Set quiet hours to current time
        $now = now();
        AlertSetting::where('user_id', $this->user->id)->update([
            'quiet_hours_start' => $now->format('H:i:s'),
            'quiet_hours_end' => $now->addHours(8)->format('H:i:s'),
        ]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        // Email should NOT be sent immediately
        Mail::assertNotQueued(RankingAlertEmail::class);
        
        // But in-app should still be created
        $this->assertDatabaseHas('notifications', ['user_id' => $this->user->id]);
    }

    /** @test */
    public function outside_quiet_hours_both_immediate(): void
    {
        Mail::fake();
        
        // Set quiet hours to midnight-6am
        AlertSetting::where('user_id', $this->user->id)->update([
            'quiet_hours_start' => '00:00:00',
            'quiet_hours_end' => '06:00:00',
        ]);
        
        // Travel to noon
        $this->travelTo(now()->setTime(12, 0));
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->user->id]);
    }

    /** @test */
    public function no_quiet_hours_all_immediate(): void
    {
        Mail::fake();
        
        AlertSetting::where('user_id', $this->user->id)->update([
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class);
    }

    /** @test */
    public function quiet_hours_overnight_calculation(): void
    {
        // Quiet hours 22:00 to 07:00
        AlertSetting::where('user_id', $this->user->id)->update([
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '07:00:00',
        ]);
        
        // Test at 23:00 (should be quiet)
        $this->travelTo(now()->setTime(23, 0));
        $this->assertTrue($this->dispatcher->isQuietHours($this->user));
        
        // Test at 03:00 (should be quiet)
        $this->travelTo(now()->setTime(3, 0));
        $this->assertTrue($this->dispatcher->isQuietHours($this->user));
        
        // Test at 10:00 (should NOT be quiet)
        $this->travelTo(now()->setTime(10, 0));
        $this->assertFalse($this->dispatcher->isQuietHours($this->user));
    }

    // ==================== Digest Mode ====================

    /** @test */
    public function instant_mode_dispatches_immediately(): void
    {
        Mail::fake();
        
        AlertSetting::where('user_id', $this->user->id)->update([
            'digest_mode' => 'instant',
        ]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class);
    }

    /** @test */
    public function daily_mode_queues_for_batch(): void
    {
        Mail::fake();
        
        AlertSetting::where('user_id', $this->user->id)->update([
            'digest_mode' => 'daily',
        ]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        // Email should NOT be sent immediately in daily mode
        Mail::assertNotQueued(RankingAlertEmail::class);
        
        // But notification should be queued for digest
        $this->assertDatabaseHas('notification_queue', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function weekly_mode_queues_for_batch(): void
    {
        Mail::fake();
        
        AlertSetting::where('user_id', $this->user->id)->update([
            'digest_mode' => 'weekly',
        ]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertNotQueued(RankingAlertEmail::class);
    }

    // ==================== In-App Dispatch ====================

    /** @test */
    public function in_app_notification_has_correct_type(): void
    {
        $events = [['event' => 'position_gain', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $this->assertDatabaseHas('notifications', [
            'type' => 'position_gain',
        ]);
    }

    /** @test */
    public function in_app_notification_has_correct_user_id(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $notification = Notification::first();
        $this->assertEquals($this->user->id, $notification->user_id);
    }

    /** @test */
    public function in_app_notification_has_correct_keyword_id(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $notification = Notification::first();
        $this->assertEquals($this->keyword->id, $notification->keyword_id);
    }

    /** @test */
    public function in_app_notification_has_correct_data(): void
    {
        $events = [[
            'event' => 'position_drop',
            'change' => 5,
            'old_position' => 5,
            'new_position' => 10,
        ]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $notification = Notification::first();
        $this->assertEquals(5, $notification->data['change']);
        $this->assertEquals(5, $notification->data['old_position']);
        $this->assertEquals(10, $notification->data['new_position']);
    }

    /** @test */
    public function in_app_notification_is_unread(): void
    {
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        $notification = Notification::first();
        $this->assertNull($notification->read_at);
    }

    // ==================== Email Dispatch ====================

    /** @test */
    public function email_uses_correct_mailable(): void
    {
        Mail::fake();
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class);
    }

    /** @test */
    public function email_has_correct_recipient(): void
    {
        Mail::fake();
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertQueued(RankingAlertEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    // ==================== Logging ====================

    /** @test */
    public function successful_dispatch_is_logged(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Alert dispatched');
            });
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
    }

    // ==================== Edge Cases ====================

    /** @test */
    public function empty_events_array_no_dispatch(): void
    {
        Mail::fake();
        
        $this->dispatcher->dispatch($this->user, $this->keyword, []);
        
        Mail::assertNotQueued(RankingAlertEmail::class);
        $this->assertEquals(0, Notification::count());
    }

    /** @test */
    public function user_without_email_skips_email_channel(): void
    {
        Mail::fake();
        
        $this->user->update(['email' => null]);
        
        $events = [['event' => 'position_drop', 'change' => 5]];
        
        $this->dispatcher->dispatch($this->user, $this->keyword, $events);
        
        Mail::assertNotQueued(RankingAlertEmail::class);
        
        // In-app should still work
        $this->assertDatabaseHas('notifications', ['user_id' => $this->user->id]);
    }
}
