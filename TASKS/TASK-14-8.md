# TASK-14-8: Create IssueDetector Service

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create the IssueDetector service that orchestrates all issue detection checks. This service receives crawl results and runs configured checkers to identify SEO issues.

## Dependencies
- TASK-14-2: Audit Issues table (Done)
- TASK-14-3: Issue Types table (Done)

## Technical Requirements

### Service Location
`backend/app/Services/Audit/IssueDetector.php`

### Core Methods
```php
class IssueDetector
{
    public function __construct(
        protected IssueTypeRepository $issueTypes,
        protected AuditIssueRepository $issues
    ) {}
    
    public function analyze(CrawlResult $result, int $auditId): array;
    public function runCheck(string $checkName, CrawlResult $result): ?Issue;
    public function getAvailableChecks(): array;
    public function registerChecker(string $name, CheckerInterface $checker): void;
}
```

### Architecture
```
IssueDetector (Orchestrator)
├── CheckerInterface
│   ├── TitleTagChecker
│   ├── MetaDescriptionChecker
│   ├── H1Checker
│   ├── BrokenLinkChecker
│   ├── PerformanceChecker
│   ├── ViewportChecker
│   └── ImageAltChecker
└── IssueFactory (creates AuditIssue records)
```

### Checker Interface
```php
interface CheckerInterface
{
    public function check(CrawlResult $result): ?IssueFinding;
    public function getIssueType(): string; // e.g., 'missing_title_tag'
}

class IssueFinding
{
    public string $issueType;
    public string $severity; // critical, warning, notice
    public string $description;
    public string $recommendation;
    public array $context = []; // Additional data
}
```

### Issue Types to Detect (MVP)
| Issue Type | Severity | Checker |
|------------|----------|---------|
| Missing title tag | Critical | TitleTagChecker |
| Duplicate title | Warning | TitleTagChecker |
| Missing meta description | Warning | MetaDescriptionChecker |
| Missing H1 | Warning | H1Checker |
| Multiple H1s | Notice | H1Checker |
| Broken link (404/5xx) | Critical | BrokenLinkChecker |
| Slow page (>3s) | Notice | PerformanceChecker |
| Missing viewport | Warning | ViewportChecker |
| Image missing alt | Notice | ImageAltChecker |

### Duplicate Detection
- Track title tags across crawl session
- Flag duplicates after first occurrence
- Store in cache: `audit:titles:{audit_id}`

## Acceptance Criteria
- [ ] IssueDetector service created with orchestrator pattern
- [ ] CheckerInterface defined and documented
- [ ] IssueFinding data structure implemented
- [ ] Support for registering custom checkers
- [ ] Duplicate title detection working
- [ ] Severity mapping from issue_types table
- [ ] Unit tests for orchestrator
- [ ] Integration tests with sample HTML

## Effort Estimate
6 hours

---

## Comments
_Agent communication log_

### [2026-03-07 08:55] Agent: ProjectManager
**Prioritization Analysis:**

| Metric | Value | Notes |
|--------|-------|-------|
| Priority | High | Foundation service for issue detection pipeline |
| Risk | Medium | Standard service implementation, no external APIs |
| Declared Dependencies | ✅ Satisfied | TASK-14-2 (Done), TASK-14-3 (Done) |
| Indirect Dependency | TASK-14-5 (To Develop) | CrawlResult data structure |

**Downstream Impact:**
- TASK-14-9 (HTML Analysis Checkers) - High Priority
- TASK-14-10 (Broken Link Checker) - Done (mocked)
- TASK-14-11 (Performance Checker) - Medium Priority
- TASK-14-12 (Image Checker) - Medium Priority

**Recommendation for PreTester:**
- Use TDD approach with mocked `CrawlResult` objects
- Define `CheckerInterface` and `IssueFinding` DTO in tests first
- SiteCrawler (TASK-14-5) provides real `CrawlResult` at runtime but is not a blocker for test design
- Tests can verify orchestrator behavior with mock checkers

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-03-07 10:15] Agent: PreTester
**Test Design Complete:**

| Metric | Value | Notes |
|--------|-------|-------|
| Test Files Created | 4 | IssueDetector, IssueFinding, CheckerInterface, Integration |
| Total Tests | 215+ | Exceeds minimum threshold |
| Test Categories | 12 | Comprehensive coverage |

**Test Files:**
1. `backend/tests/Unit/Services/Audit/IssueDetectorTest.php` - 95+ tests
2. `backend/tests/Unit/Services/Audit/DTOs/IssueFindingTest.php` - 45+ tests
3. `backend/tests/Unit/Services/Audit/CheckerInterfaceTest.php` - 35+ tests
4. `backend/tests/Feature/Services/IssueDetectorIntegrationTest.php` - 40+ tests

**Test Coverage:**
- Service instantiation and container binding
- Checker registration (single, multiple, bulk)
- Analyze workflow (no checkers, one, multiple)
- Run check method (by name, not found, case-sensitive)
- Duplicate title detection (cache-based, case-insensitive)
- Severity mapping (critical/warning/notice, database lookup)
- Error handling (exceptions, graceful degradation)
- Edge cases (empty HTML, unicode, large context)
- Security (XSS, SQL injection, cache key injection)
- Performance (100 pages < 30s, memory < 50MB)

**Key Design Decisions:**
- TDD approach with `CrawlResult` mocked from TASK-14-5 (not blocking)
- Duplicate detection uses cache key `audit:titles:{audit_id}`
- Severity mapped from `issue_types` table, checker can override
- `IssueFinding` DTO is immutable with validation

**Dependencies Verified:**
- TASK-14-2 (Audit Issues table) - Done ✅
- TASK-14-3 (Issue Types table) - Done ✅
- TASK-14-5 (SiteCrawler/CrawlResult) - Mocked in tests

**Status changed:** `To Write Tests` → `To Develop`
