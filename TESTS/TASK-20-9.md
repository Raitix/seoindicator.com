# TESTS/TASK-20-9: Image Alt Text Guide Content

## Task Reference
- **Task:** TASK-20-9
- **Specification:** SPEC-020 (SEO Guides Library)
- **Feature:** FEAT-020 (SEO Guides Library + Clean Routing)
- **Status:** Tests Coverage In Progress

## Overview
Tests for Image Alt Text Guide content structure and quality. Validates that the guide content in the registry meets all acceptance criteria including sections, checklist items, code examples, and related guides.

## Test Files

| File | Purpose | Tests |
|------|---------|-------|
| `frontend/__tests__/lib/guides/image-alt-text-content.test.ts` | Content structure and quality validation | 40+ |
| `frontend/__tests__/lib/guides/image-alt-text-code-examples.test.ts` | Code examples validation | 20+ |

## Acceptance Criteria Being Tested

| # | Criterion | Test ID |
|---|-----------|---------|
| AC-001 | Content covers: What is Image Alt Text? | CO-001 to CO-004 |
| AC-002 | Content covers: Why it matters (SEO + accessibility) | WM-001 to WM-006 |
| AC-003 | Content covers: How to write effective alt text | ET-001 to ET-005 |
| AC-004 | Content covers: Common mistakes (keyword stuffing, empty alt) | CM-001 to CM-005 |
| AC-005 | Content covers: Best practices checklist (5-10 items) | BC-001 to BC-006 |
| AC-006 | Content includes 2-3 code examples | CE-001 to CE-010 |
| AC-007 | Read time: 3-5 minutes | RT-001 to RT-003 |
| AC-008 | Related guides: image-optimization, structured-data | RG-001 to RG-004 |
| AC-009 | Content stored in guide registry | GR-001 to GR-003 |

## Test Scenarios

### Content Structure Tests (`image-alt-text-content.test.ts`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CO-001 | Guide exists in registry | slug: 'image-alt-text' | Returns SEOGuide object |
| CO-002 | Guide has correct title | guide.title | Matches expected title pattern |
| CO-003 | Guide has non-empty summary | guide.summary | String length > 20 |
| CO-004 | Guide has correct category | guide.category | Is 'on-page' or 'content' |

### Why It Matters Section Tests (SEO + Accessibility)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| WM-001 | Has "Why it matters" section | sections array | Contains section about importance |
| WM-002 | Section mentions SEO | section content | Contains 'SEO' or 'search engine' |
| WM-003 | Section mentions accessibility | section content | Contains 'accessibility' or 'WCAG' |
| WM-004 | Section mentions screen readers | section content | Contains 'screen reader' |
| WM-005 | Section explains image search benefits | section content | Contains 'Google Image' or 'image search' |
| WM-006 | Section has valid content | section.content | Length > 200 characters |

### How to Write Effective Alt Text Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ET-001 | Has effective alt text section | sections array | Contains section about writing alt text |
| ET-002 | Explains descriptive text | section content | Contains 'descriptive' or 'describe' |
| ET-003 | Mentions length guidelines | section content | Contains 'characters' or 'length' |
| ET-004 | Explains context importance | section content | Contains 'context' |
| ET-005 | Section has valid content | section.content | Length > 150 characters |

### Common Mistakes Section Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CM-001 | Has common mistakes section | sections array | Contains section about mistakes |
| CM-002 | Mentions keyword stuffing | section content | Contains 'keyword stuffing' or 'keyword' |
| CM-003 | Mentions empty alt on meaningful images | section content | Contains 'empty' or 'missing alt' |
| CM-004 | Explains decorative images | section content | Contains 'decorative' or 'alt=""' |
| CM-005 | Section has valid content | section.content | Length > 150 characters |

### Decorative Images (alt="") Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DI-001 | Explains decorative image handling | section content | Contains 'alt=""' or 'empty alt' |
| DI-002 | Explains when to use empty alt | section content | Contains 'decorative' or 'purely decorative' |
| DI-003 | Mentions aria-hidden alternative | section content | Contains 'aria-hidden' (optional) |

