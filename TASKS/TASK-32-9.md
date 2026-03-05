# TASK-32-9: ComparisonCTA Component

## STATUS
`To Test Locally`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create a call-to-action component for comparison pages with support for multiple variants, A/B testing, and sticky mobile behavior.

## Deliverables

### 1. Component File (`frontend/components/compare/ComparisonCTA.tsx`)

```typescript
import { ComparisonCTAProps } from '@/lib/compare/types';

export function ComparisonCTA({ 
  variant = 'primary', 
  text = 'Try SEOIndicator Free', 
  href = '/register',
  abTestGroup 
}: ComparisonCTAProps) {
  return (
    <div className={`comparison-cta comparison-cta--${variant}`}>
      <div className="cta-content">
        <h3>Ready to Try SEOIndicator?</h3>
        <p>Start with our free tier - no credit card required</p>
        <div className="cta-buttons">
          <Button asChild size="lg">
            <Link href={href}>{text}</Link>
          </Button>
          <Button asChild variant="outline">
            <Link href="/features">View Features</Link>
          </Button>
        </div>
      </div>
    </div>
  );
}
```

### 2. Variants

| Variant | Description | Use Case |
|---------|-------------|----------|
| `primary` | Full-width section with buttons | Main CTA section |
| `secondary` | Smaller, inline CTA | Mid-page prompt |
| `sticky` | Fixed at bottom on mobile | Mobile conversion |

### 3. Sticky Mobile CTA

```typescript
export function StickyCTA() {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      // Show after scrolling past comparison table
      setIsVisible(window.scrollY > 500);
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  if (!isVisible) return null;

  return (
    <div className="sticky-cta">
      <Button asChild>
        <Link href="/register">Try Free</Link>
      </Button>
    </div>
  );
}
```

### 4. A/B Test Integration

```typescript
// Track CTA clicks for A/B testing
function trackCTAClick(ctaId: string, abTestGroup?: string) {
  // Send to analytics
  analytics.track('cta_click', {
    cta_id: ctaId,
    ab_test_group: abTestGroup,
    page: window.location.pathname
  });
}
```

## Acceptance Criteria
- [x] Primary variant renders with both buttons
- [x] Secondary variant renders as inline CTA
- [x] Sticky variant appears on mobile after scroll
- [x] A/B test group prop tracked in analytics
- [x] Custom text and href supported
- [x] Responsive design (different layouts for mobile/desktop)
- [x] Accessible (proper button/link roles)

## Dependencies
- TASK-32-1 (Types)

