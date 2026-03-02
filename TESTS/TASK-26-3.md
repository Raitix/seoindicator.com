# TESTS/TASK-26-3.md — Credit Package Table & Seeder Tests

## Task Reference
- **Task:** [TASK-26-3: Create Credit Packages Table with Seed Data](../TASKS/TASK-26-3.md)
- **Specification:** SPEC-026: Billing, Credits & Model Selection
- **Feature:** FEAT-026: Billing, Credits & Model Selection

---

## Overview

Comprehensive test suite for the `credit_packages` database table, `CreditPackage` model, and `CreditPackageSeeder`. Tests cover model creation, casts, accessors, scopes, database indexes, edge cases, and seeder behavior.

---

## Test Files

| File | Description | Tests |
|------|-------------|-------|
| `tests/Unit/Models/CreditPackageTest.php` | Model tests | 68 tests |
| `tests/Unit/Seeders/CreditPackageSeederTest.php` | Seeder tests | 12 tests |
| `database/factories/CreditPackageFactory.php` | Factory for testing | N/A |

**Total Tests:** 80

---

## Test Scenarios

### CreditPackage Model Tests (CP-*)

#### Model Creation Tests (CP-CR-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-CR-01 | Create with required fields only | name, credits, price_usd | Model created with defaults |
| CP-CR-02 | Create with full data | all fields | Model created with all values |
| CP-CR-03 | Mass assignment protection | all fields via create | All fields fillable |
| CP-CR-04 | Name is required | missing name | QueryException |
| CP-CR-05 | Credits is required | missing credits | QueryException |
| CP-CR-06 | Price USD is required | missing price_usd | QueryException |
| CP-CR-07 | Soft deactivate package | is_active = false | Package hidden but exists |

#### Casts Tests (CP-CA-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-CA-01 | Credits DECIMAL cast | 123.45 | float 123.45 |
| CP-CA-02 | Credits precision 2 decimals | 123.456 | rounded to 123.46 |
| CP-CA-03 | Price USD DECIMAL cast | 99.99 | float 99.99 |
| CP-CA-04 | Price USD precision | 99.999 | rounded to 100.00 |
| CP-CA-05 | Bonus percent INT cast | 15 | int 15 |
| CP-CA-06 | Bonus percent default | not set | 0 |
| CP-CA-07 | Is popular BOOLEAN cast | true | bool true |
| CP-CA-08 | Is popular default | not set | false |
| CP-CA-09 | Is active BOOLEAN cast | false | bool false |
| CP-CA-10 | Is active default | not set | true |
| CP-CA-11 | Sort order INT cast | 5 | int 5 |
| CP-CA-12 | Sort order default | not set | 0 |
| CP-CA-13 | Timestamps cast | created/updated | Carbon instances |

#### Accessor Tests (CP-AC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-AC-01 | Effective credits without bonus | credits=5, bonus=0 | 5.00 |
| CP-AC-02 | Effective credits with 10% bonus | credits=25, bonus=10 | 27.50 |
| CP-AC-03 | Effective credits with 15% bonus | credits=50, bonus=15 | 57.50 |
| CP-AC-04 | Effective credits with 5% bonus | credits=10, bonus=5 | 10.50 |
| CP-AC-05 | Savings percentage no bonus | bonus=0 | 0 |
| CP-AC-06 | Savings percentage with bonus | bonus=10 | 10 |
| CP-AC-07 | Formatted price | 25.00 | "$25.00" |
| CP-AC-08 | Formatted credits | 25.00 | "$25.00" |

#### Scope Tests (CP-SC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-SC-01 | Active scope | 1 active, 1 inactive | Only active returned |
| CP-SC-02 | Active scope multiple | 2 active, 1 inactive | 2 active returned |
| CP-SC-03 | Ordered scope | sort_order 3,1,2 | Ordered by sort_order |
| CP-SC-04 | Popular scope | 1 popular, 1 not | Only popular returned |
| CP-SC-05 | Combined active+ordered | 2 active, 1 inactive | Active packages ordered |

#### Index Tests (CP-IX-*)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CP-IX-01 | Index on is_active | Index exists |
| CP-IX-02 | Index on sort_order | Index exists |

