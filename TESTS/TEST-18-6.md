# TEST-18-6: AnalysisRunnerService

## Task Reference
- **Task:** TASK-18-6
- **Feature:** FEAT-018 (Legacy 17-Indicator SEO Analysis Core Engine)
- **Status:** To Develop

---

## Overview
Tests for the `AnalysisRunnerService` that orchestrates all 17 indicator checks, aggregates results, and manages the analysis lifecycle.

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Services/AnalysisRunnerServiceTest.php` | Unit | 45+ | 100+ |
| `backend/tests/Feature/Services/AnalysisRunnerServiceIntegrationTest.php` | Integration | 15+ | 40+ |

---

## Test Scenarios

### Analysis Execution Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-01 | Run all indicators | Valid URL + HTML | All 17 indicators executed |
| AR-02 | Run specific indicators | URL + HTML + indicator list | Only specified indicators run |
| AR-03 | Skip specific indicators | URL + HTML + exclude list | All except excluded run |
| AR-04 | Empty HTML | Valid URL + empty HTML | Indicators handle gracefully |
| AR-05 | Malformed HTML | Valid URL + broken HTML | Indicators handle gracefully |

### Parallel Execution Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PE-01 | Parallel execution | Multiple indicators | Indicators run concurrently |
| PE-02 | Sequential fallback | Parallel disabled | Indicators run sequentially |
| PE-03 | Total timeout | Long-running analysis | Timeout at 60 seconds |
| PE-04 | Individual timeout | Slow indicator | Indicator times out at 5s |

### Error Handling Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EH-01 | One indicator fails | One throws exception | Others continue, error logged |
| EH-02 | Multiple indicators fail | Several throw exceptions | Remaining continue, errors logged |
| EH-03 | All indicators fail | All throw exceptions | Analysis marked failed with errors |
| EH-04 | Critical indicator fails | Critical indicator error | Analysis may be marked failed |
| EH-05 | Timeout during indicator | Indicator exceeds timeout | Skip indicator, log error |
| EH-06 | Memory exhaustion | Large HTML processing | Handle gracefully, fail analysis |

### Result Aggregation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RA-01 | Aggregate all results | 17 indicator results | Unified AnalysisResult object |
| RA-02 | Aggregate partial results | 15 results + 2 errors | Partial results included |
| RA-03 | Include timing data | Indicator execution times | Timing per indicator in result |
| RA-04 | Include errors | Failed indicators | Error details in result |
| RA-05 | Calculate summary | All results | Pass/warning/fail counts |

### Status Management Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SM-01 | Initial status | Start analysis | Status: pending |
| SM-02 | Running status | During execution | Status: running |
| SM-03 | Completed status | All indicators done | Status: completed |
| SM-04 | Failed status | Critical failure | Status: failed |
| SM-05 | Partial status | Some indicators failed | Status: completed with warnings |

### Model Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-01 | Create analysis model | Valid analysis | SEOAnalysis created with ID |
| MI-02 | Update analysis on completion | Results ready | Model updated with results |
| MI-03 | Store analysis_data JSON | Results | JSON properly stored |
| MI-04 | Link to user | Authenticated user | user_id set correctly |
| MI-05 | Link to session | Anonymous user | session_id set correctly |

### Checker Registration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-01 | Auto-discover checkers | Service container | All IndicatorCheckerInterface found |
| CR-02 | Manual registration | Explicit checker list | Only registered checkers used |
| CR-03 | Checker priority | Multiple checkers | Sorted by priority/weight |
| CR-04 | Duplicate checker ID | Same ID twice | One instance used |

### Event Dispatch Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ED-01 | Analysis started | Run begins | AnalysisStarted event dispatched |
| ED-02 | Indicator completed | Checker finishes | IndicatorCompleted event dispatched |
| ED-03 | Analysis completed | All done | AnalysisCompleted event dispatched |
| ED-04 | Analysis failed | Error occurs | AnalysisFailed event dispatched |

### Input Validation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IV-01 | Missing URL | null URL | ValidationException |
| IV-02 | Missing HTML | null HTML | ValidationException |
| IV-03 | Invalid URL | Malformed URL | ValidationException |
| IV-04 | URL not validated | Bypass validation | Rejected or validated internally |

### Caching Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-01 | Cache analysis results | Completed analysis | Results cached |
| CA-02 | Retrieve cached analysis | Same URL | Return cached results |
| CA-03 | Cache invalidation | New analysis requested | Old cache cleared |

### Configuration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CF-01 | Custom timeout | config timeout: 30 | 30 second timeout |
| CF-02 | Custom indicators | config enabled_indicators | Only enabled indicators run |
| CF-03 | Disabled indicators | config disabled_indicators | Disabled indicators skipped |
| CF-04 | Custom parallel limit | config max_parallel: 5 | Max 5 concurrent indicators |

---

## Mock Data

### Valid Indicator Result
```php
$indicatorResult = new IndicatorResult(
    id: 'url',
    name: 'URL Structure',
    category: 'technical',
    score: 3,
    weight: 0.08,
    passed: true,
    details: [
        'urlLength' => 35,
        'hasKeywords' => true,
        'isHttps' => true,
    ],
    suggestions: []
);
```

### Failed Indicator Result
```php
$failedResult = new IndicatorResult(
    id: 'title_tag',
    name: 'Title Tag',
    category: 'content',
    score: 1,
    weight: 0.10,
    passed: false,
    details: [
        'error' => 'Title tag missing',
    ],
    suggestions: [
        ['type' => 'error', 'message' => 'Add a title tag to your page.']
    ]
);
```

### Mock Checker
```php
class MockIndicatorChecker implements IndicatorCheckerInterface
{
    public function __construct(
        private string $id,
        private bool $shouldFail = false
    ) {}

