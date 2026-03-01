# TESTS/TASK-20-3: Guide Page Template Component

## Task Reference
- **Task:** TASK-20-3
- **Specification:** SPEC-020 (SEO Guides Library)
- **Status:** Tests Coverage In Progress

## Overview
Tests for the reusable guide page template components that render individual SEO guide pages with consistent layout, breadcrumb, hero, content sections, and navigation.

## Test Files

| File | Purpose | Tests |
|------|---------|-------|
| `frontend/__tests__/app/seo-guides/[slug]/page.test.tsx` | Dynamic routing page with generateStaticParams | 25+ |
| `frontend/__tests__/components/guides/GuideLayout.test.tsx` | Wrapper component for consistent layout | 15+ |
| `frontend/__tests__/components/guides/GuideBreadcrumb.test.tsx` | Breadcrumb navigation | 12+ |
| `frontend/__tests__/components/guides/GuideHero.test.tsx` | Guide hero section | 15+ |
| `frontend/__tests__/components/guides/GuideContent.test.tsx` | Markdown content renderer | 20+ |
| `frontend/__tests__/components/guides/RelatedGuides.test.tsx` | Related guides section | 12+ |
| `frontend/__tests__/components/guides/GuideNavigation.test.tsx` | Prev/next navigation | 15+ |

## Test Scenarios

### Dynamic Page (`[slug]/page.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DP-001 | Valid slug renders page | slug: 'meta-title' | Page renders with guide content |
| DP-002 | Invalid slug returns 404 | slug: 'non-existent' | Returns notFound() |
| DP-003 | generateStaticParams | No input | Returns all 17 slugs |
| DP-004 | generateMetadata valid slug | slug: 'meta-title' | Returns proper metadata |
| DP-005 | generateMetadata invalid slug | slug: 'invalid' | Returns default metadata |
| DP-006 | SEO metadata title | slug: 'meta-title' | Title includes guide title |
| DP-007 | SEO metadata description | slug: 'meta-title' | Description uses guide summary |
| DP-008 | Canonical URL | slug: 'meta-title' | Canonical URL set correctly |
| DP-009 | Open Graph metadata | slug: 'meta-title' | OG title, description set |
| DP-010 | Empty slug | slug: '' | Returns 404 |
| DP-011 | Slug with special chars | slug: 'meta<script>' | Returns 404 (security) |
| DP-012 | Case sensitivity | slug: 'Meta-Title' | Returns 404 |
| DP-013 | Trailing slash | slug: 'meta-title/' | Normalized or 404 |

### GuideLayout (`GuideLayout.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GL-001 | Renders children | children: <div>Test</div> | Children rendered |
| GL-002 | Applies correct classes | Any guide | Has layout container classes |
| GL-003 | Responsive container | Any guide | Uses responsive max-width |
| GL-004 | Proper spacing | Any guide | Has vertical padding |
| GL-005 | Dark mode support | Any guide | Supports dark mode classes |
| GL-006 | With guide prop | Valid SEOGuide | Passes guide to context |
| GL-007 | Accessibility landmark | Any guide | Has main landmark |
| GL-008 | SEO structured data | Valid SEOGuide | Renders JSON-LD schema |

### GuideBreadcrumb (`GuideBreadcrumb.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GB-001 | Renders breadcrumb | slug: 'meta-title' | Shows breadcrumb trail |
| GB-002 | Home link | Any slug | First item links to home |
| GB-003 | Guides index link | Any slug | Second item links to /seo-guides |
| GB-004 | Current guide title | slug: 'meta-title' | Shows guide title |
| GB-005 | Separator rendered | Any slug | Separator between items |
| GB-006 | Current page not linked | Any slug | Last item is text, not link |
| GB-007 | Accessible navigation | Any slug | Has nav and aria-label |
| GB-008 | Schema.org breadcrumb | Any slug | Renders breadcrumb JSON-LD |
| GB-009 | Long title handling | Long guide title | Title truncated if needed |
| GB-010 | Keyboard navigation | Any slug | All links focusable |
| GB-011 | Category in breadcrumb | slug: 'meta-title' | Optional category shown |
| GB-012 | Click navigation | Click on link | Navigates to correct URL |

