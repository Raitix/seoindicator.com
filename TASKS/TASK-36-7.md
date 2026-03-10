# TASK-36-7: ContentDecayDigestService

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the weekly digest service that summarizes newly detected decay candidates and recovered pages for notification delivery.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Prioritize
- TASK-36-6 (ContentDecayDetectionService) - To Prioritize
- FEAT-002 (Alerts/Notifications) - Done ✅

## Acceptance Criteria
- [ ] `ContentDecayDigestService` class created with:
  - `generateDigest(ContentDecayProject $project): array` method
  - `sendDigest(User $user, array $digest): void` method
  - `shouldSendDigest(ContentDecayProject $project): bool` method
- [ ] Digest content includes:
  - `new_decay_count` - Pages newly detected as decaying
  - `critical_count` - Pages with critical severity
  - `recovered_count` - Pages marked as recovered
  - `total_lost_clicks` - Estimated lost clicks across candidates
  - `total_upside` - Estimated recovery upside
  - `top_candidates` - Top 5 priority candidates by decay score
  - `recovered_pages` - Pages that recovered this week
- [ ] Digest filters:
  - Exclude ignored candidates
  - Exclude stale-noise candidates (below threshold for 2+ scans)
  - Only include candidates with meaningful scores
- [ ] Digest frequency:
  - Weekly (default)
  - Monthly (configurable)
- [ ] Integration with existing notification system (FEAT-002)
- [ ] Email template for digest notification
- [ ] Unit tests verify digest generation
- [ ] Feature tests verify notification delivery

## Effort Estimate
**3 hours** - Digest generation and notification

## Technical Notes
### Digest Email Template
```
Subject: Weekly Content Decay Report for {site_name}

Your Content Health Summary:
- {new_decay_count} pages need attention
- {critical_count} critical issues
- {recovered_count} pages recovered
- {total_lost_clicks} estimated clicks lost

Top Priority Pages:
1. {page_url} - Score: {decay_score} - {primary_reason}
2. ...

Recovered Pages:
- {page_url} - Recovered {recovered_clicks} clicks

[View Refresh Queue →]
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentDecayDigestService.php`
- `backend/resources/views/emails/content-decay-digest.blade.php`
- `backend/tests/Unit/Services/ContentDecay/ContentDecayDigestServiceTest.php`

---

## Comments

### ProjectManager (2026-03-10 23:35 GMT+2)
**Prioritization Complete**

**Priority: MEDIUM** (confirmed - already set)
- **Digest/Notification enhancement** for SPEC-036 Content Decay Detection & Refresh Planner
- This is NOT a foundation service - it's an enhancement that aggregates decay data into weekly/monthly notifications
- Users can use core decay detection without digests - this is a "nice-to-have" engagement feature
- Phase context: This is POST-DETECTION functionality - decay detection must work first
- Medium priority is appropriate for enhancement features with no downstream blockers

**Risk: LOW** (confirmed - already set)
- Standard Laravel service with email template generation
- No external API dependencies
- Uses existing notification infrastructure (FEAT-002)
- Pure business logic for aggregation and formatting
- Email delivery uses existing NotificationDispatcher

**Dependencies Analysis:**
| Dependency | Status | TDD Impact |
|------------|--------|------------|
| TASK-36-2 (Eloquent Models) | To Write Tests | Tests CAN be designed with mocked model interfaces |
| TASK-36-6 (DetectionService) | To Write Tests | Tests CAN be designed with mocked service interface |
| FEAT-002 (Alerts/Notifications) | Done ✅ | Real notification infrastructure available |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately with mocked model/service interfaces
- The service has clear interface: generateDigest(), sendDigest(), shouldSendDigest()
- Tests can mock ContentDecayProject and ContentDecayCandidate models
- Tests can mock ContentDecayDetectionService for candidate retrieval
- Tests can mock NotificationDispatcher for email delivery
- Digest filtering logic (exclude ignored, exclude stale-noise) is testable with mock data

**Digest Content Structure:**
| Field | Description | Source |
|-------|-------------|--------|
| `new_decay_count` | Pages newly detected as decaying | ContentDecayCandidate query |
| `critical_count` | Pages with critical severity | ContentDecayCandidate query |
| `recovered_count` | Pages marked as recovered | ContentDecayCandidate query |
| `total_lost_clicks` | Estimated lost clicks across candidates | Aggregation from signals |
| `total_upside` | Estimated recovery upside | Aggregation from signals |
| `top_candidates` | Top 5 priority candidates by decay score | ContentDecayCandidate query |
| `recovered_pages` | Pages that recovered this week | ContentDecayCandidate query |

**Digest Filtering Logic:**
1. Exclude ignored candidates (`status !== 'ignored'`)
2. Exclude stale-noise candidates (below threshold for 2+ scans)
3. Only include candidates with meaningful scores
4. Configurable frequency: weekly (default) or monthly

**Integration with FEAT-002:**
- Uses existing `NotificationDispatcher` for email delivery
- Extends `RankingAlertEmail` pattern for digest emails
- Can use existing quiet hours and digest mode infrastructure
- In-app notifications via existing `Notification` model

**Downstream Impact:**
- This is a **leaf task** - no other tasks depend on it
- Users can use core decay detection without digests
- Enhancement feature, not foundation infrastructure

**Execution Order for SPEC-036:**
1. TASK-36-1 (Migrations) → To Deploy
2. TASK-36-2 (Models) → To Write Tests
3. TASK-36-3/36-4/36-5 (Services) → To Write Tests (parallel)
4. TASK-36-6 (Detection Orchestrator) → To Write Tests
5. TASK-36-9 (Queue Jobs) → To Write Tests
6. TASK-36-10 (API Controllers) → To Develop
7. TASK-36-11/36-12/36-13 (Frontend) → To Develop (parallel)
8. **TASK-36-7 (Digest Service)** → This task (enhancement, leaf node)

**PreTester Queue Status:**
- Multiple tasks at "To Write Tests" - queue has capacity
- Service dependency tasks progressing in parallel

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
