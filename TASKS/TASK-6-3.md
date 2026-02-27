# TASK-6-3: API Key Generation and Validation

## Status
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

### [2026-02-27 13:58] PreTester: Test Documentation Created
- Created `tests/TASK-6-3.md` with comprehensive test scenarios
- Created `backend/tests/Unit/Services/ApiKeyServiceTest.php` with 80+ test cases
- Test coverage includes:
  - Key generation (15 tests): format, prefix, hash, entropy, uniqueness
  - Hash method (10 tests): SHA-256 output, determinism, edge cases
  - Validation (15 tests): correct/incorrect keys, timing-safe comparison
  - Prefix extraction (6 tests): length, edge cases
  - Format validation (10 tests): prefix check, base64url chars
  - Security (5 tests): timing attack prevention, entropy verification, CSPRNG
  - Edge cases (4 tests): rapid generation, long inputs
  - Integration (6 tests): full cycles, storage simulation
- Key security notes for Developer:
  - Use `random_bytes()` for CSPRNG (never `rand()` or `mt_rand()`)
  - Use `hash_equals()` for timing-safe validation
  - Key format: `sk_live_` + base64url (no padding)
  - Hash: SHA-256, 64 lowercase hex chars
  - Prefix: first 20 chars for identification
- Status: To Develop
