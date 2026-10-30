# TEST-19-3: AnalysisJobService - Core Operations

## Overview
Test suite for AnalysisJobService with methods for job lifecycle management.

---

## Test File
`tests/Unit/Services/AnalysisJobServiceTest.php`

---

## Test Scenarios

### 1. createJob Method Tests

#### 1.1 Basic Creation
- [ ] Creates job with valid user, site, and indicators
- [ ] Sets status to 'pending'
- [ ] Sets total_indicators to count of indicators array
- [ ] Sets completed_indicators to 0
- [ ] Sets failed_indicators to 0
- [ ] Sets progress_percent to 0
- [ ] Returns AnalysisJob model instance

#### 1.2 Result Creation
- [ ] Creates pending results for each indicator
- [ ] Sets indicator_slug correctly
- [ ] Sets indicator_name from slug (formatted)
- [ ] Sets result status to 'pending'

#### 1.3 Validation
- [ ] Throws exception if indicators array empty
- [ ] Throws exception if user doesn't exist
- [ ] Throws exception if site doesn't exist
- [ ] Throws exception if user doesn't own site

#### 1.4 Transaction Safety
- [ ] Rolls back if result creation fails
- [ ] Atomic operation (all or nothing)

### 2. getJob Method Tests

#### 2.1 Retrieval
- [ ] Returns job with results loaded
- [ ] Returns null if job doesn't exist
- [ ] Returns null if job ID invalid

#### 2.2 Authorization
- [ ] Throws exception if user doesn't own job
- [ ] Returns job if user owns job

### 3. getJobProgress Method Tests

#### 3.1 Progress Data
- [ ] Returns array with status
- [ ] Returns array with totalIndicators
- [ ] Returns array with completedIndicators
- [ ] Returns array with failedIndicators
- [ ] Returns array with progressPercent
- [ ] Returns array with results array
- [ ] Returns array with startedAt
- [ ] Returns array with completedAt

#### 3.2 Edge Cases
- [ ] Returns empty results array if no results
- [ ] Returns null startedAt if not started
- [ ] Returns null completedAt if not completed

### 4. cancelJob Method Tests

#### 4.1 Valid Cancellation
- [ ] Sets status to 'cancelled'
- [ ] Sets cancelled_at timestamp
- [ ] Returns true on success
- [ ] Dispatches job cancelled event

#### 4.2 State Validation
- [ ] Cancels pending job
- [ ] Cancels running job
- [ ] Fails to cancel completed job (returns false)
- [ ] Fails to cancel already cancelled job
- [ ] Fails to cancel failed job

#### 4.3 Result Handling
- [ ] Marks pending results as 'skipped'
- [ ] Leaves completed results unchanged
- [ ] Leaves failed results unchanged

### 5. startJob Method Tests

#### 5.1 Valid Start
- [ ] Sets status to 'running'
- [ ] Sets started_at timestamp
- [ ] Returns true on success
- [ ] Dispatches job started event

#### 5.2 State Validation
- [ ] Starts pending job
- [ ] Fails to start running job
- [ ] Fails to start completed job
- [ ] Fails to start cancelled job
- [ ] Fails to start failed job

### 6. completeJob Method Tests

#### 6.1 Valid Completion
- [ ] Sets status to 'completed'
- [ ] Sets completed_at timestamp
- [ ] Returns true on success
- [ ] Dispatches job completed event

#### 6.2 State Validation
- [ ] Completes running job
- [ ] Fails to complete pending job
- [ ] Fails to complete already completed job
- [ ] Fails to complete cancelled job

#### 6.3 Progress Update
- [ ] Updates progress_percent to 100
- [ ] Updates completed_indicators count

### 7. failJob Method Tests

#### 7.1 Valid Failure
- [ ] Sets status to 'failed'
- [ ] Sets error_message
- [ ] Sets completed_at timestamp
- [ ] Returns true on success
- [ ] Dispatches job failed event

#### 7.2 State Validation
- [ ] Fails running job
- [ ] Fails pending job
- [ ] Fails to fail already completed job

### 8. Authorization Tests

#### 8.1 User Ownership
- [ ] User can access their own job
- [ ] User cannot access another user's job
- [ ] Admin can access any job (if implemented)

#### 8.2 Site Ownership
- [ ] Can create job for owned site
- [ ] Cannot create job for another user's site
- [ ] Validates site ownership before job creation

### 9. Concurrency Tests

#### 9.1 Race Conditions
- [ ] Concurrent cancel/start handled correctly
- [ ] Double cancel returns false
- [ ] Double start returns false

#### 9.2 Locking
- [ ] Job locked during status transition
- [ ] Concurrent operations serialized

### 10. Edge Cases

#### 10.1 Empty Indicators
- [ ] Handles empty indicators array (validation error)
- [ ] Handles null indicators array

#### 10.2 Large Indicator Lists
- [ ] Handles 17 indicators (full SEO analysis)
- [ ] Handles 50+ indicators (stress test)

#### 10.3 Invalid States
- [ ] Handles missing results gracefully
- [ ] Handles orphaned results (no job)

### 11. Security Tests

#### 11.1 Authorization Bypass Attempts
- [ ] Cannot access job by guessing ID
- [ ] Cannot create job for another user's site
- [ ] Cannot cancel another user's job

#### 11.2 Input Validation
- [ ] SQL injection in indicators array handled
- [ ] XSS in error message handled
- [ ] Invalid indicator slugs rejected

---

## Test Implementation Notes

- Use mock events for event dispatching tests
- Use database transactions for atomic tests
- Create helper methods for common job setup
- Use Laravel's actingAs() for authentication

---

## Dependencies
- TASK-19-1 (Database migration)
- TASK-19-2 (Models)

---

## Coverage Target
- 80+ test methods
- 90%+ code coverage on service

---

*Created by PreTester for TASK-19-3*