    public function id(): string { return $this->id; }
    public function name(): string { return "Mock {$this->id}"; }
    public function category(): string { return 'test'; }
    public function weight(): float { return 0.05; }

    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult
    {
        if ($this->shouldFail) {
            throw new IndicatorException("Checker {$this->id} failed");
        }

        return new IndicatorResult(
            id: $this->id,
            name: $this->name(),
            category: $this->category(),
            score: 3,
            weight: $this->weight(),
            passed: true,
            details: [],
            suggestions: []
        );
    }

    public function getSuggestions(IndicatorResult $result): array
    {
        return [];
    }
}
```

---

## Service Interface

```php
interface AnalysisRunnerServiceInterface
{
    /**
     * Run SEO analysis on a URL.
     *
     * @param string $url Validated URL
     * @param string $html HTML content
     * @param array $options Optional settings
     *   - indicators: array (specific indicators to run)
     *   - excludeIndicators: array (indicators to skip)
     *   - parallel: bool (run in parallel, default true)
     *   - timeout: int (total timeout in seconds, default 60)
     * @return AnalysisResult
     * @throws AnalysisException On critical failure
     */
    public function run(string $url, string $html, array $options = []): AnalysisResult;

    /**
     * Get list of available indicators.
     *
     * @return IndicatorInfo[]
     */
    public function getAvailableIndicators(): array;

    /**
     * Run a single indicator.
     *
     * @param string $indicatorId
     * @param string $url
     * @param string $html
     * @return IndicatorResult
     */
    public function runIndicator(string $indicatorId, string $url, string $html): IndicatorResult;
}
```

---

## Result DTO

```php
class AnalysisResult
{
    public string $id;
    public string $url;
    public int $overallScore;
    public string $status; // pending, running, completed, failed
    public array $indicators; // IndicatorResult[]
    public array $summary; // passed, warnings, failed counts
    public array $categoryScores; // content, technical, accessibility, performance
    public array $timing; // total and per-indicator
    public array $errors; // Any errors encountered
    public Carbon $analyzedAt;
}
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Execution | 100% | All execution paths |
| Error Handling | 100% | All error scenarios |
| Result Aggregation | 100% | All aggregation cases |
| Status Management | 100% | All status transitions |
| Model Integration | 90% | Database interactions |
| Event Dispatch | 80% | Key events |
| Input Validation | 100% | All validation cases |

---

## Test Execution

```bash
# Run unit tests
php artisan test --filter=AnalysisRunnerServiceTest

# Run integration tests
php artisan test --filter=AnalysisRunnerServiceIntegrationTest

# Run with coverage
php artisan test --filter=AnalysisRunnerService --coverage

# Run all FEAT-018 tests
php artisan test --filter=TEST-18
```

---

## Dependencies
- `SEOAnalysis` model (TASK-18-2)
- `IndicatorCheckerInterface` (TASK-18-3)
- `HtmlFetcherService` (TASK-18-5)
- `ScoreCalculatorService` (TASK-18-7)
- Laravel Event dispatcher
- Laravel Service Container

---

## Notes for Developer
- Use service container to auto-discover indicator checkers
- Consider using Laravel's `dispatch` for parallel execution
- Total analysis timeout: 60 seconds
- Individual indicator timeout: 5 seconds
- Log all indicator results and errors
- Store partial results even if some indicators fail
- Use database transactions for model updates

---

## Comments

### [2026-02-28] PreTester
- Test documentation created
- 50+ test scenarios covering execution, parallel processing, error handling, aggregation
- Integration tests require mock indicator checkers
- Ready for test implementation
