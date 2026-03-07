# TEST-14-8: IssueDetector Service Tests

## Task Reference
- **Task:** TASK-14-8: Create IssueDetector Service
- **Feature:** FEAT-014: SEO Audit Core
- **Status:** Tests Coverage In Progress

## Overview
Comprehensive test suite for the IssueDetector service that orchestrates all issue detection checks. Tests cover the orchestrator pattern, checker registration, duplicate title detection, severity mapping, and error handling.

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Services/Audit/IssueDetectorTest.php` | 95+ | 150+ | To Develop |
| `backend/tests/Unit/Services/Audit/CheckerInterfaceTest.php` | 35+ | 50+ | To Develop |
| `backend/tests/Unit/Services/Audit/DTOs/IssueFindingTest.php` | 45+ | 70+ | To Develop |
| `backend/tests/Feature/Services/IssueDetectorIntegrationTest.php` | 40+ | 60+ | To Develop |
| **Total** | **215+** | **330+** | To Develop |

## Test Categories

### IssueDetectorTest.php (95+ tests)

#### SI: Service Instantiation (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SI-001 | Service can be instantiated via container | Instance created |
| SI-002 | Dependencies injected correctly | IssueTypeRepository, AuditIssueRepository |
| SI-003 | `getAvailableChecks()` returns empty array initially | `[]` |
| SI-004 | `getAvailableChecks()` returns registered checkers | Array of checker names |
| SI-005 | Service implements contract | Instanceof IssueDetectorInterface |

#### REG: Checker Registration (12 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| REG-001 | Register single checker | Checker in available checks |
| REG-002 | Register multiple checkers | All checkers available |
| REG-003 | Register checker with same name overwrites | New checker replaces old |
| REG-004 | Register checker with invalid name throws | InvalidArgumentException |
| REG-005 | Register non-CheckerInterface throws | TypeError |
| REG-006 | Get available checks returns names only | Array of strings |
| REG-007 | Register maintains insertion order | Checkers in registration order |
| REG-008 | Register with special chars in name | Allowed (hyphens, underscores) |
| REG-009 | Register with empty name throws | InvalidArgumentException |
| REG-010 | Register returns self for chaining | `$this` returned |
| REG-011 | Bulk registration via registerMany | Multiple checkers registered |
| REG-012 | RegisterMany with invalid item throws | TypeError on non-interface |

#### ANALYZE: Analyze Method (15 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| ANALYZE-001 | Analyze with no checkers returns empty array | `[]` |
| ANALYZE-002 | Analyze with one checker runs checker once | Checker called once |
| ANALYZE-003 | Analyze with multiple checkers runs all | All checkers called |
| ANALYZE-004 | Analyze creates AuditIssue records | Issues stored in database |
| ANALYZE-005 | Analyze with null finding skips issue creation | No issue created |
| ANALYZE-006 | Analyze returns array of issue IDs | Array of integers |
| ANALYZE-007 | Analyze with CrawlResult passes correct data | Checker receives CrawlResult |
| ANALYZE-008 | Analyze with audit ID links issues | Audit ID stored in issue |
| ANALYZE-009 | Analyze handles checker exception gracefully | Logs error, continues |
| ANALYZE-010 | Analyze stores issue context as JSON | Context serialized |
| ANALYZE-011 | Analyze with empty CrawlResult | Processed without errors |
| ANALYZE-012 | Analyze performance with 10 checkers | < 500ms execution |
| ANALYZE-013 | Analyze returns summary with counts | Issues array + counts |
| ANALYZE-014 | Analyze skips disabled checkers | Disabled not called |
| ANALYZE-015 | Analyze respects checker priority order | Higher priority first |

#### RUN: Run Check Method (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| RUN-001 | Run existing checker by name | IssueFinding or null |
| RUN-002 | Run non-existent checker throws | InvalidArgumentException |
| RUN-003 | Run checker returns IssueFinding | Correct DTO returned |
| RUN-004 | Run checker with null finding | null returned |
| RUN-005 | Run checker passes CrawlResult | Correct data passed |
| RUN-006 | Run single checker does not create issue | No DB write |
| RUN-007 | Run checker with exception throws | CheckerException |
| RUN-008 | Run checker case sensitivity | Exact name match required |
| RUN-009 | Run checker with invalid CrawlResult | Graceful handling |
| RUN-010 | Run checker performance | < 100ms per checker |

#### DUP: Duplicate Title Detection (12 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| DUP-001 | First occurrence of title not flagged | No duplicate issue |
| DUP-002 | Second occurrence of same title flagged | Duplicate issue created |
| DUP-003 | Duplicate detection across pages | Same title different URLs |
| DUP-004 | Duplicate detection case insensitive | "Title" = "title" = "TITLE" |
| DUP-005 | Duplicate detection normalizes whitespace | Extra spaces ignored |
| DUP-006 | Empty title handled | No duplicate, no issue |
| DUP-007 | Duplicate stores first URL as context | Original URL in context |
| DUP-008 | Duplicate uses cache key pattern | `audit:titles:{audit_id}` |
| DUP-009 | Duplicate detection cache TTL | Persists during audit |
| DUP-010 | Duplicate with 3+ occurrences | Each flagged after first |
| DUP-011 | Duplicate cleared between audits | No cross-audit pollution |
| DUP-012 | Duplicate detection performance | O(1) cache lookup |

#### SEV: Severity Mapping (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SEV-001 | Critical severity mapped correctly | severity='critical' |
| SEV-002 | Warning severity mapped correctly | severity='warning' |
| SEV-003 | Notice severity mapped correctly | severity='notice' |
| SEV-004 | Unknown severity defaults to notice | severity='notice' |
| SEV-005 | Severity from issue_types table | Database lookup |
| SEV-006 | Severity override by checker | Checker value takes precedence |
| SEV-007 | Severity case normalization | UPPERCASE → lowercase |
| SEV-008 | Severity invalid value handled | Defaults to notice |
| SEV-009 | Severity stored in AuditIssue | Correct column value |
| SEV-010 | Severity affects issue ordering | Critical first in results |

#### ERR: Error Handling (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| ERR-001 | Checker throws exception | Logged, continues to next |
| ERR-002 | Repository unavailable | Graceful degradation |
| ERR-003 | Invalid CrawlResult | Returns empty array |
| ERR-004 | Cache unavailable for duplicates | Continues without detection |
| ERR-005 | Database write failure | Throws exception |
| ERR-006 | Null CrawlResult | InvalidArgumentException |
| ERR-007 | Missing audit ID | InvalidArgumentException |
| ERR-008 | Network timeout in checker | Logged as error |
| ERR-009 | Memory limit during analysis | Graceful termination |
| ERR-010 | Circular checker dependency | Detected and prevented |

#### EDGE: Edge Cases (12 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| EDGE-001 | Empty CrawlResult HTML | Processed without issues |
| EDGE-002 | Very large HTML content | Handles without memory issue |
| EDGE-003 | Unicode in title for duplicates | Handled correctly |
| EDGE-004 | Special characters in issue context | JSON encoded properly |
| EDGE-005 | Concurrent analyze calls | Thread-safe via lock |
| EDGE-006 | Checker returns multiple findings | All processed |
| EDGE-007 | Very long title (> 1000 chars) | Truncated in cache |
| EDGE-008 | Title with only whitespace | Treated as empty |
| EDGE-009 | Issue finding without context | Stored with empty context |
| EDGE-010 | Duplicate title with different case URLs | Still detected as duplicate |
| EDGE-011 | Zero audit ID | InvalidArgumentException |
| EDGE-012 | Negative audit ID | InvalidArgumentException |

#### SEC: Security Tests (9 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SEC-001 | XSS in issue description | Sanitized before storage |
| SEC-002 | SQL injection in context | Parameterized queries |
| SEC-003 | Path traversal in URL | Sanitized |
| SEC-004 | Script tags in title | Escaped in context |
| SEC-005 | Large context JSON | Truncated to max size |
| SEC-006 | Malicious checker registration | Validated interface |
| SEC-007 | Cache key injection | Key sanitized |
| SEC-008 | DoS via large checkers array | Limit enforced |
| SEC-009 | DoS via infinite checker loop | Timeout enforced |

#### PERF: Performance Tests (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| PERF-001 | Analyze 10 pages | < 5 seconds total |
| PERF-002 | Analyze with 20 checkers | < 10 seconds |
| PERF-003 | Memory usage | < 50MB for 100 pages |
| PERF-004 | Cache hit rate for duplicates | > 95% after first check |
| PERF-005 | Database query count | < 20 queries per analyze |

#### INT: Integration Tests (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| INT-001 | Full flow with real checkers | Issues created in DB |
| INT-002 | Integration with CrawlResult | DTO passed correctly |
| INT-003 | Integration with AuditIssue model | Records created |
| INT-004 | Integration with IssueType model | Types resolved |
| INT-005 | End-to-end audit workflow | Complete flow works |

---

### CheckerInterfaceTest.php (35+ tests)

#### IF: Interface Definition (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| IF-001 | Interface exists | `CheckerInterface` defined |
| IF-002 | Interface has check method | `check(CrawlResult): ?IssueFinding` |
| IF-003 | Interface has getIssueType method | `getIssueType(): string` |
| IF-004 | Interface has getName method | `getName(): string` |
| IF-005 | Interface has getPriority method | `getPriority(): int` |

#### IMPL: Implementation Tests (15 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| IMPL-001 | TitleTagChecker implements interface | Instanceof CheckerInterface |
| IMPL-002 | MetaDescriptionChecker implements interface | Instanceof CheckerInterface |
| IMPL-003 | H1Checker implements interface | Instanceof CheckerInterface |
| IMPL-004 | BrokenLinkChecker implements interface | Instanceof CheckerInterface |
| IMPL-005 | PerformanceChecker implements interface | Instanceof CheckerInterface |
| IMPL-006 | ViewportChecker implements interface | Instanceof CheckerInterface |
| IMPL-007 | ImageAltChecker implements interface | Instanceof CheckerInterface |
| IMPL-008 | Each checker returns correct issue type | Matches specification |
| IMPL-009 | Each checker has unique name | No duplicates |
| IMPL-010 | Priority ordering works | Higher priority runs first |
| IMPL-011 | Checker returns null for passing content | No issue |
| IMPL-012 | Checker returns IssueFinding for issues | Valid DTO |
| IMPL-013 | Checker handles malformed HTML | No exception |
| IMPL-014 | Checker handles empty HTML | Returns null |
| IMPL-015 | Checker handles unicode content | Works correctly |

#### MOCK: Mock Checker Tests (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| MOCK-001 | Mock checker can be created | Works with interface |
| MOCK-002 | Mock checker returns configurable finding | Returns mock data |
| MOCK-003 | Mock checker throws on command | Exception handling |
| MOCK-004 | Mock checker tracks call count | Called N times |
| MOCK-005 | Mock checker with priority | Ordered correctly |
| MOCK-006 | Mock checker with multiple findings | Returns array |
| MOCK-007 | Mock checker with null return | Skips issue creation |
| MOCK-008 | Mock checker with delayed response | Timeout handling |
| MOCK-009 | Mock checker with context data | Context passed through |
| MOCK-010 | Mock checker with recommendation | Recommendation included |

#### AC: Acceptance Criteria (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| AC-001 | All MVP checkers defined | 7 checkers exist |
| AC-002 | Each checker maps to issue type | Correct mapping |
| AC-003 | Checker can be disabled | Disabled flag works |
| AC-004 | Checker can be configured | Config passed to checker |
| AC-005 | Checker reports metrics | Metrics available |

---

### IssueFindingTest.php (45+ tests)

#### DTO: DTO Structure (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| DTO-001 | Create with all fields | All properties set |
| DTO-002 | Create with minimal fields | Required only |
| DTO-003 | Issue type is required | InvalidArgumentException if missing |
| DTO-004 | Severity is required | InvalidArgumentException if missing |
| DTO-005 | Description is required | InvalidArgumentException if missing |
| DTO-006 | Recommendation is required | InvalidArgumentException if missing |
| DTO-007 | Context defaults to empty array | `[]` if not provided |
| DTO-008 | Severity validation | Only critical/warning/notice |
| DTO-009 | Issue type validation | Non-empty string |
| DTO-010 | DTO is immutable | Readonly properties |

#### ACC: Accessor Tests (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| ACC-001 | getIssueType() returns string | Correct value |
| ACC-002 | getSeverity() returns string | Correct value |
| ACC-003 | getDescription() returns string | Correct value |
| ACC-004 | getRecommendation() returns string | Correct value |
| ACC-005 | getContext() returns array | Correct value |
| ACC-006 | getContextValue() with key | Returns value or null |
| ACC-007 | hasContext() returns boolean | True if key exists |
| ACC-008 | toArray() returns all data | Array representation |
| ACC-009 | jsonSerialize() returns array | JSON-compatible |
| ACC-010 | toString() returns description | String representation |

#### VAL: Validation Tests (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| VAL-001 | Invalid severity throws | InvalidArgumentException |
| VAL-002 | Empty issue type throws | InvalidArgumentException |
| VAL-003 | Empty description throws | InvalidArgumentException |
| VAL-004 | Empty recommendation throws | InvalidArgumentException |
| VAL-005 | Invalid context type throws | InvalidArgumentException |
| VAL-006 | Very long description truncated | 1000 char limit |
| VAL-007 | Very long recommendation truncated | 500 char limit |
| VAL-008 | Context with nested objects | Handled correctly |
| VAL-009 | Context with special characters | JSON encoded |
| VAL-010 | From array factory method | Creates valid DTO |

#### EDGE: Edge Cases (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| EDGE-001 | Context with null value | Handled gracefully |
| EDGE-002 | Context with boolean | Stored correctly |
| EDGE-003 | Context with numeric | Stored correctly |
| EDGE-004 | Unicode in description | Stored correctly |
| EDGE-005 | Emoji in recommendation | Stored correctly |
| EDGE-006 | HTML in description | Escaped, not rendered |
| EDGE-007 | Very large context | Truncated to 10KB |
| EDGE-008 | Circular reference in context | JSON error handled |
| EDGE-009 | Clone creates new instance | Deep copy |
| EDGE-010 | Equals comparison | Same values = equal |

#### SER: Serialization Tests (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| SER-001 | JSON encode/decode | Roundtrip works |
| SER-002 | toArray then fromArray | Roundtrip works |
| SER-003 | Serialization for cache | Cacheable format |
| SER-004 | Deserialization from cache | Restores correctly |
| SER-005 | Database storage format | Compatible with JSON column |

---

### IssueDetectorIntegrationTest.php (40+ tests)

#### FLOW: Full Workflow Tests (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| FLOW-001 | Complete analysis workflow | All steps complete |
| FLOW-002 | Integration with real CrawlResult | DTO processed |
| FLOW-003 | Integration with database | Records created |
| FLOW-004 | Integration with cache | Duplicates detected |
| FLOW-005 | Integration with logging | Events logged |
| FLOW-006 | Multiple pages in sequence | Sequential processing |
| FLOW-007 | Large audit (100 pages) | Completes within limits |
| FLOW-008 | Recovery from partial failure | Continues processing |
| FLOW-009 | Cleanup on error | No orphaned records |
| FLOW-010 | Transaction handling | Atomic operations |

#### CHECK: Checker Integration (10 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| CHECK-001 | TitleTagChecker integration | Missing title detected |
| CHECK-002 | MetaDescriptionChecker integration | Missing description detected |
| CHECK-003 | H1Checker integration | Missing H1 detected |
| CHECK-004 | BrokenLinkChecker integration | Broken links detected |
| CHECK-005 | PerformanceChecker integration | Slow pages detected |
| CHECK-006 | ViewportChecker integration | Missing viewport detected |
| CHECK-007 | ImageAltChecker integration | Missing alt detected |
| CHECK-008 | Duplicate title across pages | Duplicate detected |
| CHECK-009 | Multiple issues per page | All issues detected |
| CHECK-010 | No issues on perfect page | No issues created |

#### DATA: Data Flow Tests (8 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| DATA-001 | CrawlResult to checker | Correct data passed |
| DATA-002 | IssueFinding to AuditIssue | Correct mapping |
| DATA-003 | Context preservation | All context stored |
| DATA-004 | Timestamp recording | Correct timestamps |
| DATA-005 | User association | Correct user linked |
| DATA-006 | Site association | Correct site linked |
| DATA-007 | Issue type resolution | Correct type linked |
| DATA-008 | Severity persistence | Correct severity stored |

#### API: API Integration Tests (7 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| API-001 | Called from audit service | Integration point works |
| API-002 | Called from queue job | Queue integration works |
| API-003 | Called from controller | Controller integration works |
| API-004 | Results available via API | API returns results |
| API-005 | Pagination of issues | Large result sets handled |
| API-006 | Filtering by severity | Filter works |
| API-007 | Filtering by type | Filter works |

#### PERF: Performance Integration Tests (5 tests)
| ID | Scenario | Expected |
|----|----------|----------|
| PERF-001 | Memory usage under load | No memory leak |
| PERF-002 | Database connection pooling | Efficient connections |
| PERF-003 | Cache hit rate | High hit rate |
| PERF-004 | Query optimization | N+1 prevented |
| PERF-005 | Concurrent processing | Thread safety |

---

## Mock Data

### CrawlResult Mock
```php
$crawlResult = new CrawlResult(
    url: 'https://example.com/page',
    statusCode: 200,
    responseTimeMs: 150,
    html: '<html><head><title>Test Page</title></head><body><h1>Heading</h1></body></html>',
    links: ['https://example.com/link1', 'https://example.com/link2'],
    metadata: ['title' => 'Test Page', 'description' => 'A test page'],
    errorMessage: null,
    headers: ['content-type' => 'text/html'],
    depth: 0,
    redirectChain: []
);
```

### IssueFinding Mock
```php
$issueFinding = new IssueFinding(
    issueType: 'missing_title_tag',
    severity: 'critical',
    description: 'Page is missing a title tag',
    recommendation: 'Add a descriptive title tag to the page',
    context: ['url' => 'https://example.com/page']
);
```

### Checker Mock
```php
class MockChecker implements CheckerInterface
{
    public function __construct(
        private string $name,
        private string $issueType,
        private ?IssueFinding $result = null
    ) {}
    
