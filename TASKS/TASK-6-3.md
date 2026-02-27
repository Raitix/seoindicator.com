# TASK-6-3: API Key Generation and Validation

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
Implement secure API key generation, hashing, and validation logic.

## Requirements

### Key Generation
```typescript
function generateApiKey(): { key: string; hash: string; prefix: string }
```
- Generate random 32-byte key
- Format: `sk_live_<base64url_encoded>`
- SHA-256 hash for storage
- Prefix for identification (first 20 chars)

### Key Validation
```typescript
function validateApiKey(key: string, storedHash: string): boolean
```
- Timing-safe comparison
- Handle revoked/expired keys

### Security
- Keys shown only once on creation
- Hash stored, never plaintext
- Secure random generation

## Acceptance Criteria
- [ ] Key generation working
- [ ] Hashing implemented correctly
- [ ] Validation working
- [ ] Timing-safe comparison
- [ ] Unit tests for all functions

## Dependencies
- TASK-6-2 (Model and Repository)

## Estimated Effort
Medium (2-4 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (core security feature)
- Risk: Medium (security-sensitive)
- Depends on TASK-6-2