### GuideHero (`GuideHero.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GH-001 | Renders hero section | Valid SEOGuide | Hero section rendered |
| GH-002 | Displays icon | icon: '📝' | Icon displayed |
| GH-003 | Displays title | title: 'Meta Title...' | Title in h1 |
| GH-004 | Displays summary | summary: 'Learn how...' | Summary displayed |
| GH-005 | Displays read time | readTime: 5 | Shows "5 min read" |
| GH-006 | Displays category badge | category: 'on-page' | Category badge shown |
| GH-007 | Tool badge when present | toolSlug: 'meta-tag-analyzer' | Tool badge shown |
| GH-008 | No tool badge when absent | no toolSlug | Tool badge hidden |
| GH-009 | Responsive layout | Any guide | Hero is responsive |
| GH-010 | Dark mode styling | Any guide | Dark mode supported |
| GH-011 | Accessible heading | Valid SEOGuide | Title is h1 |
| GH-012 | Long title handling | Very long title | Title wraps properly |
| GH-013 | Unicode support | title: 'H1 标题 🚀' | Unicode rendered correctly |
| GH-014 | Zero read time | readTime: 0 | Shows "Quick read" or similar |
| GH-015 | Large read time | readTime: 120 | Shows "2 hr read" |

### GuideContent (`GuideContent.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GC-001 | Renders sections | sections array | All sections rendered |
| GC-002 | Renders section titles | sections with titles | Each title in h2 |
| GC-003 | Renders markdown content | content: '## Heading' | Markdown parsed to HTML |
| GC-004 | Renders code examples | codeExamples array | Code blocks rendered |
| GC-005 | Code syntax highlighting | code with language | Syntax highlighted |
| GC-006 | Empty sections | sections: [] | Nothing rendered |
| GC-007 | Single section | One section | Section rendered correctly |
| GC-008 | Multiple sections | Multiple sections | All rendered in order |
| GC-009 | Links in markdown | content with links | Links open in new tab |
| GC-010 | Images in markdown | content with images | Images with alt text |
| GC-011 | Code block copy button | Code example | Copy button available |
| GC-012 | Lists in markdown | content with lists | UL/OL rendered |
| GC-013 | Blockquotes | content with quotes | Blockquote styled |
| GC-014 | Inline code | content with `code` | Inline code styled |
| GC-015 | Headings hierarchy | h2, h3, h4 | Proper heading levels |
| GC-016 | XSS prevention | content with <script> | Script not executed |
| GC-017 | Special characters | content with <, >, & | Entities escaped |
| GC-018 | Very long content | 10,000+ char content | Renders without crash |
| GC-019 | Unicode in content | Chinese, emoji | Renders correctly |
| GC-020 | Responsive code blocks | Wide code | Horizontal scroll |

### RelatedGuides (`RelatedGuides.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-001 | Renders related guides | relatedGuides array | Section rendered |
| RG-002 | Max 3 related guides | 5 related | Shows max 3 |
| RG-003 | No related guides | relatedGuides: [] | Section hidden |
| RG-004 | Guide cards rendered | Related guides | Card for each |
| RG-005 | Links to guides | Related guide cards | Links to /seo-guides/[slug] |
| RG-006 | Section heading | Any related | Shows "Related Guides" heading |
| RG-007 | Accessible section | Any related | Has section aria-label |
| RG-008 | Keyboard navigation | Related guides | All cards focusable |
| RG-009 | Invalid related slug | Non-existent slug | Card not rendered |
| RG-010 | Responsive grid | Multiple related | Grid layout responsive |
| RG-011 | Same category indicator | Related guides | Optional category match shown |
| RG-012 | Dark mode styling | Any related | Dark mode supported |

### GuideNavigation (`GuideNavigation.test.tsx`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GN-001 | Renders navigation | Valid slug | Nav section rendered |
| GN-002 | Previous guide link | Not first guide | Previous link shown |
| GN-003 | Next guide link | Not last guide | Next link shown |
| GN-004 | First guide | First guide in order | No previous link |
| GN-005 | Last guide | Last guide in order | No next link |
| GN-006 | Previous link correct | slug: 'meta-description' | Links to 'meta-title' |
| GN-007 | Next link correct | slug: 'meta-title' | Links to 'meta-description' |
| GN-008 | Navigation arrows | Valid slug | Arrow icons shown |
| GN-009 | Guide titles in links | Valid slug | Previous/Next titles shown |
| GN-010 | Accessible navigation | Valid slug | Has nav aria-label |
| GN-011 | Keyboard accessible | Valid slug | Tab navigates through |
| GN-012 | Back to index link | Any slug | Link to /seo-guides |
| GN-013 | Order by slug | Multiple guides | Navigation follows slug order |
| GN-014 | Responsive layout | Any slug | Responsive on mobile |
| GN-015 | Dark mode styling | Any slug | Dark mode supported |

## Mock Data

