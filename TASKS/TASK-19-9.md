# TASK-19-9: SSE Stream Endpoint

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Medium`

## Description
Implement Server-Sent Events endpoint for real-time job progress updates.

## Acceptance Criteria
- [ ] `GET /api/v1/analysis/jobs/{jobId}/stream` endpoint
- [ ] Response headers:
  - Content-Type: text/event-stream
  - Cache-Control: no-cache
  - X-Accel-Buffering: no (for nginx)
- [ ] Event types:
  - `progress`: { completedIndicators, progressPercent }
  - `result`: { indicatorSlug, status, score, percentage }
  - `complete`: { status, totalIndicators, completedIndicators }
  - `error`: { message }
- [ ] Connection management:
  - Heartbeat every 15 seconds to keep connection alive
  - Auto-disconnect on job completion
  - Client disconnect detection
- [ ] Authorization: user must own the job
- [ ] Reconnection support: Last-Event-ID header handling
- [ ] Feature tests for SSE behavior

## Dependencies
- TASK-19-3 (AnalysisJobService)
- TASK-19-8 (Progress tracking)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use PHP's native stream functions
- Consider using Laravel's response()->stream()
- Test with curl and browser EventSource API

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for SSE implementation. Medium risk due to connection management complexity. Alternative to WebSockets with simpler implementation.
