# TASK-23-1: Backlinks Database Migrations

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

## Type
Database

## Description
Create database migrations for backlinks, referring_domains, anchor_texts, and backlink_snapshots tables. Foundation for storing backlink profile data.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)

---

## Acceptance Criteria
- [ ] Migration creates `backlinks` table with all required fields
- [ ] Migration creates `referring_domains` table with unique constraint
- [ ] Migration creates `anchor_texts` table with indexes
- [ ] Migration creates `backlink_snapshots` table for historical tracking
- [ ] All foreign keys reference `projects.id` with CASCADE delete
- [ ] Indexes created for query optimization
- [ ] Migration rollback drops all tables correctly
- [ ] SQLite-compatible for testing

---

## Technical Details

### Tables to Create

**backlinks:**
```sql
CREATE TABLE backlinks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    source_url VARCHAR(2083) NOT NULL,
    source_domain VARCHAR(255) NOT NULL,
    target_url VARCHAR(2083) NOT NULL,
    anchor_text VARCHAR(500) NULL,
    link_type ENUM('dofollow', 'nofollow', 'unknown') DEFAULT 'unknown',
    first_seen_at TIMESTAMP NULL,
    last_checked_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_domain (project_id, source_domain),
    INDEX idx_source_url (source_url(255)),
    INDEX idx_target_url (target_url(255)),
    INDEX idx_is_active (is_active)
);
```

**referring_domains:**
```sql
CREATE TABLE referring_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    backlinks_count INT UNSIGNED DEFAULT 0,
    domain_trust_score TINYINT UNSIGNED DEFAULT 0,
    first_seen_at TIMESTAMP NULL,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_domain (project_id, domain),
    INDEX idx_backlinks_count (backlinks_count DESC)
);
```

**anchor_texts:**
```sql
CREATE TABLE anchor_texts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    anchor_text VARCHAR(500) NOT NULL,
    backlinks_count INT UNSIGNED DEFAULT 0,
    is_branded TINYINT(1) DEFAULT 0,
    is_exact_match TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_anchor (project_id, anchor_text(255)),
    INDEX idx_branded (is_branded)
);
```

**backlink_snapshots:**
```sql
CREATE TABLE backlink_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    total_backlinks INT UNSIGNED DEFAULT 0,
    referring_domains_count INT UNSIGNED DEFAULT 0,
    dofollow_count INT UNSIGNED DEFAULT 0,
    nofollow_count INT UNSIGNED DEFAULT 0,
    avg_domain_trust DECIMAL(5,2) DEFAULT 0,
    anchor_diversity_score DECIMAL(5,2) DEFAULT 0,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_project_date (project_id, snapshot_date),
    INDEX idx_snapshot_date (snapshot_date)
);
```

---

## Dependencies
- TASK-8-1 (Projects Table)

## Effort Estimate
`Medium` (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 22:15] Agent: PreTester
**Tests Coverage Complete**

Created comprehensive test documentation and test file for all 4 backlinks tables:

| Deliverable | Location | Description |
|-------------|----------|-------------|
| Test Documentation | `TESTS/TEST-23-1.md` | 80+ test scenarios covering all 4 tables |
| Test File | `backend/tests/Feature/Migrations/CreateBacklinksTablesTest.php` | 55 test methods |

**Test Coverage:**
- **backlinks table:** 15 tests (schema, indexes, FK, enums, security)
- **referring_domains table:** 10 tests (schema, unique constraint, cascade)
- **anchor_texts table:** 8 tests (schema, flags, cascade)
- **backlink_snapshots table:** 10 tests (schema, decimal precision, unique date)
- **Rollback tests:** 2 tests (rollback all, re-run)
- **Security tests:** 4 tests (SQL injection, XSS, unicode)

**Key Test Scenarios:**
- Foreign key CASCADE delete when project is deleted
- Unique constraints on (project_id, domain) and (project_id, snapshot_date)
- DECIMAL(5,2) precision for avg_domain_trust and anchor_diversity_score
- ENUM validation for link_type (dofollow, nofollow, unknown)
- Default values for all numeric and boolean fields
- SQL injection and XSS protection

**Handoff to Developer:**
Ready for migration implementation. See `TESTS/TEST-23-1.md` for full test scenarios.

### [2026-02-28 21:03] Agent: SpecSystemAnalyst
Created task for database migrations. Foundation task for FEAT-023 Backlinks Indicator.

### [2026-02-28 21:03] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `High` — Foundation task required before models (TASK-23-2) and services (TASK-23-3)
- **Risk**: `Medium` — Non-destructive migrations, rollback methods required
- **Execution Order**: 1st in SPEC-023 task chain (no dependencies)

**Dependency Chain for SPEC-023:**
```
TASK-23-1 (Migrations) → TASK-23-2 (Models) → TASK-23-3 (GSC Service)
```

All 3 tasks prioritized `High` as they are essential for the Backlinks Indicator MVP feature.

Handing off to PreTester for test design.
