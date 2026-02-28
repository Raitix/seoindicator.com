# TASK-19-3: AnalysisJobService - Core Operations

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create AnalysisJobService with methods for job lifecycle: create, get, cancel, and status management.

## Acceptance Criteria
- [ ] `createJob(int $userId, int $siteId, array $indicators): AnalysisJob` - creates job with pending results for each indicator
- [ ] `getJob(int $jobId): ?AnalysisJob` - retrieves job with results loaded
- [ ] `getJobProgress(int $jobId): array` - returns progress data for API responses
- [ ] `cancelJob(int $jobId): bool` - cancels running job, marks as cancelled
- [ ] `startJob(int $jobId): bool` - transitions pending job to running, sets started_at
- [ ] `completeJob(int $jobId): bool` - marks job as completed, sets completed_at
- [ ] `failJob(int $jobId, string $error): bool` - marks job as failed with error message
- [ ] Authorization check: user can only access their own jobs
- [ ] Site ownership verification before job creation
- [ ] Unit tests for all service methods

## Dependencies
- TASK-19-1 (Database migration)
- TASK-19-2 (Models)

## Effort Estimate
`Medium` (4-6 hours)

## Technical Notes
- Use dependency injection for models
- Consider using a repository pattern for testability
- Add transaction handling for job creation (atomic)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for core service layer. Medium risk due to business logic complexity. Must verify authorization at service level.
