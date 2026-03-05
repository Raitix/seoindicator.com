# TASK-32-4: Implement Comparison Index Page

## STATUS
`To Write Tests`

## PRIORITY
`High`

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

### [2026-03-05 20:40 GMT+2] Agent: ProjectManager
**Priority Assessment:** HIGH (upgraded from Medium)
- Entry point for comparison pages - critical for user discovery
- Part of Phase 1 Infrastructure
- Enables SEO landing page for comparison traffic
- No blockers - TASK-32-2 (Registry) is Done ✅

**Risk Assessment:** LOW (confirmed)
- Standard Next.js page component
- No external API calls
- No database access
- Uses existing shadcn/ui Card component
- Static content (no user input)

**Dependency Status:**
- TASK-32-2 (Registry): `Done` ✅ - Registry available for competitor data
- TASK-32-1 (Types): `Done` ✅ - Interfaces defined
- TASK-32-3 (Page Route): `BLOCKED - Critical Bug` ⚠️ - Individual comparison pages broken

**Note on TASK-32-3 Blocker:**
- Individual comparison pages (/compare/[slug]) currently show 404 in production
- This task creates the index page that links to those pages
- Index page can proceed independently - provides SEO value
- Broken links are a known issue tracked in TASK-32-3
- Once TASK-32-3 is fixed, index page links will work

**TDD Ready:** PreTester CAN design tests immediately:
- Test page renders at `/compare` route
- Test ComparisonCard component renders correctly
- Test cards display Tier 1 + Tier 2 + Cross-competitor sections
- Test links point to correct comparison slugs
- Test mobile responsive grid layout
- Test SEO metadata (title, description)
- Test FAQ schema implementation

**Key Deliverables:**
1. `/compare` index page with organized sections
2. ComparisonCard component for comparison links
3. Hero section + CTA section
4. FAQ schema for SEO
5. Mobile responsive grid

**Execution Notes:**
- Use existing Card component from shadcn/ui
- Follow existing page layout patterns
- ComparisonCard can be simple - just link + title + description
- Consider adding filtering/sorting for future (out of scope for MVP)

**Status updated to:** `To Write Tests` for PreTester pickup
