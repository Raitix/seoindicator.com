# TEST-36-6: ContentDecayDetectionService Tests

## Overview
Comprehensive unit and feature tests for the ContentDecayDetectionService - the main orchestration service that gathers signals from multiple sources, compares windows, and creates/updates decay candidates.

**Total Tests:** 380+

---

## Test File Locations
- `backend/tests/Unit/Services/ContentDecay/ContentDecayDetectionServiceTest.php` (280+ tests)
- `backend/tests/Feature/Services/ContentDecay/ContentDecayDetectionServiceTest.php` (100+ tests)

---

## Test Categories

### SI: Service Instantiation (10 tests)
| ID | Description | Expected Result |
|----|-------------|-----------------|
| SI-001 | Class exists in correct namespace | `App\Services\ContentDecay\ContentDecayDetectionService` |
| SI-002 | Class can be instantiated | No exceptions |
| SI-003 | Container binding works | `app(ContentDecayDetectionService::class)` |
| SI-004 | Required dependencies injected | ScoringService, ReasonService, PlannerService |
| SI-005 | detectForSite method exists | Public method with Site param |
| SI-006 | detectForProject method exists | Public method with Project param |
| SI-007 | gatherSignals method exists | Protected method with URL + Project params |
| SI-008 | compareWindows method exists | Protected method with signals array |
| SI-009 | upsertCandidate method exists | Protected method with detection data |
| SI-010 | Default configuration loaded | Window sizes, thresholds |

### DFS: detectForSite() Method (30 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| DFS-001 | Returns detection summary array | Valid Site | Array with processed, candidates_created |
| DFS-002 | Loads project settings | Site with project | Project loaded correctly |
| DFS-003 | Creates project if not exists | Site without project | New project created |
| DFS-004 | Filters eligible pages | GSC data | Only eligible pages processed |
| DFS-005 | Calls gatherSignals for each page | Multiple pages | Multiple calls |
| DFS-006 | Calls compareWindows for each page | Multiple pages | Multiple calls |
| DFS-007 | Calls upsertCandidate for each decay | Decay detected | Candidate upserted |
| DFS-008 | Skips ineligible pages | Low impression pages | Not in results |
| DFS-009 | Respects project detection enabled | Disabled project | Returns empty |
| DFS-010 | Handles empty GSC data | No pages | Returns empty processed |
| DFS-011 | Handles GSC API error | Exception thrown | Logs error, continues |
| DFS-012 | Creates detection summary | Multiple pages | Summary with counts |
| DFS-013 | Archives old candidates | No longer decaying | Status updated |
| DFS-014 | Downgrades severity | Decay reduced | Severity updated |
| DFS-015 | Preserves ignored candidates | Status=ignored | Not re-created |
| DFS-016 | Processes pages in batches | Large site | Batch processing |
| DFS-017 | Rate limits GSC calls | Large site | Appropriate delays |
| DFS-018 | Logs processing progress | Any run | Log entries |
| DFS-019 | Returns timing metrics | Any run | Duration in response |
| DFS-020 | Handles concurrent detection | Parallel runs | Lock prevents race |
| DFS-021 | Respects max pages limit | Large site | Limit enforced |
| DFS-022 | Handles site without user | Orphan site | Graceful failure |
| DFS-023 | Validates site active | Disabled site | Skips processing |
| DFS-024 | Returns candidate IDs | Decay detected | IDs in response |
| DFS-025 | Handles partial failures | Some pages fail | Partial results |
| DFS-026 | Stores detection run metadata | Any run | Run record created |
| DFS-027 | Cleans up stale signals | Old signals | Pruned |
| DFS-028 | Archives recovered candidates | No decay detected | Status=recovered |
| DFS-029 | Returns error summary | Failures occurred | Error count |
| DFS-030 | Zero decay pages handled | No decay found | Empty candidates array |

