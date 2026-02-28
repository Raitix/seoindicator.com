# TASK-24-4: ManifestValidatorService

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create service to validate PWA manifest structure and required fields. Ensures manifest meets PWA standards.

## Acceptance Criteria
- [ ] ManifestValidatorService created with methods:
  - validate(array $manifest): ManifestValidationResult
  - validateField(string $field, mixed $value): bool
  - getErrors(): array
- [ ] Validates required fields: name, short_name, start_url, display, icons
- [ ] Validates recommended fields: background_color, theme_color, description
- [ ] Validates display enum values: standalone, fullscreen, minimal-ui, browser
- [ ] Validates icons array structure (src, sizes, type, purpose)
- [ ] ManifestValidationResult DTO with:
  - valid (bool)
  - errors (array of ManifestError objects)
  - warnings (array)
  - fieldPresence (array of field => bool)
- [ ] Unit tests for all validation rules

## Dependencies
- TASK-24-3 (ManifestFetcherService)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Pure validation logic - no external calls
- Return detailed errors for debugging
- Use value objects/DTOs for results

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for manifest validation. Pure logic service with no external dependencies. Low risk. Validates against W3C Web App Manifest specification.

### [2026-02-28 23:08 GMT+2] Agent: ProjectManager
**Prioritization Review Complete**

- **Priority Confirmed:** High (foundation task in Phase 2, blocks TASK-24-5 through TASK-24-7)
- **Risk Confirmed:** Low (pure validation logic, no external calls)
- **Dependency Analysis:**
  - TASK-24-3 (ManifestFetcherService): **To Write Tests** (in pipeline, ahead of this task)
- **Execution Order:** After TASK-24-3 completes and provides manifest data to validate
- **Technical Notes:**
  - Validates required fields: name, short_name, start_url, display, icons
  - Validates recommended fields: background_color, theme_color, description
  - Uses value objects/DTOs for clean result structure

Status updated to `To Write Tests`. Ready for PreTester to create test cases for manifest validation rules.

### [2026-03-01 00:55 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for ManifestValidatorService:

**Test Documentation:** `tests/SPEC-24-4.md`
**Test Implementation:** `backend/tests/Unit/Services/ManifestValidatorServiceTest.php`

**Test Categories (53 tests):**
1. **validate() Method (10 tests):** Main validation entry point
   - Complete valid manifest, missing each required field, empty manifest, null input
2. **validateField() Method (12 tests):** Individual field validation
   - name, short_name, start_url, display (all 4 values), icons
3. **Icon Validation (10 tests):** Icon array structure and content
   - Valid icon, missing src/sizes/type, purpose values, empty src, sizes formats, type values
4. **Recommended Fields (6 tests):** background_color, theme_color, description
   - Missing fields generate warnings, valid color formats
5. **getErrors() Method (3 tests):** Error retrieval
   - Get errors, empty errors, multiple errors
6. **ManifestValidationResult DTO (6 tests):** Result object structure
   - valid, errors, warnings, fieldPresence properties, hasErrors/hasWarnings methods
7. **ManifestError DTO (4 tests):** Error object structure
   - field, code, message, severity properties
8. **Edge Cases (10 tests):** Unicode, extra fields, etc.
   - Extra fields ignored, Unicode in name, long name, duplicate sizes, query params, case sensitivity
9. **Security Tests (3 tests):** XSS, SQL injection, JS URLs
   - XSS in name, SQL injection in start_url, JavaScript URL in icon src

**Key Design Decisions:**
- Required fields generate errors (block validation): name, short_name, start_url, display, icons
- Recommended fields generate warnings (don't block): background_color, theme_color, description
- Display values are case-sensitive (standalone, fullscreen, minimal-ui, browser)
- Extra fields are ignored (not validated)
- Icon src is required; sizes/type/purpose are recommended
- Color formats accepted: hex (#FFF, #ffffff), named colors, rgb()
- Security: Validation doesn't sanitize input (caller's responsibility)

**DTO Classes Required:**
- `App\DTO\ManifestValidationResult` with valid, errors, warnings, fieldPresence properties
- `App\DTO\ManifestError` with field, code, message, severity properties

Status updated to `To Develop`. Ready for Developer to implement the service and DTOs.
