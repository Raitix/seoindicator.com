# TASK-36-4: ContentDecayReasonService

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Low`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the reason code assignment service that converts numeric deltas into human-readable reason codes and builds evidence payloads for UI display.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Write Tests

## Acceptance Criteria
- [x] `ContentDecayReasonService` class created with:
  - `assignReasonCodes(array $signals): array` method returning:
    - `primary_reason_code` (string)
    - `reason_codes` (array of all applicable codes)
    - `evidence` (structured evidence for UI)
- [x] Reason codes supported:
  - `clicks_down` - Click decline exceeds threshold
  - `impressions_down` - Impression decline exceeds threshold
  - `ctr_down` - CTR decline while impressions remain meaningful
  - `avg_position_down` - GSC avg position worsened materially
  - `tracked_rankings_down` - Tracked keyword visibility worsened
  - `audit_issues_present` - Open page-level audit issues exist
  - `stale_content` - Last update exceeds freshness threshold
  - `annotation_recently_changed` - Recent annotation explains volatility
  - `ai_visibility_down` - AI visibility metric declined
  - `competitor_gain_suspected` - Position loss with stable demand
  - `internal_link_weakness` - Audit/internal-link data indicates weak support
- [x] Primary reason selection logic:
  - Select the most impactful negative change
  - Prioritize actionable reasons over informational ones
- [x] Evidence payload structure:
  - `metric` (which metric changed)
  - `previous_value` (before)
  - `current_value` (after)
  - `change_percent` (relative change)
  - `change_absolute` (absolute change)
  - `context` (additional context like page type)
- [x] Threshold configuration:
  - Click loss threshold (default: 10%)
  - Impression loss threshold (default: 15%)
  - CTR drop threshold (default: 0.5 percentage points)
  - Position decline threshold (default: 3 positions)
  - Staleness threshold (default: 180 days)
- [x] Unit tests verify all reason code triggers
- [x] Unit tests verify primary reason selection

## Effort Estimate
**3 hours** - Reason code logic and evidence building

## Technical Notes
### Reason Code Priority
1. `audit_issues_present` - Technical blockers should be fixed first
2. `clicks_down` - Direct traffic loss
3. `ctr_down` - SERP presentation issue
4. `avg_position_down` - Ranking issue
5. `tracked_rankings_down` - Keyword-specific issue
6. `impressions_down` - Visibility issue
7. `stale_content` - Freshness issue
8. `ai_visibility_down` - GEO issue
9. `competitor_gain_suspected` - Competitive pressure
10. `internal_link_weakness` - Site structure issue

### Evidence Context
```php
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
        'context' => 'Blog post about SEO tips'
    ]
]
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentDecayReasonService.php`
- `backend/tests/Unit/Services/ContentDecay/ContentDecayReasonServiceTest.php`

---

## Comments

### PreTester (2026-03-11)
**Test Design Complete**

Created comprehensive test suite with 112 test scenarios in `ContentDecayReasonServiceTest.php`.

**Test Categories:**
| Category | Tests | Coverage |
|----------|-------|----------|
| INST (Service Instantiation) | 5 | Container binding, thresholds |
| CLICKS (Clicks Down) | 8 | Threshold triggers, edge cases |
| IMP (Impressions Down) | 8 | Threshold triggers, evidence |
| CTR (CTR Down) | 10 | Meaningful impressions check |
| POS (Position Down) | 8 | Null handling, page drops |
| RANK (Rankings Down) | 6 | Visibility score changes |
| AUDIT (Audit Issues) | 5 | Critical issue prioritization |
| STALE (Stale Content) | 6 | Age thresholds |
| ANN (Annotation Changed) | 5 | Time window checks |
| AI (AI Visibility) | 5 | GEO metric changes |
| COMP (Competitor Gain) | 6 | Position + stable demand |
| LINK (Link Weakness) | 5 | Internal link thresholds |
| PRIMARY (Primary Selection) | 12 | Priority ordering |
| EVID (Evidence Payload) | 10 | Structure, calculations |
| THRESH (Configuration) | 8 | Defaults, custom, config |
| EDGE (Edge Cases) | 10 | Nulls, extremes, unicode |
| RET (Return Structure) | 5 | Response format |

**Key Test Patterns:**
- Tests use mock signal arrays (no Eloquent model dependency)
- Each reason code has dedicated threshold tests
- Primary selection tests verify priority ordering
- Evidence tests verify calculation accuracy (percent/absolute changes)

**Documentation:**
- `TESTS/TEST-36-4.md` updated with full scenario documentation

**Status:** `To Develop` → Ready for Developer implementation

### ProjectManager (2026-03-10)
**Prioritization Complete**

**Priority: HIGH** (confirmed - already set)
- Core service for Content Decay feature - converts raw metrics into actionable insights
- Enables UI to display meaningful reason codes (WHY content is decaying)
- Evidence payloads are critical for user understanding and decision-making
- 11 distinct reason codes with priority ordering for actionable recommendations
- Downstream: Used by Detection Service (TASK-36-6) and displayed in UI (TASK-36-11, 36-12)

**Risk: LOW** (confirmed - already set)
- Pure business logic service - no external APIs
- Standard Laravel service with configurable thresholds
- Well-defined interface: `assignReasonCodes(array $signals): array`
- Testable with mocked signal data structures

**Dependency Analysis:**
| Dependency | Status | Impact |
|------------|--------|--------|
| TASK-36-2 (Eloquent Models) | To Write Tests | TDD: Tests CAN be designed with mocked model interfaces |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately with mocked signal data
- The service operates on signal arrays, not directly on Eloquent models
- Reason code logic is pure computation - easily testable
- Thresholds are configurable via service constructor or config file

**Reason Code Architecture:**
| Code | Priority | Category | Actionability |
|------|----------|----------|---------------|
| audit_issues_present | 1 | Technical | HIGH - Fix issues directly |
| clicks_down | 2 | Traffic | HIGH - Direct impact |
| ctr_down | 3 | SERP | HIGH - Optimize meta/snippet |
| avg_position_down | 4 | Ranking | MEDIUM - SEO work needed |
| tracked_rankings_down | 5 | Keywords | MEDIUM - Content optimization |
| impressions_down | 6 | Visibility | MEDIUM - Promote/index issues |
| stale_content | 7 | Freshness | HIGH - Update content |
| ai_visibility_down | 8 | GEO | MEDIUM - Optimize for AI |
| competitor_gain_suspected | 9 | Competitive | LOW - External factor |
| internal_link_weakness | 10 | Structure | HIGH - Add internal links |

**Evidence Payload Structure:**
- Provides BEFORE/AFTER context for UI display
- Change percentages and absolute values for quantification
- Window comparison (e.g., "28 days vs 28 days")
- Context field for page-specific information

**Execution Order for SPEC-036:**
1. TASK-36-1 (Migrations) → Done
2. TASK-36-2 (Models) → To Write Tests
3. TASK-36-3 (Detection Service) → To Write Tests (parallel with this)
4. TASK-36-4 (Reason Service) → **This task** (parallel with TASK-36-3, 36-5)
5. TASK-36-5 (Scoring Service) → To Write Tests
6. TASK-36-6 (Main Detection Orchestrator) → To Prioritize

**Parallel Development:**
- TASK-36-3, 36-4, 36-5 can all proceed to PreTester simultaneously
- All three services have well-defined interfaces
- Tests designed with mocks, implementation can proceed in parallel

**Status:** `To Write Tests` → Ready for PreTester

### Developer (2026-03-11)
**Implementation Complete**

All 122 unit tests passing (168 assertions).

**Key Implementation Details:**

1. **Service Class**: `ContentDecayReasonService` implemented with:
   - `assignReasonCodes(array $signals): array` method
   - `getThresholds(): array` method for configuration access
   - Interface `ContentDecayReasonServiceInterface` with proper DI binding

2. **Reason Code Logic**:
   - All 11 reason codes implemented with proper threshold checks
   - Priority ordering per specification (audit_issues highest, internal_link_weakness lowest)
   - Mutual exclusivity: when `competitor_gain_suspected` triggers, `avg_position_down` is suppressed (more specific diagnosis)
   - Page boundary detection: `avg_position_down` triggers on SERP page boundary crossing (e.g., page 1 → page 2)

3. **Evidence Payload**:
   - Structured with `reason`, `primary`, and `evidence` fields
   - Evidence includes: metric, previous, current, change_percent, change_absolute, window, context
   - Primary evidence marked with `primary: true`

4. **Threshold Configuration**:
   - Defaults: click_loss=10%, impression_loss=15%, ctr_drop=0.5pp, position_decline=3, staleness_days=180
   - Configurable via constructor, config file, or environment variables
   - `internal_links` and `annotation_window_days` thresholds added

5. **Special Cases Handled**:
   - Null values gracefully handled (no crash, skip the check)
   - Zero previous values (improvement case, not decline)
   - Low impression counts for CTR relevance
   - Stable demand detection for competitor gain (clicks not provided = treat as stable)
   - Annotation boundary cases using `diffInDays` with floor for accurate day counting

**Files Modified:**
- `backend/app/Services/ContentDecay/ContentDecayReasonService.php` - Main service
- `backend/app/Services/ContentDecay/Contracts/ContentDecayReasonServiceInterface.php` - Interface
- `backend/app/Providers/AppServiceProvider.php` - DI binding added

**Status:** `To Develop` → `To Test Locally`

### LocalTester (2026-03-11 15:14 GMT+2)
**Local QA Complete - PASS**

All 122 unit tests passed (168 assertions) in 16.07s.

**Test Results:**
| Category | Result |
|----------|--------|
| ContentDecayReasonServiceTest | ✅ 122 passed |
| AnnotationCategoryTest (unrelated) | ❌ 3 failed (pre-existing: class not found) |

**Implementation Verified:**
- ✅ Service class: `backend/app/Services/ContentDecay/ContentDecayReasonService.php`
- ✅ Interface: `backend/app/Services/ContentDecay/Contracts/ContentDecayReasonServiceInterface.php`
- ✅ DI binding: `AppServiceProvider.php` lines 168-169

**Adversarial Testing Notes:**
- Null signal values handled gracefully (no crashes)
- Zero division protected (returns 0% change when previous is 0)
- Unicode page titles handled correctly
- Edge cases with very small/large numbers tested
- Concurrent signal conflicts resolved by priority ordering

**Decision:** PASS → Ready for DevOps deployment

**Status:** `To Test Locally` → `To Deploy`

### DevOps (2026-03-11 17:00 GMT+2)
**Deployment Complete - SUCCESS**

Deployed commit `dac05c79` to production.

**Deployment Details:**
| Step | Status | Duration |
|------|--------|----------|
| Fetch origin | ✅ | 2s |
| Build frontend | ✅ | 71s |
| Deploy to production | ✅ | 76s |
| Smoke tests | ✅ | 8s |

**Verification:**
- ✅ Homepage loaded successfully
- ✅ Login page loaded successfully
- ✅ Deployment log: `20260311T145731Z-8f86a19c.json`

**Note:** Had to install missing `satori` npm dependency before deployment (used for OG image generation).

**Status:** `To Deploy` → `To Test Prod`
