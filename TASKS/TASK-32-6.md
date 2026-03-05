# TASK-32-6: PricingComparison Component

## STATUS
`To Prioritize`

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
