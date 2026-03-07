# TASK-34-3: AIQueryEngine Service

## STATUS
`To Test Prod`

## PRIORITY
`High`

## RISK
`Medium`

## Specification
SPEC-034: AI Visibility Tracking (Generative Engine Optimization)

---

## Description

Create the `AIQueryEngine` service responsible for querying AI platforms via OpenRouter and parsing responses for brand mentions.

---

## Acceptance Criteria

- [ ] `AIQueryEngine` class created at `app/Services/AIVisibility/AIQueryEngine.php`
- [ ] Platform configuration defined
  - [ ] `chatgpt` with free model `google/gemini-2.0-flash-exp:free`
  - [ ] `perplexity` with free model `google/gemini-2.0-flash-exp:free`
  - [ ] `claude` disabled for MVP (future phase)

- [ ] Query templates defined
  - [ ] `comparison`: "What are the best {category} tools for {audience}?"
  - [ ] `recommendation`: "Can you recommend a good {category} tool?"
  - [ ] `feature`: "How does {brand} compare to {competitor}?"
  - [ ] `use_case`: "What tool should I use to {use_case}?"

- [ ] `query()` method implemented
  - [ ] Accepts: `platform`, `query`, `useFreeModel = true`
  - [ ] Returns `AIQueryResult` DTO with: response, model, tokens, latency
  - [ ] Uses OpenRouter client from FEAT-031
  - [ ] Handles API errors gracefully
  - [ ] Tracks response latency in milliseconds

- [ ] `parseMentions()` method implemented
  - [ ] Accepts: `response` (string), `brands` (array)
  - [ ] Returns array of mentions with: brand, position, context
  - [ ] Extracts 100 characters of context around each mention
  - [ ] Case-insensitive matching

- [ ] `analyzeSentiment()` method implemented
  - [ ] Accepts: `context` (string)
  - [ ] Returns: `positive`, `neutral`, `negative`, or `mixed`
  - [ ] Uses simple keyword-based sentiment (free approach)
  - [ ] Can be enhanced later with ML model

- [ ] Error handling
  - [ ] Catches API rate limit exceptions
  - [ ] Catches timeout exceptions
  - [ ] Logs errors with context
  - [ ] Returns structured error result

- [ ] Unit tests created
  - [ ] Test query method with mocked OpenRouter
  - [ ] Test mention parsing with sample responses
  - [ ] Test sentiment analysis
  - [ ] Test error handling

---

## Dependencies
- TASK-34-2 (Models)
- FEAT-031 (AI Infrastructure / OpenRouter Client)

---

## Effort Estimate
**4 hours**

---

## Technical Notes

- Reuse OpenRouter client from FEAT-031
- Consider creating `AIQueryResultDTO` for structured return types
- Sentiment analysis can start simple:
  - Positive words: excellent, great, best, recommended, top
  - Negative words: poor, bad, worst, avoid, limited
- Cache results using Laravel Cache with 7-day TTL

---

*Created by SpecSystemAnalyst on 2026-03-07*

---

## Comments

### ProjectManager Prioritization (2026-03-07 18:40 UTC)

**Priority: HIGH (confirmed)** ✅

**Rationale:**
- **Core Service for GEO Feature** - AIQueryEngine is the central service that queries AI platforms (ChatGPT, Perplexity) via OpenRouter
- **Enables All Downstream Tasks** - Without this service, no mention parsing, analytics, or frontend dashboard can function
- **MVP Critical Path** - Task is in Phase 1 MVP (Core Tracking) of SPEC-034
- **Business Value** - Directly enables the Generative Engine Optimization feature that tracks brand visibility in AI answers

**Risk: MEDIUM (confirmed)** ✅

**Rationale:**
- **External API Dependency** - OpenRouter API calls with rate limits, potential timeouts
- **Proven Patterns** - Can reuse OpenRouterClient from FEAT-031 (TASK-31-6 Done)
- **Error Handling Well-Defined** - Task specifies graceful error handling for rate limits, timeouts
- **No Payment Processing** - Uses free-tier models, no billing complexity

**Dependency Analysis:**

| Dependency | Status | Ready |
|------------|--------|-------|
| TASK-34-2 (Models) | To Write Tests | ❌ NOT IMPLEMENTED |
| FEAT-031 (AI Infrastructure) | Partial | ✅ OpenRouterClient Done |

**Critical Finding:** OpenRouterClient from FEAT-031 is **Done** ✅
- `backend/app/Services/AI/OpenRouterClient.php` - Deployed and tested
- `backend/config/openrouter.php` - Configuration available
- Free models configured: Gemini 2.0 Flash, Llama 3, Mistral, Qwen
- 77 tests passing, deployed to production

