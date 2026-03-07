<?php

namespace Tests\Unit\Services\Audit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\IssueType;
use App\Repositories\AuditIssueRepository;
use App\Repositories\IssueTypeRepository;
use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\Contracts\IssueDetectorInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use App\Services\Audit\IssueDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit')]
#[Group('services')]
#[Group('audit')]
#[Group('issue-detector')]
class IssueDetectorTest extends TestCase
{
    private IssueDetector $service;
    private IssueTypeRepository|MockInterface $issueTypesRepo;
    private AuditIssueRepository|MockInterface $issuesRepo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->issueTypesRepo = Mockery::mock(IssueTypeRepository::class);
        $this->issuesRepo = Mockery::mock(AuditIssueRepository::class);
        
        $this->service = new IssueDetector(
            $this->issueTypesRepo,
            $this->issuesRepo
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    #[Test]
    public function si_001_service_can_be_instantiated_via_container(): void
    {
        $service = app(IssueDetector::class);
        
        $this->assertInstanceOf(IssueDetector::class, $service);
    }

    #[Test]
    public function si_002_dependencies_injected_correctly(): void
    {
        $this->assertInstanceOf(IssueDetector::class, $this->service);
    }

    #[Test]
    public function si_003_get_available_checks_returns_empty_array_initially(): void
    {
        $checks = $this->service->getAvailableChecks();
        
        $this->assertEquals([], $checks);
    }

    #[Test]
    public function si_004_get_available_checks_returns_registered_checkers(): void
    {
        $checker = $this->createMockChecker('title_checker');
        
        $this->service->registerChecker('title_checker', $checker);
        $checks = $this->service->getAvailableChecks();
        
        $this->assertEquals(['title_checker'], $checks);
    }

    #[Test]
    public function si_005_service_implements_contract(): void
    {
        $this->assertInstanceOf(IssueDetectorInterface::class, $this->service);
    }

    // =========================================================================
    // REG: Checker Registration Tests
    // =========================================================================

    #[Test]
    public function reg_001_register_single_checker(): void
    {
        $checker = $this->createMockChecker('title_checker');
        
        $result = $this->service->registerChecker('title_checker', $checker);
        $checks = $this->service->getAvailableChecks();
        
        $this->assertEquals(['title_checker'], $checks);
        $this->assertSame($this->service, $result);
    }

    #[Test]
    public function reg_002_register_multiple_checkers(): void
    {
        $checker1 = $this->createMockChecker('title_checker');
        $checker2 = $this->createMockChecker('meta_checker');
        
        $this->service->registerChecker('title_checker', $checker1);
        $this->service->registerChecker('meta_checker', $checker2);
        $checks = $this->service->getAvailableChecks();
        
        $this->assertCount(2, $checks);
        $this->assertContains('title_checker', $checks);
        $this->assertContains('meta_checker', $checks);
    }

    #[Test]
    public function reg_003_register_checker_with_same_name_overwrites(): void
    {
        $checker1 = $this->createMockChecker('title_checker', 'type1');
        $checker2 = $this->createMockChecker('title_checker', 'type2');
        
        $this->service->registerChecker('title_checker', $checker1);
        $this->service->registerChecker('title_checker', $checker2);
        
        $checks = $this->service->getAvailableChecks();
        $this->assertEquals(['title_checker'], $checks);
    }

    #[Test]
    public function reg_004_register_checker_with_invalid_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $checker = $this->createMockChecker('');
        $this->service->registerChecker('', $checker);
    }

    #[Test]
    public function reg_006_get_available_checks_returns_names_only(): void
    {
        $checker1 = $this->createMockChecker('title_checker');
        $checker2 = $this->createMockChecker('meta_checker');
        
        $this->service->registerChecker('title_checker', $checker1);
        $this->service->registerChecker('meta_checker', $checker2);
        $checks = $this->service->getAvailableChecks();
        
        foreach ($checks as $check) {
            $this->assertIsString($check);
        }
    }

