# TASK-6-4: API Key Management Endpoints

## STATUS
`To Develop`

## Priority
`High`

## Risk
`Medium`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Create API endpoints for managing API keys (create, list, revoke).

## Requirements

### Endpoints
- `POST /api/v1/api-keys` - Create new API key
- `GET /api/v1/api-keys` - List user's API keys
- `GET /api/v1/api-keys/:id` - Get single API key
- `DELETE /api/v1/api-keys/:id` - Revoke API key

### Create Endpoint
- Generate key and show once
- Accept name, permissions, rate_limit, expires_at
- Return full key on creation only

### List Endpoint
- Return all keys for authenticated user
- Hide sensitive data (hash)
- Include usage statistics

### Validation
- Name required
- Valid permissions format
- Rate limit within bounds

## Acceptance Criteria
- [ ] All endpoints implemented
- [ ] Authentication required
- [ ] Input validation
- [ ] Error handling
- [ ] Response format matches spec

## Dependencies
- TASK-6-3 (Key generation and validation)

## Estimated Effort
Medium (3-5 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (MVP feature)
- Risk: Medium (new endpoints)
- Depends on TASK-6-3

### [2026-02-27 14:15] PreTester: Test Documentation and Files Created
**Test Documentation:**
- Created `TESTS/TASK-6-4.md` with comprehensive test scenarios
- Test categories: Create, List, Show, Revoke endpoints
- Security tests: Timing attacks, IDOR, mass assignment, input validation
- 63 test scenarios total

**Created Files:**
- `backend/tests/Feature/Api/V1/ApiKeyControllerTest.php` - Feature tests (63 tests)
- `backend/app/Models/ApiKey.php` - Model with relationships and scopes
- `backend/database/factories/ApiKeyFactory.php` - Factory with states (revoked, expired, etc.)
- `backend/app/Policies/ApiKeyPolicy.php` - Authorization policy
- `backend/app/Http/Requests/Api/V1/StoreApiKeyRequest.php` - Validation request
- `backend/app/Repositories/ApiKeyRepository.php` - Data access layer

**Key Implementation Notes for Developer:**
1. **Routes needed in `routes/api.php`:**
   ```php
   Route::middleware('auth:sanctum')->group(function () {
       Route::post('/api-keys', [ApiKeyController::class, 'store']);
       Route::get('/api-keys', [ApiKeyController::class, 'index']);
       Route::get('/api-keys/{id}', [ApiKeyController::class, 'show']);
       Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
   });
   ```

2. **Controller needed:** `app/Http/Controllers/Api/V1/ApiKeyController.php`
   - `store()` - Create key, show full key ONCE only
   - `index()` - List user's keys, hide `key_hash`
   - `show()` - Get single key, hide `key_hash` and full `key`
   - `destroy()` - Revoke key (set `revoked_at`)

3. **Migration needed:** `api_keys` table with:
   - `id`, `user_id`, `name`, `key_hash`, `key_prefix`
   - `permissions` (JSON), `rate_limit` (int, default 60)
   - `last_used_at`, `expires_at`, `revoked_at`, timestamps

4. **Authorization:** Use `ApiKeyPolicy` for user isolation
   - Users can only access their own keys
   - Returns 403 Forbidden for cross-user access

5. **Valid Permissions:** See `ApiKey::VALID_PERMISSIONS` constant
   - keywords:read, keywords:write
   - rankings:read
   - reports:read, reports:write
   - webhooks:read, webhooks:write

6. **Rate Limit Bounds:** MIN=1, MAX=10000, DEFAULT=60

7. **Security Considerations:**
   - Never return `key_hash` in any response
   - Return full `key` only on creation
   - Use `$this->authorize()` in controller for policy enforcement

**Status:** To Develop (63 tests pending controller implementation)
