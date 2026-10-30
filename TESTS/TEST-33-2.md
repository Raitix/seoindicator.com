# TEST-33-2: Public Audit Views Table Tests

## Task Reference
- **Task:** TASK-33-2
- **Title:** Create Public Audit Views Table
- **Status:** Tests Coverage In Progress
- **Agent:** PreTester
- **Date:** 2026-03-05

---

## Overview

This document describes the test suite for the `public_audit_views` table, which tracks analytics for public audit page views. The table enables measuring the reach and effectiveness of shared audits.

---

## Test Files

| File | Location | Tests | Description |
|------|----------|-------|-------------|
| CreatePublicAuditViewsTableTest.php | `backend/tests/Unit/Migrations/` | 52 | Migration structure, indexes, foreign keys |
| PublicAuditViewTest.php | `backend/tests/Unit/Models/` | 52 | Model attributes, relationships, scopes |
| PublicAuditViewFactory.php | `backend/database/factories/` | - | Factory with states |

---

## Database Schema

```sql
CREATE TABLE public_audit_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    public_hash VARCHAR(32) NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    referrer VARCHAR(500) NULL,
    ip_hash VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    country_code VARCHAR(2) NULL,
    is_lead_capture TINYINT(1) DEFAULT 0,
    lead_email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_id (audit_id),
    INDEX idx_public_hash (public_hash),
    INDEX idx_viewed_at (viewed_at),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);
```

---

## Test Scenarios

### Migration Structure Tests (MS)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MS-001 | Table exists after migration | Table `public_audit_views` exists |
| MS-002 | All required columns exist | 11 columns: id, audit_id, public_hash, viewed_at, referrer, ip_hash, user_agent, country_code, is_lead_capture, lead_email, created_at |
| MS-003 | id is auto-increment primary key | BIGINT UNSIGNED, AUTO_INCREMENT |
| MS-004 | audit_id is BIGINT UNSIGNED NOT NULL | Correct type and nullable |
| MS-005 | public_hash is VARCHAR(32) NOT NULL | Correct type and length |
| MS-006 | viewed_at is TIMESTAMP with default | DEFAULT CURRENT_TIMESTAMP |
| MS-007 | referrer is VARCHAR(500) NULL | Correct type and nullable |
| MS-008 | ip_hash is VARCHAR(64) NULL | SHA-256 hash storage |
| MS-009 | user_agent is VARCHAR(500) NULL | Correct type and nullable |
| MS-010 | country_code is VARCHAR(2) NULL | ISO 2-char code |
| MS-011 | is_lead_capture is TINYINT(1) DEFAULT 0 | Boolean with default |
| MS-012 | lead_email is VARCHAR(255) NULL | Correct type and nullable |
| MS-013 | created_at is TIMESTAMP with default | DEFAULT CURRENT_TIMESTAMP |

### Index Tests (IX)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IX-001 | Index on audit_id | EXISTS for query performance |
| IX-002 | Index on public_hash | EXISTS for lookup performance |
| IX-003 | Index on viewed_at | EXISTS for date range queries |

### Foreign Key Tests (FK)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| FK-001 | Foreign key on audit_id | References audits.id |
| FK-002 | Foreign key uses CASCADE delete | DELETE_RULE = CASCADE |
| FK-003 | Cascade delete works | View deleted when audit deleted |

### Migration Data Tests (MD)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MD-001 | Insert with all fields | Record created successfully |
| MD-002 | Insert with minimal fields | Record created with defaults |
| MD-003 | public_hash is 32 characters | Length = 32 |
| MD-004 | country_code is 2 characters | Length = 2 |
| MD-005 | ip_hash is 64 characters | SHA-256 length = 64 |

### Migration Reversibility Tests (MR)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MR-001 | Migration can be rolled back | Table dropped |
| MR-002 | Migration can be re-run after rollback | Table recreated |

### Edge Cases Tests (EC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EC-001 | Long referrer URL (500 chars) | Stored successfully |
| EC-002 | Long user agent (500 chars) | Stored successfully |
| EC-003 | Multiple views for same audit | All stored |
| EC-004 | Multiple views for same hash | All stored (no unique constraint) |
| EC-005 | viewed_at in the past | Stored correctly |
| EC-006 | is_lead_capture true/false | Both values work |
| EC-007 | lead_email null or value | Both work |

