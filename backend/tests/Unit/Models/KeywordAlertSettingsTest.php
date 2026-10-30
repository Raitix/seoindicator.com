<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\KeywordAlertSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordAlertSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    /** @test */
    public function it_can_create_keyword_alert_settings()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'custom_threshold' => 5,
            'alert_events' => ['position_drop', 'position_gain'],
            'notification_channels' => ['email'],
        ]);

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'custom_threshold' => 5,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_keyword()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
        ]);

        $this->assertEquals($keyword->id, $settings->keyword->id);
    }

    /** @test */
    public function it_has_defaults()
    {
        $defaults = KeywordAlertSettings::getDefaults();

        $this->assertTrue($defaults['enabled']);
        $this->assertNull($defaults['alert_events']);
        $this->assertNull($defaults['notification_channels']);
    }

    /** @test */
    public function it_checks_if_event_is_enabled()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'alert_events' => ['position_drop', 'entered_top_10'],
        ]);

        $this->assertTrue($settings->isEventEnabled('position_drop'));
        $this->assertTrue($settings->isEventEnabled('entered_top_10'));
        $this->assertFalse($settings->isEventEnabled('position_gain'));
    }

    /** @test */
    public function it_allows_all_events_when_not_configured()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'alert_events' => null,
        ]);

        $this->assertTrue($settings->isEventEnabled('position_drop'));
        $this->assertTrue($settings->isEventEnabled('any_event'));
    }

    /** @test */
    public function it_disables_events_when_settings_disabled()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => false,
            'alert_events' => ['position_drop'],
        ]);

        $this->assertFalse($settings->isEventEnabled('position_drop'));
    }

    /** @test */
    public function it_checks_notification_channels()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'notification_channels' => ['email'],
        ]);

        $this->assertTrue($settings->isChannelEnabled('email'));
        $this->assertFalse($settings->isChannelEnabled('in_app'));
    }

    /** @test */
    public function it_has_event_type_constants()
    {
        $this->assertEquals([
            'position_drop',
            'position_gain',
            'entered_top_10',
            'left_top_10',
            'serp_feature_gained',
            'serp_feature_lost',
        ], KeywordAlertSettings::EVENT_TYPES);
    }
}
