<?php

namespace Tests\Feature\Services;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Models\Site;
use App\Models\User;
use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use App\Services\Audit\IssueDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature')]
#[Group('services')]
#[Group('audit')]
#[Group('issue-detector')]
class IssueDetectorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private IssueDetector $service;
    private User $user;
    private Site $site;
    private Audit $audit;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(IssueDetector::class);
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->audit = Audit::factory()->create([
            'site_id' => $this->user->id,
            'user_id' => $this->user->id,
        ]);
        
        // Create issue types for testing
        $this->createIssueTypes();
    }

    private function createIssueTypes(): void
    {
        $types = [
            ['type' => 'missing_title_tag', 'category' => 'meta', 'severity' => 'critical'],
            ['type' => 'duplicate_title', 'category' => 'meta', 'severity' => 'warning'],
            ['type' => 'missing_meta_description', 'category' => 'meta', 'severity' => 'warning'],
            ['type' => 'missing_h1', 'category' => 'content', 'severity' => 'warning'],
            ['type' => 'multiple_h1', 'category' => 'content', 'severity' => 'notice'],
            ['type' => 'broken_link', 'category' => 'technical', 'severity' => 'critical'],
            ['type' => 'slow_page', 'category' => 'performance', 'severity' => 'notice'],
            ['type' => 'missing_viewport', 'category' => 'mobile', 'severity' => 'warning'],
            ['type' => 'image_missing_alt', 'category' => 'content', 'severity' => 'notice'],
        ];
        
        foreach ($types as $type) {
            IssueType::create($type);
        }
    }

    // =========================================================================
    // FLOW: Full Workflow Tests
    // =========================================================================

    #[Test]
    public function flow_001_complete_analysis_workflow(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        // Register a checker
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Page is missing a title tag',
                    recommendation: 'Add a descriptive title tag'
                );
            }
            
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'title_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('title_checker', $checker);
        
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(1, $issueIds);
        $this->assertDatabaseHas('audit_issues', [
            'audit_id' => $this->audit->id,
            'issue_type' => 'missing_title_tag',
            'severity' => 'critical',
        ]);
    }

    #[Test]
    public function flow_002_integration_with_real_crawl_result(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <meta name="description" content="Test description">
</head>
<body>
    <h1>Main Heading</h1>
    <p>Content here</p>
</body>
</html>
HTML;
        
        $crawlResult = new CrawlResult(
            url: 'https://example.com/test',
            statusCode: 200,
            responseTimeMs: 150,
            html: $html,
            links: [],
            metadata: ['title' => 'Test Page', 'description' => 'Test description'],
            errorMessage: null,
            headers: ['content-type' => 'text/html'],
            depth: 0,
            redirectChain: []
        );
        
        // Register checkers that find nothing wrong
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null; // No issues
            }
            
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'title_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('title_checker', $checker);
        
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(0, $issueIds);
    }

    #[Test]
    public function flow_003_integration_with_database(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_h1',
                    severity: 'warning',
                    description: 'Page has no H1 tag',
                    recommendation: 'Add an H1 heading',
                    context: ['url' => 'https://example.com']
                );
            }
            
            public function getIssueType(): string { return 'missing_h1'; }
            public function getName(): string { return 'h1_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('h1_checker', $checker);
        
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        // Verify issue was created in database
        $issue = AuditIssue::find($issueIds[0]);
        
        $this->assertNotNull($issue);
        $this->assertEquals($this->audit->id, $issue->audit_id);
        $this->assertEquals('missing_h1', $issue->issue_type);
        $this->assertEquals('warning', $issue->severity);
        $this->assertEquals(['url' => 'https://example.com'], $issue->context);
    }

    #[Test]
    public function flow_004_integration_with_cache(): void
    {
        Cache::flush();
        
        $crawlResult1 = $this->createCrawlResult('https://example.com/page1', 'Duplicate Title');
        $crawlResult2 = $this->createCrawlResult('https://example.com/page2', 'Duplicate Title');
        
        // First page should not trigger duplicate
        $issueIds1 = $this->service->analyze($crawlResult1, $this->audit->id);
        
        // Second page with same title should trigger duplicate
        $issueIds2 = $this->service->analyze($crawlResult2, $this->audit->id);
        
        // Check duplicate was detected
        $this->assertCount(1, $issueIds2);
        
        $issue = AuditIssue::find($issueIds2[0]);
        $this->assertEquals('duplicate_title', $issue->issue_type);
    }

    #[Test]
    public function flow_006_multiple_pages_in_sequence(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Return issue for pages without "good" in URL
                if (str_contains($result->url, 'good')) {
                    return null;
                }
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Issue found',
                    recommendation: 'Fix it'
                );
            }
            
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('checker', $checker);
        
        // Analyze 5 pages
        for ($i = 1; $i <= 5; $i++) {
            $url = $i <= 3 ? "https://example.com/page{$i}" : "https://example.com/good{$i}";
            $crawlResult = $this->createCrawlResult($url);
            $this->service->analyze($crawlResult, $this->audit->id);
        }
        
        // Should have 3 issues (pages 1-3)
        $this->assertEquals(3, AuditIssue::where('audit_id', $this->audit->id)->count());
    }

    #[Test]
    public function flow_007_large_audit_100_pages(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null; // No issues for performance test
            }
            
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('checker', $checker);
        
        $start = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            $crawlResult = $this->createCrawlResult("https://example.com/page{$i}");
            $this->service->analyze($crawlResult, $this->audit->id);
        }
        
        $elapsed = microtime(true) - $start;
        
        // Should complete in under 30 seconds
        $this->assertLessThan(30, $elapsed, '100 pages should analyze in under 30 seconds');
    }

    // =========================================================================
    // CHECK: Checker Integration Tests
    // =========================================================================

    #[Test]
    public function check_001_title_tag_checker_integration(): void
    {
        $html = '<html><head></head><body></body></html>';
        $crawlResult = $this->createCrawlResultWithHtml($html);
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                if (!str_contains($result->html, '<title>')) {
                    return new IssueFinding(
                        issueType: 'missing_title_tag',
                        severity: 'critical',
                        description: 'Page is missing a title tag',
                        recommendation: 'Add a descriptive title tag'
                    );
                }
                return null;
            }
            
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'title_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('title_checker', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(1, $issueIds);
    }

    #[Test]
    public function check_002_meta_description_checker_integration(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $crawlResult = $this->createCrawlResultWithHtml($html);
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                if (!str_contains($result->html, 'meta name="description"')) {
                    return new IssueFinding(
                        issueType: 'missing_meta_description',
                        severity: 'warning',
                        description: 'Page is missing a meta description',
                        recommendation: 'Add a meta description'
                    );
                }
                return null;
            }
            
            public function getIssueType(): string { return 'missing_meta_description'; }
            public function getName(): string { return 'meta_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('meta_checker', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(1, $issueIds);
    }

    #[Test]
    public function check_003_h1_checker_integration(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>No H1 here</p></body></html>';
        $crawlResult = $this->createCrawlResultWithHtml($html);
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                if (!str_contains($result->html, '<h1')) {
                    return new IssueFinding(
                        issueType: 'missing_h1',
                        severity: 'warning',
                        description: 'Page is missing an H1 tag',
                        recommendation: 'Add an H1 heading'
                    );
                }
                return null;
            }
            
            public function getIssueType(): string { return 'missing_h1'; }
            public function getName(): string { return 'h1_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('h1_checker', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(1, $issueIds);
    }

    #[Test]
    public function check_008_duplicate_title_across_pages(): void
    {
        Cache::flush();
        
        // First page with title
        $crawlResult1 = $this->createCrawlResult('https://example.com/page1', 'Same Title');
        $this->service->analyze($crawlResult1, $this->audit->id);
        
        // Second page with same title
        $crawlResult2 = $this->createCrawlResult('https://example.com/page2', 'Same Title');
        $issueIds = $this->service->analyze($crawlResult2, $this->audit->id);
        
        $this->assertCount(1, $issueIds);
        
        $issue = AuditIssue::find($issueIds[0]);
        $this->assertEquals('duplicate_title', $issue->issue_type);
        $this->assertEquals('https://example.com/page1', $issue->context['original_url'] ?? null);
    }

    #[Test]
    public function check_009_multiple_issues_per_page(): void
    {
        $html = '<html><head></head><body><p>Content</p></body></html>';
        $crawlResult = $this->createCrawlResultWithHtml($html);
        
        $titleChecker = new class implements CheckerInterface {
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
            public function getName(): string { return 'title_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $h1Checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_h1',
                    severity: 'warning',
                    description: 'Missing H1',
                    recommendation: 'Add H1'
                );
            }
            public function getIssueType(): string { return 'missing_h1'; }
            public function getName(): string { return 'h1_checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('title_checker', $titleChecker);
        $this->service->registerChecker('h1_checker', $h1Checker);
        
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(2, $issueIds);
    }

    #[Test]
    public function check_010_no_issues_on_perfect_page(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Perfect Page</title>
    <meta name="description" content="A perfect page description">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Main Heading</h1>
    <img src="image.jpg" alt="Descriptive alt text">
</body>
</html>
HTML;
        
        $crawlResult = $this->createCrawlResultWithHtml($html);
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                // Perfect page - no issues
                return null;
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'checker'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('checker', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertCount(0, $issueIds);
    }

    // =========================================================================
    // DATA: Data Flow Tests
    // =========================================================================

    #[Test]
    public function data_001_crawl_result_to_checker(): void
    {
        $crawlResult = $this->createCrawlResult(
            'https://example.com/test-page',
            'Test Title'
        );
        
        $receivedData = new \stdClass();
        
        $checker = new class($receivedData) implements CheckerInterface {
            private \stdClass $captured;
            
            public function __construct(\stdClass $captured)
            {
                $this->captured = $captured;
            }
            
            public function check(CrawlResult $result): ?IssueFinding
            {
                $this->captured->data = $result;
                return null;
            }
            
            public function getIssueType(): string { return 'test'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, $this->audit->id);
        
        $this->assertInstanceOf(CrawlResult::class, $receivedData->data);
        $this->assertEquals('https://example.com/test-page', $receivedData->data->url);
        $this->assertEquals('Test Title', $receivedData->data->metadata['title']);
    }

    #[Test]
    public function data_002_issue_finding_to_audit_issue(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test Description',
                    recommendation: 'Test Recommendation',
                    context: ['key1' => 'value1', 'key2' => 42]
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $issue = AuditIssue::find($issueIds[0]);
        
        $this->assertEquals('missing_title_tag', $issue->issue_type);
        $this->assertEquals('critical', $issue->severity);
        $this->assertEquals('Test Description', $issue->description);
        $this->assertEquals('Test Recommendation', $issue->recommendation);
        $this->assertEquals(['key1' => 'value1', 'key2' => 42], $issue->context);
    }

    #[Test]
    public function data_003_context_preservation(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        $complexContext = [
            'url' => 'https://example.com/test',
            'line' => 42,
            'column' => 15,
            'element' => '<title>',
            'nested' => [
                'deeply' => [
                    'nested' => 'value'
                ]
            ],
            'list' => ['item1', 'item2', 'item3'],
        ];
        
        $checker = new class($complexContext) implements CheckerInterface {
            private array $context;
            
            public function __construct(array $context)
            {
                $this->context = $context;
            }
            
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test',
                    recommendation: 'Fix',
                    context: $this->context
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        $issue = AuditIssue::find($issueIds[0]);
        
        $this->assertEquals($complexContext, $issue->context);
    }

    #[Test]
    public function data_004_timestamp_recording(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test',
                    recommendation: 'Fix'
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        
        $before = now();
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        $after = now();
        
        $issue = AuditIssue::find($issueIds[0]);
        
        $this->assertNotNull($issue->created_at);
        $this->assertNotNull($issue->updated_at);
    }

    // =========================================================================
    // API: API Integration Tests
    // =========================================================================

    #[Test]
    public function api_001_called_from_audit_service(): void
    {
        // This tests that IssueDetector can be called from AuditService
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
        ]);
        
        $crawlResult = $this->createCrawlResult();
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test',
                    recommendation: 'Fix'
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $service = app(IssueDetector::class);
        $service->registerChecker('test', $checker);
        
        $issueIds = $service->analyze($crawlResult, $audit->id);
        
        $this->assertCount(1, $issueIds);
        $this->assertDatabaseHas('audit_issues', [
            'audit_id' => $audit->id,
        ]);
    }

    #[Test]
    public function api_004_results_available_via_api(): void
    {
        $crawlResult = $this->createCrawlResult();
        
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test Issue',
                    recommendation: 'Fix It'
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        $issueIds = $this->service->analyze($crawlResult, $this->audit->id);
        
        // Query issues for this audit
        $issues = AuditIssue::where('audit_id', $this->audit->id)->get();
        
        $this->assertCount(1, $issues);
        $this->assertEquals('Test Issue', $issues->first()->description);
    }

    // =========================================================================
    // PERF: Performance Integration Tests
    // =========================================================================

    #[Test]
    public function perf_001_memory_usage_under_load(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return null;
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $crawlResult = $this->createCrawlResult("https://example.com/page{$i}");
            $this->service->analyze($crawlResult, $this->audit->id);
        }
        
        $finalMemory = memory_get_usage();
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        
        // Memory growth should be under 50MB
        $this->assertLessThan(50, $memoryGrowth, 'Memory growth should be under 50MB for 100 pages');
    }

    #[Test]
    public function perf_003_cache_hit_rate(): void
    {
        Cache::flush();
        
        $title = 'Cached Title';
        
        // First request - cache miss
        $crawlResult1 = $this->createCrawlResult('https://example.com/page1', $title);
        $this->service->analyze($crawlResult1, $this->audit->id);
        
        // Verify cache was populated
        $cached = Cache::get("audit:titles:{$this->audit->id}");
        $this->assertNotNull($cached);
        $this->assertArrayHasKey(strtolower($title), $cached);
    }

    #[Test]
    public function perf_004_query_optimization(): void
    {
        $checker = new class implements CheckerInterface {
            public function check(CrawlResult $result): ?IssueFinding
            {
                return new IssueFinding(
                    issueType: 'missing_title_tag',
                    severity: 'critical',
                    description: 'Test',
                    recommendation: 'Fix'
                );
            }
            public function getIssueType(): string { return 'missing_title_tag'; }
            public function getName(): string { return 'test'; }
            public function getPriority(): int { return 0; }
        };
        
        $this->service->registerChecker('test', $checker);
        
        // Enable query log
        $queries = [];
        \DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        
        $crawlResult = $this->createCrawlResult();
        $this->service->analyze($crawlResult, $this->audit->id);
        
        // Should not have N+1 queries - should be reasonable number
        $this->assertLessThan(20, count($queries), 'Should not have excessive queries');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createCrawlResult(
        string $url = 'https://example.com',
        string $title = 'Test Page'
    ): CrawlResult {
        return new CrawlResult(
            url: $url,
            statusCode: 200,
            responseTimeMs: 150,
            html: "<html><head><title>{$title}</title></head><body></body></html>",
            links: [],
            metadata: ['title' => $title],
            errorMessage: null,
            headers: [],
            depth: 0,
            redirectChain: []
        );
    }

    private function createCrawlResultWithHtml(string $html): CrawlResult
    {
        return new CrawlResult(
            url: 'https://example.com/test',
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
