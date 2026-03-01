# TESTS/TASK-20-1: Guide Data Structure and TypeScript Types

## Task Reference
- **Task:** TASK-20-1
- **Specification:** SPEC-020 (SEO Guides Library)
- **Status:** Tests Coverage In Progress

## Overview
Tests for TypeScript interfaces and helper functions that define the SEO guide data structure. These tests verify type safety, data integrity, and helper function behavior before implementation.

## Test Files

| File | Purpose | Tests |
|------|---------|-------|
| `frontend/__tests__/lib/guides/types.test.ts` | Interface validation and type guards | 25+ |
| `frontend/__tests__/lib/guides/registry.test.ts` | Guide registry and helper functions | 30+ |

## Test Scenarios

### Types Validation (`types.test.ts`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| T-001 | Valid SEOGuide object | Complete SEOGuide with all required fields | Passes validation |
| T-002 | SEOGuide missing slug | SEOGuide without slug property | Throws validation error |
| T-003 | SEOGuide missing title | SEOGuide without title property | Throws validation error |
| T-004 | SEOGuide invalid category | category: 'invalid' | Throws validation error |
| T-005 | SEOGuide valid categories | category: 'on-page', 'technical', 'content' | All pass validation |
| T-006 | SEOGuide optional toolSlug | SEOGuide without toolSlug | Passes validation |
| T-007 | SEOGuide with toolSlug | SEOGuide with toolSlug: 'meta-tag-analyzer' | Passes validation |
| T-008 | SEOGuide icon types | icon: emoji string or component name | Both pass validation |
| T-009 | SEOGuide readTime type | readTime: number (minutes) | Passes validation |
| T-010 | SEOGuide readTime invalid | readTime: '5 min' (string) | Throws validation error |
| T-011 | GuideSection valid | Complete GuideSection with title, content | Passes validation |
| T-012 | GuideSection with codeExamples | GuideSection with codeExamples array | Passes validation |
| T-013 | CodeExample valid | Complete CodeExample with language, code | Passes validation |
| T-014 | CodeExample missing language | CodeExample without language | Throws validation error |
| T-015 | ChecklistItem valid | Complete ChecklistItem with text, priority | Passes validation |
| T-016 | ChecklistItem priority levels | priority: 'critical', 'important', 'recommended' | All pass validation |
| T-017 | ChecklistItem invalid priority | priority: 'low' | Throws validation error |
| T-018 | SEOGuide sections array | sections: GuideSection[] | Passes validation |
| T-019 | SEOGuide checklist array | checklist: ChecklistItem[] | Passes validation |
| T-020 | SEOGuide relatedGuides | relatedGuides: string[] (slugs) | Passes validation |
| T-021 | SEOGuide empty sections | sections: [] | Passes validation (optional content) |
| T-022 | SEOGuide slug format | slug: 'meta-title' (kebab-case) | Passes validation |
| T-023 | SEOGuide invalid slug format | slug: 'Meta Title' (spaces, uppercase) | Passes but flagged for review |
| T-024 | Type guard isSEOGuide() | Valid SEOGuide object | Returns true |
| T-025 | Type guard isSEOGuide() | Invalid object | Returns false |

### Registry Functions (`registry.test.ts`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| R-001 | getGuideBySlug valid | 'meta-title' | Returns SEOGuide object |
| R-002 | getGuideBySlug invalid | 'non-existent-guide' | Returns null |
| R-003 | getGuideBySlug empty | '' | Returns null |
| R-004 | getGuideBySlug case-sensitive | 'Meta-Title' | Returns null (case-sensitive) |
| R-005 | getAllGuides | No input | Returns array of 17 guides |
| R-006 | getAllGuides count | No input | Array length === 17 |
| R-007 | getAllGuides all have required fields | No input | All guides have slug, title, summary, category |
| R-008 | getAllGuides unique slugs | No input | All slugs are unique |
| R-009 | getRelatedGuides valid | 'meta-title' | Returns array of related guide slugs |
| R-010 | getRelatedGuides invalid slug | 'non-existent' | Returns empty array |
| R-011 | getRelatedGuides max 3 results | Any valid slug | Returns max 3 related guides |
| R-012 | getRelatedGuides same category | 'meta-title' | Related guides include same category |
| R-013 | getRelatedGuides excludes self | 'meta-title' | Does not include 'meta-title' in results |
| R-014 | Registry structure | All guides | Each has stub data structure |
| R-015 | Category filter helpers | 'on-page' | Returns guides filtered by category |
| R-016 | Category filter helpers | 'technical' | Returns guides filtered by category |
| R-017 | Category filter helpers | 'content' | Returns guides filtered by category |
| R-018 | getGuidesByToolSlug | 'meta-tag-analyzer' | Returns guides linked to that tool |
| R-019 | getGuidesByToolSlug no tool | 'non-existent-tool' | Returns empty array |
| R-020 | getGuidesWithTools | No input | Returns guides that have toolSlug |
| R-021 | getGuidesWithoutTools | No input | Returns guides without toolSlug |
| R-022 | Guide slug matches pattern | All guides | Slug matches expected format |
| R-023 | Guide titles are unique | All guides | No duplicate titles |
| R-024 | Guide summaries exist | All guides | Each guide has non-empty summary |
| R-025 | Registry immutability | Modify returned guide | Does not affect original registry |

