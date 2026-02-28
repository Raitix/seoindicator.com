# TASK-SEC-7-10-4: Add IDOR Protection to summary Method

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Security Classification
**CRITICAL** - IDOR Vulnerability Fix

## Parent
- Specification: [SPEC-SEC-7-10](../SPECIFICATIONS/SPEC-SEC-7-10.md)
- Feature: [FEAT-007](../FEATURES/FEAT-007.md)

## Description
Implement authorization check in the `summary` method to prevent Insecure Direct Object Reference (IDOR) attacks. Users must only access SERP feature summaries for sites they own.

## Scope
- File: `app/Http/Controllers/Api/V1/SerpFeatureController.php`
- Method: `summary(Request $request, int $siteId)`

## Implementation Details

### Current State (VULNERABLE)
```php
public function summary(Request $request, int $siteId)
{
    $site = Site::findOrFail($siteId);
    // No authorization check - IDOR vulnerability
    // ... existing logic
}
```

### Required State (SECURE)
```php
public function summary(Request $request, int $siteId)
{
    $site = Site::findOrFail($siteId);
    
    // IDOR protection
    if ($site->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access to this site.');
    }
    
    // ... existing logic
}
```

## Acceptance Criteria
- [ ] Authorization check verifies `site.user_id === auth()->id()`
- [ ] Returns 403 Forbidden for unauthorized access
- [ ] Returns 200 OK for authorized access (user's own site)
- [ ] Error message does not leak information about resource existence
- [ ] Does not break existing functionality for legitimate users

## Dependencies
- TASK-SEC-7-10-1 (Routes must be in auth middleware first)

## Effort Estimate
`Small` (30 minutes)

## Security Impact
- Fixes IDOR vulnerability
- Prevents cross-user data access
- Addresses OWASP A01:2021 (Broken Access Control)

## Testing Requirements
- Test SEC-004: Authenticated user accessing own site summary → 200
- Test SEC-005: User accessing another user's site summary → 403
- Test SEC-006: Non-existent site → 404

---

## Comments
_Agent communication log_

### [2026-02-28 12:10] Agent: SpecSystemAnalyst
- Task created from SPEC-SEC-7-10
- IDOR protection for summary endpoint
- Requires TASK-SEC-7-10-1 to be completed first
- Status: To Prioritize

### [2026-02-28 12:13] Agent: ProjectManager
- **Priority: HIGH** (CRITICAL SECURITY - IDOR vulnerability)
- **Execution Order: 2** (Parallel with Tasks 2, 3 after Task 1)
- **Dependencies: TASK-SEC-7-10-1**
- Can be developed in parallel with TASK-SEC-7-10-2 and TASK-SEC-7-10-3
- Status: To Write Tests
