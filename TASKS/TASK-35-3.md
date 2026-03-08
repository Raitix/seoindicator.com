# TASK-35-3: GBP-Related Models

## STATUS
`To Prioritize`

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
