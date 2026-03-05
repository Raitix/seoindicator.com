# TASK-32-2: Implement Comparison Registry and Data Loading

## STATUS
`To Develop`

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

### [2026-03-05 15:10 GMT+2] Agent: ProjectManager
**Priority Assessment:** HIGH (confirmed)
- Foundation task for Phase 1 - enables all comparison data access
- Registry provides single entry point for all competitor data
- Required by TASK-32-3, TASK-32-10, and all Phase 3 data tasks
- No external dependencies beyond TASK-32-1

**Risk Assessment:** LOW (confirmed)
- Pure TypeScript/React module
- No external APIs
- No database access
- Data validation functions are simple boolean checks

**Dependency Status:**
- TASK-32-1 (Types): `To Write Tests` ✅ - Interface definitions available

**TDD Ready:** PreTester CAN design tests immediately:
- Test `getCompetitorById()` returns correct data or null
- Test `getComparisonBySlug()` parses slug correctly
- Test `parseSlug()` extracts competitor IDs from slug format
- Test `validateCompetitorData()` catches malformed data
- Test `generateComparisonData()` produces valid ComparisonData

**Key Deliverables:**
1. COMPETITOR_REGISTRY object with all competitor data
2. COMPARISON_SLUGS array for static generation
3. 6 helper functions for data access and validation
4. SEOIndicator canonical data file

**Execution Notes:**
- Can proceed in parallel with TASK-32-1 tests
- SEOIndicator data should be accurate and up-to-date
- Lazy loading for competitor data recommended for bundle optimization

**Status updated to:** `To Write Tests` for PreTester pickup

### [2026-03-05 16:10 GMT+2] Agent: PreTester
**Test Design Complete:**

**Files Created:**
| File | Tests | Purpose |
|------|-------|---------|
| `TESTS/TEST-32-2.md` | - | Test documentation (140 scenarios) |
| `frontend/__tests__/lib/compare/registry.test.ts` | 140 | Registry structure and helper functions |
| `frontend/__tests__/lib/compare/competitors/seoindicator.test.ts` | 50 | SEOIndicator data validation |

**Test Categories:**
| Category | Tests |
|----------|-------|
| Registry Structure (RG-STR) | 15 |
| getCompetitorById (RG-GET) | 12 |
| getComparisonBySlug (RG-SLUG) | 15 |
| parseSlug (RG-PARSE) | 14 |
| generateComparisonData (RG-GEN) | 15 |
| getRelatedComparisons (RG-REL) | 12 |
| validateCompetitorData (RG-VAL) | 20 |
| validateComparisonData (RG-VCD) | 15 |
| Edge Cases (RG-EDGE) | 10 |
| TypeScript Types (RG-TS) | 5 |
| Acceptance Criteria (AC) | 7 |

**SEOIndicator Data Tests:**
| Category | Tests |
|----------|-------|
| Data Structure (SEO-STR) | 7 |
| Pricing (SEO-PRI) | 10 |
| Features (SEO-FEA) | 9 |
| Pros and Cons (SEO-PROS) | 8 |
| Use Cases (SEO-USE) | 9 |
| Metadata (SEO-MET) | 5 |
| Data Accuracy (SEO-ACC) | 6 |
| Module Export (SEO-EXP) | 3 |
| Integration (SEO-INT) | 3 |

**Key Test Patterns:**
- Mock competitor data modules with virtual mocks
- Use type guards from types.ts for validation
- Test all null/undefined/invalid input cases
- Verify immutability attempts on registry
- Test concurrent access for thread safety
- Memory efficiency tests with repeated calls

**Developer Notes:**
1. Create `frontend/lib/compare/registry.ts` with:
   - `COMPETITOR_REGISTRY`: Record<string, CompetitorData>
   - `COMPARISON_SLUGS`: string[]
   - `getCompetitorById(id)`: CompetitorData | null
   - `getComparisonBySlug(slug)`: ComparisonData | null
   - `parseSlug(slug)`: [string, string] | null
   - `generateComparisonData(a, b)`: ComparisonData | null
   - `getRelatedComparisons(id)`: string[]
   - `validateCompetitorData(data)`: boolean
   - `validateComparisonData(data)`: boolean

2. Create `frontend/lib/compare/competitors/seoindicator.ts` with:
   - Export `seoindicator: CompetitorData`
   - Include accurate pricing (Free + Pro tiers)
   - Feature categories with comparison values
   - Honest pros/cons
   - Target audience use cases

3. Import competitor files in registry:
   ```typescript
   import { seoindicator } from './competitors/seoindicator';
   import { ahrefs } from './competitors/ahrefs';
   // etc.
   ```

4. Consider lazy loading for bundle optimization:
   ```typescript
   // Optional: Dynamic imports for competitors
   export const COMPETITOR_REGISTRY = {
     seoindicator: () => import('./competitors/seoindicator'),
     // ...
   };
   ```

**Status:** Updated to `To Develop`
