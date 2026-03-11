# TESTS/TEST-36-4.md

## Task Reference
- **Task:** TASK-36-4: ContentDecayReasonService
- **Specification:** SPEC-036: Content Decay Detection & Refresh Planner
- **Feature:** FEAT-036: Content Decay Detection & Refresh Planner
- **Status:** To Develop

---

## Overview

This document describes the test coverage for `ContentDecayReasonService`, the service responsible for converting numeric signal deltas into human-readable reason codes and building evidence payloads for UI display.

---

## Test Files

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| `ContentDecayReasonServiceTest.php` | 112 | 200+ | Ready |

---

## Test Scenarios

### 1. Service Instantiation (INST) - 5 tests

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| INST-1 | Service can be instantiated via app container | Instance created |
| INST-2 | Service can be instantiated with default thresholds | Default config values loaded |
| INST-3 | Service can be instantiated with custom thresholds | Custom thresholds applied |
| INST-4 | Service interface is properly bound | Interface resolves to implementation |
| INST-5 | Threshold config values are accessible | getThresholds() returns array |

### 2. Reason Code Assignment - Clicks Down (CLICKS) - 8 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CLICKS-1 | Click decline exceeds threshold (15%) | prev=100, curr=80 (-20%) | `clicks_down` in codes |
| CLICKS-2 | Click decline at exact threshold (10%) | prev=100, curr=90 (-10%) | `clicks_down` in codes |
| CLICKS-3 | Click decline below threshold (5%) | prev=100, curr=95 (-5%) | `clicks_down` NOT in codes |
| CLICKS-4 | Click increase (positive change) | prev=100, curr=120 (+20%) | `clicks_down` NOT in codes |
| CLICKS-5 | Zero clicks both windows | prev=0, curr=0 | `clicks_down` NOT in codes |
| CLICKS-6 | Clicks drop to zero | prev=100, curr=0 (-100%) | `clicks_down` in codes |
| CLICKS-7 | Clicks start from zero | prev=0, curr=50 | `clicks_down` NOT in codes |
| CLICKS-8 | Custom threshold applied | threshold=20%, prev=100, curr=85 | `clicks_down` NOT in codes |

### 3. Reason Code Assignment - Impressions Down (IMP) - 8 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IMP-1 | Impression decline exceeds threshold (20%) | prev=1000, curr=700 (-30%) | `impressions_down` in codes |
| IMP-2 | Impression decline at threshold (15%) | prev=1000, curr=850 (-15%) | `impressions_down` in codes |
| IMP-3 | Impression decline below threshold | prev=1000, curr=950 (-5%) | `impressions_down` NOT in codes |
| IMP-4 | Impression increase | prev=1000, curr=1200 (+20%) | `impressions_down` NOT in codes |
| IMP-5 | Zero impressions both windows | prev=0, curr=0 | `impressions_down` NOT in codes |
| IMP-6 | Impressions drop to zero | prev=1000, curr=0 | `impressions_down` in codes |
| IMP-7 | Custom threshold applied | threshold=25%, prev=1000, curr=800 | `impressions_down` NOT in codes |
| IMP-8 | High impression loss with stable clicks | Both metrics present | `impressions_down` + evidence |

### 4. Reason Code Assignment - CTR Down (CTR) - 10 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CTR-1 | CTR decline exceeds threshold (1pp) | prev=5.0%, curr=3.5% | `ctr_down` in codes |
| CTR-2 | CTR decline at threshold (0.5pp) | prev=5.0%, curr=4.5% | `ctr_down` in codes |
| CTR-3 | CTR decline below threshold | prev=5.0%, curr=4.8% | `ctr_down` NOT in codes |
| CTR-4 | CTR increase | prev=5.0%, curr=6.0% | `ctr_down` NOT in codes |
| CTR-5 | CTR drop with low impressions | prev=5.0%, curr=3.0%, imps=50 | `ctr_down` NOT in codes (meaningful check) |
| CTR-6 | CTR drop with high impressions | prev=5.0%, curr=3.0%, imps=1000 | `ctr_down` in codes |
| CTR-7 | Zero CTR both windows | prev=0%, curr=0% | `ctr_down` NOT in codes |
| CTR-8 | CTR from zero to positive | prev=0%, curr=2.0% | `ctr_down` NOT in codes |
| CTR-9 | CTR to zero from positive | prev=5.0%, curr=0% | `ctr_down` in codes |
| CTR-10 | Custom CTR threshold | threshold=1.0pp, prev=5.0%, curr=4.6% | `ctr_down` NOT in codes |