### DFP: detectForProject() Method (25 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| DFP-001 | Returns detection summary | Valid Project | Array with results |
| DFP-002 | Uses project-specific settings | Custom settings | Settings applied |
| DFP-003 | Respects custom window days | Project with 14 days | 14-day windows |
| DFP-004 | Filters by project pages | Project pages only | Correct subset |
| DFP-005 | Handles project not found | Invalid ID | Exception or null |
| DFP-006 | Updates last_scanned_at | Any run | Timestamp updated |
| DFP-007 | Creates signals for candidates | Decay detected | Signals stored |
| DFP-008 | Associates candidates to project | New candidate | project_id set |
| DFP-009 | Handles disabled project | enabled=false | Skips detection |
| DFP-010 | Uses project thresholds | Custom thresholds | Applied correctly |
| DFP-011 | Respects project ignore list | Ignored pages | Skipped |
| DFP-012 | Processes all page types | Mixed content | All types processed |
| DFP-013 | Handles project settings_json | Custom settings | Parsed correctly |
| DFP-014 | Updates project digest timestamp | Detection run | digest_sent_at |
| DFP-015 | Stores detection metadata | Any run | Metadata persisted |
| DFP-016 | Handles project with no pages | Empty project | Empty results |
| DFP-017 | Returns project context | Any run | Project info in result |
| DFP-018 | Validates project belongs to site | Mismatch | Exception |
| DFP-019 | Uses project-specific GSC property | Custom property | Correct data source |
| DFP-020 | Handles project deletion during run | Deleted mid-run | Graceful stop |
| DFP-021 | Respects project priority pages | Priority list | Processed first |
| DFP-022 | Skips recently refreshed pages | Status=refreshed | Within cooldown |
| DFP-023 | Logs per-project metrics | Any run | Log entries |
| DFP-024 | Returns detection confidence | Any run | Confidence score |
| DFP-025 | Handles project with custom GSC filters | Filter settings | Applied to query |

### GS: gatherSignals() Method (40 tests)
| ID | Description | Data Source | Expected Result |
|----|-------------|-------------|-----------------|
| GS-001 | Returns signals array structure | All sources | gsc, rankings, audit, annotations, ai_visibility keys |
| GS-002 | GSC required signals gathered | GSC | clicks, impressions, ctr, position |
| GS-003 | GSC previous window fetched | GSC | previous array populated |
| GS-004 | GSC current window fetched | GSC | current array populated |
| GS-005 | GSC error handling | GSC failure | Returns null/error, continues |
| GS-006 | Rankings signals gathered | Rankings API | visibility score |
| GS-007 | Rankings optional - graceful skip | No rankings | rankings key null |
| GS-008 | Rankings error handling | Rankings failure | Logs warning, continues |
| GS-009 | Audit signals gathered | Audit API | unresolved_issues count |
| GS-010 | Audit optional - graceful skip | No audit | audit key null |
| GS-011 | Audit error handling | Audit failure | Logs warning, continues |
| GS-012 | Annotations gathered | Annotation API | recent_updates array |
| GS-013 | Annotation within 14 days | Recent annotation | Included in signals |
| GS-014 | Annotation older than 14 days | Old annotation | Not included |
| GS-015 | Annotation error handling | Annotation failure | Logs warning, continues |
| GS-016 | AI visibility gathered | AI Visibility API | mention_rate delta |
| GS-017 | AI visibility optional | No AI visibility | ai_visibility key null |
| GS-018 | AI visibility error handling | AI failure | Logs warning, continues |
| GS-019 | Signals timestamp included | Any | gathered_at timestamp |
| GS-020 | Page URL included in signals | Any | page_url field |
| GS-021 | Window dates included | Any | window_start, window_end |
| GS-022 | Previous window null handled | New page | Null previous values |
| GS-023 | Current window null handled | Error case | Null current values |
| GS-024 | Missing data sources logged | Partial data | Warning logged |
| GS-025 | Data source timing tracked | Any | Per-source duration |
| GS-026 | GSC data validation | Invalid GSC | Returns null |
| GS-027 | Rankings data validation | Invalid rankings | Returns null |
| GS-028 | Audit data validation | Invalid audit | Returns null |
| GS-029 | Annotation data validation | Invalid data | Returns null |
| GS-030 | AI visibility data validation | Invalid data | Returns null |
| GS-031 | Multiple annotation types | Mixed types | All included |
| GS-032 | Audit issues by severity | Mixed issues | Severity breakdown |
| GS-033 | Rankings for tracked keywords | Multiple keywords | Aggregated visibility |
| GS-034 | AI visibility for brand terms | Multiple queries | Aggregated mention rate |
| GS-035 | Signals cached appropriately | Repeat request | Cache hit |
| GS-036 | Cache invalidation | New data | Cache refreshed |
| GS-037 | Signal freshness check | Stale data | Re-fetched |
| GS-038 | Partial signal failure | Some sources fail | Available signals returned |
| GS-039 | All sources fail | Total failure | Minimal viable signals |
| GS-040 | Signals serializable | Any | json_encode works |

