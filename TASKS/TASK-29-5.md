# TASK-29-5: Rate Limiting Integration

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Integrate rate limiting for export endpoints using existing ToolsRateLimitService.

## Dependencies
- TASK-29-3 (ToolExportController provides integration point)

## Acceptance Criteria
- [ ] Rate limiting applied to `POST /api/v1/tools/export` endpoint
- [ ] Limit: 10 exports per hour per user (configurable)
- [ ] Rate limit headers returned in response:
  - `X-RateLimit-Limit`: Maximum requests per window
  - `X-RateLimit-Remaining`: Requests remaining in window
  - `X-RateLimit-Reset`: Unix timestamp when window resets
- [ ] Rate limit exceeded response: 429 Too Many Requests
- [ ] Error message: "Export rate limit exceeded. Try again in {X} minutes."
- [ ] Rate limit configuration in `config/tools.php`:
  - `export_rate_limit`: 10
  - `export_rate_window`: 3600 (seconds)

## Technical Details
```php
// In ToolExportController
public function __construct(
    private ToolExportService $exportService,
    private ToolsRateLimitService $rateLimiter
) {}

public function export(Request $request): JsonResponse
{
    // Check rate limit
    $remaining = $this->rateLimiter->attempt(
        key: "export:" . auth()->id(),
        maxAttempts: config('tools.export_rate_limit', 10),
        decaySeconds: config('tools.export_rate_window', 3600)
    );
    
    if ($remaining === false) {
        return response()->json([
            'message' => 'Export rate limit exceeded. Try again later.',
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => config('tools.export_rate_limit', 10),
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => $this->rateLimiter->availableAt("export:" . auth()->id()),
        ]);
    }
    
    // ... export logic
}
```

## Files to Create/Modify
- `backend/app/Http/Controllers/Api/V1/ToolExportController.php` (add rate limiting)
- `backend/config/tools.php` (add rate limit config)

## Effort Estimate
1 hour

## Comments
_Agent log_
