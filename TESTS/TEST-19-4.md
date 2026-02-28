# TEST-19-4: Job CRUD API Endpoints

## Overview
Test suite for REST API endpoints for job management: create, list, get, cancel.

---

## Test File
`tests/Feature/Api/AnalysisJobApiTest.php`

---

## Test Scenarios

### 1. Create Job Endpoint - POST /api/v1/sites/{siteId}/analysis/jobs

#### 1.1 Successful Creation
- [ ] Returns 201 status code
- [ ] Returns job data with id, status, totalIndicators
- [ ] Creates job in database
- [ ] Creates pending results for each indicator

#### 1.2 Request Validation
- [ ] Requires indicators array
- [ ] Requires at least 1 indicator
- [ ] Returns 422 for empty indicators array
- [ ] Returns 422 for missing indicators field
- [ ] Validates indicator slugs (optional whitelist)

#### 1.3 Authentication
- [ ] Returns 401 for unauthenticated requests
- [ ] Returns 201 for authenticated requests

#### 1.4 Authorization
- [ ] Returns 403 for site not owned by user
- [ ] Returns 404 for nonexistent site
- [ ] Creates job for owned site

#### 1.5 Response Format
- [ ] Returns JSON with id, status, totalIndicators, progressPercent, createdAt
- [ ] Includes Location header with job URL

### 2. List Jobs Endpoint - GET /api/v1/sites/{siteId}/analysis/jobs

#### 2.1 Successful List
- [ ] Returns 200 status code
- [ ] Returns paginated list of jobs
- [ ] Each job includes id, status, progressPercent, createdAt

#### 2.2 Filtering
- [ ] Filters by status query parameter
- [ ] Returns empty array if no jobs match filter
- [ ] Case-insensitive status filter

#### 2.3 Pagination
- [ ] Supports page query parameter
- [ ] Supports perPage query parameter (default 15, max 100)
- [ ] Returns pagination metadata (total, currentPage, lastPage)

#### 2.4 Authentication & Authorization
- [ ] Returns 401 for unauthenticated requests
- [ ] Returns 403 for site not owned by user
- [ ] Returns only jobs owned by user

### 3. Get Job Progress Endpoint - GET /api/v1/analysis/jobs/{jobId}

#### 3.1 Successful Retrieval
- [ ] Returns 200 status code
- [ ] Returns complete job data with results array
- [ ] Each result includes indicatorSlug, status, score, percentage

#### 3.2 Authentication & Authorization
- [ ] Returns 401 for unauthenticated requests
- [ ] Returns 403 for job not owned by user
- [ ] Returns 404 for nonexistent job

#### 3.3 Response Format
- [ ] Includes all job fields
- [ ] Includes results array with all indicator results
- [ ] Includes startedAt, completedAt, cancelledAt timestamps

### 4. Cancel Job Endpoint - DELETE /api/v1/analysis/jobs/{jobId}

#### 4.1 Successful Cancellation
- [ ] Returns 200 status code
- [ ] Returns updated job with status 'cancelled'
- [ ] Updates job in database
- [ ] Sets cancelled_at timestamp

#### 4.2 State Validation
- [ ] Cancels pending job (returns 200)
- [ ] Cancels running job (returns 200)
- [ ] Returns 400 for completed job
- [ ] Returns 400 for already cancelled job
- [ ] Returns 400 for failed job

#### 4.3 Authentication & Authorization
- [ ] Returns 401 for unauthenticated requests
- [ ] Returns 403 for job not owned by user
- [ ] Returns 404 for nonexistent job

### 5. Error Response Format

#### 5.1 Validation Errors
- [ ] Returns 422 status code
- [ ] Returns JSON with errors object
- [ ] Each error has field and message

#### 5.2 Authorization Errors
- [ ] Returns 403 status code
- [ ] Returns JSON with error message

#### 5.3 Not Found Errors
- [ ] Returns 404 status code
- [ ] Returns JSON with error message

### 6. Rate Limiting

#### 6.1 Job Creation Limits
- [ ] 10 jobs per user per hour
- [ ] Returns 429 when limit exceeded
- [ ] Includes Retry-After header

#### 6.2 API Limits
- [ ] Standard API rate limit applies
- [ ] Returns X-RateLimit headers

### 7. Edge Cases

#### 7.1 Large Indicator Lists
- [ ] Handles 17 indicators (full analysis)
- [ ] Response time under 2 seconds

#### 7.2 Concurrent Requests
- [ ] Handles concurrent creation requests
- [ ] Handles concurrent cancellation requests

#### 7.3 Invalid IDs
- [ ] Returns 404 for non-numeric job ID
- [ ] Returns 404 for non-numeric site ID
- [ ] Returns 404 for very large ID

### 8. Security Tests

#### 8.1 SQL Injection
- [ ] SQL injection in indicator slugs handled
- [ ] SQL injection in query parameters handled

#### 8.2 XSS
- [ ] XSS in error messages handled
- [ ] XSS in indicator names handled

#### 8.3 IDOR
- [ ] Cannot access another user's job
- [ ] Cannot list another user's jobs
- [ ] Cannot cancel another user's job

---

## Test Implementation Notes

- Use Laravel's `actingAs()` for authentication
- Use `assertDatabaseHas()` for database assertions
- Mock AnalysisJobService for unit tests
- Use real service for feature tests

---

## Dependencies
- TASK-19-3 (AnalysisJobService)
- Authentication middleware
- Rate limiting middleware (SPEC-006)

---

## Coverage Target
- 60+ test methods
- 90%+ code coverage on controller

---

*Created by PreTester for TASK-19-4*
