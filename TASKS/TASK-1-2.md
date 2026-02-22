# SPEC-001-2: Keyword Model and Repository

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the Keyword model and repository for database operations. Provides abstraction layer for keyword CRUD operations.

## Acceptance Criteria
- [x] Keyword model with all fields from database schema
- [x] KeywordRepository with methods: findById, findBySite, create, update, delete
- [x] Status enum: active, paused, deleted
- [x] SearchEngine enum: google (MVP only)
- [x] Device enum: desktop (MVP only)
- [x] Model includes relationships: site, rankingHistory
- [x] Repository handles pagination for list queries
- [x] Unit tests for repository methods

## Dependencies
- SPEC-001-1 (keywords table must exist)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use Eloquent ORM (Laravel) or custom repository pattern
- Include soft delete support
- Add scopes for common queries (active, bySite, needsCheck)

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for keyword model and repository. Standard data layer implementation.

### [2026-02-22 15:52] Agent: Developer
**Implementation Complete**

- Updated Keyword model: site/user relationships, rankingHistory, scopes, status soft-delete
- Added auto user_id derivation from site_id on create
- Added KeywordRepository with pagination, status filtering, create/update/delete, counts
- Added KeywordFactory + Site model/factory

**Tests:** php artisan test --filter "KeywordTest|RankingTest|KeywordRepositoryTest|RankingRepositoryTest"

**Status:** → To Test Locally