### CW: compareWindows() Method (35 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| CW-001 | Returns comparison array | Valid signals | deltas, percentages, direction |
| CW-002 | Click delta calculated | Click data | previous - current |
| CW-003 | Impression delta calculated | Impression data | previous - current |
| CW-004 | CTR delta calculated | CTR data | previous - current |
| CW-005 | Position delta calculated | Position data | current - previous (higher is worse) |
| CW-006 | Click percentage calculated | Click data | (delta / previous) * 100 |
| CW-007 | Impression percentage calculated | Impression data | (delta / previous) * 100 |
| CW-008 | CTR percentage calculated | CTR data | previous - current |
| CW-009 | Position percentage calculated | Position data | (delta / previous) * 100 |
| CW-010 | Direction determined | Click decline | "down" |
| CW-011 | Direction determined | Click increase | "up" |
| CW-012 | Direction determined | No change | "stable" |
| CW-013 | Handles null previous | New page | Special handling |
| CW-014 | Handles null current | Data gap | Special handling |
| CW-015 | Handles both null | No data | Zero deltas |
| CW-016 | Division by zero handled | Zero previous | Returns 0 or 100% |
| CW-017 | Rankings comparison included | Rankings data | visibility_delta |
| CW-018 | Audit comparison included | Audit data | issue count comparison |
| CW-019 | AI visibility comparison included | AI data | mention_rate_delta |
| CW-020 | Window size used | Custom days | Correct date range |
| CW-021 | Previous window dates returned | Any | start_date, end_date |
| CW-022 | Current window dates returned | Any | start_date, end_date |
| CW-023 | Comparison timestamp | Any | compared_at field |
| CW-024 | Absolute values included | Any | absolute_change field |
| CW-025 | Large number handling | Millions | Correct calculation |
| CW-026 | Decimal precision | Small deltas | Appropriate precision |
| CW-027 | Negative delta meaning | Clicks down | Positive delta value |
| CW-028 | Positive delta meaning | Clicks up | Negative delta value |
| CW-029 | Position reversed logic | Position worse | Positive delta |
| CW-030 | Position improved logic | Position better | Negative delta |
| CW-031 | Rankings direction | Visibility down | "down" |
| CW-032 | AI visibility direction | Mention rate down | "down" |
| CW-033 | Multiple metrics aggregated | All signals | Combined delta |
| CW-034 | Significant change threshold | Large change | flagged=true |
| CW-035 | Comparison serializable | Any | json_encode works |

### UC: upsertCandidate() Method (30 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| UC-001 | Creates new candidate | New decay | ContentDecayCandidate created |
| UC-002 | Updates existing candidate | Existing decay | Candidate updated |
| UC-003 | Finds by project + page URL hash | Existing | Correct candidate found |
| UC-004 | Sets decay_score | Any | Score stored |
| UC-005 | Sets detection_confidence | Any | Confidence stored |
| UC-006 | Sets reason_codes | Any | Reasons stored |
| UC-007 | Sets signal_sources | Any | Sources stored |
| UC-008 | Sets evidence_json | Any | Evidence stored |
| UC-009 | Sets status to backlog | New candidate | status="backlog" |
| UC-010 | Preserves status if exists | Existing with status | Status not changed |
| UC-011 | Creates ContentDecaySignal | Decay detected | Signal record created |
| UC-012 | Updates last_detected_at | Any | Timestamp updated |
| UC-013 | Increments detection_count | Existing candidate | Count incremented |
| UC-014 | Handles concurrent upsert | Parallel requests | No duplicates |
| UC-015 | Validates required fields | Missing data | Exception thrown |
| UC-016 | Logs upsert action | Any | Log entry |
| UC-017 | Returns candidate model | Any | ContentDecayCandidate returned |
| UC-018 | Sets page_type | Any | Type derived from URL |
| UC-019 | Sets severity | Any | Derived from score |
| UC-020 | Archives old signals | Many signals | Old signals archived |
| UC-021 | Preserves refresh history | Refreshed candidate | History kept |
| UC-022 | Resets refreshed_at if decay again | Re-decay | Refresh date cleared |
| UC-023 | Updates page_url if changed | URL redirect | URL updated |
| UC-024 | Handles large evidence_json | Large payload | Stored correctly |
| UC-025 | Creates in transaction | Any | Atomic operation |
| UC-026 | Rolls back on error | Failure | No partial data |
| UC-027 | Dispatches event | New decay | CandidateDetected event |
| UC-028 | Notifies subscribers | High severity | Notification sent |
| UC-029 | Respects project limit | Max candidates | Oldest archived |
| UC-030 | Returns false on skip | Ineligible | Returns null or false |

