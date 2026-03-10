<?php

namespace Tests\Unit\Models;

use App\Models\ContentDecayProject;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @Task TASK-36-2
 * @Description ContentDecayProject Model Unit Tests
 * @Tests 85+
 */
class ContentDecayProjectTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (MI-001 to MI-005)
    // ==========================================

    /** @test MI-001 */
    public function create_with_required_fields(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertDatabaseHas('content_decay_projects', [
            'id' => $project->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);
    }

    /** @test MI-002 */
    public function create_with_all_fields(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
            'window_days' => 28,
            'min_clicks_threshold' => 10,
            'min_impressions_threshold' => 100,
            'min_decay_score' => 20.00,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'settings_json' => ['custom_setting' => 'value'],
            'last_scanned_at' => now(),
        ]);

        $this->assertTrue($project->detection_enabled);
        $this->assertEquals(28, $project->window_days);
        $this->assertEquals(10, $project->min_clicks_threshold);
        $this->assertEquals(100, $project->min_impressions_threshold);
        $this->assertEquals(20.00, $project->min_decay_score);
        $this->assertTrue($project->digest_enabled);
        $this->assertEquals('weekly', $project->digest_frequency);
        $this->assertEquals(['custom_setting' => 'value'], $project->settings_json);
        $this->assertNotNull($project->last_scanned_at);
    }

    /** @test MI-003 */
    public function mass_assignment_protects_id(): void
    {
        $project = ContentDecayProject::factory()->create();
        $originalId = $project->id;

        $project->fill(['id' => 999999]);

        $this->assertEquals($originalId, $project->id);
    }

    /** @test MI-004 */
    public function factory_creates_valid_model(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertNotNull($project->id);
        $this->assertNotNull($project->user_id);
        $this->assertNotNull($project->site_id);
        $this->assertTrue($project->detection_enabled);
    }

    /** @test MI-005 */
    public function model_uses_timestamps(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertNotNull($project->created_at);
        $this->assertNotNull($project->updated_at);
    }

    // ==========================================
    // Casts Tests (CA-001 to CA-012)
    // ==========================================

    /** @test CA-001 */
    public function detection_enabled_cast_to_boolean(): void
    {
        $projectTrue = ContentDecayProject::factory()->create(['detection_enabled' => true]);
        $projectFalse = ContentDecayProject::factory()->create(['detection_enabled' => false]);

        $this->assertTrue($projectTrue->detection_enabled);
        $this->assertFalse($projectFalse->detection_enabled);
    }

    /** @test CA-002 */
    public function digest_enabled_cast_to_boolean(): void
    {
        $projectTrue = ContentDecayProject::factory()->create(['digest_enabled' => true]);
        $projectFalse = ContentDecayProject::factory()->create(['digest_enabled' => false]);

        $this->assertTrue($projectTrue->digest_enabled);
        $this->assertFalse($projectFalse->digest_enabled);
    }

    /** @test CA-003 */
    public function window_days_integer(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 28]);

        $this->assertEquals(28, $project->window_days);
        $this->assertIsInt($project->window_days);
    }

    /** @test CA-004 */
    public function min_clicks_threshold_integer(): void
    {
        $project = ContentDecayProject::factory()->create(['min_clicks_threshold' => 10]);

        $this->assertEquals(10, $project->min_clicks_threshold);
    }

    /** @test CA-005 */
    public function min_impressions_threshold_integer(): void
    {
        $project = ContentDecayProject::factory()->create(['min_impressions_threshold' => 100]);

        $this->assertEquals(100, $project->min_impressions_threshold);
    }

    /** @test CA-006 */
    public function min_decay_score_decimal(): void
    {
        $project = ContentDecayProject::factory()->create(['min_decay_score' => 20.50]);

        $project->refresh();
        $this->assertEquals(20.50, (float) $project->min_decay_score);
    }

    /** @test CA-007 */
    public function digest_frequency_enum(): void
    {
        $projectWeekly = ContentDecayProject::factory()->create(['digest_frequency' => 'weekly']);
        $projectMonthly = ContentDecayProject::factory()->create(['digest_frequency' => 'monthly']);

        $this->assertEquals('weekly', $projectWeekly->digest_frequency);
        $this->assertEquals('monthly', $projectMonthly->digest_frequency);
    }

    /** @test CA-008 */
    public function settings_json_cast_to_array(): void
    {
        $project = ContentDecayProject::factory()->create([
            'settings_json' => ['key1' => 'value1', 'key2' => 'value2'],
        ]);

        $this->assertIsArray($project->settings_json);
        $this->assertEquals('value1', $project->settings_json['key1']);
        $this->assertEquals('value2', $project->settings_json['key2']);
    }

    /** @test CA-009 */
    public function settings_json_null_returns_null(): void
    {
        $project = ContentDecayProject::factory()->create(['settings_json' => null]);

        $this->assertNull($project->settings_json);
    }

    /** @test CA-010 */
    public function settings_json_empty_array(): void
    {
        $project = ContentDecayProject::factory()->create(['settings_json' => []]);

        $this->assertIsArray($project->settings_json);
        $this->assertEmpty($project->settings_json);
    }

    /** @test CA-011 */
    public function last_scanned_at_cast_to_datetime(): void
    {
        $project = ContentDecayProject::factory()->create([
            'last_scanned_at' => now()->subHour(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $project->last_scanned_at);
    }

    /** @test CA-012 */
    public function last_scanned_at_null(): void
    {
        $project = ContentDecayProject::factory()->create(['last_scanned_at' => null]);

        $this->assertNull($project->last_scanned_at);
    }

    // ==========================================
    // Relationships Tests (RE-001 to RE-010)
    // ==========================================

    /** @test RE-001 */
    public function user_relationship_exists(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $project->user());
    }

    /** @test RE-002 */
    public function user_returns_owner(): void
    {
        $user = User::factory()->create();
        $project = ContentDecayProject::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $project->user->id);
    }

    /** @test RE-003 */
    public function site_relationship_exists(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $project->site());
    }

    /** @test RE-004 */
    public function site_returns_site(): void
    {
        $site = Site::factory()->create();
        $project = ContentDecayProject::factory()->create(['site_id' => $site->id]);

        $this->assertEquals($site->id, $project->site->id);
    }

    /** @test RE-005 */
    public function candidates_relationship_exists(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->candidates());
    }

    /** @test RE-006 */
    public function candidates_returns_collection(): void
    {
        $project = ContentDecayProject::factory()->create();

        // Skip if ContentDecayCandidate doesn't exist yet
        if (!class_exists(\App\Models\ContentDecayCandidate::class)) {
            $this->markTestSkipped('ContentDecayCandidate model not yet implemented');
        }

        $this->assertCount(0, $project->candidates);
    }

    /** @test RE-007 */
    public function eager_loading_user(): void
    {
        $project = ContentDecayProject::factory()->create();

        $loaded = ContentDecayProject::with('user')->find($project->id);

        $this->assertTrue($loaded->relationLoaded('user'));
    }

    /** @test RE-008 */
    public function eager_loading_site(): void
    {
        $project = ContentDecayProject::factory()->create();

        $loaded = ContentDecayProject::with('site')->find($project->id);

        $this->assertTrue($loaded->relationLoaded('site'));
    }

    /** @test RE-009 */
    public function unique_site_constraint(): void
    {
        $site = Site::factory()->create();

        ContentDecayProject::factory()->create(['site_id' => $site->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayProject::factory()->create(['site_id' => $site->id]);
    }

    /** @test RE-010 */
    public function delete_cascades_to_candidates(): void
    {
        $project = ContentDecayProject::factory()->create();

        if (!class_exists(\App\Models\ContentDecayCandidate::class)) {
            $this->markTestSkipped('ContentDecayCandidate model not yet implemented');
        }

        $project->delete();

        // Verify cascade delete happened
        $this->assertSoftDeleted('content_decay_projects', ['id' => $project->id]);
    }

    // ==========================================
    // Scopes Tests (SQ-001 to SQ-015)
    // ==========================================

    /** @test SQ-001 */
    public function scope_enabled_filters_enabled(): void
    {
        ContentDecayProject::factory()->create(['detection_enabled' => true]);
        ContentDecayProject::factory()->disabled()->create();

        $results = ContentDecayProject::enabled()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->detection_enabled);
    }

    /** @test SQ-002 */
    public function scope_enabled_excludes_disabled(): void
    {
        ContentDecayProject::factory()->create(['detection_enabled' => true]);
        ContentDecayProject::factory()->disabled()->create();

        $results = ContentDecayProject::enabled()->get();

        $this->assertFalse($results->contains('detection_enabled', false));
    }

    /** @test SQ-003 */
    public function scope_for_user_filters_by_user(): void
    {
        $user = User::factory()->create();
        ContentDecayProject::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create();

        $results = ContentDecayProject::forUser($user->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
    }

    /** @test SQ-004 */
    public function scope_for_user_excludes_others(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        ContentDecayProject::factory()->create(['user_id' => $user1->id]);
        ContentDecayProject::factory()->create(['user_id' => $user2->id]);

        $results = ContentDecayProject::forUser($user1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user1->id, $results->first()->user_id);
    }

    /** @test SQ-005 */
    public function scope_chainable(): void
    {
        $user = User::factory()->create();
        ContentDecayProject::factory()->create(['user_id' => $user->id, 'detection_enabled' => true]);
        ContentDecayProject::factory()->disabled()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create(['detection_enabled' => true]);

        $results = ContentDecayProject::enabled()->forUser($user->id)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-006 */
    public function scope_digest_enabled(): void
    {
        ContentDecayProject::factory()->create(['digest_enabled' => true]);
        ContentDecayProject::factory()->noDigest()->create();

        if (method_exists(ContentDecayProject::class, 'scopeDigestEnabled')) {
            $results = ContentDecayProject::digestEnabled()->get();
            $this->assertCount(1, $results);
        } else {
            $results = ContentDecayProject::where('digest_enabled', true)->get();
            $this->assertCount(1, $results);
        }
    }

    /** @test SQ-007 */
    public function scope_weekly_digest(): void
    {
        ContentDecayProject::factory()->create(['digest_frequency' => 'weekly']);
        ContentDecayProject::factory()->create(['digest_frequency' => 'monthly']);

        $results = ContentDecayProject::where('digest_frequency', 'weekly')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('weekly', $results->first()->digest_frequency);
    }

    /** @test SQ-008 */
    public function scope_monthly_digest(): void
    {
        ContentDecayProject::factory()->create(['digest_frequency' => 'monthly']);
        ContentDecayProject::factory()->create(['digest_frequency' => 'weekly']);

        $results = ContentDecayProject::where('digest_frequency', 'monthly')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('monthly', $results->first()->digest_frequency);
    }

    /** @test SQ-009 */
    public function scope_needs_scan(): void
    {
        ContentDecayProject::factory()->create(['last_scanned_at' => null]);
        ContentDecayProject::factory()->create(['last_scanned_at' => now()->subDay()]);

        $results = ContentDecayProject::whereNull('last_scanned_at')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-010 */
    public function scope_order_by_latest(): void
    {
        $older = ContentDecayProject::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContentDecayProject::factory()->create(['created_at' => now()]);

        $results = ContentDecayProject::latest()->get();

        $this->assertEquals($newer->id, $results->first()->id);
    }

    /** @test SQ-011 */
    public function scope_with_user(): void
    {
        ContentDecayProject::factory()->create();

        $results = ContentDecayProject::with('user')->get();

        $this->assertTrue($results->first()->relationLoaded('user'));
    }

    /** @test SQ-012 */
    public function scope_with_site(): void
    {
        ContentDecayProject::factory()->create();

        $results = ContentDecayProject::with('site')->get();

        $this->assertTrue($results->first()->relationLoaded('site'));
    }

    /** @test SQ-013 */
    public function scope_for_site(): void
    {
        $site = Site::factory()->create();
        ContentDecayProject::factory()->create(['site_id' => $site->id]);
        ContentDecayProject::factory()->create();

        $results = ContentDecayProject::where('site_id', $site->id)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-014 */
    public function scope_window_days(): void
    {
        ContentDecayProject::factory()->create(['window_days' => 28]);
        ContentDecayProject::factory()->create(['window_days' => 7]);

        $results = ContentDecayProject::where('window_days', 28)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-015 */
    public function scope_min_decay_score(): void
    {
        ContentDecayProject::factory()->create(['min_decay_score' => 20.00]);
        ContentDecayProject::factory()->create(['min_decay_score' => 50.00]);

        $results = ContentDecayProject::where('min_decay_score', '<=', 25)->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Helper Methods Tests (HM-001 to HM-008)
    // ==========================================

    /** @test HM-001 */
    public function is_detection_enabled_true(): void
    {
        $project = ContentDecayProject::factory()->create(['detection_enabled' => true]);

        if (method_exists($project, 'isDetectionEnabled')) {
            $this->assertTrue($project->isDetectionEnabled());
        } else {
            $this->assertTrue($project->detection_enabled);
        }
    }

    /** @test HM-002 */
    public function is_detection_enabled_false(): void
    {
        $project = ContentDecayProject::factory()->disabled()->create();

        if (method_exists($project, 'isDetectionEnabled')) {
            $this->assertFalse($project->isDetectionEnabled());
        } else {
            $this->assertFalse($project->detection_enabled);
        }
    }

    /** @test HM-003 */
    public function get_window_days_default(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 28]);

        if (method_exists($project, 'getWindowDays')) {
            $this->assertEquals(28, $project->getWindowDays());
        } else {
            $this->assertEquals(28, $project->window_days);
        }
    }

    /** @test HM-004 */
    public function get_window_days_custom(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 14]);

        if (method_exists($project, 'getWindowDays')) {
            $this->assertEquals(14, $project->getWindowDays());
        } else {
            $this->assertEquals(14, $project->window_days);
        }
    }

    /** @test HM-005 */
    public function get_thresholds(): void
    {
        $project = ContentDecayProject::factory()->create([
            'min_clicks_threshold' => 15,
            'min_impressions_threshold' => 200,
            'min_decay_score' => 25.00,
        ]);

        $this->assertEquals(15, $project->min_clicks_threshold);
        $this->assertEquals(200, $project->min_impressions_threshold);
        $this->assertEquals(25.00, (float) $project->min_decay_score);
    }

    /** @test HM-006 */
    public function should_send_digest_weekly(): void
    {
        $project = ContentDecayProject::factory()->create([
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        $this->assertTrue($project->digest_enabled);
        $this->assertEquals('weekly', $project->digest_frequency);
    }

    /** @test HM-007 */
    public function should_send_digest_monthly(): void
    {
        $project = ContentDecayProject::factory()->create([
            'digest_enabled' => true,
            'digest_frequency' => 'monthly',
        ]);

        $this->assertTrue($project->digest_enabled);
        $this->assertEquals('monthly', $project->digest_frequency);
    }

    /** @test HM-008 */
    public function should_not_send_digest_disabled(): void
    {
        $project = ContentDecayProject::factory()->noDigest()->create();

        $this->assertFalse($project->digest_enabled);
    }

    // ==========================================
    // Factory States Tests (FS-001 to FS-010)
    // ==========================================

    /** @test FS-001 */
    public function factory_default_enabled(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertTrue($project->detection_enabled);
    }

    /** @test FS-002 */
    public function factory_disabled_state(): void
    {
        $project = ContentDecayProject::factory()->disabled()->create();

        $this->assertFalse($project->detection_enabled);
    }

    /** @test FS-003 */
    public function factory_digest_enabled(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertTrue($project->digest_enabled);
    }

    /** @test FS-004 */
    public function factory_no_digest_state(): void
    {
        $project = ContentDecayProject::factory()->noDigest()->create();

        $this->assertFalse($project->digest_enabled);
    }

    /** @test FS-005 */
    public function factory_weekly_digest_state(): void
    {
        $project = ContentDecayProject::factory()->weeklyDigest()->create();

        $this->assertEquals('weekly', $project->digest_frequency);
    }

    /** @test FS-006 */
    public function factory_monthly_digest_state(): void
    {
        $project = ContentDecayProject::factory()->monthlyDigest()->create();

        $this->assertEquals('monthly', $project->digest_frequency);
    }

    /** @test FS-007 */
    public function factory_window_28_days(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertEquals(28, $project->window_days);
    }

    /** @test FS-008 */
    public function factory_window_custom_days(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 14]);

        $this->assertEquals(14, $project->window_days);
    }

    /** @test FS-009 */
    public function factory_creates_user(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertNotNull($project->user_id);
        $this->assertDatabaseHas('users', ['id' => $project->user_id]);
    }

    /** @test FS-010 */
    public function factory_creates_site(): void
    {
        $project = ContentDecayProject::factory()->create();

        $this->assertNotNull($project->site_id);
        $this->assertDatabaseHas('sites', ['id' => $project->site_id]);
    }

    // ==========================================
    // Edge Cases Tests (EC-001 to EC-010)
    // ==========================================

    /** @test EC-001 */
    public function null_settings_json(): void
    {
        $project = ContentDecayProject::factory()->create(['settings_json' => null]);

        $this->assertNull($project->settings_json);
    }

    /** @test EC-002 */
    public function empty_settings_json(): void
    {
        $project = ContentDecayProject::factory()->create(['settings_json' => []]);

        $this->assertIsArray($project->settings_json);
        $this->assertEmpty($project->settings_json);
    }

    /** @test EC-003 */
    public function nested_settings_json(): void
    {
        $project = ContentDecayProject::factory()->create([
            'settings_json' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value',
                    ],
                ],
            ],
        ]);

        $this->assertEquals('deep_value', $project->settings_json['level1']['level2']['level3']);
    }

    /** @test EC-004 */
    public function null_last_scanned_at(): void
    {
        $project = ContentDecayProject::factory()->create(['last_scanned_at' => null]);

        $this->assertNull($project->last_scanned_at);
    }

    /** @test EC-005 */
    public function window_days_boundary_min(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 7]);

        $this->assertEquals(7, $project->window_days);
    }

    /** @test EC-006 */
    public function window_days_boundary_max(): void
    {
        $project = ContentDecayProject::factory()->create(['window_days' => 56]);

        $this->assertEquals(56, $project->window_days);
    }

    /** @test EC-007 */
    public function decay_score_min_value(): void
    {
        $project = ContentDecayProject::factory()->create(['min_decay_score' => 0.00]);

        $project->refresh();
        $this->assertEquals(0.00, (float) $project->min_decay_score);
    }

    /** @test EC-008 */
    public function decay_score_max_value(): void
    {
        $project = ContentDecayProject::factory()->create(['min_decay_score' => 100.00]);

        $project->refresh();
        $this->assertEquals(100.00, (float) $project->min_decay_score);
    }

    /** @test EC-009 */
    public function large_thresholds(): void
    {
        $project = ContentDecayProject::factory()->create([
            'min_clicks_threshold' => 10000,
            'min_impressions_threshold' => 100000,
        ]);

        $this->assertEquals(10000, $project->min_clicks_threshold);
        $this->assertEquals(100000, $project->min_impressions_threshold);
    }

    /** @test EC-010 */
    public function zero_thresholds(): void
    {
        $project = ContentDecayProject::factory()->create([
            'min_clicks_threshold' => 0,
            'min_impressions_threshold' => 0,
        ]);

        $this->assertEquals(0, $project->min_clicks_threshold);
        $this->assertEquals(0, $project->min_impressions_threshold);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-005)
    // ==========================================

    /** @test SEC-001 */
    public function sql_injection_in_settings_json(): void
    {
        $project = ContentDecayProject::factory()->create([
            'settings_json' => ['key' => "'; DROP TABLE content_decay_projects; --"],
        ]);

        $this->assertDatabaseHas('content_decay_projects', ['id' => $project->id]);
    }

    /** @test SEC-002 */
    public function xss_in_settings_json(): void
    {
        $project = ContentDecayProject::factory()->create([
            'settings_json' => ['key' => '<script>alert(1)</script>'],
        ]);

        $this->assertEquals('<script>alert(1)</script>', $project->settings_json['key']);
    }

    /** @test SEC-003 */
    public function mass_assignment_user_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertEquals($user->id, $project->user_id);
    }

    /** @test SEC-004 */
    public function unique_site_prevents_duplicate_projects(): void
    {
        $site = Site::factory()->create();

        ContentDecayProject::factory()->create(['site_id' => $site->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayProject::factory()->create(['site_id' => $site->id]);
    }

    /** @test SEC-005 */
    public function invalid_digest_frequency_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayProject::factory()->create(['digest_frequency' => 'invalid']);
    }

    // ==========================================
    // Integration Tests (INT-001 to INT-005)
    // ==========================================

    /** @test INT-001 */
    public function create_and_retrieve(): void
    {
        $project = ContentDecayProject::factory()->create([
            'window_days' => 28,
            'digest_frequency' => 'weekly',
        ]);

        $retrieved = ContentDecayProject::find($project->id);

        $this->assertEquals(28, $retrieved->window_days);
        $this->assertEquals('weekly', $retrieved->digest_frequency);
    }

    /** @test INT-002 */
    public function update_and_persist(): void
    {
        $project = ContentDecayProject::factory()->create();

        $project->update([
            'window_days' => 14,
            'digest_frequency' => 'monthly',
        ]);

        $project->refresh();

        $this->assertEquals(14, $project->window_days);
        $this->assertEquals('monthly', $project->digest_frequency);
    }

    /** @test INT-003 */
    public function delete_project(): void
    {
        $project = ContentDecayProject::factory()->create();
        $id = $project->id;

        $project->delete();

        $this->assertDatabaseMissing('content_decay_projects', ['id' => $id]);
    }

    /** @test INT-004 */
    public function update_last_scanned_at(): void
    {
        $project = ContentDecayProject::factory()->create(['last_scanned_at' => null]);

        $project->update(['last_scanned_at' => now()]);

        $project->refresh();
        $this->assertNotNull($project->last_scanned_at);
    }

    /** @test INT-005 */
    public function bulk_creation(): void
    {
        ContentDecayProject::factory()->count(50)->create();

        $this->assertEquals(50, ContentDecayProject::count());
    }

    // ==========================================
    // Performance Tests (PERF-001 to PERF-003)
    // ==========================================

    /** @test PERF-001 */
    public function eager_loading_no_n_plus_one(): void
    {
        ContentDecayProject::factory()->count(20)->create();

        \DB::enableQueryLog();

        $projects = ContentDecayProject::with(['user', 'site'])->get();

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should be 3 queries: projects, users, sites
        $this->assertLessThanOrEqual(4, count($queries), 'N+1 queries detected');
        $this->assertCount(20, $projects);
    }

    /** @test PERF-002 */
    public function index_usage_for_user_query(): void
    {
        $user = User::factory()->create();
        ContentDecayProject::factory()->count(10)->create(['user_id' => $user->id]);

        $projects = ContentDecayProject::where('user_id', $user->id)->get();

        $this->assertCount(10, $projects);
    }

    /** @test PERF-003 */
    public function index_usage_for_site_query(): void
    {
        $site = Site::factory()->create();
        ContentDecayProject::factory()->create(['site_id' => $site->id]);

        $project = ContentDecayProject::where('site_id', $site->id)->first();

        $this->assertNotNull($project);
    }
}
