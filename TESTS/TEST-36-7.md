# TEST-36-7: ContentDecayDigestService Tests

## Overview
Comprehensive unit and feature tests for the ContentDecayDigestService - the service that generates weekly/monthly summaries of content decay activity including newly detected pages, recovered pages, and actionable recommendations.

**Total Tests:** 200+

---

## Test File Locations
- `backend/tests/Unit/Services/ContentDecay/ContentDecayDigestServiceTest.php` (150+ tests)
- `backend/tests/Feature/Services/ContentDecay/ContentDecayDigestServiceTest.php` (50+ tests)

---

## Test Categories

### SI: Service Instantiation (10 tests)
| ID | Description | Expected Result |
|----|-------------|-----------------|
| SI-001 | Class exists in correct namespace | `App\Services\ContentDecay\ContentDecayDigestService` |
| SI-002 | Class can be instantiated | No exceptions |
| SI-003 | Container binding works | `app(ContentDecayDigestService::class)` |
| SI-004 | Required dependencies injected | ContentDecayCandidate model |
| SI-005 | generateDigest method exists | Public method with project param |
| SI-006 | shouldSendDigest method exists | Public method returns boolean |
| SI-007 | getNewCandidates method exists | Protected method |
| SI-008 | getRecoveredCandidates method exists | Protected method |
| SI-009 | formatDigestPayload method exists | Protected method |
| SI-010 | Default configuration loaded | Frequency, thresholds |

### GD: generateDigest() Method (35 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| GD-001 | Returns digest array structure | Valid Project | Array with new, recovered, summary |
| GD-002 | Respects digest frequency weekly | Weekly project | Weekly aggregation |
| GD-003 | Respects digest frequency monthly | Monthly project | Monthly aggregation |
| GD-004 | Filters by date range | Project with dates | Correct date filter |
| GD-005 | Excludes ignored candidates | Mixed statuses | Ignored excluded |
| GD-006 | Excludes low-severity noise | Low severity | Excluded if below threshold |
| GD-007 | Includes newly detected pages | New candidates | In new_candidates array |
| GD-008 | Includes recovered pages | Recovered status | In recovered_candidates array |
| GD-009 | Generates summary statistics | Any candidates | Stats calculated |
| GD-010 | Returns empty digest if no changes | No new activity | Empty arrays |
| GD-011 | Handles project not found | Invalid ID | Returns null or empty |
| GD-012 | Handles project digest disabled | digest_enabled=false | Returns null |
| GD-013 | Respects min_decay_score filter | High threshold | Low scores excluded |
| GD-014 | Groups by severity | Mixed severities | Severity breakdown |
| GD-015 | Calculates estimated upside | Candidates present | Upside sum |
| GD-016 | Formats page URLs correctly | Any URL | Truncated if needed |
| GD-017 | Includes reason codes | Candidates present | Reasons in output |
| GD-018 | Includes page titles | Candidates present | Titles in output |
| GD-019 | Sorts by decay score | Multiple candidates | Descending order |
| GD-020 | Sorts by recovery upside | Recovery view | Descending order |
| GD-021 | Includes detection timestamps | Any candidate | first_detected_at |
| GD-022 | Includes recovery timestamps | Recovered candidate | recovered_at |
| GD-023 | Limits candidate count | Many candidates | Max N returned |
| GD-024 | Includes project context | Any run | Project info |
| GD-025 | Generates digest ID | Any run | Unique ID |
| GD-026 | Stores digest history | Any run | Digest record |
| GD-027 | Updates last_digest_sent_at | Any run | Timestamp updated |
| GD-028 | Handles concurrent generation | Parallel calls | Lock prevents race |
| GD-029 | Returns timing metrics | Any run | Duration included |
| GD-030 | Logs digest generation | Any run | Log entries |
| GD-031 | Respects user preferences | User settings | Applied |
| GD-032 | Handles timezone correctly | Any dates | UTC normalized |
| GD-033 | Aggregates multiple pages | Many pages | Efficient query |
| GD-034 | Includes severity breakdown | Mixed severity | Count per severity |
| GD-035 | Returns actionable recommendations | Decay detected | Recommendations array |

