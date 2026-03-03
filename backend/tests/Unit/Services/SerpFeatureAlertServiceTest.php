<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\AlertSettings;
use App\Models\SiteNotificationSettings;
use App\Models\Notification;
use App\Models\SerpFeature;
use App\Services\SerpFeatureAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SerpFeatureAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SerpFeatureAlertService $service;
    protected User $user;
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new SerpFeatureAlertService();

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
    }

    // ==================== Alert Types ====================

    public function test_alert_types_defined(): void
    {
        $types = SerpFeatureAlertService::getAlertTypes();

        $this->assertContains('serp_feature_gained', $types);
        $this->assertContains('serp_feature_lost', $types);
    }

    // ==================== Alert Templates ====================

    public function test_templates_defined(): void
    {
        $templates = SerpFeatureAlertService::getTemplates();

        $this->assertArrayHasKey('serp_feature_gained', $templates);
        $this->assertArrayHasKey('serp_feature_lost', $templates);

        $this->assertArrayHasKey('subject', $templates['serp_feature_gained']);
        $this->assertArrayHasKey('body', $templates['serp_feature_gained']);
        $this->assertArrayHasKey('short', $templates['serp_feature_gained']);
    }

    public function test_template_contains_required_variables(): void
    {
        $templates = SerpFeatureAlertService::getTemplates();

        // Gained template should have feature name and keyword
        $this->assertStringContainsString('{{featureName}}', $templates['serp_feature_gained']['subject']);
        $this->assertStringContainsString('{{keyword}}', $templates['serp_feature_gained']['body']);

        // Lost template should have feature name and keyword
        $this->assertStringContainsString('{{featureName}}', $templates['serp_feature_lost']['subject']);
        $this->assertStringContainsString('{{keyword}}', $templates['serp_feature_lost']['body']);
    }

    // ==================== Alert Generation ====================

    public function test_generate_alert_featured_snippet_gained(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained',
            ['url' => 'https://example.com/seo-tools', 'detected_at' => now()]
        );

        $this->assertNotNull($alert);
        $this->assertEquals('serp_feature_gained', $alert['type']);
        $this->assertEquals($this->keyword->id, $alert['keyword_id']);
        $this->assertEquals('featured_snippet', $alert['feature_type']);
        $this->assertEquals('Featured Snippet', $alert['feature_name']);
        $this->assertStringContainsString('seo tools', $alert['subject']);
        $this->assertStringContainsString('Featured Snippet', $alert['subject']);
    }

    public function test_generate_alert_local_pack_lost(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'local_pack',
            'lost',
            ['previous_url' => 'https://example.com/location', 'detected_at' => now()]
        );

        $this->assertNotNull($alert);
        $this->assertEquals('serp_feature_lost', $alert['type']);
        $this->assertEquals('local_pack', $alert['feature_type']);
        $this->assertEquals('Local Pack', $alert['feature_name']);
        $this->assertStringContainsString('Lost', $alert['subject']);
        $this->assertStringContainsString('Local Pack', $alert['subject']);
    }

    public function test_generate_alert_includes_ctr_impact(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNotNull($alert);
        $this->assertEquals('+30-50%', $alert['ctr_impact']);
        $this->assertStringContainsString('+30-50%', $alert['body']);
    }

    public function test_generate_alert_includes_keyword_name(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNotNull($alert);
        $this->assertEquals('seo tools', $alert['keyword']);
        $this->assertStringContainsString('seo tools', $alert['subject']);
    }

    public function test_generate_alert_includes_timestamp(): void
    {
        $detectedAt = now();
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained',
            ['detected_at' => $detectedAt]
        );

        $this->assertNotNull($alert);
        $this->assertEquals($detectedAt, $alert['detected_at']);
    }

    // ==================== User Preferences ====================

    public function test_generate_alert_when_serp_alerts_disabled(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'serp_feature_alerts_enabled' => false,
        ]);

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNull($alert);
    }

    public function test_generate_alert_when_global_alerts_disabled(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'global_enabled' => false,
        ]);

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNull($alert);
    }

    public function test_generate_alert_specific_feature_type_enabled(): void
    {
        // Enable only featured_snippet alerts
        AlertSettings::where('user_id', $this->user->id)->update([
            'serp_feature_types' => ['featured_snippet'],
        ]);

        // Should generate for featured_snippet
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );
        $this->assertNotNull($alert);

        // Clear dedupe cache
        Cache::flush();

        // Should NOT generate for local_pack
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'local_pack',
            'gained'
        );
        $this->assertNull($alert);
    }

    // ==================== Site Notification Settings ====================

    public function test_generate_alert_respects_site_settings(): void
    {
        SiteNotificationSettings::create([
            'site_id' => $this->site->id,
            'email_notifications' => true,
            'in_app_notifications' => true,
            'serp_feature_alerts' => false,
        ]);

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNull($alert);
    }

    public function test_generate_alert_when_site_serp_alerts_enabled(): void
    {
        SiteNotificationSettings::create([
            'site_id' => $this->site->id,
            'email_notifications' => true,
            'in_app_notifications' => true,
            'serp_feature_alerts' => true,
        ]);

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNotNull($alert);
    }

    // ==================== Deduplication ====================

    public function test_deduplication_same_feature_gained_quickly(): void
    {
        // First alert should generate
        $alert1 = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );
        $this->assertNotNull($alert1);

        // Second alert within dedupe window should be suppressed
        $alert2 = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );
        $this->assertNull($alert2);
    }

    public function test_deduplication_different_features_generate_separate_alerts(): void
    {
        $alert1 = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );
        $this->assertNotNull($alert1);

        // Different feature type should generate a separate alert
        $alert2 = $this->service->generateAlert(
            $this->keyword->id,
            'local_pack',
            'gained'
        );
        $this->assertNotNull($alert2);
        $this->assertEquals('local_pack', $alert2['feature_type']);
    }

    public function test_deduplication_gained_and_lost_generate_separate_alerts(): void
    {
        $alert1 = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );
        $this->assertNotNull($alert1);
        $this->assertEquals('serp_feature_gained', $alert1['type']);

        // Lost event for same feature should generate separate alert
        $alert2 = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'lost'
        );
        $this->assertNotNull($alert2);
        $this->assertEquals('serp_feature_lost', $alert2['type']);
    }

    // ==================== Invalid Inputs ====================

    public function test_generate_alert_invalid_action(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'invalid_action'
        );

        $this->assertNull($alert);
    }

    public function test_generate_alert_invalid_feature_type(): void
    {
        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'invalid_feature',
            'gained'
        );

        $this->assertNull($alert);
    }

    public function test_generate_alert_keyword_not_found(): void
    {
        $alert = $this->service->generateAlert(
            99999,
            'featured_snippet',
            'gained'
        );

        $this->assertNull($alert);
    }

    // ==================== Delivery ====================

    public function test_deliver_alert_creates_in_app_notification(): void
    {
        Mail::fake();

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $this->assertNotNull($alert);

        $result = $this->service->deliverAlert($alert);

        $this->assertTrue($result['delivered']);
        $this->assertTrue($result['channels']['in_app']['sent']);

        // Verify notification was created
        $notification = Notification::where('user_id', $this->user->id)
            ->where('type', 'serp_feature_gained')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals($this->keyword->id, $notification->keyword_id);
        $this->assertEquals('featured_snippet', $notification->data['feature_type']);
    }

    public function test_deliver_alert_respects_site_email_disabled(): void
    {
        Mail::fake();

        SiteNotificationSettings::create([
            'site_id' => $this->site->id,
            'email_notifications' => false,
            'in_app_notifications' => true,
            'serp_feature_alerts' => true,
        ]);

        $alert = $this->service->generateAlert(
            $this->keyword->id,
            'featured_snippet',
            'gained'
        );

        $result = $this->service->deliverAlert($alert);

        $this->assertArrayNotHasKey('email', $result['channels']);
        $this->assertTrue($result['channels']['in_app']['sent']);
    }

    // ==================== Process Change Event ====================

    public function test_process_change_event_gained(): void
    {
        Mail::fake();

        $result = $this->service->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://example.com/page',
            'detected_at' => now(),
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('serp_feature_gained', $result['type']);
        $this->assertTrue($result['delivery']['delivered']);
    }

    public function test_process_change_event_lost(): void
    {
        Mail::fake();

        $result = $this->service->processChangeEvent([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://example.com/location',
            'detected_at' => now(),
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('serp_feature_lost', $result['type']);
        $this->assertArrayHasKey('delivery', $result);
    }

    public function test_process_change_event_missing_data(): void
    {
        $result = $this->service->processChangeEvent([
            'keyword_id' => null,
            'feature_type' => null,
            'action' => null,
        ]);

        $this->assertNull($result);
    }

    // ==================== All SERP Feature Types ====================

    public function test_generate_alert_all_feature_types(): void
    {
        foreach (SerpFeature::FEATURE_TYPES as $featureType) {
            Cache::flush(); // Clear dedupe between each test

            $alert = $this->service->generateAlert(
                $this->keyword->id,
                $featureType,
                'gained'
            );

            $this->assertNotNull($alert, "Failed for feature type: {$featureType}");
            $this->assertEquals($featureType, $alert['feature_type']);
        }
    }
}
