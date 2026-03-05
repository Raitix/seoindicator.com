# TEST-32-1: Comparison Types and Interfaces Tests

## Task Reference
- **Task:** TASK-32-1
- **Specification:** SPEC-32 (Competitor Comparison Landing Pages)
- **Feature:** FEAT-032

## Overview
Test suite for TypeScript interfaces and types for the comparison system. Validates all type definitions, required/optional properties, type guards, and runtime validation for the comparison data structures.

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `frontend/__tests__/lib/compare/types.test.ts` | Unit | Type definition and validation tests |

## Test Scenarios

### TS-COMP-001: CompetitorData Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid CompetitorData with all required fields | Complete object | `isCompetitorData()` returns `true` |
| 002 | Missing required field `id` | Object without `id` | `isCompetitorData()` returns `false` |
| 003 | Missing required field `name` | Object without `name` | `isCompetitorData()` returns `false` |
| 004 | Missing required field `slug` | Object without `slug` | `isCompetitorData()` returns `false` |
| 005 | Missing required field `website` | Object without `website` | `isCompetitorData()` returns `false` |
| 006 | Missing required field `tagline` | Object without `tagline` | `isCompetitorData()` returns `false` |
| 007 | Missing required field `pricing` | Object without `pricing` | `isCompetitorData()` returns `false` |
| 008 | Missing required field `features` | Object without `features` | `isCompetitorData()` returns `false` |
| 009 | Missing required field `pros` | Object without `pros` | `isCompetitorData()` returns `false` |
| 010 | Missing required field `cons` | Object without `cons` | `isCompetitorData()` returns `false` |
| 011 | Missing required field `useCases` | Object without `useCases` | `isCompetitorData()` returns `false` |
| 012 | Missing required field `lastUpdated` | Object without `lastUpdated` | `isCompetitorData()` returns `false` |
| 013 | Missing required field `source` | Object without `source` | `isCompetitorData()` returns `false` |
| 014 | Optional field `logo` included | Object with `logo` | `isCompetitorData()` returns `true` |
| 015 | Invalid `id` type (number) | `id: 123` (number) | `isCompetitorData()` returns `false` |
| 016 | Invalid `pricing` type | `pricing: {}` (not array) | `isCompetitorData()` returns `false` |
| 017 | Invalid `features` type | `features: null` | `isCompetitorData()` returns `false` |

### TS-COMP-002: PricingTier Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid PricingTier with all required fields | Complete object | `isPricingTier()` returns `true` |
| 002 | Missing required field `name` | Object without `name` | `isPricingTier()` returns `false` |
| 003 | Missing required field `monthlyPrice` | Object without `monthlyPrice` | `isPricingTier()` returns `false` |
| 004 | Missing required field `annualPrice` | Object without `annualPrice` | `isPricingTier()` returns `false` |
| 005 | Missing required field `features` | Object without `features` | `isPricingTier()` returns `false` |
| 006 | `monthlyPrice` as number | `monthlyPrice: 29` | `isPricingTier()` returns `true` |
| 007 | `monthlyPrice` as 'Free' string | `monthlyPrice: 'Free'` | `isPricingTier()` returns `true` |
| 008 | Optional field `highlighted` | `highlighted: true` | `isPricingTier()` returns `true` |
| 009 | Invalid `monthlyPrice` type | `monthlyPrice: null` | `isPricingTier()` returns `false` |
| 010 | Invalid `features` type | `features: 'string'` | `isPricingTier()` returns `false` |

### TS-COMP-003: FeatureCategory Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid FeatureCategory with all required fields | Complete object | `isFeatureCategory()` returns `true` |
| 002 | Missing required field `name` | Object without `name` | `isFeatureCategory()` returns `false` |
| 003 | Missing required field `features` | Object without `features` | `isFeatureCategory()` returns `false` |
| 004 | Empty features array | `features: []` | `isFeatureCategory()` returns `true` |
| 005 | Features array with valid Feature objects | Array of Feature | `isFeatureCategory()` returns `true` |