### Valid SEOGuide for Testing
```typescript
const mockGuide: SEOGuide = {
  slug: 'meta-title',
  title: 'Meta Title Optimization',
  summary: 'Learn how to craft compelling page titles that improve click-through rates and search rankings.',
  category: 'on-page',
  toolSlug: 'meta-tag-analyzer',
  icon: '📝',
  readTime: 5,
  sections: [
    {
      title: 'What is a Meta Title?',
      content: 'The **meta title** is the title of your webpage that appears in search results.\n\n## Best Practices\n\n- Keep it under 60 characters\n- Include your target keyword\n- Make it compelling for clicks',
      codeExamples: [
        {
          language: 'html',
          code: '<title>Your Page Title | Brand Name</title>',
          description: 'Basic title tag structure'
        }
      ]
    },
    {
      title: 'Common Mistakes',
      content: 'Avoid these common meta title mistakes:\n\n1. Duplicate titles across pages\n2. Too short or too long titles\n3. Missing brand name',
      codeExamples: []
    }
  ],
  checklist: [
    { text: 'Keep title under 60 characters', priority: 'critical' },
    { text: 'Include target keyword', priority: 'important' },
    { text: 'Add brand name at end', priority: 'recommended' }
  ],
  relatedGuides: ['meta-description', 'h1-headings', 'open-graph']
};
```

### Guide with No Tool
```typescript
const mockGuideNoTool: SEOGuide = {
  slug: 'h1-headings',
  title: 'H1 Heading Structure',
  summary: 'Understand how to use H1 tags effectively for SEO and accessibility.',
  category: 'on-page',
  icon: '🔤',
  readTime: 4,
  sections: [],
  checklist: [],
  relatedGuides: ['heading-hierarchy', 'meta-title']
};
```

### Guide with Long Content
```typescript
const mockGuideLongContent: SEOGuide = {
  ...mockGuide,
  sections: [
    {
      title: 'Comprehensive Guide',
      content: 'A'.repeat(15000), // Very long content
      codeExamples: []
    }
  ]
};
```

### All Guide Slugs (for navigation tests)
```typescript
const ALL_GUIDE_SLUGS = [
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
  'robots-txt',
  'xml-sitemap',
  'open-graph',
  'twitter-cards'
];
```

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| Dynamic Page | 95%+ | Including generateStaticParams |
| GuideLayout | 90%+ | Layout and accessibility |
| GuideBreadcrumb | 95%+ | Navigation and schema |
| GuideHero | 95%+ | Display and responsiveness |
| GuideContent | 95%+ | Markdown rendering, XSS |
| RelatedGuides | 90%+ | Card rendering |
| GuideNavigation | 95%+ | Prev/next logic |
| **Overall** | **95%+** | |

## Test Execution

```bash
# Run all tests for this task
npm test -- --testPathPattern="guides"

# Run specific component tests
npm test -- frontend/__tests__/components/guides/GuideLayout.test.tsx
npm test -- frontend/__tests__/components/guides/GuideBreadcrumb.test.tsx
npm test -- frontend/__tests__/components/guides/GuideHero.test.tsx
npm test -- frontend/__tests__/components/guides/GuideContent.test.tsx
npm test -- frontend/__tests__/components/guides/RelatedGuides.test.tsx
npm test -- frontend/__tests__/components/guides/GuideNavigation.test.tsx
npm test -- frontend/__tests__/app/seo-guides/[slug]/page.test.tsx

# Run with coverage
npm test -- --coverage --testPathPattern="guides"
```

## Dependencies
- React Testing Library
- Jest
- @testing-library/user-event
- react-markdown (for content rendering)
- Next.js dynamic routing
- Types from `lib/guides/types.ts`
- Registry from `lib/guides/registry.ts`

## Notes for Developer

1. **Dynamic Routing:** Use Next.js App Router with `[slug]` folder structure
2. **Static Generation:** Implement `generateStaticParams()` for all 17 guides
3. **Metadata:** Use `generateMetadata()` for SEO
4. **Markdown:** Use `react-markdown` or similar for content rendering
5. **Syntax Highlighting:** Consider `react-syntax-highlighter` or `prism-react-renderer`
6. **404 Handling:** Use Next.js `notFound()` function for invalid slugs
7. **Accessibility:** Ensure all components have proper ARIA attributes
8. **Dark Mode:** All components should support dark mode via Tailwind classes
9. **Responsive:** Test all components at mobile, tablet, and desktop widths
10. **XSS Prevention:** Sanitize markdown content to prevent script injection

## Comments

### [2026-03-01 12:35 GMT+2] PreTester - Test Design Started
Created test documentation for TASK-20-3. Writing test files for:
- Dynamic routing page component
- GuideLayout wrapper
- GuideBreadcrumb navigation
- GuideHero section
- GuideContent markdown renderer
- RelatedGuides section
- GuideNavigation prev/next

Status: `Tests Coverage In Progress`
