# TASK-12-4: Implement OAuth Service for GSC

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`High`

## Description
Develop `OAuthService` to handle Google OAuth 2.0 flow for Search Console API access. Manage authorization codes, token exchange, refresh, and secure storage.

## Responsibilities
1. **Authorization Flow**
   - Generate OAuth authorization URL.
   - Handle callback and exchange authorization code for tokens.
   - Store encrypted access and refresh tokens.

2. **Token Management**
   - Auto-refresh expired tokens using refresh token.
   - Validate token expiry before API calls.

3. **Security**
   - Encrypt tokens at rest.
   - Validate redirect URIs.

## Code Example
```php
class OAuthService
{
    public function getAuthorizationUrl(): string;
    public function handleCallback(string $code): GSCConnection;
    public function refreshAccessToken(GSCConnection $connection): void;
    public function revokeAccess(GSCConnection $connection): void;
}
```

## Acceptance Criteria
- [ ] OAuth flow implemented with Google API credentials.
- [ ] Tokens encrypted and stored securely.
- [ ] Automatic token refresh on expiry.
- [ ] Unit tests cover all token handling scenarios.

## Dependencies
- TASK-12-1: `gsc_connections` table must exist.

## Effort Estimate
4-6 hours.

---

## Comments
### [2026-02-20 19:01] ProjectManager: Prioritization Complete
- Priority: High (correct - critical OAuth flow for GSC access)
- Risk: High (OAuth security, token encryption, redirect validation)
- Dependencies: TASK-12-1 (gsc_connections table)
- Security review recommended before deployment
- Execution order: After database layer (TASK-12-1, 12-2, 12-3)
- Status: To Develop

### [2026-02-20 17:35] Agent: SpecSystemAnalyst
Backend service for secure GSC OAuth integration.