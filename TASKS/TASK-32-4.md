# TASK-32-4: Implement Comparison Index Page

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create the `/compare` index page that lists all available comparisons, organized by category (Tier 1, Tier 2, cross-competitor, etc.).

## Deliverables

### 1. Index Page (`frontend/app/compare/page.tsx`)

```typescript
import { COMPETITOR_REGISTRY, COMPARISON_SLUGS } from '@/lib/compare/registry';
import { ComparisonCard } from '@/components/compare/ComparisonCard';

export const metadata: Metadata = {
  title: 'SEO Tool Comparisons | Find the Right SEO Tool',
  description: 'Compare SEOIndicator with Ahrefs, SEMrush, Moz, and other SEO tools. Find the right tool for your needs.',
};

export default function CompareIndexPage() {
  return (
    <main>
      {/* Hero section */}
      {/* Tier 1 comparisons */}
      {/* Tier 2 comparisons */}
      {/* Cross-competitor comparisons */}
      {/* CTA section */}
    </main>
  );
}
```

### 2. Comparison Card Component (`frontend/components/compare/ComparisonCard.tsx`)

```typescript
interface ComparisonCardProps {
  slug: string;
  title: string;
  description: string;
  competitorA: { name: string; logo?: string };
  competitorB: { name: string; logo?: string };
}

export function ComparisonCard({ slug, title, description, competitorA, competitorB }: ComparisonCardProps) {
  return (
    <Link href={`/compare/${slug}`}>
      <Card>
        {/* Competitor logos */}
        {/* Title */}
        {/* Description */}
      </Card>
    </Link>
  );
}
```

### 3. Page Sections

1. **Hero Section**
   - Title: "Compare SEO Tools"
   - Description: "Find the right SEO tool for your needs"
   - CTA: "See our comparisons"

2. **Tier 1 Comparisons** (High priority)
   - SEOIndicator vs Ahrefs
   - SEOIndicator vs SEMrush
   - SEOIndicator vs Moz
   - SEOIndicator vs Ubersuggest

3. **Tier 2 Comparisons** (Medium priority)
   - SEOIndicator vs Mangools
   - SEOIndicator vs Serpstat
   - SEOIndicator vs SpyFu
   - SEOIndicator vs SE Ranking

4. **Cross-Competitor Comparisons**
   - Ahrefs vs SEMrush
   - Moz vs Ahrefs
   - SEMrush vs Moz

5. **CTA Section**
   - "Try SEOIndicator Free"
   - "View Features"

## Acceptance Criteria
- [ ] Page accessible at `/compare`
- [ ] All comparisons displayed in organized sections
- [ ] ComparisonCard component renders correctly
- [ ] Links to individual comparison pages work
- [ ] Mobile responsive grid layout
- [ ] SEO metadata properly set
- [ ] FAQ schema for common questions

## Dependencies
- TASK-32-2 (Registry for slug and competitor data)

## Effort Estimate
`Small` (2-3 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/app/compare/page.tsx` | Create |
| `frontend/components/compare/ComparisonCard.tsx` | Create |

## Technical Notes
- Use existing Card component from shadcn/ui
- Follow existing page layout patterns
- Consider adding filtering/sorting for future enhancement
- Implement schema markup for SEO

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Entry point for comparison pages.
