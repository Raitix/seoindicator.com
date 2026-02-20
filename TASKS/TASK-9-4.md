# TASK-9-4: AnnotationController

## STATUS
`To Prioritize`

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
### [2026-02-20 17:51] Agent: SpecSystemAnalyst
Created task for implementing a controller to manage annotations via the backend API.