### 5. Reason Code Assignment - Average Position Down (POS) - 8 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| POS-1 | Position worsened beyond threshold (5 positions) | prev=3.0, curr=8.0 | `avg_position_down` in codes |
| POS-2 | Position worsened at threshold (3 positions) | prev=3.0, curr=6.0 | `avg_position_down` in codes |
| POS-3 | Position worsened below threshold | prev=3.0, curr=4.0 | `avg_position_down` NOT in codes |
| POS-4 | Position improved | prev=5.0, curr=2.0 | `avg_position_down` NOT in codes |
| POS-5 | Position unchanged | prev=3.0, curr=3.0 | `avg_position_down` NOT in codes |
| POS-6 | Position null in previous | prev=null, curr=5.0 | `avg_position_down` NOT in codes |
| POS-7 | Position null in current | prev=5.0, curr=null | `avg_position_down` NOT in codes |
| POS-8 | Position drop from page 1 to page 2 | prev=9.5, curr=12.0 | `avg_position_down` in codes |

### 6. Reason Code Assignment - Tracked Rankings Down (RANK) - 6 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RANK-1 | Visibility score declined | prev=85.0, curr=60.0 | `tracked_rankings_down` in codes |
| RANK-2 | Visibility score improved | prev=60.0, curr=85.0 | `tracked_rankings_down` NOT in codes |
| RANK-3 | Visibility score unchanged | prev=75.0, curr=75.0 | `tracked_rankings_down` NOT in codes |
| RANK-4 | Visibility null | prev=null, curr=null | `tracked_rankings_down` NOT in codes |
| RANK-5 | Multiple keywords declined | avg decline > threshold | `tracked_rankings_down` in codes |
| RANK-6 | Mixed keyword performance | some up, some down | Based on net visibility |

### 7. Reason Code Assignment - Audit Issues Present (AUDIT) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AUDIT-1 | Audit issues exist | unresolved_issues=5 | `audit_issues_present` in codes |
| AUDIT-2 | No audit issues | unresolved_issues=0 | `audit_issues_present` NOT in codes |
| AUDIT-3 | Audit issues null | unresolved_issues=null | `audit_issues_present` NOT in codes |
| AUDIT-4 | Critical audit issues | unresolved_issues=10, severity=critical | `audit_issues_present` primary |
| AUDIT-5 | Multiple issue categories | issues=3, warnings=7 | `audit_issues_present` in codes |

### 8. Reason Code Assignment - Stale Content (STALE) - 6 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| STALE-1 | Content older than threshold | age=365 days, threshold=180 | `stale_content` in codes |
| STALE-2 | Content at threshold | age=180 days | `stale_content` in codes |
| STALE-3 | Content below threshold | age=90 days | `stale_content` NOT in codes |
| STALE-4 | Content age null | age=null | `stale_content` NOT in codes |
| STALE-5 | Recently updated content | age=7 days | `stale_content` NOT in codes |
| STALE-6 | Custom staleness threshold | threshold=365, age=200 | `stale_content` NOT in codes |

### 9. Reason Code Assignment - Annotation Recently Changed (ANN) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ANN-1 | Recent annotation within window | annotation_at=7 days ago | `annotation_recently_changed` in codes |
| ANN-2 | Annotation outside window | annotation_at=60 days ago | `annotation_recently_changed` NOT in codes |
| ANN-3 | No annotation | annotation_at=null | `annotation_recently_changed` NOT in codes |
| ANN-4 | Annotation at exact window boundary | annotation_at=30 days | `annotation_recently_changed` in codes |
| ANN-5 | Multiple recent annotations | count=3, last=5 days ago | `annotation_recently_changed` in codes |