### Best Practices Checklist Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BC-001 | Checklist has 5-10 items | checklist array | Length >= 5 && <= 10 |
| BC-002 | All items have valid text | checklist items | Each item has non-empty text |
| BC-003 | All items have valid priority | checklist items | Priority is 'critical', 'important', or 'recommended' |
| BC-004 | At least one critical item | checklist items | At least one with priority 'critical' |
| BC-005 | Covers key alt text practices | checklist items | Covers descriptive, length, decorative |
| BC-006 | Mentions WCAG guidelines | checklist or sections | Contains 'WCAG' reference |

### Code Examples Tests (`image-alt-text-code-examples.test.ts`)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CE-001 | Has 2-3 code examples | codeExamples array | Length >= 2 && <= 3 |
| CE-002 | Has example with good alt text | code examples | Contains descriptive alt example |
| CE-003 | Has example with empty alt | code examples | Contains alt="" example |
| CE-004 | Code example has language | codeExample.language | Is valid language string |
| CE-005 | Code example has code content | codeExample.code | Non-empty string |
| CE-006 | HTML example is valid | code with HTML | Contains <img tag |
| CE-007 | Example descriptions present | codeExample.description | Each has description |
| CE-008 | Code examples are syntactically valid | code content | Parseable HTML |
| CE-009 | Shows keyword stuffing mistake | code examples | May show bad example |
| CE-010 | Shows decorative image example | code examples | Contains alt="" example |

### Read Time Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RT-001 | Read time is set | guide.readTime | Is a number |
| RT-002 | Read time in valid range | guide.readTime | >= 3 && <= 5 |
| RT-003 | Read time matches content | word count | Approximately 200-300 words per minute |

### Related Guides Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RG-001 | Has related guides array | relatedGuides | Is array |
| RG-002 | Contains image-optimization | relatedGuides | Includes 'image-optimization' |
| RG-003 | Contains structured-data | relatedGuides | Includes 'structured-data' |
| RG-004 | Related guides exist | getGuideBySlug | All related guides exist in registry |

### Guide Registry Integration Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GR-001 | Guide in registry | getAllGuides() | Contains image-alt-text |
| GR-002 | getGuideBySlug returns guide | 'image-alt-text' | Returns complete guide |
| GR-003 | Guide validates with type guard | isSEOGuide() | Returns true |

## Mock Data

### Expected Section Titles
```typescript
const EXPECTED_SECTIONS = [
  'What is Image Alt Text?',
  'Why Alt Text Matters',
  'How to Write Effective Alt Text',
  'Common Mistakes',
  'Decorative Images',
  'Best Practices Checklist'
];
```

### Expected Checklist Items (Sample)
```typescript
const EXPECTED_CHECKLIST_TOPICS = [
  'descriptive alt text',
  'empty alt for decorative',
  'keyword stuffing',
  'length',
  'context',
  'WCAG',
  'screen reader'
];
```

### Expected Code Examples Structure
```typescript
const expectedCodeExamples = [
  {
    language: 'html',
    code: '<img src="product.jpg" alt="Red leather handbag with gold buckle" />',
    description: 'Descriptive alt text for product image'
  },
  {
    language: 'html',
    code: '<img src="decorative-border.png" alt="" />',
    description: 'Empty alt for decorative image'
  },
  {
    language: 'html',
    code: '<img src="chart.png" alt="Bar chart showing 25% increase in sales Q4 2025" />',
    description: 'Alt text for complex image with data'
  }
];
```

