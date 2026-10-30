# TESTS/TASK-20-6.md — Meta Description Guide Content Tests

## Task Reference
- **Task:** TASK-20-6
- **Title:** Write Content for Meta Description Guide
- **Status:** To Write Tests

## Overview
Comprehensive test suite for validating the Meta Description SEO guide content meets all acceptance criteria.

## Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `frontend/__tests__/lib/guides/meta-description.test.ts` | 48 | Validate guide structure, content, and integration |

## Test Scenarios

### Guide Existence (MD-001 to MD-003)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-001 | meta-description slug exists in GUIDE_SLUGS | GUIDE_SLUGS array | Contains 'meta-description' |
| MD-002 | getGuideBySlug returns guide | 'meta-description' | Returns valid SEOGuide object |
| MD-003 | Guide is valid SEOGuide | guide object | Passes isSEOGuide() type guard |

### Metadata Tests (MD-004 to MD-009)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-004 | Title is descriptive | guide.title | Contains 'meta' and 'description', length > 5 |
| MD-005 | Summary is descriptive | guide.summary | Length 20-200 chars |
| MD-006 | Category is on-page | guide.category | Equals 'on-page' |
| MD-007 | Tool slug links to meta-tag-analyzer | guide.toolSlug | Equals 'meta-tag-analyzer' |
| MD-008 | Icon is defined | guide.icon | Non-empty string |
| MD-009 | Read time is 3-5 minutes | guide.readTime | Between 3 and 5 (inclusive) |

### Required Sections Tests (MD-010 to MD-017)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-010 | Sections array is not empty | guide.sections | Array with length > 0 |
| MD-011 | All sections are valid GuideSection | guide.sections | All pass isGuideSection() |
| MD-012 | "What is Meta Description?" section exists | section titles | Contains definition/what is section |
| MD-013 | SEO impact/CTR covered | section content | Contains ctr/click-through keywords |
| MD-014 | Optimization steps covered | section titles/content | Contains optimization/how-to/write keywords |
| MD-015 | Common mistakes covered | section titles/content | Contains mistake/avoid/duplicate/missing keywords |
| MD-016 | Each section has 100+ chars content | section.content | All ≥ 100 characters |
| MD-017 | Section titles are unique | section titles | No duplicates |

### Code Examples Tests (MD-018 to MD-023)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-018 | At least one section has code examples | codeExamples arrays | ≥ 1 section with code |
| MD-019 | Total code examples count is 1-5 | all codeExamples | Count between 1 and 5 |
| MD-020 | All code examples are valid | codeExample objects | All pass isCodeExample() |
| MD-021 | Code examples have language field | example.language | Non-empty string |
| MD-022 | Code examples have code field | example.code | Non-empty string |
| MD-023 | Code demonstrates HTML meta description tags | example.code | Contains meta/description/name/content |

### Checklist Tests (MD-024 to MD-032)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-024 | Checklist array is not empty | guide.checklist | Array with length > 0 |
| MD-025 | Checklist has 5-10 items | checklist.length | Between 5 and 10 (inclusive) |
| MD-026 | All items are valid ChecklistItem | checklist items | All pass isChecklistItem() |
| MD-027 | All items have 10+ char text | item.text | All ≥ 10 characters |
| MD-028 | All items have valid priority | item.priority | One of: critical, important, recommended |
| MD-029 | At least one critical priority item | item.priority | ≥ 1 item with 'critical' |
| MD-030 | Checklist covers character length | checklist text | Contains length/character/150/160 keywords |
| MD-031 | Checklist covers unique descriptions | checklist text | Contains unique/duplicate/each page keywords |
| MD-032 | Checklist covers compelling/CTR optimization | checklist text | Contains ctr/click/compelling/action keywords |

### Related Guides Tests (MD-033 to MD-038)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-033 | Related guides array is not empty | guide.relatedGuides | Array with length > 0 |
| MD-034 | meta-title is related | relatedGuides | Contains 'meta-title' |
| MD-035 | open-graph is related | relatedGuides | Contains 'open-graph' |
| MD-036 | All related guide slugs are valid | relatedGuides | All exist in GUIDE_SLUGS |
| MD-037 | No self-reference in related | relatedGuides | Does NOT contain 'meta-description' |
| MD-038 | Related guides count is 2-5 | relatedGuides.length | Between 2 and 5 |

### Content Quality Tests (MD-039 to MD-045)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-039 | Total content length is 2000+ chars | all section content | Combined length ≥ 2000 |
| MD-040 | Content mentions character limits (150-160) | content | Contains 150/160/character keywords |
| MD-041 | Content mentions Google/SEO practices | content | Contains google/serp/ranking keywords |
| MD-042 | Content mentions CTR | content | Contains click-through/ctr/compelling |
| MD-043 | Content mentions Google rewriting behavior | content | Contains rewrite/replace/override/ignores |
| MD-044 | Content is properly formatted | content | Contains markdown headings/lists |
| MD-045 | Content avoids keyword stuffing | 'meta description' count | Less than 25 occurrences |