### 10. Reason Code Assignment - AI Visibility Down (AI) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AI-1 | AI visibility declined | prev=40.0, curr=20.0 | `ai_visibility_down` in codes |
| AI-2 | AI visibility improved | prev=20.0, curr=40.0 | `ai_visibility_down` NOT in codes |
| AI-3 | AI visibility unchanged | prev=30.0, curr=30.0 | `ai_visibility_down` NOT in codes |
| AI-4 | AI visibility null | prev=null, curr=null | `ai_visibility_down` NOT in codes |
| AI-5 | AI visibility zero | prev=0.0, curr=0.0 | `ai_visibility_down` NOT in codes |

### 11. Reason Code Assignment - Competitor Gain Suspected (COMP) - 6 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| COMP-1 | Position loss with stable impressions | pos_down, impressions_stable | `competitor_gain_suspected` in codes |
| COMP-2 | Position loss with impression decline | pos_down, impressions_down | `competitor_gain_suspected` NOT in codes |
| COMP-3 | Position loss with click decline | pos_down, clicks_down | `competitor_gain_suspected` NOT in codes |
| COMP-4 | Position improved | pos_up | `competitor_gain_suspected` NOT in codes |
| COMP-5 | Stable position | pos_stable | `competitor_gain_suspected` NOT in codes |
| COMP-6 | Multiple competitor gains detected | competitor_data present | `competitor_gain_suspected` in codes |

### 12. Reason Code Assignment - Internal Link Weakness (LINK) - 5 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LINK-1 | Low internal link count | internal_links=2 | `internal_link_weakness` in codes |
| LINK-2 | Adequate internal links | internal_links=10 | `internal_link_weakness` NOT in codes |
| LINK-3 | Internal links null | internal_links=null | `internal_link_weakness` NOT in codes |
| LINK-4 | Zero internal links | internal_links=0 | `internal_link_weakness` in codes |
| LINK-5 | Link threshold configurable | threshold=5, internal_links=3 | `internal_link_weakness` in codes |

### 13. Primary Reason Selection (PRIMARY) - 12 tests

| ID | Scenario | Input | Expected Primary |
|----|----------|-------|------------------|
| PRIMARY-1 | Audit issues + clicks down | audit_issues, clicks_down | `audit_issues_present` |
| PRIMARY-2 | Clicks down + impressions down | clicks_down, impressions_down | `clicks_down` |
| PRIMARY-3 | CTR down + position down | ctr_down, avg_position_down | `ctr_down` |
| PRIMARY-4 | Position down + rankings down | avg_position_down, tracked_rankings_down | `avg_position_down` |
| PRIMARY-5 | Rankings down + impressions down | tracked_rankings_down, impressions_down | `tracked_rankings_down` |
| PRIMARY-6 | Impressions down + stale content | impressions_down, stale_content | `impressions_down` |
| PRIMARY-7 | Stale content + AI visibility down | stale_content, ai_visibility_down | `stale_content` |
| PRIMARY-8 | AI visibility + competitor gain | ai_visibility_down, competitor_gain_suspected | `ai_visibility_down` |
| PRIMARY-9 | Competitor gain + link weakness | competitor_gain_suspected, internal_link_weakness | `competitor_gain_suspected` |
| PRIMARY-10 | All reason codes present | all 11 codes | `audit_issues_present` |
| PRIMARY-11 | Single reason code | only clicks_down | `clicks_down` |
| PRIMARY-12 | No reason codes | no signals exceed threshold | null or empty |

### 14. Evidence Payload Structure (EVID) - 10 tests

| ID | Scenario | Expected Evidence Fields |
|----|----------|-------------------------|
| EVID-1 | Clicks down evidence | metric, previous, current, change_percent, change_absolute, window, context |
| EVID-2 | Percent change calculation | prev=100, curr=80 → change_percent=-20.0 |
| EVID-3 | Absolute change calculation | prev=100, curr=80 → change_absolute=-20 |
| EVID-4 | Window formatting | previous_start/end, current_start/end → "28 days vs 28 days" |
| EVID-5 | Context field population | page_title, page_type included |
| EVID-6 | All reason codes have evidence | Each code maps to evidence object |
| EVID-7 | Primary evidence marked | primary=true on selected reason |
| EVID-8 | Multiple evidence entries | Array of evidence objects |
| EVID-9 | Null value handling | null values excluded from evidence |
| EVID-10 | Decimal precision | change_percent: 2 decimal places |

