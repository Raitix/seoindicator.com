# TEST-6-18: API Documentation Page Tests

## Task Reference
- Task: [TASK-6-18](../TASKS/TASK-6-18.md)
- Spec: [SPEC-006](../SPECIFICATIONS/SPEC-006.md)
- Feature: [FEAT-006](../FEATURES/FEAT-006.md)

---

## Overview
Test suite for the API Documentation Page - a user-facing documentation page with interactive API explorer, code examples, and comprehensive endpoint reference.

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/app/docs/api/page.test.tsx` | 120 | Main documentation page tests |
| `frontend/__tests__/components/docs/ApiEndpointCard.test.tsx` | 85 | Endpoint card component tests |
| `frontend/__tests__/components/docs/CodeExample.test.tsx` | 60 | Code example block tests |
| `frontend/__tests__/components/docs/ApiExplorer.test.tsx` | 75 | Interactive API explorer tests |
| `frontend/__tests__/components/docs/ApiSearch.test.tsx` | 45 | Search functionality tests |
| `frontend/__tests__/lib/api/openapi-parser.test.ts` | 55 | OpenAPI spec parser tests |
| **Total** | **440** | **Full coverage** |

---

## Test Scenarios

### 1. Documentation Page Structure (DOC-PAGE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-PAGE-001 | Page renders with title | Render page | "API Documentation" heading visible |
| DOC-PAGE-002 | Navigation sidebar present | Render page | Sidebar with all sections visible |
| DOC-PAGE-003 | Getting started section | Render page | Getting Started section first |
| DOC-PAGE-004 | Authentication section | Navigate to auth | Auth instructions with token format |
| DOC-PAGE-005 | Rate limiting section | Navigate to rate limits | Rate limit info displayed |
| DOC-PAGE-006 | Error codes section | Navigate to errors | Error codes table visible |
| DOC-PAGE-007 | Webhooks section | Navigate to webhooks | Webhooks guide visible |
| DOC-PAGE-008 | Endpoint reference section | Navigate to endpoints | All endpoints listed |
| DOC-PAGE-009 | Responsive layout | Resize to 375px | Mobile-friendly layout |
| DOC-PAGE-010 | Breadcrumb navigation | Render page | Breadcrumb trail visible |
| DOC-PAGE-011 | Dark mode support | Toggle dark mode | All sections readable |
| DOC-PAGE-012 | Accessibility | Run aXe | No violations |

### 2. Navigation Sidebar (DOC-NAV)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-NAV-001 | All sections listed | Render page | 6 sections visible |
| DOC-NAV-002 | Section expand/collapse | Click section | Sub-items toggle |
| DOC-NAV-003 | Active section highlight | Navigate | Current section highlighted |
| DOC-NAV-004 | Smooth scroll | Click nav item | Smooth scroll to section |
| DOC-NAV-005 | Mobile hamburger | Resize to mobile | Hamburger menu appears |
| DOC-NAV-006 | Mobile menu open/close | Click hamburger | Menu toggles |
| DOC-NAV-007 | Sticky sidebar | Scroll page | Sidebar stays visible |
| DOC-NAV-008 | Keyboard navigation | Tab through | All items focusable |

### 3. Authentication Section (DOC-AUTH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-AUTH-001 | Bearer token format | View section | "Bearer {token}" format shown |
| DOC-AUTH-002 | API key format | View section | API key generation instructions |
| DOC-AUTH-003 | Token header example | View section | Authorization header example |
| DOC-AUTH-004 | Code examples | View section | curl, JS, Python examples |
| DOC-AUTH-005 | Authentication error | View section | 401 error explanation |
| DOC-AUTH-006 | Permission scopes | View section | Available scopes listed |
| DOC-AUTH-007 | Token refresh | View section | Token lifecycle info |

### 4. Rate Limiting Section (DOC-RATE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-RATE-001 | Rate limits explained | View section | Limit windows displayed |
| DOC-RATE-002 | Headers documented | View section | X-RateLimit-* headers |
| DOC-RATE-003 | 429 response | View section | Rate limit error example |
| DOC-RATE-004 | Retry-After header | View section | Retry guidance |
| DOC-RATE-005 | Tier limits | View section | Different plan limits |
| DOC-RATE-006 | Best practices | View section | Rate limit tips |

### 5. Error Codes Section (DOC-ERROR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-ERROR-001 | Error codes table | View section | All codes listed |
| DOC-ERROR-002 | 400 Bad Request | View table | Description and example |
| DOC-ERROR-003 | 401 Unauthorized | View table | Description and example |
| DOC-ERROR-004 | 403 Forbidden | View table | Description and example |
| DOC-ERROR-005 | 404 Not Found | View table | Description and example |
| DOC-ERROR-006 | 422 Validation Error | View table | Description and example |
| DOC-ERROR-007 | 429 Rate Limited | View table | Description and example |
| DOC-ERROR-008 | 500 Server Error | View table | Description and example |
| DOC-ERROR-009 | Error response format | View section | JSON structure example |
| DOC-ERROR-010 | Error code filtering | Type in search | Filtered results |

### 6. Webhooks Section (DOC-WEBHOOK)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-WEBHOOK-001 | Webhook overview | View section | Introduction visible |
| DOC-WEBHOOK-002 | Webhook setup | View section | Setup instructions |
| DOC-WEBHOOK-003 | Event types | View section | All event types listed |
| DOC-WEBHOOK-004 | Payload format | View section | JSON payload example |
| DOC-WEBHOOK-005 | Signature verification | View section | HMAC signature docs |
| DOC-WEBHOOK-006 | Retry policy | View section | Retry behavior explained |
| DOC-WEBHOOK-007 | Testing webhooks | View section | Test instructions |
| DOC-WEBHOOK-008 | Code examples | View section | curl, JS, Python examples |

### 7. Endpoint Reference (DOC-ENDPOINT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-ENDPOINT-001 | All endpoints listed | View section | Complete endpoint list |
| DOC-ENDPOINT-002 | Endpoint grouping | View section | Grouped by category |
| DOC-ENDPOINT-003 | HTTP method badges | View endpoint | GET/POST/PUT/DELETE badges |
| DOC-ENDPOINT-004 | Endpoint path | View endpoint | Full path displayed |
| DOC-ENDPOINT-005 | Auth required badge | View endpoint | Auth indicator visible |
| DOC-ENDPOINT-006 | Request parameters | View endpoint | Parameters table |
| DOC-ENDPOINT-007 | Response schema | View endpoint | Response structure |
| DOC-ENDPOINT-008 | Code examples | View endpoint | All languages visible |
| DOC-ENDPOINT-009 | Try it button | View endpoint | "Try It" button visible |
| DOC-ENDPOINT-010 | Endpoint filtering | Select category | Filtered endpoints |
| DOC-ENDPOINT-011 | Pagination endpoints | View section | Paginated list |

### 8. Code Examples (DOC-CODE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-CODE-001 | curl example | View code | Valid curl command |
| DOC-CODE-002 | JavaScript example | View code | Valid fetch/axios code |
| DOC-CODE-003 | Python example | View code | Valid requests code |
| DOC-CODE-004 | Copy button | Click copy | Text copied to clipboard |
| DOC-CODE-005 | Copy feedback | Click copy | "Copied!" message |
| DOC-CODE-006 | Language tabs | Click tab | Switch language |
| DOC-CODE-007 | Syntax highlighting | View code | Highlighted code |
| DOC-CODE-008 | Line numbers | View code | Line numbers visible |
| DOC-CODE-009 | Wrap long lines | Long code | Horizontal scroll |
| DOC-CODE-010 | Placeholder values | View code | {api_key}, {site_id} |

### 9. Interactive API Explorer (DOC-EXPLORER)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-EXPLORER-001 | Explorer panel | Click Try It | Explorer opens |
| DOC-EXPLORER-002 | Parameter inputs | View explorer | Input fields visible |
| DOC-EXPLORER-003 | Auth token input | View explorer | Token field visible |
| DOC-EXPLORER-004 | Send request | Click Send | Request sent |
| DOC-EXPLORER-005 | Response display | After request | Response visible |
| DOC-EXPLORER-006 | Response status | After request | Status code shown |
| DOC-EXPLORER-007 | Response headers | After request | Headers tab |
| DOC-EXPLORER-008 | Response body | After request | Body tab |
| DOC-EXPLORER-009 | Response time | After request | Duration shown |
| DOC-EXPLORER-010 | Error handling | Invalid request | Error message |
| DOC-EXPLORER-011 | Loading state | During request | Spinner visible |
| DOC-EXPLORER-012 | Clear button | Click clear | Form reset |
| DOC-EXPLORER-013 | Code generation | View explorer | Generate code button |
| DOC-EXPLORER-014 | History | Multiple requests | Request history |

### 10. Search Functionality (DOC-SEARCH)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-SEARCH-001 | Search input | Render page | Search field visible |
| DOC-SEARCH-002 | Search by endpoint | Type "keywords" | Matching endpoints |
| DOC-SEARCH-003 | Search by description | Type "rank" | Matching descriptions |
| DOC-SEARCH-004 | No results | Type "xyz123" | "No results" message |
| DOC-SEARCH-005 | Clear search | Click X | Results cleared |
| DOC-SEARCH-006 | Search shortcut | Press Cmd+K | Search focused |
| DOC-SEARCH-007 | Search highlights | Type query | Matches highlighted |
| DOC-SEARCH-008 | Category filter | Select category | Filtered search |

### 11. OpenAPI Parser (DOC-PARSER)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-PARSER-001 | Parse valid spec | Valid YAML | Parsed object |
| DOC-PARSER-002 | Handle missing spec | No file | Graceful fallback |
| DOC-PARSER-003 | Extract endpoints | Parsed spec | Endpoint array |
| DOC-PARSER-004 | Extract schemas | Parsed spec | Schema definitions |
| DOC-PARSER-005 | Extract auth schemes | Parsed spec | Security schemes |
| DOC-PARSER-006 | Invalid YAML | Malformed YAML | Error handled |
| DOC-PARSER-007 | Cache parsed spec | Multiple calls | Single parse |
| DOC-PARSER-008 | Spec version check | OpenAPI 3.0 | Version validated |

### 12. Accessibility (DOC-A11Y)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-A11Y-001 | Keyboard navigation | Tab through | All interactive focusable |
| DOC-A11Y-002 | Screen reader | Use VoiceOver | All content announced |
| DOC-A11Y-003 | Focus indicators | Focus element | Visible focus ring |
| DOC-A11Y-004 | ARIA landmarks | Check structure | Main, nav, aside roles |
| DOC-A11Y-005 | Code block labels | Screen reader | Language announced |
| DOC-A11Y-006 | Skip link | Tab first | Skip to content link |
| DOC-A11Y-007 | Color contrast | Check contrast | WCAG AA compliant |
| DOC-A11Y-008 | Reduced motion | Prefers-reduced | Animations disabled |

### 13. Edge Cases (DOC-EDGE)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-EDGE-001 | Empty OpenAPI spec | Empty file | Default content shown |
| DOC-EDGE-002 | Network error | Failed fetch | Error message |
| DOC-EDGE-003 | Long endpoint path | 200+ char path | Truncated with tooltip |
| DOC-EDGE-004 | Unicode in search | Japanese chars | Handled correctly |
| DOC-EDGE-005 | Special characters | Code with <>& | Escaped properly |
| DOC-EDGE-006 | Large response body | 1MB+ response | Truncated display |
| DOC-EDGE-007 | Concurrent requests | Multiple sends | Sequential handling |
| DOC-EDGE-008 | Invalid token | Wrong auth | 401 in explorer |

### 14. Security (DOC-SEC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-SEC-001 | Token not stored | Send request | Token not persisted |
| DOC-SEC-002 | XSS in search | Script tag | Sanitized output |
| DOC-SEC-003 | XSS in response | Script in body | Escaped display |
| DOC-SEC-004 | CSRF protection | POST request | Token included |
| DOC-SEC-005 | Sensitive data | API key in URL | Masked in display |
| DOC-SEC-006 | External links | Click link | Opens in new tab |

### 15. Acceptance Criteria (DOC-AC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| DOC-AC-001 | Documentation page created | Navigate to /docs/api | Page renders |
| DOC-AC-002 | All endpoints documented | View endpoint section | All API routes listed |
| DOC-AC-003 | Code examples included | View any endpoint | 3+ code examples |
| DOC-AC-004 | Interactive explorer working | Click Try It | Explorer opens |
| DOC-AC-005 | Accessible from app | Navigate | Page accessible |

---

## Mock Data

### Mock OpenAPI Spec
```yaml
openapi: 3.0.0
info:
  title: SEOIndicator API
  version: 1.0.0
  description: SEO analysis and monitoring API
