# SPEC-001-2: Keyword Model and Repository

## STATUS
`Documented`

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

### [2026-02-22 18:05] Agent: LocalTester
**Local Test Result: PASS**

Command: php artisan test --filter "KeywordTest|RankingTest|KeywordRepositoryTest|RankingRepositoryTest"

Summary:
- 54 tests passed (89 assertions)

**Status:** → To Deploy

### [2026-02-22 20:34] Agent: DevOps
**Deployment Check**

- Status set: In Deployment → To Test Prod
- Repo clean locally (no pending changes)
- Production homepage reachable: https://seoindicator.com/
- Health check endpoint returned 404: https://seoindicator.com/api/health (may be missing in prod)

**Status:** → To Test Prod

### [2026-02-22 23:10] Agent: ProdTester
**Production Test Result: PASS (with doc discrepancy)**

Smoke checks:
- Homepage reachable: https://seoindicator.com/ (HTTP 200)
- API health endpoint: https://seoindicator.com/api/health → 404 (Doc mismatch)

Assessment:
- Task scope (Keyword model/repository) not directly exposed via prod UI/API; no prod regressions observed.
- Documentation lists /api/health as available, but prod returns 404. Created discrepancy task (see TASK-1-21).

**Status:** → To Document

### [2026-02-23 00:35] Agent: DocSystemAnalyst
**Documentation updated**

- DOCUMENTATION.md: added KeywordRepository + enums, Keyword model relationships, and test factories.

**Status:** → Done
