# TASK-20-4: Set Up Routing with Static Params Generation

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Low`

## Description
Configure Next.js routing for SEO guides with static site generation, trailing slash handling, and proper static params generation for all 17 guide slugs.

## Acceptance Criteria
- [x] Update `next.config.js` with trailingSlash: true
- [x] Implement `generateStaticParams()` in guide page
- [x] All 17 guide slugs generate static HTML files
- [x] Build produces correct output structure:
  - `/seo-guides/index.html`
  - `/seo-guides/[slug]/index.html` for each guide
- [x] Client-side navigation works between guides
- [x] Direct URL access works for all guides
- [x] 404 handling for invalid slugs

## Dependencies
- TASK-20-1 (Guide data structure)
- TASK-20-3 (Guide page template)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Next.js App Router with output: 'export'
- Use generateStaticParams for build-time generation
- Test with `npm run build` and `npm run start`

## Comments
### [2026-03-01 12:28 GMT+2] ProjectManager - Prioritization Complete
**Priority Confirmed: High**

**Rationale:**
- Foundation task for SPEC-020 (SEO Guides Library)
- Phase 1 task - required for all static guide pages to work
- Enables clean URLs and proper SSG for SEO optimization
- No external API dependencies - pure Next.js configuration

**Dependency Analysis:**
- TASK-20-1 (Types & Registry) - Status: `Done` ✅
- TASK-20-3 (Template) - Status: `To Write Tests` - can develop in parallel

**Execution Order:**
```
Phase 1 (parallel development):
├── TASK-20-1 (Types) ← Done
├── TASK-20-2 (Index Page) ← In Progress
├── TASK-20-3 (Template) ← To Write Tests
└── TASK-20-4 (Routing) ← This task

Phase 2 (depends on Phase 1):
├── TASK-20-5 to 20-21 (Content pages)
└── All require routing from TASK-20-4
```

**Technical Scope:**
1. Update `next.config.js` with `trailingSlash: true`
2. Implement `generateStaticParams()` in guide page component
3. Ensure build produces correct output structure:
   - `/seo-guides/index.html`
   - `/seo-guides/[slug]/index.html` for each guide
4. Configure 404 handling for invalid slugs

**Risk Assessment:** Low risk confirmed
- Configuration changes only - no code logic
- Easily reversible
- Standard Next.js SSG pattern
- No database or API changes

**Notes for PreTester:**
- Test with `npm run build` to verify static generation
- Verify all 17 guide slugs generate HTML files
- Test direct URL access for each guide
- Test 404 handling for invalid slugs
- Test client-side navigation between guides

Status: `To Write Tests`

### [2026-03-01 12:50 GMT+2] PreTester - Test Coverage Complete
**Test Documentation Created:**
- `TESTS/TASK-20-4.md` - Comprehensive test documentation with 85+ test scenarios

**Test Files Created:**
| File | Tests | Purpose |
|------|-------|---------|
| `__tests__/config/next.config.test.ts` | 20 | Next.js config verification |
| `__tests__/lib/guides/generateStaticParams.test.ts` | 45 | Static params generation |
| `__tests__/integration/guide-routing.test.tsx` | 32 | Routing integration tests |
| **Total** | **97** | |

**Test Coverage Summary:**

| Category | Tests | Description |
|----------|-------|-------------|
| Next.js Config (NC-001 to NC-015) | 20 | Static export config, trailing slash |
| Static Params (SP-001 to SP-020) | 45 | generateStaticParams function |
| Routing Integration (RI-001 to RI-021) | 25 | Navigation, 404, security |
| Security Tests | 5 | XSS, SQL injection, path traversal |
| Accessibility | 3 | ARIA, headings, landmarks |

**Key Test Scenarios:**
1. **Next.js Config Tests:**
   - `output: 'export'` for static site generation
   - `trailingSlash: true` for clean URLs
   - `images.unoptimized: true` for static export
   - No conflicting options (rewrites, redirects, headers)

2. **Static Params Tests:**
   - Returns all 17 guide slugs
   - All slugs are valid kebab-case
   - No duplicates
   - Matches GUIDE_SLUGS from registry

3. **Routing Integration Tests:**
   - Valid slug renders page
   - Invalid slug returns 404
   - Case-sensitive slug lookup
   - Special characters rejected (XSS, SQL injection)
   - Navigation between guides works

**Security Tests Included:**
- Script tag injection in slug → 404
- SQL injection attempt → 404
- Path traversal attempt → 404
- Null bytes in slug → 404
- Very long slugs → 404

**Notes for Developer:**
1. **Next.js Config:** Already configured in `next.config.ts`:
   - `output: 'export'` ✅
   - `trailingSlash: true` ✅
   - `images.unoptimized: true` ✅

2. **generateStaticParams:** Implement in `app/seo-guides/[slug]/page.tsx`:
   ```typescript
   export function generateStaticParams() {
     return GUIDE_SLUGS.map((slug) => ({ slug }));
   }
   ```

3. **Build Verification:**
   ```bash
   npm run build
   # Verify: out/seo-guides/[slug]/index.html for each guide
   ```

4. **404 Handling:** Use `notFound()` from `next/navigation` for invalid slugs

Status: `To Develop`

### [2026-03-01 12:55 GMT+2] Developer - Implementation Complete
**Files Created:**
| File | Purpose |
|------|---------|
| `app/seo-guides/[slug]/page.tsx` | Dynamic guide page with SSG |
| `app/seo-guides/[slug]/not-found.tsx` | 404 page for invalid slugs |

**Implementation Details:**

1. **generateStaticParams()** - Returns all 17 guide slugs for SSG:
   ```typescript
   export function generateStaticParams(): { slug: string }[] {
     return GUIDE_SLUGS.map((slug) => ({ slug }));
   }
   ```

2. **generateMetadata()** - SEO metadata for each guide:
   - Title, description, canonical URL
   - Open Graph and Twitter cards
   - Uses guide data from registry

3. **GuidePage Component** - Full page with:
   - Breadcrumb navigation (Home > SEO Guides > Guide Title)
   - Hero section with category badge, tool badge, read time
   - Content placeholder (Phase 2 will populate)
   - Related guides section (max 3)
   - Prev/Next navigation
   - JSON-LD structured data (Article schema)

4. **404 Handling** - Uses Next.js `notFound()` for invalid slugs

**Build Verification:**
```
✓ npm run build succeeded
✓ /seo-guides/[slug] marked as SSG (●)
✓ All 17 guides generated: meta-title, meta-description, h1-headings, 
  heading-hierarchy, image-alt-text, image-optimization, internal-links,
  external-links, page-speed, mobile-friendly, https-security,
  structured-data, canonical-urls, robots-txt, xml-sitemap, open-graph,
  twitter-cards
