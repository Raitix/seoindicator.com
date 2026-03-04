# TASK-29-4: Download Token System

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Specification
[SPEC-029](../SPECIFICATIONS/SPEC-029.md) - Export Results (PDF/XLS)

## Description
Implement secure token-based download system with one-time use tokens and expiration.

## Dependencies
- TASK-29-3 (ToolExportService provides integration point)

## Acceptance Criteria
- [ ] Token generation: 64-character random string
- [ ] Token stored in Laravel Cache with metadata:
  - File path
  - Format (pdf/xls)
  - User ID
  - Created timestamp
- [ ] Token TTL: 1 hour (configurable via `TOOL_EXPORT_TOKEN_TTL`)
- [ ] Token validation on download:
  - Check token exists in cache
  - Verify user ID matches
  - Check not expired
- [ ] Token invalidated after successful download (one-time use)
- [ ] Expired tokens automatically cleaned by cache TTL

## Technical Details
```php
// Token generation
$token = Str::random(64);
Cache::put("export:{$token}", [
    'file' => $filePath,
    'format' => $format,
    'user_id' => auth()->id(),
    'created_at' => now()->toIso8601String(),
], now()->addSeconds(config('tools.export_token_ttl', 3600)));

// Token validation
$tokenData = Cache::get("export:{$token}");
if (!$tokenData || $tokenData['user_id'] !== auth()->id()) {
    abort(404, 'Invalid or expired download link');
}

// Token invalidation after download
Cache::forget("export:{$token}");
```

## Security Considerations
- IDOR prevention: Validate user_id matches token owner
- One-time use: Delete token after download
- Time-limited: 1-hour expiration
- Random token: 64 chars cryptographically secure

## Files to Create/Modify
- `backend/app/Services/Tools/ToolExportService.php` (add token methods)
- `backend/config/tools.php` (add export config)

## Effort Estimate
2 hours

## Comments
_Agent log_

### [2026-03-04 12:58 GMT+2] Agent: ProjectManager
**Prioritization Complete**

**Priority Assessment:** HIGH (confirmed) - Security-critical token system essential for export feature. Blocks user ability to download exports. Without this, the export feature is non-functional.

**Risk Assessment:** HIGH (confirmed) - Multiple security-critical operations:
- IDOR prevention (user_id validation)
- Token generation (cryptographic security)
- Token lifecycle (generation, validation, invalidation)
- Cache-based storage (TTL handling)
- One-time use enforcement

**Dependency Analysis:**
- TASK-29-3 (ToolExportService): `To Write Tests` - Tests being designed by PreTester
- TASK-29-3 provides the integration point (`createExport()` and `downloadExport()` methods)
- Token methods will be added to ToolExportService

**TDD Ready:** PreTester CAN design tests now with:
- Well-defined token generation spec (64 chars, Laravel Cache)
- Clear validation rules (existence, user_id match, expiration)
- One-time use behavior clearly specified
- Config value: `TOOL_EXPORT_TOKEN_TTL` (default 3600s = 1 hour)

**Key Test Scenarios for PreTester:**
1. Token generation: Length, randomness, cache storage structure
2. Token validation: Valid token, invalid token, expired token, wrong user
3. Token invalidation: Deleted after successful download
4. TTL configuration: Respects custom config value
5. Security edge cases: Concurrent downloads, replay attacks, cache driver behavior

**⚠️ SECURITY REVIEW REQUIRED:** Per CONSTITUTION, HIGH-RISK items require SecurityAuditor review before deployment. LocalTester must coordinate with SecurityAuditor before passing to DevOps.

**Execution Order:** Fourth and final task in Phase 1 pipeline. Tests can proceed immediately; implementation waits for TASK-29-3 to reach `To Develop`.

Status updated: `To Prioritize` → `To Write Tests` - Ready for PreTester
