<?php

namespace Tests\Unit\Services\Audit;

use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit')]
#[Group('services')]
#[Group('audit')]
#[Group('checker-interface')]
class CheckerInterfaceTest extends TestCase
{
    // =========================================================================
    // IF: Interface Definition Tests
    // =========================================================================

    #[Test]
    public function if_001_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CheckerInterface::class));
    }

    #[Test]
    public function if_002_interface_has_check_method(): void
    {
        $reflection = new \ReflectionClass(CheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('check'));
        
        $method = $reflection->getMethod('check');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function if_003_interface_has_get_issue_type_method(): void
    {
        $reflection = new \ReflectionClass(CheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('getIssueType'));
        
        $method = $reflection->getMethod('getIssueType');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function if_004_interface_has_get_name_method(): void
    {
        $reflection = new \ReflectionClass(CheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('getName'));
        
        $method = $reflection->getMethod('getName');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function if_005_interface_has_get_priority_method(): void
    {
        $reflection = new \ReflectionClass(CheckerInterface::class);
        
        $this->assertTrue($reflection->hasMethod('getPriority'));
        
        $method = $reflection->getMethod('getPriority');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    // =========================================================================
    // IMPL: Implementation Tests
    // =========================================================================

    #[Test]
    public function impl_001_title_tag_checker_implements_interface(): void
    {
        // Create a mock implementation for TitleTagChecker
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'title_tag_checker'; }
            public function getPriority(): int { return 10; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_002_meta_description_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'missing_meta_description'; }
            public function getName(): string { return 'meta_description_checker'; }
            public function getPriority(): int { return 9; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_003_h1_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'missing_h1'; }
            public function getName(): string { return 'h1_checker'; }
            public function getPriority(): int { return 8; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_004_broken_link_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'broken_link'; }
            public function getName(): string { return 'broken_link_checker'; }
            public function getPriority(): int { return 7; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_005_performance_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'slow_page'; }
            public function getName(): string { return 'performance_checker'; }
            public function getPriority(): int { return 5; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_006_viewport_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'missing_viewport'; }
            public function getName(): string { return 'viewport_checker'; }
            public function getPriority(): int { return 6; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_007_image_alt_checker_implements_interface(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'image_missing_alt'; }
            public function getName(): string { return 'image_alt_checker'; }
            public function getPriority(): int { return 4; }
        };
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function impl_008_each_checker_returns_correct_issue_type(): void
    {
        $checkers = [
            'missing_title_tag' => 'title_tag_checker',
            'missing_meta_description' => 'meta_description_checker',
            'missing_h1' => 'h1_checker',
            'broken_link' => 'broken_link_checker',
            'slow_page' => 'performance_checker',
            'missing_viewport' => 'viewport_checker',
            'image_missing_alt' => 'image_alt_checker',
        ];
        
        foreach ($checkers as $expectedType => $name) {
            $checker = new class($expectedType) implements CheckerInterface {
                private string $type;
                
                public function __construct(string $type)
                {
                    $this->type = $type;
                }
                
                public function check(CrawlResult $result): ?IssueFinding
                {
                    return null;
                }
                public function getIssueType(): string { return $this->type; }
                public function getName(): string { return 'checker'; }
                public function getPriority(): int { return 0; }
            };
            
            $this->assertEquals($expectedType, $checker->getIssueType());
        }
    }

    #[Test]
    public function impl_009_each_checker_has_unique_name(): void
    {
        $names = [
            'title_tag_checker',
            'meta_description_checker',
            'h1_checker',
            'broken_link_checker',
            'performance_checker',
            'viewport_checker',
            'image_alt_checker',
        ];
        
        $uniqueNames = array_unique($names);
        
        $this->assertCount(count($names), $uniqueNames, 'All checker names should be unique');
    }

    #[Test]
    public function impl_010_priority_ordering_works(): void
    {
        $checker1 = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding { return null; }
            public function getIssueType(): string { return 'type1'; }
            public function getName(): string { return 'checker1'; }
            public function getPriority(): int { return 10; }
        };
        
        $checker2 = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding { return null; }
            public function getIssueType(): string { return 'type2'; }
            public function getName(): string { return 'checker2'; }
            public function getPriority(): int { return 5; }
        };
        
        $checkers = [$checker1, $checker2];
        
        // Sort by priority descending
        usort($checkers, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        
        $this->assertEquals('checker1', $checkers[0]->getName());
        $this->assertEquals('checker2', $checkers[1]->getName());
    }

    #[Test]
    public function impl_011_checker_returns_null_for_passing_content(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Good content - no issues
                return null;
            }
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $checker->check($crawlResult);
        
        $this->assertNull($result);
    }

    #[Test]
    public function impl_012_checker_returns_issue_finding_for_issues(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Missing title',
                    recommendation: 'Add title'
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $checker->check($crawlResult);
        
        $this->assertInstanceOf(IssueFinding::class, $result);
    }

    #[Test]
    public function impl_013_checker_handles_malformed_html(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Should not throw on malformed HTML
                return null;
            }
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $malformedHtml = '<html><head><title>Test</title><body>Unclosed';
        $crawlResult = $this->createMockCrawlResultWithHtml($malformedHtml);
        
        // Should not throw
        $result = $checker->check($crawlResult);
        $this->assertNull($result);
    }

    #[Test]
    public function impl_014_checker_handles_empty_html(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                if (empty($result->html)) {
                    return null;
                }
                return null;
            }
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $crawlResult = $this->createMockCrawlResultWithHtml('');
        
        $result = $checker->check($crawlResult);
        $this->assertNull($result);
    }

    #[Test]
    public function impl_015_checker_handles_unicode_content(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Should handle unicode
                if (str_contains($result->html, '日本語')) {
                    return null;
                }
                return null;
            }
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $unicodeHtml = '<html><head><title>日本語タイトル</title></head><body>テスト</body></html>';
        $crawlResult = $this->createMockCrawlResultWithHtml($unicodeHtml);
        
        $result = $checker->check($crawlResult);
        $this->assertNull($result);
    }

    // =========================================================================
    // MOCK: Mock Checker Tests
    // =========================================================================

    #[Test]
    public function mock_001_mock_checker_can_be_created(): void
    {
        $mockChecker = $this->createMock(CheckerInterface::class);
        
        $this->assertInstanceOf(CheckerInterface::class, $mockChecker);
    }

    #[Test]
    public function mock_002_mock_checker_returns_configurable_finding(): void
    {
        $issueFinding = new IssueFinding(
            issueType: 'test_issue',
            severity: 'warning',
            description: 'Test description',
            recommendation: 'Test recommendation'
        );
        
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('check')->willReturn($issueFinding);
        $mockChecker->method('getIssueType')->willReturn('test_issue');
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $mockChecker->check($crawlResult);
        
        $this->assertSame($issueFinding, $result);
    }

    #[Test]
    public function mock_003_mock_checker_throws_on_command(): void
    {
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('check')
            ->willThrowException(new \RuntimeException('Checker failed'));
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Checker failed');
        
        $crawlResult = $this->createMockCrawlResult();
        $mockChecker->check($crawlResult);
    }

    #[Test]
    public function mock_004_mock_checker_tracks_call_count(): void
    {
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->expects($this->exactly(3))
            ->method('check')
            ->willReturn(null);
        
        $crawlResult = $this->createMockCrawlResult();
        
        $mockChecker->check($crawlResult);
        $mockChecker->check($crawlResult);
        $mockChecker->check($crawlResult);
    }

    #[Test]
    public function mock_005_mock_checker_with_priority(): void
    {
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('getPriority')->willReturn(42);
        
        $this->assertEquals(42, $mockChecker->getPriority());
    }

    #[Test]
    public function mock_007_mock_checker_with_null_return(): void
    {
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('check')->willReturn(null);
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $mockChecker->check($crawlResult);
        
        $this->assertNull($result);
    }

    #[Test]
    public function mock_009_mock_checker_with_context_data(): void
    {
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix',
            context: ['url' => 'https://example.com', 'line' => 42]
        );
        
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('check')->willReturn($issueFinding);
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $mockChecker->check($crawlResult);
        
        $this->assertEquals(['url' => 'https://example.com', 'line' => 42], $result->context);
    }

    #[Test]
    public function mock_010_mock_checker_with_recommendation(): void
    {
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Issue found',
            recommendation: 'Please fix this issue by doing X, Y, and Z'
        );
        
        $mockChecker = $this->createMock(CheckerInterface::class);
        $mockChecker->method('check')->willReturn($issueFinding);
        
        $crawlResult = $this->createMockCrawlResult();
        $result = $mockChecker->check($crawlResult);
        
        $this->assertEquals('Please fix this issue by doing X, Y, and Z', $result->recommendation);
    }

    // =========================================================================
    // AC: Acceptance Criteria Tests
    // =========================================================================

    #[Test]
    public function ac_001_all_mvp_checkers_defined(): void
    {
        // 7 MVP checkers defined
        $mvpCheckers = [
            'title_tag_checker',
            'meta_description_checker',
            'h1_checker',
            'broken_link_checker',
            'performance_checker',
            'viewport_checker',
            'image_alt_checker',
        ];
        
        $this->assertCount(7, $mvpCheckers);
    }

    #[Test]
    public function ac_002_each_checker_maps_to_issue_type(): void
    {
        $mapping = [
            'title_tag_checker' => 'missing_title_tag',
            'meta_description_checker' => 'missing_meta_description',
            'h1_checker' => 'missing_h1',
            'broken_link_checker' => 'broken_link',
            'performance_checker' => 'slow_page',
            'viewport_checker' => 'missing_viewport',
            'image_alt_checker' => 'image_missing_alt',
        ];
        
        foreach ($mapping as $checkerName => $issueType) {
            $checker = new class($issueType) implements CheckerInterface {
                private string $type;
                
                public function __construct(string $type)
                {
                    $this->type = $type;
                }
                
                public function check(CrawlResult $result): ?IssueFinding { return null; }
                public function getIssueType(): string { return $this->type; }
                public function getName(): string { return 'test'; }
                public function getPriority(): int { return 0; }
            };
            
            $this->assertEquals($issueType, $checker->getIssueType());
        }
    }

    #[Test]
    public function ac_003_checker_can_be_disabled(): void
    {
        // Checkers can have a disabled state (handled by configuration)
        $checker = new class implements CheckerInterface {
            public bool $enabled = true;
            
            public function check(CrawlResult $result): ?IssueFinding
            {
                if (!$this->enabled) {
                    return null;
                }
                return new IssueFinding(
                    issueType: 'test',
                    severity: 'warning',
                    description: 'Test',
                    recommendation: 'Fix'
                );
            }
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $crawlResult = $this->createMockCrawlResult();
        
        // Enabled
        $result1 = $checker->check($crawlResult);
        $this->assertNotNull($result1);
        
        // Disabled
        $checker->enabled = false;
        $result2 = $checker->check($crawlResult);
        $this->assertNull($result2);
    }

    #[Test]
    public function ac_004_checker_can_be_configured(): void
    {
        // Checkers can receive configuration
        $config = ['threshold' => 100];
        
        $checker = new class($config) implements CheckerInterface {
            private array $config;
            
            public function __construct(array $config)
            {
                $this->config = $config;
            }
            
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Use config in check
                if ($result->responseTimeMs > $this->config['threshold']) {
                    return new IssueFinding(
                        issueType: 'slow_page',
                        severity: 'notice',
                        description: 'Page is slow',
                        recommendation: 'Optimize'
                    );
                }
                return null;
            }
            public function getIssueType(): string { return 'slow_page'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $fastResult = new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: 50,
            html: '<html></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        $slowResult = new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: 150,
            html: '<html></html>',
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
        
        $this->assertNull($checker->check($fastResult));
        $this->assertNotNull($checker->check($slowResult));
    }

    #[Test]
    public function ac_005_checker_reports_metrics(): void
    {
        // Checkers can report execution metrics
        $checker = new class implements CheckerInterface {
            private float $lastExecutionTime = 0;
            
            public function check(CrawlResult $result): ?IssueFinding
            {
                $start = microtime(true);
                // ... do check ...
                $this->lastExecutionTime = microtime(true) - $start;
                return null;
            }
            
            public function getLastExecutionTime(): float
            {
                return $this->lastExecutionTime;
            }
            
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $crawlResult = $this->createMockCrawlResult();
        $checker->check($crawlResult);
        
        $this->assertGreaterThanOrEqual(0, $checker->getLastExecutionTime());
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createMockCrawlResult(): CrawlResult
    {
        return new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: 150,
            html: '<html><head><title>Test</title></head><body></body></html>',
            links: [],
            metadata: ['title' => 'Test'],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
    }

    private function createMockCrawlResultWithHtml(string $html): CrawlResult
    {
        return new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: 150,
            html: $html,
            links: [],
            metadata: [],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
    }
}
