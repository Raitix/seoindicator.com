# TASK-36-4: ContentDecayReasonService

## STATUS
`To Prioritize`

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
- TASK-36-2 (Eloquent Models) - To Prioritize

## Acceptance Criteria
- [ ] `ContentDecayReasonService` class created with:
  - `assignReasonCodes(array $signals): array` method returning:
    - `primary_reason_code` (string)
    - `reason_codes` (array of all applicable codes)
    - `evidence` (structured evidence for UI)
- [ ] Reason codes supported:
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
- [ ] Primary reason selection logic:
  - Select the most impactful negative change
  - Prioritize actionable reasons over informational ones
- [ ] Evidence payload structure:
  - `metric` (which metric changed)
  - `previous_value` (before)
  - `current_value` (after)
  - `change_percent` (relative change)
  - `change_absolute` (absolute change)
  - `context` (additional context like page type)
- [ ] Threshold configuration:
  - Click loss threshold (default: 10%)
  - Impression loss threshold (default: 15%)
  - CTR drop threshold (default: 0.5 percentage points)
  - Position decline threshold (default: 3 positions)
  - Staleness threshold (default: 180 days)
- [ ] Unit tests verify all reason code triggers
- [ ] Unit tests verify primary reason selection

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

*Created by SpecSystemAnalyst agent on 2026-03-10*
