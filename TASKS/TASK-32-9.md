# TASK-32-9: ComparisonCTA Component

## STATUS
`To Prioritize`

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
- [ ] Primary variant renders with both buttons
- [ ] Secondary variant renders as inline CTA
- [ ] Sticky variant appears on mobile after scroll
- [ ] A/B test group prop tracked in analytics
- [ ] Custom text and href supported
- [ ] Responsive design (different layouts for mobile/desktop)
- [ ] Accessible (proper button/link roles)

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
