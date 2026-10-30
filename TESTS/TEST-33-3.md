# TEST-33-3: Badge Impressions Table Tests

## Task Reference
- **Task:** TASK-33-3
- **Description:** Create Badge Impressions Table
- **Status:** To Write Tests
- **Priority:** High
- **Risk:** Low

## Overview

Test coverage for the `badge_impressions` table migration, BadgeImpression model, and factory. This table tracks analytics for embedded badge views and clicks on third-party sites.

## Test Files

| File | Type | Tests |
|------|------|-------|
| `backend/tests/Unit/Migrations/CreateBadgeImpressionsTableTest.php` | Migration | 54 |
| `backend/tests/Unit/Models/BadgeImpressionTest.php` | Model | 52 |
| `backend/database/factories/BadgeImpressionFactory.php` | Factory | N/A |
| **Total** | | **106+** |

## Database Schema

```sql
CREATE TABLE badge_impressions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    public_hash VARCHAR(32) NOT NULL,
    referrer_domain VARCHAR(255) NULL,
    impression_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    clicked_at TIMESTAMP NULL,
    click_target VARCHAR(10) NULL,
    ip_hash VARCHAR(64) NULL,
    
    INDEX idx_audit_id (audit_id),
    INDEX idx_public_hash (public_hash),
    INDEX idx_impression_at (impression_at),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);
```

## Test Scenarios

### Migration Tests (MS) - 54 tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MS-001 | Table `badge_impressions` exists after migration | Table exists |
| MS-002 | Column `id` exists with correct type | BIGINT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT |
| MS-003 | Column `audit_id` exists with correct type | BIGINT UNSIGNED, NOT NULL |
| MS-004 | Column `public_hash` exists with correct type | VARCHAR(32), NOT NULL |
| MS-005 | Column `referrer_domain` exists with correct type | VARCHAR(255), NULL |
| MS-006 | Column `impression_at` exists with correct type | TIMESTAMP, DEFAULT CURRENT_TIMESTAMP |
| MS-007 | Column `clicked_at` exists with correct type | TIMESTAMP, NULL |
| MS-008 | Column `click_target` exists with correct type | VARCHAR(10), NULL |
| MS-009 | Column `ip_hash` exists with correct type | VARCHAR(64), NULL |
| MS-010 | Index on `audit_id` exists | Index `idx_audit_id` exists |
| MS-011 | Index on `public_hash` exists | Index `idx_public_hash` exists |
| MS-012 | Index on `impression_at` exists | Index `idx_impression_at` exists |
| MS-013 | Foreign key on `audit_id` references `audits.id` | FK constraint exists |
| MS-014 | Foreign key CASCADE delete | CASCADE on DELETE |
| MS-015 | No unique constraint on `public_hash` | Multiple rows with same hash allowed |
| MS-016 | Insert record with all fields | Success, data retrievable |
| MS-017 | Insert record with NULL `referrer_domain` | Success (nullable) |
| MS-018 | Insert record with NULL `clicked_at` | Success (nullable) |
| MS-019 | Insert record with NULL `click_target` | Success (nullable) |
| MS-020 | Insert record with NULL `ip_hash` | Success (nullable) |
| MS-021 | Insert record fails with NULL `audit_id` | Database error |
| MS-022 | Insert record fails with NULL `public_hash` | Database error |
| MS-023 | Insert multiple records with same `public_hash` | Success (no unique constraint) |
| MS-024 | CASCADE delete when audit deleted | Orphan records deleted |
| MS-025 | Rollback drops table | Table not exists after rollback |
| MS-026 | Re-migrate after rollback | Table exists again |
| MS-027 | Insert with max `referrer_domain` length (255) | Success |
| MS-028 | Insert with max `click_target` length (10) | Success |
| MS-029 | Insert with max `ip_hash` length (64) | Success |
| MS-030 | Insert with max `public_hash` length (32) | Success |
| MS-031 | Insert with `click_target` = 'audit' | Success |
| MS-032 | Insert with `click_target` = 'home' | Success |
| MS-033 | Insert with `click_target` > 10 chars | Database error (truncated or error) |
| MS-034 | Query by `audit_id` uses index | Index scan |
| MS-035 | Query by `public_hash` uses index | Index scan |
| MS-036 | Query by `impression_at` uses index | Index scan |
| MS-037 | Bulk insert 100 records | Success, all retrievable |
| MS-038 | Update `clicked_at` from NULL to timestamp | Success |
| MS-039 | Update `click_target` from NULL to 'audit' | Success |
| MS-040 | Query impressions by date range | Correct records returned |
| MS-041 | Count impressions per audit | Correct aggregation |
| MS-042 | Delete orphan impressions via CASCADE | Correct count deleted |
| MS-043 | Unicode in `referrer_domain` | Success, stored correctly |
| MS-044 | IP hash SHA-256 format (64 hex chars) | Validated format |
| MS-045 | Public hash alphanumeric only | Regex validation |
| MS-046 | Impression timestamp auto-populated | DEFAULT CURRENT_TIMESTAMP works |
| MS-047 | Non-existent audit_id FK violation | Database error |
| MS-048 | Multiple impressions per hash tracked | No duplicate prevention |
| MS-049 | Click target enum-like values | 'audit' or 'home' expected |
| MS-050 | Timestamp precision | Microsecond precision if supported |
| MS-051 | Index cardinality check | Indexes have proper cardinality |
| MS-052 | Foreign key name convention | FK constraint named correctly |
| MS-053 | Migration batch number | Recorded in migrations table |
| MS-054 | Table engine | InnoDB (for FK support) |