### SSD: shouldSendDigest() Method (25 tests)
| ID | Description | Condition | Expected Result |
|----|-------------|-----------|-----------------|
| SSD-001 | Returns true for enabled weekly | Weekly, due | true |
| SSD-002 | Returns false for disabled digest | digest_enabled=false | false |
| SSD-003 | Returns false for not yet due | Recent send | false |
| SSD-004 | Returns true for overdue weekly | 8 days since last | true |
| SSD-005 | Returns true for overdue monthly | 32 days since last | true |
| SSD-006 | Respects weekly frequency | 7 days check | Correct boundary |
| SSD-007 | Respects monthly frequency | 30 days check | Correct boundary |
| SSD-008 | Handles null last_digest_sent_at | Never sent | true |
| SSD-009 | Handles detection disabled | detection_enabled=false | false |
| SSD-010 | Returns false if no new activity | No changes | false |
| SSD-011 | Returns true if new critical decay | Critical detected | true |
| SSD-012 | Returns false if only ignored | All ignored | false |
| SSD-013 | Returns false if only recovered | Only recovered | Depends on settings |
| SSD-014 | Respects project settings | Custom settings | Applied |
| SSD-015 | Checks candidate count threshold | Below threshold | false |
| SSD-016 | Handles project without candidates | Empty project | false |
| SSD-017 | Considers severity distribution | Only low severity | May return false |
| SSD-018 | Returns true for first digest | No previous digest | true |
| SSD-019 | Handles frequency change mid-cycle | Changed setting | Uses new frequency |
| SSD-020 | Logs decision reason | Any check | Log entry |
| SSD-021 | Returns false if only stale noise | Low-signal only | false |
| SSD-022 | Returns true after significant change | Major decay detected | true |
| SSD-023 | Respects quiet hours setting | In quiet hours | false |
| SSD-024 | Handles timezone for due check | Different TZ | Correct calculation |
| SSD-025 | Caches result briefly | Multiple calls | Same result |

### NC: getNewCandidates() Method (30 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| NC-001 | Returns collection of candidates | Valid project | Collection returned |
| NC-002 | Filters by date range | Date range | Only in range |
| NC-003 | Filters by status backlog | Mixed status | Only backlog |
| NC-004 | Filters by status planned | Mixed status | Only planned |
| NC-005 | Excludes ignored candidates | Mixed status | Ignored excluded |
| NC-006 | Excludes recovered candidates | Mixed status | Recovered excluded |
| NC-007 | Filters by severity threshold | High threshold | Low excluded |
| NC-008 | Orders by decay score desc | Multiple candidates | Highest first |
| NC-009 | Limits result count | Limit param | Max N results |
| NC-010 | Includes page URL | Any candidate | URL present |
| NC-011 | Includes page title | Any candidate | Title present |
| NC-012 | Includes decay score | Any candidate | Score present |
| NC-013 | Includes severity | Any candidate | Severity present |
| NC-014 | Includes reason codes | Any candidate | Reasons present |
| NC-015 | Includes first_detected_at | Any candidate | Timestamp present |
| NC-016 | Includes recovery upside | Any candidate | Upside present |
| NC-017 | Handles empty result | No candidates | Empty collection |
| NC-018 | Handles large dataset | Many candidates | Efficient pagination |
| NC-019 | Includes page type | Any candidate | Type present |
| NC-020 | Groups by page type | Multiple types | Type breakdown |
| NC-021 | Includes primary reason | Any candidate | Primary reason |
| NC-022 | Filters by project | Project param | Correct project |
| NC-023 | Includes detection confidence | Any candidate | Confidence present |
| NC-024 | Handles null page title | Missing title | Default value |
| NC-025 | Handles null reason codes | Missing reasons | Empty array |
| NC-026 | Truncates long URLs | Long URL | Truncated |
| NC-027 | Handles unicode URLs | Non-ASCII | Encoded correctly |
| NC-028 | Filters by min_lost_clicks | Threshold param | Filtered |
| NC-029 | Includes last_detected_at | Any candidate | Timestamp present |
| NC-030 | Eager loads relationships | With param | Relationships loaded |

