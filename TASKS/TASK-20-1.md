# TASK-20-1: Create Guide Data Structure and TypeScript Types

## STATUS
`To Document`

## PRIORITY
`High`

## RISK
`Low`

## Description
Create the TypeScript types and data structures for SEO guides, including the `SEOGuide` interface, `GuideSection`, `ChecklistItem`, and the guide registry with all 17 guide definitions.

## Acceptance Criteria
- [x] Create `lib/guides/types.ts` with TypeScript interfaces:
  - `SEOGuide` interface with all fields from SPEC-020
  - `GuideSection` interface for content sections
  - `ChecklistItem` interface with priority levels
  - `CodeExample` interface for code snippets
- [x] Create `lib/guides/registry.ts` with guide data array
- [x] Export `getGuideBySlug(slug: string)` helper function
- [x] Export `getAllGuides()` helper function
- [x] Export `getRelatedGuides(slug: string)` helper function
- [x] All 17 guide stubs defined with slug, title, summary, category
- [x] Type definitions compile without errors

## Dependencies
- None (foundation task)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use TypeScript strict mode
- Category enum: 'on-page' | 'technical' | 'content'
- Icon field can be emoji string or icon component name
- Content sections will be populated in Phase 2 tasks

## Comments
### [2026-03-01 11:45 GMT+2] ProjectManager - Prioritization
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 (SEO Guides Library)
- No dependencies - can start immediately
- All Phase 1 tasks (TASK-20-1 to 20-4) can be developed in parallel
- Required by all Phase 2 content tasks (TASK-20-5 to 20-21)

**Dependency Chain:**
```
TASK-20-1 (Types) ← TASK-20-5 to 20-21 (Content tasks)
                  ← TASK-20-22 to 20-25 (Integration tasks)
```

**Execution Order:**
- Phase 1 tasks (20-1, 20-2, 20-3, 20-4) can run in parallel
- Phase 2 content tasks depend on TASK-20-1
- Phase 3 integration tasks depend on Phase 2

**Risk Assessment:** Low risk confirmed
- TypeScript types only - no external APIs
- No database changes
- No security implications

Status: `To Write Tests`

### [2026-03-01 11:55 GMT+2] PreTester - Test Coverage Complete
**Test Documentation Created:**
- `TESTS/TASK-20-1.md` - Comprehensive test documentation

**Test Files Created:**
- `frontend/__tests__/lib/guides/types.test.ts` - 50+ tests for TypeScript interfaces
- `frontend/__tests__/lib/guides/registry.test.ts` - 50+ tests for registry helper functions

**Test Coverage Summary:**
| Category | Tests | Description |
|----------|-------|-------------|
| SEOGuide Interface | 18 | Required fields, optional fields, validation |
| GuideSection Interface | 5 | Required fields, codeExamples |
| CodeExample Interface | 5 | Required fields, optional description |
| ChecklistItem Interface | 6 | Required fields, priority validation |
| Type Guards | 8 | Runtime validation helpers |
| getGuideBySlug | 6 | Lookup by slug, null handling |
| getAllGuides | 4 | Return count, structure validation |
| getRelatedGuides | 6 | Related guides logic, max 3 |
| Category Filters | 5 | Filter by category |
| Tool Integration | 5 | Filter by toolSlug |
| Immutability | 2 | Registry returns copies |
| Edge Cases | 8 | Special inputs, null handling |

**Notes for Developer:**
1. Tests expect `lib/guides/types.ts` to export:
   - Interfaces: SEOGuide, GuideSection, ChecklistItem, CodeExample
   - Type guards: isSEOGuide(), isGuideSection(), isChecklistItem(), isCodeExample()
   - Validators: isValidCategory(), isValidPriority()
   - Constants: GUIDE_CATEGORIES, CHECKLIST_PRIORITIES

2. Tests expect `lib/guides/registry.ts` to export:
   - Functions: getGuideBySlug(), getAllGuides(), getRelatedGuides()
   - Functions: getGuidesByCategory(), getGuidesByToolSlug()
   - Functions: getGuidesWithTools(), getGuidesWithoutTools()
   - Constant: GUIDE_SLUGS (readonly array of all 17 slugs)

