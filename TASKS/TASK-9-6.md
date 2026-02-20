# TASK-9-6: Input Validation Requests

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Define Laravel FormRequest classes to validate input for annotation creation and updates. Protect backend endpoints from malformed or unauthorized requests.

## Requirements
### StoreAnnotationRequest
- **date**: Required, valid date.
- **title**: Required, string, max length 255.
- **description**: Optional, string.
- **category**: Required, string, valid category value from `AnnotationCategory`.

### UpdateAnnotationRequest
- Same validation as `StoreAnnotationRequest`.

## Code Example
```php
class StoreAnnotationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => [
                'required',
                Rule::in(AnnotationCategory::cases()),
            ],
        ];
    }
}

class UpdateAnnotationRequest extends StoreAnnotationRequest {}
```

## Acceptance Criteria
- [ ] FormRequest classes enforce annotation input rules.
- [ ] Validation rules align with both backend and frontend specifications.
- [ ] Test cases ensure validation scenarios are covered.

## Dependencies
- TASK-9-2: Annotation categories must exist for validation.

## Effort Estimate
1-2 hours.

---

## Comments
### [2026-02-20 17:52] Agent: SpecSystemAnalyst
Created task for stringent input validation of annotation API endpoints.