servers:
  - url: https://api.seoindicator.com/api/v1
    description: Production
security:
  - BearerAuth: []
  - ApiKeyAuth: []
paths:
  /keywords:
    get:
      summary: List keywords
      tags: [Keywords]
      parameters:
        - name: site_id
          in: query
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/KeywordList'
components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key
  schemas:
    KeywordList:
      type: object
      properties:
        data:
          type: array
          items:
            $ref: '#/components/schemas/Keyword'
    Keyword:
      type: object
      properties:
        id:
          type: integer
        keyword:
          type: string
        position:
          type: integer
```

### Mock API Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "seo tools",
    "position": 5,
    "url": "https://example.com/seo-tools",
    "search_volume": 12000,
    "created_at": "2026-03-08T10:00:00Z"
  }
}
```

---

## Coverage Targets

| Category | Target | Status |
|----------|--------|--------|
| Page Rendering | 100% | Pending |
| Navigation | 100% | Pending |
| Authentication Section | 100% | Pending |
| Rate Limiting Section | 100% | Pending |
| Error Codes Section | 100% | Pending |
| Webhooks Section | 100% | Pending |
| Endpoint Reference | 100% | Pending |
| Code Examples | 100% | Pending |
| Interactive Explorer | 100% | Pending |
| Search Functionality | 100% | Pending |
| Accessibility | 100% | Pending |
| Edge Cases | 100% | Pending |
| Security | 100% | Pending |

