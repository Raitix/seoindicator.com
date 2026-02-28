<?php

namespace Tests\Feature\Api;

use App\Models\AlertSetting;
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

    // ==================== GET Endpoint ====================

    /** @test */
    public function authenticated_user_gets_settings(): void
    {
        $setting = AlertSetting::factory()->create([
            'user_id' => $this->user->id,
            'default_threshold' => 5,
            'digest_mode' => 'daily',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/alert-settings');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'global_enabled' => true,
                    'default_threshold' => 5,
                    'digest_mode' => 'daily',
                ],
            ]);
    }

    /** @test */
    public function no_settings_returns_defaults(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/alert-settings');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'global_enabled' => true,
                    'default_threshold' => 3,
                    'digest_mode' => 'instant',
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_get_returns_401(): void
    {
        $response = $this->getJson('/api/user/alert-settings');

        $response->assertUnauthorized();
    }

    /** @test */
    public function existing_settings_returned(): void
    {
        $setting = AlertSetting::factory()->create([
            'user_id' => $this->user->id,
            'global_enabled' => false,
            'default_threshold' => 10,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '07:00:00',
            'digest_mode' => 'weekly',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/alert-settings');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'global_enabled' => false,
                    'default_threshold' => 10,
                    'quiet_hours_start' => '22:00:00',
                    'quiet_hours_end' => '07:00:00',
                    'digest_mode' => 'weekly',
                ],
            ]);
    }

    // ==================== PUT Endpoint ====================

    /** @test */
    public function update_global_enabled(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'global_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'global_enabled' => false,
        ]);
    }

    /** @test */
    public function update_default_threshold(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => 10,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'default_threshold' => 10,
        ]);
    }

    /** @test */
    public function update_quiet_hours(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'quiet_hours_start' => '23:00:00',
                'quiet_hours_end' => '06:00:00',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'quiet_hours_start' => '23:00:00',
            'quiet_hours_end' => '06:00:00',
        ]);
    }

    /** @test */
    public function update_digest_mode(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'digest_mode' => 'daily',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'digest_mode' => 'daily',
        ]);
    }

    /** @test */
    public function update_all_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'global_enabled' => false,
                'default_threshold' => 7,
                'quiet_hours_start' => '21:00:00',
                'quiet_hours_end' => '08:00:00',
                'digest_mode' => 'weekly',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('alert_settings', [
            'user_id' => $this->user->id,
            'global_enabled' => false,
            'default_threshold' => 7,
            'quiet_hours_start' => '21:00:00',
            'quiet_hours_end' => '08:00:00',
            'digest_mode' => 'weekly',
        ]);
    }

    /** @test */
    public function invalid_threshold_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => 150,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['default_threshold']);
    }

    /** @test */
    public function invalid_digest_mode_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'digest_mode' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['digest_mode']);
    }

    /** @test */
    public function invalid_time_format_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'quiet_hours_start' => 'not-a-time',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quiet_hours_start']);
    }

    /** @test */
    public function unauthenticated_put_returns_401(): void
    {
        $response = $this->putJson('/api/user/alert-settings', [
            'default_threshold' => 5,
        ]);

        $response->assertUnauthorized();
    }

    // ==================== Validation ====================

    /** @test */
    public function threshold_minimum_is_1(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => 0,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function threshold_maximum_is_100(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => 101,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function threshold_must_be_integer(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => 3.5,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function global_enabled_must_be_boolean(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'global_enabled' => 'yes',
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function valid_digest_modes_accepted(): void
    {
        foreach (['instant', 'daily', 'weekly'] as $mode) {
            $response = $this->actingAs($this->user)
                ->putJson('/api/user/alert-settings', [
                    'digest_mode' => $mode,
                ]);

            $response->assertOk();
        }
    }

    // ==================== Security ====================

    /** @test */
    public function sql_injection_in_threshold_sanitized(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/alert-settings', [
                'default_threshold' => "5; DROP TABLE alert_settings; --",
            ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('alert_settings', 0);
    }
}