### Performance Tests (PF)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| PF-001 | Index on audit_id improves query | Query < 100ms |
| PF-002 | Index on public_hash improves query | Query < 100ms |
| PF-003 | Index on viewed_at enables date queries | Query < 100ms |

### Security Tests (SC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SC-001 | Invalid audit_id rejected | FK constraint fails |
| SC-002 | SQL injection in public_hash | Prevented/stored safely |
| SC-003 | XSS in referrer | Stored safely (escape on output) |
| SC-004 | XSS in user_agent | Stored safely |
| SC-005 | XSS in lead_email | Stored safely |

---

### Model Tests (MI/MF/MC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MI-001 | Model instantiated | PublicAuditView instance |
| MI-002 | Correct table name | `public_audit_views` |
| MI-003 | Correct primary key | `id` |
| MI-004 | Auto-incrementing | TRUE |
| MI-005 | Key type | `int` |
| MI-006 | Uses timestamps | TRUE |
| MF-001-010 | Fillable attributes | All 9 fillables work |
| MC-001 | is_lead_capture cast | boolean |
| MC-002 | viewed_at cast | datetime |
| MC-003 | created_at cast | datetime |

### Relationship Tests (RE)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| RE-001 | Belongs to Audit | Audit relationship exists |
| RE-002 | Correct audit returned | audit_id matches |
| RE-003 | Eager loading works | with('audit') works |

### Model Scope Tests (SQ)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| SQ-001 | forAudit($id) scope | Filters by audit_id |
| SQ-002 | forHash($hash) scope | Filters by public_hash |
| SQ-003 | leadCaptures() scope | is_lead_capture = true |
| SQ-004 | withLeadEmail() scope | lead_email IS NOT NULL |
| SQ-005 | viewedBetween($start, $end) | Date range filter |
| SQ-006 | fromCountry($code) | Filters by country_code |
| SQ-007 | recent($days) | Last N days filter |

### Factory Tests (FA)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| FA-001 | Factory creates valid model | All required fields set |
| FA-002 | forAudit($audit) | audit_id set correctly |
| FA-003 | leadCapture() | is_lead_capture = true, email set |
| FA-004 | withReferrer() | referrer populated |
| FA-005 | withCountry($code) | country_code set |

### Model Edge Cases (EC)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| EC-001 | null referrer | Handled correctly |
| EC-002 | null ip_hash | Handled correctly |
| EC-003 | null user_agent | Handled correctly |
| EC-004 | null country_code | Handled correctly |
| EC-005 | null lead_email | Handled correctly |
| EC-006 | empty referrer string | Stored as empty string |
| EC-007 | Multiple views same hash | Allowed (no unique) |
| EC-008 | Unicode in user_agent | Stored correctly |

### Integration Tests (IN)

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| IN-001 | Cascade delete on audit delete | View deleted |
| IN-002 | Multiple views cascade delete | All deleted |
| IN-003 | Query by audit relationship | Works via audit->publicAuditViews |
| IN-004 | View count per audit | Accurate count |

---

## Mock Data Patterns

### Standard View
```php
PublicAuditView::factory()->create([
    'audit_id' => $audit->id,
    'public_hash' => 'aBcD1234EfGh5678IjKl9012MnOp3456',
]);
```

### Lead Capture View
```php
PublicAuditView::factory()->leadCapture('user@example.com')->create([
    'audit_id' => $audit->id,
]);
```

### View with Full Analytics
```php
PublicAuditView::factory()->withFullAnalytics()->create([
    'audit_id' => $audit->id,
]);
```

### Views for Date Range Query
```php
PublicAuditView::factory()->viewedDaysAgo(5)->create();
PublicAuditView::factory()->viewedDaysAgo(10)->create();
```

---

## Factory States

