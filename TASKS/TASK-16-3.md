# TASK-16-3: Implement Backend API Proxy for SEO Tools

## STATUS
`To Develop`

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

### [2026-02-26 19:35] Agent: PreTester
**Test Documentation Created:** `TESTS/TEST-16-3.md`

**Test Coverage:**
- 80 unit test scenarios (Controller, Parser, Rate Limiter, Validator)
- 20 integration test scenarios (Endpoint behavior, External fetches)
- 35 security test scenarios (SSRF prevention, Rate limiting, Input validation)
- 10 performance test scenarios

**Critical Security Tests (MANDATORY):**
- SSRF Prevention (ST-016 to ST-035): Block all private IPs, localhost, cloud metadata endpoints
- Rate Limiting (ST-001 to ST-015): Accurate counting, CAPTCHA enforcement
- Input Validation (ST-036 to ST-045): Injection prevention, encoding attacks

**Key Implementation Notes for Developer:**
1. SSRF protection MUST validate resolved IP (not just URL string)
2. Rate limiting uses Redis with sliding window algorithm
3. CAPTCHA triggers after 3 requests/IP/day
4. HTML parsing via Cheerio (Node.js bridge) or Symfony DomCrawler
5. Response timeout: 30 seconds max

**Dependencies for Testing:**
- Redis for rate limiting storage
- CAPTCHA service (reCAPTCHA or hCaptcha)
- Mock HTTP server for external URL simulation

**Status changed:** `To Write Tests` → `Tests Coverage In Progress`