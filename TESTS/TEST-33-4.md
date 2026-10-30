# TEST-33-4: PublicAudit Model Unit Tests

## Task Reference
- **Task:** TASK-33-4
- **Feature:** FEAT-033 - Public SEO Audit Reports & Embeddable Score Widget
- **Status:** Tests Coverage In Progress
- **Agent:** PreTester
- **Date:** 2026-03-06

---

## Overview

Comprehensive unit tests for the PublicAudit model that handles public audit data retrieval, hash generation, and visibility management. This model provides the core functionality for the public audit sharing feature.

---

## Test Files

| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `backend/tests/Unit/Models/PublicAuditTest.php` | 70+ | 100+ | TDD (awaits model) |
| `backend/database/factories/PublicAuditFactory.php` | 8 states | - | Created |

---

## Test Scenarios

### MI: Model Instantiation (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MI-001 | Model can be instantiated | `new PublicAudit()` | Instance created |
| MI-002 | Model has correct table name | `$publicAudit->getTable()` | `'public_audits'` |
| MI-003 | Model has audit relationship | `method_exists($publicAudit, 'audit')` | `true` |
| MI-004 | Model fillable attributes defined | `$publicAudit->getFillable()` | Contains `audit_id`, `public_hash`, `visibility` |
| MI-005 | Model has correct default visibility | `$publicAudit->visibility` | `'private'` |

### RE: Relationships (3 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RE-001 | PublicAudit belongs to Audit | Create with audit_id | Relationship returns Audit |
| RE-002 | Audit has one PublicAudit relationship | `method_exists($audit, 'publicAudit')` | `true` |
| RE-003 | Deleting Audit cascades to PublicAudit | Delete audit | PublicAudit also deleted |

### GH: generateHash() Method (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GH-001 | Returns 32 character string | `PublicAudit::generateHash()` | `strlen($hash) === 32` |
| GH-002 | Returns alphanumeric string | `PublicAudit::generateHash()` | Matches `/^[a-zA-Z0-9]+$/` |
| GH-003 | Uses cryptographic randomness | Generate 100 hashes | All unique |
| GH-004 | Not derived from audit ID | Generate hash with audit | Hash doesn't contain ID |
| GH-005 | Regenerates on collision | Existing hash in DB | Returns different hash |
| GH-006 | Uses Str::random helper | Generate hash | Valid Str::random(32) output |

### PB: publish() Method (6 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PB-001 | Sets visibility to public | `publish()` on private | `visibility === 'public'` |
| PB-002 | Generates hash if not exists | `publish()` without hash | `public_hash` is set |
| PB-003 | Keeps existing hash | `publish()` with existing hash | Same hash preserved |
| PB-004 | Sets hash_created_at timestamp | `publish()` | `public_hash_created_at` is set |
| PB-005 | Returns self for chaining | `publish()` | Returns `$this` |
| PB-006 | Persists changes to database | `publish()` then `fresh()` | DB updated |

### UP: unpublish() Method (5 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UP-001 | Sets visibility to private | `unpublish()` on public | `visibility === 'private'` |
| UP-002 | Clears hash | `unpublish()` | `public_hash === null` |
| UP-003 | Clears hash_created_at | `unpublish()` | `public_hash_created_at === null` |
| UP-004 | Returns self for chaining | `unpublish()` | Returns `$this` |
| UP-005 | Persists changes to database | `unpublish()` then `fresh()` | DB updated |

### IP: isPublic() Method (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IP-001 | Returns true for public audit | `visibility = 'public'` | `isPublic() === true` |
| IP-002 | Returns false for private audit | `visibility = 'private'` | `isPublic() === false` |
| IP-003 | Reflects visibility changes | publish/unpublish cycle | Matches current state |
| IP-004 | Handles null visibility | `visibility = null` | Returns `false` |

### FH: findByHash() Method (7 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FH-001 | Returns public audit for valid hash | Existing public hash | PublicAudit instance |
| FH-002 | Returns null for non-existent hash | Non-existent hash | `null` |
| FH-003 | Returns null for private audit | Private audit hash | `null` |
| FH-004 | Is case sensitive | Different case hash | `null` for different case |
| FH-005 | Loads audit relationship | Find by hash | `audit` relationship loaded |
| FH-006 | Handles empty string | `findByHash('')` | `null` |
| FH-007 | Handles null input | `findByHash(null)` | `null` |

### UN: Uniqueness/Collision Detection (3 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| UN-001 | Hash uniqueness across multiple audits | Publish 10 audits | All unique hashes |
| UN-002 | Collision detection regenerates hash | Existing hash in DB | Different hash returned |
| UN-003 | Unique constraint on public_hash column | Create duplicate hash | QueryException thrown |

### TR: Transaction Safety (3 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TR-001 | publish wraps changes in transaction | `publish()` | `DB::transaction()` called |
| TR-002 | Transaction rolls back on error | Simulate error | Original state preserved |
| TR-003 | Concurrent operations handled safely | Multiple concurrent publishes | One succeeds, others retry |

### SC: Security (4 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Hash not predictable from audit data | Create with known ID | Hash doesn't contain ID |
| SC-002 | SQL injection prevented | Malicious hash string | No exception, returns null |
| SC-003 | XSS prevented in hash values | `<script>` in hash | Returns null |
| SC-004 | Private audits not accessible | Private audit hash | Returns null |