    #[Test]
    public function reg_007_register_maintains_insertion_order(): void
    {
        $checker1 = $this->createMockChecker('alpha_checker');
        $checker2 = $this->createMockChecker('beta_checker');
        $checker3 = $this->createMockChecker('gamma_checker');
        
        $this->service->registerChecker('alpha_checker', $checker1);
        $this->service->registerChecker('beta_checker', $checker2);
        $this->service->registerChecker('gamma_checker', $checker3);
        
        $checks = $this->service->getAvailableChecks();
        $this->assertEquals(['alpha_checker', 'beta_checker', 'gamma_checker'], $checks);
    }

    #[Test]
    public function reg_008_register_with_special_chars_in_name(): void
    {
        $checker = $this->createMockChecker('title-tag_checker');
        
        $this->service->registerChecker('title-tag_checker', $checker);
        $checks = $this->service->getAvailableChecks();
        
        $this->assertContains('title-tag_checker', $checks);
    }

    #[Test]
    public function reg_009_register_with_empty_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $checker = $this->createMockChecker('test');
        $this->service->registerChecker('', $checker);
    }

    #[Test]
    public function reg_010_register_returns_self_for_chaining(): void
    {
        $checker = $this->createMockChecker('test_checker');
        
        $result = $this->service->registerChecker('test_checker', $checker);
        
        $this->assertSame($this->service, $result);
    }

    #[Test]
    public function reg_011_bulk_registration_via_register_many(): void
    {
        $checker1 = $this->createMockChecker('checker1');
        $checker2 = $this->createMockChecker('checker2');
        
        $this->service->registerMany([
            'checker1' => $checker1,
            'checker2' => $checker2,
        ]);
        
        $checks = $this->service->getAvailableChecks();
        $this->assertCount(2, $checks);
    }

    // =========================================================================
    // ANALYZE: Analyze Method Tests
    // =========================================================================

    #[Test]
    public function analyze_001_analyze_with_no_checkers_returns_empty_array(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function analyze_002_analyze_with_one_checker_runs_checker_once(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')
            ->once()
            ->with($crawlResult)
            ->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('test_issue');
        $checker->shouldReceive('getName')->andReturn('test_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test_checker', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function analyze_003_analyze_with_multiple_checkers_runs_all(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $checker1 = Mockery::mock(CheckerInterface::class);
        $checker1->shouldReceive('check')->once()->andReturn(null);
        $checker1->shouldReceive('getIssueType')->andReturn('issue1');
        $checker1->shouldReceive('getName')->andReturn('checker1');
        $checker1->shouldReceive('getPriority')->andReturn(0);
        
        $checker2 = Mockery::mock(CheckerInterface::class);
        $checker2->shouldReceive('check')->once()->andReturn(null);
        $checker2->shouldReceive('getIssueType')->andReturn('issue2');
        $checker2->shouldReceive('getName')->andReturn('checker2');
        $checker2->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('checker1', $checker1);
        $this->service->registerChecker('checker2', $checker2);
        
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function analyze_004_analyze_creates_audit_issue_records(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Page is missing a title tag',
            recommendation: 'Add a title tag',
            context: ['url' => 'https://example.com']
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('missing_title_tag');
        $checker->shouldReceive('getName')->andReturn('title_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->with('missing_title_tag')
            ->andReturn((object)['id' => 1, 'severity' => 'critical']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('title_checker', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function analyze_005_analyze_with_null_finding_skips_issue_creation(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('test_issue');
        $checker->shouldReceive('getName')->andReturn('test_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issuesRepo->shouldNotReceive('create');
        
        $this->service->registerChecker('test_checker', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function analyze_006_analyze_returns_array_of_issue_ids(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Missing title',
            recommendation: 'Add title',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('missing_title_tag');
        $checker->shouldReceive('getName')->andReturn('title_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'critical']);
        $this->issuesRepo->shouldReceive('create')->andReturn(new AuditIssue(['id' => 123]));
        
        $this->service->registerChecker('title_checker', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([123], $result);
    }

    #[Test]
    public function analyze_007_analyze_with_crawl_result_passes_correct_data(): void
    {
        $crawlResult = $this->createMockCrawlResult('https://example.com/test');
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')
            ->once()
            ->withArgs(function ($arg) use ($crawlResult) {
                return $arg === $crawlResult;
            })
            ->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function analyze_008_analyze_with_audit_id_links_issues(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test_issue',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix it',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test_issue');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['audit_id']) && $data['audit_id'] === 42;
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 42);
    }

    #[Test]
    public function analyze_009_analyze_handles_checker_exception_gracefully(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $failingChecker = Mockery::mock(CheckerInterface::class);
        $failingChecker->shouldReceive('check')
            ->once()
            ->andThrow(new \RuntimeException('Checker failed'));
        $failingChecker->shouldReceive('getIssueType')->andReturn('test1');
        $failingChecker->shouldReceive('getName')->andReturn('failing');
        $failingChecker->shouldReceive('getPriority')->andReturn(0);
        
        $workingChecker = Mockery::mock(CheckerInterface::class);
        $workingChecker->shouldReceive('check')->once()->andReturn(null);
        $workingChecker->shouldReceive('getIssueType')->andReturn('test2');
        $workingChecker->shouldReceive('getName')->andReturn('working');
        $workingChecker->shouldReceive('getPriority')->andReturn(0);
        
        Log::shouldReceive('error')->once();
        
        $this->service->registerChecker('failing', $failingChecker);
        $this->service->registerChecker('working', $workingChecker);
        
        // Should not throw, should continue to working checker
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function analyze_010_analyze_stores_issue_context_as_json(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $context = ['url' => 'https://example.com', 'line' => 42, 'tag' => 'title'];
        
        $issueFinding = new IssueFinding(
            issueType: 'test_issue',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix',
            context: $context
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test_issue');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($context) {
                return $data['context'] === $context;
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    // =========================================================================
    // RUN: Run Check Method Tests
    // =========================================================================

    #[Test]
    public function run_001_run_existing_checker_by_name(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test_issue',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test_issue');
        $checker->shouldReceive('getName')->andReturn('test_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test_checker', $checker);
        $result = $this->service->runCheck('test_checker', $crawlResult);
        
        $this->assertInstanceOf(IssueFinding::class, $result);
        $this->assertEquals('test_issue', $result->issueType);
    }

    #[Test]
    public function run_002_run_non_existent_checker_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Checker not found');
        
        $crawlResult = $this->createMockCrawlResult();
        $this->service->runCheck('nonexistent', $crawlResult);
    }

    #[Test]
    public function run_003_run_checker_returns_issue_finding(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'notice',
            description: 'Desc',
            recommendation: 'Rec',
            context: ['key' => 'value']
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test', $checker);
        $result = $this->service->runCheck('test', $crawlResult);
        
        $this->assertSame($issueFinding, $result);
    }

    #[Test]
    public function run_004_run_checker_with_null_finding(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test', $checker);
        $result = $this->service->runCheck('test', $crawlResult);
        
        $this->assertNull($result);
    }

    #[Test]
    public function run_008_run_checker_case_sensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $crawlResult = $this->createMockCrawlResult();
        $checker = $this->createMockChecker('TestChecker');
        
        $this->service->registerChecker('TestChecker', $checker);
        $this->service->runCheck('testchecker', $crawlResult); // lowercase should fail
    }

    // =========================================================================
    // DUP: Duplicate Title Detection Tests
    // =========================================================================

    #[Test]
    public function dup_001_first_occurrence_of_title_not_flagged(): void
    {
        $crawlResult = $this->createMockCrawlResult('https://example.com/page1', 'Unique Title');
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('title');
        $checker->shouldReceive('getName')->andReturn('title_checker');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('title_checker', $checker);
        $this->service->analyze($crawlResult, 1);
        
        // Should not create duplicate issue
        $this->issuesRepo->shouldNotHaveReceived('create');
    }

    #[Test]
    public function dup_002_second_occurrence_of_same_title_flagged(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:1')
            ->andReturn(['Test Title' => 'https://example.com/page1']);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/page2', 'Test Title');
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->with('duplicate_title')
            ->andReturn((object)['id' => 2, 'severity' => 'warning']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['issue_type'] === 'duplicate_title';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function dup_003_duplicate_detection_across_pages(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:1')
            ->andReturn(['Same Title' => 'https://example.com/first']);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/second', 'Same Title');
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 2, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')->andReturn(new AuditIssue(['id' => 1]));
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function dup_004_duplicate_detection_case_insensitive(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:1')
            ->andReturn(['test title' => 'https://example.com/page1']);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/page2', 'TEST TITLE');
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 2, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')->andReturn(new AuditIssue(['id' => 1]));
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function dup_005_duplicate_detection_normalizes_whitespace(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:1')
            ->andReturn(['Test Title' => 'https://example.com/page1']);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/page2', 'Test   Title');
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 2, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')->andReturn(new AuditIssue(['id' => 1]));
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function dup_006_empty_title_handled(): void
    {
        Cache::shouldReceive('get')->with('audit:titles:1')->andReturn([]);
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/page', '');
        
        $result = $this->service->analyze($crawlResult, 1);
        
        // Empty title should not create duplicate issue
        $this->assertEquals([], $result);
    }

    #[Test]
    public function dup_007_duplicate_stores_first_url_as_context(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:1')
            ->andReturn(['Duplicate' => 'https://example.com/original']);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com/copy', 'Duplicate');
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 2, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['context']['original_url']) 
                    && $data['context']['original_url'] === 'https://example.com/original';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function dup_008_duplicate_uses_cache_key_pattern(): void
    {
        Cache::shouldReceive('get')
            ->with('audit:titles:42')
            ->once()
            ->andReturn([]);
        
        Cache::shouldReceive('put')
            ->with('audit:titles:42', Mockery::any(), Mockery::any())
            ->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com', 'Title');
        
        $this->service->analyze($crawlResult, 42);
    }

    // =========================================================================
    // SEV: Severity Mapping Tests
    // =========================================================================

    #[Test]
    public function sev_001_critical_severity_mapped_correctly(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'critical',
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('missing_title_tag');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'critical']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'critical';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sev_002_warning_severity_mapped_correctly(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'missing_meta_description',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('missing_meta_description');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'warning']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'warning';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sev_003_notice_severity_mapped_correctly(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'image_missing_alt',
            severity: 'notice',
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('image_missing_alt');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'notice']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'notice';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sev_004_unknown_severity_defaults_to_notice(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'unknown_issue',
            severity: 'unknown_severity',
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('unknown_issue');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'notice']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'notice';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sev_005_severity_from_issue_types_table(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'missing_title_tag',
            severity: 'warning', // Checker says warning
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('missing_title_tag');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        // Database says critical
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'critical']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'critical';
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sev_007_severity_case_normalization(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'CRITICAL', // Uppercase
            description: 'Test',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')
            ->andReturn((object)['id' => 1, 'severity' => 'critical']);
        
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['severity'] === 'critical'; // lowercase
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    // =========================================================================
    // ERR: Error Handling Tests
    // =========================================================================

    #[Test]
    public function err_001_checker_throws_exception(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')
            ->once()
            ->andThrow(new \RuntimeException('Checker error'));
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('failing');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        Log::shouldReceive('error')->once();
        
        $this->service->registerChecker('failing', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function err_003_invalid_crawl_result(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->analyze(null, 1);
    }

    #[Test]
    public function err_006_null_crawl_result(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->analyze(null, 1);
    }

    #[Test]
    public function err_007_missing_audit_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $crawlResult = $this->createMockCrawlResult();
        $this->service->analyze($crawlResult, null);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_empty_crawl_result_html(): void
    {
        $crawlResult = $this->createMockCrawlResult('https://example.com', '');
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->service->registerChecker('test', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function edge_003_unicode_in_title_for_duplicates(): void
    {
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->once();
        
        $unicodeTitle = 'テストタイトル — 中文 — العربية';
        $crawlResult = $this->createMockCrawlResult('https://example.com', $unicodeTitle);
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function edge_004_special_characters_in_issue_context(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Test with <script>alert("xss")</script>',
            recommendation: 'Fix it',
            context: ['html' => '<div onclick="alert(1)">test</div>']
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertCount(1, $result);
    }

    #[Test]
    public function edge_007_very_long_title_truncated(): void
    {
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                // Check that title was truncated
                $title = array_key_first($value);
                return strlen($title) <= 1000;
            });
        
        $longTitle = str_repeat('a', 2000);
        $crawlResult = $this->createMockCrawlResult('https://example.com', $longTitle);
        
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function edge_008_title_with_only_whitespace(): void
    {
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com', '   ');
        
        $result = $this->service->analyze($crawlResult, 1);
        
        $this->assertEquals([], $result);
    }

    #[Test]
    public function edge_011_zero_audit_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $crawlResult = $this->createMockCrawlResult();
        $this->service->analyze($crawlResult, 0);
    }

    #[Test]
    public function edge_012_negative_audit_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $crawlResult = $this->createMockCrawlResult();
        $this->service->analyze($crawlResult, -1);
    }

    // =========================================================================
    // SEC: Security Tests
    // =========================================================================

    #[Test]
    public function sec_001_xss_in_issue_description(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: '<script>alert("xss")</script>Test Description',
            recommendation: 'Fix',
            context: []
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // Description should be sanitized
                $this->assertStringNotContainsString('<script>', $data['description']);
                return true;
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sec_002_sql_injection_in_context(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        $issueFinding = new IssueFinding(
            issueType: 'test',
            severity: 'warning',
            description: 'Test',
            recommendation: 'Fix',
            context: ['value' => "'; DROP TABLE issues; --"]
        );
        
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->once()->andReturn($issueFinding);
        $checker->shouldReceive('getIssueType')->andReturn('test');
        $checker->shouldReceive('getName')->andReturn('test');
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        $this->issueTypesRepo->shouldReceive('findByType')->andReturn((object)['id' => 1, 'severity' => 'warning']);
        $this->issuesRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // Context should be JSON encoded, not raw SQL
                $this->assertIsArray($data['context']);
                return true;
            })
            ->andReturn(new AuditIssue(['id' => 1]));
        
        $this->service->registerChecker('test', $checker);
        $this->service->analyze($crawlResult, 1);
    }

    #[Test]
    public function sec_007_cache_key_injection(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->withArgs(function ($key) {
                // Key should be sanitized - audit ID should be numeric only
                $this->assertMatchesRegularExpression('/^audit:titles:\d+$/', $key);
                return true;
            })
            ->andReturn([]);
        
        Cache::shouldReceive('put')->once();
        
        $crawlResult = $this->createMockCrawlResult('https://example.com', 'Title');
        $this->service->analyze($crawlResult, 1);
    }

    // =========================================================================
    // PERF: Performance Tests
    // =========================================================================

    #[Test]
    public function perf_001_analyze_10_checkers(): void
    {
        $crawlResult = $this->createMockCrawlResult();
        
        for ($i = 1; $i <= 10; $i++) {
            $checker = Mockery::mock(CheckerInterface::class);
            $checker->shouldReceive('check')->once()->andReturn(null);
            $checker->shouldReceive('getIssueType')->andReturn("issue_$i");
            $checker->shouldReceive('getName')->andReturn("checker_$i");
            $checker->shouldReceive('getPriority')->andReturn(0);
            
            $this->service->registerChecker("checker_$i", $checker);
        }
        
        $start = microtime(true);
        $this->service->analyze($crawlResult, 1);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $elapsed, 'Analysis should complete in under 500ms');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createMockChecker(string $name, string $issueType = 'test_issue'): CheckerInterface
    {
        $checker = Mockery::mock(CheckerInterface::class);
        $checker->shouldReceive('check')->andReturn(null);
        $checker->shouldReceive('getIssueType')->andReturn($issueType);
        $checker->shouldReceive('getName')->andReturn($name);
        $checker->shouldReceive('getPriority')->andReturn(0);
        
        return $checker;
    }

    private function createMockCrawlResult(
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
}
