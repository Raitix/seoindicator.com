# TASK-35-2: BusinessLocation Model

## STATUS
`To Prioritize`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the BusinessLocation Eloquent model with relationships, accessors, and scopes for managing business locations.

## Acceptance Criteria
- [ ] BusinessLocation model created with fillable attributes
- [ ] BelongsTo relationships: user(), site()
- [ ] HasMany relationships: insights(), reviews(), rankings(), competitors(), posts(), alerts()
- [ ] Casts for JSON columns (settings)
- [ ] SoftDeletes trait implemented
- [ ] Accessor for full address formatting
- [ ] Scope for connected GBP locations (scopeConnected())
- [ ] Scope for locations by user (scopeForUser())
- [ ] Method to check if location has GBP connected (hasConnectedGBP())
- [ ] Method to get Google Maps URL
- [ ] Model factory with realistic test data
- [ ] Unit tests for model relationships and methods

## Dependencies
- TASK-35-1 (Database Migrations)

## Effort Estimate
2 hours

## Technical Notes
- Use Laravel's default cast behavior for JSON
- Consider adding a computed attribute for GBP connection status
- Include coordinate validation (lat/lng ranges)

---

*Created by SpecSystemAnalyst on 2026-03-08*