### 15. Threshold Configuration (THRESH) - 8 tests

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| THRESH-1 | Default click threshold | 10% (0.10) |
| THRESH-2 | Default impression threshold | 15% (0.15) |
| THRESH-3 | Default CTR threshold | 0.5 percentage points |
| THRESH-4 | Default position threshold | 3 positions |
| THRESH-5 | Default staleness threshold | 180 days |
| THRESH-6 | Custom thresholds via constructor | Overrides applied |
| THRESH-7 | Config file thresholds | Loaded from config/content-decay.php |
| THRESH-8 | Environment variable thresholds | Loaded from env() |

### 16. Edge Cases (EDGE) - 10 tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EDGE-1 | Empty signals array | [] | Empty codes, null primary |
| EDGE-2 | All zero values | all metrics = 0 | No decline codes |
| EDGE-3 | All null signals | all metrics = null | No codes assigned |
| EDGE-4 | Extreme decline (99%) | prev=10000, curr=100 | All applicable codes |
| EDGE-5 | Very small numbers | prev=0.001, curr=0.0005 | Handled without error |
| EDGE-6 | Floating point precision | prev=3.333, curr=2.222 | Correct calculation |
| EDGE-7 | Unicode page titles | title="日本語タイトル" | Context preserved |
| EDGE-8 | Long page URLs | url=2048 chars | Truncated in evidence |
| EDGE-9 | Concurrent signal conflicts | Multiple strong signals | Priority-based selection |
| EDGE-10 | Signal data type variations | string/int/float mix | Normalized correctly |

### 17. Return Value Structure (RET) - 5 tests

| ID | Scenario | Expected Structure |
|----|----------|-------------------|
| RET-1 | Full response structure | primary_reason_code, reason_codes, evidence |
| RET-2 | Primary reason code type | string |
| RET-3 | Reason codes type | array of strings |
| RET-4 | Evidence type | array of objects |
| RET-5 | Empty result | primary_reason_code=null, reason_codes=[], evidence=[] |

---

## Mock Signal Data Patterns

### Standard Signal Input
```php
$signals = [
    'page_url' => 'https://example.com/blog/seo-tips',
    'page_title' => '10 SEO Tips for 2024',
    'page_type' => 'blog',
    'window' => [
        'previous_start' => '2026-02-10',
        'previous_end' => '2026-03-09',
        'current_start' => '2026-03-10',
        'current_end' => '2026-04-08',
    ],
    'clicks' => [
        'previous' => 1250,
        'current' => 850,
    ],
    'impressions' => [
        'previous' => 25000,
        'current' => 20000,
    ],
    'ctr' => [
        'previous' => 5.0,
        'current' => 4.25,
    ],
    'avg_position' => [
        'previous' => 3.5,
        'current' => 6.2,
    ],
    'tracked_rank_visibility' => [
        'previous' => 85.0,
        'current' => 60.0,
    ],
    'ai_visibility' => [
        'previous' => 35.0,
        'current' => 20.0,
    ],
    'unresolved_audit_issues' => 3,
    'content_age_days' => 365,
    'last_annotation_at' => '2026-03-01T12:00:00Z',
    'internal_links' => 2,
    'competitor_data' => [
        ['name' => 'Competitor A', 'position_change' => -3],
    ],
];
```

### Minimal Signal Input (GSC only)
```php
$minimalSignals = [
    'page_url' => 'https://example.com/page',
    'page_title' => 'Test Page',
    'page_type' => 'other',
    'window' => [...],
    'clicks' => ['previous' => 100, 'current' => 80],
    'impressions' => ['previous' => 1000, 'current' => 800],
    'ctr' => ['previous' => 10.0, 'current' => 10.0],
    'avg_position' => ['previous' => 5.0, 'current' => 5.0],
];
```

