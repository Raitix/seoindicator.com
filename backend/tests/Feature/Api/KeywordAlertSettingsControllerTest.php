<?php

namespace Tests\Feature\Api;

use App\Models\Keyword;
use App\Models\KeywordAlertSettings;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordAlertSettingsControllerTest extends TestCase
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

    public function test_show_returns_defaults_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'keyword_id' => $this->keyword->id,
                    'enabled' => true,
                    'is_default' => true,
                ],
            ]);
    }

    public function test_show_returns_existing_settings(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
            'custom_threshold' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'keyword_id' => $this->keyword->id,
                    'enabled' => false,
                    'custom_threshold' => 5,
                    'is_default' => false,
                ],
            ]);
    }

    public function test_update_creates_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'enabled' => false,
                'custom_threshold' => 10,
                'alert_events' => ['position_drop'],
                'notification_channels' => ['email'],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'enabled' => false,
                    'custom_threshold' => 10,
                    'alert_events' => ['position_drop'],
                    'notification_channels' => ['email'],
                ],
            ]);

        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
            'custom_threshold' => 10,
        ]);
    }

    public function test_update_modifies_existing_settings(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'enabled' => false,
                ],
            ]);
    }

    public function test_destroy_resets_to_defaults(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
            'custom_threshold' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => true,
            ]);

        $this->assertDatabaseMissing('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_update(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'enabled' => false,
            ]);

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete(): void
    {
        $otherUser = User::factory()->create();

        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertForbidden();

        // Settings should still exist
        $this->assertDatabaseHas('keyword_alert_settings', [
            'keyword_id' => $this->keyword->id,
        ]);
    }

    public function test_update_validates_custom_threshold_min(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_threshold']);
    }

    public function test_update_validates_custom_threshold_max(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => 101,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_threshold']);
    }

    public function test_update_validates_alert_events(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'alert_events' => ['invalid_event'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alert_events.0']);
    }

    public function test_update_accepts_valid_alert_events(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'alert_events' => ['position_drop', 'position_gain', 'entered_top_10'],
            ]);

        $response->assertOk();
    }

    public function test_update_validates_notification_channels(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'notification_channels' => ['invalid_channel'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notification_channels.0']);
    }

    public function test_update_accepts_valid_notification_channels(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'notification_channels' => ['email', 'in_app'],
            ]);

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertUnauthorized();
    }

    public function test_show_includes_all_fields(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => true,
            'custom_threshold' => 5,
            'alert_events' => ['position_drop'],
            'notification_channels' => ['email'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'keyword_id',
                    'enabled',
                    'custom_threshold',
                    'alert_events',
                    'notification_channels',
                    'is_default',
                ],
            ]);
    }

    public function test_destroy_returns_false_when_no_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$this->keyword->id}/alert-settings");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => false,
            ]);
    }

    public function test_null_custom_threshold_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$this->keyword->id}/alert-settings", [
                'custom_threshold' => null,
            ]);

        $response->assertOk();
    }
}
