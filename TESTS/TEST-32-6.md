# TEST-32-6: PricingComparison Component Tests

## Task Reference
**TASK-32-6**: PricingComparison Component
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Test File
`frontend/__tests__/components/compare/PricingComparison.test.tsx`

## Overview
Comprehensive test suite for the PricingComparison component, which displays tier-by-tier pricing for both SEOIndicator and competitors, highlighting the free tier advantage.

## Acceptance Criteria Coverage

| AC | Description | Test Coverage | Status |
|----|-------------|---------------|--------|
| AC-1 | Toggle switches between monthly/annual pricing | PC-010 to PC-013 | ✅ |
| AC-2 | Free tier prominently displayed with "Free" badge | PC-020 to PC-023 | ✅ |
| AC-3 | All tiers from both tools displayed | PC-001 to PC-005 | ✅ |
| AC-4 | Feature lists show included features | PC-050 to PC-053 | ✅ |
| AC-5 | Annual savings calculated and displayed | PC-030 to PC-032 | ✅ |
| AC-6 | Highlighted tier shows accent styling | PC-051, PC-052 | ✅ |
| AC-7 | Mobile responsive (stacked on mobile) | PC-090 to PC-095 | ⏳ |
| AC-8 | Price formatting correct (currency symbol, decimals) | PC-040 to PC-042, PC-076 to PC-078 | ⏳ |

## Test Categories

### 1. Rendering Tests (PC-001 to PC-005)
| ID | Test | Description |
|----|------|-------------|
| PC-001 | renders pricing tiers for both tools | Verifies both SEOIndicator and competitor columns render |
| PC-002 | displays tier names for SEOIndicator | Verifies Free, Pro, Business tier names |
| PC-003 | displays tier names for competitor | Verifies Lite, Standard, Enterprise tier names |
| PC-004 | renders pricing toggle | Verifies toggle buttons exist |
| PC-005 | renders grid layout for pricing cards | Verifies grid container exists |

### 2. Toggle Tests (PC-010 to PC-013)
| ID | Test | Description |
|----|------|-------------|
| PC-010 | toggles between monthly and annual view | Verifies toggle state changes |
| PC-011 | shows monthly prices by default | Verifies initial state shows monthly prices |
| PC-012 | shows annual prices after toggle | Verifies annual prices displayed after click |
| PC-013 | toggle buttons have accessible aria-pressed | Verifies accessibility attribute |

### 3. Free Tier Tests (PC-020 to PC-023)
| ID | Test | Description |
|----|------|-------------|
| PC-020 | highlights free tier with badge | Verifies "Free Forever" badge on free tier |
| PC-021 | shows "Free" text for free tiers | Verifies price displays as "Free" not "$0" |
| PC-022 | hides free tier when showFreeTier is false | Verifies prop controls visibility |
| PC-023 | free tier does not show savings badge | Verifies no savings calculation for free |

### 4. Calculations Tests (PC-030 to PC-032)
| ID | Test | Description |
|----|------|-------------|
| PC-030 | calculates annual savings correctly | Verifies percentage calculation |
| PC-031 | shows savings badge only in annual mode | Verifies conditional display |
| PC-032 | calculates competitor savings correctly | Verifies calculation for competitor tiers |

### 5. Formatting Tests (PC-040 to PC-042)
| ID | Test | Description |
|----|------|-------------|
| PC-040 | formats prices with dollar sign | Verifies currency symbol |
| PC-041 | handles "Free" price correctly | Verifies special case handling |
| PC-042 | formats prices consistently across both columns | Verifies uniform formatting |

### 6. Features Tests (PC-050 to PC-053)
| ID | Test | Description |
|----|------|-------------|
| PC-050 | lists tier features | Verifies feature count matches |
| PC-051 | highlights popular tier with badge | Verifies "Popular" badge |
| PC-052 | applies highlighted class to popular tier | Verifies CSS class applied |
| PC-053 | displays features with check icons | Verifies check icon rendering |

### 7. Accessibility Tests (PC-060 to PC-062)
| ID | Test | Description |
|----|------|-------------|
| PC-060 | toggle buttons are accessible | Verifies role and aria-label |
| PC-061 | toggle buttons can be activated by keyboard | Verifies keyboard interaction |
| PC-062 | pricing columns have accessible labels | Verifies aria-label on columns |

### 8. Edge Cases Tests (PC-070 to PC-075)
| ID | Test | Description |
|----|------|-------------|
| PC-070 | handles empty pricing arrays | Verifies graceful handling |
| PC-071 | handles zero prices | Verifies $0 formatting |
| PC-072 | handles large prices with proper formatting | Verifies $1000 formatting |
| PC-073 | handles missing features array | Verifies empty array handling |
| PC-074 | handles single tier | Verifies single item rendering |
| PC-075 | handles same monthly and annual prices | Verifies 0% savings |

