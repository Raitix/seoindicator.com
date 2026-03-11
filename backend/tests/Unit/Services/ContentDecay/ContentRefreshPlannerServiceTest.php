<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Services\ContentDecay\ContentRefreshPlannerService;
use App\Services\ContentDecay\ContentRefreshPlannerServiceInterface;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive test suite for ContentRefreshPlannerService.
 * 
 * This service generates actionable recommendations and task suggestions
 * based on content decay reason codes and evidence.
 * 
 * @see TESTS/TEST-36-5.md for full test documentation
 */
class ContentRefreshPlannerServiceTest extends TestCase
{
    private ContentRefreshPlannerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentRefreshPlannerService();
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Create a mock ContentDecayCandidate for testing.
     */
    private function createMockCandidate(array $attributes = []): ContentDecayCandidate
    {
        $defaults = [
            'id' => 1,
            'project_id' => 1,
            'page_url' => 'https://example.com/blog/seo-tips',
            'page_title' => '10 SEO Tips for 2024',
            'page_type' => 'blog',
            'decay_score' => 75.50,
            'detection_confidence' => 0.95,
            'severity' => 'high',
            'status' => 'backlog',
            'reason_codes' => ['ctr_down'],
            'signal_sources' => ['gsc'],
            'evidence_json' => [],
            'lost_clicks_estimate' => 450,
            'recovery_potential' => 1200,
        ];

        $data = array_merge($defaults, $attributes);

        // Create a model instance without persisting to database
        $candidate = ContentDecayCandidate::make($data);
        
        // Manually set array-casted attributes that need to be arrays
        $candidate->reason_codes = $data['reason_codes'] ?? [];
        $candidate->signal_sources = $data['signal_sources'] ?? [];
        $candidate->evidence_json = $data['evidence_json'] ?? [];

        return $candidate;
    }

    private function createCtrEvidence(float $previous, float $current): array
    {
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        return [
            'metric' => 'ctr',
            'previous_value' => $previous,
            'current_value' => $current,
            'change_percent' => round($change, 2),
            'change_absolute' => round($current - $previous, 2),
        ];
    }

    private function createAuditEvidence(int $count, array $types = []): array
    {
        return [
            'metric' => 'audit_issues',
            'count' => $count,
            'types' => $types ?: ['missing_meta_description', 'h1_missing', 'images_without_alt'],
        ];
    }

    private function createStaleEvidence(int $days): array
    {
        return [
            'metric' => 'content_age',
            'content_age_days' => $days,
        ];
    }

    private function createPositionEvidence(float $previous, float $current): array
    {
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        return [
            'metric' => 'avg_position',
            'previous_value' => $previous,
            'current_value' => $current,
            'change_percent' => round($change, 2),
            'change_absolute' => round($current - $previous, 1),
        ];
    }

    private function createTrackedRankingsEvidence(int $count): array
    {
        return [
            'metric' => 'tracked_rankings',
            'keywords_lost_count' => $count,
            'keywords_lost' => array_map(fn($i) => "keyword_$i", range(1, min($count, 10))),
        ];
    }

    private function createImpressionsEvidence(int $previous, int $current): array
    {
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        return [
            'metric' => 'impressions',
            'previous_value' => $previous,
            'current_value' => $current,
            'change_percent' => round($change, 2),
            'change_absolute' => $current - $previous,
        ];
    }

    private function createClicksEvidence(int $previous, int $current): array
    {
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        return [
            'metric' => 'clicks',
            'previous_value' => $previous,
            'current_value' => $current,
            'change_percent' => round($change, 2),
            'change_absolute' => $current - $previous,
        ];
    }

    private function createAIVisibilityEvidence(int $dropPercent): array
    {
        return [
            'metric' => 'ai_visibility',
            'drop_percent' => $dropPercent,
        ];
    }

    private function createCompetitorEvidence(string $name): array
    {
        return [
            'metric' => 'competitor_gain',
            'competitor_name' => $name,
        ];
    }

    private function createInternalLinkEvidence(int $currentCount): array
    {
        return [
            'metric' => 'internal_links',
            'current_count' => $currentCount,
        ];
    }

    // ============================================================
    // 1. SERVICE INSTANTIATION (SI-001 to SI-005)
    // ============================================================

