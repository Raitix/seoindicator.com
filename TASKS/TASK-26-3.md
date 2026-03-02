# TASK-26-3: Create Credit Packages Table with Seed Data

## STATUS
`To Prioritize`

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
