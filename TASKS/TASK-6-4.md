# TASK-6-4: API Key Management Endpoints

## Status
`To Write Tests`

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
