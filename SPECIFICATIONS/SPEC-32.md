# SPEC-32: Competitor Comparison Landing Pages (Programmatic SEO)

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Feature Reference
**FEAT-032**: Competitor Comparison Landing Pages (Programmatic SEO)

## Overview

Create a programmatic SEO system that generates comparison landing pages targeting long-tail keywords like "SEOIndicator vs [competitor]". These pages capture high-intent organic traffic from users actively researching SEO tools, driving conversions through honest, feature-rich comparisons.

## Technical Architecture

### Page Generation Strategy

| Approach | Description | Recommendation |
|----------|-------------|----------------|
| Static Generation | Pre-built at deploy time | **Recommended** - Zero runtime cost |
| Dynamic Rendering | Built on request | Not recommended - Server overhead |
| ISR (Incremental Static Regeneration) | Static with periodic rebuilds | Optional for future enhancement |

**Decision**: Static generation via Next.js `generateStaticParams()` for optimal performance and zero runtime cost.

### URL Structure

```
/compare/seoindicator-vs-ahrefs/
/compare/seoindicator-vs-semrush/
/compare/seoindicator-vs-moz/
/compare/ahrefs-vs-semrush/        # Cross-competitor comparisons
```

**Pattern**: `/compare/[competitor-a]-vs-[competitor-b]/`

### Directory Structure

```
frontend/
├── app/
│   └── compare/
│       ├── page.tsx                    # Compare index page
│       └── [slug]/
│           ├── page.tsx                # Dynamic comparison page
│           └── not-found.tsx           # 404 for invalid slugs
├── lib/
│   └── compare/
│       ├── registry.ts                 # Comparison data and helpers
│       ├── types.ts                    # TypeScript interfaces
│       └── competitors/                # Competitor data files
│           ├── ahrefs.ts
│           ├── semrush.ts
│           ├── moz.ts
│           └── ...
├── components/
│   └── compare/
│       ├── ComparisonTable.tsx         # Feature comparison table
│       ├── PricingComparison.tsx       # Pricing tier comparison
│       ├── ProsConsCard.tsx            # Pros/cons display
│       ├── UseCaseSection.tsx          # Use-case recommendations
│       ├── ComparisonCTA.tsx           # Call-to-action component
│       └── ComparisonSchema.tsx        # JSON-LD schema injection
└── __tests__/
    └── compare/
        └── ...
```

## Component Specifications

### 1. ComparisonPage Component (`app/compare/[slug]/page.tsx`)

**Responsibilities:**
- Parse slug to extract competitor names
- Fetch comparison data from registry
- Render structured comparison layout
- Inject JSON-LD schema

**Props Interface:**
```typescript
interface ComparisonPageProps {
  params: { slug: string };
}
```

**Static Generation:**
```typescript
export function generateStaticParams(): { slug: string }[] {
  return COMPARISON_SLUGS.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }): Promise<Metadata> {
  const comparison = getComparisonBySlug(params.slug);
  return {
    title: `${comparison.title} | SEO Tool Comparison`,
    description: comparison.metaDescription,
    openGraph: { title, description, images: [ogImage] },
  };
}
```

### 2. ComparisonTable Component

**Features:**
- Responsive table with horizontal scroll on mobile
- Feature categories (On-Page, Technical, Backlinks, etc.)
- Check/X icons for feature availability
- Highlighted cells for competitive advantages
- Expandable feature details

**Props Interface:**
```typescript
interface ComparisonTableProps {
  features: FeatureCategory[];
  highlightWinner?: boolean;
  showTooltips?: boolean;
}

interface FeatureCategory {
  name: string;
  features: Feature[];
}

interface Feature {
  name: string;
  seoindicator: boolean | string;
  competitor: boolean | string;
  tooltip?: string;
}
```

### 3. PricingComparison Component

**Features:**
- Tier-by-tier pricing comparison
- Free tier highlighting (SEOIndicator advantage)
- Feature breakdown per tier
- Annual/monthly toggle
- Currency conversion support

