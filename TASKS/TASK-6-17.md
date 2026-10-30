# TASK-6-17: OpenAPI Specification

## Status
`Done`

## Priority
`Low`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Create OpenAPI (Swagger) specification for the API.

## Requirements

### OpenAPI Document
- Version 3.0 specification
- All endpoints documented
- Request/response schemas
- Authentication description
- Error responses

### Content
- API info (title, version, description)
- Server configurations
- Security schemes (Bearer token)
- Path definitions for all endpoints
- Component schemas for entities

### Output
- `backend/src/api/openapi.yaml`

## Acceptance Criteria
- [x] OpenAPI spec created
- [x] All endpoints documented
- [x] Schemas defined
- [x] Valid OpenAPI format
- [x] Can be used for documentation generation

## Dependencies
- TASK-6-11 (all API endpoints)

## Estimated Effort
Medium (2-3 hours)

---

## Comments

### [2026-03-08 13:56] PreTester: Tests Complete ✅
- **Status:** `To Write Tests` → `To Develop`
- **Test Files Created:**
  - `backend/tests/Unit/OpenApi/OpenApiSpecTest.php` (65 tests)
  - `backend/tests/Unit/OpenApi/OpenApiSchemaTest.php` (25 tests)
  - `backend/tests/Unit/OpenApi/OpenApiEndpointsTest.php` (69 tests)
- **Total Tests:** 159 tests
- **Test Documentation:** `TESTS/TEST-6-17.md`

### Test Categories
1. **OAS-STRUCTURE:** OpenAPI file structure validation (8 tests)
2. **OAS-INFO:** API info section validation (6 tests)
3. **OAS-SERVERS:** Server configuration tests (5 tests)
4. **OAS-AUTH:** Authentication scheme tests (8 tests)
5. **OAS-PATHS:** Path definition tests (10 tests)
6. **OAS-RESPONSES:** Response definition tests (6 tests)
7. **OAS-SCHEMAS:** Component schema tests (12 tests)
8. **OAS-ENDPOINTS:** Endpoint coverage tests (90+ tests)
9. **OAS-VALIDATION:** Schema validation tests (8 tests)
10. **OAS-SECURITY:** Security documentation tests (5 tests)
11. **OAS-ERRORS:** Error documentation tests (8 tests)
12. **OAS-TAGS:** Tag organization tests (5 tests)
13. **OAS-EXAMPLES:** Example values tests (4 tests)
14. **OAS-DOCS:** Documentation generation tests (4 tests)
15. **AC:** Acceptance criteria tests (5 tests)

### Key Test Patterns
- Tests check for file existence and valid YAML/JSON parsing
- OpenAPI version must be 3.0.x
- All authentication schemes validated (Bearer, API Key)
- Endpoint coverage tests for all route groups
- Security applied to protected endpoints, public endpoints without auth
- Error responses (400, 401, 403, 404, 422, 500) documented
- Component schemas validated for proper structure

### TDD Pattern
Tests will fail until Developer creates `backend/src/api/openapi.yaml` with valid OpenAPI 3.0 specification documenting all API endpoints.

### [2026-03-13 13:25] Developer: Task Complete ✅
- **Status:** `To Develop` → `Done`
- **Output:** `backend/src/api/openapi.yaml` (1,900+ lines)
- **OpenAPI Version:** 3.0.3

### Implementation Summary
Created comprehensive OpenAPI 3.0.3 specification documenting all API endpoints:

**Endpoints Documented (90+):**
- Authentication: 10 endpoints (register, login, logout, password reset, OAuth)
- Keywords: 9 endpoints (CRUD, rankings, history, check)
- Sites: 6 endpoints (CRUD, dashboard)
- Projects: 7 endpoints (CRUD, archive, restore)
- Reports: 10 endpoints (CRUD, templates, scheduled)
- Audits: 10 endpoints (CRUD, issues, publish, embed, analytics)
- Competitors: 5 endpoints (CRUD)
- Analysis: 7 endpoints (jobs, stream, progress)
- AI Visibility: 7 endpoints (projects, run, analytics)
- Public: 13 endpoints (health, public-audit, badges, tools, models)
- Admin: 12 endpoints (stats, users, health, credits)
- Payments: 6 endpoints (methods, PayPal, credits)
- Webhooks: 6 endpoints (CRUD, test)
- Content Decay: 12 endpoints (summary, candidates, tasks, AI brief)

**Component Schemas (50+):**
- User, Site, Keyword, Project, Report, Audit
- Competitor, AnalysisJob, AIVisibilityProject
- ContentDecay, DecayCandidate, ContentRefreshTask
- Error responses, pagination meta
- Request/response schemas for all endpoints

**Security Schemes:**
- BearerAuth (JWT via Laravel Sanctum)
- ApiKeyAuth (X-API-Key header)

**Features:**
- Full request/response examples
- Parameter descriptions
- Response codes (200, 201, 204, 400, 401, 403, 404, 422, 429)
- Tags for logical grouping
- Server configurations (production, staging, local)

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: Low (documentation)
- Risk: Low (no code changes)
- Part of Phase 5: Documentation
