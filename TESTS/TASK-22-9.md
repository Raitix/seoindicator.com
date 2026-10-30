# TESTS/TASK-22-9: Size Limiter (3MB Cap Enforcement)

## Task Reference
- **Task:** [TASK-22-9](../PROJECT-SEOINDICATOR/TASKS/TASK-22-9.md)
- **Specification:** SPEC-022 (Headless Crawler Service)
- **Feature:** FEAT-022 (Headless Crawler Service - JS-Rendered HTML)

## Overview
Comprehensive unit tests for `SizeLimiterService` that enforces the 3MB total content cap per crawl job. Tests cover size tracking, limit enforcement, byte calculation for UTF-8 content, warning thresholds, and security measures.

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `backend/tests/Unit/Services/Crawler/SizeLimiterServiceTest.php` | 73 | Main service tests |

## Test Scenarios

### Service Instantiation (SI) - 2 tests
| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SI-001 | Service instantiated via container | Instance of SizeLimiterService |
| SI-002 | Service uses config values | Limits match configured values |

### checkLimit() Method (CL) - 11 tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CL-001 | Under limit | 1MB current, add 1MB | canProceed: true |
| CL-002 | Would exceed limit | 2.4MB current, add 1MB | canProceed: false |
| CL-003 | Result structure | Any | All required keys present |
| CL-004 | Remaining calculation | 1MB current | remaining: 2MB |
| CL-005 | Percent calculation | 1.5MB current | percentUsed: 50.0 |
| CL-006 | Warning at 80% | 2.4MB current | warning: true |
| CL-007 | No warning below 80% | 2MB current | warning: false |
| CL-008 | Page exceeds max | 11MB page | pageExceedsLimit: true |
| CL-009 | Exact limit boundary | At max | canProceed: false |
| CL-010 | One byte under max | max-1 | canProceed: true |
| CL-011 | Job not found | Invalid job_id | Default values returned |

### addSize() Method (AS) - 6 tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AS-001 | Updates total_size | Add 500KB | total_size updated |
| AS-002 | Multiple additions | 3 adds of 500KB, 300KB, 200KB | total_size: 1MB |
| AS-003 | Zero addition | Add 0 bytes | No change |
| AS-004 | Warning logged | Addition crosses 80% | Warning logged |
| AS-005 | Non-existent job | Invalid job_id | RuntimeException |
| AS-006 | Negative size | -500 | InvalidArgumentException |

### getTotalSize() Method (GT) - 4 tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GT-001 | Returns correct size | 1.2MB job | 1234567 bytes |
| GT-002 | New job | 0 bytes | 0 |
| GT-003 | Non-existent job | Invalid ID | 0 |
| GT-004 | After addSize | Add 500KB | Reflects change |

### getRemaining() Method (GR) - 5 tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GR-001 | Correct remaining | 1MB current | 2MB remaining |
| GR-002 | New job | 0 bytes | Full capacity |
| GR-003 | At limit | 3MB current | 0 remaining |
| GR-004 | Over limit | 3MB+ current | 0 remaining |
| GR-005 | Non-existent job | Invalid ID | Full capacity |

### enforceLimit() Method (EL) - 6 tests
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EL-001 | Content fits | 1MB content | null (accepted) |
| EL-002 | Page exceeds limit | 11MB content | null (rejected) |
| EL-003 | Never truncates | Would exceed job limit | null (rejected, not truncated) |
| EL-004 | Records skipped pages | Oversized page | Skipped page record created |
| EL-005 | Empty content | "" | null |
| EL-006 | Null content | null | null |

### Byte Calculation (BC) - 8 tests
| ID | Scenario | Input | Expected Bytes |
|----|----------|-------|----------------|
| BC-001 | ASCII content | 1000 'a' chars | 1000 bytes |
| BC-002 | UTF-8 multibyte | 100 Japanese chars | 300 bytes |
| BC-003 | Emoji | 100 emoji | 400 bytes |
| BC-004 | Mixed content | 10 ASCII + 10 Japanese + 5 emoji | 60 bytes |
| BC-005 | 8bit encoding | 'äöü' | 6 bytes |
| BC-006 | Empty string | "" | 0 bytes |
| BC-007 | Null | null | 0 bytes |
| BC-008 | Large content | 3MB - 1 chars | 3MB - 1 bytes |

### Warning Threshold (WT) - 4 tests
| ID | Scenario | Current Size | Warning |
|----|----------|--------------|---------|
| WT-001 | Exactly 80% | 2.4MB (80%) | true |
| WT-002 | Just below 80% | 2.4MB - 1 | false |
| WT-003 | Above 80% | 2.8MB | true |
| WT-004 | Custom threshold | 51% (50% threshold) | true |

### Configuration (CF) - 3 tests
| ID | Scenario | Config | Expected |
|----|----------|--------|----------|
| CF-001 | Custom max_content_size | 1MB | 1.1MB rejected |
| CF-002 | Custom max_page_size | 5MB | 6MB page rejected |
| CF-003 | Missing config | null | Uses defaults |

