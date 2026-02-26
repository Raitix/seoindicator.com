# TASK-16-3: Implement Backend API Proxy for SEO Tools

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Develop an **API Proxy** for backend data fetches to support SEO tool analysis while avoiding CORS issues.

## Responsibilities
1. Create an `APIProxyController` in Laravel.
2. Establish POST endpoint for HTML/page fetch requests.
3. Integrate *Cheerio* for server-side HTML parsing.
4. Implement CORS bypass for external resource fetching.
5. Add rate limiting and CAPTCHA for security (max 3 tries/IP).

## Mock Example Endpoint
```http
POST /api/tools/analyze
Body: { "url": "https://example.com" }
Response: { "html": "<parsed-results />" }
```

## Acceptance Criteria
- [ ] POST endpoint fetches and analyzes page HTML.
- [ ] Cheerio parses meta data, keywords, and Open Graph tags.
- [ ] CAPTCHA triggered after 3+ uses/IP.
- [ ] Unit tests cover endpoint and external fetch scenarios.

## Effort Estimate
8 hours.

---

## Comments
### [2026-02-20 17:54] Agent: SpecSystemAnalyst
Task created for API Proxy backend integration to support SEO tools.

### [2026-02-26 18:33] Agent: ProjectManager
**Prioritization Analysis:**

**Priority Confirmed: `High`**
- Foundation task - backend API that TASK-16-1 and TASK-16-2 depend on
- MVP Phase 1 component (SPEC-016)
- Blocking other frontend tool implementations

**Risk Confirmed: `Medium`**
- New API endpoint creation
- External resource fetching (CORS bypass implementation)
- Rate limiting and CAPTCHA security measures
- No database/auth changes (reduces risk from High)

**Dependency Chain:**
- This task MUST complete before TASK-16-1/TASK-16-2 integration testing
- Frontend tasks can develop in parallel but need this for full functionality

**Execution Order:**
1. TASK-16-3 (this task) → Backend API Proxy - PRIORITY
2. TASK-16-1 → Meta Tag Analyzer Frontend (already prioritized)
3. TASK-16-2 → Keyword Density Checker Frontend (already prioritized)

**Effort Estimate:** 8 hours - reasonable for Laravel controller, Cheerio integration, and security measures

**Queue Status:** Ready for PreTester