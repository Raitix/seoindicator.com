# TASK-24-9: PWA Guide API Endpoint

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create API controller and endpoint for PWA implementation guide content. Provides educational content for users improving their PWA.

## Acceptance Criteria
- [ ] PWAGuideController created with method:
  - index(): JsonResponse - GET /api/v1/pwa/guide
  - section(string $id): JsonResponse - GET /api/v1/pwa/guide/{id}
- [ ] GET endpoint returns:
  - Full guide content (markdown)
  - Table of contents with section IDs and titles
- [ ] Sections included:
  - manifest-basics: What is a manifest, required fields
  - icon-requirements: Icon sizes, formats, purposes
  - ios-support: iOS-specific considerations (apple-touch-icon)
  - service-worker: Brief overview (not full implementation)
- [ ] Content stored in database or markdown files
- [ ] No authentication required (public endpoint)
- [ ] Feature tests for endpoint

## Dependencies
- None (standalone content)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Consider storing content in markdown files for easy editing
- Could use a CMS or static content approach
- Cache the guide content

---

## Comments
_Agent communication log_

### [2026-03-03] Agent: SpecSystemAnalyst
Created task for guide API. Low-risk content delivery endpoint. Can be implemented independently of other PWA services. Content should be SEO-friendly and actionable.

### [2026-03-03] Agent: PreTester
**Tests Created:** 75 feature tests in `backend/tests/Feature/Api/V1/PWAGuideControllerTest.php`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Basic Endpoint (PG-001 to PG-010) | 10 | Index endpoint structure, public access, markdown content |
| Table of Contents (PG-011 to PG-015) | 5 | TOC structure, section matching |
| Section Endpoint (PG-020 to PG-028) | 9 | Individual section endpoints for all 4 sections |
| Content Validation (PG-030 to PG-037) | 8 | Section-specific content requirements |
| Error Handling (PG-040 to PG-045) | 6 | 404 for invalid sections, error structure |
| HTTP Methods (PG-050 to PG-054) | 5 | GET-only restriction |
| Security (PG-060 to PG-065) | 6 | SQL injection, XSS, path traversal |
| Caching (PG-070 to PG-075) | 6 | Cache headers, consistency |
| Edge Cases (PG-080 to PG-085) | 6 | Unicode, long IDs, trailing slash |
| Performance (PG-090 to PG-095) | 6 | Response time, memory usage |
| Data Types (PG-100 to PG-105) | 6 | Type validation |

**Implementation Required:**
1. `PWAGuideController` with `index()` and `section(string $id)` methods
2. Routes: `GET /api/v1/pwa/guide` and `GET /api/v1/pwa/guide/{id}`
3. Content storage (markdown files recommended)
4. Caching for guide content

**Documentation:** `TESTS/TEST-24-9.md`
