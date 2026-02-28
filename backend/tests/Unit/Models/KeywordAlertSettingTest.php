<?php

namespace Tests\Unit\Models;

use App\Models\KeywordAlertSettings;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordAlertSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createKeyword(): Keyword
    {
        $user = User::factory()->create();
        $site = Site::create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'name' => 'Test Site',
        ]);
        return Keyword::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);
    }

    public function test_mod_001_create_model_with_defaults(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create(['keyword_id' => $keyword->id]);

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $keyword->id,
            'enabled' => true,
        ]);
    }

    public function test_mod_002_fillable_attributes(): void
    {
        $settings = new KeywordAlertSettings();
        $fillable = [
            'keyword_id',
            'enabled',
            'custom_threshold',
            'alert_events',
            'notification_channels',
        ];

        $this->assertEquals($fillable, $settings->getFillable());
    }

    public function test_mod_003_keyword_relationship(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create(['keyword_id' => $keyword->id]);

        $this->assertInstanceOf(Keyword::class, $settings->keyword);
        $this->assertEquals($keyword->id, $settings->keyword->id);
    }

    public function test_mod_004_default_enabled(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create(['keyword_id' => $keyword->id]);

        $this->assertTrue($settings->enabled);
    }

    public function test_mod_005_json_casting_alert_events(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'alert_events' => ['position_drop', 'position_gain'],
        ]);

        $this->assertIsArray($settings->alert_events);
        $this->assertEquals(['position_drop', 'position_gain'], $settings->alert_events);
    }

    public function test_mod_006_json_casting_notification_channels(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'notification_channels' => ['email', 'in_app'],
        ]);

        $this->assertIsArray($settings->notification_channels);
        $this->assertEquals(['email', 'in_app'], $settings->notification_channels);
    }

    public function test_mod_007_keyword_uniqueness(): void
    {
        $keyword = $this->createKeyword();
        KeywordAlertSettings::create(['keyword_id' => $keyword->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        KeywordAlertSettings::create(['keyword_id' => $keyword->id]);
    }

    public function test_mod_008_cascade_delete(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create(['keyword_id' => $keyword->id]);

        // Use forceDelete to actually delete and trigger cascade
        $keyword->forceDelete();

        $this->assertDatabaseMissing('keyword_alert_settings', [
            'id' => $settings->id,
        ]);
    }

    public function test_mod_009_custom_threshold(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'custom_threshold' => 5,
        ]);

        $this->assertEquals(5, $settings->custom_threshold);
    }

    public function test_mod_010_is_event_enabled_all_events(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'alert_events' => null,
        ]);

        $this->assertTrue($settings->isEventEnabled('position_drop'));
        $this->assertTrue($settings->isEventEnabled('serp_feature_gained'));
    }

    public function test_mod_011_is_event_enabled_specific_events(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => true,
            'alert_events' => ['position_drop'],
        ]);

        $this->assertTrue($settings->isEventEnabled('position_drop'));
        $this->assertFalse($settings->isEventEnabled('position_gain'));
    }

    public function test_mod_012_is_event_disabled_when_keyword_disabled(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'enabled' => false,
            'alert_events' => ['position_drop'],
        ]);

        $this->assertFalse($settings->isEventEnabled('position_drop'));
    }

    public function test_mod_013_is_channel_enabled_all_channels(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'notification_channels' => null,
        ]);

        $this->assertTrue($settings->isChannelEnabled('email'));
        $this->assertTrue($settings->isChannelEnabled('in_app'));
    }

    public function test_mod_014_is_channel_enabled_specific_channels(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'notification_channels' => ['email'],
        ]);

        $this->assertTrue($settings->isChannelEnabled('email'));
        $this->assertFalse($settings->isChannelEnabled('in_app'));
    }

    public function test_mod_015_event_types_constant(): void
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

    public function test_mod_016_channels_constant(): void
    {
        $this->assertEquals(['email', 'in_app'], KeywordAlertSettings::CHANNELS);
    }

    public function test_mod_017_get_defaults(): void
    {
        $defaults = KeywordAlertSettings::getDefaults();

        $this->assertEquals([
            'enabled' => true,
            'alert_events' => null,
            'notification_channels' => null,
        ], $defaults);
    }

    public function test_mod_018_null_custom_threshold(): void
    {
        $keyword = $this->createKeyword();
        $settings = KeywordAlertSettings::create([
            'keyword_id' => $keyword->id,
            'custom_threshold' => null,
        ]);

        $this->assertNull($settings->custom_threshold);
    }
}