3. All 17 guide stubs must be defined with: slug, title, summary, category, icon, readTime

Status: `To Develop`

### [2026-03-01 11:58 GMT+2] Developer - Implementation Complete
**Files Created:**
- `frontend/lib/guides/types.ts` - TypeScript interfaces and type guards
- `frontend/lib/guides/registry.ts` - Guide registry with all 17 guide stubs

**Implementation Details:**

1. **TypeScript Interfaces (types.ts):**
   - `SEOGuide` - Main guide interface with all required fields
   - `GuideSection` - Content section with optional codeExamples
   - `ChecklistItem` - Actionable item with priority level
   - `CodeExample` - Code snippet with language and optional description
   - `GuideCategory` - Type for category ('on-page' | 'technical' | 'content')
   - `ChecklistPriority` - Type for priority ('critical' | 'important' | 'recommended')

2. **Type Guards (types.ts):**
   - `isSEOGuide()` - Runtime validation for SEOGuide objects
   - `isGuideSection()` - Runtime validation for GuideSection
   - `isChecklistItem()` - Runtime validation for ChecklistItem
   - `isCodeExample()` - Runtime validation for CodeExample
   - `isValidCategory()` - Validates category string
   - `isValidPriority()` - Validates priority string

3. **Constants (types.ts):**
   - `GUIDE_CATEGORIES` - Readonly array of valid categories
   - `CHECKLIST_PRIORITIES` - Readonly array of valid priorities

4. **Registry Functions (registry.ts):**
   - `getGuideBySlug(slug)` - Exact match lookup (case-sensitive, no trimming)
   - `getAllGuides()` - Returns copies of all 17 guides
   - `getRelatedGuides(slug)` - Returns up to 3 related guides
   - `getGuidesByCategory(category)` - Filter by category
   - `getGuidesByToolSlug(toolSlug)` - Filter by tool integration
   - `getGuidesWithTools()` - Returns 5 guides with tool integration
   - `getGuidesWithoutTools()` - Returns 12 guides without tools
   - `GUIDE_SLUGS` - Readonly array of all 17 slugs

5. **All 17 Guide Stubs Defined:**
   - On-Page (8): meta-title, meta-description, h1-headings, heading-hierarchy, image-alt-text, image-optimization, internal-links, external-links
   - Technical (7): page-speed, mobile-friendly, https-security, structured-data, canonical-urls, robots-txt, xml-sitemap
   - Content (2): open-graph, twitter-cards

6. **Tool Integration:**
   - meta-title, meta-description → meta-tag-analyzer
   - mobile-friendly → mobile-friendly-checker
   - open-graph, twitter-cards → social-media-preview

**Test Results:** 101 tests passed
- types.test.ts: All interface and type guard tests pass
- registry.test.ts: All registry function tests pass

**Minor Test Fix:**
- Updated slug regex to allow alphanumeric kebab-case (e.g., "h1-headings", "xml-sitemap")

Status: `To Test Locally`

### [2026-03-01 12:10 GMT+2] LocalTester - Local QA Complete
**Test Execution:**
```
npx jest __tests__/lib/guides/types.test.ts __tests__/lib/guides/registry.test.ts --verbose
```

**Test Results: ALL PASS**
| Suite | Tests | Status |
|-------|-------|--------|
| types.test.ts | 50 | ✅ PASS |
| registry.test.ts | 51 | ✅ PASS |
| **Total** | **101** | **✅ PASS** |

**Test Coverage by Category:**

**types.test.ts:**
- SEOGuide Interface: 15 tests (T-001 to T-023)
- GuideSection Interface: 4 tests
- CodeExample Interface: 4 tests
- ChecklistItem Interface: 8 tests (T-015 to T-017)
- isValidCategory: 5 tests
- isValidPriority: 5 tests
- Type Guards Integration: 6 tests (T-024 to T-025)
- Edge Cases: 7 tests