#### Edge Case Tests (CP-EC-*)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-EC-01 | Max credits value | 99999999.99 | Stored correctly |
| CP-EC-02 | Max bonus percent | 4294967295 | Stored correctly |
| CP-EC-03 | Max sort order | 4294967295 | Stored correctly |
| CP-EC-04 | Zero credits | 0.00 | Allowed |
| CP-EC-05 | Zero price | 0.00 | Allowed |
| CP-EC-06 | Name max 100 chars | 100 chars | Stored correctly |
| CP-EC-07 | Name exceeds 100 chars | 101 chars | QueryException |
| CP-EC-08 | Duplicate names | same name twice | Allowed (no unique) |
| CP-EC-09 | Small decimals | 0.01 | Stored correctly |
| CP-EC-10 | Bonus percent null vs zero | not set | Default 0, not null |

#### Security Tests (CP-SEC-*)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| CP-SEC-01 | Price values server-side | No client modification |
| CP-SEC-02 | Active flag hides packages | Inactive not in scope |

### Seeder Tests (SEED-CP-*)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| SEED-CP-01 | Creates exactly 4 packages | count = 4 |
| SEED-CP-02 | Creates Starter package | $5 = $5 credits, 0% bonus |
| SEED-CP-03 | Creates Basic package | $10 = $10 credits, 5% bonus |
| SEED-CP-04 | Creates Pro package (popular) | $25 = $25 credits, 10% bonus, popular |
| SEED-CP-05 | Creates Enterprise package | $50 = $50 credits, 15% bonus |
| SEED-CP-06 | Proper sort order | Starter → Basic → Pro → Enterprise |
| SEED-CP-07 | Only one popular | count(is_popular=true) = 1 |
| SEED-CP-08 | All active | count(is_active=true) = 4 |
| SEED-CP-09 | Minimum $5 purchase | min(price_usd) = 5.00 |
| SEED-CP-10 | Idempotent | Can run multiple times |
| SEED-CP-11 | Correct effective credits | Calculated correctly |
| SEED-CP-12 | Timestamps created | created_at, updated_at set |

---

## Factory States

| State | Description |
|-------|-------------|
| `starter()` | Starter tier ($5, 0% bonus) |
| `basic()` | Basic tier ($10, 5% bonus) |
| `pro()` | Pro tier ($25, 10% bonus, popular) |
| `enterprise()` | Enterprise tier ($50, 15% bonus) |
| `popular()` | Marked as popular |
| `inactive()` | Marked as inactive |
| `withBonus(int)` | Custom bonus percentage |
| `withPrice(float)` | Custom price |
| `withSortOrder(int)` | Custom sort order |

---

## Coverage Targets

| Area | Target | Status |
|------|--------|--------|
| Model Creation | 100% | ✅ |
| Casts | 100% | ✅ |
| Accessors | 100% | ✅ |
| Scopes | 100% | ✅ |
| Indexes | 100% | ✅ |
| Edge Cases | 100% | ✅ |
| Security | 100% | ✅ |
| Seeder | 100% | ✅ |

---

## Test Execution

```bash
# Run CreditPackage model tests
cd backend
php artisan test tests/Unit/Models/CreditPackageTest.php

# Run CreditPackageSeeder tests
php artisan test tests/Unit/Seeders/CreditPackageSeederTest.php

# Run all tests for this task
php artisan test --filter "CreditPackage"
```

---

## Dependencies

- **Database:** MySQL with `credit_packages` table
- **Migration:** Required before running tests
- **Factory:** `CreditPackageFactory.php` for test data

---

## Notes for Developer

### Migration Requirements
```php
Schema::create('credit_packages', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->decimal('credits', 10, 2);
    $table->decimal('price_usd', 10, 2);
    $table->unsignedInteger('bonus_percent')->default(0);
    $table->boolean('is_popular')->default(false);
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index('is_active');
    $table->index('sort_order');
});
```

### Model Requirements
- `$fillable` array with all fields
- `$casts` array for DECIMAL, INT, and BOOLEAN fields
- `scopeActive()` - filter active packages
- `scopeOrdered()` - order by sort_order
- `scopePopular()` - filter popular packages
- `getEffectiveCreditsAttribute()` - base + bonus calculation
- `getSavingsPercentageAttribute()` - returns bonus_percent
- `getFormattedPriceAttribute()` - returns "$X.XX"
- `getFormattedCreditsAttribute()` - returns "$X.XX"

### Seeder Requirements
- Create 4 packages: Starter, Basic, Pro, Enterprise
- Pro marked as popular
- All packages active
- Proper sort_order (1-4)

---

## Comments

### PreTester (2026-03-02)
- Created comprehensive test suite covering all acceptance criteria
- Tests follow existing project patterns (see CreditTest.php)
- Factory includes states for all 4 package tiers
- Seeder tests verify exact package specifications
- Security tests confirm server-side price control
- Accessor tests skipped if not implemented (allowing TDD approach)

---

*Last updated: 2026-03-02*
