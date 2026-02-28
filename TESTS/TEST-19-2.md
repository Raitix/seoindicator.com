# TEST-19-2: AnalysisJob and AnalysisJobResult Models

## Overview
Test suite for Eloquent models AnalysisJob and AnalysisJobResult with relationships, accessors, scopes, and casts.

---

## Test Files
- `tests/Unit/Models/AnalysisJobTest.php`
- `tests/Unit/Models/AnalysisJobResultTest.php`

---

## Test Scenarios

### 1. AnalysisJob Model - Basic Tests

#### 1.1 Model Creation
- [ ] Can create AnalysisJob with all fillable attributes
- [ ] Can create with minimal attributes (defaults applied)
- [ ] Mass assignment protection works correctly
- [ ] Auto-increment ID works correctly

#### 1.2 Fillable Attributes
- [ ] user_id is fillable
- [ ] site_id is fillable
- [ ] status is fillable
- [ ] total_indicators is fillable
- [ ] completed_indicators is fillable
- [ ] failed_indicators is fillable
- [ ] progress_percent is fillable
- [ ] started_at is fillable
- [ ] completed_at is fillable
- [ ] cancelled_at is fillable
- [ ] error_message is fillable

#### 1.3 Status Constants
- [ ] STATUS_PENDING constant exists
- [ ] STATUS_RUNNING constant exists
- [ ] STATUS_COMPLETED constant exists
- [ ] STATUS_CANCELLED constant exists
- [ ] STATUS_FAILED constant exists

### 2. AnalysisJob Relationships

#### 2.1 BelongsTo User
- [ ] user() relationship exists
- [ ] Returns User model
- [ ] Foreign key is correct

#### 2.2 BelongsTo Site
- [ ] site() relationship exists
- [ ] Returns Site model
- [ ] Foreign key is correct

#### 2.3 HasMany Results
- [ ] results() relationship exists
- [ ] Returns Collection of AnalysisJobResult
- [ ] Foreign key is correct
- [ ] Dependent delete works (cascade)

### 3. AnalysisJob Scopes

#### 3.1 Status Scopes
- [ ] scopePending() returns only pending jobs
- [ ] scopeRunning() returns only running jobs
- [ ] scopeCompleted() returns only completed jobs
- [ ] scopeCancelled() returns only cancelled jobs
- [ ] scopeFailed() returns only failed jobs

#### 3.2 Custom Scopes
- [ ] scopeByUser($userId) filters by user
- [ ] scopeBySite($siteId) filters by site
- [ ] scopeRecent($days) filters by created_at

### 4. AnalysisJob Accessors

#### 4.1 Progress Accessor
- [ ] getProgressPercentAttribute() calculates correctly
- [ ] Returns 0 when total_indicators is 0
- [ ] Returns integer percentage
- [ ] Handles partial completion

#### 4.2 Duration Accessor
- [ ] getDurationAttribute() calculates elapsed time
- [ ] Returns null when not started
- [ ] Returns null when still running
- [ ] Returns correct duration when completed
- [ ] Returns human-readable format

#### 4.3 Status Helpers
- [ ] isPending() returns correct boolean
- [ ] isRunning() returns correct boolean
- [ ] isCompleted() returns correct boolean
- [ ] isCancelled() returns correct boolean
- [ ] isFailed() returns correct boolean

### 5. AnalysisJob Methods

#### 5.1 Status Transitions
- [ ] markAsRunning() sets status and started_at
- [ ] markAsCompleted() sets status and completed_at
- [ ] markAsCancelled() sets status and cancelled_at
- [ ] markAsFailed() sets status and error_message

### 6. AnalysisJobResult Model - Basic Tests

#### 6.1 Model Creation
- [ ] Can create with all fillable attributes
- [ ] Can create with minimal attributes
- [ ] Mass assignment protection works

#### 6.2 Fillable Attributes
- [ ] job_id is fillable
- [ ] indicator_slug is fillable
- [ ] indicator_name is fillable
- [ ] status is fillable
- [ ] score is fillable
- [ ] max_score is fillable
- [ ] percentage is fillable
- [ ] data is fillable
- [ ] recommendations is fillable
- [ ] error_message is fillable
- [ ] started_at is fillable
- [ ] completed_at is fillable
- [ ] processing_time_ms is fillable

### 7. AnalysisJobResult Relationships

#### 7.1 BelongsTo AnalysisJob
- [ ] job() relationship exists
- [ ] Returns AnalysisJob model
- [ ] Foreign key is correct

### 8. AnalysisJobResult Scopes

#### 8.1 Status Scopes
- [ ] scopePending() returns only pending results
- [ ] scopeRunning() returns only running results
- [ ] scopeCompleted() returns only completed results
- [ ] scopeFailed() returns only failed results
- [ ] scopeSkipped() returns only skipped results

#### 8.2 Custom Scopes
- [ ] scopeByJob($jobId) filters by job
- [ ] scopeByIndicator($slug) filters by indicator

### 9. AnalysisJobResult Accessors

#### 9.1 JSON Casts
- [ ] data attribute cast to array
- [ ] data attribute returns empty array when null
- [ ] recommendations attribute cast to array
- [ ] recommendations attribute returns empty array when null

#### 9.2 Calculated Accessors
- [ ] getPercentageAttribute() calculates from score/max_score
- [ ] Returns null when no score
- [ ] Returns integer 0-100

### 10. AnalysisJobResult Methods

#### 10.1 Status Helpers
- [ ] isPending() returns correct boolean
- [ ] isRunning() returns correct boolean
- [ ] isCompleted() returns correct boolean
- [ ] isFailed() returns correct boolean
- [ ] isSkipped() returns correct boolean

#### 10.2 Status Transitions
- [ ] markAsRunning() sets status and started_at
- [ ] markAsCompleted() sets status, completed_at, score
- [ ] markAsFailed() sets status and error_message

### 11. Edge Cases

#### 11.1 AnalysisJob Edge Cases
- [ ] Zero indicators handled correctly
- [ ] Negative values rejected
- [ ] Progress over 100% clamped
- [ ] Multiple status transitions handled

#### 11.2 AnalysisJobResult Edge Cases
- [ ] Large JSON data stored correctly
- [ ] Empty recommendations array
- [ ] Score higher than max_score handled
- [ ] Very long processing times logged

### 12. Security Tests

#### 12.1 SQL Injection Prevention
- [ ] Indicator slug with SQL characters safe
- [ ] Error message with SQL characters safe
- [ ] Indicator name with SQL characters safe

#### 12.2 XSS Prevention
- [ ] Indicator name with script tags stored safely
- [ ] Error message with script tags stored safely
- [ ] Recommendations with HTML stored safely

---

## Test Implementation Notes

- Use `RefreshDatabase` trait for isolation
- Create model factories for both models
- Use `$model->refresh()` to reload from database
- Test both unit and integration scenarios

---

## Dependencies
- TASK-19-1 (Database migration)
- User model must exist
- Site model must exist

---

## Coverage Target
- 70+ test methods
- 80%+ code coverage on both models

---

*Created by PreTester for TASK-19-2*
