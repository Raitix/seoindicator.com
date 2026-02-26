# TASK-9-4: AnnotationController

## STATUS
`To Write Tests`

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