### Model Tests (MO) - 52 tests

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| MO-001 | BadgeImpression model exists | Class instantiable |
| MO-002 | Model table property | `badge_impressions` |
| MO-003 | Model primary key | `id` |
| MO-004 | Model timestamps | `true` (uses created_at) |
| MO-005 | Model fillable includes `audit_id` | Mass assignable |
| MO-006 | Model fillable includes `public_hash` | Mass assignable |
| MO-007 | Model fillable includes `referrer_domain` | Mass assignable |
| MO-008 | Model fillable includes `impression_at` | Mass assignable |
| MO-009 | Model fillable includes `clicked_at` | Mass assignable |
| MO-010 | Model fillable includes `click_target` | Mass assignable |
| MO-011 | Model fillable includes `ip_hash` | Mass assignable |
| MO-012 | Model cast for `impression_at` | `datetime` or `timestamp` |
| MO-013 | Model cast for `clicked_at` | `datetime` or `timestamp` |
| MO-014 | Relationship `audit()` exists | BelongsTo relationship |
| MO-015 | Relationship returns Audit model | Correct related model |
| MO-016 | Scope `forAudit($auditId)` exists | Query scope method |
| MO-017 | Scope `forHash($hash)` exists | Query scope method |
| MO-018 | Scope `clicked()` exists | Returns clicked impressions |
| MO-019 | Scope `notClicked()` exists | Returns non-clicked impressions |
| MO-020 | Scope `withClickTarget($target)` exists | Filter by click target |
| MO-021 | Scope `recent($days)` exists | Recent impressions |
| MO-022 | Accessor `is_clicked` | Returns true if clicked_at not null |
| MO-023 | Create via factory | Valid model instance |
| MO-024 | Query by audit relationship | Eager loading works |
| MO-025 | Delete model instance | Record removed |
| MO-026 | Mass update clicked_at | Multiple records updated |
| MO-027 | Count impressions for audit | Accurate count |
| MO-028 | Count clicks for audit | Accurate count |
| MO-029 | Click-through rate calculation | Correct percentage |
| MO-030 | Unique impressions count | Distinct by referrer_domain |
| MO-031 | Model validation `click_target` in ['audit', 'home'] | Validation rule |
| MO-032 | Model null click_target allowed | Nullable validation |
| MO-033 | Create with invalid click_target | Validation fails |
| MO-034 | Update impression after click | clicked_at, click_target set |
| MO-035 | Model serialization | JSON output correct |
| MO-036 | Model deserialization | Create from array |
| MO-037 | Model hidden attributes | Sensitive fields hidden |
| MO-038 | Model guarded attributes | Properly guarded |
| MO-039 | Bulk delete by audit_id | Multiple records deleted |
| MO-040 | Cascade delete verification | Related audit deletion removes impressions |
| MO-041 | Factory state `clicked` | Creates with clicked_at set |
| MO-042 | Factory state `notClicked` | Creates with clicked_at null |
| MO-043 | Factory state `forAudit($audit)` | Links to audit |
| MO-044 | Factory state `fromDomain($domain)` | Sets referrer_domain |
| MO-045 | Factory state `clickAudit` | click_target = 'audit' |
| MO-046 | Factory state `clickHome` | click_target = 'home' |
| MO-047 | Large dataset insert performance | < 1 second for 1000 records |
| MO-048 | Query with indexes performance | < 10ms for indexed queries |
| MO-049 | No sensitive data in model | IP hashed, not stored plain |
| MO-050 | Model exists check | Correct boolean return |
| MO-051 | Refresh model | Reload from database |
| MO-052 | Model touch timestamp | updated_at update |

