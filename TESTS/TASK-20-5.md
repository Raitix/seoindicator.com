# TESTS/TASK-20-5.md — Meta Title Guide Content Tests

## Task Reference
- **Task:** TASK-20-5
- **Title:** Write Content for Meta Title Guide
- **Status:** To Write Tests

## Overview
Comprehensive test suite for validating the Meta Title SEO guide content meets all acceptance criteria.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/lib/guides/meta-title.test.ts` | 47 | Validate guide structure, content, and integration |

## Test Scenarios

### Guide Existence (MT-001 to MT-003)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-001 | meta-title slug exists in GUIDE_SLUGS | GUIDE_SLUGS array | Contains 'meta-title' |
| MT-002 | getGuideBySlug returns guide | 'meta-title' | Returns valid SEOGuide object |
| MT-003 | Guide is valid SEOGuide | guide object | Passes isSEOGuide() type guard |

### Metadata Tests (MT-004 to MT-009)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-004 | Title is descriptive | guide.title | Contains 'meta' and 'title', length > 5 |
| MT-005 | Summary is descriptive | guide.summary | Length 20-200 chars |
| MT-006 | Category is on-page | guide.category | Equals 'on-page' |
| MT-007 | Tool slug links to meta-tag-analyzer | guide.toolSlug | Equals 'meta-tag-analyzer' |
| MT-008 | Icon is defined | guide.icon | Non-empty string |
| MT-009 | Read time is 3-5 minutes | guide.readTime | Between 3 and 5 (inclusive) |

### Required Sections Tests (MT-010 to MT-017)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-010 | Sections array is not empty | guide.sections | Array with length > 0 |
| MT-011 | All sections are valid GuideSection | guide.sections | All pass isGuideSection() |
| MT-012 | "What is Meta Title?" section exists | section titles | Contains definition/what is section |
| MT-013 | SEO impact/importance covered | section content | Contains seo/ranking/ctr keywords |
| MT-014 | Optimization steps covered | section titles/content | Contains optimization/how-to keywords |
| MT-015 | Common mistakes covered | section titles/content | Contains mistake/avoid keywords |
| MT-016 | Each section has 100+ chars content | section.content | All ≥ 100 characters |
| MT-017 | Section titles are unique | section titles | No duplicates |

### Code Examples Tests (MT-018 to MT-023)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-018 | At least one section has code examples | codeExamples arrays | ≥ 1 section with code |
| MT-019 | Total code examples count is 1-5 | all codeExamples | Count between 1 and 5 |
| MT-020 | All code examples are valid | codeExample objects | All pass isCodeExample() |
| MT-021 | Code examples have language field | example.language | Non-empty string |
| MT-022 | Code examples have code field | example.code | Non-empty string |
| MT-023 | Code demonstrates HTML title tags | example.code | Contains title/meta/html |

### Checklist Tests (MT-024 to MT-032)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-024 | Checklist array is not empty | guide.checklist | Array with length > 0 |
| MT-025 | Checklist has 5-10 items | checklist.length | Between 5 and 10 (inclusive) |
| MT-026 | All items are valid ChecklistItem | checklist items | All pass isChecklistItem() |
| MT-027 | All items have 10+ char text | item.text | All ≥ 10 characters |
| MT-028 | All items have valid priority | item.priority | One of: critical, important, recommended |
| MT-029 | At least one critical priority item | item.priority | ≥ 1 item with 'critical' |
| MT-030 | Checklist covers title length | checklist text | Contains length/character/pixel keywords |
| MT-031 | Checklist covers keyword placement | checklist text | Contains keyword/phrase/primary keywords |
| MT-032 | Checklist covers unique titles | checklist text | Contains unique/duplicate/each page keywords |

### Related Guides Tests (MT-033 to MT-038)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-033 | Related guides array is not empty | guide.relatedGuides | Array with length > 0 |
| MT-034 | meta-description is related | relatedGuides | Contains 'meta-description' |
| MT-035 | h1-headings is related | relatedGuides | Contains 'h1-headings' |
| MT-036 | All related guide slugs are valid | relatedGuides | All exist in GUIDE_SLUGS |
| MT-037 | No self-reference in related | relatedGuides | Does NOT contain 'meta-title' |
| MT-038 | Related guides count is 2-5 | relatedGuides.length | Between 2 and 5 |

### Content Quality Tests (MT-039 to MT-044)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-039 | Total content length is 2000+ chars | all section content | Combined length ≥ 2000 |
| MT-040 | Content mentions title length guidelines | content | Contains 50-60/character/pixel/truncat |
| MT-041 | Content mentions Google/SEO practices | content | Contains google/serp/ranking keywords |
| MT-042 | Content mentions CTR | content | Contains click-through/ctr/compelling |
| MT-043 | Content is properly formatted | content | Contains markdown headings/lists |
| MT-044 | Content avoids keyword stuffing | 'meta title' count | Less than 20 occurrences |

### Integration Tests (MT-045 to MT-047)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MT-045 | Guide retrievable by tool slug | getGuidesByToolSlug | Found in meta-tag-analyzer results |
| MT-046 | Related guides are retrievable | getGuideBySlug | meta-description and h1-headings exist |
| MT-047 | Guide in on-page category | getGuidesByCategory | Found in on-page guides |

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Guide Existence | 100% | 3 tests |
| Metadata | 100% | 6 tests |
| Required Sections | 100% | 8 tests |
| Code Examples | 100% | 6 tests |
| Checklist | 100% | 9 tests |
| Related Guides | 100% | 6 tests |
| Content Quality | 100% | 6 tests |
| Integration | 100% | 3 tests |
| **Total** | **100%** | **47 tests** |

## Test Execution

```bash
# Run meta-title guide tests
npx jest __tests__/lib/guides/meta-title.test.ts --verbose