## Effort Estimate
`Small` (2-3 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/components/compare/ComparisonCTA.tsx` | Create |

## Technical Notes
- Use existing Button component
- Implement intersection observer for sticky visibility
- Follow existing CTA styling patterns
- Consider adding animation for sticky appearance

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Key conversion component with A/B test support.

### [2026-03-05 17:35 GMT+2] Agent: ProjectManager
**Priority Assessment:** HIGH (confirmed)
- Key conversion component for comparison landing pages
- Directly impacts user conversion and signup flow
- A/B test integration essential for optimizing conversion rates
- Sticky mobile CTA is critical for mobile user experience (majority of traffic)

**Risk Assessment:** LOW (confirmed)
- Frontend-only React component, no external APIs
- Uses existing Button component pattern
- Standard intersection observer for sticky behavior
- No database changes, no authentication changes

**Dependency Status:**
- TASK-32-1 (Types): Done ✅ - `ComparisonCTAProps` interface available
- All interfaces defined and tested (117 tests pass)

**Phase Context:**
- Phase 2 Component - after foundation infrastructure
- Enables: Full comparison page conversion flow
- Related: TASK-32-7 (ComparisonPage) will use this component

**TDD Ready:** PreTester can design tests immediately
- Component props interface defined in TASK-32-1
- 3 variants to test: primary, secondary, sticky
- A/B test tracking can be mocked
- Intersection observer can be mocked for sticky tests

**Key Features to Test:**
1. Primary variant renders with both buttons
2. Secondary variant renders as inline CTA
3. Sticky variant appears on mobile after scroll (500px)
4. A/B test group prop tracked in analytics
5. Custom text and href supported
6. Responsive design (different layouts for mobile/desktop)
7. Accessibility (proper button/link roles)

### [2026-03-05 18:20 GMT+2] Agent: PreTester
**Test Design Complete ✅**

**Files Created:**
- `TESTS/TEST-32-9.md` - Test documentation with 50+ test scenarios
- `frontend/__tests__/components/compare/ComparisonCTA.test.tsx` - Updated test file

**Test Coverage:**
| Category | Tests | IDs |
|----------|-------|-----|
| Primary Variant | 9 tests | CTA-PRI-001 to CTA-PRI-009 |
| Secondary Variant | 6 tests | CTA-SEC-001 to CTA-SEC-006 |
| Sticky Variant | 8 tests | CTA-STK-001 to CTA-STK-008 |
| Text & Link | 6 tests | CTA-TXT-001 to CTA-TXT-006 |
| A/B Test Tracking | 9 tests | CTA-TRK-001 to CTA-TRK-009 |
| Accessibility | 7 tests | CTA-ACC-001 to CTA-ACC-007 |
| Responsive Design | 5 tests | CTA-RES-001 to CTA-RES-005 |
| Edge Cases | 7 tests | CTA-EDG-001 to CTA-EDG-007 |
| Acceptance Criteria | 7 tests | AC-1 to AC-7 |

**Total:** 64+ test scenarios covering all acceptance criteria

**Key Differences from TASK-32-19 Tests:**
1. Primary variant now tests TWO buttons (main CTA + View Features)
2. Sticky variant tests scroll behavior (500px threshold with scroll event simulation)
3. Added responsive design tests for button layout (flex-col on mobile, flex-row on desktop)
4. Added cleanup tests for scroll listener on unmount

**Implementation Notes for Developer:**
1. Primary variant: Use `<section>` with `aria-labelledby` for accessibility
2. Sticky variant: Use `window.scrollY > 500` for visibility logic
3. Cleanup: Remove scroll listener in `useEffect` return
4. Two buttons in primary: Main CTA + "View Features" (secondary button does NOT track CTA event)
5. Use existing Button component from shadcn/ui

**Status:** Ready for Developer implementation

### [2026-03-05 18:45 GMT+2] Agent: Developer
**Implementation Complete ✅**

**File Modified:**
- `frontend/components/compare/ComparisonCTA.tsx` - Rewrote component to match test expectations

**Implementation Details:**
1. **Primary variant:** 
   - Uses `<section>` element with `aria-labelledby` for accessibility
   - Renders heading "Ready to Try SEOIndicator?" with description
   - TWO buttons: primary CTA + "View Features" secondary button
   - Responsive: `flex-col` on mobile, `sm:flex-row` on desktop

2. **Secondary variant:**
   - Inline anchor element with border styling
   - Uses `ExternalLink` icon from lucide-react
   - `inline-flex` layout

3. **Sticky variant:**
   - Hidden initially, appears after `scrollY > 500`
   - Uses `useEffect` with scroll event listener
   - Proper cleanup on unmount
   - `md:hidden` class for mobile-only display
   - Fixed positioning at bottom of viewport

4. **A/B Test Tracking:**
   - Tracks click events via `trackEvent` from `@/lib/analytics`
   - Includes variant, text, href, abTestGroup, and page in payload
   - Secondary "View Features" button does NOT fire CTA event

5. **Accessibility:**
   - Proper heading hierarchy (h3 with id="cta-heading")
   - `aria-labelledby` on section
   - Icons have `aria-hidden="true"`
   - Keyboard navigable links

**Test Results:** All 64 tests pass ✅

**Status:** Ready for LocalTester