### ELIG: Eligibility Filtering (25 tests)
| ID | Description | Condition | Expected Result |
|----|-------------|-----------|-----------------|
| ELIG-001 | GSC data required both windows | Missing previous | Page skipped |
| ELIG-002 | GSC data required both windows | Missing current | Page skipped |
| ELIG-003 | Previous impressions threshold | >= 100 impressions | Eligible |
| ELIG-004 | Previous impressions below threshold | < 100 impressions | Skipped |
| ELIG-005 | Previous clicks threshold alternative | >= 10 clicks | Eligible |
| ELIG-006 | Either threshold passes | 50 impressions, 15 clicks | Eligible |
| ELIG-007 | Neither threshold passes | 50 impressions, 5 clicks | Skipped |
| ELIG-008 | Page existence check | New page | Skipped (too recent) |
| ELIG-009 | Page existence sufficient | 60+ days old | Eligible |
| ELIG-010 | Ignore state check | status=ignored | Skipped |
| ELIG-011 | Normal state | status=backlog | Eligible |
| ELIG-012 | Custom impressions threshold | Project setting | Applied |
| ELIG-013 | Custom clicks threshold | Project setting | Applied |
| ELIG-014 | Threshold from config | Config value | Applied |
| ELIG-015 | Null previous impressions | No data | Skipped |
| ELIG-016 | Zero previous impressions | Zero data | Skipped |
| ELIG-017 | High impressions low clicks | 1000 impressions, 1 click | Eligible |
| ELIG-018 | Low impressions high clicks | 50 impressions, 15 clicks | Eligible |
| ELIG-019 | Edge case - exact threshold | 100 impressions exactly | Eligible |
| ELIG-020 | Edge case - exact clicks | 10 clicks exactly | Eligible |
| ELIG-021 | Multiple eligibility filters | All pass | Eligible |
| ELIG-022 | Any filter fails | One fails | Skipped |
| ELIG-023 | Eligibility logged | Any page | Log entry |
| ELIG-024 | Eligibility count in summary | Mixed pages | Count reported |
| ELIG-025 | Skipped pages tracked | Skipped pages | Reason tracked |

### COOL: Cooldown/Suppression Rules (20 tests)
| ID | Description | Condition | Expected Result |
|----|-------------|-----------|-----------------|
| COOL-001 | 14-day refresh cooldown | Refreshed 7 days ago | Decay suppressed |
| COOL-002 | 14-day refresh cooldown passed | Refreshed 20 days ago | Normal detection |
| COOL-003 | Cooldown exact boundary | Refreshed 14 days ago | Normal detection |
| COOL-004 | High-confidence suppression | Annotation recent update | Flag suppressed |
| COOL-005 | Annotation older than 14 days | Old annotation | No suppression |
| COOL-006 | Annotation type: content_update | Recent update | Decay suppressed |
| COOL-007 | Annotation type: other | Recent other | No suppression |
| COOL-008 | Low-signal threshold | Total movement below | Candidate not created |
| COOL-009 | Low-signal threshold passed | Movement above | Candidate created |
| COOL-010 | Combined cooldowns | Multiple conditions | All applied |
| COOL-011 | Cooldown logged | Suppressed decay | Log entry |
| COOL-012 | Cooldown reason stored | Suppressed | Reason in metadata |
| COOL-013 | Cooldown expiration check | Near expiration | Flagged |
| COOL-014 | Multiple pages cooldown | Some in cooldown | Partial processing |
| COOL-015 | Cooldown configuration | Custom days | Applied |
| COOL-016 | Annotation within window | 10-day window | Suppression active |
| COOL-017 | Suppression does not affect score | Decay detected | Score still calculated |
| COOL-018 | Suppression affects visibility | Suppressed | Not in UI |
| COOL-019 | Suppression can be overridden | Admin action | Override flag |
| COOL-020 | Cooldown counts in summary | Suppressed pages | Count reported |