### RC: getRecoveredCandidates() Method (25 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| RC-001 | Returns collection of recovered | Valid project | Collection returned |
| RC-002 | Filters by status recovered | Mixed status | Only recovered |
| RC-003 | Filters by date range | Date range | Only in range |
| RC-004 | Filters by recovered_at date | Timestamp filter | Correct dates |
| RC-005 | Excludes ignored candidates | Mixed status | Ignored excluded |
| RC-006 | Orders by recovered_at desc | Multiple candidates | Most recent first |
| RC-007 | Includes page URL | Any candidate | URL present |
| RC-008 | Includes page title | Any candidate | Title present |
| RC-009 | Includes recovery upside | Any candidate | Upside present |
| RC-010 | Includes recovered_at timestamp | Any candidate | Timestamp present |
| RC-011 | Includes pre-recovery score | Any candidate | Score present |
| RC-012 | Includes time to recover | Any candidate | Duration calculated |
| RC-013 | Handles empty result | No recovered | Empty collection |
| RC-014 | Limits result count | Limit param | Max N results |
| RC-015 | Includes page type | Any candidate | Type present |
| RC-016 | Includes original reason codes | Any candidate | Reasons present |
| RC-017 | Includes detection duration | Any candidate | Duration present |
| RC-018 | Handles null values gracefully | Missing data | Defaults used |
| RC-019 | Calculates total recovery upside | Multiple candidates | Sum returned |
| RC-020 | Groups by page type | Multiple types | Type breakdown |
| RC-021 | Includes original severity | Any candidate | Original severity |
| RC-022 | Includes first_detected_at | Any candidate | Timestamp present |
| RC-023 | Filters by project | Project param | Correct project |
| RC-024 | Eager loads signals | With param | Signals loaded |
| RC-025 | Handles unicode URLs | Non-ASCII | Encoded correctly |

### FDP: formatDigestPayload() Method (20 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| FDP-001 | Returns formatted array | Valid data | Array structure |
| FDP-002 | Includes digest_id | Any payload | Unique ID |
| FDP-003 | Includes project_id | Any payload | Project ID |
| FDP-004 | Includes site_id | Any payload | Site ID |
| FDP-005 | Includes generated_at | Any payload | Timestamp |
| FDP-006 | Includes period_start | Any payload | Date |
| FDP-007 | Includes period_end | Any payload | Date |
| FDP-008 | Includes new_candidates | With data | Array of candidates |
| FDP-009 | Includes recovered_candidates | With data | Array of candidates |
| FDP-010 | Includes summary statistics | Any payload | Stats array |
| FDP-011 | Formats URLs correctly | Any URL | Proper format |
| FDP-012 | Truncates long values | Long strings | Truncated |
| FDP-013 | Formats numbers | Any numbers | Proper precision |
| FDP-014 | Formats dates | Any dates | ISO format |
| FDP-015 | Handles empty new_candidates | No new | Empty array |
| FDP-016 | Handles empty recovered | No recovered | Empty array |
| FDP-017 | Includes actionable_recommendations | With data | Recommendations |
| FDP-018 | Includes severity_breakdown | Mixed severity | Counts |
| FDP-019 | Includes page_type_breakdown | Mixed types | Counts |
| FDP-020 | JSON serializable | Any payload | json_encode works |

