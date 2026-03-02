# TASK-26-3: Create Credit Packages Table with Seed Data

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Related Specification
SPEC-026: Billing, Credits & Model Selection

## Related Feature
FEAT-026: Billing, Credits & Model Selection

---

## Description
Create database migration and seeder for credit packages - the predefined pricing tiers users can purchase.

## Acceptance Criteria

### credit_packages Table
- [ ] `id` - BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- [ ] `name` - VARCHAR(100) NOT NULL (e.g., "Starter", "Pro", "Enterprise")
- [ ] `credits` - DECIMAL(10, 2) NOT NULL (dollar value)
- [ ] `price_usd` - DECIMAL(10, 2) NOT NULL (actual price)
- [ ] `bonus_percent` - INT UNSIGNED DEFAULT 0 (extra credits percentage)
- [ ] `is_popular` - TINYINT(1) DEFAULT 0 (highlight badge)
- [ ] `is_active` - TINYINT(1) DEFAULT 1 (soft delete alternative)
- [ ] `sort_order` - INT UNSIGNED DEFAULT 0 (display order)
- [ ] `created_at` and `updated_at` timestamps

### Indexes
- [ ] Index on `is_active`
- [ ] Index on `sort_order`

### CreditPackage Model
- [ ] `app/Models/CreditPackage.php` created
- [ ] Scope for active packages only
- [ ] Accessor for effective credits (base + bonus)
- [ ] Accessor for savings percentage (if any)

### Seed Data
- [ ] Create `database/seeders/CreditPackageSeeder.php`
- [ ] Seed at least 4 packages:
  - Starter: $5 = $5 credits (minimum)
  - Basic: $10 = $10 credits + 5% bonus
  - Pro: $25 = $25 credits + 10% bonus (marked popular)
  - Enterprise: $50 = $50 credits + 15% bonus
- [ ] Proper sort_order for display

## Dependencies
- None (independent table)

## Effort Estimate
`Small` (1-2 hours)

## Notes
- Bonus credits are calculated at purchase time, not stored
- Minimum $5 purchase to reduce transaction fee impact
- Popular badge helps guide user choice
- Packages can be added/modified via admin interface later

## Security Considerations
- Price values are server-side only (frontend displays, doesn't set)
- Active flag allows hiding packages without deletion
- No user input affects package pricing

## Comments
_Leave notes for the next agent here_

### PreTester (2026-03-02)
**Test Status:** 80 tests created - ready for development

**Files Created:**
- `tests/Unit/Models/CreditPackageTest.php` - 68 model tests
- `tests/Unit/Seeders/CreditPackageSeederTest.php` - 12 seeder tests
- `database/factories/CreditPackageFactory.php` - factory with 4 tier states
- `TESTS/TASK-26-3.md` - test documentation

**Test Categories:**
- Model Creation (7 tests) - PASS (will pass after migration)
- Casts (13 tests) - PASS (will pass after migration)
- Accessors (8 tests) - SKIP (awaiting implementation)
- Scopes (5 tests) - SKIP (awaiting implementation)
- Indexes (2 tests) - PASS (will pass after migration)
- Edge Cases (10 tests) - PASS (will pass after migration)
- Security (2 tests) - PASS (will pass after migration)
- Seeder (12 tests) - PASS (will pass after seeder creation)

**Key Requirements for Developer:**
1. Create migration with proper indexes on `is_active` and `sort_order`
2. Create CreditPackage model with:
   - `$fillable` for all columns
   - `$casts` for decimal/int/boolean fields
   - `scopeActive()`, `scopeOrdered()`, `scopePopular()`
   - `effective_credits` accessor (base + bonus calculation)
   - `savings_percentage` accessor (returns bonus_percent)
   - `formatted_price` and `formatted_credits` accessors
3. Create CreditPackageSeeder with 4 tiers:
   - Starter: $5 = $5 credits, 0% bonus
   - Basic: $10 = $10 credits, 5% bonus
   - Pro: $25 = $25 credits, 10% bonus, **popular**
   - Enterprise: $50 = $50 credits, 15% bonus

**Run Tests:**
```bash
cd backend
php artisan test --filter "CreditPackage"
```

### ProjectManager Analysis (2026-03-02)
**Priority Assessment:** ✅ High - CORRECT
- Foundation task for billing MVP - credit packages are required for pricing page
- Independent table with no dependencies - can proceed immediately
- Enables TASK-26-20 (Pricing Page) and payment flow tasks

**Risk Assessment:** ✅ Low - CORRECT
- Standard migration and seeder - no external APIs
- Non-destructive (adds table only)
- Price values are server-side only (security consideration noted)

**Dependency Chain:**
- TASK-26-1 (credits/transactions tables): `In Development` - independent from this task
- credit_packages table is standalone, not related to credits/transactions tables
- Both can be developed in parallel

**Execution Order:** Phase 1 Database task
- Can proceed immediately alongside TASK-26-1 (different tables)
- Enables Phase 6 Frontend tasks (pricing page)
- Handoff to PreTester for test design