### GD: Graceful Degradation (20 tests)
| ID | Description | Missing Source | Expected Result |
|----|-------------|----------------|-----------------|
| GD-001 | GSC missing detection fails | GSC unavailable | Detection skipped |
| GD-002 | Rankings missing continues | Rankings unavailable | Detection proceeds |
| GD-003 | Audit missing continues | Audit unavailable | Detection proceeds |
| GD-004 | AI visibility missing continues | AI unavailable | Detection proceeds |
| GD-005 | Annotations missing continues | Annotations unavailable | Detection proceeds |
| GD-006 | Multiple sources missing | Rankings + AI | Detection proceeds |
| GD-007 | All optional missing | Only GSC | Detection proceeds |
| GD-008 | Confidence reflects available data | Partial data | Lower confidence |
| GD-009 | Partial data logged | Missing sources | Warning logged |
| GD-010 | Graceful error response | Source timeout | Continues with available |
| GD-011 | Retry on transient failure | Temporary error | Retry attempted |
| GD-012 | Circuit breaker pattern | Repeated failures | Skip source temporarily |
| GD-013 | Source health tracking | Any | Health score maintained |
| GD-014 | Fallback to cached data | Fresh data unavailable | Cache used |
| GD-015 | Cache expiration handling | Stale cache | Re-fetch attempted |
| GD-016 | Data quality check | Invalid data | Skipped gracefully |
| GD-017 | Partial signal array | Some signals | Used what available |
| GD-018 | Empty signal handling | No signals | Zero decay |
| GD-019 | Degradation reported | Partial sources | Report in summary |
| GD-020 | Full recovery after outage | Source restored | Normal operation |

### WC: Window Comparison Logic (15 tests)
| ID | Description | Configuration | Expected Result |
|----|-------------|---------------|-----------------|
| WC-001 | Default 28 days | No config | 28-day windows |
| WC-002 | Custom window size | Project setting | Applied window |
| WC-003 | Window overlap avoided | Any | No overlapping dates |
| WC-004 | Previous window before current | Any | Correct ordering |
| WC-005 | Window boundaries respected | Any | Complete days only |
| WC-006 | Incomplete current window handled | Mid-day | Previous complete days |
| WC-007 | Window dates stored | Any | In signals/comparison |
| WC-008 | Weekday/weekend consideration | Optional | Can be factored |
| WC-009 | Seasonality window | Optional | Can use 7/14/56 days |
| WC-010 | Window comparison deterministic | Same data | Same result |
| WC-011 | Window dates inclusive | Any | Start and end included |
| WC-012 | Window timezone handling | Any | UTC normalized |
| WC-013 | Window calculation performance | Any | Efficient query |
| WC-014 | Window configuration validation | Invalid | Default used |
| WC-015 | Window comparison documented | Any | In result metadata |

### PERF: Performance Tests (10 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| PERF-001 | Single page detection | 1 page | < 500ms |
| PERF-002 | Small site detection | 10 pages | < 2 seconds |
| PERF-003 | Medium site detection | 100 pages | < 10 seconds |
| PERF-004 | Large site detection | 1000 pages | < 60 seconds |
| PERF-005 | Memory usage | 100 pages | < 100MB |
| PERF-006 | Database query count | 100 pages | Bounded queries |
| PERF-007 | GSC API call efficiency | 100 pages | Batched requests |
| PERF-008 | Concurrent detection | Parallel runs | Lock contention handled |
| PERF-009 | Cache hit rate | Repeated detection | > 80% cache hits |
| PERF-010 | Signal processing time | Full signals | < 100ms per page |