| State | Description |
|-------|-------------|
| `forAudit($audit)` | Associate with specific audit |
| `withHash($hash)` | Set specific public hash |
| `withReferrer()` | Add random referrer URL |
| `withSpecificReferrer($url)` | Set specific referrer |
| `withIpHash($ip)` | Hash and store IP address |
| `withUserAgent($ua)` | Set specific user agent |
| `withCountry($code)` | Set country code |
| `leadCapture($email)` | Mark as lead capture with email |
| `viewedAt($timestamp)` | Set viewed_at timestamp |
| `viewedDaysAgo($days)` | Set viewed_at in past |
| `fromGoogle()` | Set Google search referrer |
| `directVisit()` | No referrer (direct) |
| `chromeBrowser()` | Chrome user agent |
| `safariBrowser()` | Safari user agent |
| `mobileDevice()` | Mobile user agent |
| `withFullAnalytics()` | All analytics fields populated |

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Migration Structure | 100% columns verified | 13 tests |
| Indexes | 100% indexes verified | 3 tests |
| Foreign Keys | 100% FK verified | 3 tests |
| Model Fillable | 100% fillables tested | 10 tests |
| Model Casts | 100% casts tested | 3 tests |
| Relationships | 100% relationships tested | 3 tests |
| Scopes | All scopes tested | 7 tests |
| Edge Cases | Critical paths | 14 tests |
| Security | XSS/SQL injection | 5 tests |
| Performance | Query performance | 3 tests |
| **Total** | **Comprehensive** | **104+ tests** |

---

## Test Execution

```bash
# Run all migration tests
php artisan test --filter=CreatePublicAuditViewsTableTest

# Run all model tests
php artisan test --filter=PublicAuditViewTest

# Run all public audit view tests
php artisan test --filter="PublicAuditView"

# Run with coverage
php artisan test --filter="PublicAuditView" --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-33-1 | Done ✅ | Visibility columns exist in audits table |
| Audit model | Available | Factory exists |
| Audit::factory() | Available | Used in tests |

---

## Notes for Developer

### Implementation Order
1. Create migration `xxxx_create_public_audit_views_table.php`
2. Create model `PublicAuditView.php`
3. Add relationship to Audit model (hasMany publicAuditViews)
4. Run tests

### Key Implementation Details
- **No unique constraint** on public_hash - multiple views allowed per hash
- **CASCADE delete** on audit_id foreign key
- **IP hashing**: Use `hash('sha256', $ip . config('app.key'))` for privacy
- **referrer max 500 chars** - truncate before storage if longer
- **country_code**: Store ISO 2-char uppercase

### Model Scopes to Implement
```php
public function scopeForAudit($query, int $auditId)
public function scopeForHash($query, string $hash)
public function scopeLeadCaptures($query)
public function scopeWithLeadEmail($query)
public function scopeViewedBetween($query, $start, $end)
public function scopeFromCountry($query, string $code)
public function scopeRecent($query, int $days = 7)
```

### Audit Model Relationship
Add to Audit model:
```php
public function publicAuditViews(): HasMany
{
    return $this->hasMany(PublicAuditView::class);
}
```

---

## Acceptance Criteria Verification

| AC | Description | Test Coverage |
|----|-------------|---------------|
| AC-1 | Migration creates `public_audit_views` table | MS-001 |
| AC-2 | All 11 columns created with correct types | MS-002 to MS-013 |
| AC-3 | Foreign key CASCADE delete on audit_id | FK-001, FK-002, FK-003 |
| AC-4 | Indexes on audit_id, public_hash, viewed_at | IX-001, IX-002, IX-003 |
| AC-5 | PublicAuditView model with relationships | RE-001, RE-002, RE-003 |
| AC-6 | Factory created for testing | FA-001 to FA-005 |
| AC-7 | Unit tests verify structure and relationships | All tests |

---

## Comments

### [2026-03-05 23:50 GMT+2] PreTester - Tests Complete
**Status:** Tests Coverage In Progress → To Develop

**Files Created:**
- `backend/tests/Unit/Migrations/CreatePublicAuditViewsTableTest.php` (52 tests)
- `backend/tests/Unit/Models/PublicAuditViewTest.php` (52 tests)
- `backend/database/factories/PublicAuditViewFactory.php` (16 factory states)
- `PROJECT-SEOINDICATOR/TESTS/TEST-33-2.md` (this file)

**Test Summary:**
- **Migration Tests:** 52 tests covering structure, indexes, foreign keys, data, reversibility, edge cases, performance, security
- **Model Tests:** 52 tests covering instantiation, fillable, casts, relationships, scopes, factory, edge cases, integration
- **Factory:** 16 states for various test scenarios

**Key Findings:**
- No unique constraint on public_hash (multiple views per hash allowed)
- IP addresses stored as SHA-256 hash + app.key salt for privacy
- referrer and user_agent limited to 500 chars
- country_code is 2-char ISO format
- Cascade delete ensures clean data when audits are deleted

**TDD Pattern:** Tests will fail until migration and model are created.

**Handoff to Developer:** Ready for implementation. All acceptance criteria are testable.
