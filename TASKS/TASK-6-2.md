# TASK-6-2: API Key Model and Repository

## Status
`To Write Tests`

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