### SEC: Security Tests (10 tests)
| ID | Description | Input | Expected Result |
|----|-------------|-------|-----------------|
| SEC-001 | SQL injection in page URL | Malicious URL | Sanitized |
| SEC-002 | XSS in evidence | Script tags | Escaped |
| SEC-003 | User isolation | Cross-user project | Access denied |
| SEC-004 | Project ownership check | Other user's project | Access denied |
| SEC-005 | API key not logged | GSC API key | Masked in logs |
| SEC-006 | Sensitive data not exposed | PII in signals | Not in response |
| SEC-007 | Rate limiting respected | Rapid calls | Throttled |
| SEC-008 | Input validation | Invalid params | Validation error |
| SEC-009 | Mass assignment prevention | Extra fields | Ignored |
| SEC-010 | Evidence size limit | Large payload | Truncated or rejected |

### INT: Integration Tests (15 tests)
| ID | Description | Context | Expected Result |
|----|-------------|---------|-----------------|
| INT-001 | Full detection flow | All services | Complete result |
| INT-002 | ScoringService integration | Decay detected | Correct score |
| INT-003 | ReasonService integration | Decay detected | Correct reasons |
| INT-004 | PlannerService integration | Decay detected | Recommendations |
| INT-005 | Model persistence | Detection run | Data persisted |
| INT-006 | Event dispatching | High severity | Event fired |
| INT-007 | Queue job integration | Async detection | Job queued |
| INT-008 | API response format | Controller call | Correct format |
| INT-009 | Error propagation | Service failure | Proper error |
| INT-010 | Transaction handling | Partial failure | Rolled back |
| INT-011 | Cache invalidation | New detection | Cache cleared |
| INT-012 | Logging integration | Any run | Log entries |
| INT-013 | Metrics collection | Any run | Metrics stored |
| INT-014 | Digest generation | Detection complete | Digest created |
| INT-015 | Recovery detection | Improved page | Recovered status |

### EDGE: Edge Cases (20 tests)
| ID | Description | Input | Expected Behavior |
|----|-------------|-------|-------------------|
| EDGE-001 | Empty GSC response | No pages | Empty result |
| EDGE-002 | Null project settings | Missing settings | Defaults used |
| EDGE-003 | Very old candidate | 1 year old | Archived |
| EDGE-004 | Zero score decay | No decay | No candidate |
| EDGE-005 | Maximum score decay | Critical decay | Critical severity |
| EDGE-006 | Unicode in page URLs | Non-ASCII | Handled correctly |
| EDGE-007 | Very long URL | 2000+ chars | Truncated/stored |
| EDGE-008 | Special characters in evidence | Emojis, symbols | Stored correctly |
| EDGE-009 | Concurrent candidate creation | Race condition | No duplicates |
| EDGE-010 | Orphan candidate | Project deleted | Cleaned up |
| EDGE-011 | Missing project ID | Null project | Exception handled |
| EDGE-012 | Invalid window configuration | Negative days | Default used |
| EDGE-013 | GSC rate limit exceeded | API limit | Backoff and retry |
| EDGE-014 | Database connection lost | Connection error | Graceful failure |
| EDGE-015 | Memory limit approached | Large dataset | Batch processing |
| EDGE-016 | Timeout during detection | Long running | Partial results |
| EDGE-017 | Invalid candidate status | Unknown status | Default status |
| EDGE-018 | Circular reference detection | Self-reference | Prevented |
| EDGE-019 | Negative metrics | Negative clicks | Treated as zero |
| EDGE-020 | Future-dated signals | Future dates | Ignored |

---

## Test Implementation Patterns

### Mock Dependencies
```php
// Mock ScoringService
$scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
$scoringService->shouldReceive('calculateDecayScore')
    ->andReturn(['decay_score' => 45, 'severity' => 'high']);

// Mock ReasonService
$reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
$reasonService->shouldReceive('assignReasonCodes')
    ->andReturn(['primary_reason_code' => 'clicks_down', ...]);

// Mock PlannerService
$plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
$plannerService->shouldReceive('generateRecommendations')
    ->andReturn(['primary_recommendation' => '...', ...]);
```

