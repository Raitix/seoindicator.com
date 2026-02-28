# TASK-19-7: Queue Worker with Concurrency Throttling

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Description
Implement queue-based processing with concurrency limits and throttling for indicator checks.

## Acceptance Criteria
- [ ] Queue configuration for 'analysis' queue:
  - Driver: database
  - Retry after: 90 seconds
  - After commit: true
- [ ] Job dispatching with configurable concurrency (max 5 concurrent)
- [ ] Per-indicator timeout (30 seconds default)
- [ ] Memory limit per worker (256MB)
- [ ] Retry logic: single retry on failure with exponential backoff
- [ ] Progress tracking: updates job progress_percent after each indicator
- [ ] Graceful shutdown: handles SIGTERM, completes current indicator
- [ ] Failed job handling: marks indicator as failed, continues with others
- [ ] Queue monitoring: logs queue depth and processing time
- [ ] Unit tests for queue behavior

## Dependencies
- TASK-19-3 (AnalysisJobService)
- TASK-19-5 (IndicatorProcessor base class)
- TASK-19-6 (Core indicators)

## Effort Estimate
`Large` (6-8 hours)

## Technical Notes
- Use Laravel Queue system
- Consider using Laravel Horizon for monitoring (if available)
- Implement batch processing with `Bus::batch()`

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for queue processing. HIGH RISK due to concurrency complexity, memory management, and error handling. Critical for system stability.
