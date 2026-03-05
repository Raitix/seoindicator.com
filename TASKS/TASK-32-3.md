# TASK-32-3: Create Comparison Page Route with Static Generation

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Implement the dynamic comparison page route using Next.js App Router with static site generation via `generateStaticParams()`. This is the core page that renders comparison content.

## Deliverables

### 1. Dynamic Route Page (`frontend/app/compare/[slug]/page.tsx`)

```typescript
import { generateStaticParams, generateMetadata } from 'next';
import { getComparisonBySlug, COMPARISON_SLUGS } from '@/lib/compare/registry';
import { ComparisonTable } from '@/components/compare/ComparisonTable';
import { PricingComparison } from '@/components/compare/PricingComparison';
import { ProsConsCard } from '@/components/compare/ProsConsCard';
import { UseCaseSection } from '@/components/compare/UseCaseSection';
import { ComparisonCTA } from '@/components/compare/ComparisonCTA';
import { ComparisonSchema } from '@/components/compare/ComparisonSchema';

export function generateStaticParams(): { slug: string }[] {
  return COMPARISON_SLUGS.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }): Promise<Metadata> {
  const comparison = getComparisonBySlug(params.slug);
  if (!comparison) {
    return { title: 'Comparison Not Found' };
  }
  return {
    title: `${comparison.title} | SEO Tool Comparison`,
    description: comparison.metaDescription,
    openGraph: {
      title: comparison.title,
      description: comparison.metaDescription,
      type: 'article',
    },
  };
}

export default function ComparisonPage({ params }: ComparisonPageProps) {
  const comparison = getComparisonBySlug(params.slug);
  
  if (!comparison) {
    notFound();
  }

  return (
    <main>
      {/* Hero section */}
      {/* Comparison table */}
      {/* Pricing comparison */}
      {/* Pros/cons */}
      {/* Use cases */}
      {/* CTA */}
      {/* Schema */}
    </main>
  );
}
```

### 2. 404 Not Found Page (`frontend/app/compare/[slug]/not-found.tsx`)

```typescript
export default function CompareNotFound() {
  return (
    <div>
      <h1>Comparison Not Found</h1>
      <p>The comparison you're looking for doesn't exist.</p>
      <Link href="/compare">View all comparisons</Link>
    </div>
  );
}
```

### 3. Page Structure Implementation

Implement the complete page layout:
1. Hero section with title and summary
2. Feature comparison table
3. Pricing comparison
4. Pros/cons cards (side-by-side)
5. Use case recommendations
6. FAQ section
7. CTA section
8. Related comparisons links

## Acceptance Criteria
- [ ] `generateStaticParams()` returns all valid comparison slugs
- [ ] `generateMetadata()` generates correct title and description
- [ ] Invalid slugs trigger 404 page
- [ ] Page renders correctly for all comparison slugs
- [ ] Static pages generated at build time (no SSR)
- [ ] Lighthouse score > 90
- [ ] Mobile responsive layout

## Dependencies
- TASK-32-1 (Types)
- TASK-32-2 (Registry)
- TASK-32-5 through TASK-32-10 (Components) - for full implementation

## Effort Estimate
`Medium` (3-4 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/app/compare/[slug]/page.tsx` | Create |
| `frontend/app/compare/[slug]/not-found.tsx` | Create |

## Technical Notes
- Use Next.js 14 App Router patterns
- Implement streaming for large pages if needed
- Ensure proper error boundaries
- Follow existing project layout conventions
- Consider adding loading.tsx for fallback

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Core routing infrastructure for comparison pages.
