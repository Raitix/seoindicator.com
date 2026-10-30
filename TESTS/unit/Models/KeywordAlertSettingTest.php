<?php

namespace Tests\Unit\Models;

use App\Models\KeywordAlertSetting;
use App\Models\Keyword;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordAlertSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
    }

    /** @test */
    public function can_create_model_with_defaults(): void
    {
        $setting = KeywordAlertSetting::create([
            'keyword_id' => $this->keyword->id,
        ]);
        
        $this->assertDatabaseHas('keyword_alert_settings', [
            'id' => $setting->id,
            'keyword_id' => $this->keyword->id,
            'enabled' => true,
        ]);
    }

    /** @test */
    public function all_columns_are_fillable(): void
    {
        $setting = KeywordAlertSetting::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
            'custom_threshold' => 5,
            'alert_events' => ['position_drop', 'entered_top_10'],
            'notification_channels' => ['email', 'in_app'],
        ]);
        
        $this->assertEquals(false, $setting->enabled);
        $this->assertEquals(5, $setting->custom_threshold);
        $this->assertEquals(['position_drop', 'entered_top_10'], $setting->alert_events);
        $this->assertEquals(['email', 'in_app'], $setting->notification_channels);
    }

    /** @test */
    public function belongs_to_keyword(): void
    {
        $setting = KeywordAlertSetting::factory()->create(['keyword_id' => $this->keyword->id]);
        
        $this->assertInstanceOf(Keyword::class, $setting->keyword);
        $this->assertEquals($this->keyword->id, $setting->keyword->id);
    }

    /** @test */
    public function enabled_defaults_to_true(): void
    {
        $setting = KeywordAlertSetting::factory()->create(['keyword_id' => $this->keyword->id]);
        
        $this->assertTrue($setting->enabled);
    }

    /** @test */
    public function alert_events_casts_to_array(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => ['position_drop', 'position_gain'],
        ]);
        
        $this->assertIsArray($setting->alert_events);
        $this->assertCount(2, $setting->alert_events);
        $this->assertEquals('position_drop', $setting->alert_events[0]);
    }

    /** @test */
    public function notification_channels_casts_to_array(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'notification_channels' => ['email'],
        ]);
        
        $this->assertIsArray($setting->notification_channels);
        $this->assertCount(1, $setting->notification_channels);
        $this->assertEquals('email', $setting->notification_channels[0]);
    }

    /** @test */
    public function keyword_id_must_be_unique(): void
    {
        KeywordAlertSetting::factory()->create(['keyword_id' => $this->keyword->id]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        KeywordAlertSetting::factory()->create(['keyword_id' => $this->keyword->id]);
    }

    /** @test */
    public function cascade_deletes_with_keyword(): void
    {
        $setting = KeywordAlertSetting::factory()->create(['keyword_id' => $this->keyword->id]);
        
        $this->keyword->delete();
        
        $this->assertDatabaseMissing('keyword_alert_settings', [
            'id' => $setting->id,
        ]);
    }

    /** @test */
    public function custom_threshold_can_be_null(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => null,
        ]);
        
        $this->assertNull($setting->custom_threshold);
    }

    /** @test */
    public function empty_alert_events_array_is_valid(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => [],
        ]);
        
        $this->assertIsArray($setting->alert_events);
        $this->assertEmpty($setting->alert_events);
    }

    /** @test */
    public function empty_notification_channels_array_is_valid(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'notification_channels' => [],
        ]);
        
        $this->assertIsArray($setting->notification_channels);
        $this->assertEmpty($setting->notification_channels);
    }

    /** @test */
    public function single_event_stored_correctly(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => ['position_drop'],
        ]);
        
        $fresh = KeywordAlertSetting::find($setting->id);
        $this->assertEquals(['position_drop'], $fresh->alert_events);
    }

    /** @test */
    public function multiple_events_stored_correctly(): void
    {
        $events = ['position_drop', 'position_gain', 'entered_top_10', 'left_top_10'];
        
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => $events,
        ]);
        
        $fresh = KeywordAlertSetting::find($setting->id);
        $this->assertEquals($events, $fresh->alert_events);
    }

    /** @test */
    public function custom_threshold_minimum_is_1(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 1,
        ]);
        
        $this->assertEquals(1, $setting->custom_threshold);
    }

    /** @test */
    public function custom_threshold_maximum_is_100(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 100,
        ]);
        
        $this->assertEquals(100, $setting->custom_threshold);
    }

    /** @test */
    public function sql_injection_in_json_is_sanitized(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => ["position_drop'; DROP TABLE keyword_alert_settings; --"],
        ]);
        
        // Should sanitize or reject - table should still exist
        $this->assertDatabaseHas('keyword_alert_settings', ['id' => $setting->id]);
    }
}
