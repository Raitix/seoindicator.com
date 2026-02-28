# TASK-18-6: AnalysisRunnerService

## STATUS
`To Write Tests`

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
