# TASK-35-3: GBP-Related Models

## STATUS
`To Write Tests`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Low`

## Description
Create Eloquent models for GBP-related data: GBPInsight, GBPReview, and GBPPost.

## Acceptance Criteria
- [ ] GBPInsight model created:
  - BelongsTo relationship to BusinessLocation
  - Casts for numeric fields (views, searches, calls, etc.)
  - Date casting for insights_date
  - Scope for date range filtering
  - Method to calculate total actions (calls + directions + website_clicks)
- [ ] GBPReview model created:
  - BelongsTo relationship to BusinessLocation
  - Fillable: google_review_id, author_name, author_photo, rating, review_text, sentiment, response fields
  - Casts for rating (integer) and dates
  - Scope for rating filtering (scopePositive(), scopeNegative(), scopeNeutral())
  - Scope for unresponded reviews
  - Method to check if owner has responded
  - Method to get sentiment label (positive/negative/neutral)
- [ ] GBPPost model created:
  - BelongsTo relationship to BusinessLocation
  - Casts for published_at timestamp
  - Scope for published posts
  - Accessor for post type label (EVENT, OFFER, UPDATE, etc.)
- [ ] Model factories for all three models
- [ ] Unit tests for all models

## Dependencies
- TASK-35-1 (Database Migrations)

## Effort Estimate
2 hours

## Technical Notes
- Rating is TINYINT (1-5 stars)
- Consider adding computed attributes for common aggregations
- Sentiment field should be enum-like (positive, negative, neutral)

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### ProjectManager Session (2026-03-08 21:48 GMT+2)
- **Priority:** HIGH (confirmed) - Model layer for GBP data (insights, reviews, posts)
- **Risk:** LOW (confirmed) - Standard Eloquent models, no external dependencies

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-35-1 (Database Migrations) | To Test Locally | ✅ YES |
| TASK-35-2 (BusinessLocation Model) | To Prioritize | ❌ NO (transitive) |

**Technical Assessment:**
- 3 models to create: GBPInsight, GBPReview, GBPPost
- All models have `belongsTo` relationship to BusinessLocation
- Migrations for all 3 tables exist ✅
- BusinessLocation model NOT YET CREATED (TASK-35-2 pending)

**TDD Pattern Applied:**
- PreTester CAN design tests with mocked BusinessLocation interface
- Model tests verify: fillable, casts, relationships, scopes, accessors
- Implementation waits for TASK-35-2 to reach `To Test Locally`
- This enables parallel test design with model development

**Model Details:**
| Model | Table | Key Relationships | Key Scopes |
|-------|-------|-------------------|------------|
| GBPInsight | gbp_insights | belongsTo(BusinessLocation) | forDateRange() |
| GBPReview | gbp_reviews | belongsTo(BusinessLocation) | positive(), negative(), neutral(), unresponded() |
| GBPPost | gbp_posts | belongsTo(BusinessLocation) | published() |

**Priority Reasoning:**
- HIGH because these are foundation models for GBP data tracking
- Enables downstream: TASK-35-9 (GBPInsightsService), TASK-35-10 (GBPReviewsService)
- Low risk - standard Eloquent model patterns, well-defined schema

**Dependency Chain:**
```
TASK-35-1 (Migrations) ✅ DONE
  ↓
TASK-35-2 (BusinessLocation Model) ← BLOCKED HERE
  ↓
TASK-35-3 (This task - GBP Models) ← Current
  ↓
TASK-35-9, 35-10 (Services)
  ↓
TASK-35-14 (Controllers)
```

**Decision:**
- **Status:** `To Write Tests` - PreTester can design tests with mocked BusinessLocation interface
- Parallel development enabled: tests ready when BusinessLocation model exists
- Key lesson: Model tasks with transitive dependencies can proceed to PreTester using TDD with mocked interfaces