## Mock Data

### Valid SEOGuide Object
```typescript
const validSEOGuide: SEOGuide = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to optimize your page titles for better SEO.',
  category: 'on-page',
  toolSlug: 'meta-tag-analyzer',
  icon: '📝',
  readTime: 5,
  sections: [
    {
      title: 'What is a Meta Title?',
      content: 'The meta title is the title of your webpage...',
      codeExamples: [
        {
          language: 'html',
          code: '<title>Your Page Title</title>',
          description: 'Basic title tag'
        }
      ]
    }
  ],
  checklist: [
    { text: 'Keep title under 60 characters', priority: 'critical' },
    { text: 'Include target keyword', priority: 'important' },
    { text: 'Make it compelling for clicks', priority: 'recommended' }
  ],
  relatedGuides: ['meta-description', 'h1-headings']
};
```

### Valid GuideSection Object
```typescript
const validGuideSection: GuideSection = {
  title: 'Section Title',
  content: 'Section content in markdown format.',
  codeExamples: [
    {
      language: 'html',
      code: '<title>Example</title>',
      description: 'Example title tag'
    }
  ]
};
```

### Valid ChecklistItem Object
```typescript
const validChecklistItem: ChecklistItem = {
  text: 'Checklist item text',
  priority: 'important'
};
```

### Valid CodeExample Object
```typescript
const validCodeExample: CodeExample = {
  language: 'html',
  code: '<title>Example</title>',
  description: 'Example title tag'
};
```

### All 17 Guide Slugs
```typescript
const EXPECTED_GUIDE_SLUGS = [
  'meta-title',
  'meta-description',
  'h1-headings',
  'heading-hierarchy',
  'image-alt-text',
  'image-optimization',
  'internal-links',
  'external-links',
  'page-speed',
  'mobile-friendly',
  'https-security',
  'structured-data',
  'canonical-urls',
  'open-graph',
  'twitter-cards',
  'robots-txt',
  'xml-sitemap'
];
```

## Coverage Targets

| Metric | Target |
|--------|--------|
| Type validation branches | 100% |
| Helper function branches | 100% |
| Edge cases | 100% |
| Overall coverage | 95%+ |

## Test Execution

```bash
# Run all tests for this task
npm test -- --testPathPattern="guides"

# Run types tests only
npm test -- frontend/__tests__/lib/guides/types.test.ts

# Run registry tests only
npm test -- frontend/__tests__/lib/guides/registry.test.ts

# Run with coverage
npm test -- --coverage --testPathPattern="guides"
```

## Dependencies
- TypeScript strict mode
- Jest test framework
- Frontend types: `frontend/lib/guides/types.ts`
- Frontend registry: `frontend/lib/guides/registry.ts`

## Notes for Developer

1. **Type Safety:** Use TypeScript's type system to enforce interfaces at compile time
2. **Runtime Validation:** Consider using Zod for runtime validation if guides are loaded dynamically
3. **Immutability:** Registry should return copies, not references to original objects
4. **Slug Format:** All slugs should be lowercase kebab-case for URL compatibility
5. **Category Enum:** Use TypeScript union type for category: `'on-page' | 'technical' | 'content'`
6. **Priority Enum:** Use TypeScript union type for priority: `'critical' | 'important' | 'recommended'`

## Comments

### [2026-03-01 11:50 GMT+2] PreTester - Test Design Started
Created test documentation for TASK-20-1. Writing test files for:
- TypeScript interface validation
- Guide registry helper functions
- Edge cases for all inputs

Status: `Tests Coverage In Progress`
