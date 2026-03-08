# TASK-35-4: Local SEO Models

## STATUS
`To Prioritize`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent models for local SEO tracking: LocalRanking, LocalCompetitor, and LocalSEOAlert.

## Acceptance Criteria
- [ ] LocalRanking model created:
  - BelongsTo relationships: businessLocation(), keyword()
  - Casts for coordinates (decimal) and competitors_in_pack (JSON)
  - Scope for local pack positions (scopeInPack())
  - Scope for date range filtering
  - Method to get position change from previous check
  - Method to check if in local pack (position <= 3)
- [ ] LocalCompetitor model created:
  - BelongsTo relationship to BusinessLocation
  - Casts for rating (decimal) and review_count (integer)
  - Method to get Google Maps URL
  - Method to compare with location (rating difference, etc.)
- [ ] LocalSEOAlert model created:
  - BelongsTo relationships: user(), businessLocation()
  - Casts for data (JSON) and is_read (boolean)
  - Scope for unread alerts
  - Scope by severity (scopeCritical(), scopeWarning(), scopeInfo())
  - Scope by alert_type
  - Method to mark as read
  - Constants for alert types and severities
- [ ] Model factories for all models
- [ ] Unit tests for all models

## Dependencies
- TASK-35-1 (Database Migrations)

## Effort Estimate
2 hours

## Technical Notes
- Alert types: ranking_improved, ranking_dropped, new_review, negative_review, rating_dropped, competitor_overtake, gbp_disconnected
- Severities: info, warning, critical
- Local pack position is TINYINT (NULL if not in pack)

---

*Created by SpecSystemAnalyst on 2026-03-08*
