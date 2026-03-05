# TASK-32-2: Implement Comparison Registry and Data Loading

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Specification Reference
**SPEC-32**: Competitor Comparison Landing Pages (Programmatic SEO)

## Description
Create the registry system that manages competitor data and generates comparison data structures. This module provides helper functions for loading, validating, and transforming competitor data.

## Deliverables

### 1. Registry File (`frontend/lib/compare/registry.ts`)

```typescript
import { CompetitorData, ComparisonData, FAQ } from './types';
import { ahrefs } from './competitors/ahrefs';
import { semrush } from './competitors/semrush';
// ... other competitors

// Registry of all competitors
export const COMPETITOR_REGISTRY: Record<string, CompetitorData> = {
  seoindicator: SEOINDICATOR_DATA,
  ahrefs,
  semrush,
  moz,
  ubersuggest,
  // ... others
};

// All valid comparison slugs
export const COMPARISON_SLUGS: string[] = [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'ahrefs-vs-semrush',
  // ... cross-competitor comparisons
];

// Helper functions
export function getCompetitorById(id: string): CompetitorData | null;
export function getComparisonBySlug(slug: string): ComparisonData | null;
export function parseSlug(slug: string): [string, string] | null;
export function generateComparisonData(competitorA: string, competitorB: string): ComparisonData;
export function getRelatedComparisons(competitorId: string): string[];
export function validateCompetitorData(data: CompetitorData): boolean;
```

### 2. SEOIndicator Data (`frontend/lib/compare/competitors/seoindicator.ts`)

Create the canonical SEOIndicator competitor data file with:
- Pricing tiers (Free, Pro)
- Feature categories
- Pros and cons
- Use cases

### 3. Validation Functions

```typescript
export function validateCompetitorData(data: CompetitorData): boolean {
  // Check required fields
  // Validate pricing structure
  // Validate feature categories
  // Return true/false
}

export function validateComparisonData(data: ComparisonData): boolean {
  // Check required fields
  // Validate slug format
  // Check competitor IDs exist
  // Return true/false
}
```

## Acceptance Criteria
- [ ] Registry exports all competitors from single entry point
- [ ] `getComparisonBySlug()` returns valid `ComparisonData` for all slugs
- [ ] `parseSlug()` correctly extracts competitor IDs from slug
- [ ] Invalid slugs return `null` (not throw errors)
- [ ] Validation functions catch malformed data
- [ ] SEOIndicator data file created with accurate info
- [ ] Unit tests for all helper functions

## Dependencies
- TASK-32-1 (Types and Interfaces)

## Effort Estimate
`Medium` (3-4 hours)

## Files to Create/Modify
| File | Action |
|------|--------|
| `frontend/lib/compare/registry.ts` | Create |
| `frontend/lib/compare/competitors/seoindicator.ts` | Create |

## Technical Notes
- Use lazy loading for competitor data to optimize bundle size
- Cache parsed comparison data
- Handle edge cases (missing competitor, invalid slug format)
- Follow existing project patterns for data loading

---

## Comments
### [2026-03-05 14:55 GMT+2] Agent: SpecSystemAnalyst
Task created as part of SPEC-32 breakdown. Depends on TASK-32-1 for type definitions.
