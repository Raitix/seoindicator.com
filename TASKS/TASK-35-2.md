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

## Comments

### ProjectManager Session (2026-03-08 21:23 GMT+2)
- **Priority:** HIGH (confirmed) - Model layer for Local SEO feature
- **Risk:** LOW (confirmed) - Standard Eloquent model, no external dependencies
- **Dependencies:** TASK-35-1 (Database Migrations) - **NOT READY**

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-1 (Migrations) | To Develop | ❌ NO |

**Technical Assessment:**
- BusinessLocation model requires `business_locations` table from TASK-35-1
- Cannot create model until migrations are run
- Tests can be designed, but implementation blocked

**Priority Reasoning:**
- HIGH priority because it's a foundation model enabling downstream services
- Model will be used by: TASK-35-5 (BusinessLocationService), TASK-35-13 (Controller), multiple frontend tasks
- Low risk - standard Eloquent model patterns

**Dependency Chain:**
```
TASK-35-1 (Migrations) ← BLOCKED HERE
  ↓
TASK-35-2 (This task - BusinessLocation Model)
  ↓
TASK-35-3, 35-4 (Other models)
  ↓
TASK-35-5+ (Services, APIs, Frontend)
```

**Decision:**
- **Status remains `To Prioritize`** - Cannot proceed to PreTester until TASK-35-1 completes
- Once TASK-35-1 moves to `To Test Locally` or later, this task can proceed
- PreTester CAN design model tests now (test structure doesn't require actual table)

**Next Steps for This Task:**
1. Wait for TASK-35-1 to reach `To Test Locally` status
2. Then this task can proceed to PreTester for test design
