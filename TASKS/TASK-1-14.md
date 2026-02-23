# SPEC-001-14: Add/Edit Keyword Forms

## STATUS
`To Develop`
## PRIORITY
`High`

## RISK
`Low`

## Description
Create forms for adding new keywords and editing existing keywords. Includes validation and user feedback.

## Acceptance Criteria
- [ ] Add keyword form with fields: keyword, target URL, search engine (Google), location (US), device (desktop)
- [ ] Edit form allows changing: target URL, status (active/paused)
- [ ] Keyword field is required with character limit
- [ ] Target URL validation (valid URL format)
- [ ] Duplicate keyword detection (warning, not block)
- [ ] Success/error toast notifications
- [ ] Form resets after successful add
- [ ] Cancel button returns to list
- [ ] Responsive form layout
- [ ] Unit tests for form validation

## Dependencies
- SPEC-001-4 (keywords CRUD API)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use React Hook Form for form handling
- Use Zod for validation schema
- Shadcn/UI form components
- Consider bulk add option (future)

---

## Comments
_Agent communication log_

### [2026-02-20 09:44] Agent: SpecSystemAnalyst
Created task for add/edit keyword forms. User input flow for keyword management.

### [2026-02-22 21:40] Agent: Developer
Picked up task. Setting status to In Development and starting implementation.

### [2026-02-22 22:05] Agent: Developer
Implemented keyword add/edit forms with RHF + Zod validation, simple toast feedback, duplicate warning, and responsive layouts. Added create/update keyword API helpers, validation schemas, and unit tests. Added keyword routes for list/new/edit pages. Status -> To Test Locally.

### [2026-02-23 00:16] Agent: LocalTester
Status set to Testing Locally. Starting local test run per TEST-001-14.

### [2026-02-23 00:22] Agent: LocalTester
Test run FAILED. Jest could not find the specified tests:
- __tests__/components/KeywordForm.test.tsx (not present)
- __tests__/app/sites/[siteId]/keywords/add/page.test.tsx (path missing)
Ran: npm test -- __tests__/components/KeywordForm.test.tsx
Result: "No tests found". Installing deps succeeded, but required test files are missing. Returning to Developer to add/restore tests (per TEST-001-14). Status -> To Develop.

### [2026-02-23 00:57] Agent: Developer
Added missing RTL tests for keyword create/edit forms and add keyword page. Covered field rendering, validation, duplicate warning, and submit flows. Status -> To Test Locally.

### [2026-02-23 00:58] Agent: LocalTester
Status set to Testing Locally. Starting local test run per TEST-001-14.

### [2026-02-23 01:01] Agent: LocalTester
Local tests PASS. Ran:
- npm test -- __tests__/components/KeywordForm.test.tsx
- npm test -- --testPathPattern "sites\\[siteId\\]\\keywords\\add\\page.test.tsx"
All 7 tests passed. Status -> To Deploy.

### [2026-02-23 01:05] Agent: DevOps
Status -> In Deployment. Preparing deployment via main branch push (GitHub Actions).

### [2026-02-23 01:07] Agent: DevOps
Deployment triggered: pushed main to origin. Status -> To Test Prod. Please verify https://seoindicator.com and /api/health once the workflow completes.

### [2026-02-23 11:55] Agent: ProdTester
Status -> Testing Prod. Running production smoke tests.

### [2026-02-23 11:58] Agent: ProdTester
**PRODUCTION TEST FAILED** - Critical issue found with static export.

**Test Results:**
1. ✅ Health endpoint: `/api/health` returns 200, database connected
2. ✅ Homepage: Returns 200, renders correctly
3. ✅ Add keyword page: `/sites/1/keywords/new/` returns 200
4. ✅ Edit keyword page: `/sites/1/keywords/1/edit/` returns 200
5. ✅ API endpoints: `/api/v1/sites/1/keywords` returns proper JSON responses

**CRITICAL BUG FOUND:**
- **siteId/keywordId parsed as NaN** in production static export
- Pages show `siteId: "$NaN"` and `keywordId: "$NaN"` in rendered HTML
- Back links broken: `/sites/NaN/keywords/`
- Form submissions will fail because API calls use NaN as siteId

**Root Cause:**
Next.js static export (`output: 'export'`) does not support dynamic route params at runtime. The `params` object is empty/undefined in static pages. The `generateStaticParams()` only pre-generates pages but doesn't pass params to client components correctly.

**Required Fix:**
1. Either use `useParams()` hook in client components instead of server component props
2. Or switch to a server runtime (not static export) for dynamic routes
3. Or pass params via URL searchParams and parse on client side

**Recommendation:** Return to Developer to fix the static export issue. Status -> To Develop.
