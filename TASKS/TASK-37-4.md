# TASK-37-4: Graceful Token Expiry Handling

## STATUS
`To Prioritize`

## PRIORITY
`Critical`

## RISK
`High`

## Specification
SPEC-037

## Description
Implement graceful token expiry handling throughout the application. When any API call returns 401, the application should clear the invalid session and redirect to login with a user-friendly message.

## Problem
1. Multiple endpoints can return 401 (expired token)
2. No centralized handling of 401 responses
3. User sees raw error messages instead of clean re-auth flow

## Solution
1. Add response interceptor for 401 errors in API client
2. Clear localStorage on 401
3. Redirect to login with session expiry message
4. Store intended destination for post-login redirect

## Acceptance Criteria
- [ ] 401 responses trigger automatic logout
- [ ] localStorage cleared on any 401 response
- [ ] User redirected to login page
- [ ] Current URL saved as redirect target
- [ ] User-friendly "session expired" message shown
- [ ] No raw error messages for auth failures
- [ ] Unit tests for 401 handling

## Technical Details

### Files to Modify
- `frontend/lib/api/client.ts` - Add response interceptor
- `frontend/lib/contexts/AuthContext.tsx` - Clear state on 401

### Code Changes
```typescript
// In API client
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Clear auth state
      localStorage.removeItem('seoindicator_token');
      localStorage.removeItem('seoindicator_user');
      
      // Store redirect target
      const currentPath = window.location.pathname;
      if (currentPath !== '/login') {
        localStorage.setItem('seoindicator_redirect', currentPath);
      }
      
      // Redirect to login
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

## Dependencies
- TASK-37-1 (Token validation)

## Effort Estimate
`Small` (1-2 hours)

## Related Issues
- FEAT-037
- SPEC-037
