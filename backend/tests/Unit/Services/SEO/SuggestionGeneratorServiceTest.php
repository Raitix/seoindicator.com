<?php

namespace Tests\Unit\Services\SEO;

use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use App\Services\SEO\SuggestionGeneratorService;
use App\Services\SEO\SuggestionCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SuggestionGeneratorService
 * 
 * @see TASK-18-8
 * @see TEST-TASK-18-8
 */
class SuggestionGeneratorServiceTest extends TestCase
{
    // ============================================
    // SG-001: Service Instantiation
    // ============================================

    #[Test]
    public function sg_001_01_creates_instance_with_default_config(): void
    {
        $service = new SuggestionGeneratorService();

        $this->assertInstanceOf(SuggestionGeneratorService::class, $service);
    }

    #[Test]
    public function sg_001_02_accepts_custom_config_for_limit_per_indicator(): void
    {
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 5]);

        $this->assertEquals(5, $service->getLimitPerIndicator());
    }

    #[Test]
    public function sg_001_03_default_limit_per_indicator_is_3(): void
    {
        $service = new SuggestionGeneratorService();

        $this->assertEquals(3, $service->getLimitPerIndicator());
    }

    #[Test]
    public function sg_001_04_accepts_array_of_checkers_in_constructor(): void
    {
        $checker1 = $this->createMockChecker('title_tag', 'Title Tag', 'content');
        $checker2 = $this->createMockChecker('meta_desc', 'Meta Description', 'content');

        $service = new SuggestionGeneratorService([], [$checker1, $checker2]);

        $this->assertEquals(2, $service->getCheckerCount());
    }

    // ============================================
    // SG-002: Generate Suggestions from Results
    // ============================================

    #[Test]
    public function sg_002_01_accepts_array_of_indicator_result_objects(): void
    {
        $service = new SuggestionGeneratorService();
        $results = [$this->createDefaultResult()];

        $collection = $service->generate($results);

        $this->assertInstanceOf(SuggestionCollection::class, $collection);
    }

    #[Test]
    public function sg_002_02_calls_get_suggestions_on_each_checker(): void
    {
        $checker = $this->createMockChecker('title_tag', 'Title Tag', 'content');
        $checker->expects($this->once())
            ->method('getSuggestions')
            ->willReturn([]);

        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('title_tag', 'Title Tag', 'content');

        $service->generate([$result]);
    }

    #[Test]
    public function sg_002_03_aggregates_suggestions_from_all_checkers(): void
    {
        $checker1 = $this->createMockCheckerWithSuggestions('title_tag', [
            new Suggestion('warning', 'Title too short', null, 2),
        ]);
        $checker2 = $this->createMockCheckerWithSuggestions('meta_desc', [
            new Suggestion('error', 'Missing meta description', null, 3),
        ]);

        $service = new SuggestionGeneratorService([], [$checker1, $checker2]);
        $results = [
            $this->createResultForChecker('title_tag', 'Title Tag', 'content'),
            $this->createResultForChecker('meta_desc', 'Meta Description', 'content'),
        ];

        $collection = $service->generate($results);

        $this->assertEquals(2, $collection->count());
    }

    #[Test]
    public function sg_002_04_returns_empty_collection_for_empty_results(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate([]);

        $this->assertTrue($collection->isEmpty());
    }

    #[Test]
    public function sg_002_05_handles_null_result_array(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate(null);

        $this->assertTrue($collection->isEmpty());
    }

    // ============================================
    // SG-003: Prioritization by Impact
    // ============================================

    #[Test]
    public function sg_003_01_sorts_by_priority_high_first(): void
    {
        $suggestions = [
            new Suggestion('warning', 'Low priority', null, 1),
            new Suggestion('error', 'High priority', null, 3),
            new Suggestion('info', 'Medium priority', null, 2),
        ];

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $sorted = $collection->all();
        $this->assertEquals(3, $sorted[0]->priority);
        $this->assertEquals(2, $sorted[1]->priority);
        $this->assertEquals(1, $sorted[2]->priority);
    }

    #[Test]
    public function sg_003_02_considers_indicator_weight_in_sorting(): void
    {
        $highWeightChecker = $this->createMockCheckerWithWeight('title_tag', 1.0, [
            new Suggestion('warning', 'Title issue', null, 2),
        ]);
        $lowWeightChecker = $this->createMockCheckerWithWeight('meta_desc', 0.5, [
            new Suggestion('error', 'Meta issue', null, 3),
        ]);

        $service = new SuggestionGeneratorService([], [$highWeightChecker, $lowWeightChecker]);
        $results = [
            $this->createResultWithWeight('title_tag', 'Title Tag', 'content', 1.0),
            $this->createResultWithWeight('meta_desc', 'Meta Description', 'content', 0.5),
        ];

        $collection = $service->generate($results);

        // Even though meta has higher priority, title has higher weight
        $this->assertEquals('Title issue', $collection->first()->message);
    }

    #[Test]
    public function sg_003_03_same_priority_sorted_by_type_error_warning_info(): void
    {
        $suggestions = [
            new Suggestion('info', 'Info message', null, 2),
            new Suggestion('warning', 'Warning message', null, 2),
            new Suggestion('error', 'Error message', null, 2),
        ];

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $sorted = $collection->all();

        $this->assertEquals('error', $sorted[0]->type);
        $this->assertEquals('warning', $sorted[1]->type);
        $this->assertEquals('info', $sorted[2]->type);
    }

    #[Test]
    public function sg_003_04_calculates_impact_score_correctly(): void
    {
        $service = new SuggestionGeneratorService();
        $suggestion = new Suggestion('error', 'Test', null, 3);
        $weight = 1.0;

        $impact = $service->calculateImpactScore($suggestion, $weight);

        $this->assertGreaterThan(0, $impact);
    }

    // ============================================
    // SG-004: Grouping by Category
    // ============================================

    #[Test]
    public function sg_004_01_groups_suggestions_by_indicator_category(): void
    {
        $contentChecker = $this->createMockCheckerWithSuggestions('title_tag', [
            new Suggestion('warning', 'Title issue', null, 2),
        ]);
        $technicalChecker = $this->createMockCheckerWithSuggestions('ssl', [
            new Suggestion('error', 'SSL issue', null, 3),
        ]);

        $service = new SuggestionGeneratorService([], [$contentChecker, $technicalChecker]);
        $results = [
            $this->createResultForChecker('title_tag', 'Title Tag', 'content'),
            $this->createResultForChecker('ssl', 'SSL', 'technical'),
        ];

        $collection = $service->generate($results);
        $grouped = $collection->groupByCategory();

        $this->assertArrayHasKey('content', $grouped);
        $this->assertArrayHasKey('technical', $grouped);
    }

    #[Test]
    public function sg_004_02_returns_all_4_categories_even_if_empty(): void
    {
        $checker = $this->createMockCheckerWithSuggestions('title_tag', [
            new Suggestion('warning', 'Title issue', null, 2),
        ]);

        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('title_tag', 'Title Tag', 'content');

        $collection = $service->generate([$result]);
        $grouped = $collection->groupByCategory();

        $this->assertArrayHasKey('content', $grouped);
        $this->assertArrayHasKey('technical', $grouped);
        $this->assertArrayHasKey('accessibility', $grouped);
        $this->assertArrayHasKey('performance', $grouped);
    }

    #[Test]
    public function sg_004_03_multiple_suggestions_in_same_category(): void
    {
        $checker1 = $this->createMockCheckerWithSuggestions('title_tag', [
            new Suggestion('warning', 'Title issue', null, 2),
        ]);
        $checker2 = $this->createMockCheckerWithSuggestions('meta_desc', [
            new Suggestion('error', 'Meta issue', null, 3),
        ]);

        $service = new SuggestionGeneratorService([], [$checker1, $checker2]);
        $results = [
            $this->createResultForChecker('title_tag', 'Title Tag', 'content'),
            $this->createResultForChecker('meta_desc', 'Meta Description', 'content'),
        ];

        $collection = $service->generate($results);
        $grouped = $collection->groupByCategory();

        $this->assertCount(2, $grouped['content']);
    }

    #[Test]
    public function sg_004_04_category_order_is_consistent(): void
    {
        $service = new SuggestionGeneratorService();
        $expectedOrder = ['content', 'technical', 'accessibility', 'performance'];

        $order = $service->getCategoryOrder();

        $this->assertEquals($expectedOrder, $order);
    }

    // ============================================
    // SG-005: Limit Suggestions Per Indicator
    // ============================================

    #[Test]
    public function sg_005_01_limits_suggestions_to_configured_limit(): void
    {
        $suggestions = [
            new Suggestion('warning', 'Issue 1', null, 3),
            new Suggestion('warning', 'Issue 2', null, 2),
            new Suggestion('warning', 'Issue 3', null, 1),
            new Suggestion('warning', 'Issue 4', null, 1),
            new Suggestion('warning', 'Issue 5', null, 1),
        ];

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 3], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertEquals(3, $collection->count());
    }

    #[Test]
    public function sg_005_02_keeps_highest_priority_suggestions_when_limiting(): void
    {
        $suggestions = [
            new Suggestion('warning', 'Low 1', null, 1),
            new Suggestion('warning', 'Low 2', null, 1),
            new Suggestion('warning', 'High', null, 3),
            new Suggestion('warning', 'Medium', null, 2),
        ];

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 2], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $messages = array_map(fn($s) => $s->message, $collection->all());

        $this->assertContains('High', $messages);
        $this->assertContains('Medium', $messages);
    }

    #[Test]
    public function sg_005_03_no_limit_when_set_to_0(): void
    {
        $suggestions = [];
        for ($i = 1; $i <= 10; $i++) {
            $suggestions[] = new Suggestion('warning', "Issue $i", null, 1);
        }

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 0], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertEquals(10, $collection->count());
    }

    #[Test]
    public function sg_005_04_limit_can_be_changed_after_instantiation(): void
    {
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 3]);

        $service->setLimitPerIndicator(5);

        $this->assertEquals(5, $service->getLimitPerIndicator());
    }

    // ============================================
    // SG-006: SuggestionCollection Return Type
    // ============================================

    #[Test]
    public function sg_006_01_returns_suggestion_collection_instance(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate([]);

        $this->assertInstanceOf(SuggestionCollection::class, $collection);
    }

    #[Test]
    public function sg_006_02_collection_has_all_method(): void
    {
        $service = new SuggestionGeneratorService();
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('warning', 'Test', null, 1),
        ]);
        $service->addChecker($checker);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertIsArray($collection->all());
    }

    #[Test]
    public function sg_006_03_collection_has_count_method(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate([]);

        $this->assertIsInt($collection->count());
    }

    #[Test]
    public function sg_006_04_collection_has_is_empty_method(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate([]);

        $this->assertTrue($collection->isEmpty());
    }

    #[Test]
    public function sg_006_05_collection_has_first_method(): void
    {
        $service = new SuggestionGeneratorService();
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('warning', 'First', null, 1),
        ]);
        $service->addChecker($checker);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertInstanceOf(Suggestion::class, $collection->first());
    }

    #[Test]
    public function sg_006_06_collection_has_group_by_category_method(): void
    {
        $service = new SuggestionGeneratorService();

        $collection = $service->generate([]);

        $this->assertIsArray($collection->groupByCategory());
    }

    #[Test]
    public function sg_006_07_collection_has_get_by_priority_method(): void
    {
        $service = new SuggestionGeneratorService();
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('warning', 'High', null, 3),
            new Suggestion('warning', 'Low', null, 1),
        ]);
        $service->addChecker($checker);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $high = $collection->getByPriority(3);

        $this->assertCount(1, $high);
        $this->assertEquals('High', $high[0]->message);
    }

    #[Test]
    public function sg_006_08_collection_has_get_by_type_method(): void
    {
        $service = new SuggestionGeneratorService();
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('error', 'Error', null, 2),
            new Suggestion('warning', 'Warning', null, 2),
        ]);
        $service->addChecker($checker);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $errors = $collection->getByType('error');

        $this->assertCount(1, $errors);
    }

    // ============================================
    // SG-007: Handle Indicators with No Suggestions
    // ============================================

    #[Test]
    public function sg_007_01_checker_returns_empty_array(): void
    {
        $checker = $this->createMockCheckerWithSuggestions('test', []);
        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertTrue($collection->isEmpty());
    }

    #[Test]
    public function sg_007_02_checker_returns_null_from_get_suggestions(): void
    {
        $checker = $this->createMockChecker('test', 'Test', 'content');
        $checker->method('getSuggestions')->willReturn(null);

        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertTrue($collection->isEmpty());
    }

    #[Test]
    public function sg_007_03_mixed_results_some_with_suggestions_some_without(): void
    {
        $checkerWith = $this->createMockCheckerWithSuggestions('with', [
            new Suggestion('warning', 'Issue', null, 1),
        ]);
        $checkerWithout = $this->createMockCheckerWithSuggestions('without', []);

        $service = new SuggestionGeneratorService([], [$checkerWith, $checkerWithout]);
        $results = [
            $this->createResultForChecker('with', 'With', 'content'),
            $this->createResultForChecker('without', 'Without', 'content'),
        ];

        $collection = $service->generate($results);

        $this->assertEquals(1, $collection->count());
    }

    #[Test]
    public function sg_007_04_result_with_score_3_may_have_no_suggestions(): void
    {
        $checker = $this->createMockChecker('test', 'Test', 'content');
        $checker->method('getSuggestions')->willReturn([]);

        $service = new SuggestionGeneratorService([], [$checker]);
        $result = new IndicatorResult(
            id: 'test',
            name: 'Test',
            category: 'content',
            score: 3,
            weight: 1.0,
            passed: true
        );

        $collection = $service->generate([$result]);

        $this->assertTrue($collection->isEmpty());
    }

    // ============================================
    // SG-008: Quick Wins Category
    // ============================================

    #[Test]
    public function sg_008_01_identifies_quick_wins_easy_fixes(): void
    {
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('warning', 'Easy fix', 'Add title tag', 2),
        ]);

        $service = new SuggestionGeneratorService(['enableQuickWins' => true], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $quickWins = $collection->getQuickWins();

        $this->assertCount(1, $quickWins);
    }

    #[Test]
    public function sg_008_02_quick_wins_are_high_impact_easy_to_fix(): void
    {
        $suggestions = [
            new Suggestion('warning', 'Complex fix', 'Rewrite entire site', 3),
            new Suggestion('warning', 'Easy fix', 'Add alt text', 2),
        ];

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService(['enableQuickWins' => true], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);
        $quickWins = $collection->getQuickWins();

        // "Add alt text" should be identified as quick win
        $this->assertStringContainsString('Add', $quickWins[0]->action);
    }

    #[Test]
    public function sg_008_03_can_disable_quick_wins_feature(): void
    {
        $checker = $this->createMockCheckerWithSuggestions('test', [
            new Suggestion('warning', 'Fix', 'Add title', 2),
        ]);

        $service = new SuggestionGeneratorService(['enableQuickWins' => false], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $collection = $service->generate([$result]);

        $this->assertCount(0, $collection->getQuickWins());
    }

    // ============================================
    // SG-009: Error Handling
    // ============================================

    #[Test]
    public function sg_009_01_handles_checker_throwing_exception(): void
    {
        $checker = $this->createMockChecker('test', 'Test', 'content');
        $checker->method('getSuggestions')
            ->willThrowException(new \Exception('Checker error'));

        $service = new SuggestionGeneratorService([], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        // Should not throw, should handle gracefully
        $collection = $service->generate([$result]);

        $this->assertInstanceOf(SuggestionCollection::class, $collection);
    }

    #[Test]
    public function sg_009_03_continues_processing_other_checkers_on_error(): void
    {
        $failingChecker = $this->createMockChecker('fail', 'Fail', 'content');
        $failingChecker->method('getSuggestions')
            ->willThrowException(new \Exception('Error'));

        $workingChecker = $this->createMockCheckerWithSuggestions('work', [
            new Suggestion('warning', 'Works', null, 1),
        ]);

        $service = new SuggestionGeneratorService([], [$failingChecker, $workingChecker]);
        $results = [
            $this->createResultForChecker('fail', 'Fail', 'content'),
            $this->createResultForChecker('work', 'Work', 'content'),
        ];

        $collection = $service->generate($results);

        $this->assertEquals(1, $collection->count());
    }

    #[Test]
    public function sg_009_04_handles_invalid_result_type(): void
    {
        $service = new SuggestionGeneratorService();

        // Should throw or handle gracefully
        $this->expectException(\InvalidArgumentException::class);
        $service->generate(['not a result']);
    }

    // ============================================
    // SG-010: Performance Tests
    // ============================================

    #[Test]
    public function sg_010_01_handles_17_indicators_efficiently(): void
    {
        $checkers = [];
        $results = [];

        foreach (range(1, 17) as $i) {
            $id = "indicator_$i";
            $checkers[] = $this->createMockCheckerWithSuggestions($id, [
                new Suggestion('warning', "Suggestion $i", null, 2),
            ]);
            $results[] = $this->createResultForChecker($id, "Indicator $i", 'content');
        }

        $service = new SuggestionGeneratorService([], $checkers);

        $start = microtime(true);
        $collection = $service->generate($results);
        $duration = microtime(true) - $start;

        $this->assertEquals(17, $collection->count());
        $this->assertLessThan(0.1, $duration); // 100ms max
    }

    #[Test]
    public function sg_010_02_handles_large_suggestion_counts(): void
    {
        $suggestions = [];
        for ($i = 1; $i <= 100; $i++) {
            $suggestions[] = new Suggestion('warning', "Suggestion $i", null, 1);
        }

        $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
        $service = new SuggestionGeneratorService(['limitPerIndicator' => 0], [$checker]);
        $result = $this->createResultForChecker('test', 'Test', 'content');

        $start = microtime(true);
        $collection = $service->generate([$result]);
        $duration = microtime(true) - $start;

        $this->assertEquals(100, $collection->count());
        $this->assertLessThan(0.05, $duration); // 50ms max
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function createMockChecker(string $id, string $name, string $category): IndicatorCheckerInterface
    {
        $checker = $this->createMock(IndicatorCheckerInterface::class);
        $checker->method('id')->willReturn($id);
        $checker->method('name')->willReturn($name);
        $checker->method('category')->willReturn($category);
        return $checker;
    }

    private function createMockCheckerWithSuggestions(string $id, array $suggestions): IndicatorCheckerInterface
    {
        $checker = $this->createMockChecker($id, 'Checker ' . $id, 'content');
        $checker->method('getSuggestions')->willReturn($suggestions);
        return $checker;
    }

    private function createMockCheckerWithWeight(string $id, float $weight, array $suggestions): IndicatorCheckerInterface
    {
        $checker = $this->createMockCheckerWithSuggestions($id, $suggestions);
        $checker->method('weight')->willReturn($weight);
        return $checker;
    }

    private function createDefaultResult(): IndicatorResult
    {
        return new IndicatorResult(
            id: 'test',
            name: 'Test Indicator',
            category: 'content',
            score: 2,
            weight: 1.0,
            passed: true
        );
    }

    private function createResultForChecker(
        string $id,
        string $name,
        string $category,
        int $score = 2
    ): IndicatorResult {
        return new IndicatorResult(
            id: $id,
            name: $name,
            category: $category,
            score: $score,
            weight: 1.0,
            passed: $score >= 2
        );
    }

    private function createResultWithWeight(
        string $id,
        string $name,
        string $category,
        float $weight
    ): IndicatorResult {
        return new IndicatorResult(
            id: $id,
            name: $name,
            category: $category,
            score: 2,
            weight: $weight,
            passed: true
        );
    }
}
