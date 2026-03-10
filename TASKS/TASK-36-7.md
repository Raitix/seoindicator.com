# TASK-36-7: ContentDecayDigestService

## STATUS
`To Prioritize`

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

*Created by SpecSystemAnalyst agent on 2026-03-10*