### SD: sendDigest() Method (25 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| SD-001 | Calls notification system | Valid payload | Notification sent |
| SD-002 | Returns true on success | Valid payload | true |
| SD-003 | Returns false on failure | Invalid payload | false |
| SD-004 | Handles notification exception | Exception thrown | false, logged |
| SD-005 | Logs send attempt | Any send | Log entry |
| SD-006 | Updates last_digest_sent_at | Successful send | Timestamp updated |
| SD-007 | Stores digest record | Any send | Record created |
| SD-008 | Respects user notification prefs | User settings | Applied |
| SD-009 | Handles email notifications | Email channel | Email sent |
| SD-010 | Handles in-app notifications | In-app channel | Notification created |
| SD-011 | Handles webhook notifications | Webhook URL | POST sent |
| SD-012 | Formats email subject | Any payload | Proper subject |
| SD-013 | Formats email body | Any payload | HTML/plain text |
| SD-014 | Includes unsubscribe link | Email payload | Link present |
| SD-015 | Rate limits sends | Rapid sends | Throttled |
| SD-016 | Handles partial failure | Some channels fail | Partial success |
| SD-017 | Retries on transient failure | Temporary error | Retry attempted |
| SD-018 | Respects quiet hours | Night time | Delayed |
| SD-019 | Batches multiple projects | Multiple projects | Batched |
| SD-020 | Validates payload before send | Invalid payload | Skipped |
| SD-021 | Includes digest preview text | Email payload | Preview text |
| SD-022 | Handles large payloads | Many candidates | Truncated or paginated |
| SD-023 | Supports markdown formatting | Markdown flag | Formatted output |
| SD-024 | Tracks delivery status | Any send | Status tracked |
| SD-025 | Cleans up old digest records | Cleanup run | Old records removed |

### EX: Exclusion Logic (20 tests)
| ID | Description | Condition | Expected Result |
|----|-------------|-----------|-----------------|
| EX-001 | Excludes ignored candidates | status=ignored | Not in digest |
| EX-002 | Excludes low severity if threshold | severity=low | Excluded if min=medium |
| EX-003 | Excludes stale noise | Below threshold | Excluded |
| EX-004 | Excludes candidates below score | decay_score < min | Excluded |
| EX-005 | Excludes candidates below lost_clicks | lost < threshold | Excluded |
| EX-006 | Includes all severities if no threshold | No threshold | All included |
| EX-007 | Respects project min_decay_score | Custom threshold | Applied |
| EX-008 | Excludes already sent candidates | Duplicate check | No duplicates |
| EX-009 | Excludes candidates outside date range | Outside window | Excluded |
| EX-010 | Includes edge case - exact threshold | Exact match | Included |
| EX-011 | Handles null exclusion fields | Null values | Default behavior |
| EX-012 | Excludes candidates with low confidence | Confidence < threshold | Excluded |
| EX-013 | Includes mixed severity with threshold | Mixed | Filtered correctly |
| EX-014 | Excludes recently sent in batch | Recent send | Deduped |
| EX-015 | Respects user exclusion settings | User prefs | Applied |
| EX-016 | Logs exclusion reasons | Any exclusion | Log entry |
| EX-017 | Counts excluded for reporting | Exclusions | Count tracked |
| EX-018 | Excludes candidates with no upside | Zero upside | May exclude |
| EX-019 | Handles empty exclusion list | No exclusions | All included |
| EX-020 | Excludes noise from seasonal pages | Seasonal flag | Excluded |

### PERF: Performance Tests (10 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| PERF-001 | Single project digest | 1 project | < 100ms |
| PERF-002 | Small candidate set | 10 candidates | < 50ms |
| PERF-003 | Medium candidate set | 100 candidates | < 200ms |
| PERF-004 | Large candidate set | 1000 candidates | < 2 seconds |
| PERF-005 | Memory usage | 100 candidates | < 50MB |
| PERF-006 | Database query count | 100 candidates | Bounded queries |
| PERF-007 | Batch processing | Multiple projects | Efficient batching |
| PERF-008 | Cache utilization | Repeat calls | Cache hit |
| PERF-009 | Large payload handling | 500 candidates | No memory issues |
| PERF-010 | Concurrent digest generation | Parallel calls | Lock handled |

### SEC: Security Tests (10 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| SEC-001 | SQL injection in URL filter | Malicious URL | Sanitized |
| SEC-002 | XSS in page title | Script tags | Escaped |
| SEC-003 | User isolation | Cross-user project | Access denied |
| SEC-004 | Project ownership check | Other user's project | Access denied |
| SEC-005 | Sensitive data not exposed | PII in candidates | Not in digest |
| SEC-006 | Payload size limit | Large payload | Truncated |
| SEC-007 | Email header injection | Malicious email | Sanitized |
| SEC-008 | Notification rate limiting | Rapid sends | Throttled |
| SEC-009 | Input validation | Invalid params | Validation error |
| SEC-010 | Digest record authorization | Access check | Verified |

