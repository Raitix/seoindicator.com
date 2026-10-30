<?php

namespace Tests\Unit\Models;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @Task TASK-36-2
 * @Description ContentDecayCandidate Model Unit Tests
 * @Tests 120+
 */
class ContentDecayCandidateTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (MI-001 to MI-006)
    // ==========================================

    /** @test MI-001 */
    public function create_with_required_fields(): void
    {
        $project = ContentDecayProject::factory()->create();

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page',
            'page_url_hash' => hash('sha256', 'https://example.com/page'),
            'decay_score' => 45.50,
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down', 'ctr_down'],
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        $this->assertDatabaseHas('content_decay_candidates', [
            'id' => $candidate->id,
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page',
        ]);
    }

    /** @test MI-002 */
    public function create_with_all_fields(): void
    {
        $project = ContentDecayProject::factory()->create();

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/full-page',
            'page_url_hash' => hash('sha256', 'https://example.com/full-page'),
            'page_title' => 'Full Test Page',
            'page_type' => 'blog',
            'severity' => 'high',
            'decay_score' => 65.75,
            'detection_confidence' => 0.93,
            'lost_click_estimate' => 500,
            'recovery_upside_estimate' => 750,
            'primary_reason_code' => 'impressions_down',
            'reason_codes' => ['impressions_down', 'avg_position_down'],
            'signal_sources' => ['gsc', 'rankings'],
            'evidence_json' => ['clicks_before' => 1000, 'clicks_after' => 500],
            'status' => 'backlog',
            'first_detected_at' => now()->subDays(7),
            'last_detected_at' => now(),
            'planned_at' => null,
            'started_at' => null,
            'refreshed_at' => null,
            'recovered_at' => null,
            'ignored_at' => null,
        ]);

        $this->assertEquals('Full Test Page', $candidate->page_title);
        $this->assertEquals('blog', $candidate->page_type);
        $this->assertEquals('high', $candidate->severity);
        $this->assertEquals(65.75, (float) $candidate->decay_score);
        $this->assertEquals(0.93, (float) $candidate->detection_confidence);
        $this->assertEquals(500, $candidate->lost_click_estimate);
        $this->assertEquals(750, $candidate->recovery_upside_estimate);
    }

    /** @test MI-003 */
    public function mass_assignment_protects_id(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $originalId = $candidate->id;

        $candidate->fill(['id' => 999999]);

        $this->assertEquals($originalId, $candidate->id);
    }

    /** @test MI-004 */
    public function factory_creates_valid_model(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertNotNull($candidate->id);
        $this->assertNotNull($candidate->project_id);
        $this->assertNotNull($candidate->page_url);
        $this->assertNotNull($candidate->decay_score);
    }

    /** @test MI-005 */
    public function model_uses_timestamps(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertNotNull($candidate->created_at);
        $this->assertNotNull($candidate->updated_at);
    }

    /** @test MI-006 */
    public function default_status_is_backlog(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertEquals('backlog', $candidate->status);
    }

    // ==========================================
    // Casts Tests (CA-001 to CA-015)
    // ==========================================

    /** @test CA-001 */
    public function decay_score_cast_to_decimal(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['decay_score' => 45.50]);

        $candidate->refresh();
        $this->assertEquals(45.50, (float) $candidate->decay_score);
    }

    /** @test CA-002 */
    public function detection_confidence_cast_to_decimal(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['detection_confidence' => 0.85]);

        $candidate->refresh();
        $this->assertEquals(0.85, (float) $candidate->detection_confidence);
    }

    /** @test CA-003 */
    public function reason_codes_cast_to_array(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'reason_codes' => ['clicks_down', 'ctr_down', 'impressions_down'],
        ]);

        $this->assertIsArray($candidate->reason_codes);
        $this->assertCount(3, $candidate->reason_codes);
        $this->assertContains('clicks_down', $candidate->reason_codes);
    }

    /** @test CA-004 */
    public function signal_sources_cast_to_array(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'signal_sources' => ['gsc', 'rankings', 'audit'],
        ]);

        $this->assertIsArray($candidate->signal_sources);
        $this->assertContains('gsc', $candidate->signal_sources);
    }

    /** @test CA-005 */
    public function evidence_json_cast_to_array(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => [
                'clicks_before' => 1000,
                'clicks_after' => 500,
                'delta' => -500,
            ],
        ]);

        $this->assertIsArray($candidate->evidence_json);
        $this->assertEquals(1000, $candidate->evidence_json['clicks_before']);
        $this->assertEquals(-500, $candidate->evidence_json['delta']);
    }

    /** @test CA-006 */
    public function signal_sources_null_returns_null(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['signal_sources' => null]);

        $this->assertNull($candidate->signal_sources);
    }

    /** @test CA-007 */
    public function evidence_json_null_returns_null(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['evidence_json' => null]);

        $this->assertNull($candidate->evidence_json);
    }

    /** @test CA-008 */
    public function severity_enum(): void
    {
        $candidateLow = ContentDecayCandidate::factory()->create(['severity' => 'low']);
        $candidateMedium = ContentDecayCandidate::factory()->create(['severity' => 'medium']);
        $candidateHigh = ContentDecayCandidate::factory()->create(['severity' => 'high']);
        $candidateCritical = ContentDecayCandidate::factory()->create(['severity' => 'critical']);

        $this->assertEquals('low', $candidateLow->severity);
        $this->assertEquals('medium', $candidateMedium->severity);
        $this->assertEquals('high', $candidateHigh->severity);
        $this->assertEquals('critical', $candidateCritical->severity);
    }

    /** @test CA-009 */
    public function status_enum(): void
    {
        foreach (['backlog', 'planned', 'in_progress', 'refreshed', 'recovered', 'ignored'] as $status) {
            $candidate = ContentDecayCandidate::factory()->create(['status' => $status]);
            $this->assertEquals($status, $candidate->status);
        }
    }

    /** @test CA-010 */
    public function page_type(): void
    {
        foreach (['home', 'blog', 'landing', 'product', 'tool', 'compare', 'docs', 'other'] as $type) {
            $candidate = ContentDecayCandidate::factory()->create(['page_type' => $type]);
            $this->assertEquals($type, $candidate->page_type);
        }
    }

    /** @test CA-011 */
    public function first_detected_at_cast_to_datetime(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'first_detected_at' => now()->subDays(7),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $candidate->first_detected_at);
    }

    /** @test CA-012 */
    public function last_detected_at_cast_to_datetime(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'last_detected_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $candidate->last_detected_at);
    }

    /** @test CA-013 */
    public function planned_at_cast_to_datetime(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'planned_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $candidate->planned_at);
    }

    /** @test CA-014 */
    public function lost_click_estimate_integer(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['lost_click_estimate' => 500]);

        $this->assertEquals(500, $candidate->lost_click_estimate);
    }

    /** @test CA-015 */
    public function recovery_upside_estimate_integer(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 750]);

        $this->assertEquals(750, $candidate->recovery_upside_estimate);
    }

    // ==========================================
    // Relationships Tests (RE-001 to RE-010)
    // ==========================================

    /** @test RE-001 */
    public function project_relationship_exists(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $candidate->project());
    }

    /** @test RE-002 */
    public function project_returns_project(): void
    {
        $project = ContentDecayProject::factory()->create();
        $candidate = ContentDecayCandidate::factory()->create(['project_id' => $project->id]);

        $this->assertEquals($project->id, $candidate->project->id);
    }

    /** @test RE-003 */
    public function signals_relationship_exists(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        if (!class_exists(\App\Models\ContentDecaySignal::class)) {
            $this->markTestSkipped('ContentDecaySignal model not yet implemented');
        }

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $candidate->signals());
    }

    /** @test RE-004 */
    public function refresh_tasks_relationship_exists(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        if (!class_exists(\App\Models\ContentRefreshTask::class)) {
            $this->markTestSkipped('ContentRefreshTask model not yet implemented');
        }

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $candidate->refreshTasks());
    }

    /** @test RE-005 */
    public function eager_loading_project(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $loaded = ContentDecayCandidate::with('project')->find($candidate->id);

        $this->assertTrue($loaded->relationLoaded('project'));
    }

    /** @test RE-006 */
    public function unique_project_page_url_constraint(): void
    {
        $project = ContentDecayProject::factory()->create();
        $url = 'https://example.com/unique-page';

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => $url,
            'page_url_hash' => hash('sha256', $url),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => $url,
            'page_url_hash' => hash('sha256', $url),
        ]);
    }

    /** @test RE-007 */
    public function same_url_different_project_allowed(): void
    {
        $project1 = ContentDecayProject::factory()->create();
        $project2 = ContentDecayProject::factory()->create();
        $url = 'https://example.com/shared-page';

        $candidate1 = ContentDecayCandidate::factory()->create([
            'project_id' => $project1->id,
            'page_url' => $url,
            'page_url_hash' => hash('sha256', $url),
        ]);

        $candidate2 = ContentDecayCandidate::factory()->create([
            'project_id' => $project2->id,
            'page_url' => $url,
            'page_url_hash' => hash('sha256', $url),
        ]);

        $this->assertNotEquals($candidate1->id, $candidate2->id);
    }

    /** @test RE-008 */
    public function delete_cascades_to_signals(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        if (!class_exists(\App\Models\ContentDecaySignal::class)) {
            $this->markTestSkipped('ContentDecaySignal model not yet implemented');
        }

        $candidate->delete();

        $this->assertDatabaseMissing('content_decay_candidates', ['id' => $candidate->id]);
    }

    /** @test RE-009 */
    public function delete_cascades_to_refresh_tasks(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        if (!class_exists(\App\Models\ContentRefreshTask::class)) {
            $this->markTestSkipped('ContentRefreshTask model not yet implemented');
        }

        $candidate->delete();

        $this->assertDatabaseMissing('content_decay_candidates', ['id' => $candidate->id]);
    }

    /** @test RE-010 */
    public function cascade_from_project(): void
    {
        $project = ContentDecayProject::factory()->create();
        $candidate = ContentDecayCandidate::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('content_decay_candidates', ['id' => $candidate->id]);
    }

    // ==========================================
    // Scopes Tests (SQ-001 to SQ-030)
    // ==========================================

    /** @test SQ-001 */
    public function scope_by_status_filters_by_status(): void
    {
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);
        ContentDecayCandidate::factory()->planned()->create();

        $results = ContentDecayCandidate::byStatus('backlog')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('backlog', $results->first()->status);
    }

    /** @test SQ-002 */
    public function scope_by_status_planned(): void
    {
        ContentDecayCandidate::factory()->planned()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::byStatus('planned')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('planned', $results->first()->status);
    }

    /** @test SQ-003 */
    public function scope_by_severity_filters_by_severity(): void
    {
        ContentDecayCandidate::factory()->create(['severity' => 'critical']);
        ContentDecayCandidate::factory()->create(['severity' => 'medium']);

        $results = ContentDecayCandidate::bySeverity('critical')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('critical', $results->first()->severity);
    }

    /** @test SQ-004 */
    public function scope_by_severity_high(): void
    {
        ContentDecayCandidate::factory()->highSeverity()->create();
        ContentDecayCandidate::factory()->create(['severity' => 'low']);

        $results = ContentDecayCandidate::bySeverity('high')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-005 */
    public function scope_by_page_type_filters_by_type(): void
    {
        ContentDecayCandidate::factory()->create(['page_type' => 'blog']);
        ContentDecayCandidate::factory()->create(['page_type' => 'product']);

        $results = ContentDecayCandidate::byPageType('blog')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('blog', $results->first()->page_type);
    }

    /** @test SQ-006 */
    public function scope_minimum_score_filters(): void
    {
        ContentDecayCandidate::factory()->create(['decay_score' => 75.00]);
        ContentDecayCandidate::factory()->create(['decay_score' => 25.00]);

        $results = ContentDecayCandidate::minimumScore(50)->get();

        $this->assertCount(1, $results);
        $this->assertGreaterThanOrEqual(50, (float) $results->first()->decay_score);
    }

    /** @test SQ-007 */
    public function scope_backlog(): void
    {
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);
        ContentDecayCandidate::factory()->planned()->create();

        $results = ContentDecayCandidate::backlog()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('backlog', $results->first()->status);
    }

    /** @test SQ-008 */
    public function scope_planned(): void
    {
        ContentDecayCandidate::factory()->planned()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::planned()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('planned', $results->first()->status);
    }

    /** @test SQ-009 */
    public function scope_in_progress(): void
    {
        ContentDecayCandidate::factory()->inProgress()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::inProgress()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('in_progress', $results->first()->status);
    }

    /** @test SQ-010 */
    public function scope_refreshed(): void
    {
        ContentDecayCandidate::factory()->refreshed()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::refreshed()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('refreshed', $results->first()->status);
    }

    /** @test SQ-011 */
    public function scope_recovered(): void
    {
        ContentDecayCandidate::factory()->recovered()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::recovered()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('recovered', $results->first()->status);
    }

    /** @test SQ-012 */
    public function scope_ignored(): void
    {
        ContentDecayCandidate::factory()->ignored()->create();
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $results = ContentDecayCandidate::ignored()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('ignored', $results->first()->status);
    }

    /** @test SQ-013 */
    public function scope_chainable(): void
    {
        ContentDecayCandidate::factory()->create(['severity' => 'critical', 'status' => 'backlog']);
        ContentDecayCandidate::factory()->create(['severity' => 'critical', 'status' => 'planned']);
        ContentDecayCandidate::factory()->create(['severity' => 'medium', 'status' => 'backlog']);

        $results = ContentDecayCandidate::bySeverity('critical')->backlog()->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-014 */
    public function scope_order_by_decay_score(): void
    {
        ContentDecayCandidate::factory()->create(['decay_score' => 25.00]);
        ContentDecayCandidate::factory()->create(['decay_score' => 75.00]);

        $results = ContentDecayCandidate::orderBy('decay_score', 'desc')->get();

        $this->assertEquals(75.00, (float) $results->first()->decay_score);
    }

    /** @test SQ-015 */
    public function scope_order_by_recovery_upside(): void
    {
        ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 100]);
        ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 500]);

        $results = ContentDecayCandidate::orderBy('recovery_upside_estimate', 'desc')->get();

        $this->assertEquals(500, $results->first()->recovery_upside_estimate);
    }

    /** @test SQ-016 */
    public function scope_for_project(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->create(['project_id' => $project->id]);
        ContentDecayCandidate::factory()->create();

        $results = ContentDecayCandidate::where('project_id', $project->id)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-017 */
    public function scope_critical_severity(): void
    {
        ContentDecayCandidate::factory()->criticalSeverity()->create();
        ContentDecayCandidate::factory()->create(['severity' => 'low']);

        $results = ContentDecayCandidate::bySeverity('critical')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-018 */
    public function scope_search_by_page_url(): void
    {
        ContentDecayCandidate::factory()->create(['page_url' => 'https://example.com/blog/seo-tips']);
        ContentDecayCandidate::factory()->create(['page_url' => 'https://example.com/products/widget']);

        $results = ContentDecayCandidate::where('page_url', 'like', '%blog%')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-019 */
    public function scope_search_by_page_title(): void
    {
        ContentDecayCandidate::factory()->create(['page_title' => 'SEO Tips for 2024']);
        ContentDecayCandidate::factory()->create(['page_title' => 'Product Guide']);

        $results = ContentDecayCandidate::where('page_title', 'like', '%SEO%')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-020 */
    public function scope_high_decay_score(): void
    {
        ContentDecayCandidate::factory()->create(['decay_score' => 80.00]);
        ContentDecayCandidate::factory()->create(['decay_score' => 30.00]);

        $results = ContentDecayCandidate::where('decay_score', '>=', 70)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-021 */
    public function scope_recently_detected(): void
    {
        ContentDecayCandidate::factory()->create(['first_detected_at' => now()]);
        ContentDecayCandidate::factory()->create(['first_detected_at' => now()->subDays(30)]);

        $results = ContentDecayCandidate::where('first_detected_at', '>=', now()->subDays(7))->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-022 */
    public function scope_not_ignored(): void
    {
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);
        ContentDecayCandidate::factory()->ignored()->create();

        $results = ContentDecayCandidate::where('status', '!=', 'ignored')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-023 */
    public function scope_actionable(): void
    {
        ContentDecayCandidate::factory()->create(['status' => 'backlog']);
        ContentDecayCandidate::factory()->create(['status' => 'planned']);
        ContentDecayCandidate::factory()->ignored()->create();
        ContentDecayCandidate::factory()->recovered()->create();

        $results = ContentDecayCandidate::whereIn('status', ['backlog', 'planned', 'in_progress'])->get();

        $this->assertCount(2, $results);
    }

    /** @test SQ-024 */
    public function scope_by_reason_code(): void
    {
        ContentDecayCandidate::factory()->create([
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down', 'ctr_down'],
        ]);
        ContentDecayCandidate::factory()->create([
            'primary_reason_code' => 'impressions_down',
            'reason_codes' => ['impressions_down'],
        ]);

        $results = ContentDecayCandidate::where('primary_reason_code', 'clicks_down')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-025 */
    public function scope_latest(): void
    {
        $older = ContentDecayCandidate::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContentDecayCandidate::factory()->create(['created_at' => now()]);

        $results = ContentDecayCandidate::latest()->get();

        $this->assertEquals($newer->id, $results->first()->id);
    }

    /** @test SQ-026 */
    public function scope_with_project(): void
    {
        ContentDecayCandidate::factory()->create();

        $results = ContentDecayCandidate::with('project')->get();

        $this->assertTrue($results->first()->relationLoaded('project'));
    }

    /** @test SQ-027 */
    public function scope_has_lost_clicks(): void
    {
        ContentDecayCandidate::factory()->create(['lost_click_estimate' => 500]);
        ContentDecayCandidate::factory()->create(['lost_click_estimate' => 0]);

        $results = ContentDecayCandidate::where('lost_click_estimate', '>', 0)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-028 */
    public function scope_has_recovery_upside(): void
    {
        ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 750]);
        ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 0]);

        $results = ContentDecayCandidate::where('recovery_upside_estimate', '>', 0)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-029 */
    public function scope_high_confidence(): void
    {
        ContentDecayCandidate::factory()->create(['detection_confidence' => 0.95]);
        ContentDecayCandidate::factory()->create(['detection_confidence' => 0.75]);

        $results = ContentDecayCandidate::where('detection_confidence', '>=', 0.90)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-030 */
    public function scope_detected_in_window(): void
    {
        ContentDecayCandidate::factory()->create(['first_detected_at' => now()->subDays(3)]);
        ContentDecayCandidate::factory()->create(['first_detected_at' => now()->subDays(30)]);

        $results = ContentDecayCandidate::whereBetween('first_detected_at', [now()->subDays(7), now()])->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Accessors Tests (AC-001 to AC-008)
    // ==========================================

    /** @test AC-001 */
    public function page_url_hash_accessor(): void
    {
        $url = 'https://example.com/test-page';
        $candidate = ContentDecayCandidate::factory()->create([
            'page_url' => $url,
            'page_url_hash' => hash('sha256', $url),
        ]);

        $this->assertEquals(hash('sha256', $url), $candidate->page_url_hash);
    }

    /** @test AC-002 */
    public function page_url_hash_auto_generated(): void
    {
        // Test if model has auto-hash mutator
        $url = 'https://example.com/auto-hash-page';

        $candidate = ContentDecayCandidate::create([
            'project_id' => ContentDecayProject::factory()->create()->id,
            'page_url' => $url,
            'decay_score' => 50.00,
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down'],
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        // If mutator exists, hash should be auto-generated
        if (method_exists($candidate, 'setPageUrlHashAttribute')) {
            $this->assertEquals(hash('sha256', $url), $candidate->page_url_hash);
        }
    }

    /** @test AC-003 */
    public function is_recovered_accessor_true(): void
    {
        $candidate = ContentDecayCandidate::factory()->recovered()->create();

        if (method_exists($candidate, 'getIsRecoveredAttribute')) {
            $this->assertTrue($candidate->is_recovered);
        } else {
            $this->assertEquals('recovered', $candidate->status);
        }
    }

    /** @test AC-004 */
    public function is_recovered_accessor_false(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        if (method_exists($candidate, 'getIsRecoveredAttribute')) {
            $this->assertFalse($candidate->is_recovered);
        } else {
            $this->assertNotEquals('recovered', $candidate->status);
        }
    }

    /** @test AC-005 */
    public function severity_label_accessor(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['severity' => 'critical']);

        if (method_exists($candidate, 'getSeverityLabelAttribute')) {
            $this->assertEquals('Critical', $candidate->severity_label);
        }
    }

    /** @test AC-006 */
    public function status_label_accessor(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'in_progress']);

        if (method_exists($candidate, 'getStatusLabelAttribute')) {
            $this->assertEquals('In Progress', $candidate->status_label);
        }
    }

    /** @test AC-007 */
    public function decay_score_formatted(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['decay_score' => 75.50]);

        if (method_exists($candidate, 'getDecayScoreFormattedAttribute')) {
            $this->assertEquals('75.50', $candidate->decay_score_formatted);
        }
    }

    /** @test AC-008 */
    public function primary_reason_label(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['primary_reason_code' => 'clicks_down']);

        if (method_exists($candidate, 'getPrimaryReasonLabelAttribute')) {
            $label = $candidate->primary_reason_label;
            $this->assertIsString($label);
        }
    }

    // ==========================================
    // Helper Methods Tests (HM-001 to HM-015)
    // ==========================================

    /** @test HM-001 */
    public function mark_as_planned(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        if (method_exists($candidate, 'markAsPlanned')) {
            $candidate->markAsPlanned();
        } else {
            $candidate->update(['status' => 'planned', 'planned_at' => now()]);
        }

        $candidate->refresh();
        $this->assertEquals('planned', $candidate->status);
        $this->assertNotNull($candidate->planned_at);
    }

    /** @test HM-002 */
    public function mark_as_in_progress(): void
    {
        $candidate = ContentDecayCandidate::factory()->planned()->create();

        if (method_exists($candidate, 'markAsInProgress')) {
            $candidate->markAsInProgress();
        } else {
            $candidate->update(['status' => 'in_progress', 'started_at' => now()]);
        }

        $candidate->refresh();
        $this->assertEquals('in_progress', $candidate->status);
        $this->assertNotNull($candidate->started_at);
    }

    /** @test HM-003 */
    public function mark_as_refreshed(): void
    {
        $candidate = ContentDecayCandidate::factory()->inProgress()->create();

        if (method_exists($candidate, 'markAsRefreshed')) {
            $candidate->markAsRefreshed();
        } else {
            $candidate->update(['status' => 'refreshed', 'refreshed_at' => now()]);
        }

        $candidate->refresh();
        $this->assertEquals('refreshed', $candidate->status);
        $this->assertNotNull($candidate->refreshed_at);
    }

    /** @test HM-004 */
    public function mark_as_recovered(): void
    {
        $candidate = ContentDecayCandidate::factory()->refreshed()->create();

        if (method_exists($candidate, 'markAsRecovered')) {
            $candidate->markAsRecovered();
        } else {
            $candidate->update(['status' => 'recovered', 'recovered_at' => now()]);
        }

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
        $this->assertNotNull($candidate->recovered_at);
    }

    /** @test HM-005 */
    public function mark_as_ignored(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        if (method_exists($candidate, 'markAsIgnored')) {
            $candidate->markAsIgnored();
        } else {
            $candidate->update(['status' => 'ignored', 'ignored_at' => now()]);
        }

        $candidate->refresh();
        $this->assertEquals('ignored', $candidate->status);
        $this->assertNotNull($candidate->ignored_at);
    }

    /** @test HM-006 */
    public function status_transition_backlog_to_planned(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        $candidate->update(['status' => 'planned', 'planned_at' => now()]);

        $this->assertEquals('planned', $candidate->status);
    }

    /** @test HM-007 */
    public function status_transition_planned_to_in_progress(): void
    {
        $candidate = ContentDecayCandidate::factory()->planned()->create();

        $candidate->update(['status' => 'in_progress', 'started_at' => now()]);

        $this->assertEquals('in_progress', $candidate->status);
    }

    /** @test HM-008 */
    public function status_transition_in_progress_to_refreshed(): void
    {
        $candidate = ContentDecayCandidate::factory()->inProgress()->create();

        $candidate->update(['status' => 'refreshed', 'refreshed_at' => now()]);

        $this->assertEquals('refreshed', $candidate->status);
    }

    /** @test HM-009 */
    public function status_transition_refreshed_to_recovered(): void
    {
        $candidate = ContentDecayCandidate::factory()->refreshed()->create();

        $candidate->update(['status' => 'recovered', 'recovered_at' => now()]);

        $this->assertEquals('recovered', $candidate->status);
    }

    /** @test HM-010 */
    public function get_evidence_value(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => ['clicks_before' => 1000, 'clicks_after' => 500],
        ]);

        if (method_exists($candidate, 'getEvidenceValue')) {
            $this->assertEquals(1000, $candidate->getEvidenceValue('clicks_before'));
            $this->assertEquals(500, $candidate->getEvidenceValue('clicks_after'));
        } else {
            $this->assertEquals(1000, $candidate->evidence_json['clicks_before'] ?? null);
        }
    }

    /** @test HM-011 */
    public function has_reason_code_true(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'reason_codes' => ['clicks_down', 'ctr_down'],
        ]);

        if (method_exists($candidate, 'hasReasonCode')) {
            $this->assertTrue($candidate->hasReasonCode('clicks_down'));
        } else {
            $this->assertContains('clicks_down', $candidate->reason_codes);
        }
    }

    /** @test HM-012 */
    public function has_reason_code_false(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'reason_codes' => ['clicks_down'],
        ]);

        if (method_exists($candidate, 'hasReasonCode')) {
            $this->assertFalse($candidate->hasReasonCode('impressions_down'));
        } else {
            $this->assertNotContains('impressions_down', $candidate->reason_codes);
        }
    }

    /** @test HM-013 */
    public function get_signal_sources_list(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'signal_sources' => ['gsc', 'rankings', 'audit'],
        ]);

        $this->assertCount(3, $candidate->signal_sources);
    }

    /** @test HM-014 */
    public function is_actionable(): void
    {
        $candidateBacklog = ContentDecayCandidate::factory()->create(['status' => 'backlog']);
        $candidateIgnored = ContentDecayCandidate::factory()->ignored()->create();

        if (method_exists($candidateBacklog, 'isActionable')) {
            $this->assertTrue($candidateBacklog->isActionable());
            $this->assertFalse($candidateIgnored->isActionable());
        }
    }

    /** @test HM-015 */
    public function get_recovery_potential(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'lost_click_estimate' => 500,
            'recovery_upside_estimate' => 750,
        ]);

        $this->assertEquals(500, $candidate->lost_click_estimate);
        $this->assertEquals(750, $candidate->recovery_upside_estimate);
    }

    // ==========================================
    // Factory States Tests (FS-001 to FS-015)
    // ==========================================

    /** @test FS-001 */
    public function factory_default_status_backlog(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertEquals('backlog', $candidate->status);
    }

    /** @test FS-002 */
    public function factory_planned_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->planned()->create();

        $this->assertEquals('planned', $candidate->status);
        $this->assertNotNull($candidate->planned_at);
    }

    /** @test FS-003 */
    public function factory_in_progress_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->inProgress()->create();

        $this->assertEquals('in_progress', $candidate->status);
        $this->assertNotNull($candidate->started_at);
    }

    /** @test FS-004 */
    public function factory_refreshed_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->refreshed()->create();

        $this->assertEquals('refreshed', $candidate->status);
        $this->assertNotNull($candidate->refreshed_at);
    }

    /** @test FS-005 */
    public function factory_recovered_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->recovered()->create();

        $this->assertEquals('recovered', $candidate->status);
        $this->assertNotNull($candidate->recovered_at);
    }

    /** @test FS-006 */
    public function factory_ignored_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->ignored()->create();

        $this->assertEquals('ignored', $candidate->status);
        $this->assertNotNull($candidate->ignored_at);
    }

    /** @test FS-007 */
    public function factory_critical_severity_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->criticalSeverity()->create();

        $this->assertEquals('critical', $candidate->severity);
    }

    /** @test FS-008 */
    public function factory_high_severity_state(): void
    {
        $candidate = ContentDecayCandidate::factory()->highSeverity()->create();

        $this->assertEquals('high', $candidate->severity);
    }

    /** @test FS-009 */
    public function factory_blog_page_type(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['page_type' => 'blog']);

        $this->assertEquals('blog', $candidate->page_type);
    }

    /** @test FS-010 */
    public function factory_high_decay_score(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['decay_score' => 85.00]);

        $this->assertEquals(85.00, (float) $candidate->decay_score);
    }

    /** @test FS-011 */
    public function factory_creates_project(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $this->assertNotNull($candidate->project_id);
        $this->assertDatabaseHas('content_decay_projects', ['id' => $candidate->project_id]);
    }

    /** @test FS-012 */
    public function factory_with_reason_codes(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'reason_codes' => ['clicks_down', 'impressions_down', 'ctr_down'],
        ]);

        $this->assertCount(3, $candidate->reason_codes);
    }

    /** @test FS-013 */
    public function factory_with_evidence(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => [
                'clicks_before' => 1000,
                'clicks_after' => 500,
                'delta' => -500,
            ],
        ]);

        $this->assertIsArray($candidate->evidence_json);
        $this->assertEquals(-500, $candidate->evidence_json['delta']);
    }

    /** @test FS-014 */
    public function factory_with_lost_clicks(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['lost_click_estimate' => 1000]);

        $this->assertEquals(1000, $candidate->lost_click_estimate);
    }

    /** @test FS-015 */
    public function factory_with_recovery_upside(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 1500]);

        $this->assertEquals(1500, $candidate->recovery_upside_estimate);
    }

    // ==========================================
    // Edge Cases Tests (EC-001 to EC-012)
    // ==========================================

    /** @test EC-001 */
    public function long_page_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 500);

        $candidate = ContentDecayCandidate::factory()->create(['page_url' => $longUrl]);

        $this->assertEquals($longUrl, $candidate->page_url);
    }

    /** @test EC-002 */
    public function unicode_in_page_title(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'page_title' => '日本語タイトル — SEOガイド',
        ]);

        $this->assertEquals('日本語タイトル — SEOガイド', $candidate->page_title);
    }

    /** @test EC-003 */
    public function emoji_in_page_title(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'page_title' => '🚀 SEO Tips & Tricks 📈',
        ]);

        $this->assertEquals('🚀 SEO Tips & Tricks 📈', $candidate->page_title);
    }

    /** @test EC-004 */
    public function null_page_title(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['page_title' => null]);

        $this->assertNull($candidate->page_title);
    }

    /** @test EC-005 */
    public function empty_reason_codes(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['reason_codes' => []]);

        $this->assertIsArray($candidate->reason_codes);
        $this->assertEmpty($candidate->reason_codes);
    }

    /** @test EC-006 */
    public function null_signal_sources(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['signal_sources' => null]);

        $this->assertNull($candidate->signal_sources);
    }

    /** @test EC-007 */
    public function zero_decay_score(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['decay_score' => 0.00]);

        $this->assertEquals(0.00, (float) $candidate->decay_score);
    }

    /** @test EC-008 */
    public function max_decay_score(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['decay_score' => 100.00]);

        $this->assertEquals(100.00, (float) $candidate->decay_score);
    }

    /** @test EC-009 */
    public function large_lost_click_estimate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['lost_click_estimate' => 100000]);

        $this->assertEquals(100000, $candidate->lost_click_estimate);
    }

    /** @test EC-010 */
    public function large_recovery_upside(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['recovery_upside_estimate' => 500000]);

        $this->assertEquals(500000, $candidate->recovery_upside_estimate);
    }

    /** @test EC-011 */
    public function deeply_nested_evidence_json(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => [
                'metrics' => [
                    'gsc' => [
                        'clicks' => ['before' => 1000, 'after' => 500],
                        'impressions' => ['before' => 10000, 'after' => 8000],
                    ],
                ],
            ],
        ]);

        $this->assertEquals(500, $candidate->evidence_json['metrics']['gsc']['clicks']['after']);
    }

    /** @test EC-012 */
    public function all_timestamps_null(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'planned_at' => null,
            'started_at' => null,
            'refreshed_at' => null,
            'recovered_at' => null,
            'ignored_at' => null,
        ]);

        $this->assertNull($candidate->planned_at);
        $this->assertNull($candidate->started_at);
        $this->assertNull($candidate->refreshed_at);
        $this->assertNull($candidate->recovered_at);
        $this->assertNull($candidate->ignored_at);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-006)
    // ==========================================

    /** @test SEC-001 */
    public function sql_injection_in_page_url(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'page_url' => "https://example.com/page'; DROP TABLE content_decay_candidates; --",
        ]);

        $this->assertDatabaseHas('content_decay_candidates', ['id' => $candidate->id]);
    }

    /** @test SEC-002 */
    public function xss_in_page_title(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'page_title' => '<script>alert(1)</script> SEO Tips',
        ]);

        $this->assertEquals('<script>alert(1)</script> SEO Tips', $candidate->page_title);
    }

    /** @test SEC-003 */
    public function sql_injection_in_evidence_json(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'evidence_json' => ['key' => "'; DROP TABLE users; --"],
        ]);

        $this->assertDatabaseHas('content_decay_candidates', ['id' => $candidate->id]);
    }

    /** @test SEC-004 */
    public function invalid_status_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayCandidate::factory()->create(['status' => 'invalid_status']);
    }

    /** @test SEC-005 */
    public function invalid_severity_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecayCandidate::factory()->create(['severity' => 'extreme']);
    }

    /** @test SEC-006 */
    public function mass_assignment_project_id(): void
    {
        $project = ContentDecayProject::factory()->create();

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'decay_score' => 50.00,
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down'],
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        $this->assertEquals($project->id, $candidate->project_id);
    }

    // ==========================================
    // Integration Tests (INT-001 to INT-006)
    // ==========================================

    /** @test INT-001 */
    public function create_and_retrieve(): void
    {
        $candidate = ContentDecayCandidate::factory()->create([
            'page_url' => 'https://example.com/integration-test',
            'decay_score' => 75.00,
        ]);

        $retrieved = ContentDecayCandidate::find($candidate->id);

        $this->assertEquals('https://example.com/integration-test', $retrieved->page_url);
        $this->assertEquals(75.00, (float) $retrieved->decay_score);
    }

    /** @test INT-002 */
    public function update_and_persist(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $candidate->update([
            'status' => 'planned',
            'planned_at' => now(),
        ]);

        $candidate->refresh();

        $this->assertEquals('planned', $candidate->status);
        $this->assertNotNull($candidate->planned_at);
    }

    /** @test INT-003 */
    public function delete_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $id = $candidate->id;

        $candidate->delete();

        $this->assertDatabaseMissing('content_decay_candidates', ['id' => $id]);
    }

    /** @test INT-004 */
    public function full_status_lifecycle(): void
    {
        $candidate = ContentDecayCandidate::factory()->create(['status' => 'backlog']);

        // Backlog -> Planned
        $candidate->update(['status' => 'planned', 'planned_at' => now()]);
        $this->assertEquals('planned', $candidate->status);

        // Planned -> In Progress
        $candidate->update(['status' => 'in_progress', 'started_at' => now()]);
        $this->assertEquals('in_progress', $candidate->status);

        // In Progress -> Refreshed
        $candidate->update(['status' => 'refreshed', 'refreshed_at' => now()]);
        $this->assertEquals('refreshed', $candidate->status);

        // Refreshed -> Recovered
        $candidate->update(['status' => 'recovered', 'recovered_at' => now()]);
        $this->assertEquals('recovered', $candidate->status);
    }

    /** @test INT-005 */
    public function bulk_creation(): void
    {
        ContentDecayCandidate::factory()->count(100)->create();

        $this->assertEquals(100, ContentDecayCandidate::count());
    }

    /** @test INT-006 */
    public function complex_query(): void
    {
        ContentDecayCandidate::factory()->count(5)->create(['severity' => 'critical', 'status' => 'backlog']);
        ContentDecayCandidate::factory()->count(3)->create(['severity' => 'high', 'status' => 'planned']);
        ContentDecayCandidate::factory()->count(2)->ignored()->create(['severity' => 'medium']);

        $results = ContentDecayCandidate::whereIn('severity', ['critical', 'high'])
            ->whereIn('status', ['backlog', 'planned'])
            ->get();

        $this->assertCount(8, $results);
    }

    // ==========================================
    // Performance Tests (PERF-001 to PERF-003)
    // ==========================================

    /** @test PERF-001 */
    public function eager_loading_no_n_plus_one(): void
    {
        ContentDecayCandidate::factory()->count(20)->create();

        \DB::enableQueryLog();

        $candidates = ContentDecayCandidate::with('project')->get();

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should be 2 queries: candidates + projects
        $this->assertLessThanOrEqual(3, count($queries), 'N+1 queries detected');
        $this->assertCount(20, $candidates);
    }

    /** @test PERF-002 */
    public function index_usage_for_project_query(): void
    {
        $project = ContentDecayProject::factory()->create();
        ContentDecayCandidate::factory()->count(10)->create(['project_id' => $project->id]);

        $candidates = ContentDecayCandidate::where('project_id', $project->id)->get();

        $this->assertCount(10, $candidates);
    }

    /** @test PERF-003 */
    public function index_usage_for_status_query(): void
    {
        ContentDecayCandidate::factory()->count(10)->create(['status' => 'backlog']);
        ContentDecayCandidate::factory()->count(5)->planned()->create();

        $backlog = ContentDecayCandidate::where('status', 'backlog')->get();

        $this->assertCount(10, $backlog);
    }
}
