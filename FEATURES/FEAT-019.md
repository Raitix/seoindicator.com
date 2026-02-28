# FEAT-019: Progressive Analysis & Job System (Realtime Results)

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement the legacy progressive analysis system: create a job, process indicators concurrently (throttled), and update the UI in real time with progress bar, per‑row updates, and cancel support.

## User Story
As a user, I want results to appear progressively so I can start improving before the full analysis finishes.

## Acceptance Criteria
- [ ] Job created per analysis with unique ID.
- [ ] Indicator queue with concurrency limits (e.g., 5).
- [ ] Progress endpoint (0/17 → 17/17) and cancel endpoint.
- [ ] UI shows progress bar + live row updates.

## Notes
- Source: legacy progressive job system (user request).

---

## Comments

### [2026-02-28] Architect
**Specification Created:** SPEC-019.md

**Architecture Decision:**
- Uses Server-Sent Events (SSE) for real-time updates (simpler than WebSockets, no external service needed)
- Database-backed job queue with Laravel's native queue system
- 5 concurrent indicator processing limit to prevent resource exhaustion
- Polling fallback for environments without SSE support

**Key Components:**
1. `analysis_jobs` table - tracks job state, progress, timing
2. `analysis_job_results` table - per-indicator results with scores
3. `AnalysisJobService` - orchestrates job lifecycle
4. `IndicatorProcessor` - abstract base for indicator implementations
5. SSE stream endpoint for real-time UI updates

**Tasks Breakdown:** 16 tasks across 4 phases (see SPEC-019.md)

**Risk Reassessed:** Medium (remains) - SSE is standard technology, queue processing is well-understood. Memory/timing concerns mitigated with limits.

**Status:** Ready for SpecSystemAnalyst to break into tasks.

---
*Created by Lead from individual user request (legacy migration).*