### Complete Guide Structure (Reference)
```typescript
const imageAltTextGuide: SEOGuide = {
  slug: 'image-alt-text',
  title: 'Image Alt Text for SEO and Accessibility',
  summary: 'Write effective alt text for images to improve SEO and accessibility.',
  category: 'on-page',
  icon: '🖼️',
  readTime: 4,
  sections: [
    {
      title: 'What is Image Alt Text?',
      content: 'Image alt text (alternative text) is an HTML attribute...',
      codeExamples: []
    },
    {
      title: 'Why Alt Text Matters',
      content: 'Alt text serves two main purposes: accessibility and SEO...',
      codeExamples: []
    },
    {
      title: 'How to Write Effective Alt Text',
      content: 'Writing good alt text requires balance between description...',
      codeExamples: []
    },
    {
      title: 'Common Mistakes',
      content: 'Avoid these common alt text mistakes...',
      codeExamples: []
    },
    {
      title: 'Decorative Images',
      content: 'Not all images need descriptive alt text...',
      codeExamples: [
        {
          language: 'html',
          code: '<img src="border.png" alt="" />',
          description: 'Empty alt for decorative image'
        }
      ]
    }
  ],
  checklist: [
    { text: 'Add descriptive alt text to all meaningful images', priority: 'critical' },
    { text: 'Use empty alt="" for decorative images only', priority: 'critical' },
    { text: 'Keep alt text under 125 characters', priority: 'important' },
    { text: 'Avoid keyword stuffing in alt text', priority: 'important' },
    { text: 'Describe the image content, not just keywords', priority: 'important' },
    { text: 'Include context relevant to the page', priority: 'important' },
    { text: 'Follow WCAG 2.1 guidelines for accessibility', priority: 'recommended' }
  ],
  relatedGuides: ['image-optimization', 'structured-data']
};
```

## Coverage Targets

| Metric | Target |
|--------|--------|
| Content structure validation | 100% |
| Acceptance criteria coverage | 100% |
| Code examples validation | 100% |
| Related guides validation | 100% |
| Overall test coverage | 95%+ |

## Test Execution

```bash
# Run all tests for this task
npm test -- --testPathPattern="image-alt-text-content"
npm test -- --testPathPattern="image-alt-text-code-examples"

# Run with coverage
npm test -- --coverage --testPathPattern="image-alt-text"

# Run all guide content tests
npm test -- --testPathPattern="guides.*content"
```

## Dependencies
- `frontend/lib/guides/registry.ts` - Guide data source
- `frontend/lib/guides/types.ts` - Type definitions and guards
- Jest test framework
- TypeScript strict mode

## Notes for Developer

1. **Content Population:** Update the `sections` and `checklist` arrays in registry.ts for `image-alt-text` guide
2. **Read Time Calculation:** Based on word count, typically 200-250 words per minute
3. **Code Examples:** Must include examples of good alt text, empty alt for decorative images, and optionally bad examples
4. **Accessibility Focus:** Mention WCAG 2.1 guidelines and screen readers
5. **SEO Focus:** Explain Google Image Search benefits
6. **Decorative Images:** Explain when to use alt="" vs descriptive alt
7. **Common Mistakes:** Cover keyword stuffing, empty alt on meaningful images
8. **Checklist Items:** 5-10 items with appropriate priority levels
9. **Related Guides:** Must include `image-optimization` and `structured-data`
10. **Content Quality:** Each section should be informative, actionable, and SEO-friendly

## Content Guidelines

### Section Content Requirements
- Each section should have 150-350 words
- Use markdown formatting (bold, lists, links)
- Include practical tips and examples
- Reference WCAG guidelines where appropriate

### Checklist Item Requirements
- Clear, actionable text
- Appropriate priority level
- Covers key alt text practices
- Practical for users to implement

### Code Example Requirements
- Syntactically valid HTML
- Clear description of purpose
- Appropriate language tag
- Shows both good and bad examples (optional)

## Comments

### [2026-03-02 11:45 GMT+2] PreTester - Test Design Started
Created test documentation for TASK-20-9. Tests validate:
- Content structure (sections, checklist)
- SEO and accessibility coverage
- Effective alt text writing guidance
- Common mistakes coverage
- Decorative images handling
- Code examples quality
- Read time accuracy
- Related guides linking

Status: `Tests Coverage In Progress`
