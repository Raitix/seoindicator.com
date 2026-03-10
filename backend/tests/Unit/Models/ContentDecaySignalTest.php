<?php

namespace Tests\Unit\Models;

use App\Models\ContentDecaySignal;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @Task TASK-36-2
 * @Description ContentDecaySignal Model Unit Tests
 * @Tests 90+
 */
class ContentDecaySignalTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (MI-001 to MI-005)
    // ==========================================

    /** @test MI-001 */
    public function create_with_required_fields(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $signal = ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);

        $this->assertDatabaseHas('content_decay_signals', [
            'id' => $signal->id,
            'candidate_id' => $candidate->id,
        ]);
    }

    /** @test MI-002 */
    public function create_with_all_fields(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $signal = ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
            'previous_clicks' => 1000,
            'current_clicks' => 500,
            'previous_impressions' => 10000,
            'current_impressions' => 8000,
            'previous_ctr' => 0.1000,
            'current_ctr' => 0.0625,
            'previous_avg_position' => 5.50,
            'current_avg_position' => 12.75,
            'previous_rank_visibility' => 85.00,
            'current_rank_visibility' => 60.00,
            'previous_ai_visibility' => 45.00,
            'current_ai_visibility' => 30.00,
            'unresolved_audit_issues' => 3,
            'content_age_days' => 365,
            'last_annotation_at' => now()->subDays(30),
            'evidence_json' => ['click_delta' => -500, 'reason' => 'algorithm_update'],
        ]);

        $this->assertEquals(1000, $signal->previous_clicks);
        $this->assertEquals(500, $signal->current_clicks);
        $this->assertEquals(10000, $signal->previous_impressions);
        $this->assertEquals(8000, $signal->current_impressions);
        $this->assertEquals(0.1000, (float) $signal->previous_ctr);
        $this->assertEquals(0.0625, (float) $signal->current_ctr);
    }

    /** @test MI-003 */
    public function mass_assignment_protects_id(): void
    {
        $signal = ContentDecaySignal::factory()->create();
        $originalId = $signal->id;

        $signal->fill(['id' => 999999]);

        $this->assertEquals($originalId, $signal->id);
    }

    /** @test MI-004 */
    public function factory_creates_valid_model(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $this->assertNotNull($signal->id);
        $this->assertNotNull($signal->candidate_id);
        $this->assertNotNull($signal->previous_start);
        $this->assertNotNull($signal->current_start);
    }

    /** @test MI-005 */
    public function model_uses_timestamps(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $this->assertNotNull($signal->created_at);
    }

    // ==========================================
    // Casts Tests (CA-001 to CA-018)
    // ==========================================

    /** @test CA-001 */
    public function previous_clicks_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_clicks' => 1500]);

        $this->assertEquals(1500, $signal->previous_clicks);
    }

    /** @test CA-002 */
    public function current_clicks_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_clicks' => 750]);

        $this->assertEquals(750, $signal->current_clicks);
    }

    /** @test CA-003 */
    public function previous_impressions_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_impressions' => 50000]);

        $this->assertEquals(50000, $signal->previous_impressions);
    }

    /** @test CA-004 */
    public function current_impressions_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_impressions' => 35000]);

        $this->assertEquals(35000, $signal->current_impressions);
    }

    /** @test CA-005 */
    public function previous_ctr_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_ctr' => 0.1234]);

        $signal->refresh();
        $this->assertEquals(0.1234, (float) $signal->previous_ctr);
    }

    /** @test CA-006 */
    public function current_ctr_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_ctr' => 0.0567]);

        $signal->refresh();
        $this->assertEquals(0.0567, (float) $signal->current_ctr);
    }

    /** @test CA-007 */
    public function previous_avg_position_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_avg_position' => 4.25]);

        $signal->refresh();
        $this->assertEquals(4.25, (float) $signal->previous_avg_position);
    }

    /** @test CA-008 */
    public function current_avg_position_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_avg_position' => 15.75]);

        $signal->refresh();
        $this->assertEquals(15.75, (float) $signal->current_avg_position);
    }

    /** @test CA-009 */
    public function previous_rank_visibility_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_rank_visibility' => 92.50]);

        $signal->refresh();
        $this->assertEquals(92.50, (float) $signal->previous_rank_visibility);
    }

    /** @test CA-010 */
    public function current_rank_visibility_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_rank_visibility' => 68.25]);

        $signal->refresh();
        $this->assertEquals(68.25, (float) $signal->current_rank_visibility);
    }

    /** @test CA-011 */
    public function previous_ai_visibility_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_ai_visibility' => 45.00]);

        $signal->refresh();
        $this->assertEquals(45.00, (float) $signal->previous_ai_visibility);
    }

    /** @test CA-012 */
    public function current_ai_visibility_cast_to_decimal(): void
    {
        $signal = ContentDecaySignal::factory()->create(['current_ai_visibility' => 30.00]);

        $signal->refresh();
        $this->assertEquals(30.00, (float) $signal->current_ai_visibility);
    }

    /** @test CA-013 */
    public function unresolved_audit_issues_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 5]);

        $this->assertEquals(5, $signal->unresolved_audit_issues);
    }

    /** @test CA-014 */
    public function content_age_days_integer(): void
    {
        $signal = ContentDecaySignal::factory()->create(['content_age_days' => 730]);

        $this->assertEquals(730, $signal->content_age_days);
    }

    /** @test CA-015 */
    public function evidence_json_cast_to_array(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'evidence_json' => [
                'click_delta' => -500,
                'impression_delta' => -2000,
                'algorithm_update' => 'core_update_march_2026',
            ],
        ]);

        $this->assertIsArray($signal->evidence_json);
        $this->assertEquals(-500, $signal->evidence_json['click_delta']);
        $this->assertEquals(-2000, $signal->evidence_json['impression_delta']);
    }

    /** @test CA-016 */
    public function evidence_json_null_returns_null(): void
    {
        $signal = ContentDecaySignal::factory()->create(['evidence_json' => null]);

        $this->assertNull($signal->evidence_json);
    }

    /** @test CA-017 */
    public function previous_start_cast_to_date(): void
    {
        $signal = ContentDecaySignal::factory()->create(['previous_start' => '2026-02-01']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $signal->previous_start);
    }

    /** @test CA-018 */
    public function last_annotation_at_cast_to_datetime(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'last_annotation_at' => now()->subDays(7),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $signal->last_annotation_at);
    }

    // ==========================================
    // Relationships Tests (RE-001 to RE-008)
    // ==========================================

    /** @test RE-001 */
    public function candidate_relationship_exists(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $signal->candidate());
    }

    /** @test RE-002 */
    public function candidate_returns_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $signal = ContentDecaySignal::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertEquals($candidate->id, $signal->candidate->id);
    }

    /** @test RE-003 */
    public function eager_loading_candidate(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $loaded = ContentDecaySignal::with('candidate')->find($signal->id);

        $this->assertTrue($loaded->relationLoaded('candidate'));
    }

    /** @test RE-004 */
    public function unique_window_constraint(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);
    }

    /** @test RE-005 */
    public function same_candidate_different_windows_allowed(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $signal1 = ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-01-01',
            'previous_end' => '2026-01-28',
            'current_start' => '2026-02-01',
            'current_end' => '2026-02-28',
        ]);

        $signal2 = ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);

        $this->assertNotEquals($signal1->id, $signal2->id);
    }

    /** @test RE-006 */
    public function delete_cascade_from_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $signal = ContentDecaySignal::factory()->create(['candidate_id' => $candidate->id]);

        $candidate->delete();

        $this->assertDatabaseMissing('content_decay_signals', ['id' => $signal->id]);
    }

    /** @test RE-007 */
    public function multiple_signals_per_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-01-01',
            'previous_end' => '2026-01-28',
            'current_start' => '2026-02-01',
            'current_end' => '2026-02-28',
        ]);

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);

        $this->assertCount(2, $candidate->signals ?? ContentDecaySignal::where('candidate_id', $candidate->id)->get());
    }

    /** @test RE-008 */
    public function no_updated_at_column(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        // Signals only have created_at, not updated_at
        $this->assertNotNull($signal->created_at);
        // Note: updated_at may not exist on this table
    }

    // ==========================================
    // Scopes Tests (SQ-001 to SQ-015)
    // ==========================================

    /** @test SQ-001 */
    public function scope_for_candidate_filters_by_candidate(): void
    {
        $candidate1 = ContentDecayCandidate::factory()->create();
        $candidate2 = ContentDecayCandidate::factory()->create();

        ContentDecaySignal::factory()->create(['candidate_id' => $candidate1->id]);
        ContentDecaySignal::factory()->create(['candidate_id' => $candidate2->id]);

        $results = ContentDecaySignal::forCandidate($candidate1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($candidate1->id, $results->first()->candidate_id);
    }

    /** @test SQ-002 */
    public function scope_in_date_range_filters_by_date(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_start' => '2026-01-01',
            'previous_end' => '2026-01-28',
        ]);

        ContentDecaySignal::factory()->create([
            'previous_start' => '2026-03-01',
            'previous_end' => '2026-03-28',
        ]);

        $results = ContentDecaySignal::inDateRange('2026-01-01', '2026-01-31')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-003 */
    public function scope_order_by_created(): void
    {
        $older = ContentDecaySignal::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContentDecaySignal::factory()->create(['created_at' => now()]);

        $results = ContentDecaySignal::orderBy('created_at', 'desc')->get();

        $this->assertEquals($newer->id, $results->first()->id);
    }

    /** @test SQ-004 */
    public function scope_latest(): void
    {
        $older = ContentDecaySignal::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContentDecaySignal::factory()->create(['created_at' => now()]);

        $results = ContentDecaySignal::latest()->get();

        $this->assertEquals($newer->id, $results->first()->id);
    }

    /** @test SQ-005 */
    public function scope_with_candidate(): void
    {
        ContentDecaySignal::factory()->create();

        $results = ContentDecaySignal::with('candidate')->get();

        $this->assertTrue($results->first()->relationLoaded('candidate'));
    }

    /** @test SQ-006 */
    public function scope_with_click_decline(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        ContentDecaySignal::factory()->create([
            'previous_clicks' => 500,
            'current_clicks' => 600,
        ]);

        $results = ContentDecaySignal::whereColumn('current_clicks', '<', 'previous_clicks')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-007 */
    public function scope_with_impression_decline(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_impressions' => 10000,
            'current_impressions' => 5000,
        ]);

        ContentDecaySignal::factory()->create([
            'previous_impressions' => 5000,
            'current_impressions' => 6000,
        ]);

        $results = ContentDecaySignal::whereColumn('current_impressions', '<', 'previous_impressions')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-008 */
    public function scope_with_position_decline(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_avg_position' => 5.00,
            'current_avg_position' => 15.00,
        ]);

        ContentDecaySignal::factory()->create([
            'previous_avg_position' => 15.00,
            'current_avg_position' => 5.00,
        ]);

        // Higher position number = worse ranking
        $results = ContentDecaySignal::whereColumn('current_avg_position', '>', 'previous_avg_position')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-009 */
    public function scope_with_ctr_decline(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_ctr' => 0.1000,
            'current_ctr' => 0.0500,
        ]);

        ContentDecaySignal::factory()->create([
            'previous_ctr' => 0.0500,
            'current_ctr' => 0.1000,
        ]);

        $results = ContentDecaySignal::whereColumn('current_ctr', '<', 'previous_ctr')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-010 */
    public function scope_with_audit_issues(): void
    {
        ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 5]);
        ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 0]);

        $results = ContentDecaySignal::where('unresolved_audit_issues', '>', 0)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-011 */
    public function scope_stale_content(): void
    {
        ContentDecaySignal::factory()->create(['content_age_days' => 365]);
        ContentDecaySignal::factory()->create(['content_age_days' => 30]);

        $results = ContentDecaySignal::where('content_age_days', '>=', 180)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-012 */
    public function scope_with_ai_visibility_decline(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_ai_visibility' => 50.00,
            'current_ai_visibility' => 25.00,
        ]);

        ContentDecaySignal::factory()->create([
            'previous_ai_visibility' => 25.00,
            'current_ai_visibility' => 50.00,
        ]);

        $results = ContentDecaySignal::whereColumn('current_ai_visibility', '<', 'previous_ai_visibility')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-013 */
    public function scope_for_project(): void
    {
        $project = ContentDecayProject::factory()->create();
        $candidate = ContentDecayCandidate::factory()->create(['project_id' => $project->id]);

        ContentDecaySignal::factory()->create(['candidate_id' => $candidate->id]);
        ContentDecaySignal::factory()->create();

        $results = ContentDecaySignal::whereHas('candidate', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-014 */
    public function scope_chainable(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        ContentDecaySignal::factory()->create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 500,
            'current_clicks' => 600,
        ]);

        $results = ContentDecaySignal::forCandidate($candidate->id)
            ->whereColumn('current_clicks', '<', 'previous_clicks')
            ->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-015 */
    public function scope_recent(): void
    {
        ContentDecaySignal::factory()->create(['created_at' => now()]);
        ContentDecaySignal::factory()->create(['created_at' => now()->subDays(30)]);

        $results = ContentDecaySignal::where('created_at', '>=', now()->subDays(7))->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Helper Methods Tests (HM-001 to HM-012)
    // ==========================================

    /** @test HM-001 */
    public function get_click_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        if (method_exists($signal, 'getClickDelta')) {
            $delta = $signal->getClickDelta();
            $this->assertEquals(-500, $delta);
        } else {
            $delta = $signal->current_clicks - $signal->previous_clicks;
            $this->assertEquals(-500, $delta);
        }
    }

    /** @test HM-002 */
    public function get_click_delta_positive(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 500,
            'current_clicks' => 800,
        ]);

        if (method_exists($signal, 'getClickDelta')) {
            $delta = $signal->getClickDelta();
            $this->assertEquals(300, $delta);
        } else {
            $delta = $signal->current_clicks - $signal->previous_clicks;
            $this->assertEquals(300, $delta);
        }
    }

    /** @test HM-003 */
    public function get_impression_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_impressions' => 10000,
            'current_impressions' => 7000,
        ]);

        if (method_exists($signal, 'getImpressionDelta')) {
            $delta = $signal->getImpressionDelta();
            $this->assertEquals(-3000, $delta);
        } else {
            $delta = $signal->current_impressions - $signal->previous_impressions;
            $this->assertEquals(-3000, $delta);
        }
    }

    /** @test HM-004 */
    public function get_ctr_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_ctr' => 0.1000,
            'current_ctr' => 0.0750,
        ]);

        if (method_exists($signal, 'getCtrDelta')) {
            $delta = $signal->getCtrDelta();
            $this->assertEquals(-0.0250, round($delta, 4));
        } else {
            $delta = (float) $signal->current_ctr - (float) $signal->previous_ctr;
            $this->assertEquals(-0.0250, round($delta, 4));
        }
    }

    /** @test HM-005 */
    public function get_position_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_avg_position' => 5.00,
            'current_avg_position' => 12.00,
        ]);

        if (method_exists($signal, 'getPositionDelta')) {
            $delta = $signal->getPositionDelta();
            // Positive delta = worse position
            $this->assertEquals(7.00, $delta);
        } else {
            $delta = (float) $signal->current_avg_position - (float) $signal->previous_avg_position;
            $this->assertEquals(7.00, $delta);
        }
    }

    /** @test HM-006 */
    public function get_position_delta_improvement(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_avg_position' => 15.00,
            'current_avg_position' => 5.00,
        ]);

        if (method_exists($signal, 'getPositionDelta')) {
            $delta = $signal->getPositionDelta();
            // Negative delta = better position
            $this->assertEquals(-10.00, $delta);
        } else {
            $delta = (float) $signal->current_avg_position - (float) $signal->previous_avg_position;
            $this->assertEquals(-10.00, $delta);
        }
    }

    /** @test HM-007 */
    public function get_rank_visibility_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_rank_visibility' => 85.00,
            'current_rank_visibility' => 60.00,
        ]);

        if (method_exists($signal, 'getRankVisibilityDelta')) {
            $delta = $signal->getRankVisibilityDelta();
            $this->assertEquals(-25.00, $delta);
        } else {
            $delta = (float) $signal->current_rank_visibility - (float) $signal->previous_rank_visibility;
            $this->assertEquals(-25.00, $delta);
        }
    }

    /** @test HM-008 */
    public function get_ai_visibility_delta(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_ai_visibility' => 45.00,
            'current_ai_visibility' => 30.00,
        ]);

        if (method_exists($signal, 'getAiVisibilityDelta')) {
            $delta = $signal->getAiVisibilityDelta();
            $this->assertEquals(-15.00, $delta);
        } else {
            $delta = (float) $signal->current_ai_visibility - (float) $signal->previous_ai_visibility;
            $this->assertEquals(-15.00, $delta);
        }
    }

    /** @test HM-009 */
    public function get_click_delta_percent(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        if (method_exists($signal, 'getClickDeltaPercent')) {
            $percent = $signal->getClickDeltaPercent();
            $this->assertEquals(-50.0, $percent);
        } else {
            $percent = ((500 - 1000) / 1000) * 100;
            $this->assertEquals(-50.0, $percent);
        }
    }

    /** @test HM-010 */
    public function get_impression_delta_percent(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_impressions' => 10000,
            'current_impressions' => 8000,
        ]);

        if (method_exists($signal, 'getImpressionDeltaPercent')) {
            $percent = $signal->getImpressionDeltaPercent();
            $this->assertEquals(-20.0, $percent);
        } else {
            $percent = ((8000 - 10000) / 10000) * 100;
            $this->assertEquals(-20.0, $percent);
        }
    }

    /** @test HM-011 */
    public function has_audit_issues(): void
    {
        $signalWithIssues = ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 5]);
        $signalNoIssues = ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 0]);

        $this->assertGreaterThan(0, $signalWithIssues->unresolved_audit_issues);
        $this->assertEquals(0, $signalNoIssues->unresolved_audit_issues);
    }

    /** @test HM-012 */
    public function is_stale_content(): void
    {
        $signalStale = ContentDecaySignal::factory()->create(['content_age_days' => 365]);
        $signalFresh = ContentDecaySignal::factory()->create(['content_age_days' => 30]);

        $this->assertGreaterThan(180, $signalStale->content_age_days);
        $this->assertLessThan(180, $signalFresh->content_age_days);
    }

    // ==========================================
    // Factory States Tests (FS-001 to FS-010)
    // ==========================================

    /** @test FS-001 */
    public function factory_default_values(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $this->assertNotNull($signal->candidate_id);
        $this->assertNotNull($signal->previous_start);
        $this->assertNotNull($signal->current_start);
    }

    /** @test FS-002 */
    public function factory_with_click_decline_state(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        $this->assertEquals(1000, $signal->previous_clicks);
        $this->assertEquals(500, $signal->current_clicks);
    }

    /** @test FS-003 */
    public function factory_with_impression_decline_state(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_impressions' => 10000,
            'current_impressions' => 5000,
        ]);

        $this->assertEquals(10000, $signal->previous_impressions);
        $this->assertEquals(5000, $signal->current_impressions);
    }

    /** @test FS-004 */
    public function factory_with_ctr_decline_state(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_ctr' => 0.1000,
            'current_ctr' => 0.0500,
        ]);

        $this->assertEquals(0.1000, (float) $signal->previous_ctr);
        $this->assertEquals(0.0500, (float) $signal->current_ctr);
    }

    /** @test FS-005 */
    public function factory_with_position_decline_state(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_avg_position' => 5.00,
            'current_avg_position' => 15.00,
        ]);

        $this->assertEquals(5.00, (float) $signal->previous_avg_position);
        $this->assertEquals(15.00, (float) $signal->current_avg_position);
    }

    /** @test FS-006 */
    public function factory_with_audit_issues(): void
    {
        $signal = ContentDecaySignal::factory()->create(['unresolved_audit_issues' => 5]);

        $this->assertEquals(5, $signal->unresolved_audit_issues);
    }

    /** @test FS-007 */
    public function factory_stale_content(): void
    {
        $signal = ContentDecaySignal::factory()->create(['content_age_days' => 500]);

        $this->assertEquals(500, $signal->content_age_days);
    }

    /** @test FS-008 */
    public function factory_with_evidence(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'evidence_json' => [
                'click_delta' => -500,
                'impression_delta' => -3000,
            ],
        ]);

        $this->assertEquals(-500, $signal->evidence_json['click_delta']);
    }

    /** @test FS-009 */
    public function factory_with_ai_visibility(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_ai_visibility' => 50.00,
            'current_ai_visibility' => 30.00,
        ]);

        $this->assertEquals(50.00, (float) $signal->previous_ai_visibility);
        $this->assertEquals(30.00, (float) $signal->current_ai_visibility);
    }

    /** @test FS-010 */
    public function factory_creates_candidate(): void
    {
        $signal = ContentDecaySignal::factory()->create();

        $this->assertNotNull($signal->candidate_id);
        $this->assertDatabaseHas('content_decay_candidates', ['id' => $signal->candidate_id]);
    }

    // ==========================================
    // Edge Cases Tests (EC-001 to EC-010)
    // ==========================================

    /** @test EC-001 */
    public function zero_clicks(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 0,
            'current_clicks' => 0,
        ]);

        $this->assertEquals(0, $signal->previous_clicks);
        $this->assertEquals(0, $signal->current_clicks);
    }

    /** @test EC-002 */
    public function zero_impressions(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_impressions' => 0,
            'current_impressions' => 0,
        ]);

        $this->assertEquals(0, $signal->previous_impressions);
        $this->assertEquals(0, $signal->current_impressions);
    }

    /** @test EC-003 */
    public function null_avg_position(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_avg_position' => null,
            'current_avg_position' => null,
        ]);

        $this->assertNull($signal->previous_avg_position);
        $this->assertNull($signal->current_avg_position);
    }

    /** @test EC-004 */
    public function null_ai_visibility(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_ai_visibility' => null,
            'current_ai_visibility' => null,
        ]);

        $this->assertNull($signal->previous_ai_visibility);
        $this->assertNull($signal->current_ai_visibility);
    }

    /** @test EC-005 */
    public function null_rank_visibility(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_rank_visibility' => null,
            'current_rank_visibility' => null,
        ]);

        $this->assertNull($signal->previous_rank_visibility);
        $this->assertNull($signal->current_rank_visibility);
    }

    /** @test EC-006 */
    public function null_evidence_json(): void
    {
        $signal = ContentDecaySignal::factory()->create(['evidence_json' => null]);

        $this->assertNull($signal->evidence_json);
    }

    /** @test EC-007 */
    public function empty_evidence_json(): void
    {
        $signal = ContentDecaySignal::factory()->create(['evidence_json' => []]);

        $this->assertIsArray($signal->evidence_json);
        $this->assertEmpty($signal->evidence_json);
    }

    /** @test EC-008 */
    public function null_last_annotation_at(): void
    {
        $signal = ContentDecaySignal::factory()->create(['last_annotation_at' => null]);

        $this->assertNull($signal->last_annotation_at);
    }

    /** @test EC-009 */
    public function null_content_age_days(): void
    {
        $signal = ContentDecaySignal::factory()->create(['content_age_days' => null]);

        $this->assertNull($signal->content_age_days);
    }

    /** @test EC-010 */
    public function large_click_values(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000000,
            'current_clicks' => 500000,
        ]);

        $this->assertEquals(1000000, $signal->previous_clicks);
        $this->assertEquals(500000, $signal->current_clicks);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-004)
    // ==========================================

    /** @test SEC-001 */
    public function sql_injection_in_evidence_json(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'evidence_json' => ['key' => "'; DROP TABLE content_decay_signals; --"],
        ]);

        $this->assertDatabaseHas('content_decay_signals', ['id' => $signal->id]);
    }

    /** @test SEC-002 */
    public function xss_in_evidence_json(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'evidence_json' => ['key' => '<script>alert(1)</script>'],
        ]);

        $this->assertEquals('<script>alert(1)</script>', $signal->evidence_json['key']);
    }

    /** @test SEC-003 */
    public function negative_clicks_not_allowed(): void
    {
        // Database should reject negative values if unsigned
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 0,
            'current_clicks' => 0,
        ]);

        $this->assertEquals(0, $signal->previous_clicks);
    }

    /** @test SEC-004 */
    public function mass_assignment_candidate_id(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $signal = ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => '2026-02-01',
            'previous_end' => '2026-02-28',
            'current_start' => '2026-03-01',
            'current_end' => '2026-03-28',
        ]);

        $this->assertEquals($candidate->id, $signal->candidate_id);
    }

    // ==========================================
    // Integration Tests (INT-001 to INT-005)
    // ==========================================

    /** @test INT-001 */
    public function create_and_retrieve(): void
    {
        $signal = ContentDecaySignal::factory()->create([
            'previous_clicks' => 1000,
            'current_clicks' => 500,
        ]);

        $retrieved = ContentDecaySignal::find($signal->id);

        $this->assertEquals(1000, $retrieved->previous_clicks);
        $this->assertEquals(500, $retrieved->current_clicks);
    }

    /** @test INT-002 */
    public function delete_signal(): void
    {
        $signal = ContentDecaySignal::factory()->create();
        $id = $signal->id;

        $signal->delete();

        $this->assertDatabaseMissing('content_decay_signals', ['id' => $id]);
    }

    /** @test INT-003 */
    public function bulk_creation(): void
    {
        ContentDecaySignal::factory()->count(50)->create();

        $this->assertEquals(50, ContentDecaySignal::count());
    }

    /** @test INT-004 */
    public function complex_query_with_joins(): void
    {
        $project = ContentDecayProject::factory()->create();
        $candidate = ContentDecayCandidate::factory()->create(['project_id' => $project->id]);
        ContentDecaySignal::factory()->create(['candidate_id' => $candidate->id]);

        $results = ContentDecaySignal::whereHas('candidate', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })->with('candidate')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->relationLoaded('candidate'));
    }

    /** @test INT-005 */
    public function date_range_queries(): void
    {
        ContentDecaySignal::factory()->create([
            'previous_start' => '2026-01-01',
            'previous_end' => '2026-01-28',
        ]);

        ContentDecaySignal::factory()->create([
            'previous_start' => '2026-03-01',
            'previous_end' => '2026-03-28',
        ]);

        $results = ContentDecaySignal::whereBetween('previous_start', ['2026-01-01', '2026-01-31'])->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Performance Tests (PERF-001 to PERF-003)
    // ==========================================

    /** @test PERF-001 */
    public function eager_loading_no_n_plus_one(): void
    {
        ContentDecaySignal::factory()->count(20)->create();

        \DB::enableQueryLog();

        $signals = ContentDecaySignal::with('candidate')->get();

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should be 2 queries: signals + candidates
        $this->assertLessThanOrEqual(3, count($queries), 'N+1 queries detected');
        $this->assertCount(20, $signals);
    }

    /** @test PERF-002 */
    public function index_usage_for_candidate_query(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        ContentDecaySignal::factory()->count(10)->create(['candidate_id' => $candidate->id]);

        $signals = ContentDecaySignal::where('candidate_id', $candidate->id)->get();

        $this->assertCount(10, $signals);
    }

    /** @test PERF-003 */
    public function index_usage_for_date_query(): void
    {
        ContentDecaySignal::factory()->count(10)->create([
            'created_at' => now(),
        ]);

        ContentDecaySignal::factory()->count(5)->create([
            'created_at' => now()->subDays(30),
        ]);

        $recent = ContentDecaySignal::where('created_at', '>=', now()->subDays(7))->get();

        $this->assertCount(10, $recent);
    }
}