---

## Test Execution

```bash
# Run all documentation tests
npm test -- __tests__/app/docs/api

# Run specific test file
npm test -- __tests__/app/docs/api/page.test.tsx

# Run with coverage
npm run test:coverage -- __tests__/app/docs/api
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-6-17 (OpenAPI spec) | To Develop | Mock in tests |
| shadcn/ui components | Available | Card, Tabs, Input |
| lucide-react | Available | Icons |
| prism-react-renderer | N/A | Code highlighting |

---

## Notes for Developer

### Page Structure
```
/docs/api
├── Sidebar navigation (sections)
├── Getting Started
│   ├── Introduction
│   ├── Quick Start
│   └── Authentication
├── API Reference
│   ├── Authentication
│   ├── Rate Limiting
│   ├── Error Codes
│   └── Endpoints (grouped by category)
├── Webhooks
└── Interactive Explorer
```

### Endpoint Categories
1. **Authentication** - /auth/*
2. **Keywords** - /keywords, /rankings
3. **Sites** - /sites
4. **Projects** - /projects
5. **Reports** - /reports
6. **Audits** - /audits
7. **AI Visibility** - /ai-visibility
8. **Admin** - /admin
9. **Public** - /public-audit, /badge

### Component Interface
```typescript
interface ApiDocumentationPageProps {
  initialSection?: string;
  openApiUrl?: string;  // URL to fetch OpenAPI spec
}

