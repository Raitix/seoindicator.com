# TASK-6-2: API Key Model and Repository

## STATUS
`To Develop`

## Priority
`High`

## Risk
`Low`

## Specification
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Description
Create the API Key model and repository layer for database operations.

## Requirements

### Model
- TypeScript interface for ApiKey entity
- Type-safe property definitions
- Relationship to User model

### Repository Methods
- `create(data: CreateApiKeyData): Promise<ApiKey>`
- `findById(id: number): Promise<ApiKey | null>`
- `findByHash(keyHash: string): Promise<ApiKey | null>`
- `findByPrefix(prefix: string): Promise<ApiKey[]>`
- `findByUserId(userId: number): Promise<ApiKey[]>`
- `update(id: number, data: Partial<ApiKey>): Promise<ApiKey>`
- `revoke(id: number): Promise<void>`
- `delete(id: number): Promise<void>`

## Acceptance Criteria
- [ ] Model interface defined
- [ ] Repository class implemented
- [ ] All CRUD operations working
- [ ] Type-safe implementation

## Dependencies
- TASK-6-1 (Database schema)

## Estimated Effort
Medium (2-4 hours)

---

## Comments

### [2026-02-27 12:25] ProjectManager: Task Created
- Priority: High (foundation task)
- Risk: Low (no external dependencies)
- Depends on TASK-6-1

### [2026-02-28 11:35] PreTester: Tests Completed
- Created TEST-6-2.md test documentation
- Created ApiKeyRepositoryTest.php with 56 tests (101 assertions)
- All tests passing
- Model and repository already implemented (ApiKey.php, ApiKeyRepository.php)
- Coverage: Create, Find, User operations, Update, Revoke, Delete, Validation, Edge cases, Security
- Test categories: CR (Create), FN (Find), UO (User Ops), UP (Update), RV (Revoke), DL (Delete), VS (Validation), EC (Edge Cases), SC (Security)