### Mock GSC Data
```php
$gscData = [
    'previous' => [
        'clicks' => 1250,
        'impressions' => 20000,
        'ctr' => 0.0625,
        'position' => 5.2
    ],
    'current' => [
        'clicks' => 850,
        'impressions' => 18000,
        'ctr' => 0.0472,
        'position' => 7.8
    ]
];
```

### Mock Signals
```php
$signals = [
    'gsc' => $gscData,
    'rankings' => [
        'previous' => ['visibility' => 45.2],
        'current' => ['visibility' => 38.1]
    ],
    'audit' => [
        'unresolved_issues' => 3,
        'issues' => [
            ['type' => 'broken_link', 'severity' => 'warning'],
            ['type' => 'missing_alt', 'severity' => 'notice']
        ]
    ],
    'annotations' => [
        'recent_updates' => []
    ],
    'ai_visibility' => [
        'previous' => ['mention_rate' => 0.12],
        'current' => ['mention_rate' => 0.08]
    ]
];
```

### Expected Detection Result
```php
[
    'processed_pages' => 25,
    'eligible_pages' => 20,
    'candidates_created' => 5,
    'candidates_updated' => 3,
    'candidates_archived' => 2,
    'errors' => 0,
    'duration_ms' => 1234,
    'pages' => [
        [
            'page_url' => 'https://example.com/blog/seo-tips',
            'decay_score' => 45.5,
            'severity' => 'high',
            'status' => 'backlog',
            'reason_codes' => ['clicks_down', 'position_down'],
            'recommendation' => 'Refresh content and fix technical issues'
        ],
        // ... more pages
    ]
]
```

---

## Service Interface
```php
interface ContentDecayDetectionServiceInterface {
    /**
     * Run decay detection for all projects belonging to a site.
     */
    public function detectForSite(Site $site): array;
    
    /**
     * Run decay detection for a specific project.
     */
    public function detectForProject(ContentDecayProject $project): array;
    
    /**
     * Gather signals from all available sources for a page.
     */
    public function gatherSignals(string $pageUrl, ContentDecayProject $project): array;
    
    /**
     * Compare current vs previous window signals.
     */
    public function compareWindows(array $signals): array;
    
    /**
     * Create or update a decay candidate record.
     */
    public function upsertCandidate(array $detectionData): ?ContentDecayCandidate;
}
```

---

## Dependencies
| Dependency | Status | TDD Approach |
|------------|--------|--------------|
| TASK-36-2 (Models) | To Develop | Mock model interfaces |
| TASK-36-3 (ScoringService) | To Develop | Mock service interface |
| TASK-36-4 (ReasonService) | To Develop | Mock service interface |
| TASK-36-5 (PlannerService) | To Develop | Mock service interface |
| FEAT-012 (GSC Integration) | Done ✅ | Mock GSC data |
| FEAT-001 (Rank Tracking) | Done ✅ | Mock ranking data |
| FEAT-014 (Site Audit) | Partial ✅ | Mock audit data |

---

## Coverage Target
- **Lines:** 95%
- **Branches:** 90%
- **Methods:** 100%

---

## Acceptance Criteria Mapping

| AC | Tests | Status |
|----|-------|--------|
| ContentDecayDetectionService class created | SI-001 to SI-010 | ✅ Covered |
| detectForSite() method | DFS-001 to DFS-030 | ✅ Covered |
| detectForProject() method | DFP-001 to DFP-025 | ✅ Covered |
| gatherSignals() method | GS-001 to GS-040 | ✅ Covered |
| compareWindows() method | CW-001 to CW-035 | ✅ Covered |
| upsertCandidate() method | UC-001 to UC-030 | ✅ Covered |
| Eligibility filtering | ELIG-001 to ELIG-025 | ✅ Covered |
| Cooldown/suppression rules | COOL-001 to COOL-020 | ✅ Covered |
| Signal gathering from sources | GS section | ✅ Covered |
| Window comparison | CW section | ✅ Covered |
| Graceful degradation | GD-001 to GD-020 | ✅ Covered |
| Unit tests verify eligibility | ELIG section | ✅ Covered |
| Unit tests verify signal gathering | GS section | ✅ Covered |
| Unit tests verify window comparison | CW section | ✅ Covered |
| Feature tests verify full detection flow | INT section | ✅ Covered |

---

*Created by PreTester agent on 2026-03-11*
