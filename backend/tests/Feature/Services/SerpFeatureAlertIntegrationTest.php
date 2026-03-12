<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\AlertSettings;
use App\Models\SiteNotificationSettings;
use App\Models\Notification;
use App\Services\SerpFeatureChangeTracker;
use App\Services\SerpFeatureAlertService;
use App\Repositories\SerpFeatureHistoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SerpFeatureAlertIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected Keyword $keyword;
    protected SerpFeatureChangeTracker $tracker;
    protected SerpFeatureAlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->site = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
            'name' => 'Test Site',
        ]);

        $this->keyword = Keyword::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
        ]);

        // Create default alert settings with SERP features enabled
        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
            'serp_feature_alerts_enabled' => true,
        ]);

        $this->tracker = app(SerpFeatureChangeTracker::class);
        $this->alertService = app(SerpFeatureAlertService::class);
    }

    // ==================== Event Emission ====================

    public function test_event_emitted_on_feature_gained(): void
    {
        $events = [];
        Event::listen('serp-feature.gained', function (...$eventData) use (&$events) {
            // Laravel passes array items as individual arguments
            // Reconstruct the event array
            $events[] = is_array($eventData[0] ?? null) ? $eventData[0] : $eventData;
        });

        $changes = $this->tracker->trackChanges(
            $this->keyword->id,
            [
                ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/page'],
            ],
            [
                ['type' => 'featured_snippet', 'is_present' => false, 'url' => null],
            ]
        );

        $this->assertCount(1, $changes);
        $this->assertEquals('gained', $changes[0]['action']);
        $this->assertCount(1, $events, 'Event should be emitted');
        $this->assertEquals('featured_snippet', $events[0]['feature_type']);
    }

    public function test_event_emitted_on_feature_lost(): void
    {
        $events = [];
        Event::listen('serp-feature.lost', function (...$eventData) use (&$events) {
            // Laravel passes array items as individual arguments
            // Reconstruct the event array
            $events[] = is_array($eventData[0] ?? null) ? $eventData[0] : $eventData;
        });

        $changes = $this->tracker->trackChanges(
            $this->keyword->id,
            [
                ['type' => 'featured_snippet', 'is_present' => false, 'url' => null],
            ],
            [
                ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/page'],
            ]
        );

        $this->assertCount(1, $changes);
        $this->assertEquals('lost', $changes[0]['action']);
        $this->assertCount(1, $events, 'Event should be emitted');
        $this->assertEquals('featured_snippet', $events[0]['feature_type']);
    }

    public function test_event_contains_correct_data(): void
    {
        $capturedEvent = null;
        Event::listen('serp-feature.gained', function (...$eventData) use (&$capturedEvent) {
            // Laravel passes array items as individual arguments
            // Reconstruct the event array
            $capturedEvent = is_array($eventData[0] ?? null) ? $eventData[0] : $eventData;
        });

        $this->tracker->trackChanges(
            $this->keyword->id,
            [
                ['type' => 'local_pack', 'is_present' => true, 'url' => 'https://example.com/location'],
            ],
            [
                ['type' => 'local_pack', 'is_present' => false, 'url' => null],
            ]
        );

        $this->assertNotNull($capturedEvent);
        $this->assertEquals($this->keyword->id, $capturedEvent['keyword_id']);
        $this->assertEquals('local_pack', $capturedEvent['feature_type']);
        $this->assertEquals('gained', $capturedEvent['action']);
        $this->assertNotNull($capturedEvent['detected_at']);
    }

    // ==================== End-to-End Flow ====================

    public function test_full_flow_feature_gained_creates_notification(): void
    {
        Mail::fake();

        // Manually trigger the event flow
        $event = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://example.com/page',
            'previous_url' => null,
            'detected_at' => now(),
        ];

        $result = $this->alertService->processChangeEvent($event);

        $this->assertNotNull($result);
        $this->assertEquals('serp_feature_gained', $result['type']);
        $this->assertTrue($result['delivery']['delivered']);

        // Verify in-app notification
        $notification = Notification::where('user_id', $this->user->id)
            ->where('type', 'serp_feature_gained')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('featured_snippet', $notification->data['feature_type']);
        $this->assertEquals('seo tools', $notification->data['keyword']);
    }

    public function test_full_flow_feature_lost_creates_notification(): void
    {
        Mail::fake();

        $event = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'current_url' => null,
            'previous_url' => 'https://example.com/location',
            'detected_at' => now(),
        ];

        $result = $this->alertService->processChangeEvent($event);

        $this->assertNotNull($result);
        $this->assertEquals('serp_feature_lost', $result['type']);

        // Verify in-app notification
        $notification = Notification::where('user_id', $this->user->id)
            ->where('type', 'serp_feature_lost')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('local_pack', $notification->data['feature_type']);
        $this->assertEquals('https://example.com/location', $notification->data['previous_url']);
    }

    // ==================== Multiple Features ====================

    public function test_multiple_features_same_keyword(): void
    {
        Mail::fake();

        // Trigger two different feature changes
        $event1 = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now(),
        ];

        $event2 = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'image_pack',
            'action' => 'gained',
            'detected_at' => now(),
        ];

        $this->alertService->processChangeEvent($event1);
        $this->alertService->processChangeEvent($event2);

        // Should create two separate notifications
        $notifications = Notification::where('user_id', $this->user->id)
            ->whereIn('type', ['serp_feature_gained', 'serp_feature_lost'])
            ->get();

        $this->assertCount(2, $notifications);

        $featureTypes = $notifications->pluck('data.feature_type')->toArray();
        $this->assertContains('featured_snippet', $featureTypes);
        $this->assertContains('image_pack', $featureTypes);
    }

    // ==================== User Preference Integration ====================

    public function test_alert_respects_user_serp_feature_preference(): void
    {
        // Enable only featured_snippet alerts
        AlertSettings::where('user_id', $this->user->id)->update([
            'serp_feature_types' => ['featured_snippet'],
        ]);

        // Featured snippet should work
        $result1 = $this->alertService->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now(),
        ]);
        $this->assertNotNull($result1);

        // Clear cache for next test
        \Illuminate\Support\Facades\Cache::flush();

        // Local pack should be filtered out
        $result2 = $this->alertService->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'detected_at' => now(),
        ]);
        $this->assertNull($result2);
    }

    // ==================== Site Settings Integration ====================

    public function test_alert_respects_site_notification_settings(): void
    {
        SiteNotificationSettings::create([
            'site_id' => $this->site->id,
            'email_notifications' => false,
            'in_app_notifications' => true,
            'serp_feature_alerts' => true,
        ]);

        $result = $this->alertService->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now(),
        ]);

        $this->assertNotNull($result);
        $this->assertTrue($result['delivery']['channels']['in_app']['sent']);
        $this->assertArrayNotHasKey('email', $result['delivery']['channels']);
    }

    public function test_site_serp_alerts_disabled(): void
    {
        SiteNotificationSettings::create([
            'site_id' => $this->site->id,
            'email_notifications' => true,
            'in_app_notifications' => true,
            'serp_feature_alerts' => false,
        ]);

        $result = $this->alertService->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now(),
        ]);

        $this->assertNull($result);
    }

    // ==================== Error Handling ====================

    public function test_invalid_keyword_id_handled_gracefully(): void
    {
        $result = $this->alertService->processChangeEvent([
            'keyword_id' => 99999,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now(),
        ]);

        $this->assertNull($result);
    }

    public function test_missing_event_data_handled_gracefully(): void
    {
        $result = $this->alertService->processChangeEvent([]);

        $this->assertNull($result);
    }

    // ==================== Notification API Response ====================

    public function test_notification_format_for_api(): void
    {
        $this->alertService->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://example.com/page',
            'detected_at' => now(),
        ]);

        $notification = Notification::first();
        $apiResponse = $notification->toApiResponse();

        $this->assertEquals('serp_feature_gained', $apiResponse['type']);
        $this->assertArrayHasKey('keyword', $apiResponse['data']);
        $this->assertArrayHasKey('feature_type', $apiResponse['data']);
        $this->assertArrayHasKey('feature_name', $apiResponse['data']);
        $this->assertArrayHasKey('short', $apiResponse['data']);
    }
}