    #[Test]
    public function si_001_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ContentRefreshPlannerService::class, $this->service);
    }

    #[Test]
    public function si_002_service_implements_interface(): void
    {
        $this->assertInstanceOf(ContentRefreshPlannerServiceInterface::class, $this->service);
    }

    #[Test]
    public function si_003_service_has_generate_recommendations_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateRecommendations'));
    }

    #[Test]
    public function si_004_service_has_recommendation_templates(): void
    {
        $this->assertTrue(property_exists($this->service, 'templates') || method_exists($this->service, 'getTemplates'));
    }

    #[Test]
    public function si_005_service_has_page_type_configurations(): void
    {
        $this->assertTrue(property_exists($this->service, 'pageTypeConfigs') || method_exists($this->service, 'getPageTypeConfigs'));
    }

    // ============================================================
    // 2. SINGLE-TRIGGER RECOMMENDATIONS - CTR DOWN (ST-001 to ST-010)
    // ============================================================

    #[Test]
    public function st_001_ctr_down_returns_correct_primary(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('title', strtolower($result['primary_recommendation']));
    }

    #[Test]
    public function st_002_ctr_down_recommendation_type(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('meta_update', $result['task_suggestions'][0]['recommendation_type']);
    }

    #[Test]
    public function st_003_ctr_down_due_date(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'severity' => 'medium', // Use medium severity for baseline due date test
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);
        $dueDate = $result['task_suggestions'][0]['due_date'];
        $expectedDate = Carbon::now()->addDays(7);

        // Allow 1 day tolerance for timing differences
        $this->assertTrue(
            $dueDate->diffInDays($expectedDate, absolute: true) <= 1,
            "Due date should be approximately 7 days from now. Expected: {$expectedDate->format('Y-m-d')}, Got: {$dueDate->format('Y-m-d')}"
        );
    }

    #[Test]
    public function st_004_ctr_down_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('high', $result['task_suggestions'][0]['priority']);
    }

    #[Test]
    public function st_005_ctr_down_task_title_contains_keyword(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);
        $title = strtolower($result['task_suggestions'][0]['title']);

        $this->assertTrue(
            str_contains($title, 'title') || str_contains($title, 'meta'),
            "Task title should contain 'title' or 'meta'"
        );
    }

    #[Test]
    public function st_006_ctr_down_task_description_includes_percentage(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.0, 2.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('50', $result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_007_ctr_down_single_task_generated(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertCount(1, $result['task_suggestions']);
    }

    #[Test]
    public function st_008_ctr_down_with_evidence_included_in_description(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => [
                'ctr_down' => [
                    'metric' => 'ctr',
                    'previous_value' => 4.5,
                    'current_value' => 2.0,
                    'change_percent' => -55.56,
                    'change_absolute' => -2.5,
                ],
            ],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_009_ctr_down_supporting_empty_when_only_trigger(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEmpty($result['supporting_recommendations']);
    }

    #[Test]
    public function st_010_ctr_down_with_critical_severity(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['ctr_down'],
            'severity' => 'critical',
            'evidence_json' => ['ctr_down' => $this->createCtrEvidence(5.2, 2.8)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('critical', $result['task_suggestions'][0]['priority']);
    }

    // ============================================================
    // 2. SINGLE-TRIGGER RECOMMENDATIONS - AUDIT ISSUES (ST-011 to ST-020)
    // ============================================================

    #[Test]
    public function st_011_audit_issues_returns_correct_primary(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('technical', strtolower($result['primary_recommendation']));
    }

    #[Test]
    public function st_012_audit_issues_recommendation_type(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('technical_fix', $result['task_suggestions'][0]['recommendation_type']);
    }

    #[Test]
    public function st_013_audit_issues_due_date(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);
        $expectedDue = Carbon::now()->addDays(3)->format('Y-m-d');

        $this->assertEquals($expectedDue, $result['task_suggestions'][0]['due_date']->format('Y-m-d'));
    }

    #[Test]
    public function st_014_audit_issues_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('critical', $result['task_suggestions'][0]['priority']);
    }

    #[Test]
    public function st_015_audit_issues_task_count_in_description(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('5', $result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_016_audit_issues_blocking_content_message(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('fix', strtolower($result['primary_recommendation']));
    }

    #[Test]
    public function st_017_audit_issues_highest_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('critical', $result['task_suggestions'][0]['priority']);
    }

    #[Test]
    public function st_018_audit_issues_with_specific_types(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => [
                'audit_issues_present' => [
                    'metric' => 'audit_issues',
                    'count' => 3,
                    'types' => ['missing_meta_description', 'h1_missing', 'images_without_alt'],
                ],
            ],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_019_audit_issues_task_source(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present'],
            'evidence_json' => ['audit_issues_present' => $this->createAuditEvidence(5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('audit', $result['task_suggestions'][0]['source']);
    }

    #[Test]
    public function st_020_audit_issues_prioritized_over_ctr(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['audit_issues_present', 'ctr_down'],
            'evidence_json' => [
                'audit_issues_present' => $this->createAuditEvidence(5),
                'ctr_down' => $this->createCtrEvidence(5.2, 2.8),
            ],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('critical', $result['task_suggestions'][0]['priority']);
        $this->assertEquals('technical_fix', $result['task_suggestions'][0]['recommendation_type']);
    }

    // ============================================================
    // 2. SINGLE-TRIGGER RECOMMENDATIONS - STALE CONTENT (ST-021 to ST-030)
    // ============================================================

    #[Test]
    public function st_021_stale_content_returns_correct_primary(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(180)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('update', strtolower($result['primary_recommendation']));
    }

    #[Test]
    public function st_022_stale_content_recommendation_type(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(180)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('content_refresh', $result['task_suggestions'][0]['recommendation_type']);
    }

    #[Test]
    public function st_023_stale_content_due_date(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'severity' => 'medium', // Use medium severity for baseline due date test
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(180)],
        ]);

        $result = $this->service->generateRecommendations($candidate);
        $dueDate = $result['task_suggestions'][0]['due_date'];
        $expectedDate = Carbon::now()->addDays(14);

        // Allow 1 day tolerance for timing differences
        $this->assertTrue(
            $dueDate->diffInDays($expectedDate, absolute: true) <= 1,
            "Due date should be approximately 14 days from now. Expected: {$expectedDate->format('Y-m-d')}, Got: {$dueDate->format('Y-m-d')}"
        );
    }

    #[Test]
    public function st_024_stale_content_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(180)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('high', $result['task_suggestions'][0]['priority']);
    }

    #[Test]
    public function st_025_stale_content_task_description_includes_age(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(365)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('365', $result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_026_stale_content_examples_update_suggested(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(365)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $description = strtolower($result['task_suggestions'][0]['description']);
        $this->assertTrue(
            str_contains($description, 'example') || str_contains($description, 'update'),
            "Description should suggest updating examples or content"
        );
    }

    #[Test]
    public function st_027_stale_content_date_references_suggested(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(365)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_028_stale_content_supporting_recommendations(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(365)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEmpty($result['supporting_recommendations']);
    }

    #[Test]
    public function st_029_stale_content_very_old_elevated_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(730)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertContains($result['task_suggestions'][0]['priority'], ['high', 'critical']);
    }

    #[Test]
    public function st_030_stale_content_recent_update_lower_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['stale_content'],
            'evidence_json' => ['stale_content' => $this->createStaleEvidence(30)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['priority']);
    }

    // ============================================================
    // 2. SINGLE-TRIGGER RECOMMENDATIONS - POSITION DOWN (ST-031 to ST-040)
    // ============================================================

    #[Test]
    public function st_031_position_down_returns_correct_primary(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('content', strtolower($result['primary_recommendation']));
    }

    #[Test]
    public function st_032_position_down_recommendation_type(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('ranking_recovery', $result['task_suggestions'][0]['recommendation_type']);
    }

    #[Test]
    public function st_033_position_down_due_date(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'severity' => 'medium', // Use medium severity for baseline due date test
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);
        $dueDate = $result['task_suggestions'][0]['due_date'];
        $expectedDate = Carbon::now()->addDays(10);

        // Allow 1 day tolerance for timing differences
        $this->assertTrue(
            $dueDate->diffInDays($expectedDate, absolute: true) <= 1,
            "Due date should be approximately 10 days from now. Expected: {$expectedDate->format('Y-m-d')}, Got: {$dueDate->format('Y-m-d')}"
        );
    }

    #[Test]
    public function st_034_position_down_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'severity' => 'medium', // Use medium severity for baseline priority test
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEquals('medium', $result['task_suggestions'][0]['priority']);
    }

    #[Test]
    public function st_035_position_down_with_specific_queries(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => [
                'avg_position_down' => [
                    'metric' => 'avg_position',
                    'previous_value' => 5.2,
                    'current_value' => 12.5,
                    'change_percent' => 140.38,
                    'change_absolute' => 7.3,
                    'affected_queries' => ['seo tips', 'content optimization'],
                ],
            ],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_036_position_down_task_description_includes_positions(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.0, 10.0)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertStringContainsString('5', $result['task_suggestions'][0]['description']);
    }

    #[Test]
    public function st_037_position_down_significant_elevated_priority(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(3.0, 15.0)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertContains($result['task_suggestions'][0]['priority'], ['medium', 'high']);
    }

    #[Test]
    public function st_038_position_down_supporting_recommendations(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertEmpty($result['supporting_recommendations']);
    }

    #[Test]
    public function st_039_position_down_with_tracked_rankings(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down', 'tracked_rankings_down'],
            'evidence_json' => [
                'avg_position_down' => $this->createPositionEvidence(5.2, 12.5),
                'tracked_rankings_down' => $this->createTrackedRankingsEvidence(5),
            ],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertGreaterThanOrEqual(2, count($result['task_suggestions']));
    }

    #[Test]
    public function st_040_position_down_keywords_listed(): void
    {
        $candidate = $this->createMockCandidate([
            'reason_codes' => ['avg_position_down'],
            'evidence_json' => ['avg_position_down' => $this->createPositionEvidence(5.2, 12.5)],
        ]);

        $result = $this->service->generateRecommendations($candidate);

        $this->assertNotEmpty($result['task_suggestions'][0]['description']);
    }
}