    public function check(CrawlResult $result): ?IssueFinding
    {
        return $this->result;
    }
    
    public function getIssueType(): string
    {
        return $this->issueType;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getPriority(): int
    {
        return 0;
    }
}
```

---

## Coverage Targets

| Metric | Target |
|--------|--------|
| Line Coverage | 90%+ |
| Branch Coverage | 85%+ |
| Method Coverage | 95%+ |
| Class Coverage | 100% |

---

## Test Execution

```bash
# Run all tests for IssueDetector
php artisan test --filter=IssueDetector

# Run specific test file
php artisan test tests/Unit/Services/Audit/IssueDetectorTest.php

# Run with coverage
php artisan test --coverage --filter=IssueDetector

# Run integration tests only
php artisan test tests/Feature/Services/IssueDetectorIntegrationTest.php
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-14-2 (Audit Issues table) | Done ✅ | Database schema ready |
| TASK-14-3 (Issue Types table) | Done ✅ | Reference data ready |
| TASK-14-5 (SiteCrawler) | To Develop | CrawlResult DTO mocked |
| TASK-14-6 (RobotsParser) | To Develop | Not needed for this task |

---

## Notes for Developer

### Key Implementation Points

1. **Orchestrator Pattern**: IssueDetector should act as a central coordinator that:
   - Registers and manages checkers
   - Runs checkers in priority order
   - Creates AuditIssue records from findings
   - Handles duplicate detection across pages

2. **Checker Interface**: Each checker must:
   - Implement `CheckerInterface`
   - Return `IssueFinding` or `null`
   - Have a unique name
   - Report its issue type

3. **Duplicate Detection**: 
   - Use cache with key `audit:titles:{audit_id}`
   - Store title → URL mapping
   - Flag second and subsequent occurrences

4. **Severity Mapping**:
   - Look up severity from `issue_types` table
   - Allow checker to override
   - Default to 'notice' if not found

5. **Error Handling**:
   - Log checker exceptions but continue processing
   - Wrap in try-catch for each checker
   - Report errors in analysis summary

### DTO Classes to Create

```php
// IssueFinding DTO
class IssueFinding
{
    public function __construct(
        public readonly string $issueType,
        public readonly string $severity, // critical, warning, notice
        public readonly string $description,
        public readonly string $recommendation,
        public readonly array $context = []
    ) {}
}

// CrawlResult DTO (from TASK-14-5, mock for now)
class CrawlResult
{
    public function __construct(
        public readonly string $url,
        public readonly int $statusCode,
        public readonly int $responseTimeMs,
        public readonly string $html,
        public readonly array $links = [],
        public readonly array $metadata = [],
        public readonly ?string $errorMessage = null,
        public readonly array $headers = [],
        public readonly int $depth = 0,
        public readonly array $redirectChain = []
    ) {}
}
```

### MVP Issue Types

| Issue Type | Severity | Checker |
|------------|----------|---------|
| missing_title_tag | Critical | TitleTagChecker |
| duplicate_title | Warning | TitleTagChecker |
| missing_meta_description | Warning | MetaDescriptionChecker |
| missing_h1 | Warning | H1Checker |
| multiple_h1 | Notice | H1Checker |
| broken_link | Critical | BrokenLinkChecker |
| slow_page | Notice | PerformanceChecker |
| missing_viewport | Warning | ViewportChecker |
| image_missing_alt | Notice | ImageAltChecker |

---

## Comments
_Agent communication log_

### [2026-03-07 10:05] Agent: PreTester
**Test Design Analysis:**

| Metric | Value | Notes |
|--------|-------|-------|
| Priority | High | Foundation service for issue detection pipeline |
| Risk | Medium | Standard service implementation, no external APIs |
| Test Categories | 12 | Comprehensive coverage |
| Total Tests | 215+ | Exceeds minimum threshold |

**Key Design Decisions:**
- TDD approach with mocked checkers first
- CrawlResult mocked from TASK-14-5 (not blocking)
- IssueFinding DTO tested independently
- Duplicate detection uses cache (Redis/array driver)
- Severity mapped from issue_types table

**Status changed:** `To Write Tests` → `Tests Coverage In Progress`
