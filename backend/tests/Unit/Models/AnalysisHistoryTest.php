<?php

namespace Tests\Unit\Models;

use App\Models\AnalysisHistory;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AnalysisHistory model.
 *
 * @see TASK-25-12
 * @see TEST-25-12
 */
class AnalysisHistoryTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Creation Tests (MC)
    // ==========================================

    /** @test MC-001: Can create analysis with all fields */
    public function test_mc_001_can_create_with_all_fields(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com/page',
            'status' => 'completed',
            'results' => ['title' => 'Test', 'score' => 85],
            'error_message' => null,
            'analysis_duration_ms' => 1234,
            'analyzed_at' => now(),
        ]);

        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'tool_name' => 'meta-tag-analyzer',
            'status' => 'completed',
            'analysis_duration_ms' => 1234,
        ]);
    }

    /** @test MC-002: Can create with minimal fields */
    public function test_mc_002_can_create_with_minimal_fields(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'keyword-density',
            'input_url' => 'https://example.com',
        ]);

        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
            'user_id' => $user->id,
            'tool_name' => 'keyword-density',
        ]);
    }

    /** @test MC-003: Default status is 'completed' */
    public function test_mc_003_default_status_is_completed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'heading-analyzer',
            'input_url' => 'https://example.com',
        ]);

        $analysis->refresh(); // Get DB defaults
        $this->assertEquals('completed', $analysis->status);
    }

    /** @test MC-004: results cast to array */
    public function test_mc_004_results_cast_to_array(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => ['title' => 'Test Title', 'score' => 85, 'issues' => []],
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertEquals('Test Title', $analysis->results['title']);
        $this->assertEquals(85, $analysis->results['score']);
    }

    /** @test MC-005: analyzed_at cast to datetime */
    public function test_mc_005_analyzed_at_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'analyzed_at' => '2026-03-01 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $analysis->analyzed_at);
        $this->assertEquals('2026-03-01 10:30:00', $analysis->analyzed_at->format('Y-m-d H:i:s'));
    }

    /** @test MC-006: Null site_id allowed */
    public function test_mc_006_null_site_id_allowed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'site_id' => null,
            'tool_name' => 'link-analyzer',
            'input_url' => 'https://example.com',
        ]);

        $this->assertNull($analysis->site_id);
    }

    /** @test MC-007: Null results allowed */
    public function test_mc_007_null_results_allowed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => null,
        ]);

        $this->assertNull($analysis->results);
    }

    /** @test MC-008: Null error_message allowed */
    public function test_mc_008_null_error_message_allowed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'error_message' => null,
        ]);

        $this->assertNull($analysis->error_message);
    }

    /** @test MC-009: Null analysis_duration_ms allowed */
    public function test_mc_009_null_analysis_duration_ms_allowed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'analysis_duration_ms' => null,
        ]);

        $this->assertNull($analysis->analysis_duration_ms);
    }

    /** @test MC-010: Valid status enum values */
    public function test_mc_010_valid_status_enum_values(): void
    {
        $user = User::factory()->create();
        $validStatuses = ['pending', 'completed', 'failed'];

        foreach ($validStatuses as $status) {
            $analysis = AnalysisHistory::create([
                'user_id' => $user->id,
                'tool_name' => 'meta-tag-analyzer',
                'input_url' => 'https://example.com',
                'status' => $status,
            ]);

            $this->assertEquals($status, $analysis->status);
        }
    }

    // ==========================================
    // Relationship Tests (RL)
    // ==========================================

    /** @test RL-001: BelongsTo User relationship */
    public function test_rl_001_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $analysis->user->id);
        $this->assertInstanceOf(User::class, $analysis->user);
    }

    /** @test RL-002: BelongsTo Site relationship */
    public function test_rl_002_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $analysis = AnalysisHistory::factory()->forSite($site)->create();

        $this->assertEquals($site->id, $analysis->site->id);
        $this->assertInstanceOf(Site::class, $analysis->site);
    }

    /** @test RL-003: Site relationship returns null when no site */
    public function test_rl_003_site_relationship_returns_null(): void
    {
        $analysis = AnalysisHistory::factory()->withoutSite()->create();

        $this->assertNull($analysis->site);
    }

    /** @test RL-004: User hasMany analysisHistory */
    public function test_rl_004_user_has_many_analysis_history(): void
    {
        $user = User::factory()->create();
        $analysis1 = AnalysisHistory::factory()->forUser($user)->create();
        $analysis2 = AnalysisHistory::factory()->forUser($user)->create();

        $this->assertCount(2, $user->analysisHistory);
        $this->assertTrue($user->analysisHistory->contains($analysis1));
        $this->assertTrue($user->analysisHistory->contains($analysis2));
    }

    /** @test RL-005: Site hasMany analysisHistory */
    public function test_rl_005_site_has_many_analysis_history(): void
    {
        $site = Site::factory()->create();
        $analysis1 = AnalysisHistory::factory()->forSite($site)->create();
        $analysis2 = AnalysisHistory::factory()->forSite($site)->create();

        $this->assertCount(2, $site->analysisHistory);
        $this->assertTrue($site->analysisHistory->contains($analysis1));
        $this->assertTrue($site->analysisHistory->contains($analysis2));
    }

    /** @test RL-006: Deleting user cascades to analyses */
    public function test_rl_006_deleting_user_cascades_to_analyses(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->forUser($user)->create();

        $user->delete();

        $this->assertDatabaseMissing('analysis_history', [
            'id' => $analysis->id,
        ]);
    }

    /** @test RL-007: Deleting site sets site_id to NULL */
    public function test_rl_007_deleting_site_sets_site_id_null(): void
    {
        $site = Site::factory()->create();
        $analysis = AnalysisHistory::factory()->forSite($site)->create();

        $site->delete();

        $analysis->refresh();
        $this->assertNull($analysis->site_id);
        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
        ]);
    }

    // ==========================================
    // Scope Tests (SC)
    // ==========================================

    /** @test SC-001: scopeCompleted filters completed */
    public function test_sc_001_scope_completed_filters(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->create(['status' => 'pending']);
        AnalysisHistory::factory()->forUser($user)->create(['status' => 'failed']);

        $results = AnalysisHistory::completed()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals('completed', $a->status));
    }

    /** @test SC-002: scopePending filters pending */
    public function test_sc_002_scope_pending_filters(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['status' => 'pending']);
        AnalysisHistory::factory()->forUser($user)->create(['status' => 'failed']);

        $results = AnalysisHistory::pending()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertEquals('pending', $a->status));
    }

    /** @test SC-003: scopeFailed filters failed */
    public function test_sc_003_scope_failed_filters(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->create(['status' => 'pending']);
        AnalysisHistory::factory()->forUser($user)->count(3)->create(['status' => 'failed']);

        $results = AnalysisHistory::failed()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals('failed', $a->status));
    }

    /** @test SC-004: scopeByTool filters by tool_name */
    public function test_sc_004_scope_by_tool_filters(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['tool_name' => 'keyword-density']);

        $results = AnalysisHistory::byTool('meta-tag-analyzer')->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals('meta-tag-analyzer', $a->tool_name));
    }

    /** @test SC-005: scopeByUser filters by user_id */
    public function test_sc_005_scope_by_user_filters(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->forUser($user1)->count(3)->create();
        AnalysisHistory::factory()->forUser($user2)->count(2)->create();

        $results = AnalysisHistory::byUser($user1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals($user1->id, $a->user_id));
    }

    /** @test SC-006: scopeBySite filters by site_id */
    public function test_sc_006_scope_by_site_filters(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        AnalysisHistory::factory()->forSite($site1)->count(3)->create();
        AnalysisHistory::factory()->forSite($site2)->count(2)->create();

        $results = AnalysisHistory::bySite($site1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals($site1->id, $a->site_id));
    }

    /** @test SC-007: scopeAnalyzedBetween date range */
    public function test_sc_007_scope_analyzed_between_filters(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(10),
        ]);
        $middle = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(5),
        ]);
        $recent = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now(),
        ]);

        $results = AnalysisHistory::analyzedBetween(
            now()->subDays(7)->startOfDay(),
            now()->subDays(3)->endOfDay()
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals($middle->id, $results->first()->id);
    }

    /** @test SC-008: scopeRecent ordered by analyzed_at DESC */
    public function test_sc_008_scope_recent_orders_desc(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(2),
        ]);
        $middle = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDay(),
        ]);
        $recent = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now(),
        ]);

        $results = AnalysisHistory::recent()->get();

        $this->assertEquals($recent->id, $results->first()->id);
        $this->assertEquals($old->id, $results->last()->id);
    }

    /** @test SC-009: scopeWithResults filters non-null results */
    public function test_sc_009_scope_with_results_filters(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->withResults()->create();
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['results' => null]);

        $results = AnalysisHistory::withResults()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertNotNull($a->results));
    }

    /** @test SC-010: scopeWithoutSite filters null site_id */
    public function test_sc_010_scope_without_site_filters(): void
    {
        $site = Site::factory()->create();

        AnalysisHistory::factory()->forSite($site)->count(3)->create();
        AnalysisHistory::factory()->withoutSite()->count(2)->create();

        $results = AnalysisHistory::withoutSite()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertNull($a->site_id));
    }

    // ==========================================
    // Edge Case Tests (EC)
    // ==========================================

    /** @test EC-001: Large JSON in results */
    public function test_ec_001_large_json_in_results(): void
    {
        $user = User::factory()->create();

        // Create large JSON (~100KB)
        $largeResults = [
            'data' => str_repeat('A', 100000),
            'items' => array_fill(0, 1000, ['key' => 'value']),
        ];

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => $largeResults,
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertCount(1000, $analysis->results['items']);
    }

    /** @test EC-002: Unicode in input_url */
    public function test_ec_002_unicode_in_input_url(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com/путь/страница',
        ]);

        $this->assertEquals('https://example.com/путь/страница', $analysis->input_url);
    }

    /** @test EC-003: Unicode in results */
    public function test_ec_003_unicode_in_results(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [
                'title' => 'Заголовок страницы',
                'description' => 'Описание с unicode символами: €£¥',
            ],
        ]);

        $this->assertEquals('Заголовок страницы', $analysis->results['title']);
        $this->assertStringContainsString('€£¥', $analysis->results['description']);
    }

    /** @test EC-004: Very long tool_name (100 chars) */
    public function test_ec_004_long_tool_name(): void
    {
        $user = User::factory()->create();

        $longName = str_repeat('a', 100);

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => $longName,
            'input_url' => 'https://example.com',
        ]);

        $this->assertEquals($longName, $analysis->tool_name);
    }

    /** @test EC-005: Very long input_url (2083 chars) */
    public function test_ec_005_long_input_url(): void
    {
        $user = User::factory()->create();

        $longUrl = 'https://example.com/path?' . str_repeat('a', 2050);

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => $longUrl,
        ]);

        $this->assertEquals($longUrl, $analysis->input_url);
    }

    /** @test EC-006: Zero duration */
    public function test_ec_006_zero_duration(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'analysis_duration_ms' => 0,
        ]);

        $this->assertEquals(0, $analysis->analysis_duration_ms);
    }

    /** @test EC-007: Empty results object */
    public function test_ec_007_empty_results_object(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [],
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertEmpty($analysis->results);
    }

    // ==========================================
    // Security Tests (SEC)
    // ==========================================

    /** @test SEC-001: SQL injection in input_url prevented */
    public function test_sec_001_sql_injection_in_url_prevented(): void
    {
        $user = User::factory()->create();

        $maliciousUrl = "https://example.com?id=1'; DROP TABLE analysis_history; --";

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => $maliciousUrl,
        ]);

        // Verify the data is stored as-is (parameterized query)
        $this->assertStringContainsString("DROP TABLE", $analysis->input_url);

        // Verify table still exists
        $this->assertDatabaseHas('analysis_history', ['id' => $analysis->id]);
    }

    /** @test SEC-002: SQL injection in tool_name prevented */
    public function test_sec_002_sql_injection_in_tool_name_prevented(): void
    {
        $user = User::factory()->create();

        $maliciousName = "tool'; DELETE FROM analysis_history WHERE 1=1; --";

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => $maliciousName,
            'input_url' => 'https://example.com',
        ]);

        // Verify table still exists
        $this->assertDatabaseHas('analysis_history', ['id' => $analysis->id]);
    }

    /** @test SEC-003: XSS in error_message not executed */
    public function test_sec_003_xss_in_error_message_not_executed(): void
    {
        $user = User::factory()->create();

        $xssPayload = '<script>alert("xss")</script>';

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'error_message' => $xssPayload,
        ]);

        // Values are stored as-is, output escaping happens at display layer
        $this->assertEquals($xssPayload, $analysis->error_message);
    }

    /** @test SEC-004: XSS in results JSON not executed */
    public function test_sec_004_xss_in_results_json_not_executed(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [
                'title' => '<script>alert("xss")</script>',
                'description' => '<img src="x" onerror="alert(1)">',
            ],
        ]);

        // Values are stored as-is, output escaping happens at display layer
        $this->assertEquals('<script>alert("xss")</script>', $analysis->results['title']);
    }
}