**registry.test.ts:**
- getGuideBySlug: 8 tests (R-001 to R-004)
- getAllGuides: 6 tests (R-005 to R-008)
- getRelatedGuides: 6 tests (R-009 to R-013)
- Registry Structure: 4 tests (R-014, R-022 to R-024)
- getGuidesByCategory: 5 tests (R-015 to R-017)
- getGuidesByToolSlug: 4 tests (R-018 to R-019)
- getGuidesWithTools: 2 tests (R-020)
- getGuidesWithoutTools: 2 tests (R-021)
- Registry Immutability: 2 tests (R-025)
- Edge Cases: 6 tests
- GUIDE_SLUGS constant: 2 tests

**Adversarial Testing:**
- ✅ Null/undefined inputs handled correctly
- ✅ Invalid slugs return null
- ✅ Empty strings handled gracefully
- ✅ Very long strings handled without crash
- ✅ Special characters in slug lookup (injection attempts)
- ✅ Immutability verified - registry returns copies

**Security Check:**
- ✅ No injection vulnerabilities in slug lookup
- ✅ No prototype pollution possible
- ✅ Registry immutability enforced

**Decision: PASS → Ready for Deployment**

Status: `To Deploy`

### [2026-03-01 12:15 GMT+2] DevOps - Deployment Complete
**Deployment Summary:**
- Git push to origin/main: SUCCESS
- GitHub Actions build-and-test: SUCCESS (1m5s)
- SSH Deploy: Manual fallback (GitHub Actions SSH timed out)
- Server code sync: VERIFIED (commits match)
- TypeScript files deployed: `frontend/lib/guides/types.ts`, `frontend/lib/guides/registry.ts`
- Backend migrations: No new migrations needed
- Config/Route cache: Refreshed
- Health check: PASS
  - Status: `ok`
  - Database: `connected`
  - App: `SEOIndicator`

**GitHub Actions Issue:**
- SSH step timed out (16s) - Hostinger blocking GitHub Actions IPs
- Manual SSH from local machine succeeded
- Consider adding GitHub Actions IP ranges to Hostinger allowlist

**Files Verified on Production:**
```
frontend/lib/guides/types.ts (5931 bytes)
frontend/lib/guides/registry.ts (9230 bytes)
```

Status: `To Test Prod`

### [2026-03-01 12:18 GMT+2] ProdTester - Production QA Complete
**Test Execution:**
```
npx jest __tests__/lib/guides/types.test.ts __tests__/lib/guides/registry.test.ts --verbose
```

**Test Results: ALL PASS**
| Suite | Tests | Status |
|-------|-------|--------|
| types.test.ts | 50 | ✅ PASS |
| registry.test.ts | 51 | ✅ PASS |
| **Total** | **101** | **✅ PASS** |

**TypeScript Compilation:**
```
npx tsc --noEmit lib/guides/types.ts lib/guides/registry.ts
```
- ✅ No errors (silent output = success)

**Production Health Check:**
```
GET https://seoindicator.com/api/health
```
- Status: `ok`
- Database: `connected`
- App: `SEOIndicator`

**Files Verified:**
- `frontend/lib/guides/types.ts` - EXISTS
- `frontend/lib/guides/registry.ts` - EXISTS

**Guide Registry Verification:**
| Category | Count | Guides |
|----------|-------|--------|
| On-Page | 8 | meta-title, meta-description, h1-headings, heading-hierarchy, image-alt-text, image-optimization, internal-links, external-links |
| Technical | 7 | page-speed, mobile-friendly, https-security, structured-data, canonical-urls, robots-txt, xml-sitemap |
| Content | 2 | open-graph, twitter-cards |
| **Total** | **17** | ✅ All expected guides present |

**Tool Integration Verified:**
- `meta-tag-analyzer` → meta-title, meta-description
- `mobile-friendly-checker` → mobile-friendly
- `social-media-preview` → open-graph, twitter-cards

**Security Check:**
- ✅ No injection vulnerabilities in slug lookup
- ✅ Registry immutability enforced (returns copies)
- ✅ No prototype pollution possible
- ✅ Input validation on all helper functions

**Decision: PASS → Ready for Documentation**

Status: `To Document`
