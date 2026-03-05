# TASK-32-1: Create Comparison Types and Interfaces

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create TypeScript interfaces and types for the comparison system. This is the foundation task that all other comparison tasks depend on.

## Deliverables

### 1. Types File (`frontend/lib/compare/types.ts`)

```typescript
// Core data structures
export interface CompetitorData {
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

export interface PricingTier {
  name: string;
  monthlyPrice: number | 'Free';
  annualPrice: number | 'Free';
  features: string[];
  highlighted?: boolean;
}

export interface FeatureCategory {
  name: string;
  features: Feature[];
}

export interface Feature {
  name: string;
  seoindicator: boolean | string;
  competitor: boolean | string;
  tooltip?: string;
}

export interface UseCase {
  title: string;
  description: string;
  audience: 'solo' | 'small-team' | 'enterprise' | 'agency';
  budget: 'free' | 'budget' | 'mid-range' | 'premium';
}

export interface ComparisonData {
  slug: string;
  title: string;
  metaDescription: string;
  competitors: [string, string];
  summary: string;
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
  verdict: string;
  faqs: FAQ[];
  relatedComparisons: string[];
}

export interface FAQ {
  question: string;
  answer: string;
}
```

### 2. Component Props Interfaces

```typescript
// Component props
export interface ComparisonTableProps {
  features: FeatureCategory[];
  highlightWinner?: boolean;
  showTooltips?: boolean;
}

export interface PricingComparisonProps {
  seoindicator: PricingTier[];
  competitor: PricingTier[];
  showFreeTier?: boolean;
}

export interface ProsConsCardProps {
  tool: string;
  pros: string[];
  cons: string[];
  overallRating?: number;
}

export interface UseCaseSectionProps {
  seoindicator: UseCase[];
  competitor: UseCase[];
}

export interface ComparisonCTAProps {
  variant?: 'primary' | 'secondary' | 'sticky';
  text?: string;
  href?: string;
  abTestGroup?: string;
}

export interface ComparisonSchemaProps {
  comparison: ComparisonData;
  faqs: FAQ[];
}
```

### 3. Page Props Interface

```typescript
export interface ComparisonPageProps {
  params: { slug: string };
}
```

## Acceptance Criteria
- [ ] All interfaces defined in `frontend/lib/compare/types.ts`
- [ ] TypeScript compiles without errors
- [ ] Interfaces exported for use in other modules
- [ ] JSDoc comments for all public interfaces
- [ ] No `any` types used

## Dependencies
- None (foundation task)

## Effort Estimate
`Small` (1-2 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/lib/compare/types.ts` | Create |

## Technical Notes
- Use strict TypeScript (no `any`)
- Follow existing project TypeScript conventions
- Ensure all properties are properly typed
- Consider future extensibility (e.g., `overallRating` is optional)

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Foundation task with no dependencies.