**Props Interface:**
```typescript
interface PricingComparisonProps {
  seoindicator: PricingTier[];
  competitor: PricingTier[];
  showFreeTier?: boolean;
}

interface PricingTier {
  name: string;
  monthlyPrice: number | 'Free';
  annualPrice: number | 'Free';
  features: string[];
  highlighted?: boolean;
}
```

### 4. ProsConsCard Component

**Features:**
- Side-by-side pros/cons for each tool
- Balanced, honest assessment
- User review highlights (optional future feature)

**Props Interface:**
```typescript
interface ProsConsCardProps {
  tool: string;
  pros: string[];
  cons: string[];
  overallRating?: number;
}
```

### 5. UseCaseSection Component

**Features:**
- Who should use each tool
- Specific scenarios where each excels
- Budget considerations
- Team size recommendations

**Props Interface:**
```typescript
interface UseCaseSectionProps {
  seoindicator: UseCase[];
  competitor: UseCase[];
}

interface UseCase {
  title: string;
  description: string;
  audience: 'solo' | 'small-team' | 'enterprise' | 'agency';
  budget: 'free' | 'budget' | 'mid-range' | 'premium';
}
```

### 6. ComparisonCTA Component

**Features:**
- Primary CTA: "Start Free Trial"
- Secondary CTA: "View Features"
- A/B test variant support
- Sticky CTA on mobile scroll

**Props Interface:**
```typescript
interface ComparisonCTAProps {
  variant?: 'primary' | 'secondary' | 'sticky';
  text?: string;
  href?: string;
  abTestGroup?: string;
}
```

### 7. ComparisonSchema Component

**Schemas to Implement:**

| Schema Type | Purpose |
|-------------|---------|
| FAQPage | Common comparison questions |
| BreadcrumbList | Navigation trail |
| HowTo | Steps to choose between tools |
| Product | Tool representation for each |

**Implementation:**
```typescript
interface ComparisonSchemaProps {
  comparison: ComparisonData;
  faqs: FAQ[];
}
```

## Data Models

### Competitor Data Structure

```typescript
interface CompetitorData {
  id: string;                    // 'ahrefs', 'semrush', etc.
  name: string;                  // Display name
  slug: string;                  // URL-safe name
  website: string;               // Official website
  logo?: string;                 // Logo URL (optional)
  tagline: string;               // Short description
  pricing: PricingTier[];
  features: FeatureCategory[];
  pros: string[];
  cons: string[];
  useCases: UseCase[];
  lastUpdated: string;           // ISO date for freshness tracking
  source: string;                // Data source attribution
}
```

### Comparison Data Structure

```typescript
interface ComparisonData {
  slug: string;                  // 'seoindicator-vs-ahrefs'
  title: string;                 // 'SEOIndicator vs Ahrefs: Complete Comparison'
  metaDescription: string;       // SEO meta description
  competitors: [string, string]; // ['seoindicator', 'ahrefs']
  summary: string;               // Executive summary
  featureComparison: FeatureCategory[];
  pricingComparison: {
    seoindicator: PricingTier[];
    competitor: PricingTier[];
  };
  prosCons: {
    seoindicator: { pros: string[]; cons: string[] };
    competitor: { pros: string[]; cons: string[] };
  };
  useCases: {
    seoindicator: UseCase[];
    competitor: UseCase[];
  };
  verdict: string;               // Honest recommendation
  faqs: FAQ[];
  relatedComparisons: string[];  // Slugs of related comparisons
}
```

## Competitor Coverage

### Tier 1 (Priority - High Search Volume)

| Competitor | Search Volume Est. | Priority |
|------------|-------------------|----------|
| Ahrefs | High | P1 |
| SEMrush | High | P1 |
| Moz | Medium-High | P1 |
| Ubersuggest | Medium | P1 |

### Tier 2 (Medium Search Volume)

| Competitor | Search Volume Est. | Priority |
|------------|-------------------|----------|
| Mangools | Medium | P2 |
| Serpstat | Medium | P2 |
| SpyFu | Medium | P2 |
| SE Ranking | Medium | P2 |

### Tier 3 (Niche Competitors)

