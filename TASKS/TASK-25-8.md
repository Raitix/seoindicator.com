# TASK-25-8: Implement GoogleAuthController

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Specification: [SPEC-025](../SPECIFICATIONS/SPEC-025.md)
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)

---

## Description
Create the GoogleAuthController with endpoints to initiate Google OAuth and handle the callback. This provides the API surface for Google sign-in.

---

## Acceptance Criteria
- [ ] GoogleAuthController created
- [ ] POST `/api/v1/auth/google` endpoint
  - Returns redirect URL for Google OAuth
  - Includes state parameter for CSRF
- [ ] POST `/api/v1/auth/google/callback` endpoint
  - Accepts `code` and `state` parameters
  - Returns user data + Sanctum token
  - Includes `is_new_user` flag
- [ ] Rate limiting on callback endpoint
- [ ] Proper error responses for:
  - User denied access
  - Invalid state
  - OAuth errors
- [ ] Returns user avatar URL from Google

---

## Dependencies
- TASK-25-7 (GoogleOAuthService)

---

## Effort Estimate
`Small` (2-3 hours)

---

## Technical Notes
```php
class GoogleAuthController
{
    public function redirect(GoogleOAuthService $service)
    {
        return response()->json([
            'redirect_url' => $service->getRedirectUrl(),
        ]);
    }

    public function callback(Request $request, GoogleOAuthService $service)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        $result = $service->handleCallback($validated['code'], $validated['state']);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
```

---

## Comments
_Agent communication log_

### [2026-02-28 20:55 GMT+2] Agent: SpecSystemAnalyst
Created task as part of SPEC-025 breakdown (Phase 2: Google OAuth).

---
*Created by SpecSystemAnalyst*
