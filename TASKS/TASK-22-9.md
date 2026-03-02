# TASK-22-9: Size Limiter (3MB Cap Enforcement)

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Medium`

## Related Specification
SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## Description
Implement size limiter to track and enforce the 3MB total content cap per crawl job, preventing resource exhaustion.

## Acceptance Criteria

### Service Structure
- [ ] Service created at `app/Services/Crawler/SizeLimiterService.php`
- [ ] Constructor injection for CrawlJob model

### Core Methods
- [ ] `checkLimit(string $jobId, int $additionalSize): bool` - Check if adding content would exceed limit
- [ ] `addSize(string $jobId, int $size): void` - Record content size for job
- [ ] `getTotalSize(string $jobId): int` - Get current total size
- [ ] `getRemaining(string $jobId): int` - Get remaining capacity
- [ ] `enforceLimit(string $jobId, string $content): string|null` - Truncate content if needed

### Size Tracking
- [ ] Track cumulative size per crawl job
- [ ] Track size per page
- [ ] Store in crawl_jobs.total_size field
- [ ] Update after each page is processed

### Size Limits
- [ ] Default max total size: 3MB (3,145,728 bytes)
- [ ] Max page size: 10MB (10,485,760 bytes) - reject larger pages
- [ ] Warning threshold: 80% of max (2.4MB)
- [ ] Configurable via config/crawler.php

### Enforcement Behavior
- [ ] When total exceeds max: stop crawling, mark job as completed with partial results
- [ ] When page exceeds page max: skip page, log warning, continue with other pages
- [ ] Record which pages were skipped due to size
- [ ] Never silently truncate HTML (breaks SEO analysis)

### Size Calculation
- [ ] Count bytes, not characters (mb_strlen with 8bit encoding)
- [ ] Include rendered_html size
- [ ] Include structured_text size
- [ ] Don't include meta/links JSON size (negligible)
- [ ] Handle UTF-8 correctly

### Response Handling
- [ ] Return remaining capacity in API responses
- [ ] Include size warnings in job status
- [ ] Include total size in completion summary

### SizeLimiterResult Structure
```php
[
    'canProceed' => bool,
    'currentSize' => int,
    'maxSize' => int,
    'remaining' => int,
    'percentUsed' => float,
    'warning' => bool, // true if > 80%
    'pageExceedsLimit' => bool,
]
```

### Configuration
- [ ] `max_content_size` (default: 3145728 bytes = 3MB)
- [ ] `max_page_size` (default: 10485760 bytes = 10MB)
- [ ] `warning_threshold` (default: 0.8 = 80%)
- [ ] `enforce_strict` (default: true - stop on limit)

### Database Updates
- [ ] Update CrawlJob model to track total_size
- [ ] Update CrawlPage model to track individual size
- [ ] Add scope for jobs over size limit

### Tests
- [ ] Test size tracking
- [ ] Test limit enforcement
- [ ] Test page size rejection
- [ ] Test remaining calculation
- [ ] Test with Unicode content (multi-byte characters)
- [ ] Test warning threshold

## Dependencies
- TASK-22-1 (Database migrations)
- TASK-22-2 (Models)

## Effort Estimate
`Low` (2 hours)

## Notes
- Size limits are critical for preventing memory exhaustion
- Consider compression for storage (doesn't affect cap calculation)
- Log when jobs hit size limits for monitoring

## Comments
_Leave notes for the next agent here_

### ProjectManager Prioritization (2026-03-02 17:42 GMT+2)

**Priority: Medium (confirmed)**
- Resource protection service - prevents memory exhaustion from oversized crawl results
- Not on critical path but important for production stability
- Dependencies satisfied - can proceed immediately

**Risk: Medium (confirmed)**
- Incorrect size calculation could cause false rejections or memory issues
- UTF-8 byte handling is critical (mb_strlen with 8bit encoding)
- Affects user experience when limits are hit

**Dependencies Status:**
| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-1 (Migrations) | Done ✅ | total_size column exists |
| TASK-22-2 (Models) | Done ✅ | CrawlJob and CrawlPage models ready |

**Execution Context:**
- **Phase 3 Service** - Runs after migrations and models
- Service: `app/Services/Crawler/SizeLimiterService.php`
- Key methods: checkLimit(), addSize(), getTotalSize(), getRemaining(), enforceLimit()
- Size limits: 3MB total, 10MB per page, 80% warning threshold
- Database: Uses crawl_jobs.total_size and crawl_pages.size fields

**Key Implementation Details:**
1. Byte counting (not characters) - use `mb_strlen($content, '8bit')`
2. Cumulative tracking per job across all pages
3. Page size rejection (>10MB) vs job limit (stop crawling, partial results)
4. Never silently truncate HTML (breaks SEO analysis)
5. Configuration via `config/crawler.php`

**Handoff to PreTester:**
- Design tests for size tracking, limit enforcement, Unicode handling
- Test edge cases: exact limit, 1 byte over, multi-byte UTF-8 characters
- Test warning threshold at 80% (2.4MB)
- Test page size rejection vs job size limit behavior
- Test SizeLimiterResult DTO structure
