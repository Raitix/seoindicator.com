# TASK-35-7: Google OAuth Service for GBP

## STATUS
`To Prioritize`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`High`

## Description
Create GoogleOAuthService for handling Google OAuth 2.0 flow to connect Google Business Profile accounts. This is a security-sensitive task requiring careful token handling.

## Acceptance Criteria
- [ ] GoogleOAuthService created with interface
- [ ] OAuth 2.0 flow implementation:
  - Method: getAuthorizationUrl(): string
    - Generates Google OAuth URL with correct scopes
    - Includes state parameter for CSRF protection
    - Required scope: https://www.googleapis.com/auth/business.manage
  - Method: handleCallback(string $code, string $state): array
    - Exchanges authorization code for tokens
    - Validates state parameter
    - Returns access_token, refresh_token, expires_at
- [ ] Token management:
  - Method: refreshAccessToken(string $refreshToken): array
    - Refreshes expired access tokens
  - Method: storeTokens(BusinessLocation $location, array $tokens): void
    - Encrypts tokens before storage
    - Stores in gbp_tokens column (JSON)
  - Method: getValidAccessToken(BusinessLocation $location): ?string
    - Returns valid access token (refreshes if expired)
    - Returns null if not connected or refresh failed
- [ ] Disconnect functionality:
  - Method: disconnect(BusinessLocation $location): void
    - Revokes tokens with Google
    - Clears local token storage
- [ ] Token encryption using Laravel's encrypt()
- [ ] State parameter stored in session for validation
- [ ] Comprehensive error handling
- [ ] Unit tests with mocked OAuth responses
- [ ] Security review required (High Risk)

## Dependencies
- TASK-35-2 (BusinessLocation Model)

## Effort Estimate
4 hours

## Technical Notes
- Use league/oauth2-google package
- Store encrypted tokens in JSON column: {access_token, refresh_token, expires_at}
- NEVER expose tokens in API responses
- Consider token refresh scheduling for proactive refresh
- This is HIGH RISK due to OAuth security implications

---

*Created by SpecSystemAnalyst on 2026-03-08*