## Factory States

| State | Description | Fields Set |
|-------|-------------|------------|
| `clicked()` | Impression with click | `clicked_at` = now, `click_target` = 'audit' |
| `notClicked()` | Impression without click | `clicked_at` = null, `click_target` = null |
| `forAudit($audit)` | Link to specific audit | `audit_id`, `public_hash` from audit |
| `fromDomain($domain)` | Set referrer domain | `referrer_domain` = $domain |
| `clickAudit()` | Click target is audit | `click_target` = 'audit' |
| `clickHome()` | Click target is home | `click_target` = 'home' |
| `recent($days)` | Recent impression | `impression_at` within X days |
| `withIpHash($ip)` | Set IP hash | `ip_hash` = hashed IP |

## Mock Data

```php
// Valid badge impression
$impression = [
    'audit_id' => 1,
    'public_hash' => 'abc123def456ghi789jkl012mno345pq',
    'referrer_domain' => 'example.com',
    'impression_at' => '2026-03-06 10:00:00',
    'clicked_at' => null,
    'click_target' => null,
    'ip_hash' => hash('sha256', '192.168.1.1' . config('app.key')),
];

// Clicked impression
$clickedImpression = [
    'audit_id' => 1,
    'public_hash' => 'abc123def456ghi789jkl012mno345pq',
    'referrer_domain' => 'example.com',
    'impression_at' => '2026-03-06 10:00:00',
    'clicked_at' => '2026-03-06 10:05:00',
    'click_target' => 'audit',
    'ip_hash' => hash('sha256', '192.168.1.1' . config('app.key')),
];
```

## Coverage Targets

| Metric | Target |
|--------|--------|
| Migration Structure | 100% |
| Migration Data Operations | 100% |
| Migration Reversibility | 100% |
| Model CRUD | 100% |
| Model Relationships | 100% |
| Model Scopes | 100% |
| Factory States | 100% |

## Test Execution

```bash
# Run migration tests
php artisan test --filter=CreateBadgeImpressionsTableTest

# Run model tests
php artisan test --filter=BadgeImpressionTest

# Run all tests for this task
php artisan test --filter="BadgeImpression|badge_impressions"
```

## Dependencies

- TASK-33-1 (visibility columns) - Done ✅
- Audits table (existing)

## Notes for Developer

1. **Migration File:** `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_badge_impressions_table.php`
2. **Model File:** `backend/app/Models/BadgeImpression.php`
3. **Factory File:** `backend/database/factories/BadgeImpressionFactory.php`
4. **No unique constraint:** Multiple impressions per hash allowed (analytics tracking)
5. **IP Hashing:** Use SHA-256 with app.key salt for privacy
6. **Click Target:** Enum-like values 'audit' or 'home', stored as VARCHAR(10)

## Acceptance Criteria Status

- [ ] Migration creates `badge_impressions` table
- [ ] All columns from spec schema created
- [ ] Foreign key constraint on audit_id with CASCADE delete
- [ ] Indexes on audit_id, public_hash, impression_at
- [ ] BadgeImpression model created with relationships
- [ ] Factory created for testing
- [ ] Unit test verifies table structure and relationships

---

## Comments

### [2026-03-06 12:45 GMT+2] PreTester - Test Design Complete

**Tests Designed:** 106+ test scenarios across 2 test files
- Migration Tests: 54 scenarios
- Model Tests: 52 scenarios

**Key Test Patterns:**
- No unique constraint on `public_hash` (multiple impressions per hash allowed)
- `clicked_at` initially NULL, set when click event received
- `click_target` enum-like values: 'audit' or 'home'
- IP hashed with SHA-256 + app.key salt for privacy
- CASCADE delete foreign key on `audit_id`

**Factory States Designed:** 8 states for testing flexibility
- clicked, notClicked, forAudit, fromDomain, clickAudit, clickHome, recent, withIpHash

**TDD Pattern:** Tests will fail until migration and model are created by Developer.

**Handoff to Developer:** Ready for implementation.