---

## Reason Code Priority Order

| Priority | Code | Category | Actionability |
|----------|------|----------|---------------|
| 1 | `audit_issues_present` | Technical | HIGH |
| 2 | `clicks_down` | Traffic | HIGH |
| 3 | `ctr_down` | SERP | HIGH |
| 4 | `avg_position_down` | Ranking | MEDIUM |
| 5 | `tracked_rankings_down` | Keywords | MEDIUM |
| 6 | `impressions_down` | Visibility | MEDIUM |
| 7 | `stale_content` | Freshness | HIGH |
| 8 | `ai_visibility_down` | GEO | MEDIUM |
| 9 | `competitor_gain_suspected` | Competitive | LOW |
| 10 | `internal_link_weakness` | Structure | HIGH |

---

## Test Execution Commands

```bash
# Run all ContentDecayReasonService tests
php artisan test tests/Unit/Services/ContentDecay/ContentDecayReasonServiceTest.php

# Run with verbose output
php artisan test tests/Unit/Services/ContentDecay/ContentDecayReasonServiceTest.php -v

# Run specific test category
php artisan test tests/Unit/Services/ContentDecay/ContentDecayReasonServiceTest.php --filter "testPrimaryReason"
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-36-2 (Eloquent Models) | To Write Tests | Tests use mock arrays, not models |

---

## Notes for Developer

1. **Service Interface:**
```php
interface ContentDecayReasonServiceInterface
{
    public function assignReasonCodes(array $signals): array;
    public function getThresholds(): array;
}
```

2. **Return Structure:**
```php
[
    'primary_reason_code' => 'clicks_down',
    'reason_codes' => ['clicks_down', 'avg_position_down', 'stale_content'],
    'evidence' => [
        [
            'reason' => 'clicks_down',
            'primary' => true,
            'evidence' => [
                'metric' => 'clicks',
                'previous' => 1250,
                'current' => 850,
                'change_percent' => -32.0,
                'change_absolute' => -400,
                'window' => '28 days vs 28 days',
                'context' => 'Blog post: 10 SEO Tips for 2024',
            ],
        ],
        // ... additional evidence entries
    ],
]
```

3. **Config File Structure:**
```php
// config/content-decay.php
return [
    'thresholds' => [
        'click_loss' => env('CONTENT_DECAY_CLICK_THRESHOLD', 0.10),
        'impression_loss' => env('CONTENT_DECAY_IMPRESSION_THRESHOLD', 0.15),
        'ctr_drop' => env('CONTENT_DECAY_CTR_THRESHOLD', 0.5),
        'position_decline' => env('CONTENT_DECAY_POSITION_THRESHOLD', 3),
        'staleness_days' => env('CONTENT_DECAY_STALENESS_THRESHOLD', 180),
        'internal_links' => env('CONTENT_DECAY_LINK_THRESHOLD', 3),
        'annotation_window_days' => env('CONTENT_DECAY_ANNOTATION_WINDOW', 30),
    ],
];
```

4. **Priority Logic:**
- Always select the highest-priority reason code
- Audit issues take precedence over all other signals
- Traffic loss (clicks) prioritized over visibility loss (impressions)
- Actionable reasons prioritized over informational

---

## Comments

### PreTester (2026-03-11)
**Test Design Complete**

Created comprehensive test suite with 112 test scenarios covering:
- All 11 reason code triggers
- Primary reason selection priority logic
- Evidence payload structure and calculations
- Threshold configuration
- Edge cases and null handling

**Key Design Decisions:**
1. Tests use mock signal arrays instead of Eloquent models
2. Each reason code has dedicated test category
3. Primary selection tests verify priority ordering
4. Evidence tests verify calculation accuracy
5. Threshold tests verify both defaults and configuration

**Dependencies:**
- TASK-36-2 (Models) status `To Write Tests` does NOT block this task
- Tests are designed with TDD pattern - interfaces mocked
- Developer can implement service against this test specification

**Status:** Tests designed, ready for implementation
