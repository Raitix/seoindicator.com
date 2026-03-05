# TASK-32-6: PricingComparison Component

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create a pricing comparison component that displays tier-by-tier pricing for both tools, highlighting SEOIndicator's free tier advantage.

## Deliverables

### 1. Component File (`frontend/components/compare/PricingComparison.tsx`)

```typescript
import { PricingTier, PricingComparisonProps } from '@/lib/compare/types';

export function PricingComparison({ 
  seoindicator, 
  competitor, 
  showFreeTier = true 
}: PricingComparisonProps) {
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'annual'>('monthly');

  return (
    <div className="pricing-comparison">
      {/* Billing toggle */}
      <BillingToggle value={billingCycle} onChange={setBillingCycle} />
      
      {/* Pricing cards grid */}
      <div className="pricing-grid">
        {/* SEOIndicator pricing */}
        <div className="pricing-column">
          <h3>SEOIndicator</h3>
          {seoindicator.map((tier) => (
            <PricingCard key={tier.name} tier={tier} cycle={billingCycle} isSelf />
          ))}
        </div>
        
        {/* Competitor pricing */}
        <div className="pricing-column">
          <h3>Competitor</h3>
          {competitor.map((tier) => (
            <PricingCard key={tier.name} tier={tier} cycle={billingCycle} />
          ))}
        </div>
      </div>
    </div>
  );
}
```

### 2. Features

| Feature | Description |
|---------|-------------|
| Tier comparison | Side-by-side pricing tiers |
| Free tier highlight | SEOIndicator free tier prominently displayed |
| Feature breakdown | Show features included in each tier |
| Annual/monthly toggle | Switch between billing periods |
| Currency support | Display prices in user's currency (future) |
| Savings indicator | Show savings for annual plans |

### 3. Sub-components

```typescript
// Billing cycle toggle (Monthly/Annual)
function BillingToggle({ value, onChange }: { value: string; onChange: (v: string) => void });

// Individual pricing card
function PricingCard({ tier, cycle, isSelf }: { tier: PricingTier; cycle: string; isSelf?: boolean });

// Feature list with check icons
function FeatureList({ features }: { features: string[] });

// Price display with optional savings
function PriceDisplay({ tier, cycle }: { tier: PricingTier; cycle: string });
```

### 4. SEOIndicator Pricing Data

| Tier | Monthly | Annual | Key Features |
|------|---------|--------|--------------|
| Free | $0 | $0 | 5 analyses/day, basic tools |
| Pro | $29 | $19/mo | Unlimited analyses, all tools |

## Acceptance Criteria
- [ ] Toggle switches between monthly/annual pricing
- [ ] Free tier prominently displayed with "Free" badge
- [ ] All tiers from both tools displayed
- [ ] Feature lists show included features
- [ ] Annual savings calculated and displayed
- [ ] Highlighted tier shows accent styling
- [ ] Mobile responsive (stacked on mobile)
- [ ] Price formatting correct (currency symbol, decimals)

## Dependencies
- TASK-32-1 (Types)

## Effort Estimate
`Medium` (3-4 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/components/compare/PricingComparison.tsx` | Create |

## Technical Notes
- Use existing Card and Button components
- Consider using React Context for currency (future)
- Implement memo for pricing calculations
- Follow existing pricing page styling

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Key conversion component highlighting free tier advantage.

### [2026-03-05 16:58 GMT+2] Agent: ProjectManager
**Priority Assessment:** HIGH (confirmed)
- Conversion-focused component for programmatic SEO landing pages
- Directly impacts user acquisition by highlighting SEOIndicator's free tier advantage
- Key differentiator against competitors - pricing is a primary decision factor for users

**Risk Assessment:** LOW (confirmed)
- Frontend-only React component, no external APIs
- Uses existing types from TASK-32-1 (Done ✅)
- Standard Next.js component patterns

**Dependency Status:**
- TASK-32-1 (Types): Done ✅ - All interfaces available

**TDD Ready:** PreTester can design tests immediately with PricingComparisonProps interface

**Key Features:**
- Tier-by-tier pricing comparison (SEOIndicator vs Competitor)
- Free tier highlight - SEOIndicator's key competitive advantage
- Monthly/Annual billing toggle
- Annual savings indicator
- Mobile responsive (stacked layout)

**Sub-components to Implement:**
1. BillingToggle - Monthly/Annual switch
2. PricingCard - Individual tier card with features
3. FeatureList - Feature list with check icons
4. PriceDisplay - Formatted price with savings

**Phase Context:** Phase 2 Components - builds on Types foundation (TASK-32-1)

**Status:** Updated to `To Write Tests`

### [2026-03-05 18:15 GMT+2] Agent: PreTester
**Test Design Complete:**
- Created `TESTS/TEST-32-6.md` with comprehensive test documentation
- Updated `frontend/__tests__/components/compare/PricingComparison.test.tsx` with 53 tests
- All tests pass ✅

**Test Coverage Summary:**
| Category | Tests |
|----------|-------|
| Rendering | 5 |
| Toggle | 4 |
| Free Tier | 4 |
| Calculations | 3 |
| Formatting | 3 |
| Features | 4 |
| Accessibility | 3 |
| Edge Cases | 6 |
| Props | 2 |
| Decimal Formatting | 3 |
| Custom/Enterprise Prices | 4 |
| Mobile Responsive | 6 |
| Annual Savings Edge Cases | 3 |
| Component Integration | 3 |
| **Total** | **53** |

**Acceptance Criteria Coverage:**
| AC | Status |
|----|--------|
| Toggle switches between monthly/annual pricing | ✅ PC-010 to PC-013 |
| Free tier prominently displayed with "Free" badge | ✅ PC-020 to PC-023 |
| All tiers from both tools displayed | ✅ PC-001 to PC-005 |
| Feature lists show included features | ✅ PC-050 to PC-053 |
| Annual savings calculated and displayed | ✅ PC-030 to PC-032 |
| Highlighted tier shows accent styling | ✅ PC-051, PC-052 |
| Mobile responsive (stacked on mobile) | ✅ PC-090 to PC-095 |
| Price formatting correct (currency symbol, decimals) | ✅ PC-040 to PC-042, PC-076 to PC-078 |

**Developer Notes:**
1. Mock component exists in test file - replace with actual implementation
2. Implement price rounding to 2 decimal places (see PC-078)
3. Use Tailwind responsive classes for mobile layout
4. Key calculation: `((monthly * 12 - annual * 12) / (monthly * 12)) * 100` for savings percentage

**Status:** Updated to `To Develop`