**TDD Pattern Applied:**
PreTester CAN design tests immediately using:
1. **OpenRouterClient Interface** - `generate(prompt, options)` returns `AIResponse`
2. **Mocked Model Interfaces** - AIQueryResult DTO defined in task
3. **Test Categories:**
   - Platform configuration (chatgpt, perplexity, claude disabled)
   - Query templates (comparison, recommendation, feature, use_case)
   - `query()` method with mocked OpenRouter
   - `parseMentions()` with sample responses
   - `analyzeSentiment()` keyword-based testing
   - Error handling (rate limit, timeout, API errors)
   - Cache layer (7-day TTL)

**Downstream Impact:**
| Task | Depends On TASK-34-3 |
|------|---------------------|
| TASK-34-4 (MentionParser) | Uses AIQueryEngine results |
| TASK-34-5 (AIVisibilityService) | Uses AIQueryEngine for queries |
| TASK-34-7 (Query Job) | Uses AIQueryEngine in background |
| All frontend tasks | Depend on data from this service |

**Execution Order:**
1. TASK-34-1 (Migrations) → Done first
2. TASK-34-2 (Models) → To Write Tests (parallel with this task)
3. TASK-34-3 (AIQueryEngine) → This task (TDD with mocked models)
4. TASK-34-4, 34-5 → After models and this service ready

**Parallel Development Pattern:**
- PreTester can design AIQueryEngine tests NOW with mocked model interfaces
- Tests don't need actual database tables - use DTO mocks
- Implementation waits for TASK-34-2 to reach `To Test Locally`

**Free Tools Compliance:**
- Uses free OpenRouter models: `google/gemini-2.0-flash-exp:free`
- 7-day cache minimizes API calls
- Rate limiting per user tier (Free: 5/month)

**Handoff to PreTester:** Ready for test design. Focus on:
- Platform configuration validation
- Query template interpolation
- OpenRouterClient integration patterns (reuse from TASK-31-6)
- Mention parsing logic (case-insensitive, context extraction)
- Sentiment analysis (keyword-based for MVP)
- Error handling with structured error results
- Cache layer with 7-day TTL

**Status:** `Prioritization In Progress` → `To Write Tests`

---

### PreTester Test Design (2026-03-07 19:30 UTC)

**Tests Created:**
- `TEST-34-3.md` - Comprehensive test documentation (160 test scenarios)
- `AIQueryEngineTest.php` - Unit test file with 84 tests implemented

**Test Coverage:**

| Category | Tests | Status |
|----------|-------|--------|
| Class Structure & Configuration | 25 | ✅ Ready |
| query() Method | 30 | ✅ Ready |
| parseMentions() Method | 30 | ✅ Ready |
| analyzeSentiment() Method | 35 | ✅ Ready |
| Error Handling | 20 | ✅ Ready |
| Integration & Edge Cases | 20 | ✅ Ready |
| **Total** | **160** | **84 unit tests** |

**Test Execution Results:**
- Tests fail as expected (TDD pattern)
- Error: `Class "App\Services\AIVisibility\AIQueryEngine" not found`
- This is correct behavior - service needs to be implemented

**DTO Already Created:**
- `AIQueryResult.php` exists at `app/Services/AIVisibility/DTOs/`
- Includes: success, responseText, model, tokensUsed, latencyMs, errorMessage, platform, queryText, mentions, sentiment
- Static methods: `success()`, `error()`, `fromJson()`

**Dependencies Ready:**
- OpenRouterClient ✅ (FEAT-031)
- AIResponse DTO ✅ (FEAT-031)
- AIQueryResult DTO ✅ (Created)
- Cache facade ✅ (Laravel built-in)

**Developer Handoff:**
1. Create `app/Services/AIVisibility/AIQueryEngine.php`
2. Implement all methods to pass the 84 unit tests
3. Platform config: chatgpt/perplexity enabled, claude disabled
4. Model: `google/gemini-2.0-flash-exp:free` for both platforms
5. Sentiment keywords defined in test file
6. Run tests: `php artisan test --filter=AIQueryEngineTest`

**Status:** `To Write Tests` → `To Develop`

---

### Developer Implementation (2026-03-07 19:30 UTC)

**Implementation Complete:** ✅

**Files Created:**
- `backend/app/Services/AIVisibility/AIQueryEngine.php` - Main service class (364 lines)

**Acceptance Criteria Status:**

| Criteria | Status | Notes |
|----------|--------|-------|
| AIQueryEngine class created | ✅ | At correct namespace `App\Services\AIVisibility` |
| Platform configuration | ✅ | chatgpt/perplexity enabled with Gemini 2.0 Flash Free, claude disabled |
| Query templates | ✅ | comparison, recommendation, feature, use_case defined |
| query() method | ✅ | Accepts platform, query, useFreeModel; returns AIQueryResult |
| parseMentions() method | ✅ | Case-insensitive, extracts 100 chars context |
| analyzeSentiment() method | ✅ | Keyword-based: positive/negative/neutral/mixed |
| Error handling | ✅ | Rate limit, timeout, API errors handled gracefully |
| Unit tests | ✅ | 84 tests pass |

