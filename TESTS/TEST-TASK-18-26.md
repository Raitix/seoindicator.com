# TEST-TASK-18-26: AnalysisController - API Endpoints

## Overview
Tests for SEO Analysis API controller endpoints: create analysis, get results, list analyses, and cached analysis by domain.

## Test Files
- `backend/tests/Feature/Api/V1/AnalysisControllerTest.php` - Feature tests (main)
- `backend/tests/Unit/Http/Requests/StoreAnalysisRequestTest.php` - Form request validation
- `backend/tests/Unit/Http/Resources/AnalysisResourceTest.php` - API resource tests (pre-existing)

---

## Test Categories

### AC-001: POST /api/v1/analyze - Create Analysis
| ID | Test | Description |
|----|------|-------------|
| AC-001.01 | create_analysis_authenticated | Returns 202 Accepted for authenticated user |
| AC-001.02 | create_analysis_anonymous | Returns 202 Accepted for anonymous user |
| AC-001.03 | create_analysis_valid_domain | Creates analysis with valid domain |
| AC-001.04 | create_analysis_invalid_domain | Returns 422 for invalid domain format |
| AC-001.05 | create_analysis_normalizes_domain | Normalizes domain (adds https://, removes path) |
| AC-001.06 | create_analysis_returns_polling_url | Returns polling URL in Location header |
| AC-001.07 | create_analysis_returns_analysis_id | Returns analysis ID in response |
| AC-001.08 | create_analysis_rate_limit_anonymous | 3/day limit for anonymous by IP |
| AC-001.09 | create_analysis_rate_limit_authenticated | 10/day limit for authenticated user |
| AC-001.10 | create_analysis_rate_limit_headers | X-RateLimit headers in response |
| AC-001.11 | create_analysis_429_response | Returns 429 with retry-after on limit exceeded |
| AC-001.12 | create_analysis_ssrf_blocked | Blocks SSRF attempts (localhost, private IPs) |
| AC-001.13 | create_analysis_dispatches_job | Dispatches analysis job to queue |
| AC-001.14 | create_analysis_links_to_user | Links analysis to authenticated user |
| AC-001.15 | create_analysis_links_to_session | Links analysis to session for anonymous |

### AC-002: GET /api/v1/analyses/{id} - Get Results
| ID | Test | Description |
|----|------|-------------|
| AC-002.01 | get_analysis_exists | Returns 200 with analysis data |
| AC-002.02 | get_analysis_not_found | Returns 404 for non-existent ID |
| AC-002.03 | get_analysis_ownership | Returns 403 for other user's analysis |
| AC-002.04 | get_analysis_pending | Returns pending status with progress |
| AC-002.05 | get_analysis_running | Returns running status with progress |
| AC-002.06 | get_analysis_completed | Returns completed with full results |
| AC-002.07 | get_analysis_failed | Returns failed with error message |
| AC-002.08 | get_analysis_anonymous_owner | Allows anonymous owner via session |
| AC-002.09 | get_analysis_response_structure | Validates response JSON structure |
| AC-002.10 | get_analysis_includes_score | Includes overall_score in response |
| AC-002.11 | get_analysis_includes_indicators | Includes indicator results array |
| AC-002.12 | get_analysis_includes_category_scores | Includes category_scores if available |
| AC-002.13 | get_analysis_includes_summary | Includes overall_summary if available |
| AC-002.14 | get_analysis_etag | Returns ETag header for caching |
| AC-002.15 | get_analysis_304_not_modified | Returns 304 if ETag matches |

### AC-003: GET /api/v1/analyses - List Analyses
| ID | Test | Description |
|----|------|-------------|
| AC-003.01 | list_analyses_authenticated | Returns user's analyses for authenticated |
| AC-003.02 | list_analyses_anonymous | Returns session's analyses for anonymous |
| AC-003.03 | list_analyses_pagination | Paginates results (default 15) |
| AC-003.04 | list_analyses_per_page_param | Respects per_page parameter (max 100) |
| AC-003.05 | list_analyses_page_param | Respects page parameter |
| AC-003.06 | list_analyses_filter_status | Filters by status query param |
| AC-003.07 | list_analyses_filter_domain | Filters by domain query param |
| AC-003.08 | list_analyses_sort_created_desc | Default sort by created_at DESC |
| AC-003.09 | list_analyses_sort_param | Respects sort parameter |
| AC-003.10 | list_analyses_empty | Returns empty array for no analyses |
| AC-003.11 | list_analyses_only_owned | Only returns owned analyses |
| AC-003.12 | list_analyses_response_structure | Validates response JSON structure |
| AC-003.13 | list_analyses_meta_pagination | Includes pagination meta data |
| AC-003.14 | list_analyses_rate_limit | 60/min rate limit for endpoint |
| AC-003.15 | list_analyses_cache_headers | Cache-Control headers set |

### AC-004: GET /api/v1/analyze?domain=... - Cached by Domain
| ID | Test | Description |
|----|------|-------------|
| AC-004.01 | cached_analysis_found | Returns cached analysis for domain |
| AC-004.02 | cached_analysis_not_found | Returns 404 for no cached analysis |
| AC-004.03 | cached_analysis_expired | Returns 404 for expired analysis |
| AC-004.04 | cached_analysis_valid | Returns 200 for non-expired analysis |
| AC-004.05 | cached_analysis_normalizes_domain | Normalizes domain before lookup |
| AC-004.06 | cached_analysis_user_specific | Authenticated: only user's cached |
| AC-004.07 | cached_analysis_session_specific | Anonymous: only session's cached |
| AC-004.08 | cached_analysis_public_fallback | Falls back to public cached if allowed |
| AC-004.09 | cached_analysis_etag | Returns ETag for caching |
| AC-004.10 | cached_analysis_304 | Returns 304 if ETag matches |
| AC-004.11 | cached_analysis_rate_limit | 60/min rate limit |
| AC-004.12 | cached_analysis_response_structure | Validates response structure |

### AUTH: Authentication Tests
| ID | Test | Description |
|----|------|-------------|
| AUTH-001 | unauthenticated_list | Returns 401 for list without auth |
| AUTH-002 | unauthenticated_show | Returns 401 for show without auth |
| AUTH-003 | unauthenticated_create | Allows anonymous create |
| AUTH-004 | unauthenticated_cached | Allows anonymous cached lookup |
| AUTH-005 | session_cookie_anonymous | Supports session cookie for anonymous |
| AUTH-006 | bearer_token_authenticated | Supports Bearer token for authenticated |

### SEC: Security Tests
| ID | Test | Description |
|----|------|-------------|
| SEC-001 | ssrf_localhost | Blocks localhost URLs |
| SEC-002 | ssrf_private_ip | Blocks private IP ranges |
| SEC-003 | ssrf_cloud_metadata | Blocks cloud metadata IPs (169.254.169.254) |
| SEC-004 | ssrf_ipv6_loopback | Blocks IPv6 loopback (::1) |
| SEC-005 | ssrf_ipv6_private | Blocks IPv6 private ranges |
| SEC-006 | ssrf_protocol_bypass | Blocks non-HTTP protocols |
| SEC-007 | ssrf_dns_rebinding | Handles DNS rebinding attempts |
| SEC-008 | sql_injection_domain | SQL injection protection on domain |
| SEC-009 | xss_domain | XSS protection on domain |
| SEC-010 | enumeration_prevention | Same 404 for not-found vs not-owned |
| SEC-011 | id_enumeration | No sequential ID enumeration |
| SEC-012 | rate_limit_enforcement | Rate limits enforced |

### HTTP: HTTP Method Tests
| ID | Test | Description |
|----|------|-------------|
| HTTP-001 | post_analyze_only | POST only on /analyze |
| HTTP-002 | get_analyses_only | GET only on /analyses |
| HTTP-003 | get_analyses_id_only | GET only on /analyses/{id} |
| HTTP-004 | get_cached_only | GET only on /analyze?domain= |
| HTTP-005 | options_cors | CORS preflight handling |
| HTTP-006 | 405_wrong_method | Returns 405 for wrong methods |

### EDGE: Edge Cases
| ID | Test | Description |
|----|------|-------------|
| EDGE-001 | unicode_domain | Handles unicode domains (IDN) |
| EDGE-002 | punycode_domain | Handles punycode domains |
| EDGE-003 | very_long_domain | Handles very long domain names |
| EDGE-004 | special_chars_domain | Handles special characters |
| EDGE-005 | empty_domain_param | Returns 422 for empty domain |
| EDGE-006 | null_domain_param | Returns 422 for null domain |
| EDGE-007 | whitespace_domain | Trims whitespace from domain |
| EDGE-008 | uppercase_domain | Lowercases domain for lookup |
| EDGE-009 | concurrent_analysis | Handles concurrent analysis requests |
| EDGE-010 | analysis_timeout | Handles analysis timeout gracefully |
| EDGE-011 | partial_results | Returns partial results on failure |
| EDGE-012 | large_analysis_data | Handles large analysis results |
| EDGE-013 | invalid_analysis_id | Handles non-numeric ID gracefully |
| EDGE-014 | negative_page_param | Handles negative page number |
| EDGE-015 | overflow_per_page | Limits per_page to max 100 |

### PERF: Performance Tests
| ID | Test | Description |
|----|------|-------------|
| PERF-001 | list_response_time | List response < 200ms |
| PERF-002 | show_response_time | Show response < 100ms |
| PERF-003 | create_response_time | Create response < 500ms |
| PERF-004 | cached_response_time | Cached lookup < 100ms |
| PERF-005 | pagination_performance | Pagination with 1000+ analyses |

---

## Response Structures

### Analysis Response (GET /analyses/{id}, POST /analyze)
```json
{
  "data": {
    "id": 123,
    "domain": "example.com",
    "normalized_url": "https://example.com",
    "overall_score": 85,
    "status": "completed",
    "error_message": null,
    "overall_summary": "Your website performs well...",
    "category_scores": {
      "content": 88,
      "technical": 82,
      "accessibility": 85,
      "performance": 78
    },
    "indicators": [
      {
        "name": "title",
        "score": 3,
        "status": "OK",
        "message": "Title tag is present",
        "value": "Example Domain"
      }
    ],
    "checked_at": "2026-03-19T10:00:00Z",
    "expires_at": "2026-03-26T10:00:00Z",
    "created_at": "2026-03-19T09:55:00Z"
  }
}
```

### Analysis List Response (GET /analyses)
```json
{
  "data": [
    {
      "id": 123,
      "domain": "example.com",
      "overall_score": 85,
      "status": "completed",
      "checked_at": "2026-03-19T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

### Create Analysis Response (POST /analyze - 202 Accepted)
```json
{
  "data": {
    "id": 123,
    "domain": "example.com",
    "status": "pending",
    "created_at": "2026-03-19T10:00:00Z"
  },
  "links": {
    "self": "/api/v1/analyses/123",
    "status": "/api/v1/analyses/123"
  }
}
```

### Error Response (4xx/5xx)
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The domain field is required.",
    "details": {
      "domain": ["The domain field is required."]
    }
  }
}
```

### Rate Limit Exceeded Response (429)
```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many analysis requests. Please try again later.",
    "retry_after": 3600
  }
}
```

---

## Rate Limiting Configuration

| Endpoint | Anonymous | Authenticated |
|----------|-----------|---------------|
| POST /analyze | 3/day/IP | 10/day/user |
| GET /analyses | 60/min/IP | 60/min/user |
| GET /analyses/{id} | 60/min/IP | 60/min/user |
| GET /analyze?domain= | 60/min/IP | 60/min/user |

---

## Headers

### Request Headers
- `Authorization: Bearer {token}` - For authenticated requests
- `X-Session-ID: {uuid}` - For anonymous session tracking
- `Accept: application/json`

### Response Headers
- `X-RateLimit-Limit` - Maximum requests allowed
- `X-RateLimit-Remaining` - Remaining requests in window
- `X-RateLimit-Reset` - Unix timestamp when limit resets
- `Retry-After` - Seconds until retry (on 429)
- `ETag` - Resource version hash
- `Location` - Polling URL (on 202 Created)
- `Cache-Control: no-store` - For analysis results

---

## Dependencies
- TASK-18-2 (SEOAnalysis model) - Done ✅
- TASK-18-4 (DomainValidatorService) - To Develop
- TASK-18-6 (AnalysisRunnerService) - To Develop
- TASK-18-28 (Rate limiting middleware) - To Write Tests

---

## Test Count Summary
| Category | Tests |
|----------|-------|
| AC-001: Create Analysis | 15 |
| AC-002: Get Results | 15 |
| AC-003: List Analyses | 15 |
| AC-004: Cached by Domain | 12 |
| AUTH: Authentication | 6 |
| SEC: Security | 12 |
| HTTP: HTTP Methods | 6 |
| EDGE: Edge Cases | 15 |
| PERF: Performance | 5 |
| **Total** | **101** |

---

## Status
- [x] Test documentation created
- [ ] Test file created
- [ ] Tests passing