### INT: Integration Tests (15 tests)
| ID | Description | Context | Expected Result |
|----|-------------|---------|-----------------|
| INT-001 | Full digest generation | All dependencies | Complete digest |
| INT-002 | Database persistence | Digest run | Records created |
| INT-003 | Notification dispatch | Valid digest | Notification sent |
| INT-004 | Event dispatching | Digest generated | Event fired |
| INT-005 | Queue job integration | Async digest | Job queued |
| INT-006 | API response format | Controller call | Correct format |
| INT-007 | Error propagation | Service failure | Proper error |
| INT-008 | Transaction handling | Partial failure | Rolled back |
| INT-009 | Cache invalidation | New digest | Cache cleared |
| INT-010 | Logging integration | Any run | Log entries |
| INT-011 | Metrics collection | Any run | Metrics stored |
| INT-012 | Multiple projects | Batch digest | All processed |
| INT-013 | Recovery measurement | Recovered pages | Correct stats |
| INT-014 | Notification preferences | User settings | Preferences applied |
| INT-015 | Cleanup job integration | Old records | Cleaned up |

### EDGE: Edge Cases (20 tests)
| ID | Description | Input | Expected Behavior |
|----|-------------|-------|-------------------|
| EDGE-001 | Empty project | No candidates | Empty digest |
| EDGE-002 | Null project settings | Missing settings | Defaults used |
| EDGE-003 | Very old candidates | 1 year old | Excluded by date |
| EDGE-004 | Zero decay score | No decay | Excluded |
| EDGE-005 | Maximum candidates | 1000 candidates | Limited |
| EDGE-006 | Unicode in page URLs | Non-ASCII | Handled correctly |
| EDGE-007 | Very long URL | 2000+ chars | Truncated |
| EDGE-008 | Special characters in title | Emojis, symbols | Stored correctly |
| EDGE-009 | Concurrent digest generation | Race condition | Lock prevents issues |
| EDGE-010 | Orphan candidates | Project deleted | Cleaned up |
| EDGE-011 | Missing project ID | Null project | Exception handled |
| EDGE-012 | Invalid frequency setting | Unknown frequency | Default used |
| EDGE-013 | Notification service down | Service unavailable | Logged, retry queued |
| EDGE-014 | Database connection lost | Connection error | Graceful failure |
| EDGE-015 | Memory limit approached | Large dataset | Batch processing |
| EDGE-016 | Timeout during generation | Long running | Partial results |
| EDGE-017 | Invalid candidate status | Unknown status | Default status |
| EDGE-018 | Circular reference detection | Self-reference | Prevented |
| EDGE-019 | Negative metrics | Negative clicks | Treated as zero |
| EDGE-020 | Future-dated candidates | Future dates | Ignored |

---

## Test Implementation Patterns

### Mock Dependencies
```php
// Mock ContentDecayProject
$project = Mockery::mock(ContentDecayProject::class);
$project->shouldReceive('getAttribute')->with('id')->andReturn(1);
$project->shouldReceive('getAttribute')->with('digest_enabled')->andReturn(true);
$project->shouldReceive('getAttribute')->with('digest_frequency')->andReturn('weekly');

// Mock Candidate Query
$candidates = collect([
    (object) [
        'id' => 1,
        'page_url' => 'https://example.com/page1',
        'page_title' => 'Test Page',
        'decay_score' => 45.5,
        'severity' => 'high',
        'status' => 'backlog',
        'reason_codes' => ['clicks_down', 'position_down'],
    ],
]);
```

