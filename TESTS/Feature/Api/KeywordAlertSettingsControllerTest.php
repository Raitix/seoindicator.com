<?php

namespace Tests\Feature\Api;

use App\Models\KeywordAlertSetting;
use App\Models\Keyword;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordAlertSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Keyword $keyword;
    protected Keyword $otherKeyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $this->otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id]);
    }

    // ==================== GET Endpoint ====================

    /** @test */
    public function authenticated_user_gets_keyword_settings(): void
    {
        $setting = KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 5,
            'alert_events' => ['position_drop', 'entered_top_10'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'enabled' => true,
                    'custom_threshold' => 5,
                    'alert_events' => ['position_drop', 'entered_top_10'],
                ],
            ]);
    }

    /** @test */
    public function no_settings_returns_defaults(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'enabled' => true,
                    'custom_threshold' => null,
                    'alert_events' => ['position_drop', 'position_gain', 'entered_top_10', 'left_top_10'],
                    'notification_channels' => ['email', 'in_app'],
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_get_returns_401(): void
    {
        $response = $this->getJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertUnauthorized();
    }

    /** @test */
    public function other_users_keyword_returns_403(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/keywords/{$this->otherKeyword->id}/alert-settings");

        $response->assertForbidden();
    }

    /** @test */
    public function non_existent_keyword_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/keywords/99999/alert-settings');

        $response->assertNotFound();
    }

    // ==================== PUT Endpoint ====================

    /** @test */
    public function update_enabled(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
        ]);
    }

    /** @test */
    public function update_custom_threshold(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 10,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 10,
        ]);
    }

    /** @test */
    public function update_alert_events(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'alert_events' => ['position_drop', 'left_top_10'],
            ]);

        $response->assertOk();

        $setting = KeywordAlertSetting::where('keyword_id', $this->keyword->id)->first();
        $this->assertEquals(['position_drop', 'left_top_10'], $setting->alert_events);
    }

    /** @test */
    public function update_notification_channels(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'notification_channels' => ['email'],
            ]);

        $response->assertOk();

        $setting = KeywordAlertSetting::where('keyword_id', $this->keyword->id)->first();
        $this->assertEquals(['email'], $setting->notification_channels);
    }

    /** @test */
    public function update_all_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'enabled' => false,
                'custom_threshold' => 7,
                'alert_events' => ['position_drop'],
                'notification_channels' => ['email'],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
            'custom_threshold' => 7,
        ]);
    }

    /** @test */
    public function invalid_threshold_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 150,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['custom_threshold']);
    }

    /** @test */
    public function invalid_event_type_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'alert_events' => ['invalid_event'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['alert_events']);
    }

    /** @test */
    public function invalid_channel_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'notification_channels' => ['sms'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['notification_channels']);
    }

    /** @test */
    public function unauthenticated_put_returns_401(): void
    {
        $response = $this->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
            'enabled' => false,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function other_users_keyword_put_returns_403(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->otherKeyword->id}/alert-settings", [
                'enabled' => false,
            ]);

        $response->assertForbidden();
    }

    // ==================== DELETE Endpoint ====================

    /** @test */
    public function delete_settings_resets_to_defaults(): void
    {
        KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
        ]);
    }

    /** @test */
    public function delete_no_settings_returns_success(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk();
    }

    /** @test */
    public function delete_unauthenticated_returns_401(): void
    {
        $response = $this->deleteJson("/api/keywords/{$this->keyword->id}/alert-settings");

        $response->assertUnauthorized();
    }

    /** @test */
    public function delete_other_users_keyword_returns_403(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/keywords/{$this->otherKeyword->id}/alert-settings");

        $response->assertForbidden();
    }

    // ==================== Validation ====================

    /** @test */
    public function threshold_minimum_is_1(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 0,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function threshold_maximum_is_100(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 101,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function valid_event_types_accepted(): void
    {
        $validEvents = ['position_drop', 'position_gain', 'entered_top_10', 'left_top_10'];

        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'alert_events' => $validEvents,
            ]);

        $response->assertOk();
    }

    /** @test */
    public function valid_channels_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/keywords/{$this->keyword->id}/alert-settings", [
                'notification_channels' => ['email', 'in_app'],
            ]);

        $response->assertOk();
    }

    // ==================== Security ====================

    /** @test */
    public function sql_injection_in_keyword_id_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/keywords/1; DROP TABLE keywords/alert-settings");

        $response->assertNotFound();
    }

    /** @test */
    public function path_traversal_in_keyword_id_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/keywords/../etc/passwd/alert-settings");

        $response->assertNotFound();
    }
}