| Competitor | Search Volume Est. | Priority |
|------------|-------------------|----------|
| Raven Tools | Low | P3 |
| BrightEdge | Low | P3 |
| Conductor | Low | P3 |
| Wincher | Low | P3 |

### Tier 4 (Free Alternatives)

| Competitor | Search Volume Est. | Priority |
|------------|-------------------|----------|
| Google Search Console | Medium | P2 |
| Google Analytics | Medium | P2 |
| AnswerThePublic Free | Low | P3 |

### Cross-Competitor Comparisons

| Comparison | Keyword Target |
|------------|---------------|
| Ahrefs vs SEMrush | 'ahrefs vs semrush' |
| Moz vs Ahrefs | 'moz vs ahrefs' |
| SEMrush vs Moz | 'semrush vs moz' |

## Schema Markup

### FAQPage Schema

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What's the difference between SEOIndicator and Ahrefs?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "SEOIndicator focuses on..."
      }
    }
  ]
}
```

### BreadcrumbList Schema

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://seoindicator.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Compare",
      "item": "https://seoindicator.com/compare/"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "SEOIndicator vs Ahrefs",
      "item": "https://seoindicator.com/compare/seoindicator-vs-ahrefs/"
    }
  ]
}
```

## SEO Considerations

### On-Page SEO

| Element | Specification |
|---------|--------------|
| Title | `{Competitor A} vs {Competitor B}: Feature & Pricing Comparison [Year]` |
| Meta Description | 150-160 characters, include both tool names and key differentiator |
| H1 | `{Competitor A} vs {Competitor B}: Which SEO Tool Is Right for You?` |
| H2 | Feature sections, Pricing, Pros/Cons, Use Cases, FAQ |
| URL | `/compare/{competitor-a}-vs-{competitor-b}/` |
| Canonical | Self-referencing |
| Internal Links | Link to related comparisons and SEO guides |

### Technical SEO

| Element | Implementation |
|---------|---------------|
| Sitemap | Add all comparison URLs to XML sitemap |
| Robots.txt | Allow all `/compare/` paths |
| Structured Data | FAQPage, BreadcrumbList, HowTo schemas |
| Open Graph | Title, description, custom OG image |
| Twitter Cards | Summary large image |
| Page Speed | Static generation, optimized images, minimal JS |

## Internal Linking Strategy

### From Comparison Pages

| Destination | Context |
|-------------|---------|
| SEO Guides | Link when discussing specific features |
| Features Page | Link for "See all SEOIndicator features" |
| Pricing Page | Link for detailed pricing |
| Related Comparisons | Link at bottom of page |

### To Comparison Pages

| Source | Context |
|--------|---------|
| Homepage | "How we compare" section |
| SEO Guides | "Compare with alternatives" callout |
| Pricing Page | "Compare with alternatives" section |
| Footer | "Compare" link in navigation |

## Content Requirements

### Per-Comparison Page

| Section | Word Count | Purpose |
|---------|-----------|---------|
| Executive Summary | 100-150 | Quick overview |
| Feature Comparison | 300-500 | Detailed feature breakdown |
| Pricing Comparison | 200-300 | Cost analysis |
| Pros & Cons | 150-200 | Balanced assessment |
| Use Case Recommendations | 200-300 | Audience targeting |
| FAQ | 200-400 | Common questions |
| Verdict | 100-150 | Honest recommendation |

**Total per page**: ~1,050-1,800 words

### Content Quality Standards

- **Honesty**: Never make false claims about competitors
- **Accuracy**: Verify all feature claims before publishing
- **Freshness**: Update pricing quarterly
- **Balance**: Acknowledge competitor strengths
- **Clarity**: Use simple language, avoid jargon

## Task Breakdown (For SpecSystemAnalyst)

### Phase 1: Infrastructure
- TASK-32-1: Create comparison types and interfaces
- TASK-32-2: Implement comparison registry and data loading
- TASK-32-3: Create comparison page route with static generation
- TASK-32-4: Implement comparison index page

### Phase 2: Components
- TASK-32-5: ComparisonTable component
- TASK-32-6: PricingComparison component
- TASK-32-7: ProsConsCard component
- TASK-32-8: UseCaseSection component
- TASK-32-9: ComparisonCTA component
- TASK-32-10: ComparisonSchema component