### Mock Digest Payload
```php
$digestPayload = [
    'digest_id' => 'digest-abc123',
    'project_id' => 1,
    'site_id' => 1,
    'generated_at' => '2026-03-11T12:00:00Z',
    'period_start' => '2026-03-04',
    'period_end' => '2026-03-11',
    'new_candidates' => [
        [
            'page_url' => 'https://example.com/blog/seo-tips',
            'page_title' => '10 SEO Tips for 2024',
            'decay_score' => 45.5,
            'severity' => 'high',
            'reason_codes' => ['clicks_down'],
            'lost_clicks' => 450,
            'recovery_upside' => 500,
            'first_detected_at' => '2026-03-10T15:30:00Z',
        ],
    ],
    'recovered_candidates' => [
        [
            'page_url' => 'https://example.com/old-post',
            'page_title' => 'Recovered Post',
            'recovery_upside' => 200,
            'recovered_at' => '2026-03-09T10:00:00Z',
        ],
    ],
    'summary' => [
        'total_new' => 5,
        'total_recovered' => 2,
        'total_ignored' => 1,
        'critical_count' => 1,
        'high_count' => 3,
        'medium_count' => 1,
        'estimated_upside_clicks' => 1500,
        'severity_breakdown' => [
            'critical' => 1,
            'high' => 3,
            'medium' => 1,
            'low' => 0,
        ],
        'page_type_breakdown' => [
            'blog' => 3,
            'landing' => 1,
            'product' => 1,
        ],
    ],
    'actionable_recommendations' => [
        'Focus on critical pages with highest recovery upside',
        'Refresh content on 3 high-severity blog posts',
        'Fix technical issues on 2 landing pages',
    ],
];
```

### Mock Notification Response
```php
// Mock notification service
$notificationService = Mockery::mock(NotificationServiceInterface::class);
$notificationService->shouldReceive('send')
    ->once()
    ->with(
        Mockery::type(User::class),
        'content_decay_digest',
        Mockery::type('array')
    )
    ->andReturn(['success' => true, 'message_id' => 'msg-123']);
```

### Expected Digest Response
```php
[
    'success' => true,
    'digest_id' => 'digest-abc123',
    'sent_at' => '2026-03-11T12:00:00Z',
    'channels' => ['email', 'in_app'],
    'stats' => [
        'new_candidates' => 5,
        'recovered_candidates' => 2,
        'total_candidates' => 7,
    ],
]
```

---

## Service Interface
```php
interface ContentDecayDigestServiceInterface
{
    /**
     * Generate a digest for a project.
     */
    public function generateDigest(ContentDecayProject $project, array $options = []): array;
    
    /**
     * Determine if a digest should be sent for a project.
     */
    public function shouldSendDigest(ContentDecayProject $project): bool;
    
    /**
     * Send the digest through notification channels.
     */
    public function sendDigest(ContentDecayProject $project, array $payload): bool;
    
    /**
     * Get the date range for the next digest period.
     */
    public function getDigestPeriod(ContentDecayProject $project): array;
}
```

---

## Dependencies
| Dependency | Status | TDD Approach |
|------------|--------|--------------|
| TASK-36-1 (Migrations) | Done ✅ | Use RefreshDatabase |
| TASK-36-2 (Models) | To Test Locally | Use factory models |
| FEAT-002 (Alerts/Notifications) | Done ✅ | Mock notification service |

---

## Coverage Target
- **Lines:** 95%
- **Branches:** 90%
- **Methods:** 100%

---

## Acceptance Criteria Mapping

| AC | Tests | Status |
|----|-------|--------|
| generateDigest() method exists | SI-005 | ✅ Covered |
| generateDigest() returns new candidates | GD-007, NC section | ✅ Covered |
| generateDigest() returns recovered candidates | GD-008, RC section | ✅ Covered |
| generateDigest() excludes ignored candidates | GD-005, EX-001 | ✅ Covered |
| generateDigest() excludes stale-noise candidates | GD-006, EX section | ✅ Covered |
| shouldSendDigest() method exists | SI-006 | ✅ Covered |
| shouldSendDigest() respects frequency | SSD section | ✅ Covered |
| sendDigest() integrates with notification system | SD section | ✅ Covered |
| Unit tests verify new candidate filtering | NC section | ✅ Covered |
| Unit tests verify recovered candidate filtering | RC section | ✅ Covered |
| Feature tests verify full digest flow | INT section | ✅ Covered |

---

*Created by PreTester agent on 2026-03-11*
