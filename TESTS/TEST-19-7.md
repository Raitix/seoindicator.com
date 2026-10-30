# TEST-19-7: Queue Worker with Concurrency Throttling

## Overview
Test suite for queue-based processing with concurrency limits and throttling.

---

## Test File
`tests/Unit/Services/AnalysisQueueWorkerTest.php`

---

## Test Scenarios

### 1. Queue Configuration Tests

#### 1.1 Queue Setup
- [ ] Queue uses database driver
- [ ] Queue named 'analysis'
- [ ] Retry after 90 seconds
- [ ] After commit enabled

#### 1.2 Queue Operations
- [ ] Jobs can be pushed to queue
- [ ] Jobs can be popped from queue
- [ ] Failed jobs tracked

### 2. Concurrency Tests

#### 2.1 Max Concurrent Indicators
- [ ] Processes up to 5 indicators simultaneously
- [ ] Queues additional indicators
- [ ] Waits for slot before processing
- [ ] Respects configuration setting

#### 2.2 Batch Processing
- [ ] Processes indicators in batches
- [ ] Moves to next batch after completion
- [ ] Handles partial batch completion

### 3. Timeout Tests

#### 3.1 Per-Indicator Timeout
- [ ] Default 30 second timeout
- [ ] Marks indicator as failed on timeout
- [ ] Continues with other indicators
- [ ] Logs timeout error

#### 3.2 Configurable Timeout
- [ ] Respects custom timeout setting
- [ ] Per-processor timeout override

### 4. Memory Limit Tests

#### 4.1 Memory Monitoring
- [ ] Monitors worker memory usage
- [ ] Stops before reaching limit (256MB)
- [ ] Logs memory usage

#### 4.2 Memory Recovery
- [ ] Clears memory between jobs
- [ ] Handles memory-heavy processors

### 5. Retry Logic Tests

#### 5.1 Single Retry
- [ ] Retries failed indicator once
- [ ] Exponential backoff applied
- [ ] Marks as failed after retry

#### 5.2 Non-Retryable Errors
- [ ] Doesn't retry invalid URL errors
- [ ] Doesn't retry SSRF-blocked URLs

### 6. Progress Tracking Tests

#### 6.1 Progress Updates
- [ ] Updates progress_percent after each indicator
- [ ] Updates completed_indicators count
- [ ] Updates failed_indicators count
- [ ] Dispatches progress events

#### 6.2 Real-time Updates
- [ ] Progress visible immediately
- [ ] Events dispatched for SSE

### 7. Graceful Shutdown Tests

#### 7.1 Signal Handling
- [ ] Handles SIGTERM gracefully
- [ ] Completes current indicator
- [ ] Saves job state before exit

#### 7.2 Job Recovery
- [ ] Running jobs marked as failed on worker crash
- [ ] Can resume pending jobs

### 8. Error Handling Tests

#### 8.1 Indicator Failure
- [ ] Marks indicator as failed
- [ ] Continues with other indicators
- [ ] Logs error message

#### 8.2 Complete Job Failure
- [ ] Handles all indicators failing
- [ ] Marks job as failed
- [ ] Sets error message

### 9. Queue Monitoring Tests

#### 9.1 Queue Depth
- [ ] Logs queue depth
- [ ] Alerts on queue buildup

#### 9.2 Processing Time
- [ ] Logs processing time per indicator
- [ ] Tracks average processing time

### 10. Stress Tests

#### 10.1 High Load
- [ ] Handles 100+ queued jobs
- [ ] Maintains concurrency limit
- [ ] No memory leaks

#### 10.2 Long Running
- [ ] Runs for extended period
- [ ] Memory stable
- [ ] No connection leaks

### 11. Integration Tests

#### 11.1 Full Job Processing
- [ ] Processes complete job end-to-end
- [ ] All indicators processed
- [ ] Progress tracked correctly

#### 11.2 Concurrent Jobs
- [ ] Multiple jobs processing simultaneously
- [ ] Per-job concurrency respected
- [ ] Global concurrency respected

---

## Test Implementation Notes

- Use Laravel's Queue::fake() for unit tests
- Use Redis for queue state tracking
- Test with actual workers for integration
- Mock slow/failing processors

---

## Dependencies
- TASK-19-3 (AnalysisJobService)
- TASK-19-5 (IndicatorProcessor)
- TASK-19-6 (Core indicators)

---

## Coverage Target
- 50+ test methods
- 85%+ code coverage

---

*Created by PreTester for TASK-19-7*
