# TASK-8-1: Create Projects Table

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the projects table for organizing multi-site data by users. This table stores basic project information and links each project to a user.

## Schema Requirements
```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    notes TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_archived (archived_at)
);
```

## Acceptance Criteria
- [ ] Migration file for creating `projects` table is implemented.
- [ ] Project data includes user-level constraints (linked to user accounts).
- [ ] Foreign key constraints tested for deleting/cascading effects on projects.
- [ ] Indexes implemented for `user_id` and `archived_at` fields.
- [ ] Seeder file for inserting initial test data.

## Dependencies
- Requires user authentication system (pre-existing).

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:15] Agent: SpecSystemAnalyst
Task created to define and implement the core `projects` table for multi-site data organization in the database.

### [2026-02-26 18:40] Agent: ProjectManager
**Prioritization Decision:**
- **Priority: High** (confirmed) - Foundation task for FEAT-008 Multi-Site Project Management
- **Risk: Low** (confirmed) - Simple table creation with no destructive operations

**Dependency Analysis:**
- This is the FIRST task in the TASK-8-* chain (projects table creation)
- All other TASK-8-* tasks (2-10) depend directly or indirectly on this task
- TASK-8-2 (Modify Tables), TASK-8-3 (Data Migration), TASK-8-4 (Middleware) all require the projects table

**Execution Order:**
1. TASK-8-1 (this task) → Foundation
2. TASK-8-2 (Modify Tables) → Schema changes
3. TASK-8-3 (Data Migration) → Legacy data handling
4. TASK-8-4 (Middleware) → Context injection
5. TASK-8-5 (Global Scope) → Data isolation
6. TASK-8-6 (Policies) → Authorization
7. TASK-8-7 (CRUD Endpoints) → API layer
8. TASK-8-8 (Frontend) → UI components
9. TASK-8-9 (Unit Tests) → Backend testing
10. TASK-8-10 (Integration Tests) → E2E testing

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-26 19:42] Agent: PreTester
**Test Documentation Created:** `TESTS/TEST-8-1.md`

**Coverage Summary:**
- **68 test scenarios** across 8 categories
- Migration Structure: 13 tests (MT-001 to MT-013)
- Foreign Key Constraints: 7 tests (FK-001 to FK-007)
- Index Verification: 6 tests (IX-001 to IX-006)
- Model Behavior: 10 tests (MD-001 to MD-010)
- Seeder Functionality: 7 tests (SD-001 to SD-007)
- Edge Cases: 15 tests (EC-001 to EC-015)
- Data Integrity: 5 tests (DI-001 to DI-005)
- Query Performance: 5 tests (QP-001 to QP-005)

**Critical Tests:**
- FK-004: CASCADE delete when user deleted (data integrity)
- FK-005: Insert with non-existent user_id fails (constraint enforcement)
- IX-003/IX-004: Index usage for user_id and archived_at queries

**Business Rule Questions Raised:**
1. Should multiple `is_default=true` projects be allowed per user?
2. Should future `archived_at` dates be permitted?

**Status changed:** `To Write Tests` → `To Develop`