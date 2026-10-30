<?php

namespace Tests\Feature\Api;

use App\Models\AlertSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_show_returns_default_settings_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/alert-settings');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'global_enabled' => true,
                    'default_threshold' => 3,
                    'digest_mode' => 'instant',
                ],
            ]);
    }

    public function test_show_returns_existing_settings(): void
    {
        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => false,
            'default_threshold' => 5,
            'digest_mode' => 'daily',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/alert-settings');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'global_enabled' => false,
                    'default_threshold' => 5,
                    'digest_mode' => 'daily',
                ],
            ]);
    }

    public function test_update_creates_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'global_enabled' => false,
                'default_threshold' => 10,
                'digest_mode' => 'weekly',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'global_enabled' => false,
                    'default_threshold' => 10,
                    'digest_mode' => 'weekly',
                ],
            ]);

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'global_enabled' => false,
            'default_threshold' => 10,
            'digest_mode' => 'weekly',
        ]);
    }

    public function test_update_modifies_existing_settings(): void
    {
        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'global_enabled' => false,
                'default_threshold' => 5,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'global_enabled' => false,
                    'default_threshold' => 5,
                ],
            ]);
    }

    public function test_update_validates_threshold_min(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'default_threshold' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_threshold']);
    }

    public function test_update_validates_threshold_max(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'default_threshold' => 101,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_threshold']);
    }

    public function test_update_validates_digest_mode(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'digest_mode' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['digest_mode']);
    }

    public function test_update_accepts_valid_digest_modes(): void
    {
        foreach (['instant', 'daily', 'weekly'] as $mode) {
            $response = $this->actingAs($this->user)
                ->putJson('/api/v1/user/alert-settings', [
                    'digest_mode' => $mode,
                ]);

            $response->assertOk();
        }
    }

    public function test_update_quiet_hours(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '07:00',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'quiet_hours_start' => '22:00',
                    'quiet_hours_end' => '07:00',
                ],
            ]);
    }

    public function test_update_serp_feature_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'serp_feature_alerts_enabled' => true,
                'serp_feature_types' => ['featured_snippet', 'local_pack'],
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'serp_feature_alerts_enabled' => true,
                    'serp_feature_types' => ['featured_snippet', 'local_pack'],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/user/alert-settings');

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_update(): void
    {
        $response = $this->putJson('/api/v1/user/alert-settings', [
            'global_enabled' => false,
        ]);

        $response->assertUnauthorized();
    }

    public function test_show_includes_all_fields(): void
    {
        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 5,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '07:00:00',
            'digest_mode' => 'daily',
            'serp_feature_alerts_enabled' => true,
            'serp_feature_types' => ['featured_snippet'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/alert-settings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'global_enabled',
                    'default_threshold',
                    'quiet_hours_start',
                    'quiet_hours_end',
                    'digest_mode',
                    'serp_feature_alerts_enabled',
                    'serp_feature_types',
                ],
            ]);
    }

    public function test_update_validates_boolean_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/alert-settings', [
                'global_enabled' => 'not-a-boolean',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['global_enabled']);
    }

    public function test_users_cannot_access_other_users_settings(): void
    {
        $otherUser = User::factory()->create();
        AlertSettings::create([
            'user_id' => $otherUser->id,
            'global_enabled' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/alert-settings');

        // Should return current user's settings (or defaults), not other user's
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'global_enabled' => true, // Default, not other user's false
                ],
            ]);
    }
}