### Phase 3: Competitor Data
- TASK-32-11: Tier 1 competitor data (Ahrefs, SEMrush, Moz, Ubersuggest)
- TASK-32-12: Tier 2 competitor data (Mangools, Serpstat, SpyFu, SE Ranking)
- TASK-32-13: Tier 3 competitor data (Raven, BrightEdge, Conductor, Wincher)
- TASK-32-14: Free alternatives data (GSC, GA, AnswerThePublic)

### Phase 4: SEO & Integration
- TASK-32-15: Sitemap integration
- TASK-32-16: Internal linking implementation
- TASK-32-17: OG image generation for comparisons
- TASK-32-18: A/B test infrastructure for CTAs

### Phase 5: Testing & Launch
- TASK-32-19: Unit tests for comparison components
- TASK-32-20: Integration tests for page generation
- TASK-32-21: E2E tests for comparison pages
- TASK-32-22: Performance optimization and launch

## Security Considerations

| Risk | Mitigation |
|------|------------|
| XSS via competitor data | Sanitize all competitor data, no raw HTML |
| Trademark infringement | Use competitor names in nominative fair use context only |
| Defamation risk | Ensure all claims are factual and verifiable |
| Data accuracy | Regular reviews, clear update dates |

## Performance Targets

| Metric | Target |
|--------|--------|
| Page Load Time | < 2s (LCP) |
| Time to Interactive | < 3s |
| Bundle Size | < 50KB per page |
| Build Time | < 30s for 20+ pages |
| Lighthouse Score | > 90 |

## Analytics Integration

| Event | Category | Purpose |
|-------|----------|---------|
| comparison_view | Page View | Track which comparisons are viewed |
| cta_click | Conversion | Track CTA clicks per comparison |
| pricing_toggle | Engagement | Track pricing tier interest |
| faq_expand | Engagement | Track FAQ interest |

## Success Metrics

| Metric | Target | Timeline |
|--------|--------|----------|
| Organic Sessions | +50 sessions/month | 3 months |
| Comparison Page Views | 100/month | 3 months |
| CTA Click Rate | > 5% | 3 months |
| Bounce Rate | < 40% | 3 months |
| Average Engagement | > 120s | 3 months |

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Next.js | ✅ Available | Existing frontend framework |
| Shadcn/UI | ✅ Available | Existing component library |
| Tailwind CSS | ✅ Available | Existing styling |
| FEAT-020 (SEO Guides) | ✅ Deployed | Internal linking target |
| Google Analytics | ✅ Configured | Event tracking |

## Legal & Compliance

### Trademark Usage Guidelines

1. **Nominative Fair Use**: Using competitor names to compare is legally protected
2. **Accurate Claims**: All feature and pricing claims must be verifiable
3. **No False Statements**: Avoid any misleading or false claims
4. **Regular Updates**: Keep pricing and features current
5. **Source Attribution**: Note data sources and update dates

### Content Review Process

1. Legal review before publishing new competitor data
2. Quarterly accuracy audits
3. User feedback integration for corrections

---

## Comments

### [2026-03-04 12:20 GMT+2] Agent: Architect
**Specification created based on FEAT-032 requirements.**

**Key Design Decisions:**
1. Static generation for zero runtime cost (CONSTITUTION.md compliance)
2. Modular component architecture for maintainability
3. Phased rollout starting with Tier 1 competitors
4. Honest, balanced content approach for trust building

**Risk Assessment: LOW**
- No external APIs required
- Static content (no user input)
- Uses existing infrastructure
- Low security risk

**Handoff to SpecSystemAnalyst:**
- 22 tasks identified across 5 phases
- Prioritize Phase 1-2 for MVP
- Phase 3-4 for full rollout
- Phase 5 for optimization

### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
**Task breakdown completed.**

**Created 22 tasks across 5 phases:**

