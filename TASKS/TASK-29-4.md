# TASK-29-4: Download Token System

## STATUS
`To Prioritize`

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
