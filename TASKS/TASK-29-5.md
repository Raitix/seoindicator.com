# TASK-29-5: Rate Limiting Integration

## STATUS
`To Develop`

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

### [2026-03-05 20:55 GMT+2] Agent: PreTester
**Tests Coverage Complete**

**Test Files Created:**
- `backend/tests/Feature/Api/V1/Tools/RateLimitIntegrationTest.php` - 52 tests
- `TESTS/TEST-29-5.md` - Test documentation updated

**Config Updated:**
- Added `export_rate_window` to `backend/config/tools.php`

**Implementation Approach for Developer:**
1. Rate limiting should be checked FIRST in `ToolExportController::export()`
2. Use Laravel's `RateLimiter` facade or Cache-based implementation
3. Key pattern: `export_rate_limit:{user_id}`
4. Return 429 with proper headers when limit exceeded
5. Include rate limit headers on ALL responses (success and error)

**Key Service Integration Options:**
- Option A: Extend `ToolsRateLimitService` with user-based methods
- Option B: Use Laravel's built-in `RateLimiter::attempt()` directly
- Option C: Use Cache facade with atomic increment

**Acceptance Criteria Mapping:**
- [x] Rate limiting applied to POST /api/v1/tools/export → Tests RL-001 to RL-010
- [x] Limit: 10 exports per hour per user → Tests RL-005, RL-006
- [x] Rate limit headers returned → Tests RH-001 to RH-008
- [x] 429 Too Many Requests response → Tests RE-001 to RE-006
- [x] Error message with retry time → Tests RE-002
- [x] Config in tools.php → Tests RC-001 to RC-005

**Tests will fail until implementation is complete.** Run tests with:
```bash
php artisan test tests/Feature/Api/V1/Tools/RateLimitIntegrationTest.php
```

Status updated: `Tests Coverage In Progress` → `To Develop`

### [2026-03-05 14:42 GMT+2] Agent: ProjectManager
**Prioritization Complete**

**Priority Assessment:** Medium (confirmed)
- Security/enhancement feature, not foundation infrastructure
- Export endpoint already functional (TASK-29-3 Done)
- Protects against abuse but doesn't add new user functionality
- Should be implemented before heavy usage, but not blocking

**Risk Assessment:** Low (confirmed)
- Standard Laravel service integration
- Uses existing ToolsRateLimitService
- No external APIs or complex logic
- Configuration already exists in `config/tools.php`

**Dependency Analysis:**
- TASK-29-3 (ToolExportController): Done ✅ - Controller exists and functional
- ToolsRateLimitService: Exists ✅ with methods `checkLimit()`, `increment()`, `getHeaders()`
- Config `export_rate_limit`: Already set to 10 in `config/tools.php`

**Implementation Notes:**
- Task requires **user-based** rate limiting (not IP-based like existing service)
- Need to add user-specific method to ToolsRateLimitService OR use different key pattern
- Current service is IP-based; task specifies `auth()->id()` for key generation
- Options:
  1. Add `checkUserLimit(int $userId)` method to ToolsRateLimitService
  2. Use Cache facade directly in controller with user-based key
- Rate limit: 10 exports per hour per user (configurable)
- Headers required: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- 429 response with "Try again in X minutes" message

**TDD Ready:** PreTester CAN design tests with:
- Mocked ToolsRateLimitService interface
- Well-defined response structure (429 with retry message)
- Rate limit headers specification
- Config values documented

**Execution Order:** Phase 2 Backend Polish - Can proceed immediately since TASK-29-3 is complete.

Status updated: `To Prioritize` → `To Write Tests` - Ready for PreTester