### TS-COMP-004: Feature Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid Feature with all required fields | Complete object | `isFeature()` returns `true` |
| 002 | Missing required field `name` | Object without `name` | `isFeature()` returns `false` |
| 003 | Missing required field `seoindicator` | Object without `seoindicator` | `isFeature()` returns `false` |
| 004 | Missing required field `competitor` | Object without `competitor` | `isFeature()` returns `false` |
| 005 | `seoindicator` as boolean | `seoindicator: true` | `isFeature()` returns `true` |
| 006 | `seoindicator` as string | `seoindicator: 'Yes'` | `isFeature()` returns `true` |
| 007 | Optional field `tooltip` | `tooltip: 'Help text'` | `isFeature()` returns `true` |
| 008 | Invalid `seoindicator` type | `seoindicator: null` | `isFeature()` returns `false` |

### TS-COMP-005: UseCase Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid UseCase with all required fields | Complete object | `isUseCase()` returns `true` |
| 002 | Missing required field `title` | Object without `title` | `isUseCase()` returns `false` |
| 003 | Missing required field `description` | Object without `description` | `isUseCase()` returns `false` |
| 004 | Missing required field `audience` | Object without `audience` | `isUseCase()` returns `false` |
| 005 | Missing required field `budget` | Object without `budget` | `isUseCase()` returns `false` |
| 006 | Valid `audience` values | 'solo', 'small-team', 'enterprise', 'agency' | All return `true` |
| 007 | Invalid `audience` value | `audience: 'invalid'` | `isUseCase()` returns `false` |
| 008 | Valid `budget` values | 'free', 'budget', 'mid-range', 'premium' | All return `true` |
| 009 | Invalid `budget` value | `budget: 'expensive'` | `isUseCase()` returns `false` |

### TS-COMP-006: ComparisonData Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid ComparisonData with all required fields | Complete object | `isComparisonData()` returns `true` |
| 002 | Missing required field `slug` | Object without `slug` | `isComparisonData()` returns `false` |
| 003 | Missing required field `title` | Object without `title` | `isComparisonData()` returns `false` |
| 004 | Missing required field `metaDescription` | Object without `metaDescription` | `isComparisonData()` returns `false` |
| 005 | Missing required field `competitors` | Object without `competitors` | `isComparisonData()` returns `false` |
| 006 | Missing required field `summary` | Object without `summary` | `isComparisonData()` returns `false` |
| 007 | Missing required field `featureComparison` | Object without `featureComparison` | `isComparisonData()` returns `false` |
| 008 | Missing required field `pricingComparison` | Object without `pricingComparison` | `isComparisonData()` returns `false` |
| 009 | Missing required field `prosCons` | Object without `prosCons` | `isComparisonData()` returns `false` |
| 010 | Missing required field `useCases` | Object without `useCases` | `isComparisonData()` returns `false` |
| 011 | Missing required field `verdict` | Object without `verdict` | `isComparisonData()` returns `false` |
| 012 | Missing required field `faqs` | Object without `faqs` | `isComparisonData()` returns `false` |
| 013 | Missing required field `relatedComparisons` | Object without `relatedComparisons` | `isComparisonData()` returns `false` |
| 014 | `competitors` as tuple of 2 strings | `['seoindicator', 'ahrefs']` | `isComparisonData()` returns `true` |
| 015 | Invalid `competitors` type | `competitors: ['single']` (1 item) | `isComparisonData()` returns `false` |

### TS-COMP-007: FAQ Interface

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid FAQ with all required fields | Complete object | `isFAQ()` returns `true` |
| 002 | Missing required field `question` | Object without `question` | `isFAQ()` returns `false` |
| 003 | Missing required field `answer` | Object without `answer` | `isFAQ()` returns `false` |
| 004 | Invalid `question` type | `question: 123` | `isFAQ()` returns `false` |
| 005 | Invalid `answer` type | `answer: null` | `isFAQ()` returns `false` |

### TS-COMP-008: Component Props Interfaces

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Valid ComparisonTableProps | Complete object | Type compiles |
| 002 | ComparisonTableProps optional fields | Missing optional fields | Type compiles |
| 003 | Valid PricingComparisonProps | Complete object | Type compiles |
| 004 | Valid ProsConsCardProps | Complete object | Type compiles |
| 005 | ProsConsCardProps optional `overallRating` | Missing `overallRating` | Type compiles |
| 006 | Valid UseCaseSectionProps | Complete object | Type compiles |
| 007 | Valid ComparisonCTAProps | Complete object | Type compiles |
| 008 | ComparisonCTAProps optional fields | Missing optional fields | Type compiles |
| 009 | Valid ComparisonSchemaProps | Complete object | Type compiles |
| 010 | Valid ComparisonPageProps | Complete object | Type compiles |

