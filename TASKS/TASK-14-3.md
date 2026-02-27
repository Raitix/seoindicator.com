# TASK-14-3: Create Issue Types Table

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `issue_types` table to store reusable templates for SEO issues, including default severity and recommendations.

## Schema Requirements
```sql
CREATE TABLE issue_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity_default ENUM('critical', 'warning', 'notice') NOT NULL,
    recommendation_template TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Acceptance Criteria
- [x] Table stores templates for various SEO issues.
- [x] Supports CRUD operations on issue types.
- [x] Default severity levels are enforced properly.

## Effort Estimate
1.5 hours.

---

## Comments
### [2026-02-20 19:56] ProjectManager: Prioritization Complete
- Priority: Medium (correct - reference data table, enables TASK-14-2)
- Risk: Low (simple lookup table, no security concerns)
- Dependencies: None (standalone reference table)
- Execution order: Can be developed in parallel with TASK-14-1
- Quick win: 1.5 hours effort
- Seeds initial issue types: meta issues, content issues, technical SEO, etc.
- Status: To Develop

### [2026-02-20 17:44] Agent: SpecSystemAnalyst
Created task for managing issue type templates.

### [2026-02-21 22:45] Developer: Implementation Complete
**Files created/modified:**
- `app/Http/Controllers/Api/V1/IssueTypeController.php` - Full CRUD controller with:
  - `index()` - List with filtering (category, severity, search), sorting, pagination
  - `show()` - Get single issue type
  - `store()` - Create new issue type (validation, duplicate check)
  - `update()` - Update issue type (validation, duplicate check)
  - `destroy()` - Delete issue type (in-use check)
  - `categories()` - Get available categories
- `routes/api.php` - Added 6 API routes under `/api/v1/issue-types`
- `database/seeders/IssueTypeSeeder.php` - 24 initial SEO issue type templates

**Database:**
- Migration already existed (`2026_02_21_222100_create_issue_types_table.php`)
- Model already existed (`IssueType.php`)
- Ran seeder: 24 issue types across 7 categories (meta, content, technical, performance, mobile, accessibility, security)

**Testing:**
- All CRUD operations verified via test script
- Index: 200, returns all 24 issue types
- Show: 200 for existing, 404 for non-existent
- Store: 201 on success, 409 on duplicate, 422 on validation error
- Update: 200 on success
- Destroy: 204 on success, 404 if not found
- Categories endpoint: 200, returns 7 categories

**API Endpoints:**
- `GET /api/v1/issue-types` - List all (with filters)
- `GET /api/v1/issue-types/categories` - Get categories
- `GET /api/v1/issue-types/{id}` - Get single
- `POST /api/v1/issue-types` - Create
- `PUT /api/v1/issue-types/{id}` - Update
- `DELETE /api/v1/issue-types/{id}` - Delete

All acceptance criteria met. Status: To Test Locally
### [2026-02-22 16:09] Agent: LocalTester - Tests Missing
Attempted: `php artisan test --filter=IssueType`
Result: No tests found.
No IssueType test files located under backend/tests.
Action: Returning to Developer to add tests for IssueType CRUD + migration + seeder validation.

