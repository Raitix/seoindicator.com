# TASK-27-6: Request Tracking Middleware

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Parent Specification
SPEC-027: Admin Dashboard (Ops + Usage)

## Description
Create middleware to track API requests for usage analytics. Records request counts by endpoint, method, and timestamp for aggregation.

## Acceptance Criteria
- [ ] Middleware class created at `app/Http/Middleware/RequestTrackingMiddleware.php`
- [ ] Records: endpoint path, HTTP method, timestamp, user_id (if authenticated)
- [ ] Uses lightweight storage (Redis or database queue)
- [ ] Does not significantly impact request latency (< 5ms overhead)
- [ ] Configurable sample rate for high-traffic endpoints
- [ ] Handles queue failures gracefully
- [ ] Unit tests verify tracking behavior
- [ ] Performance tests confirm minimal overhead

## Dependencies
- None (standalone infrastructure)

## Effort Estimate
`Medium` (2-4 hours)

## Technical Notes
- Consider using async queue for write operations
- Use Redis INCR for atomic counters
- Aggregate to database periodically (scheduled job)
- Exclude health check endpoints from tracking

## Data Storage
Option 1: Redis with TTL-based keys
Option 2: Database table with hourly aggregation

## Comments

---
*Created by SpecSystemAnalyst from SPEC-027*
