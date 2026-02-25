# TEST-14-4: Documentation Consistency for Audits Schema

## Task Reference
- Task: [TASK-14-4](../TASKS/TASK-14-4.md)

## Scope
Validate that DOCUMENTATION.md properly reflects the audits table schema (and related audit tables) that exist in migrations.

## Acceptance Criteria Under Test
1. DOCUMENTATION.md includes `audits` table documentation
2. DOCUMENTATION.md includes `audit_issues` table documentation
3. DOCUMENTATION.md includes `issue_types` table documentation
4. Documentation reflects `site_id` (not `project_id`) for audits
5. All documented fields match migration schema

## Test Cases

### TC-1: Audits Table Documentation Presence
**Given:** DOCUMENTATION.md exists
**When:** Searching for audits table documentation
**Then:** Section exists describing audits table with correct schema

**Expected Documentation:**
```
### Audits Table (FEAT-014)
- Stores metadata for SEO site audits
- Columns: id, site_id, status (enum), pages_crawled, issues_found, started_at, completed_at, timestamps
- Foreign Key: site_id → sites(id) ON DELETE CASCADE
- Indexes: (site_id, status), status, completed_at
```

### TC-2: Audit Issues Table Documentation Presence
**Given:** DOCUMENTATION.md exists
**When:** Searching for audit_issues table documentation
**Then:** Section exists describing audit_issues table with correct schema

**Expected Documentation:**
```
### Audit Issues Table (FEAT-014)
- Stores detailed issue records for audits
- Columns: id, audit_id, url, issue_type_id, severity (enum), description, recommendation, status (enum), timestamps
- Foreign Keys: audit_id → audits(id) ON DELETE CASCADE, issue_type_id → issue_types(id) ON DELETE CASCADE
- Indexes: (audit_id, severity), severity, status, (audit_id, status), issue_type_id
```

### TC-3: Issue Types Table Documentation Presence
**Given:** DOCUMENTATION.md exists
**When:** Searching for issue_types table documentation
**Then:** Section exists describing issue_types table with correct schema

**Expected Documentation:**
```
### Issue Types Table (FEAT-014)
- Stores issue type definitions for audits
- Columns: id, category, name, description, severity_default (enum), recommendation_template, timestamps
```

### TC-4: Site ID vs Project ID Consistency
**Given:** Audits table documentation exists
**When:** Checking the foreign key column
**Then:** Documentation shows `site_id` (NOT `project_id`)

**Validation:** No occurrence of `project_id` in audits documentation section.

### TC-5: Status Enum Values Correct
**Given:** Audits table documentation exists
**When:** Checking status enum values
**Then:** Documentation shows: pending, running, completed, failed

**Given:** Audit issues table documentation exists
**When:** Checking severity enum values
**Then:** Documentation shows: critical, warning, notice

**Given:** Audit issues table documentation exists
**When:** Checking status enum values
**Then:** Documentation shows: open, fixed, ignored

## Test Implementation

### Automated Validation Script
Location: `backend/tests/Feature/DocumentationConsistencyTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class DocumentationConsistencyTest extends TestCase
{
    private string $docPath;
    private string $migrationsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->docPath = base_path('../DOCUMENTATION.md');
        $this->migrationsPath = database_path('migrations');
    }

    /** @test */
    public function documentation_includes_audits_table()
    {
        $doc = File::get($this->docPath);
        $this->assertStringContainsString('audits', $doc, 'DOCUMENTATION.md should mention audits table');
        $this->assertStringContainsString('site_id', $doc, 'Audits documentation should use site_id');
    }

    /** @test */
    public function documentation_uses_site_id_not_project_id_for_audits()
    {
        $doc = File::get($this->docPath);
        
        // Find audits section
        if (preg_match('/audits.*?(?=###|\Z)/s', $doc, $matches)) {
            $auditsSection = $matches[0];
            $this->assertStringContainsString('site_id', $auditsSection);
            $this->assertStringNotContainsString('project_id', $auditsSection,
                'Audits documentation should use site_id, not project_id');
        }
    }

    /** @test */
    public function documentation_includes_audit_issues_table()
    {
        $doc = File::get($this->docPath);
        $this->assertStringContainsString('audit_issues', $doc, 
            'DOCUMENTATION.md should mention audit_issues table');
    }

    /** @test */
    public function documentation_includes_issue_types_table()
    {
        $doc = File::get($this->docPath);
        $this->assertStringContainsString('issue_types', $doc, 
            'DOCUMENTATION.md should mention issue_types table');
    }

    /** @test */
    public function audits_status_enum_documented()
    {
        $doc = File::get($this->docPath);
        $expectedStatuses = ['pending', 'running', 'completed', 'failed'];
        
        foreach ($expectedStatuses as $status) {
            $this->assertStringContainsString($status, $doc,
                "DOCUMENTATION.md should document audits status: {$status}");
        }
    }

    /** @test */
    public function audit_issues_severity_enum_documented()
    {
        $doc = File::get($this->docPath);
        $expectedSeverities = ['critical', 'warning', 'notice'];
        
        foreach ($expectedSeverities as $severity) {
            $this->assertStringContainsString($severity, $doc,
                "DOCUMENTATION.md should document audit_issues severity: {$severity}");
        }
    }
}
```

## Execution Steps

### Pre-Requisites
1. DOCUMENTATION.md exists at project root
2. Migrations exist for audits, audit_issues, issue_types

### Run Tests
```bash
cd backend
php artisan test --filter=DocumentationConsistencyTest
```

### Expected Results
```
PASS  Tests\Feature\DocumentationConsistencyTest
✓ documentation includes audits table
✓ documentation uses site id not project id for audits
✓ documentation includes audit issues table
✓ documentation includes issue types table
✓ audits status enum documented
✓ audit issues severity enum documented

Tests: 6 passed (12 assertions)
```

## Status
- [ ] Test documentation written (this file)
- [ ] PHPUnit test class implemented
- [ ] Tests passing
- [ ] DOCUMENTATION.md updated to pass tests

## Notes
- This is a documentation consistency test - validates DOCUMENTATION.md matches migrations
- Developer will update DOCUMENTATION.md to pass these tests
- Tests should be added to CI pipeline for ongoing consistency validation