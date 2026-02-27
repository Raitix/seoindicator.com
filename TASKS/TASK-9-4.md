# TASK-9-4: AnnotationController

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Develop `AnnotationController` to handle CRUD operations for keyword annotations. Integrate policies and request validation for secure and reliable operations.

## Controller Methods
1. **index**: List annotations with optional filters (date range, category).
2. **store**: Create a new annotation with `StoreAnnotationRequest` validation.
3. **show**: Retrieve a single annotation by ID.
4. **update**: Edit an annotation with `UpdateAnnotationRequest` validation.
5. **destroy**: Delete an annotation.

## Code Example
```php
class AnnotationController extends Controller
{
    public function index(Keyword $keyword)
    {
        $annotations = $keyword->annotations()
            ->when(request('from'), fn($q, $from) => $q->where('date', '>=', $from))
            ->when(request('to'), fn($q, $to) => $q->where('date', '<=', $to))
            ->when(request('category'), fn($q, $cat) => $q->where('category', $cat))
            ->orderBy('date', 'desc')
            ->get();

        return AnnotationResource::collection($annotations);
    }

    public function store(Keyword $keyword, StoreAnnotationRequest $request)
    {
        $annotation = $keyword->annotations()->create([
            ...$request->validated(),
            'project_id' => $keyword->project_id,
        ]);

        return new AnnotationResource($annotation);
    }

    // Other methods omitted for brevity.
}
```

## Acceptance Criteria
- [ ] CRUD operations implemented with appropriate request validations.
- [ ] Annotations scoped to the authenticated user and project.
- [ ] Test cases verify various scenarios (e.g., invalid input, unauthorized access).

## Dependencies
- TASK-9-3: Annotation model must exist.
- TASK-9-5: Policies must be defined for authorization.

## Effort Estimate
3-5 hours.

---

## Comments
### [2026-02-27 11:52] Agent: PreTester
**Test Coverage Complete:**
- Created `TESTS/TEST-9-4.md` with 73 test scenarios covering:
  - Index (List) tests: IX-001 to IX-010 (10 tests)
  - Store (Create) tests: ST-001 to ST-012 (12 tests)
  - Show (Single) tests: SH-001 to SH-004 (4 tests)
  - Update tests: UP-001 to UP-010 (10 tests)
  - Destroy (Delete) tests: DE-001 to DE-004 (4 tests)
  - Authorization (Unauthenticated): AZ-001 to AZ-005 (5 tests)
  - Authorization (Cross-User): AZ-010 to AZ-014 (5 tests)
  - Authorization (Owner): AZ-020 to AZ-024 (5 tests)
  - Edge Cases: EC-001 to EC-012 (12 tests)
  - Integration: IT-001 to IT-006 (6 tests)

**Test File Created:**
- `backend/tests/Feature/Api/V1/AnnotationControllerTest.php` - 73 test methods

**Controller Implementation Status:**
- ✅ `index()` - List with date range, category filters, pagination
- ✅ `store()` - Create with validation and authorization
- ✅ `show()` - Show single annotation
- ✅ `update()` - Update annotation
- ✅ `destroy()` - Delete annotation
- ✅ Authorization calls via `$this->authorize()`

**Key Testing Priorities:**
1. AZ-010 to AZ-014: Cross-user authorization (critical for security)
2. ST-005, ST-006: Required field validation
3. EC-006, EC-007: XSS/SQL injection handling
4. IX-002, IX-003: Filter functionality

**Dependency Note:**
TASK-9-5 (AnnotationPolicy) is required for authorization tests. Tests will initially fail until policy is implemented - correct TDD behavior.

**Status:** `Tests Coverage In Progress` → Ready for Developer implementation

### [2026-02-26 20:03] Agent: ProjectManager
**Priority Assessment:** Confirmed `High` - Core API endpoint layer, integration layer in the dependency chain. Controller enables CRUD operations for annotations, which multiple frontend components depend on.

**Risk Assessment:** Confirmed `Medium` - New API endpoints with non-destructive operations. Standard Laravel controller implementation with request validation and authorization.

**Dependency Analysis:**
- TASK-9-3 (Annotation Model): Status `To Write Tests` - ahead in pipeline ✅
- TASK-9-5 (Policies): Status `To Prioritize` - should be developed before/during for authorization integration

**Execution Order Notes:**
- Controller should be developed after model (TASK-9-3) is ready
- Policies (TASK-9-5) can be developed in parallel or slightly after
- Input validation requests (TASK-9-6) should be ready for controller validation
- Frontend components (TASK-9-7, TASK-9-8) depend on this controller's API

**PreTester Notes:**
- Test CRUD operations with valid/invalid input
- Test authorization scoping (user can only access their own annotations)
- Test request validation integration
- Test API response formats

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-20 17:51] Agent: SpecSystemAnalyst
Created task for implementing a controller to manage annotations via the backend API.