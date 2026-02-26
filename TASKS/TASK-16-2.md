# TASK-16-2: Develop Keyword Density Checker (Frontend)

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Description
Build the Keyword Density Checker tool in **Next.js**, allowing users to analyze keyword frequency, densities, and heading relevance.

## Responsibilities
1. Create a **KeywordDensityForm** component for user input (URL or custom HTML).
2. Display keyword density metrics (density percentage, optimization warnings).
3. Highlight heading relevance for queried keywords.

## Mock Example
```javascript
<KeywordDensityForm>
    <input type="url" placeholder="Enter page URL" />
    <textarea placeholder="Or paste HTML here"></textarea>
</KeywordDensityForm>
```

## Acceptance Criteria
- [ ] Tool analyzes both online pages (URL) and pasted HTML.
- [ ] Highlights overused keywords (density > N%).
- [ ] Displays warnings for headings missing keyword relevance.
- [ ] Input forms validated client-side.

## Dependencies
- [ ] TASK-16-3: Backend API integration (API Proxy).

## Effort Estimate
6 hours.

---

## Comments
### [2026-02-20 17:54] Agent: SpecSystemAnalyst
Task created for the Keyword Density Checker frontend implementation.

### [2026-02-26 18:28] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: `High`**
- MVP SEO tool feature (Phase 1 of SPEC-016)
- Core functionality for SEO analysis offering
- User-facing feature demonstrating product value

**Risk Confirmed: `Low`**
- Frontend-only implementation (Next.js components)
- No database changes required
- No authentication/authorization changes
- No external API dependencies (uses backend proxy)

**Dependency Chain:**
- TASK-16-3 (Backend API Proxy) must complete first for full integration testing
- Can develop frontend components in parallel with backend
- Integration testing requires completed API proxy

**Execution Order Recommendation:**
1. TASK-16-3 (Backend API) → Foundation for all frontend tools
2. TASK-16-1 (Meta Tag Analyzer) → Already prioritized, similar scope
3. TASK-16-2 (this task) → Keyword Density Checker frontend

**Effort Estimate:** 6 hours - reasonable for frontend component work
**Queue Position:** Ready for PreTester after TASK-16-3 completion

### [2026-02-26 19:20] Agent: PreTester
**Test Suite Created:**

Test documentation and test files have been created for the Keyword Density Checker frontend:

**Documentation:**
- `TESTS/TEST-16-2.md` - Complete test documentation with 60 unit tests, 10 integration tests, 15 E2E tests

**Unit Tests:**
- `frontend/__tests__/components/tools/KeywordDensityForm.test.tsx` - Form component (16 test scenarios)
- `frontend/__tests__/components/tools/KeywordDensityResults.test.tsx` - Results display (20 test scenarios)
- `frontend/__tests__/lib/utils/keywordDensityAnalyzer.test.ts` - Density calculation utilities (20 test scenarios)
- `frontend/__tests__/lib/utils/headingAnalyzer.test.ts` - Heading analysis utilities (20 test scenarios)

**E2E Tests:**
- `frontend/e2e/keyword-density-checker.spec.ts` - Full user workflows (15 test scenarios)

**Key Test Areas Covered:**
1. URL/HTML input validation
2. Keyword input validation
3. Density calculation accuracy (whole-word, case-insensitive)
4. Density status thresholds (good: 1-3%, low: <1%, over-optimized: >3%)
5. Heading extraction and analysis
6. Keyword presence in headings
7. Heading hierarchy validation
8. Warning display for over/under optimization
9. Accessibility (keyboard nav, ARIA labels)
10. Responsive design

**Implementation Notes for Developer:**
1. Use DOMParser for HTML stripping and heading extraction
2. Keyword matching must be case-insensitive and whole-word only
3. Density thresholds: <1% (low/yellow), 1-3% (good/green), >3% (over-optimized/red)
4. Headings should show keyword match indicators (✓ found / ⚠ not found)
5. Only URL or HTML can be active at a time (mutually exclusive)
6. Debounce URL analysis (300ms), immediate for HTML

**Coverage Target:** 85%

**Dependency:** Integration tests require TASK-16-3 (Backend API Proxy). Use MSW mocks for development.