✓ Output structure verified:
  - out/seo-guides/index.html
  - out/seo-guides/[slug]/index.html for all 17 guides
```

**Test Results:**
- All TASK-20-4 specific tests passed (97 tests)
- Config tests: 20 passed
- Static params tests: 45 passed
- Routing integration tests: 32 passed

Status: `To Test Locally`

### [2026-03-01 13:08 GMT+2] LocalTester - Local QA Complete
**Test Execution Summary:**
```
Test Suites: 3 passed, 3 total
Tests:       97 passed, 97 total
Time:        1.865s
```

**Test Files Verified:**
| File | Tests | Status |
|------|-------|--------|
| `__tests__/config/next.config.test.ts` | 20 | ✅ PASS |
| `__tests__/lib/guides/generateStaticParams.test.ts` | 45 | ✅ PASS |
| `__tests__/integration/guide-routing.test.tsx` | 32 | ✅ PASS |

**Build Verification:**
```
✓ npm run build succeeded (Next.js 16.1.6 Turbopack)
✓ Generating static pages (40/40) in 717.4ms
✓ /seo-guides/[slug] marked as SSG (●) with +14 more paths
```

**Output Structure Verified:**
```
out/seo-guides/
├── index.html                              ✅
├── meta-title/index.html                   ✅
├── meta-description/index.html             ✅
├── h1-headings/index.html                  ✅
├── heading-hierarchy/index.html            ✅
├── image-alt-text/index.html               ✅
├── image-optimization/index.html           ✅
├── internal-links/index.html               ✅
├── external-links/index.html               ✅
├── page-speed/index.html                   ✅
├── mobile-friendly/index.html              ✅
├── https-security/index.html               ✅
├── structured-data/index.html              ✅
├── canonical-urls/index.html               ✅
├── robots-txt/index.html                   ✅
├── xml-sitemap/index.html                  ✅
├── open-graph/index.html                   ✅
└── twitter-cards/index.html                ✅
```
**Total: 18 HTML files (1 index + 17 guides)**

**Acceptance Criteria Verified:**
- [x] `next.config.ts` has `trailingSlash: true` ✅
- [x] `generateStaticParams()` implemented ✅
- [x] All 17 guide slugs generate static HTML ✅
- [x] Build produces correct output structure ✅
- [x] 404 handling via `not-found.tsx` ✅

**Configuration Verified:**
```typescript
// next.config.ts
{
  output: 'export',        ✅
  trailingSlash: true,     ✅
  images: { unoptimized: true }  ✅
}
```

**Decision: PASS** → Ready for deployment

Status: `To Deploy`

### [2026-03-01 13:10 GMT+2] DevOps - Deployment Complete
**Deployment Status:** ✅ SUCCESS

**Deployment Method:** GitHub Actions (automated via push to origin/main)

**Commit Deployed:** `69ae048` - feat(seo-guides): implement routing with generateStaticParams (TASK-20-4)

**Deployment Notes:**
- Implementation was already pushed to origin/main
- GitHub Actions workflow triggered automatically
- Static export deployed to production via SCP to `public_html/`
- All 17 guide pages now live at:
  - `https://seoindicator.com/seo-guides/`
  - `https://seoindicator.com/seo-guides/[slug]/`

**Files Deployed:**
| Path | Type |
|------|------|
| `out/seo-guides/index.html` | Index page |
| `out/seo-guides/[slug]/index.html` | 17 guide pages |

**Post-Deployment Verification:**
- ✅ Code pushed to origin/main
- ✅ GitHub Actions workflow completed
- ✅ Static files deployed to production

**Handoff:** ProdTester to validate in production

Status: `To Test Prod`
