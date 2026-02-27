# TASK-9-10: End-to-End Integration Tests

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Develop end-to-end integration tests for annotations. These tests ensure coherence between frontend components and backend APIs.

## Test Coverage
1. **API Integration**
   - Ensure CRUD operations (`create`, `read`, `update`, `delete`) work with frontend input.
   - Validate API responses match the data model.

2. **Frontend Integration**
   - Simulate user workflows in frontend components.
   - Ensure `AnnotationList` renders API data correctly.
   - Ensure `AnnotationForm` interacts seamlessly with backend endpoints.

3. **Error Scenarios**
   - Simulate invalid input (e.g., incorrect category value).
   - Test unauthorized access attempts.

## Acceptance Criteria
- [ ] All API endpoints tested using a mock backend server.
- [ ] Frontend and backend alignment confirmed for all workflows.
- [ ] Test cases document edge cases and error-handling scenarios.

## Dependencies
- TASK-9-7: Frontend list component must exist.
- TASK-9-8: Frontend form component ready for integration.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-27 11:30] Agent: PreTester
**Test Documentation Complete:**
- Created `TESTS/TEST-9-10.md` with 91 test scenarios covering:
  - API Integration (API-001 to API-012): 12 tests for CRUD operations
  - Error Scenarios (ERR-001 to ERR-015): 15 tests for validation and authorization errors
  - Frontend Integration (FE-001 to FE-015): 15 tests for AnnotationList and AnnotationForm components
  - Full Workflows (WF-001 to WF-008): 8 tests for complete user journeys
  - Authorization (AUTH-001 to AUTH-010): 10 tests for access control
  - Data Consistency (DC-001 to DC-010): 10 tests for database persistence
  - Performance (PERF-001 to PERF-007): 7 tests for response time validation
  - Edge Cases (EC-001 to EC-014): 14 tests for boundary conditions

**Test Files Created:**
- `backend/tests/Integration/AnnotationE2ETest.php` - 50+ PHP test methods
- `frontend/tests/integration/AnnotationIntegration.spec.tsx` - 20+ React/Vitest tests

**Key Test Coverage:**
1. **Security Tests:**
   - SQL injection attempt (ERR-014) - title stored as-is, no injection
   - XSS attempt (ERR-015) - stored, sanitization on output
   - Authorization enforcement (AUTH-005 to AUTH-008) - 403 Forbidden for non-owners

2. **Data Integrity:**
   - Cascade delete tests (via FK constraints from TASK-9-1)
   - Timestamp auto-management
   - Category default to 'other'

3. **Frontend-Backend Alignment:**
   - API response structure matches component expectations
   - Category dropdown values match backend validation
   - Date filtering uses correct query parameters

**API Endpoint Structure Tested:**
```
GET    /api/v1/keywords/{keywordId}/annotations         - List
POST   /api/v1/keywords/{keywordId}/annotations         - Create
GET    /api/v1/annotations/{id}                         - Read
PUT    /api/v1/annotations/{id}                         - Update
DELETE /api/v1/annotations/{id}                         - Delete
```

**Frontend Component Props Documented:**
- AnnotationList: keywordId, annotations, onEdit, onDelete, isLoading, error
- AnnotationForm: keywordId, annotation (optional), onSave, onCancel

**Dependencies for Developer:**
- Tests require Annotation model with factory (TASK-9-2)
- Tests require AnnotationController with routes (TASK-9-4)
- Tests require AnnotationPolicy (TASK-9-5)
- Frontend tests require components (TASK-9-7, TASK-9-8)

**Status changed:** `To Write Tests` → `To Develop`

### [2026-02-26 19:43] Agent: ProjectManager
**Priority Assessment:** Confirmed `High` - E2E integration tests are critical for validating multi-component annotation workflows.

**Risk Assessment:** Confirmed `Low` - Testing task with no production impact.

**Dependency Analysis:**
- TASK-9-7 (AnnotationList Component): Currently `To Prioritize` - will need to be developed first
- TASK-9-8 (AnnotationForm Component): Currently `To Prioritize` - will need to be developed first

**Rationale:**
- E2E tests validate the complete annotation feature chain (frontend → backend → database)
- User-facing functionality requires comprehensive integration testing
- Test coverage includes API integration, frontend integration, and error scenarios
- PreTester can design tests in parallel with frontend development

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-20 17:55] Agent: SpecSystemAnalyst
Integration testing task outlined to validate end-to-end workflows for annotations.