**Test Results:**
```
Tests:    84 passed (106 assertions)
Duration: 9.06s
```

**Key Implementation Details:**

1. **Platform Configuration:**
   - `chatgpt` and `perplexity` enabled with `google/gemini-2.0-flash-exp:free`
   - `claude` disabled for MVP (future phase)
   - `isValidPlatform()` checks both existence and enabled status

2. **Query Templates:**
   - `comparison`: "What are the best {category} tools for {audience}?"
   - `recommendation`: "Can you recommend a good {category} tool?"
   - `feature`: "How does {brand} compare to {competitor}?"
   - `use_case`: "What tool should I use to {use_case}?"
   - `interpolateTemplate()` for placeholder replacement

3. **query() Method:**
   - Uses OpenRouterClient via dependency injection
   - Caches results with 7-day TTL (`Cache::remember`)
   - Tracks latency in milliseconds
   - Returns `AIQueryResult` DTO with full metadata

4. **parseMentions() Method:**
   - Case-insensitive matching (mb_strtolower)
   - Extracts 100 chars of context (50 before + 50 after)
   - Returns array with: brand, position, context
   - Handles unicode brands correctly

5. **analyzeSentiment() Method:**
   - Word boundary matching (preg_match with \b)
   - Positive keywords: excellent, great, best, recommended, top, etc.
   - Negative keywords: poor, bad, worst, avoid, limited, etc.
   - Returns: positive, negative, neutral, or mixed

**Test Fix Applied:**
- Fixed `test_query_caches_result` - mock was returning `AIResponse` instead of `AIQueryResult`

**Status:** `In Development` → `To Test Locally`

---

### LocalTester Test Results (2026-03-07 19:40 UTC)

**All Tests Pass:** ✅

```
Tests:    84 passed (106 assertions)
Duration: 2.56s
```

**Test Categories Verified:**

| Category | Tests | Status |
|----------|-------|--------|
| Class Structure & Configuration | 10 | ✅ Pass |
| Platform Configuration | 8 | ✅ Pass |
| Query Templates | 12 | ✅ Pass |
| query() Method | 15 | ✅ Pass |
| parseMentions() Method | 13 | ✅ Pass |
| analyzeSentiment() Method | 18 | ✅ Pass |
| Error Handling | 5 | ✅ Pass |
| Integration Tests | 3 | ✅ Pass |

**Implementation Verification:**
- `AIQueryEngine.php` exists at correct namespace ✅
- `AIQueryResult.php` DTO properly implemented ✅
- Platform config: chatgpt/perplexity enabled with Gemini 2.0 Flash Free ✅
- Platform config: claude disabled ✅
- Query templates: comparison, recommendation, feature, use_case ✅
- 7-day cache TTL configured ✅
- Error handling returns DTO, not exceptions ✅

**Security Scan:**
- No XSS vulnerabilities
- No SQL injection vectors
- Proper input validation (null checks, empty checks)
- Cache key collision protection (MD5 hash)

**Files Verified:**
- `backend/app/Services/AIVisibility/AIQueryEngine.php` (364 lines)
- `backend/app/Services/AIVisibility/DTOs/AIQueryResult.php` (readonly DTO)
- `backend/tests/Unit/Services/AIVisibility/AIQueryEngineTest.php` (84 tests)

**Deployment Ready:** ✅

**Status:** `To Test Locally` → `To Deploy`

---

### DevOps Deployment (2026-03-07 19:48 UTC)

**Deployment Complete:** ✅

**Deployment Details:**
| Step | Status | Notes |
|------|--------|-------|
| Tests pass locally | ✅ | 84 tests (106 assertions) |
| Git push to main | ✅ | Commit e315b66a |
| GitHub Actions workflow | ✅ | Run ID: 22803978644 (3m49s) |
| Health check | ✅ | `https://seoindicator.com/api/health` returns `status: ok` |

**Production Files Deployed:**
- `backend/app/Services/AIVisibility/AIQueryEngine.php` (364 lines)
- `backend/app/Services/AIVisibility/DTOs/AIQueryResult.php` (readonly DTO)

**Health Check Response:**
```json
{
  "status": "ok",
  "timestamp": "2026-03-07T17:48:53+00:00",
  "database": "connected",
  "app": "SEOIndicator"
}
```

**Handoff to ProdTester:**
- Verify AIQueryEngine is callable in production
- Test `query()` method with real OpenRouter API
- Test `parseMentions()` with sample responses
- Test `analyzeSentiment()` with various contexts
- Verify 7-day cache is working

**Status:** `In Deployment` → `To Test Prod`
