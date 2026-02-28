# TASK-18-6: AnalysisRunnerService

## STATUS
`In Development`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create orchestration service that runs all 17 indicator checks, aggregates results, and manages the analysis lifecycle.

## Acceptance Criteria
- [ ] Accepts validated URL and HTML content
- [ ] Runs all registered indicator checkers
- [ ] Parallel execution where possible (use async or queue)
- [ ] Aggregates results into unified analysis structure
- [ ] Handles individual checker failures gracefully (continue with others)
- [ ] Records timing per indicator
- [ ] Creates SEOAnalysis model instance
- [ ] Manages status transitions (pending → running → completed/failed)
- [ ] Logs errors for failed indicators
- [ ] Unit tests with mock checkers
- [ ] Integration tests with real checkers

## Dependencies
- TASK-18-2 (SEOAnalysis model)
- TASK-18-3 (IndicatorCheckerInterface)
- TASK-18-5 (HtmlFetcherService)
- TASK-18-7 (ScoreCalculatorService)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use service container to resolve all IndicatorCheckerInterface implementations
- Consider using Laravel's dispatch_sync for parallel execution
- Total timeout: 60 seconds for entire analysis
- Store partial results if some indicators fail

---

## Comments
### [2026-02-28] SpecSystemAnalyst
Created from SPEC-018 Section 10.2. Central orchestration for analysis pipeline.

### [2026-02-28] PreTester
**Test Documentation Created:** TESTS/TEST-18-6.md (50+ test scenarios)
**Test File Created:** backend/tests/Unit/Services/AnalysisRunnerServiceTest.php (35+ test methods)

**Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| Analysis Execution (AR) | 5 | All indicators, specific indicators, exclusions |
| Error Handling (EH) | 3 | Indicator failures, graceful degradation |
| Result Aggregation (RA) | 3 | Timing, summary, structure |
| Status Management (SM) | 2 | Completed/failed status |
| Input Validation (IV) | 3 | URL/HTML validation |
| Checker Registration (CR) | 2 | Auto-discovery, manual registration |
| Single Indicator | 2 | Individual checker execution |
| Configuration (CF) | 2 | Timeout, indicator filters |
| Edge Cases | 5 | Large HTML, unicode, malformed, scripts, styles |

**Key Integration Points:**
- Requires SEOAnalysis model (TASK-18-2)
- Requires IndicatorCheckerInterface (TASK-18-3)
- Requires ScoreCalculatorService (TASK-18-7)

**Status:** Tests written, ready for Developer implementation.

### [2026-02-28] PreTester
**Integration Tests Created:** backend/tests/Feature/Services/AnalysisRunnerServiceIntegrationTest.php (25+ integration tests)

**Integration Test Coverage:**
| Category | Tests | Description |
|----------|-------|-------------|
| End-to-End | 3 | Full workflow, user linking, session linking |
| Real Indicators | 3 | Title, meta description, H1 integration |
| Events | 2 | Started/Completed events |
| Performance | 2 | Timeout compliance, large HTML |
| Partial Failures | 2 | Partial results, error capture |
| Score Calculation | 2 | Overall score, category scores |
| Data Persistence | 2 | JSON storage, timing data |
| Concurrent Analysis | 2 | Multiple analyses, URL updates |
| Input Variations | 3 | HTTPS, unicode, HTML5 semantic |

**Test File Summary:**
- Unit tests: `tests/Unit/Services/AnalysisRunnerServiceTest.php` (35+ tests)
- Integration tests: `tests/Feature/Services/AnalysisRunnerServiceIntegrationTest.php` (25+ tests)
- Test documentation: `TESTS/TEST-18-6.md` (comprehensive scenario list)

**Status Change:** Tests Coverage In Progress → To Develop
