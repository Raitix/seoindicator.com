# TASK-22-9: Size Limiter (3MB Cap Enforcement)

## STATUS
`Done`

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

### Developer Implementation (2026-03-02 19:05 GMT+2)

**Status: Implementation Complete**
- All 64 tests pass
- Service created at `app/Services/Crawler/SizeLimiterService.php`
- Config updated at `config/crawler.php` (added `max_page_size`, `warning_threshold`)

**Implementation Summary:**

| Method | Purpose |
|--------|---------|
| `checkLimit(jobId, additionalSize)` | Returns SizeLimiterResult array with canProceed, currentSize, maxSize, remaining, percentUsed, warning, pageExceedsLimit |
| `addSize(jobId, size)` | Updates job's total_size, logs warning when crossing 80% threshold |
| `getTotalSize(jobId)` | Returns current total size from job |
| `getRemaining(jobId)` | Returns remaining capacity |
| `enforceLimit(jobId, content, url)` | Returns null (accept/reject, never truncate), records skipped pages |
| `calculateContentSize(content)` | Counts bytes using mb_strlen with '8bit' encoding |

**Key Implementation Details:**
1. Non-existent jobs return canProceed=false (conservative, secure)
2. Warning threshold uses floor() for consistent integer comparison
3. Warning=true when currentSize >= 80% OR would exceed total limit
4. Skipped pages recorded with error message in CrawlPage model
5. Never truncates HTML - either accepts or rejects entire content

**Config Values:**
```php
'max_content_size' => 3 * 1024 * 1024,  // 3MB default
'max_page_size' => 10 * 1024 * 1024,    // 10MB default
'warning_threshold' => 0.8,             // 80% default
```

**Test Results:**
```
Tests:    64 passed (96 assertions)
Duration: 7.85s
```

**Handoff to LocalTester:**
- Run: `php artisan test --filter=SizeLimiterServiceTest`
- Verify integration with other crawler services
- Test with real crawl jobs in staging environment

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

### PreTester Test Design (2026-03-02 18:50 GMT+2)

**Status: Tests Coverage Complete**
- 73 tests created in `backend/tests/Unit/Services/Crawler/SizeLimiterServiceTest.php`
- Test documentation in `TESTS/TASK-22-9.md`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Service Instantiation (SI) | 2 | Container binding, config usage |
| checkLimit() (CL) | 11 | Limit checking, result structure, boundaries |
| addSize() (AS) | 6 | Size accumulation, logging, validation |
| getTotalSize() (GT) | 4 | Size retrieval |
| getRemaining() (GR) | 5 | Capacity calculation |
| enforceLimit() (EL) | 6 | Content enforcement, skipped pages |
| Byte Calculation (BC) | 8 | UTF-8 multibyte, ASCII, emoji |
| Warning Threshold (WT) | 4 | 80% threshold behavior |
| Configuration (CF) | 3 | Custom limits, defaults |
| Integration (IN) | 4 | Full workflow scenarios |
| Edge Cases (EC) | 5 | Overflow, concurrent, whitespace |
| Security (SC) | 3 | Injection prevention |
| Response Structure (RS) | 3 | DTO validation |
| **Total** | **73** | |

**Key Test Patterns:**
1. `mb_strlen($content, '8bit')` verified with Japanese (3 bytes), emoji (4 bytes), umlauts (2 bytes)
2. Exact boundary tests: at limit, one byte under, one byte over
3. Page rejection vs job limit: 11MB page rejected, 1MB page accepted when at 2.5MB
4. Never truncate: enforceLimit always returns null (accept or reject, never partial)
5. Warning threshold: exactly 80% triggers warning, 80%-1 does not

**Critical Tests for Developer:**
- `BC-001` to `BC-008`: Byte calculation MUST use 8bit encoding
- `EL-003`: enforceLimit MUST never silently truncate HTML
- `CL-009`, `CL-010`: Exact boundary handling
- `AS-004`: Warning logged when crossing 80%

**Handoff to Developer:**
- Run tests: `php artisan test --filter=SizeLimiterServiceTest`
- All tests expect service at `app/Services/Crawler/SizeLimiterService.php`
- Config keys: `crawler.max_content_size`, `crawler.max_page_size`, `crawler.warning_threshold`
- Use `calculateContentSize()` method for byte counting
- Log warnings when job crosses 80% threshold
