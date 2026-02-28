# TASK-19-8: Progress Tracking and Updates

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Description
Implement progress tracking service that updates job state and broadcasts progress events.

## Acceptance Criteria
- [ ] ProgressService with methods:
  - `updateProgress(int $jobId, string $indicatorSlug, float $score): void`
  - `markComplete(int $jobId, string $indicatorSlug, IndicatorResult $result): void`
  - `markFailed(int $jobId, string $indicatorSlug, string $error): void`
- [ ] Automatic progress_percent calculation after each indicator
- [ ] Database updates are atomic (transaction)
- [ ] Event dispatching for real-time updates:
  - `AnalysisProgressUpdated` event with: jobId, completedIndicators, progressPercent
  - `AnalysisIndicatorComplete` event with: jobId, indicatorSlug, score, percentage
- [ ] Events are broadcastable for SSE integration
- [ ] Progress history stored for debugging (optional)
- [ ] Unit tests for progress calculation

## Dependencies
- TASK-19-2 (Models)
- TASK-19-3 (AnalysisJobService)

## Effort Estimate
`Medium` (3-4 hours)

## Technical Notes
- Use Laravel Events for broadcasting
- Consider using Redis for real-time state caching
- Keep progress updates lightweight (avoid N+1 queries)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for progress tracking. Low risk - standard event-driven architecture. Required for real-time UI updates.