### TS-COMP-009: TypeScript Compilation

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | All interfaces compile without errors | TypeScript compiler | No compilation errors |
| 002 | No `any` types used | TypeScript compiler | `noImplicitAny: true` passes |
| 003 | All interfaces properly exported | Module imports | All exports accessible |
| 004 | JSDoc comments present | Source file | All public interfaces have JSDoc |

### TS-COMP-010: Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| 001 | Null value | `isCompetitorData(null)` | Returns `false` |
| 002 | Undefined value | `isCompetitorData(undefined)` | Returns `false` |
| 003 | Empty object | `isCompetitorData({})` | Returns `false` |
| 004 | Array instead of object | `isCompetitorData([])` | Returns `false` |
| 005 | String instead of object | `isCompetitorData('string')` | Returns `false` |
| 006 | Unicode characters in strings | `name: '测试工具 🚀'` | Returns `true` |
| 007 | Very long string values | `name: 'a'.repeat(10000)` | Returns `true` |
| 008 | Extra properties on object | Additional properties | Returns `true` (extra props allowed) |

## Mock Data

### Valid CompetitorData
```typescript
const validCompetitorData: CompetitorData = {
  id: 'ahrefs',
  name: 'Ahrefs',
  slug: 'ahrefs',
  website: 'https://ahrefs.com',
  logo: 'https://ahrefs.com/logo.png',
  tagline: 'All-in-one SEO toolset',
  pricing: [
    { name: 'Lite', monthlyPrice: 99, annualPrice: 82, features: ['Feature 1'] }
  ],
  features: [
    { name: 'Backlink Analysis', seoindicator: true, competitor: true }
  ],
  pros: ['Comprehensive data', 'User-friendly'],
  cons: ['Expensive', 'Steeper learning curve'],
  useCases: [
    { title: 'Solo Blogger', description: 'For individual bloggers', audience: 'solo', budget: 'budget' }
  ],
  lastUpdated: '2026-03-05T00:00:00Z',
  source: 'Official website'
};
```

### Valid ComparisonData
```typescript
const validComparisonData: ComparisonData = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Complete Comparison',
  metaDescription: 'Compare SEOIndicator and Ahrefs...',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Both tools offer SEO analysis...',
  featureComparison: [],
  pricingComparison: {
    seoindicator: [{ name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: [] }],
    competitor: [{ name: 'Lite', monthlyPrice: 99, annualPrice: 82, features: [] }]
  },
  prosCons: {
    seoindicator: { pros: ['Free'], cons: ['Limited features'] },
    competitor: { pros: ['Comprehensive'], cons: ['Expensive'] }
  },
  useCases: {
    seoindicator: [],
    competitor: []
  },
  verdict: 'Choose SEOIndicator for budget-conscious users...',
  faqs: [
    { question: 'Is SEOIndicator free?', answer: 'Yes, SEOIndicator offers a free tier.' }
  ],
  relatedComparisons: ['seoindicator-vs-semrush']
};
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| CompetitorData Interface | 100% | 17 |
| PricingTier Interface | 100% | 10 |
| FeatureCategory Interface | 100% | 5 |
| Feature Interface | 100% | 8 |
| UseCase Interface | 100% | 9 |
| ComparisonData Interface | 100% | 15 |
| FAQ Interface | 100% | 5 |
| Component Props | 100% | 10 |
| TypeScript Compilation | 100% | 4 |
| Edge Cases | 100% | 8 |
| **Total** | **100%** | **91** |

## Test Execution

```bash
# Run tests
cd frontend
npm test -- __tests__/lib/compare/types.test.ts

# Run with coverage
npm test -- --coverage __tests__/lib/compare/types.test.ts

# TypeScript compilation check
npx tsc --noEmit lib/compare/types.ts
```

## Dependencies
- None (foundation task)

## Notes for Developer
1. Create `frontend/lib/compare/types.ts` with all interfaces
2. Include type guard functions for runtime validation
3. Add JSDoc comments for all public interfaces
4. Ensure strict TypeScript with no `any` types
5. Export all interfaces for use in other modules

---

## Comments
### [2026-03-05 15:12 GMT+2] Agent: PreTester
Test documentation created with 91 test scenarios covering all interfaces.
Status updated to `Tests Coverage In Progress`.