# Run with coverage
npx jest __tests__/lib/guides/meta-title.test.ts --coverage
```

## Dependencies

- `@/lib/guides/registry` - Guide data and helper functions
- `@/lib/guides/types` - TypeScript interfaces and type guards

## Notes for Developer

### Required Content Sections

The meta-title guide MUST include content covering:

1. **What is Meta Title?**
   - Definition of meta title tag
   - How it appears in SERPs
   - Difference from H1 heading

2. **Why Meta Titles Matter for SEO**
   - Click-through rate impact
   - Ranking considerations
   - User experience signals

3. **How to Optimize Meta Titles**
   - Title length guidelines (50-60 characters / ~580 pixels)
   - Keyword placement (front-load primary keywords)
   - Branding considerations
   - Creating unique titles per page

4. **Common Mistakes to Avoid**
   - Duplicate titles across pages
   - Too long or too short titles
   - Keyword stuffing
   - Missing branding

5. **Best Practices Checklist**
   - 5-10 actionable items with priority levels

### Code Example Requirements

At least 1-2 code examples demonstrating:

```html
<!-- Good meta title example -->
<title>Complete Guide to Meta Titles for SEO | BrandName</title>

<!-- Bad meta title example (too long, stuffed) -->
<title>SEO Meta Title Optimization Guide Best Practices Tips Tricks 2024</title>
```

### Tool Integration

- The guide links to `meta-tag-analyzer` tool (FEAT-016 - Done)
- Users can analyze their meta titles using this tool

### Related Guides

Must include:
- `meta-description` - Complementary meta tag
- `h1-headings` - Related on-page element

---

## Comments

### [2026-03-02 10:58 GMT+2] PreTester - Test Coverage In Progress
Starting test design for TASK-20-5. Reading task file and context.

### [2026-03-02 11:05 GMT+2] PreTester - Test File Created
**Test Documentation Created:**
- `TESTS/TASK-20-5.md` - This file

**Test Files Created:**
- `frontend/__tests__/lib/guides/meta-title.test.ts` - 47 tests for meta-title guide content

**Test Coverage Summary:**
| Category | Tests | Description |
|----------|-------|-------------|
| Guide Existence | 3 | Slug exists, retrievable, valid structure |
| Metadata | 6 | Title, summary, category, toolSlug, icon, readTime |
| Required Sections | 8 | Content sections for all required topics |
| Code Examples | 6 | HTML examples present and valid |
| Checklist | 9 | 5-10 items with proper priorities |
| Related Guides | 6 | meta-description, h1-headings linked |
| Content Quality | 6 | Length, formatting, SEO keywords |
| Integration | 3 | Registry functions work correctly |

**Notes for Developer:**
1. Tests expect `getGuideBySlug('meta-title')` to return a valid SEOGuide
2. Sections must cover: definition, SEO impact, optimization, mistakes
3. Checklist must have 5-10 items with at least one 'critical' priority
4. Code examples should demonstrate HTML title tags
5. readTime should be 3-5 minutes (inclusive)
6. toolSlug must equal 'meta-tag-analyzer'
7. relatedGuides must include 'meta-description' and 'h1-headings'

Status: `Tests Coverage In Progress`
