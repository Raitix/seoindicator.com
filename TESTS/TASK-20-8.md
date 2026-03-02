# Test Documentation: TASK-20-8 - Heading Hierarchy Guide Content

## Task Reference
- Task: TASK-20-8
- Title: Write Content for Heading Hierarchy Guide
- Status: To Write Tests
- Priority: Medium
- Risk: Low

## Overview
This document defines tests for the Heading Hierarchy (H1-H6) guide content. The guide teaches users how to create logical content structures using proper heading order for better UX and SEO.

## Test Files

| File | Purpose | Tests |
|------|---------|-------|
| `frontend/__tests__/lib/guides/heading-hierarchy-content.test.ts` | Content validation | 40+ tests |

## Test Scenarios

### TC-001: Guide Registry Entry Exists
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-001-01 | Get guide by slug | `getGuideBySlug('heading-hierarchy')` | Returns SEOGuide object |
| TC-001-02 | Slug matches | `guide.slug` | `'heading-hierarchy'` |
| TC-001-03 | Title is correct | `guide.title` | Contains 'Heading Hierarchy' |
| TC-001-04 | Category is on-page | `guide.category` | `'on-page'` |
| TC-001-05 | Read time in range | `guide.readTime` | 4-6 minutes |
| TC-001-06 | Icon is defined | `guide.icon` | Non-empty string |
| TC-001-07 | Summary is defined | `guide.summary` | Non-empty string, 10-200 chars |

### TC-002: Sections Structure
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-002-01 | Sections array exists | `guide.sections` | Array with length >= 5 |
| TC-002-02 | Each section has title | `section.title` | Non-empty string |
| TC-002-03 | Each section has content | `section.content` | Non-empty string, min 100 chars |
| TC-002-04 | Code examples valid | `section.codeExamples` | Each has language, code, optional description |

### TC-003: Required Content Topics
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-003-01 | "What is Heading Hierarchy" section | Search sections | At least one section covers heading hierarchy definition |
| TC-003-02 | SEO benefits covered | Search content | Content mentions SEO, rankings, search engines |
| TC-003-03 | Accessibility benefits covered | Search content | Content mentions accessibility, screen readers, a11y |
| TC-003-04 | How to structure headings | Search content | Content covers H1-H6 hierarchy, proper order |
| TC-003-05 | Common mistakes covered | Search content | Content mentions skipping levels, wrong order |
| TC-003-06 | Visual hierarchy mentioned | Search content | Content includes visual/diagram reference |

### TC-004: Section Title Validation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-004-01 | Definition section exists | Section titles | Contains "What is" or "Heading Hierarchy" |
| TC-004-02 | Benefits section exists | Section titles | Contains "SEO" or "Benefits" or "Why" |
| TC-004-03 | How-to section exists | Section titles | Contains "How to" or "Structure" or "Implement" |
| TC-004-04 | Mistakes section exists | Section titles | Contains "Mistakes" or "Common" or "Avoid" |
| TC-004-05 | Best practices section exists | Section titles | Contains "Best Practices" or "Tips" or "Guidelines" |

### TC-005: Content Quality
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-005-01 | Total content length | Sum of all content | >= 3000 characters (for 4-6 min read) |
| TC-005-02 | H1 mentioned in content | Search content | Content discusses H1 usage |
| TC-005-03 | H2-H6 mentioned in content | Search content | Content discusses subheading hierarchy |
| TC-005-04 | Code examples present | `sections[].codeExamples` | At least 2 code examples total |
| TC-005-05 | HTML examples included | Search code examples | At least 1 HTML code example |

### TC-006: Checklist Validation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-006-01 | Checklist array exists | `guide.checklist` | Array with length 5-10 |
| TC-006-02 | Each item has text | `item.text` | Non-empty string |
| TC-006-03 | Each item has priority | `item.priority` | Valid ChecklistPriority |
| TC-006-04 | At least one critical item | Filter by priority | >= 1 critical items |
| TC-006-05 | At least one important item | Filter by priority | >= 1 important items |
| TC-006-06 | Priorities are valid | All priorities | All are 'critical', 'important', or 'recommended' |

### TC-007: Related Guides
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-007-01 | Related guides array exists | `guide.relatedGuides` | Array with length >= 2 |
| TC-007-02 | h1-headings is related | `guide.relatedGuides` | Includes 'h1-headings' |
| TC-007-03 | internal-links is related | `guide.relatedGuides` | Includes 'internal-links' |
| TC-007-04 | Related guides are valid slugs | Each slug | Exists in GUIDE_SLUGS |

### TC-008: Content-Specific Validation
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| TC-008-01 | H1 limit mentioned | Search content | Content mentions one H1 per page |
| TC-008-02 | Heading order explained | Search content | Content explains H1 > H2 > H3 hierarchy |
| TC-008-03 | Skipping levels warning | Search content | Content warns against H1 > H3 jumps |
| TC-008-04 | Screen reader navigation | Search content | Content mentions screen reader heading navigation |
| TC-008-05 | Outline/table of contents | Search content | Content discusses heading outlines |

## Mock Data
Not required - tests use actual guide registry data.

## Coverage Targets
- Content Structure: 100% of required sections
- Content Topics: 100% of required topics
- Checklist: 100% of items validated
- Related Guides: 100% validated
- Code Examples: >= 2 examples

## Test Execution
```bash
npx jest __tests__/lib/guides/heading-hierarchy-content.test.ts --verbose
```

## Dependencies
- `lib/guides/types.ts` - Type definitions
- `lib/guides/registry.ts` - Guide data

## Notes for Developer
1. Content must be comprehensive enough for 4-6 minute read time
2. Include practical HTML examples showing correct and incorrect heading structures
3. Consider adding a visual diagram showing heading hierarchy tree
4. Ensure relatedGuides includes 'h1-headings' and 'internal-links'
5. Checklist should have at least 5 items with proper priority distribution
6. Content should emphasize both SEO and accessibility benefits

## Comments
### [2026-03-02 11:38 GMT+2] PreTester - Test Documentation Created
Test documentation created for Heading Hierarchy guide content validation. Tests verify:
- Registry entry with correct metadata
- All required sections present
- Content covers all specified topics
- Checklist has 5-10 items with valid priorities
- Related guides properly linked
- Code examples included
- Content quality for 4-6 min read time

Status: `To Develop`