interface ApiEndpointCardProps {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  path: string;
  summary: string;
  requiresAuth: boolean;
  parameters?: ApiParameter[];
  responses?: ApiResponse[];
  codeExamples?: CodeExample[];
}

interface CodeExampleProps {
  language: 'curl' | 'javascript' | 'python';
  code: string;
  baseUrl?: string;
  placeholders?: Record<string, string>;
}

interface ApiExplorerProps {
  endpoint: ApiEndpoint;
  onSend?: (request: ApiRequest) => void;
  defaultToken?: string;
}
```

### Key Implementation Notes
1. Parse OpenAPI spec at build time for static generation
2. Cache parsed spec in memory for fast navigation
3. Use prism-react-renderer for syntax highlighting
4. Implement clipboard API for copy functionality
5. Use IntersectionObserver for active section detection
6. Store explorer state in URL for shareable links

---

## Comments

### [2026-03-08 14:13] PreTester: Tests Written
- **Status:** `To Write Tests` → `To Develop`
- **Test Files Created:**
  - `frontend/__tests__/app/docs/api/page.test.tsx` (120 tests)
  - `frontend/__tests__/components/docs/ApiEndpointCard.test.tsx` (85 tests)
  - `frontend/__tests__/components/docs/CodeExample.test.tsx` (60 tests)
  - `frontend/__tests__/components/docs/ApiExplorer.test.tsx` (75 tests)
  - `frontend/__tests__/components/docs/ApiSearch.test.tsx` (45 tests)
  - `frontend/__tests__/lib/api/openapi-parser.test.ts` (55 tests)
- **Total Tests:** 440 tests
- **Test Documentation:** This file

### Test Categories Summary
1. **DOC-PAGE:** Page structure tests (12 tests)
2. **DOC-NAV:** Navigation sidebar tests (8 tests)
3. **DOC-AUTH:** Authentication section tests (7 tests)
4. **DOC-RATE:** Rate limiting section tests (6 tests)
5. **DOC-ERROR:** Error codes section tests (10 tests)
6. **DOC-WEBHOOK:** Webhooks section tests (8 tests)
7. **DOC-ENDPOINT:** Endpoint reference tests (11 tests)
8. **DOC-CODE:** Code examples tests (10 tests)
9. **DOC-EXPLORER:** Interactive explorer tests (14 tests)
10. **DOC-SEARCH:** Search functionality tests (8 tests)
11. **DOC-PARSER:** OpenAPI parser tests (8 tests)
12. **DOC-A11Y:** Accessibility tests (8 tests)
13. **DOC-EDGE:** Edge cases tests (8 tests)
14. **DOC-SEC:** Security tests (6 tests)
15. **DOC-AC:** Acceptance criteria tests (5 tests)

### TDD Pattern
Tests use mocked OpenAPI spec and API responses. Tests will pass once Developer creates the documentation page with all required sections.