### EC: Edge Cases (8 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Publish already public audit | Double publish | No change to hash |
| EC-002 | Unpublish already private audit | Double unpublish | No error |
| EC-003 | Rapid publish/unpublish cycles | 5 cycles | All succeed |
| EC-004 | Hash contains only alphanumeric | Generate hash | Matches `/^[a-zA-Z0-9]+$/` |
| EC-005 | Multiple audits public simultaneously | Publish 3 audits | All unique, all public |
| EC-006 | Unicode in hash parameter | Unicode string | Returns null |
| EC-007 | Very long hash parameter | 1000 char string | Returns null |
| EC-008 | findByHash with exact 32 chars | 32-char hash | Finds correctly |

### SQ: Model Scopes (3 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SQ-001 | public scope returns only public audits | `PublicAudit::public()->get()` | Only visibility='public' |
| SQ-002 | private scope returns only private audits | `PublicAudit::private()->get()` | Only visibility='private' |
| SQ-003 | withHash scope finds by hash | `PublicAudit::withHash($hash)` | Matches hash |

### ACC: Accessors (2 tests)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ACC-001 | public_url accessor generates correct URL | Public audit with hash | URL contains hash |
| ACC-002 | public_url returns empty for private | Private audit | Empty string |

### AC: Acceptance Criteria (8 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| AC-001 | PublicAudit model created with audit relationship | Relationship works |
| AC-002 | generateHash creates 32-char alphanumeric string | Valid format |
| AC-003 | publish sets visibility public and generates hash | Both actions occur |
| AC-004 | unpublish sets visibility private and clears hash | Both actions occur |
| AC-005 | isPublic checks visibility status | Correct boolean |
| AC-006 | findByHash retrieves public audit or null | Correct result |
| AC-007 | Hash uniqueness guaranteed | All unique |
| AC-008 | Hash is cryptographically random | High entropy |

---

## Mock Data

### Default PublicAudit State
```php
[
    'audit_id' => Audit::factory(),
    'public_hash' => null,
    'visibility' => 'private',
    'public_hash_created_at' => null,
]
```

### Factory States

| State | Visibility | Hash | Use Case |
|-------|------------|------|----------|
| `published()` | public | Generated | Public shared audit |
| `unpublished()` | private | null | Private audit |
| `withHash($hash)` | public | Custom | Specific hash testing |
| `forAudit($audit)` | default | default | Link to specific audit |
| `publishedAt($date)` | public | Generated | Time-based testing |
| `publishedDaysAgo($days)` | public | Generated | Aging tests |
| `recentlyPublished()` | public | Generated | Fresh public audit |
| `oldHash()` | public | Generated | Long-published audit |

---

## Expected Model Interface

```php
class PublicAudit extends Model
{
    // Fillable
    protected $fillable = ['audit_id', 'public_hash', 'visibility', 'public_hash_created_at'];
    
    // Defaults
    protected $attributes = ['visibility' => 'private'];
    
    // Relationships
    public function audit(): BelongsTo;
    
    // Static Methods
    public static function generateHash(): string;
    public static function findByHash(string $hash): ?self;
    
    // Instance Methods
    public function publish(): self;
    public function unpublish(): self;
    public function isPublic(): bool;
    
    // Scopes
    public function scopePublic($query);
    public function scopePrivate($query);
    public function scopeWithHash($query, string $hash);
    
    // Accessors
    public function getPublicUrlAttribute(): string;
}
```

---

## Database Schema

```sql
CREATE TABLE public_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    public_hash VARCHAR(32) NULL UNIQUE,
    visibility ENUM('public', 'private') DEFAULT 'private',
    public_hash_created_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    UNIQUE KEY public_audits_public_hash_unique (public_hash),
    INDEX public_audits_audit_id_index (audit_id),
    INDEX public_audits_visibility_index (visibility)
);
```

---

## Test Execution

```bash
# Run all PublicAudit tests
php artisan test --filter PublicAuditTest

# Run specific test category
php artisan test --filter "PublicAuditTest::test.*generateHash"

# Run with coverage
php artisan test --filter PublicAuditTest --coverage
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-33-1 (visibility columns) | Done ✅ | Migration complete |
| Audit model | Exists ✅ | Relationship target |
| AuditFactory | Exists ✅ | Used in test setup |

---

## Notes for Developer

1. **Hash Generation:** Use `Str::random(32)` for cryptographic randomness
2. **Transaction Safety:** Wrap `publish()` in `DB::transaction()` for race condition prevention
3. **Collision Handling:** Implement loop with `while (self::where('public_hash', $hash)->exists())` for collision detection
4. **Visibility Enum:** Consider using enum class for type safety
5. **Soft Deletes:** Not recommended - hard delete preserves public hash uniqueness
6. **Cache Consideration:** `findByHash()` should cache results for frequently accessed public audits

---

## Comments

### [2026-03-06 15:35 GMT+2] PreTester - Test Creation Complete

**Tests Created:** 70+ test scenarios across 13 categories
- Model Instantiation: 5 tests
- Relationships: 3 tests
- generateHash(): 6 tests
- publish(): 6 tests
- unpublish(): 5 tests
- isPublic(): 4 tests
- findByHash(): 7 tests
- Uniqueness/Collision: 3 tests
- Transaction Safety: 3 tests
- Security: 4 tests
- Edge Cases: 8 tests
- Model Scopes: 3 tests
- Accessors: 2 tests
- Acceptance Criteria: 8 tests

**Factory Created:** PublicAuditFactory with 8 states

**TDD Pattern:** Tests will fail until PublicAudit model is implemented

**Handoff:** Ready for Developer implementation

---

Last updated: 2026-03-06