### Integration Tests (MD-046 to MD-048)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MD-046 | Guide retrievable by tool slug | getGuidesByToolSlug | Found in meta-tag-analyzer results |
| MD-047 | Related guides are retrievable | getGuideBySlug | meta-title and open-graph exist |
| MD-048 | Guide in on-page category | getGuidesByCategory | Found in on-page guides |

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Guide Existence | 100% | 3 tests |
| Metadata | 100% | 6 tests |
| Required Sections | 100% | 8 tests |
| Code Examples | 100% | 6 tests |
| Checklist | 100% | 9 tests |
| Related Guides | 100% | 6 tests |
| Content Quality | 100% | 7 tests |
| Integration | 100% | 3 tests |
| **Total** | **100%** | **48 tests** |

## Test Execution

```bash
# Run meta-description guide tests
npx jest __tests__/lib/guides/meta-description.test.ts --verbose

# Run with coverage
npx jest __tests__/lib/guides/meta-description.test.ts --coverage
```

## Dependencies

- `@/lib/guides/registry` - Guide data and helper functions
- `@/lib/guides/types` - TypeScript interfaces and type guards

## Notes for Developer

### Required Content Sections

The meta-description guide MUST include content covering:

1. **What is Meta Description?**
   - Definition of meta description tag
   - How it appears in SERPs (below title)
   - HTML implementation (`<meta name="description" content="...">`)

2. **Why Meta Descriptions Matter for SEO**
   - Click-through rate (CTR) impact
   - Not a direct ranking factor but influences user behavior
   - Quality signals and user engagement

3. **How to Write Compelling Descriptions**
   - Character limits (150-160 characters recommended)
   - Keyword inclusion (natural placement)
   - Call-to-action phrases
   - Matching user intent

4. **Common Mistakes to Avoid**
   - Duplicate descriptions across pages
   - Auto-generated descriptions (low quality)
   - Missing descriptions entirely
   - Too short or too long
   - Keyword stuffing

5. **Google's Rewriting Behavior**
   - Google often rewrites descriptions (up to 70% of the time)
   - When Google ignores your description
   - How to write descriptions that Google is more likely to use

6. **Best Practices Checklist**
   - 5-10 actionable items with priority levels

### Code Example Requirements

At least 1-2 code examples demonstrating:

```html
<!-- Good meta description example -->
<meta name="description" content="Learn how to write compelling meta descriptions that boost click-through rates. Expert tips, character limits, and best practices for SEO.">

<!-- Bad meta description example (keyword stuffing) -->
<meta name="description" content="SEO meta description guide, meta description tips, meta description best practices, meta description optimization SEO guide">
```

### Tool Integration

- The guide links to `meta-tag-analyzer` tool (FEAT-016 - Done)
- Users can analyze their meta descriptions using this tool

### Related Guides

Must include:
- `meta-title` - Complementary meta tag
- `open-graph` - Social sharing description

### Character Limit Guidelines

- **Recommended length:** 150-160 characters
- **Pixel width:** ~920 pixels (varies by character width)
- **Too short:** Under 120 characters (missed opportunity)
- **Too long:** Over 160 characters (truncated in SERPs)
- **Note:** Google may truncate or rewrite regardless of length

---

## Comments

### [2026-03-02 11:03 GMT+2] PreTester - Test Coverage In Progress
Starting test design for TASK-20-6. Reading task file and context.

### [2026-03-02 11:10 GMT+2] PreTester - Test File Created
**Test Documentation Created:**
- `TESTS/TASK-20-6.md` - This file

**Test Files Created:**
- `frontend/__tests__/lib/guides/meta-description.test.ts` - 48 tests for meta-description guide content

**Test Coverage Summary:**
| Category | Tests | Description |
|----------|-------|-------------|
| Guide Existence | 3 | Slug exists, retrievable, valid structure |
| Metadata | 6 | Title, summary, category, toolSlug, icon, readTime |
| Required Sections | 8 | Content sections for all required topics |
| Code Examples | 6 | HTML examples present and valid |
| Checklist | 9 | 5-10 items with proper priorities |
| Related Guides | 6 | meta-title, open-graph linked |
| Content Quality | 7 | Length, formatting, SEO keywords, Google rewriting |
| Integration | 3 | Registry functions work correctly |

**Notes for Developer:**
1. Tests expect `getGuideBySlug('meta-description')` to return a valid SEOGuide
2. Sections must cover: definition, SEO impact/CTR, optimization, mistakes, Google rewriting
3. Checklist must have 5-10 items with at least one 'critical' priority
4. Code examples should demonstrate HTML meta description tags
5. readTime should be 3-5 minutes (inclusive)
6. toolSlug must equal 'meta-tag-analyzer'
7. relatedGuides must include 'meta-title' and 'open-graph'
8. Content must mention character limits (150-160) and Google's rewriting behavior

Status: `Tests Coverage In Progress`
