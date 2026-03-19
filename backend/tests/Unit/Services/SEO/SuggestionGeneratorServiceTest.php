<?php

namespace Tests\Unit\Services\SEO;

use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use App\Services\SEO\SuggestionGeneratorService;
use App\Services\SEO\SuggestionCollection;
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
    describe('Service Instantiation', () => {
        it('SG-001-01: creates instance with default config', () => {
            $service = new SuggestionGeneratorService();

            expect($service)->toBeInstanceOf(SuggestionGeneratorService::class);
        });

        it('SG-001-02: accepts custom config for limit per indicator', () => {
            $service = new SuggestionGeneratorService(['limitPerIndicator' => 5]);

            expect($service->getLimitPerIndicator())->toBe(5);
        });

        it('SG-001-03: default limit per indicator is 3', () => {
            $service = new SuggestionGeneratorService();

            expect($service->getLimitPerIndicator())->toBe(3);
        });

        it('SG-001-04: accepts array of checkers in constructor', () => {
            $checker1 = $this->createMockChecker('title_tag', 'Title Tag', 'content');
            $checker2 = $this->createMockChecker('meta_desc', 'Meta Description', 'content');

            $service = new SuggestionGeneratorService([], [$checker1, $checker2]);

            expect($service->getCheckerCount())->toBe(2);
        });
    });

    // ============================================
    // SG-002: Generate Suggestions from Results
    // ============================================
    describe('Generate Suggestions from Results', () => {
        it('SG-002-01: accepts array of IndicatorResult objects', () => {
            $service = new SuggestionGeneratorService();
            $results = [$this->createDefaultResult()];

            $collection = $service->generate($results);

            expect($collection)->toBeInstanceOf(SuggestionCollection::class);
        });

        it('SG-002-02: calls getSuggestions on each checker', () => {
            $checker = $this->createMockChecker('title_tag', 'Title Tag', 'content');
            $checker->expects($this->once())
                ->method('getSuggestions')
                ->willReturn([]);

            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('title_tag', 'Title Tag', 'content');

            $service->generate([$result]);
        });

        it('SG-002-03: aggregates suggestions from all checkers', () => {
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

            expect($collection->count())->toBe(2);
        });

        it('SG-002-04: returns empty collection for empty results', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate([]);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('SG-002-05: handles null result array', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate(null);

            expect($collection->isEmpty())->toBeTrue();
        });
    });

    // ============================================
    // SG-003: Prioritization by Impact
    // ============================================
    describe('Prioritization by Impact', () => {
        it('SG-003-01: sorts by priority (high first)', () => {
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
            expect($sorted[0]->priority)->toBe(3);
            expect($sorted[1]->priority)->toBe(2);
            expect($sorted[2]->priority)->toBe(1);
        });

        it('SG-003-02: considers indicator weight in sorting', () => {
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
            expect($collection->first()->message)->toBe('Title issue');
        });

        it('SG-003-03: same priority sorted by type (error > warning > info)', () => {
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

            expect($sorted[0]->type)->toBe('error');
            expect($sorted[1]->type)->toBe('warning');
            expect($sorted[2]->type)->toBe('info');
        });

        it('SG-003-04: calculates impact score correctly', () => {
            $service = new SuggestionGeneratorService();
            $suggestion = new Suggestion('error', 'Test', null, 3);
            $weight = 1.0;

            $impact = $service->calculateImpactScore($suggestion, $weight);

            expect($impact)->toBeGreaterThan(0);
        });
    });

    // ============================================
    // SG-004: Grouping by Category
    // ============================================
    describe('Grouping by Category', () => {
        it('SG-004-01: groups suggestions by indicator category', () => {
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

            expect($grouped)->toHaveKey('content');
            expect($grouped)->toHaveKey('technical');
        });

        it('SG-004-02: returns all 4 categories even if empty', () => {
            $checker = $this->createMockCheckerWithSuggestions('title_tag', [
                new Suggestion('warning', 'Title issue', null, 2),
            ]);

            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('title_tag', 'Title Tag', 'content');

            $collection = $service->generate([$result]);
            $grouped = $collection->groupByCategory();

            expect($grouped)->toHaveKeys(['content', 'technical', 'accessibility', 'performance']);
        });

        it('SG-004-03: multiple suggestions in same category', () => {
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

            $collection = $service->generate([$result]);
            $grouped = $collection->groupByCategory();

            expect($grouped['content'])->toHaveCount(2);
        });

        it('SG-004-04: category order is consistent', () => {
            $service = new SuggestionGeneratorService();
            $expectedOrder = ['content', 'technical', 'accessibility', 'performance'];

            $order = $service->getCategoryOrder();

            expect($order)->toBe($expectedOrder);
        });
    });

    // ============================================
    // SG-005: Limit Suggestions Per Indicator
    // ============================================
    describe('Limit Suggestions Per Indicator', () => {
        it('SG-005-01: limits suggestions to configured limit', () => {
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

            expect($collection->count())->toBe(3);
        });

        it('SG-005-02: keeps highest priority suggestions when limiting', () => {
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

            expect($messages)->toContain('High');
            expect($messages)->toContain('Medium');
        });

        it('SG-005-03: no limit when set to 0 or null', () => {
            $suggestions = array_map(
                fn($i) => new Suggestion('warning', "Issue $i", null, 1),
                range(1, 10)
            );

            $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
            $service = new SuggestionGeneratorService(['limitPerIndicator' => 0], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->count())->toBe(10);
        });

        it('SG-005-04: limit can be changed after instantiation', () => {
            $service = new SuggestionGeneratorService(['limitPerIndicator' => 3]);

            $service->setLimitPerIndicator(5);

            expect($service->getLimitPerIndicator())->toBe(5);
        });
    });

    // ============================================
    // SG-006: SuggestionCollection Return Type
    // ============================================
    describe('SuggestionCollection Return Type', () => {
        it('SG-006-01: returns SuggestionCollection instance', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate([]);

            expect($collection)->toBeInstanceOf(SuggestionCollection::class);
        });

        it('SG-006-02: collection has all() method', () => {
            $service = new SuggestionGeneratorService();
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('warning', 'Test', null, 1),
            ]);
            $service->addChecker($checker);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->all())->toBeArray();
        });

        it('SG-006-03: collection has count() method', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate([]);

            expect($collection->count())->toBeInt();
        });

        it('SG-006-04: collection has isEmpty() method', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate([]);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('SG-006-05: collection has first() method', () => {
            $service = new SuggestionGeneratorService();
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('warning', 'First', null, 1),
            ]);
            $service->addChecker($checker);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->first())->toBeInstanceOf(Suggestion::class);
        });

        it('SG-006-06: collection has groupByCategory() method', () => {
            $service = new SuggestionGeneratorService();

            $collection = $service->generate([]);

            expect($collection->groupByCategory())->toBeArray();
        });

        it('SG-006-07: collection has getByPriority() method', () => {
            $service = new SuggestionGeneratorService();
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('warning', 'High', null, 3),
                new Suggestion('warning', 'Low', null, 1),
            ]);
            $service->addChecker($checker);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);
            $high = $collection->getByPriority(3);

            expect($high)->toHaveCount(1);
            expect($high[0]->message)->toBe('High');
        });

        it('SG-006-08: collection has getByType() method', () => {
            $service = new SuggestionGeneratorService();
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('error', 'Error', null, 2),
                new Suggestion('warning', 'Warning', null, 2),
            ]);
            $service->addChecker($checker);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);
            $errors = $collection->getByType('error');

            expect($errors)->toHaveCount(1);
        });
    });

    // ============================================
    // SG-007: Handle Indicators with No Suggestions
    // ============================================
    describe('Handle Indicators with No Suggestions', () => {
        it('SG-007-01: checker returns empty array', () => {
            $checker = $this->createMockCheckerWithSuggestions('test', []);
            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('SG-007-02: checker returns null from getSuggestions', () => {
            $checker = $this->createMockChecker('test', 'Test', 'content');
            $checker->method('getSuggestions')->willReturn(null);

            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('SG-007-03: mixed results (some with suggestions, some without)', () => {
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

            expect($collection->count())->toBe(1);
        });

        it('SG-007-04: result with score 3 (excellent) may have no suggestions', () => {
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

            expect($collection->isEmpty())->toBeTrue();
        });
    });

    // ============================================
    // SG-008: Quick Wins Category
    // ============================================
    describe('Quick Wins Category', () => {
        it('SG-008-01: identifies quick wins (easy fixes)', () => {
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('warning', 'Easy fix', 'Add title tag', 2),
            ]);

            $service = new SuggestionGeneratorService(['enableQuickWins' => true], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);
            $quickWins = $collection->getQuickWins();

            expect($quickWins)->toHaveCount(1);
        });

        it('SG-008-02: quick wins are high impact, easy to fix', () => {
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
            expect($quickWins[0]->action)->toContain('Add');
        });

        it('SG-008-03: can disable quick wins feature', () => {
            $checker = $this->createMockCheckerWithSuggestions('test', [
                new Suggestion('warning', 'Fix', 'Add title', 2),
            ]);

            $service = new SuggestionGeneratorService(['enableQuickWins' => false], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $collection = $service->generate([$result]);

            expect($collection->getQuickWins())->toHaveCount(0);
        });
    });

    // ============================================
    // SG-009: Error Handling
    // ============================================
    describe('Error Handling', () => {
        it('SG-009-01: handles checker throwing exception', () => {
            $checker = $this->createMockChecker('test', 'Test', 'content');
            $checker->method('getSuggestions')
                ->willThrowException(new \Exception('Checker error'));

            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            // Should not throw, should handle gracefully
            $collection = $service->generate([$result]);

            expect($collection)->toBeInstanceOf(SuggestionCollection::class);
        });

        it('SG-009-02: logs error when checker fails', () => {
            $checker = $this->createMockChecker('test', 'Test', 'content');
            $checker->method('getSuggestions')
                ->willThrowException(new \Exception('Checker error'));

            $service = new SuggestionGeneratorService([], [$checker]);
            $result = $this->createResultForChecker('test', 'Test', 'content');

            $service->generate([$result]);

            // Logger should have been called
            // Implementation will need a logger mock
        });

        it('SG-009-03: continues processing other checkers on error', () => {
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

            expect($collection->count())->toBe(1);
        });

        it('SG-009-04: handles invalid result type', () => {
            $service = new SuggestionGeneratorService();

            // Should throw or handle gracefully
            expect(fn() => $service->generate(['not a result']))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    // ============================================
    // SG-010: Performance Tests
    // ============================================
    describe('Performance', () => {
        it('SG-010-01: handles 17 indicators efficiently', () => {
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

            expect($collection->count())->toBe(17);
            expect($duration)->toBeLessThan(0.1); // 100ms max
        });

        it('SG-010-02: handles large suggestion counts', () => {
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

            expect($collection->count())->toBe(100);
            expect($duration)->toBeLessThan(0.05); // 50ms max
        });
    });

    // ============================================
    // SG-011: Integration Scenarios
    // ============================================
    describe('Integration Scenarios', () => {
        it('SG-011-01: realistic onboarding flow', () => {
            // Simulate a typical onboarding with mixed results
            $checkers = [
                $this->createMockCheckerWithSuggestions('title_tag', [
                    new Suggestion('error', 'Missing title tag', 'Add a title tag', 3),
                ]),
                $this->createMockCheckerWithSuggestions('meta_desc', [
                    new Suggestion('warning', 'Meta description too short', 'Expand to 150+ characters', 2),
                ]),
                $this->createMockCheckerWithSuggestions('h1', [
                    new Suggestion('warning', 'Multiple H1 tags found', 'Use only one H1', 2),
                ]),
                $this->createMockCheckerWithSuggestions('images_alt', [
                    new Suggestion('info', '3 images missing alt text', 'Add descriptive alt text', 1),
                ]),
            ];

            $results = [
                $this->createResultForChecker('title_tag', 'Title Tag', 'content', 1),
                $this->createResultForChecker('meta_desc', 'Meta Description', 'content', 2),
                $this->createResultForChecker('h1', 'H1 Tag', 'content', 1),
                $this->createResultForChecker('images_alt', 'Image Alt Text', 'accessibility', 2),
            ];

            $service = new SuggestionGeneratorService(['enableQuickWins' => true], $checkers);
            $collection = $service->generate($results);

            // Verify prioritization
            expect($collection->count())->toBe(4);
            expect($collection->first()->priority)->toBe(3); // Error first

            // Verify grouping
            $grouped = $collection->groupByCategory();
            expect($grouped['content'])->toHaveCount(3);
            expect($grouped['accessibility'])->toHaveCount(1);

            // Verify quick wins
            $quickWins = $collection->getQuickWins();
            expect($quickWins)->toHaveCount(1);
        });

        it('SG-011-02: all indicators pass (excellent site)', () => {
            $checkers = [];
            $results = [];

            foreach (['title_tag', 'meta_desc', 'h1', 'ssl'] as $id) {
                $checkers[] = $this->createMockCheckerWithSuggestions($id, []);
                $results[] = new IndicatorResult(
                    id: $id,
                    name: ucfirst(str_replace('_', ' ', $id)),
                    category: 'content',
                    score: 3,
                    weight: 1.0,
                    passed: true
                );
            }

            $service = new SuggestionGeneratorService([], $checkers);
            $collection = $service->generate($results);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('SG-011-03: all indicators fail (poor site)', () => {
            $suggestions = [
                new Suggestion('error', 'Critical issue 1', 'Fix immediately', 3),
                new Suggestion('error', 'Critical issue 2', 'Fix immediately', 3),
                new Suggestion('warning', 'Warning issue', 'Consider fixing', 2),
            ];

            $checker = $this->createMockCheckerWithSuggestions('test', $suggestions);
            $service = new SuggestionGeneratorService(['limitPerIndicator' => 0], [$checker]);
            $result = new IndicatorResult(
                id: 'test',
                name: 'Test',
                category: 'content',
                score: 1,
                weight: 1.0,
                passed: false
            );

            $collection = $service->generate([$result]);

            expect($collection->count())->toBe(3);
            $errors = $collection->getByType('error');
            expect($errors)->toHaveCount(2);
        });
    });

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