### 9. Props Tests (PC-080 to PC-081)
| ID | Test | Description |
|----|------|-------------|
| PC-080 | uses default value for showFreeTier | Verifies default true |
| PC-081 | respects showFreeTier prop | Verifies prop behavior |

### 10. Mobile Responsive Tests (NEW - PC-090 to PC-095)
| ID | Test | Description |
|----|------|-------------|
| PC-090 | displays grid on desktop viewport | Verifies 2-column layout on desktop |
| PC-091 | stacks columns on mobile viewport | Verifies 1-column layout on mobile |
| PC-092 | toggle remains accessible on mobile | Verifies toggle visibility on mobile |
| PC-093 | pricing cards full width on mobile | Verifies cards expand on mobile |
| PC-094 | features list scrolls horizontally on mobile if needed | Verifies overflow handling |
| PC-095 | maintains readability on small screens | Verifies text remains readable |

### 11. Price Decimal Formatting Tests (NEW - PC-076 to PC-078)
| ID | Test | Description |
|----|------|-------------|
| PC-076 | formats prices with decimals correctly | Verifies $19.99 formatting |
| PC-077 | handles mixed integer and decimal prices | Verifies consistent display |
| PC-078 | rounds decimals appropriately | Verifies 2 decimal places |

### 12. Custom/Enterprise Price Tests (NEW - PC-085 to PC-088)
| ID | Test | Description |
|----|------|-------------|
| PC-085 | handles "Custom" price type | Verifies enterprise tier handling |
| PC-086 | does not calculate savings for custom prices | Verifies no savings badge |
| PC-087 | shows "Contact Sales" for custom tiers | Verifies messaging |
| PC-088 | handles mixed price types (Free, Custom, Number) | Verifies all types together |

## Test Data

### Standard Tiers
```typescript
const mockSeoindicatorTiers: PricingTier[] = [
  {
    name: 'Free',
    monthlyPrice: 'Free',
    annualPrice: 'Free',
    features: ['1 project', '10 keywords', 'Basic tracking'],
  },
  {
    name: 'Pro',
    monthlyPrice: 19,
    annualPrice: 15,
    highlighted: true,
    features: ['5 projects', '100 keywords', 'Daily tracking', 'Competitor analysis'],
  },
  {
    name: 'Business',
    monthlyPrice: 49,
    annualPrice: 39,
    features: ['20 projects', '500 keywords', 'Team collaboration'],
  },
];
```

### Edge Case Data
- Empty arrays
- Zero prices ($0)
- Large prices ($1000+)
- Decimal prices ($19.99)
- Custom/Enterprise prices
- Same monthly/annual prices

## Sub-components Tested

| Component | Tests |
|-----------|-------|
| BillingToggle | PC-010, PC-013, PC-060, PC-061 |
| PricingCard | PC-001 to PC-005, PC-050 to PC-053 |
| FeatureList | PC-050, PC-053 |
| PriceDisplay | PC-040 to PC-042, PC-076 to PC-078 |

## Test Run Summary

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
| Mobile Responsive | 6 |
| Decimal Formatting | 3 |
| Custom Price | 4 |
| **Total** | **47** |

## Developer Notes

1. **Implementation Priority:**
   - Start with basic rendering (PC-001 to PC-005)
   - Add toggle functionality (PC-010 to PC-013)
   - Implement free tier highlighting (PC-020 to PC-023)
   - Add savings calculations (PC-030 to PC-032)
   - Ensure accessibility (PC-060 to PC-062)
   - Add responsive styles (PC-090 to PC-095)

2. **Key Implementation Points:**
   - Use `useState` for billing cycle toggle
   - Calculate savings: `((monthly * 12 - annual * 12) / (monthly * 12)) * 100`
   - Free tier should have "Free Forever" badge
   - Highlighted tier should have `highlighted` CSS class
   - Mobile breakpoint: 768px (stacked layout)

3. **CSS Classes to Implement:**
   - `.pricing-comparison` - Container
   - `.pricing-grid` - Grid layout (2 cols desktop, 1 col mobile)
   - `.highlighted` - Popular tier styling
   - `.free-tier-badge` - Free tier badge
   - `.savings-badge` - Annual savings indicator

4. **Dependencies:**
   - TASK-32-1 (Types) - Done ✅
   - Lucide React icons (Check, Star, Sparkles)

---

## Comments
### [2026-03-05 18:12 GMT+2] Agent: PreTester
**Test Design Session Started:**
- Existing test file found: `PricingComparison.test.tsx` (82 tests)
- Tests originally created for TASK-32-19, now adapting for TASK-32-6
- Added 13 new tests for mobile responsive and decimal/custom price handling
- Total test coverage: 95 tests across 12 categories

**Acceptance Criteria Status:**
- 6 of 8 ACs fully covered
- 2 ACs need additional tests (mobile responsive, decimal formatting)
- New tests added to cover gaps

**Status:** Tests Coverage In Progress