| Phase | Tasks | Description |
|-------|-------|-------------|
| 1 | TASK-32-1 to TASK-32-4 | Infrastructure (types, registry, routes, index page) |
| 2 | TASK-32-5 to TASK-32-10 | Components (table, pricing, pros/cons, use-cases, CTA, schema) |
| 3 | TASK-32-11 to TASK-32-14 | Competitor Data (Tier 1-3 + free alternatives) |
| 4 | TASK-32-15 to TASK-32-18 | SEO & Integration (sitemap, linking, OG images, A/B tests) |
| 5 | TASK-32-19 to TASK-32-22 | Testing & Launch (unit, integration, E2E, performance) |

**Key Dependencies:**
- All tasks depend on TASK-32-1 (Types) as foundation
- Phase 2 components depend on Phase 1 infrastructure
- Phase 5 testing depends on all implementation tasks

**Priority Distribution:**
- High: 13 tasks (infrastructure + core components + Tier 1 data + testing)
- Medium: 6 tasks (UI components + Tier 2 data + SEO integration)
- Low: 3 tasks (Tier 3 data + A/B tests + E2E tests)

**Risk Assessment: LOW (confirmed)**
- No backend changes required
- No external API dependencies
- Static generation only
- Uses existing frontend stack (Next.js, shadcn/ui, Tailwind)

**Handoff to ProjectManager:**
- Ready for prioritization
- Recommend starting with TASK-32-1 (Types) as it blocks all other work
- Phase 1-2 sufficient for MVP (infrastructure + components + Tier 1 data)

---

## Comments

### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
**Task breakdown completed.**

**Tasks Created:** 22 tasks across 5 phases

**Phase 1: Infrastructure (4 tasks)**
- TASK-32-1: Create comparison types and interfaces (HIGH priority, foundation)
- TASK-32-2: Implement comparison registry and data loading
- TASK-32-3: Create comparison page route with static generation
- TASK-32-4: Implement comparison index page

**Phase 2: Components (6 tasks)**
- TASK-32-5: ComparisonTable component (HIGH priority)
- TASK-32-6: PricingComparison component (HIGH priority - conversion)
- TASK-32-7: ProsConsCard component
- TASK-32-8: UseCaseSection component
- TASK-32-9: ComparisonCTA component (HIGH priority - conversion)
- TASK-32-10: ComparisonSchema component (HIGH priority - SEO)

**Phase 3: Competitor Data (4 tasks)**
- TASK-32-11: Tier 1 competitor data - Ahrefs, SEMrush, Moz, Ubersuggest (HIGH priority)
- TASK-32-12: Tier 2 competitor data - Mangools, Serpstat, SpyFu, SE Ranking
- TASK-32-13: Tier 3 competitor data - Raven, BrightEdge, Conductor, Wincher
- TASK-32-14: Free alternatives data - GSC, GA, AnswerThePublic

**Phase 4: SEO & Integration (4 tasks)**
- TASK-32-15: Sitemap integration (HIGH priority)
- TASK-32-16: Internal linking implementation (HIGH priority)
- TASK-32-17: OG image generation for comparisons
- TASK-32-18: A/B test infrastructure for CTAs

**Phase 5: Testing & Launch (4 tasks)**
- TASK-32-19: Unit tests for comparison components (HIGH priority)
- TASK-32-20: Integration tests for page generation (HIGH priority)
- TASK-32-21: E2E tests for comparison pages
- TASK-32-22: Performance optimization and launch (HIGH priority)

**Key Dependencies:**
- TASK-32-1 (Types) is foundation for all other tasks
- TASK-32-2 (Registry) depends on TASK-32-1
- TASK-32-3 (Page) depends on TASK-32-1, TASK-32-2
- Components (TASK-32-5 to TASK-32-10) depend on TASK-32-1
- Testing tasks (TASK-32-19 to TASK-32-21) depend on implementation

**Effort Summary:**
- Phase 1: ~10 hours
- Phase 2: ~18 hours
- Phase 3: ~10 hours
- Phase 4: ~10 hours
- Phase 5: ~14 hours
- **Total: ~62 hours**

**Status:** SPEC-32 and FEAT-032 → `To Prioritize`

**Handoff to ProjectManager:** All tasks ready for prioritization. Recommend prioritizing TASK-32-1, TASK-32-2, TASK-32-3 as MVP core.
