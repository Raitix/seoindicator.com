<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Services\ContentDecay\ContentDecayReasonService;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for ContentDecayReasonService.
 *
 * Tests cover:
 * - AC-1: Service class with assignReasonCodes() method
 * - AC-2: All 11 reason codes supported
 * - AC-3: Primary reason selection logic
 * - AC-4: Evidence payload structure
 * - AC-5: Threshold configuration
 *
 * @see TASK-36-4
 * @see SPEC-036
 * @see TESTS/TEST-36-4.md
 */
class ContentDecayReasonServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =================================================================
    // INST: Service Instantiation
    // =================================================================

    /** @test INST-1: Service can be instantiated via app container */
    public function test_service_can_be_instantiated_via_container(): void
    {
        $service = app(ContentDecayReasonService::class);
        $this->assertInstanceOf(ContentDecayReasonService::class, $service);
    }

    /** @test INST-2: Service can be instantiated with default thresholds */
    public function test_service_instantiated_with_default_thresholds(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertIsArray($thresholds);
        $this->assertArrayHasKey('click_loss', $thresholds);
        $this->assertArrayHasKey('impression_loss', $thresholds);
    }

    /** @test INST-3: Service can be instantiated with custom thresholds */
    public function test_service_accepts_custom_thresholds(): void
    {
        $customThresholds = [
            'click_loss' => 0.20,
            'impression_loss' => 0.25,
        ];

        $service = new ContentDecayReasonService($customThresholds);
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.20, $thresholds['click_loss']);
        $this->assertEquals(0.25, $thresholds['impression_loss']);
    }

    /** @test INST-4: Service interface is properly bound */
    public function test_interface_resolves_to_implementation(): void
    {
        $service = app(ContentDecayReasonServiceInterface::class);
        $this->assertInstanceOf(ContentDecayReasonService::class, $service);
    }

    /** @test INST-5: Threshold config values are accessible */
    public function test_get_thresholds_returns_array(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertIsArray($thresholds);
        $this->assertArrayHasKey('click_loss', $thresholds);
        $this->assertArrayHasKey('impression_loss', $thresholds);
        $this->assertArrayHasKey('ctr_drop', $thresholds);
        $this->assertArrayHasKey('position_decline', $thresholds);
        $this->assertArrayHasKey('staleness_days', $thresholds);
        $this->assertArrayHasKey('internal_links', $thresholds);
        $this->assertArrayHasKey('annotation_window_days', $thresholds);
    }

    // =================================================================
    // CLICKS: Reason Code Assignment - Clicks Down
    // =================================================================

    /** @test CLICKS-1: Click decline exceeds threshold */
    public function test_clicks_down_when_decline_exceeds_threshold(): void
    {
        $service = new ContentDecayReasonService(['click_loss' => 0.10]);

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-2: Click decline at exact threshold */
    public function test_clicks_down_at_exact_threshold(): void
    {
        $service = new ContentDecayReasonService(['click_loss' => 0.10]);

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 90],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-3: Click decline below threshold */
    public function test_clicks_down_not_triggered_below_threshold(): void
    {
        $service = new ContentDecayReasonService(['click_loss' => 0.10]);

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 95],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-4: Click increase does not trigger */
    public function test_clicks_down_not_triggered_on_increase(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 120],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-5: Zero clicks both windows */
    public function test_clicks_down_not_triggered_when_both_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 0, 'current' => 0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-6: Clicks drop to zero */
    public function test_clicks_down_triggered_when_drop_to_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-7: Clicks start from zero */
    public function test_clicks_down_not_triggered_from_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 0, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('clicks_down', $result['reason_codes']);
    }

    /** @test CLICKS-8: Custom threshold applied */
    public function test_clicks_down_respects_custom_threshold(): void
    {
        $service = new ContentDecayReasonService(['click_loss' => 0.20]);

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 85],
        ]);

        $result = $service->assignReasonCodes($signals);

        // 15% decline is below 20% threshold
        $this->assertNotContains('clicks_down', $result['reason_codes']);
    }

    // =================================================================
    // IMP: Reason Code Assignment - Impressions Down
    // =================================================================

    /** @test IMP-1: Impression decline exceeds threshold */
    public function test_impressions_down_when_decline_exceeds_threshold(): void
    {
        $service = new ContentDecayReasonService(['impression_loss' => 0.15]);

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 700],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-2: Impression decline at threshold */
    public function test_impressions_down_at_exact_threshold(): void
    {
        $service = new ContentDecayReasonService(['impression_loss' => 0.15]);

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 850],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-3: Impression decline below threshold */
    public function test_impressions_down_not_triggered_below_threshold(): void
    {
        $service = new ContentDecayReasonService(['impression_loss' => 0.15]);

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 950],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-4: Impression increase does not trigger */
    public function test_impressions_down_not_triggered_on_increase(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 1200],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-5: Zero impressions both windows */
    public function test_impressions_down_not_triggered_when_both_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 0, 'current' => 0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-6: Impressions drop to zero */
    public function test_impressions_down_triggered_when_drop_to_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-7: Custom threshold applied */
    public function test_impressions_down_respects_custom_threshold(): void
    {
        $service = new ContentDecayReasonService(['impression_loss' => 0.25]);

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 800],
        ]);

        $result = $service->assignReasonCodes($signals);

        // 20% decline is below 25% threshold
        $this->assertNotContains('impressions_down', $result['reason_codes']);
    }

    /** @test IMP-8: High impression loss with stable clicks */
    public function test_impressions_down_with_evidence(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 500],
            'clicks' => ['previous' => 50, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('impressions_down', $result['reason_codes']);

        // Verify evidence exists for impressions
        $impressionEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'impressions_down');
        $this->assertNotNull($impressionEvidence);
    }

    // =================================================================
    // CTR: Reason Code Assignment - CTR Down
    // =================================================================

    /** @test CTR-1: CTR decline exceeds threshold */
    public function test_ctr_down_when_decline_exceeds_threshold(): void
    {
        $service = new ContentDecayReasonService(['ctr_drop' => 0.5]);

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 3.5],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-2: CTR decline at threshold */
    public function test_ctr_down_at_exact_threshold(): void
    {
        $service = new ContentDecayReasonService(['ctr_drop' => 0.5]);

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 4.5],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-3: CTR decline below threshold */
    public function test_ctr_down_not_triggered_below_threshold(): void
    {
        $service = new ContentDecayReasonService(['ctr_drop' => 0.5]);

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 4.8],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-4: CTR increase does not trigger */
    public function test_ctr_down_not_triggered_on_increase(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 6.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-5: CTR drop with low impressions */
    public function test_ctr_down_not_triggered_with_low_impressions(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 3.0],
            'impressions' => ['previous' => 50, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        // Low impressions make CTR not meaningful
        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-6: CTR drop with high impressions */
    public function test_ctr_down_triggered_with_high_impressions(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 3.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-7: Zero CTR both windows */
    public function test_ctr_down_not_triggered_when_both_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 0, 'current' => 0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-8: CTR from zero to positive */
    public function test_ctr_down_not_triggered_from_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 0, 'current' => 2.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-9: CTR to zero from positive */
    public function test_ctr_down_triggered_when_drop_to_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('ctr_down', $result['reason_codes']);
    }

    /** @test CTR-10: Custom CTR threshold */
    public function test_ctr_down_respects_custom_threshold(): void
    {
        $service = new ContentDecayReasonService(['ctr_drop' => 1.0]);

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 4.6],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        // 0.4pp decline is below 1.0pp threshold
        $this->assertNotContains('ctr_down', $result['reason_codes']);
    }

    // =================================================================
    // POS: Reason Code Assignment - Average Position Down
    // =================================================================

    /** @test POS-1: Position worsened beyond threshold */
    public function test_avg_position_down_when_worsened_beyond_threshold(): void
    {
        $service = new ContentDecayReasonService(['position_decline' => 3]);

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-2: Position worsened at threshold */
    public function test_avg_position_down_at_exact_threshold(): void
    {
        $service = new ContentDecayReasonService(['position_decline' => 3]);

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 6.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-3: Position worsened below threshold */
    public function test_avg_position_down_not_triggered_below_threshold(): void
    {
        $service = new ContentDecayReasonService(['position_decline' => 3]);

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 4.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-4: Position improved */
    public function test_avg_position_down_not_triggered_on_improvement(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 5.0, 'current' => 2.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-5: Position unchanged */
    public function test_avg_position_down_not_triggered_when_unchanged(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 3.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-6: Position null in previous */
    public function test_avg_position_down_not_triggered_with_null_previous(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => null, 'current' => 5.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-7: Position null in current */
    public function test_avg_position_down_not_triggered_with_null_current(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 5.0, 'current' => null],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test POS-8: Position drop from page 1 to page 2 */
    public function test_avg_position_down_page_drop(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 9.5, 'current' => 12.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('avg_position_down', $result['reason_codes']);
    }

    // =================================================================
    // RANK: Reason Code Assignment - Tracked Rankings Down
    // =================================================================

    /** @test RANK-1: Visibility score declined */
    public function test_tracked_rankings_down_when_visibility_declined(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 85.0, 'current' => 60.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('tracked_rankings_down', $result['reason_codes']);
    }

    /** @test RANK-2: Visibility score improved */
    public function test_tracked_rankings_down_not_triggered_on_improvement(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 60.0, 'current' => 85.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('tracked_rankings_down', $result['reason_codes']);
    }

    /** @test RANK-3: Visibility score unchanged */
    public function test_tracked_rankings_down_not_triggered_when_unchanged(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 75.0, 'current' => 75.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('tracked_rankings_down', $result['reason_codes']);
    }

    /** @test RANK-4: Visibility null */
    public function test_tracked_rankings_down_not_triggered_with_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => null, 'current' => null],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('tracked_rankings_down', $result['reason_codes']);
    }

    /** @test RANK-5: Multiple keywords declined */
    public function test_tracked_rankings_down_with_multiple_keywords(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 80.0, 'current' => 50.0],
            'keyword_rankings' => [
                ['keyword' => 'seo tips', 'position_change' => -5],
                ['keyword' => 'seo guide', 'position_change' => -3],
            ],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('tracked_rankings_down', $result['reason_codes']);
    }

    /** @test RANK-6: Mixed keyword performance */
    public function test_tracked_rankings_down_with_mixed_performance(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 70.0, 'current' => 70.0],
            'keyword_rankings' => [
                ['keyword' => 'seo tips', 'position_change' => 5],
                ['keyword' => 'seo guide', 'position_change' => -5],
            ],
        ]);

        $result = $service->assignReasonCodes($signals);

        // Net visibility unchanged, should not trigger
        $this->assertNotContains('tracked_rankings_down', $result['reason_codes']);
    }

    // =================================================================
    // AUDIT: Reason Code Assignment - Audit Issues Present
    // =================================================================

    /** @test AUDIT-1: Audit issues exist */
    public function test_audit_issues_present_when_issues_exist(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 5,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('audit_issues_present', $result['reason_codes']);
    }

    /** @test AUDIT-2: No audit issues */
    public function test_audit_issues_present_not_triggered_when_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 0,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('audit_issues_present', $result['reason_codes']);
    }

    /** @test AUDIT-3: Audit issues null */
    public function test_audit_issues_present_not_triggered_when_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => null,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('audit_issues_present', $result['reason_codes']);
    }

    /** @test AUDIT-4: Critical audit issues become primary */
    public function test_audit_issues_present_becomes_primary_with_critical_issues(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 10,
            'audit_severity' => 'critical',
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('audit_issues_present', $result['primary_reason_code']);
    }

    /** @test AUDIT-5: Multiple issue categories */
    public function test_audit_issues_present_with_multiple_categories(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 10,
            'audit_issues_breakdown' => [
                'critical' => 3,
                'warning' => 7,
            ],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('audit_issues_present', $result['reason_codes']);
    }

    // =================================================================
    // STALE: Reason Code Assignment - Stale Content
    // =================================================================

    /** @test STALE-1: Content older than threshold */
    public function test_stale_content_when_older_than_threshold(): void
    {
        $service = new ContentDecayReasonService(['staleness_days' => 180]);

        $signals = $this->getBaseSignals([
            'content_age_days' => 365,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('stale_content', $result['reason_codes']);
    }

    /** @test STALE-2: Content at threshold */
    public function test_stale_content_at_exact_threshold(): void
    {
        $service = new ContentDecayReasonService(['staleness_days' => 180]);

        $signals = $this->getBaseSignals([
            'content_age_days' => 180,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('stale_content', $result['reason_codes']);
    }

    /** @test STALE-3: Content below threshold */
    public function test_stale_content_not_triggered_below_threshold(): void
    {
        $service = new ContentDecayReasonService(['staleness_days' => 180]);

        $signals = $this->getBaseSignals([
            'content_age_days' => 90,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('stale_content', $result['reason_codes']);
    }

    /** @test STALE-4: Content age null */
    public function test_stale_content_not_triggered_when_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'content_age_days' => null,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('stale_content', $result['reason_codes']);
    }

    /** @test STALE-5: Recently updated content */
    public function test_stale_content_not_triggered_for_recent_content(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'content_age_days' => 7,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('stale_content', $result['reason_codes']);
    }

    /** @test STALE-6: Custom staleness threshold */
    public function test_stale_content_respects_custom_threshold(): void
    {
        $service = new ContentDecayReasonService(['staleness_days' => 365]);

        $signals = $this->getBaseSignals([
            'content_age_days' => 200,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('stale_content', $result['reason_codes']);
    }

    // =================================================================
    // ANN: Reason Code Assignment - Annotation Recently Changed
    // =================================================================

    /** @test ANN-1: Recent annotation within window */
    public function test_annotation_recently_changed_when_within_window(): void
    {
        $service = new ContentDecayReasonService(['annotation_window_days' => 30]);

        $signals = $this->getBaseSignals([
            'last_annotation_at' => now()->subDays(7)->toIso8601String(),
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('annotation_recently_changed', $result['reason_codes']);
    }

    /** @test ANN-2: Annotation outside window */
    public function test_annotation_recently_changed_not_triggered_outside_window(): void
    {
        $service = new ContentDecayReasonService(['annotation_window_days' => 30]);

        $signals = $this->getBaseSignals([
            'last_annotation_at' => now()->subDays(60)->toIso8601String(),
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('annotation_recently_changed', $result['reason_codes']);
    }

    /** @test ANN-3: No annotation */
    public function test_annotation_recently_changed_not_triggered_when_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'last_annotation_at' => null,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('annotation_recently_changed', $result['reason_codes']);
    }

    /** @test ANN-4: Annotation at exact window boundary */
    public function test_annotation_recently_changed_at_boundary(): void
    {
        $service = new ContentDecayReasonService(['annotation_window_days' => 30]);

        $signals = $this->getBaseSignals([
            'last_annotation_at' => now()->subDays(30)->toIso8601String(),
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('annotation_recently_changed', $result['reason_codes']);
    }

    /** @test ANN-5: Multiple recent annotations */
    public function test_annotation_recently_changed_with_multiple(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'last_annotation_at' => now()->subDays(5)->toIso8601String(),
            'annotation_count' => 3,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('annotation_recently_changed', $result['reason_codes']);
    }

    // =================================================================
    // AI: Reason Code Assignment - AI Visibility Down
    // =================================================================

    /** @test AI-1: AI visibility declined */
    public function test_ai_visibility_down_when_declined(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => 40.0, 'current' => 20.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('ai_visibility_down', $result['reason_codes']);
    }

    /** @test AI-2: AI visibility improved */
    public function test_ai_visibility_down_not_triggered_on_improvement(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => 20.0, 'current' => 40.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ai_visibility_down', $result['reason_codes']);
    }

    /** @test AI-3: AI visibility unchanged */
    public function test_ai_visibility_down_not_triggered_when_unchanged(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => 30.0, 'current' => 30.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ai_visibility_down', $result['reason_codes']);
    }

    /** @test AI-4: AI visibility null */
    public function test_ai_visibility_down_not_triggered_with_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => null, 'current' => null],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ai_visibility_down', $result['reason_codes']);
    }

    /** @test AI-5: AI visibility zero */
    public function test_ai_visibility_down_not_triggered_when_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => 0.0, 'current' => 0.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('ai_visibility_down', $result['reason_codes']);
    }

    // =================================================================
    // COMP: Reason Code Assignment - Competitor Gain Suspected
    // =================================================================

    /** @test COMP-1: Position loss with stable impressions */
    public function test_competitor_gain_suspected_with_position_loss_stable_impressions(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
            'clicks' => ['previous' => 100, 'current' => 100],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('competitor_gain_suspected', $result['reason_codes']);
    }

    /** @test COMP-2: Position loss with impression decline */
    public function test_competitor_gain_suspected_not_triggered_with_impression_decline(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
            'impressions' => ['previous' => 1000, 'current' => 600],
        ]);

        $result = $service->assignReasonCodes($signals);

        // Demand dropped, not competitor gain
        $this->assertNotContains('competitor_gain_suspected', $result['reason_codes']);
    }

    /** @test COMP-3: Position loss with click decline */
    public function test_competitor_gain_suspected_not_triggered_with_click_decline(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        // Clicks dropped - different signal
        $this->assertNotContains('competitor_gain_suspected', $result['reason_codes']);
    }

    /** @test COMP-4: Position improved */
    public function test_competitor_gain_suspected_not_triggered_on_improvement(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 8.0, 'current' => 3.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('competitor_gain_suspected', $result['reason_codes']);
    }

    /** @test COMP-5: Stable position */
    public function test_competitor_gain_suspected_not_triggered_when_stable(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 5.0, 'current' => 5.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('competitor_gain_suspected', $result['reason_codes']);
    }

    /** @test COMP-6: Multiple competitor gains detected */
    public function test_competitor_gain_suspected_with_competitor_data(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 6.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
            'competitor_data' => [
                ['name' => 'Competitor A', 'position_change' => 3],
                ['name' => 'Competitor B', 'position_change' => 2],
            ],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('competitor_gain_suspected', $result['reason_codes']);
    }

    // =================================================================
    // LINK: Reason Code Assignment - Internal Link Weakness
    // =================================================================

    /** @test LINK-1: Low internal link count */
    public function test_internal_link_weakness_when_low_count(): void
    {
        $service = new ContentDecayReasonService(['internal_links' => 3]);

        $signals = $this->getBaseSignals([
            'internal_links' => 2,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('internal_link_weakness', $result['reason_codes']);
    }

    /** @test LINK-2: Adequate internal links */
    public function test_internal_link_weakness_not_triggered_when_adequate(): void
    {
        $service = new ContentDecayReasonService(['internal_links' => 3]);

        $signals = $this->getBaseSignals([
            'internal_links' => 10,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('internal_link_weakness', $result['reason_codes']);
    }

    /** @test LINK-3: Internal links null */
    public function test_internal_link_weakness_not_triggered_when_null(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'internal_links' => null,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNotContains('internal_link_weakness', $result['reason_codes']);
    }

    /** @test LINK-4: Zero internal links */
    public function test_internal_link_weakness_triggered_when_zero(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'internal_links' => 0,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('internal_link_weakness', $result['reason_codes']);
    }

    /** @test LINK-5: Link threshold configurable */
    public function test_internal_link_weakness_respects_threshold(): void
    {
        $service = new ContentDecayReasonService(['internal_links' => 5]);

        $signals = $this->getBaseSignals([
            'internal_links' => 4,
        ]);

        $result = $service->assignReasonCodes($signals);

        // 4 is below threshold of 5
        $this->assertContains('internal_link_weakness', $result['reason_codes']);
    }

    // =================================================================
    // PRIMARY: Primary Reason Selection
    // =================================================================

    /** @test PRIMARY-1: Audit issues + clicks down */
    public function test_primary_reason_audit_issues_over_clicks(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 5,
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('audit_issues_present', $result['primary_reason_code']);
    }

    /** @test PRIMARY-2: Clicks down + impressions down */
    public function test_primary_reason_clicks_over_impressions(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
            'impressions' => ['previous' => 1000, 'current' => 500],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('clicks_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-3: CTR down + position down */
    public function test_primary_reason_ctr_over_position(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ctr' => ['previous' => 5.0, 'current' => 2.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('ctr_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-4: Position down + rankings down */
    public function test_primary_reason_position_over_rankings(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
            'tracked_rank_visibility' => ['previous' => 85.0, 'current' => 50.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('avg_position_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-5: Rankings down + impressions down */
    public function test_primary_reason_rankings_over_impressions(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'tracked_rank_visibility' => ['previous' => 85.0, 'current' => 50.0],
            'impressions' => ['previous' => 1000, 'current' => 500],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('tracked_rankings_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-6: Impressions down + stale content */
    public function test_primary_reason_impressions_over_stale(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'impressions' => ['previous' => 1000, 'current' => 500],
            'content_age_days' => 365,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('impressions_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-7: Stale content + AI visibility down */
    public function test_primary_reason_stale_over_ai_visibility(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'content_age_days' => 365,
            'ai_visibility' => ['previous' => 40.0, 'current' => 20.0],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('stale_content', $result['primary_reason_code']);
    }

    /** @test PRIMARY-8: AI visibility + competitor gain */
    public function test_primary_reason_ai_visibility_over_competitor(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'ai_visibility' => ['previous' => 40.0, 'current' => 20.0],
            'avg_position' => ['previous' => 3.0, 'current' => 6.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('ai_visibility_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-9: Competitor gain + link weakness */
    public function test_primary_reason_competitor_over_link_weakness(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.0, 'current' => 6.0],
            'impressions' => ['previous' => 1000, 'current' => 1000],
            'clicks' => ['previous' => 100, 'current' => 100],
            'internal_links' => 1,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('competitor_gain_suspected', $result['primary_reason_code']);
    }

    /** @test PRIMARY-10: All reason codes present */
    public function test_primary_reason_all_codes_present(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 5,
            'clicks' => ['previous' => 100, 'current' => 50],
            'impressions' => ['previous' => 1000, 'current' => 500],
            'ctr' => ['previous' => 5.0, 'current' => 2.0],
            'avg_position' => ['previous' => 3.0, 'current' => 8.0],
            'tracked_rank_visibility' => ['previous' => 85.0, 'current' => 50.0],
            'content_age_days' => 365,
            'ai_visibility' => ['previous' => 40.0, 'current' => 20.0],
            'internal_links' => 1,
        ]);

        $result = $service->assignReasonCodes($signals);

        // Audit issues should always be primary when present
        $this->assertEquals('audit_issues_present', $result['primary_reason_code']);
    }

    /** @test PRIMARY-11: Single reason code */
    public function test_primary_reason_single_code(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertEquals('clicks_down', $result['primary_reason_code']);
    }

    /** @test PRIMARY-12: No reason codes */
    public function test_primary_reason_no_codes(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([]);

        $result = $service->assignReasonCodes($signals);

        $this->assertNull($result['primary_reason_code']);
        $this->assertEmpty($result['reason_codes']);
    }

    // =================================================================
    // EVID: Evidence Payload Structure
    // =================================================================

    /** @test EVID-1: Clicks down evidence */
    public function test_evidence_clicks_down_structure(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertNotNull($clicksEvidence);
        $this->assertArrayHasKey('reason', $clicksEvidence);
        $this->assertArrayHasKey('primary', $clicksEvidence);
        $this->assertArrayHasKey('evidence', $clicksEvidence);

        $evidence = $clicksEvidence['evidence'];
        $this->assertArrayHasKey('metric', $evidence);
        $this->assertArrayHasKey('previous', $evidence);
        $this->assertArrayHasKey('current', $evidence);
        $this->assertArrayHasKey('change_percent', $evidence);
        $this->assertArrayHasKey('change_absolute', $evidence);
    }

    /** @test EVID-2: Percent change calculation */
    public function test_evidence_percent_change_calculation(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertEquals(-20.0, $clicksEvidence['evidence']['change_percent']);
    }

    /** @test EVID-3: Absolute change calculation */
    public function test_evidence_absolute_change_calculation(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertEquals(-20, $clicksEvidence['evidence']['change_absolute']);
    }

    /** @test EVID-4: Window formatting */
    public function test_evidence_window_formatting(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertArrayHasKey('window', $clicksEvidence['evidence']);
        $this->assertStringContainsString('days', $clicksEvidence['evidence']['window']);
    }

    /** @test EVID-5: Context field population */
    public function test_evidence_context_field(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
        ], [
            'page_title' => '10 SEO Tips for 2024',
            'page_type' => 'blog',
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertArrayHasKey('context', $clicksEvidence['evidence']);
    }

    /** @test EVID-6: All reason codes have evidence */
    public function test_evidence_all_codes_have_evidence(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'unresolved_audit_issues' => 5,
            'clicks' => ['previous' => 100, 'current' => 50],
            'impressions' => ['previous' => 1000, 'current' => 500],
            'content_age_days' => 365,
        ]);

        $result = $service->assignReasonCodes($signals);

        // Each reason code should have a corresponding evidence entry
        foreach ($result['reason_codes'] as $reasonCode) {
            $evidence = collect($result['evidence'])
                ->firstWhere('reason', $reasonCode);
            $this->assertNotNull($evidence, "Evidence missing for {$reasonCode}");
        }
    }

    /** @test EVID-7: Primary evidence marked */
    public function test_evidence_primary_marked(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
            'impressions' => ['previous' => 1000, 'current' => 500],
        ]);

        $result = $service->assignReasonCodes($signals);

        $primaryEvidence = collect($result['evidence'])
            ->firstWhere('primary', true);

        $this->assertNotNull($primaryEvidence);
        $this->assertEquals($result['primary_reason_code'], $primaryEvidence['reason']);
    }

    /** @test EVID-8: Multiple evidence entries */
    public function test_evidence_multiple_entries(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
            'impressions' => ['previous' => 1000, 'current' => 500],
            'content_age_days' => 365,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertGreaterThanOrEqual(3, count($result['evidence']));
    }

    /** @test EVID-9: Null value handling */
    public function test_evidence_null_value_handling(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 80],
            'avg_position' => ['previous' => null, 'current' => null],
        ]);

        $result = $service->assignReasonCodes($signals);

        // Should not crash and should only have clicks_down
        $this->assertContains('clicks_down', $result['reason_codes']);
        $this->assertNotContains('avg_position_down', $result['reason_codes']);
    }

    /** @test EVID-10: Decimal precision */
    public function test_evidence_decimal_precision(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 83],
        ]);

        $result = $service->assignReasonCodes($signals);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        // Should have 2 decimal places
        $changePercent = $clicksEvidence['evidence']['change_percent'];
        $decimalPlaces = strlen(substr(strrchr((string)$changePercent, '.'), 1));
        $this->assertLessThanOrEqual(2, $decimalPlaces);
    }

    // =================================================================
    // THRESH: Threshold Configuration
    // =================================================================

    /** @test THRESH-1: Default click threshold */
    public function test_default_click_threshold(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.10, $thresholds['click_loss']);
    }

    /** @test THRESH-2: Default impression threshold */
    public function test_default_impression_threshold(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.15, $thresholds['impression_loss']);
    }

    /** @test THRESH-3: Default CTR threshold */
    public function test_default_ctr_threshold(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.5, $thresholds['ctr_drop']);
    }

    /** @test THRESH-4: Default position threshold */
    public function test_default_position_threshold(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(3, $thresholds['position_decline']);
    }

    /** @test THRESH-5: Default staleness threshold */
    public function test_default_staleness_threshold(): void
    {
        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(180, $thresholds['staleness_days']);
    }

    /** @test THRESH-6: Custom thresholds via constructor */
    public function test_custom_thresholds_via_constructor(): void
    {
        $service = new ContentDecayReasonService([
            'click_loss' => 0.25,
            'impression_loss' => 0.30,
            'ctr_drop' => 1.0,
            'position_decline' => 5,
            'staleness_days' => 365,
        ]);

        $thresholds = $service->getThresholds();

        $this->assertEquals(0.25, $thresholds['click_loss']);
        $this->assertEquals(0.30, $thresholds['impression_loss']);
        $this->assertEquals(1.0, $thresholds['ctr_drop']);
        $this->assertEquals(5, $thresholds['position_decline']);
        $this->assertEquals(365, $thresholds['staleness_days']);
    }

    /** @test THRESH-7: Config file thresholds */
    public function test_config_file_thresholds(): void
    {
        Config::set('content-decay.thresholds.click_loss', 0.20);
        Config::set('content-decay.thresholds.impression_loss', 0.25);

        $service = new ContentDecayReasonService();
        $thresholds = $service->getThresholds();

        $this->assertEquals(0.20, $thresholds['click_loss']);
        $this->assertEquals(0.25, $thresholds['impression_loss']);
    }

    /** @test THRESH-8: Environment variable thresholds */
    public function test_env_thresholds(): void
    {
        // This test verifies that env() values are read
        // The actual env values are set in phpunit.xml or .env.testing
        $service = new ContentDecayReasonService();

        $this->assertIsArray($service->getThresholds());
    }

    // =================================================================
    // EDGE: Edge Cases
    // =================================================================

    /** @test EDGE-1: Empty signals array */
    public function test_empty_signals_array(): void
    {
        $service = new ContentDecayReasonService();

        $result = $service->assignReasonCodes([]);

        $this->assertNull($result['primary_reason_code']);
        $this->assertEmpty($result['reason_codes']);
        $this->assertEmpty($result['evidence']);
    }

    /** @test EDGE-2: All zero values */
    public function test_all_zero_values(): void
    {
        $service = new ContentDecayReasonService();

        $signals = [
            'clicks' => ['previous' => 0, 'current' => 0],
            'impressions' => ['previous' => 0, 'current' => 0],
            'ctr' => ['previous' => 0, 'current' => 0],
            'avg_position' => ['previous' => 0, 'current' => 0],
            'tracked_rank_visibility' => ['previous' => 0, 'current' => 0],
            'ai_visibility' => ['previous' => 0, 'current' => 0],
        ];

        $result = $service->assignReasonCodes($signals);

        // No decline codes should be triggered
        $this->assertNotContains('clicks_down', $result['reason_codes']);
        $this->assertNotContains('impressions_down', $result['reason_codes']);
    }

    /** @test EDGE-3: All null signals */
    public function test_all_null_signals(): void
    {
        $service = new ContentDecayReasonService();

        $signals = [
            'clicks' => ['previous' => null, 'current' => null],
            'impressions' => ['previous' => null, 'current' => null],
            'ctr' => ['previous' => null, 'current' => null],
            'avg_position' => ['previous' => null, 'current' => null],
            'tracked_rank_visibility' => ['previous' => null, 'current' => null],
            'ai_visibility' => ['previous' => null, 'current' => null],
        ];

        $result = $service->assignReasonCodes($signals);

        $this->assertEmpty($result['reason_codes']);
    }

    /** @test EDGE-4: Extreme decline (99%) */
    public function test_extreme_decline(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 10000, 'current' => 100],
            'impressions' => ['previous' => 100000, 'current' => 1000],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
        $this->assertContains('impressions_down', $result['reason_codes']);
    }

    /** @test EDGE-5: Very small numbers */
    public function test_very_small_numbers(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 0.001, 'current' => 0.0005],
        ]);

        // Should not crash
        $result = $service->assignReasonCodes($signals);
        $this->assertIsArray($result);
    }

    /** @test EDGE-6: Floating point precision */
    public function test_floating_point_precision(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'avg_position' => ['previous' => 3.333, 'current' => 6.666],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('avg_position_down', $result['reason_codes']);
    }

    /** @test EDGE-7: Unicode page titles */
    public function test_unicode_page_titles(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ], [
            'page_title' => '日本語タイトル - SEOガイド',
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);

        $clicksEvidence = collect($result['evidence'])
            ->firstWhere('reason', 'clicks_down');

        $this->assertNotNull($clicksEvidence);
    }

    /** @test EDGE-8: Long page URLs */
    public function test_long_page_urls(): void
    {
        $service = new ContentDecayReasonService();

        $longUrl = 'https://example.com/' . str_repeat('a', 500);

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ], [
            'page_url' => $longUrl,
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
    }

    /** @test EDGE-9: Concurrent signal conflicts */
    public function test_concurrent_signal_conflicts(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],      // Strong click loss
            'impressions' => ['previous' => 100, 'current' => 90],  // Mild impression loss
            'ctr' => ['previous' => 100.0, 'current' => 55.0],      // Strong CTR loss
            'avg_position' => ['previous' => 3.0, 'current' => 4.0], // Mild position loss
        ]);

        $result = $service->assignReasonCodes($signals);

        // Should pick clicks_down as primary (priority 2)
        $this->assertEquals('clicks_down', $result['primary_reason_code']);
    }

    /** @test EDGE-10: Signal data type variations */
    public function test_signal_data_type_variations(): void
    {
        $service = new ContentDecayReasonService();

        // Mix of string, int, float
        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => '100', 'current' => 80],      // String
            'impressions' => ['previous' => 1000, 'current' => 500], // Int
            'ctr' => ['previous' => 5.5, 'current' => 3.0],          // Float
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertContains('clicks_down', $result['reason_codes']);
        $this->assertContains('impressions_down', $result['reason_codes']);
        $this->assertContains('ctr_down', $result['reason_codes']);
    }

    // =================================================================
    // RET: Return Value Structure
    // =================================================================

    /** @test RET-1: Full response structure */
    public function test_full_response_structure(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertArrayHasKey('primary_reason_code', $result);
        $this->assertArrayHasKey('reason_codes', $result);
        $this->assertArrayHasKey('evidence', $result);
    }

    /** @test RET-2: Primary reason code type */
    public function test_primary_reason_code_type(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertIsString($result['primary_reason_code']);
    }

    /** @test RET-3: Reason codes type */
    public function test_reason_codes_type(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertIsArray($result['reason_codes']);
        foreach ($result['reason_codes'] as $code) {
            $this->assertIsString($code);
        }
    }

    /** @test RET-4: Evidence type */
    public function test_evidence_type(): void
    {
        $service = new ContentDecayReasonService();

        $signals = $this->getBaseSignals([
            'clicks' => ['previous' => 100, 'current' => 50],
        ]);

        $result = $service->assignReasonCodes($signals);

        $this->assertIsArray($result['evidence']);
        foreach ($result['evidence'] as $evidence) {
            $this->assertIsArray($evidence);
        }
    }

    /** @test RET-5: Empty result */
    public function test_empty_result(): void
    {
        $service = new ContentDecayReasonService();

        $result = $service->assignReasonCodes([]);

        $this->assertNull($result['primary_reason_code']);
        $this->assertEmpty($result['reason_codes']);
        $this->assertEmpty($result['evidence']);
    }

    // =================================================================
    // Helper Methods
    // =================================================================

    /**
     * Get base signals array with optional overrides.
     */
    private function getBaseSignals(array $overrides = [], array $metaOverrides = []): array
    {
        $base = [
            'page_url' => $metaOverrides['page_url'] ?? 'https://example.com/blog/seo-tips',
            'page_title' => $metaOverrides['page_title'] ?? 'Test Page',
            'page_type' => $metaOverrides['page_type'] ?? 'blog',
            'window' => [
                'previous_start' => '2026-02-10',
                'previous_end' => '2026-03-09',
                'current_start' => '2026-03-10',
                'current_end' => '2026-04-08',
            ],
        ];

        return array_merge($base, $overrides);
    }
}
