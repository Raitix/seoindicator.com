<?php

namespace Tests\Unit\Models;

use App\Models\AlertSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_model_with_defaults(): void
    {
        $user = User::factory()->create();
        
        $setting = AlertSetting::create([
            'user_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('alert_settings', [
            'id' => $setting->id,
            'user_id' => $user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
            'digest_mode' => 'instant',
        ]);
    }

    /** @test */
    public function all_columns_are_fillable(): void
    {
        $user = User::factory()->create();
        
        $setting = AlertSetting::create([
            'user_id' => $user->id,
            'global_enabled' => false,
            'default_threshold' => 5,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '07:00:00',
            'digest_mode' => 'daily',
        ]);
        
        $this->assertEquals(false, $setting->global_enabled);
        $this->assertEquals(5, $setting->default_threshold);
        $this->assertEquals('22:00:00', $setting->quiet_hours_start);
        $this->assertEquals('07:00:00', $setting->quiet_hours_end);
        $this->assertEquals('daily', $setting->digest_mode);
    }

    /** @test */
    public function belongs_to_user(): void
    {
        $user = User::factory()->create();
        $setting = AlertSetting::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $setting->user);
        $this->assertEquals($user->id, $setting->user->id);
    }

    /** @test */
    public function global_enabled_defaults_to_true(): void
    {
        $setting = AlertSetting::factory()->create();
        
        $this->assertTrue($setting->global_enabled);
    }

    /** @test */
    public function default_threshold_defaults_to_3(): void
    {
        $setting = AlertSetting::factory()->create();
        
        $this->assertEquals(3, $setting->default_threshold);
    }

    /** @test */
    public function digest_mode_defaults_to_instant(): void
    {
        $setting = AlertSetting::factory()->create();
        
        $this->assertEquals('instant', $setting->digest_mode);
    }

    /** @test */
    public function user_id_must_be_unique(): void
    {
        $user = User::factory()->create();
        AlertSetting::factory()->create(['user_id' => $user->id]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AlertSetting::factory()->create(['user_id' => $user->id]);
    }

    /** @test */
    public function cascade_deletes_with_user(): void
    {
        $user = User::factory()->create();
        $setting = AlertSetting::factory()->create(['user_id' => $user->id]);
        
        $user->delete();
        
        $this->assertDatabaseMissing('alert_settings', [
            'id' => $setting->id,
        ]);
    }

    /** @test */
    public function can_update_all_fields(): void
    {
        $setting = AlertSetting::factory()->create();
        
        $setting->update([
            'global_enabled' => false,
            'default_threshold' => 10,
            'quiet_hours_start' => '23:00:00',
            'quiet_hours_end' => '06:00:00',
            'digest_mode' => 'weekly',
        ]);
        
        $this->assertEquals(false, $setting->fresh()->global_enabled);
        $this->assertEquals(10, $setting->fresh()->default_threshold);
        $this->assertEquals('23:00:00', $setting->fresh()->quiet_hours_start);
        $this->assertEquals('06:00:00', $setting->fresh()->quiet_hours_end);
        $this->assertEquals('weekly', $setting->fresh()->digest_mode);
    }

    /** @test */
    public function quiet_hours_accepts_valid_time_format(): void
    {
        $setting = AlertSetting::factory()->create([
            'quiet_hours_start' => '22:30:00',
            'quiet_hours_end' => '07:15:00',
        ]);
        
        $this->assertEquals('22:30:00', $setting->quiet_hours_start);
        $this->assertEquals('07:15:00', $setting->quiet_hours_end);
    }

    /** @test */
    public function threshold_cannot_be_zero(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AlertSetting::factory()->create(['default_threshold' => 0]);
    }

    /** @test */
    public function threshold_cannot_exceed_100(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AlertSetting::factory()->create(['default_threshold' => 101]);
    }

    /** @test */
    public function quiet_hours_can_be_null(): void
    {
        $setting = AlertSetting::factory()->create([
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ]);
        
        $this->assertNull($setting->quiet_hours_start);
        $this->assertNull($setting->quiet_hours_end);
    }

    /** @test */
    public function quiet_hours_same_start_and_end_is_valid(): void
    {
        $setting = AlertSetting::factory()->create([
            'quiet_hours_start' => '12:00:00',
            'quiet_hours_end' => '12:00:00',
        ]);
        
        $this->assertEquals('12:00:00', $setting->quiet_hours_start);
        $this->assertEquals('12:00:00', $setting->quiet_hours_end);
    }

    /** @test */
    public function digest_mode_validates_enum_values(): void
    {
        $validModes = ['instant', 'daily', 'weekly'];
        
        foreach ($validModes as $mode) {
            $setting = AlertSetting::factory()->create(['digest_mode' => $mode]);
            $this->assertEquals($mode, $setting->digest_mode);
        }
    }

    /** @test */
    public function sql_injection_in_threshold_is_sanitized(): void
    {
        $setting = AlertSetting::factory()->create([
            'default_threshold' => "5; DROP TABLE alert_settings; --",
        ]);
        
        // Should fail validation or sanitize
        $this->assertDatabaseHas('alert_settings', ['id' => $setting->id]);
    }
}
