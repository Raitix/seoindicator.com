# TASK-16-2: Develop Keyword Density Checker (Frontend)

## STATUS
`To Write Tests`

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