### Integration (IN) - 4 tests
| ID | Scenario | Description |
|----|----------|-------------|
| IN-001 | Full workflow | Multiple pages, size accumulation |
| IN-002 | Hits size limit | Job at 2.5MB, try to add 1MB |
| IN-003 | Page rejected, others continue | 11MB page rejected, 500KB accepted |
| IN-004 | Percent accuracy | Exactly 50% usage |

### Edge Cases (EC) - 5 tests
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| EC-001 | Small additions | 1000 × 1KB | 1MB total |
| EC-002 | Integer overflow | PHP_INT_MAX - 1 | Handled gracefully |
| EC-003 | Concurrent access | Multiple additions | Correct total |
| EC-004 | Zero byte content | "" | Handled |
| EC-005 | Whitespace only | 1000 spaces | 1000 bytes |

### Security (SC) - 3 tests
| ID | Scenario | Input | Expected |
|----|----------|-------|----------|
| SC-001 | Negative size injection | -500000 | Exception thrown |
| SC-002 | SQL injection in job_id | "'; DROP TABLE..." | Safe handling |
| SC-003 | XSS in URL | <script>... | Safe logging |

### Response Structure (RS) - 3 tests
| ID | Scenario | Expected |
|----|----------|----------|
| RS-001 | All required keys | 7 keys present |
| RS-002 | Types correct | bool, int, float |
| RS-003 | maxSize matches config | 3145728 |

## Mock Data

### SizeLimiterResult Structure
```php
[
    'canProceed' => bool,        // Whether content can be added
    'currentSize' => int,        // Current total bytes
    'maxSize' => int,            // Maximum allowed bytes
    'remaining' => int,          // Remaining capacity
    'percentUsed' => float,      // Percentage of limit used
    'warning' => bool,           // True if > 80%
    'pageExceedsLimit' => bool,  // True if page > 10MB
]
```

### Size Constants
```php
MAX_CONTENT_SIZE = 3145728;  // 3MB in bytes
MAX_PAGE_SIZE = 10485760;    // 10MB in bytes
WARNING_THRESHOLD = 0.8;     // 80%
WARNING_SIZE = 2516582;      // 2.4MB (80% of 3MB)
```

### Byte Calculation Examples
| Content | Character Count | Byte Count |
|---------|-----------------|------------|
| `'abc'` | 3 | 3 |
| `'äöü'` | 3 | 6 |
| `'あいう'` | 3 | 9 |
| `'😀😁😂'` | 3 | 12 |

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Service Instantiation | 100% | 2 |
| checkLimit() | 100% | 11 |
| addSize() | 100% | 6 |
| getTotalSize() | 100% | 4 |
| getRemaining() | 100% | 5 |
| enforceLimit() | 100% | 6 |
| Byte Calculation | 100% | 8 |
| Warning Threshold | 100% | 4 |
| Configuration | 100% | 3 |
| Integration | 100% | 4 |
| Edge Cases | 100% | 5 |
| Security | 100% | 3 |
| Response Structure | 100% | 3 |
| **Total** | **100%** | **73** |

## Test Execution

```bash
# Run all SizeLimiter tests
php artisan test --filter=SizeLimiterServiceTest

# Run specific test category
php artisan test --filter="SizeLimiterServiceTest::test_bc"

# Run with coverage
php artisan test --filter=SizeLimiterServiceTest --coverage
```

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-22-1 (Migrations) | Done ✅ | total_size column exists |
| TASK-22-2 (Models) | Done ✅ | CrawlJob and CrawlPage models |
| CrawlJobFactory | Done ✅ | Factory with states |
| CrawlPageFactory | Done ✅ | Factory with states |

## Notes for Developer

### Implementation Requirements
1. **Service Location:** `app/Services/Crawler/SizeLimiterService.php`
2. **Byte Calculation:** Use `mb_strlen($content, '8bit')` NOT `strlen()`
3. **Never Truncate:** HTML content must never be silently truncated
4. **Page Rejection:** Pages > 10MB are skipped, not truncated
5. **Warning Logging:** Log when job crosses 80% threshold

### SizeLimiterResult DTO
```php
class SizeLimiterResult
{
    public bool $canProceed;
    public int $currentSize;
    public int $maxSize;
    public int $remaining;
    public float $percentUsed;
    public bool $warning;
    public bool $pageExceedsLimit;
}
```

### Key Behaviors
1. **Under Limit:** Content accepted, size added to job
2. **Would Exceed Limit:** Return canProceed=false, do NOT add size
3. **Page > 10MB:** Skip page, log warning, record as skipped, continue crawling
4. **Job > 3MB:** Stop crawling, mark job completed with partial results

### Config Keys
```php
// config/crawler.php
'max_content_size' => env('CRAWLER_MAX_CONTENT_SIZE', 3145728), // 3MB
'max_page_size' => env('CRAWLER_MAX_PAGE_SIZE', 10485760),     // 10MB
'warning_threshold' => env('CRAWLER_WARNING_THRESHOLD', 0.8),  // 80%
```

## Comments

### PreTester Notes (2026-03-02)
- 73 tests covering all acceptance criteria
- UTF-8 byte calculation tests critical for international content
- Security tests for negative size and injection attacks
- Edge cases for integer overflow and concurrent access
- Tests verify HTML is NEVER silently truncated

---

Last updated: 2026-03-02
