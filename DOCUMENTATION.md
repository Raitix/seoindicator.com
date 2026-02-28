# DOCUMENTATION.md

---
## Tools Infrastructure (TASK-16-1)

### Feature Description
Foundational infrastructure for interactive SEO tools. Provides CORS proxy API endpoint, HTML parsing capabilities, base tool class architecture, and shared utilities for all interactive SEO analysis tools in FEAT-016.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/tools/analyze` | Main tool analysis endpoint |
| GET | `/api/v1/tools/rate-limit-status` | Get current rate limit status |
| GET | `/api/v1/tools/captcha-config` | Get CAPTCHA configuration |

### Analyze Endpoint
`POST /api/v1/tools/analyze`

**Request Body:**
```json
{
  "url": "https://example.com",
  "tool": "meta-tag-analyzer"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "analyzedAt": "2026-02-27T12:00:00Z",
    "tool": "meta-tag-analyzer",
    "results": { ... }
  }
}
```

### Core Components

| Component | File | Description |
|-----------|------|-------------|
| API Controller | `APIProxyController.php` | Main endpoint handling CORS proxy |
| HTML Parser | `HtmlParserService.php` | Symfony DomCrawler integration |
| Base SEOTool | `Services/Tools/SEOTool.php` | Abstract base class for tools |
| URL Validation | `URLValidationService.php` | SSRF protection and URL sanitization |
| Rate Limiting | `ToolsRateLimitService.php` | Per-IP rate limiting |
| ToolResult DTO | `Services/Tools/ToolResult.php` | Structured result container |

### Base SEOTool Class
```php
abstract class SEOTool
{
    abstract public function name(): string;
    abstract public function analyze(Crawler $crawler, string $url): ToolResult;
    
    // Shared methods available to all tools
    protected function fetchUrl(string $url): string;
    protected function parseHtml(string $html): Crawler;
    protected function validateUrl(string $url): bool;
}
```

### URL Validation (SSRF Protection)
| Protection | Description |
|------------|-------------|
| Localhost blocked | `localhost`, `127.*` blocked |
| Private IPs blocked | `10.*`, `172.16-31.*`, `192.168.*` blocked |
| Cloud metadata blocked | `169.254.169.254` blocked |
| File protocol blocked | `file://` protocol blocked |
| Internal domains | Domains containing "internal" blocked |

### Rate Limiting Configuration
| Limit Type | Default | Description |
|------------|---------|-------------|
| Per-IP Daily Limit | 3 requests | Max tool uses per IP per 24 hours |
| CAPTCHA Threshold | 3 uses | CAPTCHA shown after 3 tool uses |
| Request Timeout | 30s | Total request timeout |
| Connection Timeout | 10s | Connection establishment timeout |
| Max Response Size | 10MB | Maximum HTML response size |

### Response Headers
```
X-RateLimit-Limit: 3
X-RateLimit-Remaining: 2
X-RateLimit-Reset: 1709049600
```

### Error Handling
| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `INVALID_URL` | 400 | URL format validation failed |
| `BLOCKED_URL` | 403 | URL blocked by SSRF protection |
| `FETCH_FAILED` | 502 | Failed to fetch external URL |
| `RATE_LIMIT_EXCEEDED` | 429 | Rate limit exceeded |
| `TOOL_NOT_FOUND` | 404 | Unknown tool name |

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| SSRF Protection | URL validation service blocks private IPs, localhost |
| Rate Limiting | Per-IP tracking with configurable limits |
| CAPTCHA Integration | hCaptcha/ReCAPTCHA support after threshold |
| Request Timeout | Prevents hanging on slow responses |
| Response Size Limit | Prevents memory exhaustion from large pages |

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| `ToolsRateLimitServiceTest` | 64 | 250 |
| `APIProxyControllerTest` | Feature tests | API endpoint tests |
| `URLValidationServiceTest` | URL validation tests | SSRF protection tests |
| `MetaTagAnalyzerTest` | Tool-specific tests | Extraction tests |
| `KeywordDensityCheckerTest` | Tool-specific tests | Density calculation tests |

### Production Status
- **Deployed**: 2026-02-27 (via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 64+ tests passing
- **Status**: Complete

### Dependencies
- Symfony DomCrawler (HTML parsing)
- Laravel HTTP Client (URL fetching)

### Related Tasks
- TASK-16-2: Meta Tag Analyzer Tool
- TASK-16-3: Keyword Density Checker Tool
- TASK-16-4: Tool Embedding System
- TASK-16-11: Rate Limiting & Anti-Abuse

---
## Keyword Add/Edit Forms (TASK-1-14)

### Feature Description
Forms for adding new keywords and editing existing keywords in the keyword tracking system. Provides user-friendly interface for keyword management with validation and feedback.

### Form Components

**Add Keyword Form:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `keyword` | string | Yes | The search term to track (max 255 chars) |
| `targetUrl` | URL | No | User's target URL for this keyword |
| `searchEngine` | select | Yes | Search engine (default: Google) |
| `location` | select | Yes | Country code (default: US) |
| `device` | select | Yes | Device type (default: desktop) |

**Edit Keyword Form:**
| Field | Type | Description |
|-------|------|-------------|
| `targetUrl` | URL | Update target URL |
| `status` | select | Change status (active/paused) |

### Validation Rules
| Field | Validation |
|-------|------------|
| `keyword` | Required, string, max 255 chars |
| `targetUrl` | Optional, valid URL format |
| `searchEngine` | Required, enum: google |
| `location` | Required, valid country code |
| `device` | Required, enum: desktop, mobile |

### User Features
- **Duplicate Detection**: Warning shown for duplicate keywords (not blocking)
- **Form Reset**: Form clears after successful add
- **Cancel Button**: Returns to keyword list
- **Toast Notifications**: Success/error feedback
- **Responsive Layout**: Works on desktop and mobile

### Technical Implementation
- **Framework**: React Hook Form for form handling
- **Validation**: Zod schema validation
- **UI Components**: Shadcn/UI form components
- **Routing**:
  - Add: `/sites/[siteId]/keywords/new/`
  - Edit: `/sites/[siteId]/keywords/[keywordId]/edit/`
- **API Integration**: Creates/updates keywords via Keywords API

### Static Export Support
Dynamic route pages use `useParams()` hook for client-side route parameter extraction, ensuring compatibility with Next.js static export (`output: 'export'`).

### Frontend Files
- `frontend/app/sites/[siteId]/keywords/new/page.tsx` - Add keyword page
- `frontend/app/sites/[siteId]/keywords/[keywordId]/edit/page.tsx` - Edit keyword page
- `frontend/components/keywords/KeywordForm.tsx` - Shared form component
- `frontend/lib/validations/keyword.ts` - Zod validation schemas
- `frontend/lib/api/keywords.ts` - API helper functions

### Unit Tests
| Test File | Tests | Description |
|-----------|-------|-------------|
| `KeywordForm.test.tsx` | 6 | Field rendering, validation, duplicate warning, submit flows |
| `add/page.test.tsx` | 1 | Page renders correctly with mocked params |

### Production Status
- **Deployed**: 2026-02-28 (GitHub Actions)
- **Health Check**: Passing
- **Tests**: 7/7 passing
- **Status**: Complete

### Dependencies
- TASK-1-4 (Keywords CRUD API)
- TASK-1-1 (Keywords table)

### Related Tasks
- TASK-1-13: Keyword List Page
- TASK-1-15: Ranking History Chart

---
## Rankings API Endpoints (TASK-6-9)

### Service Description
REST API endpoint for retrieving keyword ranking history. Provides programmatic access to historical ranking data via secure API key authentication, enabling integration with external dashboards, reporting tools, and automation systems.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/keywords/{id}/rankings` | Get ranking history for a keyword |

### Get Ranking History
`GET /api/v1/keywords/{id}/rankings`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `from` | ISO 8601 date | 30 days ago | Start date filter |
| `to` | ISO 8601 date | today | End date filter |
| `limit` | int | 100 | Max results (max: 1000) |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "keyword_id": 1,
    "history": [
      {
        "position": 5,
        "checked_at": "2026-02-27T17:00:00Z"
      },
      {
        "position": 7,
        "checked_at": "2026-02-26T17:00:00Z"
      }
    ]
  }
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Missing or invalid API key |
| 403 | Keyword owned by another user |
| 404 | Keyword not found |
| 422 | Invalid date parameters |

### Authentication
- **Method:** API Key via Bearer token
- **Header:** `Authorization: Bearer sk_live_...`
- **Permission:** `rankings:read` required

### Authorization
| Check | Behavior |
|-------|----------|
| Valid API key | Request processed |
| Invalid/revoked key | 401 Unauthorized |
| Cross-user keyword | 403 Forbidden |
| Non-existent keyword | 404 Not Found |

### Security Implementation
| Measure | Implementation |
|---------|----------------|
| Authentication | API Key middleware with Bearer token |
| IDOR Protection | User isolation via keyword ownership check |
| SQL Injection | Eloquent ORM with parameterized queries |
| Route Constraint | Numeric ID only (`where('id', '[0-9]+')`) |
| Rate Limiting | Per-key rate limits with headers |

### Rate Limit Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1709049600
```

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/RankingsApiController.php`
- Route: Defined in `backend/routes/api.php`
- Middleware: `api-key.auth:rankings:read`

### Route Configuration
```php
Route::get('keywords/{id}/rankings', [RankingsApiController::class, 'index'])
    ->middleware('api-key.auth:rankings:read')
    ->where('id', '[0-9]+');
```

### Ordering
- Results ordered by `checked_at DESC` (most recent first)

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| RankingsApiControllerTest | 44 | 125 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Success cases | 10 | History retrieval, filtering, pagination |
| Authentication | 6 | API key validation, revoked/expired keys |
| Authorization | 3 | User isolation, keyword ownership |
| Validation | 8 | Date parameters, limit bounds |
| Edge cases | 8 | Position 0, deep rankings, ISO dates |
| Security | 4 | SQL injection, IDOR, path traversal |
| Rate limiting | 4 | Headers, per-key limits |
| Integration | 2 | Full workflow, user isolation |

### Production Status
- **Deployed**: 2026-02-27 (via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 44/44 passing (125 assertions)
- **Status**: Complete

### Dependencies
- TASK-6-3 (API Key Service)
- TASK-6-8 (Keywords API)
- FEAT-001 (Keywords & Rankings tables)

### Related Tasks
- TASK-6-8: Keywords API endpoints
- TASK-6-3: API Key Service
- TASK-6-5: Rate Limiting Middleware

---
## Project CRUD API (TASK-8-7)

### Service Description
REST API endpoints for project management (Create, Read, Update, Delete). Users can organize their SEO work into separate projects/sites, enabling multi-site management for agencies and freelancers. Core functionality for the Multi-Site Project Management feature (FEAT-008).

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/projects` | Create a new project |
| GET | `/api/v1/projects` | List all projects for authenticated user |
| GET | `/api/v1/projects/{id}` | Get single project details |
| PUT | `/api/v1/projects/{id}` | Update project name, domain, notes |
| DELETE | `/api/v1/projects/{id}` | Delete project (requires confirmation) |
| POST | `/api/v1/projects/{id}/archive` | Archive project (soft delete) |
| POST | `/api/v1/projects/{id}/restore` | Restore archived project |

### Create Project
`POST /api/v1/projects`

**Request Body:**
```json
{
  "name": "Client Website",
  "domain": "https://example.com",
  "notes": "Main client SEO project"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `name` | Required, string, max 255 chars |
| `domain` | Optional, valid URL format, max 500 chars |
| `notes` | Optional, string, max 5000 chars (HTML sanitized) |

**Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "name": "Client Website",
  "domain": "https://example.com",
  "notes": "Main client SEO project",
  "is_default": false,
  "archived_at": null,
  "created_at": "2026-02-26T18:00:00Z",
  "updated_at": "2026-02-26T18:00:00Z"
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 422 | Invalid domain format or missing name |

### List Projects
`GET /api/v1/projects`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `archived` | boolean | false | Include/exclude archived projects |
| `page` | int | 1 | Page number |
| `perPage` | int | 15 | Results per page (max: 100) |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Client Website",
      "domain": "https://example.com",
      "notes": "Main client SEO project",
      "is_default": true,
      "archived_at": null,
      "created_at": "2026-02-26T18:00:00Z",
      "updated_at": "2026-02-26T18:00:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "active": 1,
    "archived": 0,
    "page": 1,
    "perPage": 15
  }
}
```

### Get Project
`GET /api/v1/projects/{id}`

**Response (200):** Returns single project object

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 403 | Project owned by another user |
| 404 | Project not found |

### Update Project
`PUT /api/v1/projects/{id}`

**Request Body:**
```json
{
  "name": "Updated Name",
  "domain": "https://newdomain.com",
  "notes": "Updated notes"
}
```

**Note:** Partial updates supported - only provided fields are updated.

**Response (200):** Returns updated project object

### Delete Project
`DELETE /api/v1/projects/{id}?confirm=true`

**Important:** Delete requires `confirm=true` query parameter to prevent accidental deletion.

**Response (200):**
```json
{
  "success": true,
  "message": "Project deleted successfully.",
  "deleted_id": 1
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 400 | Missing `confirm=true` parameter |
| 401 | Unauthenticated request |
| 403 | Project owned by another user |
| 404 | Project not found |

**Side Effects:** Cascade deletes all keywords, competitors, and reports associated with the project.

### Archive Project
`POST /api/v1/projects/{id}/archive`

**Response (200):**
```json
{
  "id": 1,
  "archived_at": "2026-02-26T20:00:00Z"
}
```

**Note:** Archived projects are excluded from default list views but retain all data.

### Restore Project
`POST /api/v1/projects/{id}/restore`

**Response (200):**
```json
{
  "id": 1,
  "archived_at": null
}
```

### Authorization
- All endpoints require authentication (Bearer token)
- Users can only access their own projects (user isolation via `ProjectPolicy`)
- Cross-user access returns 403 Forbidden (not 404, to provide clear authorization feedback)

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| Authentication | `auth` middleware on all endpoints |
| IDOR Protection | `ProjectPolicy` enforces user isolation |
| SQL Injection | Eloquent ORM with parameterized queries |
| XSS | `strip_tags()` on notes field |
| Mass Assignment | `$fillable` whitelist on Project model |
| Delete Protection | Confirmation parameter required |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/ProjectController.php`
- Request: `backend/app/Http/Requests/ProjectRequest.php`
- Model: `backend/app/Models/Project.php`
- Policy: `backend/app/Policies/ProjectPolicy.php`
- Routes: Defined in `backend/routes/api.php`

### Model Properties
| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `user_id` | int | FK to users.id |
| `account_id` | int? | FK to accounts.id (nullable) |
| `name` | string | Project name |
| `domain` | string? | Project domain URL |
| `notes` | text? | User notes |
| `is_default` | bool | Whether this is user's default project |
| `archived_at` | timestamp? | Archive timestamp |

### Model Relationships
| Method | Relationship | Description |
|--------|--------------|-------------|
| `user()` | belongsTo | User who owns this project |
| `keywords()` | hasMany | Keywords in this project |
| `competitors()` | hasMany | Competitors in this project |
| `reports()` | hasMany | Reports in this project |
| `members()` | hasMany | ProjectMember records |

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| ProjectControllerTest | 39 | 39 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Authentication | 2 | 401 for unauthenticated requests |
| Create Project | 10 | Validation, ownership, edge cases |
| Read Project | 9 | List, single, pagination, filtering |
| Update Project | 7 | Partial updates, validation |
| Archive/Restore | 5 | Toggle behavior, authorization |
| Delete Project | 4 | Confirmation, cascade behavior |
| Security | 2 | SQL injection, XSS protection |

**Skipped Tests (Dependencies):**
- DP-005: Delete cascades to keywords (requires keywords.project_id)
- DP-006: Delete cascades to competitors (requires competitors.project_id)
- DP-007: Delete cascades to reports (requires reports.project_id)

### Production Status
- **Deployed**: 2026-02-27 (commit via GitHub Actions run 22461012834)
- **Health Check**: Passing
- **Tests**: 39/39 passing (3 skipped - pending dependencies)
- **Status**: Complete

### Dependencies
- TASK-8-1 (Projects Table)
- TASK-8-6 (Project Policies)

### Related Tasks
- TASK-8-1: Projects Table schema
- TASK-8-2: Add project_id to existing tables
- TASK-8-3: Data migration for existing records
- TASK-8-4: SetProjectContext middleware
- TASK-8-5: ProjectScope global scope
- TASK-8-6: ProjectPolicy authorization
- TASK-8-8: Frontend project components

---
## Competitor CRUD API (TASK-3-2)

### Service Description
REST API endpoints for competitor management (Create, Read, Update, Delete). Users can add competitor domains to track alongside their own rankings. Core functionality for the competitor rank comparison feature (FEAT-003).

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/competitors` | Create a new competitor |
| GET | `/api/v1/competitors` | List all competitors for authenticated user |
| GET | `/api/v1/competitors/{id}` | Get single competitor details |
| PUT | `/api/v1/competitors/{id}` | Update competitor displayName and notes |
| DELETE | `/api/v1/competitors/{id}` | Delete competitor (cascade delete rankings) |

### Create Competitor
`POST /api/v1/competitors`

**Request Body:**
```json
{
  "domain": "competitor.com",
  "displayName": "Main Competitor",
  "notes": "Primary market rival"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `domain` | Required, valid domain format, unique per user |
| `displayName` | Optional, string, max 255 chars |
| `notes` | Optional, string, max 5000 chars |

**Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "domain": "competitor.com",
  "display_name": "Main Competitor",
  "notes": "Primary market rival",
  "created_at": "2026-02-20T10:05:00Z",
  "updated_at": "2026-02-20T10:05:00Z"
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 400 | Invalid domain format |
| 409 | Duplicate competitor for same user |

### List Competitors
`GET /api/v1/competitors`

**Response (200):**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "domain": "competitor.com",
    "display_name": "Main Competitor",
    "notes": "Primary market rival",
    "created_at": "2026-02-20T10:05:00Z",
    "updated_at": "2026-02-20T10:05:00Z"
  }
]
```

### Get Competitor
`GET /api/v1/competitors/{id}`

**Response (200):** Returns single competitor object

**Error Responses:**
| Code | Condition |
|------|-----------|
| 404 | Competitor not found or not owned by user |

### Update Competitor
`PUT /api/v1/competitors/{id}`

**Request Body:**
```json
{
  "displayName": "Updated Name",
  "notes": "Updated notes"
}
```

**Note:** Domain cannot be changed after creation.

**Response (200):** Returns updated competitor object

### Delete Competitor
`DELETE /api/v1/competitors/{id}`

**Response (204):** No content

**Side Effects:** Cascade deletes all competitor_rankings records

### Authorization
- All endpoints require authentication
- Users can only access their own competitors (user isolation via `findForUser()` method)
- Cross-user access returns 404 (not 403, to avoid information leakage)

### Competitor Limit
- Default limit: 3 competitors per user
- Enforced in `StoreCompetitorRequest` validation
- Configurable for future subscription tiers

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| Authentication | `auth` middleware on all endpoints |
| IDOR Protection | `findForUser()` enforces user isolation |
| SQL Injection | Eloquent ORM with parameterized queries |
| XSS | Input validation and output escaping |
| SSRF Protection | Domain validation via `DomainValidator` service |
| Mass Assignment | `$fillable` whitelist on Competitor model |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/CompetitorController.php`
- Repository: `backend/app/Repositories/CompetitorRepository.php`
- Requests: `StoreCompetitorRequest`, `UpdateCompetitorRequest`
- Model: `backend/app/Models/Competitor.php`
- Factory: `backend/database/factories/CompetitorFactory.php`
- Routes: Defined in `backend/routes/api.php`

### Model Properties
| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `user_id` | int | FK to users.id |
| `domain` | string | Competitor domain |
| `display_name` | string? | Friendly display name |
| `notes` | string? | User notes |
| `deleted_at` | timestamp? | Soft delete timestamp |

### Model Relationships
| Method | Relationship | Description |
|--------|--------------|-------------|
| `user()` | belongsTo | User who owns this competitor |
| `rankings()` | hasMany | CompetitorRanking records |

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| CompetitorControllerTest | 6 | 15 |
| CompetitorRepositoryTest | 5 | 9 |
| CompetitorSecurityTest | 8 | 19 |
| **Total** | **19** | **43** |

**Test Categories:**
| Category | Description |
|----------|-------------|
| CRUD Operations | Create, list, get, update, delete |
| Validation | Missing domain, duplicate, format |
| Security | SQL injection, XSS, IDOR protection |
| Authorization | Unauthenticated (401), cross-user (404) |

### Production Status
- **Deployed**: 2026-02-27 (commit c86486d via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 19/19 passing (43 assertions)
- **Status**: Complete

### Dependencies
- TASK-3-1 (Competitors table schema)
- DomainValidator service (existing)

### Related Tasks
- TASK-3-3: Domain Validation Service
- TASK-3-4: Ranking Check Integration
- TASK-3-5: Comparison Engine Service

---
## Production Routing Configuration (TASK-12-12)

### Service Description
Production server routing configuration for Laravel API endpoints on shared hosting (LiteSpeed). Documents the `.htaccess` rules required to properly route `/api/*` requests to the Laravel application.

### Problem Solved
Production API endpoints (`/api/health`, `/api/v1/*`) were returning 404 errors because the `.htaccess` routing was incorrectly stripping the `/api/` prefix before passing requests to Laravel.

### Root Cause
- **Incorrect Rule:** `RewriteRule ^api/(.*)$ backend/public/index.php [L,QSA]`
- This captured everything AFTER `/api/` and passed to Laravel
- Laravel's `api.php` routes already expect `/api/` prefix (configured in `bootstrap/app.php`)
- Result: Request to `/api/health` became `/health` → 404

### Solution
Updated `.htaccess` to preserve full `REQUEST_URI`:

```apache
# API Routes - Route to Laravel with full URI preserved
RewriteRule ^api/(.*)$ backend/public/index.php [L,QSA,E=REQUEST_URI:/%{REQUEST_URI}]
```

**Key Change:** The `E=REQUEST_URI:/%{REQUEST_URI}` flag ensures Laravel receives the full original request path including `/api/` prefix.

### Production Environment
- **Server:** LiteSpeed (shared hosting)
- **Document Root:** `public_html/`
- **Laravel Entry Point:** `backend/public/index.php`
- **Production URL:** https://seoindicator.com

### Verified Endpoints
| Endpoint | Status | Description |
|----------|--------|-------------|
| `GET /api/health` | 200 OK | Health check endpoint |
| `GET /api/v1/projects/{id}/gsc` | 401 | GSC data (auth required) |
| `GET /api/v1/projects/{id}/gsc/opportunities` | 401 | Opportunity keywords (auth required) |

### Security Notes
- All protected endpoints return 401 Unauthorized (not 404) when routes are properly configured
- Public health endpoint returns 200 with JSON status
- No sensitive information exposed in error responses

### Technical Implementation
- File: `public_html/.htaccess` (production server)
- Related: `backend/routes/api.php` (Laravel API routes)
- Related: `backend/bootstrap/app.php` (route prefix configuration)

### Deployment
- **Fixed:** 2026-02-24 (commit in TASK-5-9)
- **Deployed:** 2026-02-27 (via GitHub Actions)
- **Verified:** 2026-02-27 (production testing passed)

### Dependencies
- Laravel routing configuration
- LiteSpeed/Apache `.htaccess` support

---
## API Key Service (TASK-6-3)

### Service Description
Core security service for API key generation, hashing, and validation. Provides cryptographically secure API key management with timing-safe comparison for authentication.

### Core Functionalities

**ApiKeyService Class:**
| Method | Description |
|--------|-------------|
| `generate(): array` | Generate new API key with hash and prefix |
| `hash(string $key): string` | SHA-256 hash of key (64 lowercase hex chars) |
| `validate(string $key, string $storedHash): bool` | Timing-safe key validation |
| `getPrefix(string $key): string` | Extract first 20 chars for identification |
| `isValidFormat(string $key): bool` | Validate key format (prefix + base64url body) |

### Key Generation
```php
$result = $apiKeyService->generate();
// Returns: ['key' => 'sk_live_...', 'hash' => '...', 'prefix' => 'sk_live_abc123...']
```

**Generation Process:**
1. Generate 32 random bytes via `random_bytes()` (CSPRNG, 256-bit entropy)
2. Encode as base64url (no padding)
3. Prefix with `sk_live_`
4. Hash with SHA-256 for storage
5. Extract first 20 chars as prefix

### Key Format
```
sk_live_<base64url_encoded_random_bytes>
```
- Prefix: `sk_live_` (9 chars)
- Body: 43+ chars of base64url-encoded data
- Total length: 52+ chars
- Characters: `a-z`, `A-Z`, `0-9`, `-`, `_` (URL-safe)

### Security Implementation
| Security Measure | Implementation |
|------------------|----------------|
| CSPRNG | `random_bytes(32)` - 256 bits of entropy |
| Hashing | SHA-256, 64 lowercase hex characters |
| Timing-safe comparison | `hash_equals()` for validation |
| No plaintext storage | Only hash stored in database |
| Key shown once | Returned only on creation |

### Validation Flow
```php
// 1. Client sends: Authorization: Bearer sk_live_...
// 2. Server hashes key
$hash = hash('sha256', $key);
// 3. Timing-safe comparison with stored hash
$valid = hash_equals($storedHash, $hash);
```

### Technical Implementation
- Service: `backend/app/Services/ApiKeyService.php`
- Test: `backend/tests/Unit/Services/ApiKeyServiceTest.php`
- Dependencies: None (pure PHP implementation)

### Test Coverage
| Category | Tests | Assertions |
|----------|-------|------------|
| Key Generation (KG) | 14 | - |
| Hash Method (HM) | 10 | - |
| Validation (VL) | 13 | - |
| Prefix Extraction (PX) | 6 | - |
| Format Validation (FV) | 11 | - |
| Security (SC) | 6 | - |
| Edge Cases (EC) | 5 | - |
| Integration (IT) | 6 | - |
| **Total** | **66** | **110** |

### Production Status
- **Deployed**: 2026-02-27 (commit via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 66/66 passing (110 assertions)
- **Status**: Complete

### Dependencies
- TASK-6-1 (API Keys table - pending)
- TASK-6-2 (ApiKey model/repository - pending)

---
## Team Management Service (TASK-11-3)

### Service Description
Backend service class for managing team collaboration features. Handles adding/removing team members, role updates, and invitation token management with security-first design.

### Core Functionalities

**TeamService Class:**
| Method | Description |
|--------|-------------|
| `addMember(array $data): TeamMember` | Invite user to team with secure token |
| `updateRole(int $teamMemberId, string $role): bool` | Update member's role |
| `removeMember(int $teamMemberId): bool` | Remove member from team |
| `validateInvitationToken(string $token): ?TeamMember` | Validate invitation token |
| `acceptInvitation(string $token, int $userId): bool` | Accept pending invitation |
| `cancelInvitation(int $teamMemberId): bool` | Cancel pending invitation |
| `canManageTeam(User $user, Account $account): bool` | Check management permission |

### Security Measures

**Token Security:**
- 64-character cryptographic tokens
- Timing-safe comparison via `hash_equals()`
- Unique token constraint in database
- Token expiration support (24-hour TTL)

**Role-Based Restrictions:**
- Owner/Admin can manage team members
- Role hierarchy enforced via PermissionValidator
- Self-modification prevention (cannot change own role)
- Last admin protection (cannot demote last admin)

**Permission Checks:**
- `canManageTeam()` validates actor permissions
- Cross-team access prevention
- Cascade cleanup on member removal (project_members)

### Technical Implementation
- Service: `backend/app/Services/TeamService.php`
- Model: `backend/app/Models/TeamMember.php`
- Model: `backend/app/Models/Account.php`
- Migration: `2026_02_26_200000_create_accounts_table.php`
- Migration: `2026_02_26_200100_create_team_members_table.php`
- Migration: `2026_02_26_200200_add_account_id_to_projects_table.php`

### Model Relationships

**TeamMember Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `account()` | belongsTo | The account this membership belongs to |
| `user()` | belongsTo | The user who is a member |
| `inviter()` | belongsTo | User who sent the invitation |

**Account Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `owner()` | belongsTo | Account owner (user) |
| `members()` | hasMany | TeamMember records |
| `projects()` | hasMany | Projects belonging to account |

**Project Model (updated):**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `account()` | belongsTo | Account this project belongs to |

**User Model (updated):**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `account()` | belongsTo | User's owned account |
| `teamMemberships()` | hasMany | TeamMember records |

### Database Schema

**Accounts Table:**
```sql
CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Team Members Table:**
```sql
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    invitation_token VARCHAR(64) NULL UNIQUE,
    invited_by BIGINT UNSIGNED NOT NULL,
    invited_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY idx_team_account_user (account_id, user_id)
);
```

### Role Hierarchy
| Role | Level | Permissions |
|------|-------|-------------|
| Owner | 100 | Full access, manage team, billing |
| Admin | 75 | Full access, manage team |
| Editor | 50 | Add/edit keywords, generate reports |
| Viewer | 25 | Read-only access |

### Test Coverage
- **Unit Tests:** 56 tests, 101 assertions
- **Test File:** `backend/tests/Unit/Services/TeamServiceTest.php`

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Add Member (AM) | 13 | Invitation creation, token generation |
| Update Role (UR) | 8 | Role change validation |
| Remove Member (RM) | 7 | Member removal with cascade |
| Token Validation (TV) | 6 | Token verification |
| Permission Checks (PC) | 6 | Authorization validation |
| Security/Edge Cases | 16 | XSS, SQL injection, boundaries |

### Production Status
- **Deployed**: 2026-02-26 (commit 82a3081)
- **Migration Batch**: 10
- **Health Check**: Passing
- **Tests**: 56/56 passing
- **Status**: Complete

### Dependencies
- TASK-11-1: Team members table (pending)
- TASK-11-4: PermissionValidator (in progress)

### Related Tasks
- TASK-11-1: Team members table creation
- TASK-11-2: Project members table
- TASK-11-4: Permission validator

---
## Database Schema: Project Members Table (TASK-11-2)

### Project Members Table
Stores project-level user assignments with role-specific permissions. Enables fine-grained access control for team collaboration, allowing users to be members of multiple projects with different roles.

```sql
CREATE TABLE project_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY idx_project_user (project_id, user_id)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `project_id` | BIGINT UNSIGNED | FK to projects.id |
| `user_id` | BIGINT UNSIGNED | FK to users.id |
| `role` | ENUM | Project role: `editor` or `viewer` (default: `viewer`) |
| `created_at` | TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Constraints:**
- Foreign Key: `project_id → projects.id` ON DELETE CASCADE
- Foreign Key: `user_id → users.id` ON DELETE CASCADE
- Unique: `(project_id, user_id)` - prevents duplicate project memberships

**Indexes:**
- `idx_project_user` unique index on `(project_id, user_id)`

**Model:** `App\Models\ProjectMember`

### Role Permissions (Project Level)
| Permission | Editor | Viewer |
|------------|--------|--------|
| View project data | ✅ | ✅ |
| Add/edit keywords | ✅ | ❌ |
| Delete keywords | ✅ | ❌ |
| Manage competitors | ✅ | ❌ |
| Generate reports | ✅ | ✅ |
| Schedule reports | ✅ | ❌ |
| Configure alerts | ✅ | ❌ |

### Model Relationships

**ProjectMember Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `project()` | belongsTo | The project this membership belongs to |
| `user()` | belongsTo | The user who is a member |

**Project Model (added relationships):**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `members()` | hasMany | ProjectMember records for this project |
| `users()` | belongsToMany | Users who are members of this project (through project_members) |

**User Model (added relationships):**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `projectMemberships()` | hasMany | ProjectMember records for this user |
| `memberProjects()` | belongsToMany | Projects this user is a member of (through project_members) |

### Production Status
- **Deployed**: 2026-02-26 (commit 42ca3ac via GitHub Actions)
- **Migration**: `2026_02_26_191000_create_project_members_table.php`
- **Verified**: Health check passing, 28/28 tests passing
- **Status**: Complete

### Dependencies
- TASK-8-1 (Projects Table)
- Existing users table

### Related Tasks
- TASK-11-1: Team members table (global team roles)
- TASK-11-3: Team management service
- TASK-11-4: Permission validator

---
## Database Schema: Keyword Suggestions Tables (TASK-5-1)

### Keyword Suggestions Table
Stores discovered keyword opportunities for tracking. Populated by the keyword discovery system (FEAT-005) using Google Suggest API and question-based generation.

```sql
CREATE TABLE keyword_suggestions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    source VARCHAR(255) NOT NULL,
    search_volume INT NULL,
    difficulty_score INT NULL,
    opportunity_score INT NULL,
    is_tracking TINYINT(1) DEFAULT 0,
    is_dismissed TINYINT(1) DEFAULT 0,
    discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY keyword_suggestions_site_id_keyword_unique (site_id, keyword)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id |
| `keyword` | VARCHAR(255) | The suggested keyword |
| `source` | VARCHAR(255) | Source: `suggest`, `competitor`, `related` |
| `search_volume` | INT | Monthly search volume (nullable - MVP has no data) |
| `difficulty_score` | INT | Keyword difficulty 0-100 (nullable - MVP has no data) |
| `opportunity_score` | INT | Calculated opportunity score (0-100) |
| `is_tracking` | TINYINT(1) | Whether keyword is already being tracked |
| `is_dismissed` | TINYINT(1) | Whether user dismissed this suggestion |
| `discovered_at` | TIMESTAMP | When suggestion was first discovered |

**Constraints:**
- Foreign Key: `site_id → sites.id` ON DELETE CASCADE
- Unique: `(site_id, keyword)` - prevents duplicate suggestions per site

**Indexes:**
- `idx_suggestions_site_id` on `site_id`
- `idx_suggestions_keyword` on `keyword`
- `idx_suggestions_opportunity` on `opportunity_score DESC`

### Dismissed Suggestions Table
Tracks keywords the user has explicitly dismissed from suggestions, preventing rediscovery.

```sql
CREATE TABLE dismissed_suggestions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id |
| `keyword` | VARCHAR(255) | The dismissed keyword |
| `dismissed_at` | TIMESTAMP | When user dismissed the suggestion |

**Constraints:**
- Foreign Key: `site_id → sites.id` ON DELETE CASCADE

**Indexes:**
- `idx_dismissed_site` on `site_id`

### Model Relationships

**KeywordSuggestion Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `site()` | belongsTo | The site this suggestion belongs to |

**DismissedSuggestion Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `site()` | belongsTo | The site this dismissed keyword belongs to |

### Production Status
- **Deployed**: 2026-02-27 (migration batch 13)
- **Migration**: `2026_02_26_230500_create_keyword_suggestions_tables.php`
- **Verified**: Health check passing, 26/26 tests passing
- **Status**: Complete

### Dependencies
- TASK-1-1 (Sites table)
- FEAT-001 (Keyword Rank Tracking)

### Related Tasks
- TASK-5-2: KeywordSuggestion model and repository
- TASK-5-3: Opportunity scorer utility
- TASK-5-4: Google Suggest fetcher
- TASK-5-5: Question keyword generator
- TASK-5-6: Suggestion aggregation service

---
## Database Schema: Projects Table (TASK-8-1)

### Projects Table
Stores project information for multi-site data organization. Each project belongs to a user and can contain keywords, competitors, and reports.

```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    account_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    notes TEXT NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_archived (archived_at)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | FK to users.id (nullable for legacy support) |
| `account_id` | BIGINT UNSIGNED | FK to accounts.id (nullable) |
| `name` | VARCHAR(255) | Project name |
| `domain` | VARCHAR(500) | Project domain URL (nullable) |
| `notes` | TEXT | User notes about project (nullable) |
| `is_default` | BOOLEAN | Whether this is user's default project |
| `archived_at` | TIMESTAMP | Soft delete/archive timestamp (nullable) |

**Constraints:**
- Foreign Key: `user_id → users.id` ON DELETE CASCADE
- Foreign Key: `account_id → accounts.id` ON DELETE SET NULL

**Indexes:**
- `idx_projects_user_id` on `user_id`
- `idx_projects_archived` on `archived_at`

**Model:** `App\Models\Project`

### Model Methods

**Relationships:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `user()` | belongsTo | User who owns the project |
| `keywords()` | hasMany | Keywords in this project |
| `competitors()` | hasMany | Competitors in this project |
| `reports()` | hasMany | Reports in this project |

**Scopes:**
| Scope | Description |
|-------|-------------|
| `active()` | Returns non-archived projects |
| `archived()` | Returns archived projects |
| `default()` | Returns default projects |

**Methods:**
| Method | Description |
|--------|-------------|
| `archive()` | Sets `archived_at` to current timestamp |
| `unarchive()` | Clears `archived_at` |
| `isArchived()` | Returns true if project is archived |

**Factory States:**
- `default()` - Sets `is_default` to true
- `archived()` - Sets `archived_at` to a past timestamp
- `forUser($userId)` - Sets specific user_id
- `withNotes($notes)` - Sets custom notes

### Production Status
- **Deployed**: 2026-02-26 (commit via GitHub Actions)
- **Migration**: `2026_02_26_175543_add_user_id_notes_is_default_archived_at_to_projects_table`
- **Verified**: Health check passing, schema verified
- **Tests**: 38 tests, 73 assertions passing

### Dependencies
- User authentication system (existing)
- Accounts table (existing)

### Related Tasks
- TASK-8-2: Add project_id to existing tables
- TASK-8-3: Data migration for existing records
- TASK-8-4: SetProjectContext middleware
- TASK-8-5: ProjectScope global scope
- TASK-8-6: ProjectPolicy authorization
- TASK-8-7: Project CRUD endpoints
- TASK-8-8: Frontend project components
- TASK-8-9: Unit tests
- TASK-8-10: Integration tests

---
## Project Context Middleware (TASK-8-4)

### Service Description
Middleware that injects the current project's context into the application at runtime. Ensures all subsequent queries and operations are scoped to the active project, enabling multi-site data isolation.

### Core Functionalities

**SetProjectContext Middleware:**
| Method | Description |
|--------|-------------|
| `handle(Request, Closure)` | Extract project ID and bind project to app context |

### Context Extraction
The middleware extracts project ID from two sources (in order of precedence):

1. **HTTP Header:** `X-Project-ID` (takes precedence)
2. **Session:** `current_project_id`

```php
$projectId = $request->header('X-Project-ID') 
             ?? session('current_project_id');
```

### Project Binding
When a valid project ID is found:
1. Fetches project from database where `user_id === auth()->id()`
2. Binds project to application container: `app()->instance('current_project', $project)`
3. Makes project available via `app('current_project')` throughout request lifecycle

### Authorization Enforcement
| Check | Behavior |
|-------|----------|
| User owns project | Project bound to context |
| Cross-user project | Project not bound (silently ignored) |
| Invalid project ID | Request continues without project context |
| Unauthenticated | Request continues without project context |

### Security Implementation
| Measure | Implementation |
|---------|----------------|
| SQL Injection Prevention | `filter_var($projectId, FILTER_VALIDATE_INT)` |
| IDOR Prevention | Ownership check: `user_id === auth()->id()` |
| Graceful Failure | No exceptions thrown for invalid/missing IDs |

### Middleware Registration
Registered in `bootstrap/app.php` for both middleware groups:

```php
->web()
    ->middleware([/* ... */, \App\Http\Middleware\SetProjectContext::class])
->api()
    ->middleware([/* ... */, \App\Http\Middleware\SetProjectContext::class])
```

**Alias:** `project.context` (for route-level usage)

### Usage Examples

**API Request with Header:**
```http
GET /api/v1/keywords HTTP/1.1
Host: api.example.com
Authorization: Bearer <token>
X-Project-ID: 42
```

**Session-based Project:**
```php
// Set current project in session
session(['current_project_id' => $project->id]);

// Middleware automatically binds project
app('current_project'); // Returns Project model or null
```

### Technical Implementation
- Middleware: `backend/app/Http/Middleware/SetProjectContext.php`
- Registration: `bootstrap/app.php`
- Dependencies: `Project` model, `auth()` helper

### Test Coverage
| Test File | Tests | Status |
|-----------|-------|--------|
| `SetProjectContextTest.php` (Unit) | 33 | ✅ PASS |
| `SetProjectContextIntegrationTest.php` | 10 | ✅ PASS |
| `ProjectContextSecurityTest.php` | 18 | ✅ PASS |
| **Total** | **61** | ✅ **ALL PASS** |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Header Extraction (HE) | 6 | X-Project-ID header handling |
| Session Extraction (SE) | 5 | Session-based project context |
| Authorization (AU) | 8 | Cross-user access prevention |
| Security (SC) | 12 | SQL injection, IDOR prevention |
| Edge Cases (EC) | 10 | Invalid inputs, null handling |

### Production Status
- **Deployed**: 2026-02-26 (commit 718ebc6 via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 61/61 passing
- **Status**: Complete

### Skipped Tests (Dependencies)
- Tests requiring `/api/test-endpoint` route (to be created)
- Tests requiring `project_id` column on `keywords` table (TASK-8-2)
- Tests requiring `/dashboard` route (frontend task)

### Dependencies
- TASK-8-1 (Projects Table)
- Authentication middleware (existing)

### Related Tasks
- TASK-8-2: Add project_id to existing tables
- TASK-8-5: ProjectScope global scope
- TASK-8-6: ProjectPolicy authorization

---
## GSC Connection Controller API (TASK-12-7)

### Service Description
REST API controller for managing Google Search Console OAuth connections. Provides endpoints for initiating OAuth flow, disconnecting properties, and listing connected GSC properties. Core functionality for the GSC integration feature (FEAT-012).

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/gsc/connect` | Initiate OAuth flow with authorization code |
| DELETE | `/api/gsc/disconnect/{propertyId}` | Remove connection and revoke tokens |
| GET | `/api/gsc/properties` | List all connected GSC properties for user |

### Connect Endpoint
`POST /api/gsc/connect`

**Description:** Initiates the OAuth flow by exchanging an authorization code for access and refresh tokens. Creates a new GSC connection record for the authenticated user.

**Request Body:**
```json
{
  "authorization_code": "4/0AeanS0..."
}
```

**Response (201):**
```json
{
  "success": true,
  "property_url": "https://example.com"
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 400 | Invalid authorization code |
| 409 | Property already connected |

### Disconnect Endpoint
`DELETE /api/gsc/disconnect/{propertyId}`

**Description:** Removes a GSC connection and revokes the associated OAuth tokens. Users can only disconnect their own connections.

**Response (200):**
```json
{
  "success": true
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 403 | Connection owned by another user |
| 404 | Connection not found |

### List Properties Endpoint
`GET /api/gsc/properties`

**Description:** Returns all GSC properties connected by the authenticated user.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "property_url": "https://example.com",
      "site_url": "example.com",
      "connected_at": "2026-02-27T10:00:00Z"
    }
  ]
}
```

### Authorization
- All endpoints require authentication (Bearer token)
- Users can only access their own connections (user isolation)
- Returns 401 for unauthenticated requests
- Returns 403 for unauthorized access attempts

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| Authentication | `auth` middleware on all endpoints |
| IDOR Protection | Ownership check on all operations |
| Token Encryption | Tokens encrypted at rest |
| Token Exposure | Tokens hidden from JSON responses |
| SQL Injection | Eloquent ORM with parameterized queries |
| CSRF Protection | Stateless API with token auth |
| OAuth State | State parameter validation in OAuth flow |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/GSCConnectionController.php`
- Model: `backend/app/Models/GSCConnection.php`
- Routes: Defined in `backend/routes/api.php`

### Model Properties
| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `user_id` | int | FK to users.id |
| `site_url` | string | Site URL from GSC |
| `property_url` | string | GSC property URL |
| `access_token` | string | Encrypted OAuth access token |
| `refresh_token` | string | Encrypted OAuth refresh token |
| `token_expires_at` | timestamp | Token expiration time |

### Model Relationships
| Method | Relationship | Description |
|--------|--------------|-------------|
| `user()` | belongsTo | User who owns this connection |

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| GSCApiEndpointsTest | 11 | 13 |
| GSCSecurityTest | 14 | 37 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Connect endpoint | 3 | OAuth flow initiation |
| Disconnect endpoint | 3 | Connection removal |
| Properties endpoint | 3 | Listing connections |
| Authorization | 4 | 401/403 responses |
| Security | 14 | Token handling, ownership, injection |

### Production Status
- **Deployed**: 2026-02-27 (commit 0fd81f7)
- **Health Check**: Passing
- **Tests**: 25/25 passing (50 assertions)
- **Status**: Complete

### Dependencies
- TASK-12-4 (OAuth service)
- Google OAuth 2.0 integration

### Related Tasks
- TASK-12-4: OAuth service
- TASK-12-5: GSC Data Sync
- TASK-12-8: GSC Data Controller

---
## GSC Data Controller API (TASK-12-8)

### Service Description
REST API controller for exposing Google Search Console metrics and opportunity keywords. Provides endpoints for retrieving GSC data with filtering and pagination, plus opportunity keyword identification based on CTR gaps.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects/{projectId}/gsc` | Retrieve GSC data with date filters |
| GET | `/api/v1/projects/{projectId}/gsc/opportunities` | List opportunity keywords (CTR gap analysis) |

### GSC Data Endpoint
`GET /api/v1/projects/{projectId}/gsc`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `from` | date | 30 days ago | Start date filter (YYYY-MM-DD) |
| `to` | date | today | End date filter (YYYY-MM-DD) |
| `page` | int | 1 | Page number |
| `perPage` | int | 15 | Results per page (max: 100) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "seo tools",
      "page": "https://example.com/tools",
      "clicks": 150,
      "impressions": 3000,
      "ctr": 5.0,
      "avg_position": 3.2,
      "data_date": "2026-02-27"
    }
  ],
  "meta": {
    "total": 45,
    "page": 1,
    "perPage": 15
  }
}
```

### Opportunity Keywords Endpoint
`GET /api/v1/projects/{projectId}/gsc/opportunities`

Returns keywords with high impressions but lower-than-expected CTR, indicating optimization potential.

**Algorithm:**
- Calculates expected CTR based on average position (industry benchmarks)
- Identifies queries where actual CTR < expected CTR
- Scores by potential missed clicks: `(expected_ctr - actual_ctr) * impressions`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "query": "keyword research tool",
      "clicks": 50,
      "impressions": 800,
      "ctr": 6.25,
      "avg_position": 4.5,
      "expected_ctr": 8.5,
      "opportunity_score": 18.0
    }
  ]
}
```

### Authorization
- All endpoints require authentication (Bearer token)
- User must have project access via `ProjectPolicy`
- Returns 401 for unauthenticated requests
- Returns 403 for unauthorized project access

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/GSCDataController.php`
- Methods: `index()`, `opportunities()`
- Uses: `scopeOpportunities()` for CTR gap heuristic
- Routes: Defined in `backend/routes/api.php` under `/api/v1/projects/{projectId}/gsc`

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| GSCApiEndpointsTest | 11 | 13 |
| GSCSecurityTest | 14 | 37 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Data retrieval | 4 | Pagination, date filters |
| Opportunities | 3 | CTR gap calculation |
| Authorization | 4 | 401/403 responses |
| Security | 14 | SQL injection, XSS, path traversal |

### Production Status
- **Deployed**: 2026-02-23 (commit 2d3d903)
- **Routing Fix**: Added `.htaccess` for `/api/*` rewrite
- **Health Check**: Passing
- **Routes Verified**: 200 OK (health), 401 (protected endpoints)
- **Status**: Complete

### Security Notes
- SQL injection protection: Eloquent ORM with parameterized queries
- XSS protection: Input validation and output escaping
- Path traversal: Rejected at routing layer (404)
- Invalid project IDs: Rejected at auth layer (401)

### Dependencies
- TASK-12-5: Data sync service populates GSC data
- TASK-12-6: Query matching links keywords to GSC data

---
## Unit Tests: Projects (TASK-8-9)

### Test Coverage Summary
Unit tests for the Project model, policies, and relationships. Tests ensure isolated component validation and authorization logic.

### Test Files

| Test File | Tests | Assertions | Description |
|-----------|-------|------------|-------------|
| `ProjectValidationTest.php` | 21 | 21 | Model field validation tests |
| `ProjectRelationshipTest.php` | 23 | 23 | Relationship and scope tests |
| `ProjectTest.php` | 13 | - | Core model functionality |
| `ProjectPolicyTest.php` | 25 | - | Authorization policy tests |
| `ProjectAuthorizationTest.php` | 21 | - | Feature-level authorization |
| `ProjectsTableConstraintsTest.php` | 13 | - | Database constraint validation |

**Total: ~116 tests across all test files**

### ProjectValidationTest Coverage
Tests for model-level field validation:

| Category | Tests | Description |
|----------|-------|-------------|
| Name Validation | 5 | Required, empty, length constraints |
| Domain Validation | 3 | Null, valid format, length |
| User ID Validation | 2 | Null handling, FK constraint |
| Behavioral Fields | 4 | Notes, is_default, archived_at |
| Edge Cases | 7 | Special characters, unicode, whitespace |

### ProjectRelationshipTest Coverage
Tests for Eloquent relationships and model scopes:

| Category | Tests | Description |
|----------|-------|-------------|
| Direct Relationships | 4 | User, Account, GSCData, GSCConnections |
| HasMany Relationships | 4 | Keywords, Competitors, Reports, Members |
| BelongsToMany | 2 | Users through members pivot |
| Cascade Delete | 3 | Deletion behavior with related records |
| Model Scopes | 4 | Active, Archived, Default scopes |
| Instance Methods | 6 | Archive/unarchive/isArchived methods |

### ProjectPolicyTest Coverage
Authorization tests for project access control:

| Policy Method | Tests | Scenarios |
|---------------|-------|-----------|
| `view` | 5 | Owner, non-owner, admin, guest, archived |
| `update` | 5 | Owner, non-owner, admin, guest, archived |
| `delete` | 5 | Owner, non-owner, admin, guest, archived |
| `restore` | 5 | Owner, non-owner, admin, guest, archived |
| `forceDelete` | 5 | Owner, non-owner, admin, guest, archived |

### Pending Coverage
- **ProjectScope tests** - Waiting on TASK-8-5 implementation (global scope)

### Technical Implementation
- **Location:** `backend/tests/Unit/Models/Project/`
- **Factory:** `ProjectFactory` with states (default, archived, forUser, withNotes)
- **RefreshDatabase:** Each test runs in isolated transaction

### Dependencies
- TASK-8-1 (Projects Table schema)
- TASK-8-6 (Project Policies)

### Status
- **Completed:** 2026-02-26
- **Code Coverage:** ~95% for Project model
- **Pending:** ProjectScope tests (TASK-8-5 dependency)

### Keywords Table
```sql
CREATE TABLE keywords (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    site_id INTEGER NOT NULL,
    keyword TEXT NOT NULL,
    target_url TEXT,
    search_engine TEXT NOT NULL DEFAULT 'google',
    location TEXT NOT NULL DEFAULT 'us',
    device TEXT NOT NULL DEFAULT 'desktop',
    status TEXT NOT NULL DEFAULT 'active',
    last_checked_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | INTEGER | Primary key |
| `user_id` | INTEGER | Future multi-tenant support |
| `site_id` | INTEGER | FK to sites.id |
| `keyword` | TEXT | The search term to track |
| `target_url` | TEXT | User's target URL for this keyword |
| `search_engine` | TEXT | Search engine (default: 'google') |
| `location` | TEXT | Country code (default: 'us') |
| `device` | TEXT | Device type: 'desktop' or 'mobile' |
| `status` | TEXT | Enum: 'active', 'paused', 'deleted' |
| `last_checked_at` | DATETIME | Last rank check timestamp |
| `gsc_query_match` | VARCHAR(255) | Fuzzy match to GSC queries (nullable) |

**Constraints:**
- Foreign Key: `site_id → sites.id`
- Unique: `(site_id, keyword, search_engine, location, device)` named `keywords_unique`

**Indexes:**
- `idx_keywords_site_id` on `site_id`
- `idx_keywords_status` on `status`
- `idx_keywords_last_checked` on `last_checked_at`
- `idx_keywords_gsc_query_match` on `(site_id, keyword)` for GSC integration

### Ranking History Table
```sql
CREATE TABLE ranking_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword_id INTEGER NOT NULL,
    position INTEGER,
    url_found TEXT,
    serp_features TEXT,
    checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    check_duration_ms INTEGER,
    error TEXT,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | INTEGER | Primary key |
| `keyword_id` | INTEGER | FK to keywords.id |
| `position` | INTEGER | Rank position (NULL = not in top 100) |
| `url_found` | TEXT | Actual ranking URL discovered |
| `serp_features` | TEXT | JSON: featured snippet, local pack, etc. |
| `checked_at` | DATETIME | Check timestamp |
| `check_duration_ms` | INTEGER | Performance tracking |
| `error` | TEXT | Error message if check failed |

**Constraints:**
- Foreign Key: `keyword_id → keywords.id`

**Indexes:**
- `idx_ranking_keyword_id` on `keyword_id`
- `idx_ranking_checked_at` on `checked_at`
- `idx_ranking_keyword_date` on `(keyword_id, checked_at)`

### Sites Table (Reference)
```sql
CREATE TABLE sites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url TEXT NOT NULL UNIQUE,
    name TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---
## Database Schema: Competitors Tables (TASK-3-1)

### Competitors Table
Stores competitor domains for rank comparison tracking.

```sql
CREATE TABLE competitors (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    display_name VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY competitors_user_domain_unique (user_id, domain)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | FK to users.id |
| `domain` | VARCHAR(255) | Competitor domain (e.g., 'competitor.com') |
| `display_name` | VARCHAR(255) | Friendly name for display (nullable) |
| `notes` | TEXT | User notes about competitor (nullable) |
| `deleted_at` | TIMESTAMP | Soft delete timestamp (nullable) |

**Constraints:**
- Foreign Key: `user_id → users.id` ON DELETE CASCADE
- Unique: `(user_id, domain)` - prevents duplicate competitors per user

**Indexes:**
- `idx_competitors_user_id` on `user_id`
- `idx_competitors_domain` on `domain`

**Model:** `App\Models\Competitor` (uses SoftDeletes, has User relationship)

### Competitor Rankings Table
Stores historical ranking positions for competitor domains on tracked keywords.

```sql
CREATE TABLE competitor_rankings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    competitor_id BIGINT UNSIGNED NOT NULL,
    keyword_id BIGINT UNSIGNED NOT NULL,
    position INT UNSIGNED,
    url VARCHAR(2083),
    checked_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
    UNIQUE KEY competitor_rankings_unique (competitor_id, keyword_id, checked_at)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `competitor_id` | BIGINT UNSIGNED | FK to competitors.id |
| `keyword_id` | BIGINT UNSIGNED | FK to keywords.id |
| `position` | INT UNSIGNED | Rank position (NULL = not in top 100) |
| `url` | VARCHAR(2083) | URL found at that position (nullable) |
| `checked_at` | TIMESTAMP | When the ranking was checked |

**Constraints:**
- Foreign Key: `competitor_id → competitors.id` ON DELETE CASCADE
- Foreign Key: `keyword_id → keywords.id` ON DELETE CASCADE
- Unique: `(competitor_id, keyword_id, checked_at)` - one ranking per competitor/keyword/date

**Indexes:**
- `idx_competitor_rankings_competitor_id` on `competitor_id`
- `idx_competitor_rankings_keyword_id` on `keyword_id`
- `idx_competitor_rankings_checked_at` on `checked_at`

**Model:** `App\Models\CompetitorRanking` (belongs to Competitor, belongs to Keyword)

---
## Database Schema: Reports Tables (TASK-4-1)

### Reports Table
Stores generated report metadata and file references.

```sql
CREATE TABLE reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    format VARCHAR(20) NOT NULL DEFAULT 'pdf',
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    compare_range_start DATE,
    compare_range_end DATE,
    sections JSON,
    generated_at TIMESTAMP NULL,
    file_path VARCHAR(500),
    file_size INT UNSIGNED,
    error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id |
| `name` | VARCHAR(255) | Report name/title |
| `type` | VARCHAR(50) | Report type: 'daily', 'weekly', 'monthly', 'custom', 'one-time' |
| `format` | VARCHAR(20) | Export format: 'pdf', 'csv' |
| `status` | VARCHAR(50) | Status: 'pending', 'generating', 'completed', 'failed' |
| `date_range_start` | DATE | Report period start |
| `date_range_end` | DATE | Report period end |
| `compare_range_start` | DATE | Comparison period start (optional) |
| `compare_range_end` | DATE | Comparison period end (optional) |
| `sections` | JSON | Array of included section names |
| `generated_at` | TIMESTAMP | When report was generated |
| `file_path` | VARCHAR(500) | Path to generated file |
| `file_size` | INT UNSIGNED | File size in bytes |
| `error` | TEXT | Error message if generation failed |

**Indexes:**
- `idx_reports_site_id` on `site_id`
- `idx_reports_status` on `status`
- `idx_reports_generated_at` on `generated_at`

### Report Templates Table
Stores reusable report configurations.

```sql
CREATE TABLE report_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    sections JSON NOT NULL,
    branding JSON,
    cover_page_text TEXT,
    executive_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id (NULL = global template) |
| `name` | VARCHAR(255) | Template name |
| `is_default` | TINYINT(1) | Whether this is the default template for the site |
| `sections` | JSON | Array of section names to include |
| `branding` | JSON | Branding config: {logo_url, primary_color, font} |
| `cover_page_text` | TEXT | Custom text for cover page |
| `executive_summary` | TEXT | Default executive summary text |

**Indexes:**
- `idx_report_templates_site_id` on `site_id`
- `idx_report_templates_is_default` on `is_default`

### Scheduled Reports Table
Stores automated report schedule configurations.

```sql
CREATE TABLE scheduled_reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    format VARCHAR(20) NOT NULL DEFAULT 'pdf',
    recipients JSON NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES report_templates(id)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id |
| `template_id` | BIGINT UNSIGNED | FK to report_templates.id |
| `name` | VARCHAR(255) | Schedule name |
| `frequency` | VARCHAR(50) | Schedule: 'daily', 'weekly', 'biweekly', 'monthly' |
| `format` | VARCHAR(20) | Export format: 'pdf', 'csv' |
| `recipients` | JSON | Array of recipient email addresses |
| `enabled` | TINYINT(1) | Whether schedule is active |
| `last_run` | TIMESTAMP | Last execution time |
| `next_run` | TIMESTAMP | Next scheduled execution |

**Indexes:**
- `idx_scheduled_reports_site_id` on `site_id`
- `idx_scheduled_reports_next_run` on `next_run`
- `idx_scheduled_reports_enabled` on `enabled`

### Report Branding Table
Stores per-site branding configuration for white-label reports.

```sql
CREATE TABLE report_branding (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL UNIQUE,
    logo_url VARCHAR(500),
    logo_file VARCHAR(500),
    primary_color VARCHAR(20) DEFAULT '#2563eb',
    secondary_color VARCHAR(20) DEFAULT '#64748b',
    font_family VARCHAR(100) DEFAULT 'Inter',
    company_name VARCHAR(255),
    company_website VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id (unique - one branding per site) |
| `logo_url` | VARCHAR(500) | URL to logo image |
| `logo_file` | VARCHAR(500) | Local file path if uploaded |
| `primary_color` | VARCHAR(20) | Primary brand color (hex) |
| `secondary_color` | VARCHAR(20) | Secondary brand color (hex) |
| `font_family` | VARCHAR(100) | Font family for PDF |
| `company_name` | VARCHAR(255) | Company name for reports |
| `company_website` | VARCHAR(500) | Company website URL |

**Indexes:**
- `idx_report_branding_site_id` on `site_id` (unique)

---
## Report Generation API (TASK-4-8)

### Endpoint:
`POST /api/v1/sites/:siteId/reports/generate`

### Description:
Triggers the generation of site analysis reports (PDF/CSV formats). For MVP, uses synchronous generation with immediate report creation. Supports status tracking and comprehensive error handling.

### Request:
- **Method**: POST
- **Endpoint**: /api/v1/sites/{siteId}/reports/generate
- **Headers:**
  - Authorization: `Bearer <token>`
- **Request Body:**
  ```json
  {
    "name": "Weekly SEO Report",
    "type": "weekly",
    "format": "pdf",
    "dateRange": {
      "start": "2026-02-13",
      "end": "2026-02-20"
    },
    "compareRange": {
      "start": "2026-02-06",
      "end": "2026-02-13"
    },
    "sections": ["summary", "keywords", "changes", "trends"]
  }
  ```
  - `name`: (required) Report title.
  - `type`: (required) Type of report (`daily`, `weekly`, `monthly`, `custom`, `one-time`).
  - `format`: (required) Format (`pdf`, `csv`).
  - `dateRange`: (required) Object with `start` and `end` ISO dates.
  - `compareRange`: Optional comparison timeframe object.
  - `sections`: Optional array of section names (valid: `summary`, `keywords`, `changes`, `top-performers`, `needs-attention`, `trends`).

### Response:
- **Success (200 for MVP synchronous):**
  ```json
  {
    "success": true,
    "data": {
      "reportId": 1,
      "status": "completed",
      "downloadUrl": "/api/v1/reports/1/download"
    }
  }
  ```

- **Validation Error (422):**
  ```json
  {
    "message": "The name field is required.",
    "errors": {
      "name": ["The name field is required."]
    }
  }
  ```

- **Error (4xx/5xx):**
  ```json
  {
    "success": false,
    "message": "Report generation failed"
  }
  ```

### MVP Implementation Notes:
- **Synchronous Generation**: Reports are generated immediately during the request (not queued).
- **File Storage**: Generated files stored in `storage/reports/` directory.
- **Error Handling**: Failed generation sets report status to `failed` with error message.
- **Integration**: Uses `ReportDataAggregator`, `CsvReportExporter`, `PdfReportExporter`.

### Test Coverage:
| Test ID | Scenario | Result |
|---------|----------|--------|
| 8.1 | Generate report (PDF) | PASS |
| 8.2 | Generate report (CSV) | PASS |
| 8.3 | Missing required fields | PASS (422) |
| 8.4 | Invalid date range | PASS (422) |
| 8.5 | Sections validation | PASS (422) |
| 8.6 | Status set to generating | PASS |
| 8.7 | Exporter failure handling | PASS |

**Test File:** `backend/tests/Feature/Api/V1/ReportControllerTest.php`

### Production Status:
- **Deployed**: 2026-02-24 (commit 852b975)
- **Verified**: Route active, health check passing
- **Status**: Complete

### Dependencies:
- TASK-4-2 (Report model)
- TASK-4-4 (CSV Exporter)
- TASK-4-5 (PDF Exporter)
- TASK-4-6 (Data Aggregator)

---
## PDF Report Exporter (TASK-4-5)

### Service Description
Generates professional PDF reports from SEO data using native PHP PDF generation (no external dependencies). Supports multi-page reports with cover page, executive summary, keyword rankings, position changes, and trend charts.

### Core Functionalities

**PdfReportExporter Class:**
- `generate(Report $report, array $data): string` - Returns PDF content as string
- Configurable sections: cover, summary, keywords, changes, trends
- A4 page size (595x842 pts) with proper margins
- Automatic page numbering

**Report Sections:**
| Section | Method | Description |
|---------|--------|-------------|
| Cover Page | `renderCoverPage()` | Logo placeholder, title, date range |
| Executive Summary | `renderSummarySection()` | Key metrics overview |
| Keyword Rankings | `renderKeywordsSection()` | Full keyword table |
| Position Changes | `renderChangesSection()` | Highlights position changes |
| Trend Charts | `renderTrendsSection()` | Position history visualization |

**PDF Features:**
- A4 page size (595x842 points)
- Proper margins (40pt sides, 50pt top/bottom)
- Page numbering on all pages
- UTF-8 support for special characters
- In-memory generation (no temp file exposure)

### Technical Implementation
- Class: `backend/app/Exporters/PdfReportExporter.php`
- Uses native PHP PDF generation (no external dependencies)
- Returns string (PDF binary content)

### API Integration
- Used by `ReportController::download()` for PDF format
- Triggered via `GET /api/v1/reports/{id}/download` with `format=pdf`

### Unit Tests
18 tests, 46 assertions covering:
- Minimal data PDF generation
- Cover page rendering (title, date range, logo placeholder)
- Executive Summary section
- Position Changes section
- Trend Charts section
- Keywords table rendering
- A4 page size verification
- Page numbering
- Special character escaping
- Large dataset handling
- Empty section handling
- Missing logo/date handling

### Security Notes
- PDF special characters properly escaped (no injection vectors)
- No external dependencies in PDF generation
- In-memory generation (no temp file exposure)
- File storage via Laravel Storage (secured)

### Dependencies
- TASK-4-2 (Report model)
- TASK-4-6 (Report data aggregation)

### Deployment
- Deployed: 2026-02-24 via GitHub Actions
- Production verified: 2026-02-25

---
## Database Schema: SEO Site Audits (FEAT-014)

### Audits Table
Stores metadata for SEO site audits. Uses `site_id` (not `project_id`) for consistency with existing schema.

```sql
CREATE TABLE audits (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    pages_crawled INT UNSIGNED DEFAULT 0,
    issues_found INT UNSIGNED DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id (NOT project_id) |
| `status` | ENUM | Audit status: `pending`, `running`, `completed`, `failed` |
| `pages_crawled` | INT UNSIGNED | Number of pages crawled during audit |
| `issues_found` | INT UNSIGNED | Total issues discovered |
| `started_at` | TIMESTAMP | When audit started |
| `completed_at` | TIMESTAMP | When audit finished |

**Constraints:**
- Foreign Key: `site_id → sites.id` ON DELETE CASCADE

**Indexes:**
- `idx_site_status` on `(site_id, status)`
- `idx_status` on `status`
- `idx_completed_at` on `completed_at`

### Issue Types Table
Stores issue type definitions for audits. Pre-populated with common SEO issue categories.

```sql
CREATE TABLE issue_types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    severity_default ENUM('critical', 'warning', 'notice') NOT NULL,
    recommendation_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `category` | VARCHAR(50) | Issue category (e.g., 'technical', 'content', 'performance') |
| `name` | VARCHAR(100) | Issue type name |
| `description` | TEXT | Detailed description of issue type |
| `severity_default` | ENUM | Default severity: `critical`, `warning`, `notice` |
| `recommendation_template` | TEXT | Template for fix recommendations |

### Audit Issues Table
Stores detailed issue records discovered during audits.

```sql
CREATE TABLE audit_issues (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    audit_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(2083) NOT NULL,
    issue_type_id BIGINT UNSIGNED NOT NULL,
    severity ENUM('critical', 'warning', 'notice') NOT NULL,
    description TEXT NOT NULL,
    recommendation TEXT,
    status ENUM('open', 'fixed', 'ignored') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_type_id) REFERENCES issue_types(id) ON DELETE CASCADE
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `audit_id` | BIGINT UNSIGNED | FK to audits.id |
| `url` | VARCHAR(2083) | URL where issue was found |
| `issue_type_id` | BIGINT UNSIGNED | FK to issue_types.id |
| `severity` | ENUM | Issue severity: `critical`, `warning`, `notice` |
| `description` | TEXT | Issue description |
| `recommendation` | TEXT | Specific fix recommendation |
| `status` | ENUM | Issue status: `open`, `fixed`, `ignored` |

**Constraints:**
- Foreign Key: `audit_id → audits.id` ON DELETE CASCADE
- Foreign Key: `issue_type_id → issue_types.id` ON DELETE CASCADE

**Indexes:**
- `idx_audit_severity` on `(audit_id, severity)`
- `idx_severity` on `severity`
- `idx_status` on `status`
- `idx_audit_status` on `(audit_id, status)`
- `idx_issue_type_id` on `issue_type_id`

---
## SERP Fetcher Interface and Mock Implementation (TASK-1-7)

### Service Description
Defines the SERP fetcher interface and provides a mock implementation for development and testing. This allows the application to develop without external API dependencies.

### Core Functionalities

**SERPFetcher Interface:**
- `fetch(keyword: string, options: SERPOptions): Promise<SERPResult>` - Fetch SERP results for a keyword
- Configurable search engine, location, device, and target URL options

**SERPResult Interface:**
| Field | Type | Description |
|-------|------|-------------|
| `keyword` | string | The searched keyword |
| `searchEngine` | string | Search engine used (google, bing) |
| `location` | string | Location code (us, uk, etc.) |
| `device` | string | Device type (desktop, mobile) |
| `results` | SERPEntry[] | Array of SERP results (100 entries) |
| `checkedAt` | Date | Timestamp of the check |
| `error` | string? | Error message if failed |

**SERPEntry Interface:**
| Field | Type | Description |
|-------|------|-------------|
| `position` | number | Rank position (1-100) |
| `url` | string | Result URL |
| `title` | string | Page title |
| `description` | string | Meta description |
| `isOurSite` | boolean | True if matches target URL |

**SERPOptions Interface:**
| Field | Type | Description |
|-------|------|-------------|
| `searchEngine` | string | 'google' or 'bing' |
| `location` | string | Country code |
| `device` | string | 'desktop' or 'mobile' |
| `targetUrl` | string? | URL to match in results |

**MockSERPFetcher Implementation:**
- Returns 100 simulated SERP results
- Randomized positions within realistic ranges
- Simulates network delay (100-500ms)
- Supports deterministic mode with seed for testing
- Can simulate errors for error handling tests
- Identifies target URL with `isOurSite` flag

### Technical Implementation
- Interface: `backend/app/Services/SERPFetcherInterface.php`
- Mock Implementation: `backend/app/Services/MockSERPFetcher.php`
- DTO: `backend/app/Services/SERPResultData.php`

### Unit Tests
17 test scenarios covering:
- Results shape validation
- Deterministic behavior with seed
- Error simulation
- Target URL matching
- Network delay simulation

### Dependencies
None (standalone interface for dependency injection).

### Migration Path
For production, implement real SERP fetchers:
- `HttpSERPFetcher` - For testing environments
- `GoogleCSEFetcher` - Google Custom Search API (free tier: 100/day)
- `ScraperSERPFetcher` - Self-hosted scraper (optional)

---
## Rank Checker Worker (TASK-1-11)

### Service Description
Background worker that processes rank check jobs from the queue, fetches SERP data, and stores ranking results.

### Core Functionalities

**Queue Processing:**
- Processes jobs from JobQueue (TASK-1-10)
- Configurable concurrency limit (default: 5 concurrent jobs)
- Handles both single keyword checks and batch site checks

**SERP Fetching:**
- Integrates with SERPFetcherInterface (mock by default, HttpSERPFetcher for testing)
- Captures SERP features (featured snippets, local packs, etc.)
- Records check duration for performance monitoring

**Ranking Storage:**
- Stores results in `ranking_history` table
- Records position, URL found, SERP features, check duration
- Updates keyword's `last_checked_at` timestamp

**Error Handling:**
- Graceful handling of fetcher errors
- Retry-aware (respects job maxAttempts from queue)
- Logs all check attempts and results

### Technical Implementation
- Worker: `backend/app/Workers/RankCheckerWorker.php`
- Service: `backend/app/Services/RankCheckerService.php`
- Interface: `backend/app/Services/SERPFetcherInterface.php`
- Implementations:
  - `MockSERPFetcher` (development)
  - `HttpSERPFetcher` (testing environment)
- Job: `backend/app/Jobs/CheckKeywordJob.php`
- DTO: `backend/app/Services/SERPResultData.php`

### Unit Tests
29 tests, 80 assertions covering:
- Single keyword rank checking
- Site batch rank checking
- Error handling and retry logic
- SERP features capture
- Duration tracking
- Mocked fetcher/queue integration

### Dependencies
- TASK-1-2 (Keyword Repository)
- TASK-1-3 (Ranking Repository)
- TASK-1-7 (SERP Fetcher)
- TASK-1-10 (Job Queue)

---
## Job Queue System (TASK-1-10)

### Service Description
SQLite-backed job queue for background rank checking operations.
- MVP implementation using SQLite for simplicity (production can migrate to Redis/BullMQ).
- Supports job prioritization, retry logic, and dead letter queue for failed jobs.

### Core Functionalities

**Job Operations:**
- `addJob(payload, priority, maxAttempts)` - Add job to queue with configurable priority and retry attempts
- `getJob(jobId)` - Retrieve job by ID from active queue or dead letter queue
- `updateJob(jobId, data)` - Update job status, attempts, error info
- `getPending(limit)` - Fetch pending jobs ordered by priority (high → normal → low)

**Job Properties (CheckJob DTO):**
- `id`, `type`, `payload`, `priority`, `status`, `attempts`, `maxAttempts`
- Timestamps: `createdAt`, `startedAt`, `completedAt`, `failedAt`
- `error` - Error message for failed jobs

**Job Statuses:** `pending`, `running`, `completed`, `failed`

**Priority Levels:** `high`, `normal`, `low`

**Retry Logic:** Configurable max attempts (default: 3), auto-retry on failure

**Dead Letter Queue:** Jobs exceeding max attempts are moved to `queue_dead_letters` table

**Auto-Cleanup:** Completed jobs older than 7 days are automatically removed

### Technical Implementation
- Service: `backend/app/Services/Queue/JobQueue.php`
- Interface: `backend/app/Services/Queue/JobQueueInterface.php`
- DTO: `backend/app/Services/Queue/CheckJob.php`
- Storage: `storage/app/queue.sqlite`
- Tables: `queue_jobs`, `queue_dead_letters`

### Unit Tests
4 tests, 23 assertions covering addJob, getJob, updateJob, getPending operations.

### Dependencies
None (standalone SQLite implementation).

### Migration Path
For production scale, migrate to BullMQ + Redis.

---
## Report Authorization Security (TASK-4-22)

### Security Issue
The report download endpoints had a critical authorization vulnerability where ANY authenticated user could download ANY report by knowing the report ID. This allowed unauthorized access to other users' SEO report data.

### Fix Implementation

**ReportPolicy** (`backend/app/Policies/ReportPolicy.php`)
- Policy class for clean authorization logic
- Methods: `viewAny`, `view`, `create`, `delete`, `download`
- All methods verify `$report->site->user_id === $user->id`

**Secured Routes:**
```
GET    /api/v1/sites/{siteId}/reports           - List reports (auth required)
POST   /api/v1/sites/{siteId}/reports/generate  - Generate report (auth required)
GET    /api/v1/reports/{reportId}               - View report (auth + ownership)
DELETE /api/v1/reports/{reportId}               - Delete report (auth + ownership)
GET    /api/v1/reports/{reportId}/download      - Download report (auth + ownership)
```

**Authorization Flow:**
1. **Unauthenticated request** → 401 Unauthorized
2. **Authenticated but wrong user** → 403 Forbidden
3. **Owner** → Full access as expected

**ReportController Authorization Checks:**
| Method | Authorization Logic |
|--------|---------------------|
| `index()` | Verify user owns the site |
| `show()` | Verify user owns the report's site |
| `destroy()` | Verify user owns the report's site |
| `generate()` | Verify user owns the site |
| `download()` | Verify user owns the report's site |

**Tests Added:**
- 11 new authorization tests in `ReportControllerTest`
- 5 tests for 401 Unauthorized (unauthenticated access)
- 5 tests for 403 Forbidden (accessing other user's resources)
- 1 test verifying owner can access their own reports
- Total: 29 ReportControllerTest tests, 79 assertions pass

### Dependencies
- TASK-4-7 through TASK-4-10 (Report API endpoints)

---
## Report Data Aggregation Service (TASK-4-6)

### Service Description
Aggregates ranking data for SEO reports, collecting keyword positions, calculating changes, identifying top performers and items needing attention, and generating trend data for charts.

### Core Functionalities

**ReportDataAggregator Class:**
- `aggregate(siteId, dateRange, compareRange)` - Main method to compile report data
- Supports flexible date range formats (object or array)
- Handles missing/empty data gracefully

**Data Aggregation:**
| Output Field | Description |
|--------------|-------------|
| `keywords` | All keywords with current/previous positions and changes |
| `top_performers` | Top 5 keywords with biggest position improvements |
| `needs_attention` | Top 5 keywords with biggest position drops |
| `trends` | Position history points for each keyword |
| `competitors` | Optional competitor summary (if ComparisonService available) |

**Position Change Calculation:**
- Negative change = position improvement (e.g., 10→5 = -5, improved)
- Positive change = position drop (e.g., 5→10 = +5, declined)
- Handles NULL positions (not ranked)

**Performance:**
- Efficiently queries large datasets with date range filtering
- Groups rankings by keyword for parallel processing
- Returns latest ranking per keyword per date range

### Technical Implementation
- Class: `backend/app/Services/ReportDataAggregator.php`
- Used by: PDF Report Exporter, CSV Report Exporter
- Integration: ReportController calls aggregator for data compilation

### Unit Tests
12 tests, 37 assertions covering:
- Valid date range aggregation
- Compare range calculations
- Top performers selection
- Needs attention selection
- Trend data aggregation
- Optional competitor data
- Empty dataset handling
- Large dataset performance
- Missing compare range
- Tie-breaking determinism
- Date range exclusion
- Multiple rankings per day (latest wins)

### Dependencies
- TASK-1-1 (Keywords model)
- TASK-1-3 (Ranking model/repository)
- Optional: ComparisonService (competitor data)

### Deployment
- Deployed: 2026-02-24 via GitHub Actions
- Production verified: 2026-02-25

---
## Rank Check Scheduler (TASK-1-12)

### Service Description
Automated scheduler that queues rank check jobs on a configurable interval. Enables hands-free daily rank tracking for all active keywords.

### Core Functionalities

**Automated Scheduling:**
- Runs on configurable interval (default: every 24 hours)
- Queues check jobs for all active keywords
- Respects `last_checked_at` - skips recently checked keywords
- Randomizes job timing within a configurable window to avoid API bursts

**Priority-Based Scheduling:**
- Keywords with recent user views get higher priority
- Uses `last_viewed_at` timestamp for priority calculation
- Ensures important keywords are checked first

**Manual Trigger:**
- Admin endpoint: `POST /api/v1/scheduler/rank-checks/run` (authentication required)
- Artisan command: `php artisan rank-checks:schedule`

**Logging:**
- Logs scheduler runs and job counts
- Tracks scheduling window and keyword selection

### Configuration

**Environment Variables:**
| Variable | Default | Description |
|----------|---------|-------------|
| `RANK_CHECK_INTERVAL_MINUTES` | `1440` | Time between scheduler runs (24 hours) |
| `RANK_CHECK_SPREAD_MINUTES` | `60` | Random window to spread job scheduling |

### Technical Implementation
- Service: `backend/app/Services/RankCheckScheduler.php`
- Controller: `backend/app/Http/Controllers/Api/V1/SchedulerController.php`
- Command: `backend/app/Console/Commands/ScheduleRankChecks.php`
- Route: `POST /api/v1/scheduler/rank-checks/run`

### Dependencies
- TASK-1-10 (Job Queue)
- TASK-1-11 (Rank Checker Worker)

### Unit Tests
2 tests, 6 assertions covering scheduler logic and queue scheduling.

---
## Database Schema: GSC Connections Table (TASK-12-1)

### GSC Connections Table
Stores OAuth tokens and property information for Google Search Console API access. Each connection represents a single GSC property linked to a user account.

```sql
CREATE TABLE gsc_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_url VARCHAR(500) NOT NULL,
    property_url VARCHAR(500) NOT NULL,
    access_token VARCHAR(2000) NOT NULL,
    refresh_token VARCHAR(2000) NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_property (user_id, property_url)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | FK to users.id |
| `site_url` | VARCHAR(500) | Site URL from GSC |
| `property_url` | VARCHAR(500) | GSC property URL |
| `access_token` | VARCHAR(2000) | Encrypted OAuth access token |
| `refresh_token` | VARCHAR(2000) | Encrypted OAuth refresh token |
| `token_expires_at` | TIMESTAMP | Token expiration time |
| `status` | ENUM | Connection status: `active`, `expired`, `revoked` |
| `last_synced_at` | TIMESTAMP | Last successful data sync time (nullable) |

**Constraints:**
- Foreign Key: `user_id → users.id` ON DELETE CASCADE
- Unique: `(user_id, property_url)` - prevents duplicate property connections

**Indexes:**
- `idx_user_property` unique index on `(user_id, property_url)`
- `idx_status` on `status`
- `idx_token_expires_at` on `token_expires_at`

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| Token Encryption | Laravel `encrypted` cast on access_token, refresh_token |
| Token Exposure | Tokens hidden from JSON serialization (`$hidden` array) |
| User Isolation | Foreign key cascade delete removes connections on user delete |
| Duplicate Prevention | Unique constraint on (user_id, property_url) |

### Model Relationships

**GSCConnection Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `user()` | belongsTo | User who owns this connection |

### Model Methods

**Status Helpers:**
| Method | Description |
|--------|-------------|
| `isActive()` | Returns true if status is 'active' |
| `markAsExpired()` | Sets status to 'expired' |
| `markAsRevoked()` | Sets status to 'revoked' |
| `markAsSynced()` | Sets last_synced_at to now |

**Token Helpers:**
| Method | Description |
|--------|-------------|
| `isTokenExpired()` | Returns true if token has expired |
| `tokenExpiresWithin($minutes)` | Returns true if token expires within given minutes |

**Query Scopes:**
| Scope | Description |
|-------|-------------|
| `active()` | Returns only active connections |
| `needsRefresh()` | Returns connections with tokens expiring soon |
| `readyForSync()` | Returns active connections ready for data sync |

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| CreateGSCConnectionsTableTest | 11 | 11 |
| GSCConnectionTest | 16 | 16 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Schema Validation | 11 | Table structure, columns, indexes, constraints |
| Model Relationships | 2 | User relationship |
| Token Management | 4 | Expiration helpers, update method |
| Status Management | 5 | Status checks, state transitions |
| Query Scopes | 3 | Active, needsRefresh, readyForSync |
| Security | 2 | Tokens encrypted, hidden from serialization |

### Production Status
- **Deployed**: 2026-02-21 (migration batch 3)
- **Migration**: `2026_02_21_190000_create_gsc_connections_table.php`
- **Verified**: Health check passing, 27 tests passing (64 assertions)
- **Status**: Complete

### Dependencies
- Existing users table
- Laravel encryption service

### Related Tasks
- TASK-12-4: OAuth Service
- TASK-12-5: GSC Data Sync
- TASK-12-7: GSC Connection Controller API
- TASK-12-8: GSC Data Controller API

---
## Database Schema: GSC Query Match Column (TASK-12-3)

### Schema Change
Added `gsc_query_match` column to the `keywords` table to support fuzzy matching between tracked keywords and Google Search Console query data.

### Migration
```sql
ALTER TABLE keywords ADD COLUMN gsc_query_match VARCHAR(255) NULL COMMENT 'Fuzzy match to GSC queries';
```

### New Field
| Field | Type | Description |
|-------|------|-------------|
| `gsc_query_match` | VARCHAR(255) | Fuzzy match to GSC queries (nullable) |

### Index
```sql
CREATE INDEX keywords_gsc_query_match_index ON keywords(gsc_query_match);
```

### Purpose
- Links tracked keywords to GSC query data
- Enables correlation between rank tracking and actual search performance
- Supports future GSC integration features (FEAT-012)

### Dependencies
- Existing `keywords` table (TASK-1-1)

### Deployment
- Deployed: 2026-02-22 via GitHub Actions
- Production verified: 2026-02-23

---
## Scheduled Reports API (TASK-4-11)

### Service Description
REST API endpoints for managing scheduled report configurations. Enables users to create, list, update, delete, pause, and resume automated report schedules.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/scheduled-reports` | List scheduled reports for a site |
| GET | `/api/v1/scheduled-reports/{scheduledId}` | Get scheduled report details |
| POST | `/api/v1/sites/{siteId}/scheduled-reports` | Create a new scheduled report |
| PUT | `/api/v1/scheduled-reports/{scheduledId}` | Update a scheduled report |
| DELETE | `/api/v1/scheduled-reports/{scheduledId}` | Delete a scheduled report |
| POST | `/api/v1/scheduled-reports/{scheduledId}/pause` | Pause a schedule |
| POST | `/api/v1/scheduled-reports/{scheduledId}/resume` | Resume a schedule |

### Create Scheduled Report
```http
POST /api/v1/sites/{siteId}/scheduled-reports
Content-Type: application/json

{
  "name": "Weekly Client Report",
  "templateId": 1,
  "frequency": "weekly",
  "format": "pdf",
  "recipients": ["client@example.com", "manager@example.com"]
}
```

**Request Validation:**
| Field | Rules |
|-------|-------|
| `name` | Required, string, max 255 chars |
| `templateId` | Optional, must exist in report_templates |
| `frequency` | Required, enum: `daily`, `weekly`, `biweekly`, `monthly` |
| `format` | Required, enum: `pdf`, `csv` |
| `recipients` | Required, array (min 1), each must be valid email |

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "siteId": 1,
    "templateId": 1,
    "name": "Weekly Client Report",
    "frequency": "weekly",
    "frequencyLabel": "Weekly",
    "format": "pdf",
    "recipients": ["client@example.com", "manager@example.com"],
    "enabled": true,
    "lastRun": null,
    "nextRun": "2026-03-02T09:00:00Z",
    "createdAt": "2026-02-26T16:00:00Z"
  }
}
```

### Pause/Resume Schedule
- **Pause:** Sets `enabled=false`, preserves `next_run` value
- **Resume:** Sets `enabled=true`, recalculates `next_run` based on current time and frequency

### Frequency Schedule Calculation
| Frequency | Next Run Logic |
|-----------|----------------|
| `daily` | Tomorrow at 09:00 |
| `weekly` | Next Monday at 09:00 |
| `biweekly` | 2 weeks from now at 09:00 |
| `monthly` | First day of next month at 09:00 |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/ScheduledReportController.php`
- Model: `backend/app/Models/ScheduledReport.php`
- Repository: `backend/app/Repositories/ScheduledReportRepository.php`
- Factory: `backend/database/factories/ScheduledReportFactory.php`

### Model Properties
| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `site_id` | int | FK to sites.id |
| `template_id` | int? | FK to report_templates.id (nullable) |
| `name` | string | Schedule name |
| `frequency` | string | Schedule frequency enum |
| `format` | string | Export format: `pdf` or `csv` |
| `recipients` | array | JSON array of email addresses |
| `enabled` | bool | Whether schedule is active |
| `last_run` | Carbon? | Last execution timestamp |
| `next_run` | Carbon? | Next scheduled execution |

### Security Notes
- Name field sanitized with `strip_tags()` to prevent XSS
- Email validation ensures valid recipient addresses
- Frequency values restricted to allowed enum values

### Dependencies
- TASK-4-1 (scheduled_reports table)
- TASK-4-3 (ReportTemplate model)

### Deployment
- Deployed: 2026-02-26
- Status: Post-MVP feature

---
## Scheduler Service for Scheduled Reports (TASK-4-12)

### Service Description
Background scheduler service that manages scheduled report generation. Runs via Laravel's task scheduling system, processes due reports, and updates execution timestamps.

### Core Functionalities

**ReportScheduler Service:**
- `start()` - Initializes all enabled scheduled reports
- `processDueReports()` - Runs at configured intervals to process pending reports
- Generates reports for each due scheduled report entry
- Updates `last_run` and `next_run` timestamps after processing
- Handles failures gracefully (logs errors, allows retry)
- Supports pause/resume via `ScheduledReport.enabled` flag

**Schedule Calculation:**
| Frequency | Cron Expression | Next Run Logic |
|-----------|-----------------|----------------|
| `daily` | `0 9 * * *` | 9 AM daily |
| `weekly` | `0 9 * * 1` | 9 AM every Monday |
| `biweekly` | `0 9 1,15 * *` | 9 AM on 1st and 15th |
| `monthly` | `0 9 1 * *` | 9 AM on 1st of month |

**Error Handling:**
- Logs all scheduling errors to Laravel logs
- Failed reports do not block other scheduled reports
- Retry logic via configurable max attempts
- Dead letter tracking for persistently failing schedules

### Technical Implementation
- Service: `backend/app/Services/ReportScheduler.php`
- Command: `backend/app/Console/Commands/ProcessScheduledReports.php`
- Kernel: Registered in `app/Console/Kernel.php`
- Queue: Optional queue worker integration for reliability

### Artisan Commands
```bash
# Process all due scheduled reports
php artisan reports:process-scheduled

# Start the scheduler (typically run via cron)
php artisan schedule:run
```

### Monitoring
- Logs scheduling attempts with timestamps
- Tracks `last_run` for each scheduled report
- Calculates `next_run` based on frequency
- Health check endpoint available for monitoring

### Dependencies
- TASK-4-8 (Report generation)
- TASK-4-11 (Scheduled Reports API)
- TASK-4-19 (Email delivery - optional)

### Unit Tests
Tests cover:
- Schedule initialization
- Due report processing
- Timestamp updates
- Error handling
- Pause/resume functionality

### Deployment
- Deployed: 2026-02-26
- Status: Post-MVP feature (Phase 4)

---
## Cron Job Integration for Scheduled Reports (TASK-4-13)

### Service Description
Server-side cron configuration for Laravel's task scheduler. Enables automatic execution of scheduled reports and cleanup jobs without manual intervention.

### Core Functionalities

**Laravel Scheduler Configuration:**
- Registered in `app/Console/Kernel.php`
- Runs every minute via system cron
- Processes due scheduled reports
- Cleans up old generated report files

**Cron Entry:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Commands:**
| Command | Schedule | Description |
|---------|----------|-------------|
| `reports:process-scheduled` | Every minute | Process all due scheduled reports |
| `reports:cleanup` | Daily at midnight | Remove reports older than retention period |

**Kernel.php Configuration:**
```php
protected function schedule(Schedule $schedule)
{
    // Process scheduled reports every minute
    $schedule->command('reports:process-scheduled')
        ->everyMinute()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/scheduler.log'));

    // Clean up old report files daily
    $schedule->command('reports:cleanup --days=30')
        ->daily()
        ->at('00:00')
        ->appendOutputTo(storage_path('logs/reports-cleanup.log'));
}
```

### Retention Policy

**Generated Report Files:**
- Default retention: 30 days
- Configurable via `REPORT_RETENTION_DAYS` environment variable
- Cleanup removes files from `storage/reports/` directory

**Database Records:**
- Completed reports older than 30 days are archived
- Failed reports retained for 7 days for debugging
- Scheduled report logs retained for 90 days

### Logging Configuration

**Scheduler Logs:**
- Location: `storage/logs/scheduler.log`
- Contains: Execution timestamps, report counts, errors
- Rotation: Daily, kept for 14 days

**Log Format:**
```
[2026-02-26 09:00:00] Processing scheduled reports...
[2026-02-26 09:00:01] Found 3 due reports
[2026-02-26 09:00:05] Report ID 123 generated successfully
[2026-02-26 09:00:08] Report ID 124 generated successfully
[2026-02-26 09:00:12] Report ID 125 failed: Email send error
[2026-02-26 09:00:12] Completed: 2 success, 1 failure
```

### Deployment Instructions

**Production Server Setup:**

1. **Add Cron Entry:**
   ```bash
   crontab -e
   # Add this line:
   * * * * * cd /var/www/seoindicator && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Verify Cron is Running:**
   ```bash
   # Check cron logs
   grep CRON /var/log/syslog

   # Test scheduler manually
   php artisan schedule:run -v
   ```

3. **Ensure Proper Permissions:**
   ```bash
   chmod -R 775 storage/
   chown -R www-data:www-data storage/
   ```

4. **Configure Environment Variables:**
   ```env
   REPORT_RETENTION_DAYS=30
   SCHEDULER_LOG_CHANNEL=daily
   ```

**Systemd Timer Alternative (Recommended for Production):**
```ini
# /etc/systemd/system/seoindicator-scheduler.service
[Unit]
Description=SEOIndicator Laravel Scheduler

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/seoindicator
ExecStart=/usr/bin/php artisan schedule:run

[Install]
WantedBy=multi-user.target
```

```ini
# /etc/systemd/system/seoindicator-scheduler.timer
[Unit]
Description=Run SEOIndicator scheduler every minute

[Timer]
OnCalendar=*:*:00
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
# Enable and start
systemctl enable seoindicator-scheduler.timer
systemctl start seoindicator-scheduler.timer
```

### Monitoring

**Health Check:**
- Endpoint: `GET /api/v1/health/scheduler`
- Returns: Last run timestamp, queue size, error count

**Alert Conditions:**
- Scheduler not running for >5 minutes
- More than 10 failed reports in last hour
- Queue backlog >100 pending reports

### Dependencies
- TASK-4-12 (Scheduler Service)
- TASK-4-8 (Report Generation)
- TASK-4-19 (Email Delivery - optional)

### Deployment
- Deployed: 2026-02-26
- Status: Post-MVP feature (Phase 4)

---
## Comparison Engine Service (TASK-3-5)

### Service Description
Core business logic for competitor comparison, providing dashboard data, keyword gap analysis, and historical comparison aggregation.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/rankings/comparison` | Comparison dashboard with summary and filtered results |
| GET | `/api/v1/competitors/{id}/rankings` | Competitor's rankings for user's keywords |
| GET | `/api/v1/competitors/{id}/gaps` | Keyword gaps (competitor ranks, user doesn't) |
| GET | `/api/v1/rankings/comparison/history` | Historical comparison for charting |

### Comparison Dashboard Endpoint
`GET /api/v1/rankings/comparison`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `filter` | string | `all` | Filter: `all`, `im-winning`, `theyre-winning`, `gap`, `both-rank` |
| `page` | int | 1 | Page number |
| `perPage` | int | 15 | Results per page |

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "totalKeywords": 100,
      "imWinning": 45,
      "theyreWinning": 30,
      "bothRank": 75,
      "onlyIRank": 10,
      "onlyTheyRank": 15
    },
    "results": [
      {
        "keyword": "seo tools",
        "userPosition": 5,
        "competitorPosition": 8,
        "positionDiff": -3,
        "status": "im-winning"
      }
    ],
    "meta": {
      "total": 100,
      "page": 1,
      "perPage": 15
    }
  }
}
```

### Status Determination Logic
| Status | Condition |
|--------|-----------|
| `im-winning` | User position < competitor position (or user ranks, competitor doesn't) |
| `theyre-winning` | Competitor position < user position (or competitor ranks, user doesn't) |
| `gap` | Competitor ranks in top 100, user doesn't rank at all |
| `both-rank` | Both user and competitor rank in top 100 |
| `only-i-rank` | Only user ranks in top 100 |

### Position Diff Calculation
- **Negative:** User is ahead (e.g., user at 3, competitor at 8 → diff = -5)
- **Positive:** Competitor is ahead (e.g., user at 8, competitor at 3 → diff = +5)
- **NULL:** One or both positions unavailable

### Competitor Rankings Endpoint
`GET /api/v1/competitors/{id}/rankings`

Returns all rankings for a specific competitor on the user's tracked keywords.

### Keyword Gaps Endpoint
`GET /api/v1/competitors/{id}/gaps`

Returns keywords where the competitor ranks but the user doesn't - useful for content gap analysis.

### Historical Comparison Endpoint
`GET /api/v1/rankings/comparison/history`

Returns time-series data for charting position changes over time.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `competitorId` | int | Competitor ID to compare against |
| `days` | int | Number of days to look back (default: 30) |

### Technical Implementation
- Service: `backend/app/Services/ComparisonService.php`
- Controller: `backend/app/Http/Controllers/Api/V1/ComparisonController.php`
- Repository: Uses repository pattern for data access

### Caching
- Comparison results cached for 5 minutes (TTL: 300s)
- Cache key includes user ID and filter parameters
- Cache invalidated on new ranking data

### Security Notes
- All endpoints protected by `auth` middleware
- User isolation enforced - users can only access their own competitors' data
- Attempts to access other users' competitors return 404

### Unit Tests
- 6 tests, 13 assertions in `ComparisonServiceTest`
- Covers: Summary calculation, position diff, status determination

### Feature Tests
- 9 tests, 19 assertions in `ComparisonControllerTest`
- Covers: Dashboard API, filter validation, pagination, competitor rankings, gaps, history, user isolation

### Dependencies
- TASK-3-1 (competitors and competitor_rankings tables)
- TASK-3-4 (competitor rankings collection)

### Deployment
- Deployed: 2026-02-22 via GitHub Actions
- Production verified: 2026-02-24

---
## Fuzzy Query Matching for GSC Data (TASK-12-6)

### Service Description
Implements fuzzy matching between tracked keywords and Google Search Console query data, enabling automatic association between `keywords` and `gsc_data` records.

### Core Functionalities

**Query Matching Algorithm:**
- Normalizes queries (lowercase, trim whitespace, strip special characters)
- Calculates similarity score using Levenshtein distance
- Auto-matches high-confidence pairs (>90% similarity by default)
- Configurable threshold for matching sensitivity

**Integration:**
- Auto-matching runs during GSC data sync (integrated in `GSCDataSync::storeData`)
- Manual override supported via `QueryMatcher::setManualMatch` method
- Stores confirmed matches in `gsc_query_match` column

### Technical Implementation
- Service: `backend/app/Services/QueryMatcher.php`
- Key Methods:
  - `findMatches(Keyword $keyword): array` - Find matching GSC queries for a keyword
  - `calculateSimilarity(string $a, string $b): float` - Compute similarity score
  - `autoMatch(Project $project): int` - Auto-match all keywords for a project
  - `setManualMatch(Keyword $keyword, string $query): void` - Manual match override

### Acceptance Criteria Status
| Criteria | Status |
|----------|--------|
| Fuzzy matching with configurable threshold | ✅ Complete |
| Auto-matching in GSCDataSync | ✅ Complete |
| UI for manual match adjustment | ⏳ Pending (backend ready) |
| Unit tests | ✅ Complete |

### Unit Tests
- 3 tests, 4 assertions in `QueryMatcherTest`
- Tests cover normalization, similarity calculation, auto-match logic

### Security Notes
- Uses Eloquent ORM (no SQL injection risk)
- Backend logic only (no XSS risk)
- Normalization sanitizes special characters

### Dependencies
- TASK-12-3 (`gsc_query_match` column)

### Deployment
- Deployed: 2026-02-23 via GitHub Actions
- Production verified: 2026-02-24

---
## Keywords API - SERP Feature Filtering (TASK-7-12)

### Feature Description
Extends the keywords list endpoint to support filtering by SERP feature presence. Users can now quickly identify which keywords trigger specific SERP features like featured snippets, local packs, or knowledge panels.

### API Endpoint
`GET /api/v1/sites/{siteId}/keywords`

### New Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `serpFeature` | string | null | Comma-separated list of SERP feature types to filter by |
| `present` | boolean | true | Whether to filter for presence (true) or absence (false) of features |

### Valid SERP Feature Types
- `featured_snippet`
- `local_pack`
- `knowledge_panel`
- `sitelinks`
- `reviews`
- `images`
- `videos`
- `related_questions`
- `shopping_results`
- `top_stories`

### Usage Examples

**Find keywords WITH featured snippets:**
```
GET /api/v1/sites/1/keywords?serpFeature=featured_snippet
```

**Find keywords WITHOUT local pack:**
```
GET /api/v1/sites/1/keywords?serpFeature=local_pack&present=false
```

**Find keywords with multiple SERP features (OR logic):**
```
GET /api/v1/sites/1/keywords?serpFeature=featured_snippet,local_pack
```

**Combine with other filters:**
```
GET /api/v1/sites/1/keywords?serpFeature=featured_snippet&status=active&page=1&perPage=20
```

### Response Structure
Each keyword in the response now includes a `serpFeatures` object:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "targetUrl": "https://example.com/seo",
      "searchEngine": "google",
      "location": "us",
      "device": "desktop",
      "status": "active",
      "currentPosition": 5,
      "previousPosition": 7,
      "positionChange": -2,
      "lastCheckedAt": "2026-02-26T12:00:00Z",
      "serpFeatures": {
        "featured_snippet": true,
        "local_pack": false,
        "knowledge_panel": false,
        "sitelinks": true,
        "reviews": false,
        "images": false,
        "videos": false,
        "related_questions": false,
        "shopping_results": false,
        "top_stories": false
      }
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "perPage": 20
  }
}
```

### Error Responses

**Invalid SERP Feature Type (400):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid SERP feature type: invalid_type. Valid types are: featured_snippet, local_pack, knowledge_panel, sitelinks, reviews, images, videos, related_questions, shopping_results, top_stories"
  }
}
```

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/KeywordController.php`
- New methods:
  - `getKeywordsFilteredBySerpFeature()` - Query builder for SERP feature filtering
  - `getSerpFeaturesByKeywords()` - Batch fetch SERP features for keywords
  - `formatKeywordWithFeatures()` - Format keyword with SERP features included
- Model: `backend/app/Models/SerpFeature.php` (FEATURE_TYPES constant)

### Unit Tests
12 new tests in `KeywordControllerTest.php`:
- `test_filter_by_single_serp_feature`
- `test_filter_by_serp_feature_present_false`
- `test_filter_by_multiple_serp_features`
- `test_filter_by_serp_feature_no_matches`
- `test_filter_by_serp_feature_with_status`
- `test_filter_by_serp_feature_with_pagination`
- `test_response_includes_serp_features`
- `test_invalid_serp_feature_type`
- `test_empty_serp_feature_parameter`
- `test_present_parameter_variations`
- `test_sql_injection_in_serp_feature`

### Security Notes
- Input validation prevents invalid feature types
- SQL injection protection via Eloquent ORM
- Feature types validated against whitelist (`SerpFeature::FEATURE_TYPES`)

### Dependencies
- TASK-7-11 (SERP Features tracking)

### Deployment
- Deployed: 2026-02-26
- Status: Implementation complete, tests passing

---
## Google Suggest Fetcher (TASK-5-4)

### Service Description
Fetcher for Google Suggest API to retrieve keyword suggestions. This is the primary FREE source for keyword discovery in MVP, providing autocomplete suggestions from Google's search data.

### Core Functionalities

**SuggestFetcherInterface:**
- `fetch(string $keyword): array` - Fetch suggestions for a keyword
- Returns array of suggestion strings
- Graceful error handling (returns empty array on failure)

**GoogleSuggestFetcher Implementation:**
- API endpoint: `http://suggestqueries.google.com/complete/search?client=firefox&q={keyword}`
- Parses JSON response (array format: `[originalKeyword, suggestions[]]`)
- In-memory caching for repeated requests (same session)
- Configurable timeout via constructor

### Error Handling
| Error Type | HTTP Code | Behavior |
|------------|-----------|----------|
| Rate Limit | 429 | Throw `RateLimitException` |
| Server Error | 5xx | Throw `FetchException` |
| Network Error | N/A | Throw `NetworkException` |
| 4xx (except 429) | 400-499 | Return empty array (no exception) |

### Response Format
Google's response format:
```json
["seo tools", ["seo tools free", "seo tools for beginners", "seo tools google", ...]]
```

Parsed result:
```php
["seo tools free", "seo tools for beginners", "seo tools google", ...]
```

### Technical Implementation
- Interface: `backend/app/Services/Fetchers/SuggestFetcherInterface.php`
- Implementation: `backend/app/Services/Fetchers/GoogleSuggestFetcher.php`
- Exceptions:
  - `FetchException` - General fetch errors
  - `NetworkException` - Connection issues
  - `RateLimitException` - HTTP 429 responses
  - `TimeoutException` - Request timeout

### Unit Tests
25 tests, 39 assertions covering:
- Successful fetch scenarios
- Empty/malformed response handling
- Error handling: rate limit (429), server error (5xx), network, timeout
- URL encoding and special characters
- Unicode handling
- Caching behavior
- Edge cases: empty keyword, long keyword, whitespace
- Security: XSS prevention, SQL injection, null byte injection

### Production Verification
| Test | Description | Result |
|------|-------------|--------|
| Basic fetch | 'seo tools' | PASS (10 suggestions) |
| Empty keyword | Empty string | PASS (empty array) |
| Unicode | 'café' | PASS (0 suggestions) |
| Special chars | 'seo-tools' | PASS (10 suggestions) |
| Caching | Same keyword | PASS (cached) |
| XSS attempt | `<script>` | PASS (safe) |
| SQL injection | `' OR 1=1` | PASS (safe) |

### Security Notes
- URL encoding with `rawurlencode()` for RFC 3986 compliance
- Non-string values filtered from suggestions
- No XSS/injection vulnerabilities detected
- Rate limiting respected to avoid IP blocking

### API Usage Limits
- FREE API - no API key required
- Rate limiting: Respect to avoid IP blocking
- Consider retry logic with exponential backoff for production

### Dependencies
None (standalone fetcher)

### Deployment
- Deployed: 2026-02-27 via GitHub Actions
- Production verified: 2026-02-27
- Status: Complete

---
## Generate Suggestions Endpoint (TASK-5-8)

### Service Description
API endpoint for generating new keyword suggestions by triggering the suggestion aggregation service. Accepts a seed keyword and configuration options, dispatches the SuggestionService to fetch suggestions from multiple sources, and returns a job ID for status tracking.

### API Endpoint

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sites/{siteId}/suggestions/generate` | Generate new keyword suggestions |

### Generate Suggestions Endpoint
`POST /api/v1/sites/{siteId}/suggestions/generate`

**Request Body:**
```json
{
  "seedKeyword": "seo tools",
  "sources": ["suggest", "question"],
  "maxResults": 50
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `seedKeyword` | Required, string, max 255 chars |
| `sources` | Required, array, min 1, values in: `suggest`, `question`, `related`, `competitor` |
| `maxResults` | Optional, integer, min 1, max 100, default 50 |

**Response (202 Accepted):**
```json
{
  "jobId": "suggest-abc123",
  "status": "queued",
  "count": 15
}
```

**Error Responses:**
| Code | Condition |
|------|-----------|
| 401 | Unauthenticated request |
| 403 | User does not own the site |
| 422 | Invalid request body |

### Suggestion Sources
| Source | Description |
|--------|-------------|
| `suggest` | Google Suggest API autocomplete suggestions |
| `question` | Question-based keyword variations (what/how/why/when/where/who/which) |
| `related` | Related searches (post-MVP) |
| `competitor` | Competitor keywords (post-MVP) |

### Processing Flow
1. Validate request parameters
2. Verify user owns the site (authorization)
3. Call `SuggestionService::generateSuggestions()` synchronously
4. Return 202 Accepted with job ID and count of suggestions generated

### Authorization
- All endpoints require authentication (Bearer token)
- User must own the site (verified via `$this->authorize('update', $site)`)
- Returns 401 for unauthenticated requests
- Returns 403 for unauthorized site access

### Security Implementation
| Security Measure | Implementation |
|------------------|----------------|
| Authentication | `auth` middleware |
| Authorization | Site ownership via policy |
| SQL Injection | Eloquent ORM with parameterized queries |
| XSS | Input validation |
| Rate Limiting | Deferred to post-MVP |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/SuggestionController.php`
- Service: `backend/app/Services/SuggestionService.php`
- Request: Validates seedKeyword, sources, maxResults
- Route: Defined in `backend/routes/api.php`

### Deferred Features
- Rate limiting (max 10 requests per hour per site) - post-MVP enhancement
- Queue-based async processing for high-volume production

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| SuggestionControllerTest | 80 | 192 |
| Generate Endpoint Tests | 14 | 35 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Generate Returns 202 | 1 | Accepted response |
| Validation (seedKeyword) | 2 | Required, max length |
| Validation (sources) | 3 | Required, array, valid values |
| Validation (maxResults) | 2 | Min 1, max 100 |
| Authorization | 3 | 401/403 responses |
| Functional | 3 | Success cases |

### Production Status
- **Deployed**: 2026-02-27 (commit 591aaa5)
- **Health Check**: Passing
- **Tests**: 80/80 passing (192 assertions)
- **Status**: Complete

### Dependencies
- TASK-5-1 (Database schema - keyword_suggestions table)
- TASK-5-2 (KeywordSuggestion model and repository)
- TASK-5-6 (Suggestion aggregation service)

### Related Tasks
- TASK-5-7: Suggestions CRUD API endpoints
- TASK-5-9: Track/Dismiss endpoints

---
## AnnotationController API (TASK-9-4)

### Service Description
REST API controller for managing keyword annotations. Provides CRUD operations with authorization via policies and request validation for secure annotation management.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/keywords/{keywordId}/annotations` | List annotations for a keyword |
| POST | `/api/v1/keywords/{keywordId}/annotations` | Create a new annotation |
| GET | `/api/v1/annotations/{id}` | Get single annotation |
| PUT | `/api/v1/annotations/{id}` | Update annotation |
| DELETE | `/api/v1/annotations/{id}` | Delete annotation |

### List Annotations
`GET /api/v1/keywords/{keywordId}/annotations`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `from` | date | Filter annotations from date (inclusive) |
| `to` | date | Filter annotations to date (inclusive) |
| `category` | string | Filter by category |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "keyword_id": 123,
      "date": "2026-02-15",
      "title": "Updated meta title",
      "description": "Changed meta title to include target keyword",
      "category": "content_update",
      "category_label": "Content Update",
      "category_icon": "✏️",
      "created_at": "2026-02-15T10:30:00Z",
      "updated_at": "2026-02-15T10:30:00Z"
    }
  ]
}
```

### Create Annotation
`POST /api/v1/keywords/{keywordId}/annotations`

**Request Body:**
```json
{
  "date": "2026-02-15",
  "title": "Updated meta title",
  "description": "Changed meta title to include target keyword",
  "category": "content_update"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `date` | Required, date, before_or_equal:today |
| `title` | Required, string, max:255 |
| `description` | Nullable, string, max:5000 |
| `category` | Required, in:content_update,link_building,technical_fix,algorithm_update,competitor_action,seasonal,manual_action,other |

**Response (201):**
```json
{
  "id": 1,
  "keyword_id": 123,
  "project_id": 1,
  "date": "2026-02-15",
  "title": "Updated meta title",
  "description": "Changed meta title to include target keyword",
  "category": "content_update",
  "created_at": "2026-02-15T10:30:00Z"
}
```

### Update Annotation
`PUT /api/v1/annotations/{id}`

Same request body and validation as create.

### Delete Annotation
`DELETE /api/v1/annotations/{id}`

**Response (200):**
```json
{
  "success": true
}
```

### Authorization
All endpoints enforce user ownership via `AnnotationPolicy`:
- Users can only access annotations for projects they own
- Authorization checks use `$this->authorize()` in controller methods
- Returns 403 Forbidden for unauthorized access

### Test Coverage
73 test scenarios in `backend/tests/Feature/Api/V1/AnnotationControllerTest.php`:
- Index (List): 10 tests (filtering, pagination, authorization)
- Store (Create): 12 tests (validation, authorization, success)
- Show (Read): 4 tests
- Update: 10 tests (validation, authorization)
- Destroy (Delete): 4 tests
- Authorization (Unauthenticated): 5 tests (401 responses)
- Authorization (Cross-User): 5 tests (403 responses)
- Authorization (Owner): 5 tests (success)
- Edge Cases: 12 tests (XSS, SQL injection, boundaries)
- Integration: 6 tests

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/AnnotationController.php`
- Resource: `backend/app/Http/Resources/AnnotationResource.php`
- Request: `backend/app/Http/Requests/StoreAnnotationRequest.php`
- Request: `backend/app/Http/Requests/UpdateAnnotationRequest.php`
- Policy: `backend/app/Policies/AnnotationPolicy.php`

### Dependencies
- TASK-9-1 (Annotations table schema)
- TASK-9-3 (Annotation model)
- TASK-9-5 (AnnotationPolicy)
- TASK-9-6 (Input validation requests)

### Security Notes
- All endpoints require authentication
- Authorization via policies ensures user isolation
- Input validation prevents malformed data
- XSS/SQL injection tests pass (safe handling)

### Deployment Status
- Implementation complete
- Tests passing
- Status: Done

---
## Track/Dismiss Endpoints (TASK-5-9)

### Service Description
REST API endpoints for managing keyword suggestions. Provides functionality to add suggestions to tracking (converting them to tracked keywords) and dismiss suggestions the user doesn't want. These endpoints bridge the keyword discovery system with the keyword tracking system.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sites/{siteId}/suggestions/{suggestionId}/track` | Add single suggestion to tracking |
| POST | `/api/v1/sites/{siteId}/suggestions/bulk-track` | Add multiple suggestions to tracking |
| POST | `/api/v1/sites/{siteId}/suggestions/{suggestionId}/dismiss` | Dismiss a suggestion |

### Track Endpoint
`POST /api/v1/sites/{siteId}/suggestions/{suggestionId}/track`

**Description:** Converts a suggestion into a tracked keyword. Creates a keyword record in the keywords table and marks the suggestion as tracking.

**Authorization:** User must own the site (401 unauthenticated, 403 unauthorized)

**Response (201):**
```json
{
  "keyword": {
    "id": 1,
    "keyword": "best seo tools 2026",
    "status": "active"
  },
  "suggestion": {
    "id": 1,
    "is_tracking": true
  }
}
```

**Side Effects:**
- Creates keyword record in `keywords` table (FEAT-001)
- Updates `keyword_suggestions.is_tracking = true`

### Bulk Track Endpoint
`POST /api/v1/sites/{siteId}/suggestions/bulk-track`

**Request Body:**
```json
{
  "suggestionIds": [1, 2, 3, 4, 5]
}
```

**Response (201):**
```json
{
  "added": [
    { "keyword": "best seo tools 2026", "keywordId": 1 },
    { "keyword": "seo software free", "keywordId": 2 }
  ],
  "skipped": [
    { "keyword": "seo", "reason": "Already tracking" }
  ]
}
```

**Behavior:**
- Uses database transaction for atomicity
- Validates each suggestion belongs to the site
- Returns separate arrays for added and skipped suggestions

### Dismiss Endpoint
`POST /api/v1/sites/{siteId}/suggestions/{suggestionId}/dismiss`

**Description:** Marks a suggestion as dismissed so it won't appear in future suggestion lists. Also creates a record in the `dismissed_suggestions` table to prevent rediscovery.

**Response (200):**
```json
{
  "id": 1,
  "is_dismissed": true
}
```

**Side Effects:**
- Updates `keyword_suggestions.is_dismissed = true`
- Creates record in `dismissed_suggestions` table

### Authorization Flow
| Request | Response |
|---------|----------|
| Unauthenticated | 401 Unauthorized |
| Authenticated, wrong user | 403 Forbidden |
| Owner | Full access |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/SuggestionController.php`
- Methods: `track()`, `bulkTrack()`, `dismiss()`
- Routes defined in: `backend/routes/api.php`

### Test Coverage
| Test File | Tests | Assertions |
|-----------|-------|------------|
| SuggestionControllerTest | 80 | 192 |

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Track endpoint | 9 | Single suggestion tracking |
| Bulk-track endpoint | 11 | Multiple suggestions, added/skipped arrays |
| Dismiss endpoint | 8 | Suggestion dismissal |
| Authorization | 6 | 401/403 response verification |
| Security/Adversarial | 7 | SQL injection, XSS, invalid inputs |

### Security Notes
- SQL injection protection: Eloquent ORM with parameterized queries
- XSS protection: Input validation and output escaping
- Authorization enforced at controller level
- Database transactions for bulk operations

### Production Status
- **Deployed**: 2026-02-27 (GitHub Actions run 22492252069)
- **Health Check**: Passing
- **Tests**: 80/80 passing (192 assertions)
- **Status**: Complete

### Dependencies
- TASK-5-1 (Database schema - keyword_suggestions, dismissed_suggestions tables)
- TASK-5-2 (KeywordSuggestion model and repository)
- FEAT-001 (Keywords table for tracking)

---
## Suggestion Aggregation Service (TASK-5-6)

### Service Description
Core orchestration service that aggregates keyword suggestions from multiple sources, deduplicates results, calculates opportunity scores, and stores suggestions in the database. This is the central hub for keyword discovery functionality.

### Core Functionalities

**SuggestionService Class:**
- `generateSuggestions(siteId: int, seedKeyword: string, sources: array): int` - Main method for generating suggestions
- Accepts sources array: `['suggest', 'question']` (extensible for future sources)
- Returns count of new suggestions added

**Source Integration:**
| Source | Fetcher | Description |
|--------|---------|-------------|
| `suggest` | GoogleSuggestFetcher | Google autocomplete suggestions |
| `question` | QuestionGenerator | Who/what/where/how keyword variations |

**Processing Pipeline:**
1. Fetch suggestions from each enabled source
2. Deduplicate across sources (case-insensitive keyword matching)
3. Calculate opportunity score for each suggestion
4. Store in `keyword_suggestions` table via upsert
5. Mark existing suggestions as `is_dismissed: false` if rediscovered
6. Log performance metrics

**Error Handling:**
- Graceful degradation - continues with other sources on failure
- Logs all errors with context
- Transaction rollback on storage failure

### Technical Implementation
- Service: `backend/app/Services/SuggestionService.php`
- Dependencies (injected via constructor):
  - `KeywordSuggestionRepository` - Storage operations
  - `QuestionGenerator` - Question keyword generation
  - `GoogleSuggestFetcher` - Google Suggest API
  - `OpportunityScorer` - Score calculation

### Usage Example
```php
$suggestionService = app(SuggestionService::class);
$count = $suggestionService->generateSuggestions(
    siteId: 1,
    seedKeyword: 'seo tools',
    sources: ['suggest', 'question']
);
// Returns: 15 (number of new suggestions added)
```

### Unit Tests
14 tests, 14 assertions covering:
- SS-001: Generate from question source
- SS-002: Calculate opportunity scores
- SS-003: Store in repository
- SS-004: Deduplicate existing suggestions
- SS-005: Undismiss rediscovered suggestions
- SS-006: Handle empty results
- SS-007: Multiple sources aggregation
- SS-008: Log performance
- SS-009: Handle fetcher exception
- SS-010: Storage failure handling
- Max results limit test
- Correct source label test
- Deduplication across sources
- Integration with real dependencies

### Production Status
- **Deployed**: 2026-02-27 (commit 45fa05c)
- **Health Check**: Passing
- **Tests**: 14 passed (14 assertions)
- **Database**: keyword_suggestions table operational

### Security Notes
- Input validation on seed keywords
- No SQL injection vulnerabilities (Eloquent ORM)
- Graceful error handling prevents information leakage
- Rate limiting handled by fetchers

### Dependencies
- TASK-5-1 (Database schema - keyword_suggestions table)
- TASK-5-2 (KeywordSuggestion model and repository)
- TASK-5-3 (Opportunity scorer)
- TASK-5-4 (Google Suggest fetcher)
- TASK-5-5 (Question keyword generator)

---
## Suggestions CRUD API Endpoints (TASK-5-7)

### Service Description
REST API endpoints for listing, paginating, and filtering keyword suggestions. This is the primary API layer for the frontend to display and manage keyword suggestions discovered through the keyword discovery system.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/suggestions` | List suggestions with pagination and filtering |
| GET | `/api/v1/sites/{siteId}/suggestions/export` | Export suggestions as CSV |

### List Suggestions Endpoint
`GET /api/v1/sites/{siteId}/suggestions`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `source` | string | null | Filter by suggestion source (suggest, question, all) |
| `minScore` | int | null | Minimum opportunity score filter |
| `maxScore` | int | null | Maximum opportunity score filter |
| `includeDismissed` | boolean | false | Include dismissed suggestions |
| `includeTracking` | boolean | false | Include suggestions already being tracked |
| `page` | int | 1 | Page number |
| `perPage` | int | 20 | Results per page (max: 100) |
| `sort` | string | opportunity_score | Sort field |
| `order` | string | desc | Sort order (asc, desc) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "best seo tools 2026",
      "source": "suggest",
      "opportunityScore": 75,
      "isTracking": false,
      "isDismissed": false,
      "discoveredAt": "2026-02-27T10:00:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "perPage": 20
  }
}
```

**Default Behavior:**
- Excludes dismissed suggestions (`isDismissed: false`)
- Excludes already tracking suggestions (`isTracking: false`)
- Sorts by `opportunity_score DESC`
- Returns paginated results

**Authorization:**
- Requires authentication (Bearer token)
- User must own the site (verified via `$this->authorize('view', $site)`)
- Returns 401 for unauthenticated requests
- Returns 403 for unauthorized site access

### Export Suggestions Endpoint
`GET /api/v1/sites/{siteId}/suggestions/export`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `source` | string | Filter by suggestion source |

**Response:**
- Content-Type: `text/csv`
- Headers: `Keyword,Source,Opportunity Score,Tracking Status,Discovered At`
- Returns CSV download with filtered suggestions

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/SuggestionController.php`
- Methods: `index()`, `export()`
- Repository: `KeywordSuggestionRepository::getPaginated()`
- Routes defined in: `backend/routes/api.php`

### Test Coverage
| Category | Tests | Assertions |
|----------|-------|------------|
| Index (List) | 17 | 17 |
| Export | 8 | 8 |
| Authorization | 6 | 6 |
| Security/Adversarial | 6 | 6 |
| Track endpoint | 9 | 9 |
| Bulk-track endpoint | 12 | 12 |
| Dismiss endpoint | 8 | 8 |
| Generate endpoint | 14 | 14 |
| **Total** | **80** | **192** |

### Security Notes
- SQL injection protection: Eloquent ORM with parameterized queries
- XSS protection: Input sanitization in query parameters
- Authorization boundaries enforced (401/403 responses)
- All endpoints require authentication

### Production Status
- **Deployed**: 2026-02-27 (commit 97e06ee..ecfccf3)
- **Health Check**: Passing
- **Production Tests**: 80 tests, 192 assertions passing
- **Status**: Complete

### Dependencies
- TASK-5-1 (Database schema - keyword_suggestions table)
- TASK-5-2 (KeywordSuggestion model and repository)

---
## Other SERP Feature Detection (TASK-7-7)

### Service Description
Implements detection for remaining SERP feature types beyond featured snippets and local packs. Provides comprehensive SERP feature tracking for image packs, videos, People Also Ask, knowledge panels, sitelinks, reviews, FAQ rich results, and product listings.

### Core Functionalities

**Detection Methods:**
| Detector | Method | Description |
|----------|--------|-------------|
| Image Pack | `detectImagePack()` | Detects horizontal image rows in SERP |
| Video | `detectVideo()` | Detects video thumbnails (YouTube, Vimeo, self-hosted) |
| People Also Ask | `detectPeopleAlsoAsk()` | Detects expandable Q&A sections |
| Knowledge Panel | `detectKnowledgePanel()` | Detects entity info sidebar |
| Sitelinks | `detectSitelinks()` | Detects additional page links under result |
| Reviews | `detectReviews()` | Detects star ratings in results |
| FAQ | `detectFAQ()` | Detects FAQ rich result accordions |
| Product | `detectProduct()` | Detects product listings with price/availability |

### Return Structure
Each detector returns a `SERPFeature` object:
```typescript
interface SERPFeature {
  type: string;           // Feature type identifier
  isPresent: boolean;     // Whether feature is present for target URL
  position?: number;      // Position within feature (1-indexed)
  url?: string;           // URL shown in feature
  count?: number;         // Count (e.g., number of sitelinks)
  rating?: number;        // Star rating (for reviews)
  reviewCount?: number;   // Number of reviews
  snippet?: string;       // Text snippet (for FAQ, PAA)
  matchedQuestions?: string[]; // Questions matching target (for PAA)
}
```

### URL Matching
All detectors use shared URL utilities for consistent domain matching:
- `extractHostname(url)` - Extracts hostname from URL
- `normalizeHostname(hostname)` - Normalizes (lowercase, removes www prefix)
- `domainsMatch(url1, url2)` - Compares two URLs for domain match

### Technical Implementation
**Detector Files:**
- `backend/src/services/detectors/url-utils.js` - Shared URL utilities
- `backend/src/services/detectors/image-pack-detector.js`
- `backend/src/services/detectors/video-detector.js`
- `backend/src/services/detectors/people-also-ask-detector.js`
- `backend/src/services/detectors/knowledge-panel-detector.js`
- `backend/src/services/detectors/sitelinks-detector.js`
- `backend/src/services/detectors/reviews-detector.js`
- `backend/src/services/detectors/faq-detector.js`
- `backend/src/services/detectors/product-detector.js`

### Feature-Specific Logic

**Image Pack Detector:**
- Checks `serp.images` array
- Matches target domain in image source URLs
- Returns position if found (1-indexed)

**Video Detector:**
- Checks `serp.videos` array
- Matches YouTube/Vimeo/self-hosted videos
- Returns position and video platform

**People Also Ask Detector:**
- Checks `serp.relatedQuestions` array
- Finds questions linking to target domain
- Returns matched questions array
- Bug fix: `isPresent` based on actual domain match (not question count)

**Knowledge Panel Detector:**
- Checks `serp.knowledgePanel` object
- Matches source URL against target domain
- Returns title and snippet if present

**Sitelinks Detector:**
- Checks first organic result for sitelinks
- Only applies to position 1 result
- Returns count of sitelinks

**Reviews Detector:**
- Checks organic results for rating data
- Matches target domain
- Returns star rating and review count

**FAQ Detector:**
- Checks for FAQ rich results structure
- Matches links in FAQ items
- Returns matched FAQ snippets

**Product Detector:**
- Checks `serp.products` or shopping results
- Matches target domain in product listings
- Returns price, availability, position

### Unit Tests
**JavaScript Tests (Mocha):** 162 tests across all 8 detectors
| Detector | Tests | Description |
|----------|-------|-------------|
| Image Pack | 22 | Detection, position, URL matching |
| Video | 22 | YouTube/Vimeo/self-hosted, position |
| People Also Ask | 19 | Question matching, domain check |
| Knowledge Panel | 22 | Entity detection, source matching |
| Sitelinks | 17 | First result, count detection |
| Reviews | 21 | Rating, review count, stars |
| FAQ | 17 | FAQ items, link matching |
| Product | 24 | Price, availability, position |

**PHP Tests (PHPUnit - SERP Feature related):** 130 tests
- SerpFeatureRepositoryTest: 17 tests
- SerpFeatureChangeTrackerTest: 30 tests
- SerpFeatureServiceTest: 14 tests
- SerpFeatureControllerTest: 22 tests
- SerpFeatureWorkflowTest: 21 tests
- SerpFeaturesApiIntegrationTest: 26 tests

### Adversarial Testing
All detectors tested for:
- Null SERP objects → graceful handling
- Malformed URLs → no exceptions
- Empty arrays → proper fallback
- Special characters in feature types
- XSS/SQL injection patterns → handled correctly
- Prototype pollution prevention

### Security Notes
- All detectors handle malformed input gracefully
- No exceptions thrown on edge cases
- URL validation prevents injection
- Shared utilities ensure consistent sanitization

### Dependencies
- TASK-7-4 (Detector service infrastructure)
- TASK-7-5 (Featured snippet detector - pattern reference)
- TASK-7-6 (Local pack detector - pattern reference)

### Deployment
- **Deployed**: 2026-02-27 (commit acf9794)
- **Production Verified**: 2026-02-27
- **Tests**: 316 JavaScript, 130 PHP passing
- **Status**: Complete

---
## Question Keyword Generator (TASK-5-5)

### Service Description
Utility class for generating question-based keyword variations. Provides additional FREE keyword suggestions beyond Google Suggest by combining a seed keyword with common question prefixes.

### Core Functionalities

**QuestionGenerator Class:**
- `generate(?string $keyword): array` - Returns array of question keywords
- `__invoke(?string $keyword): array` - Invokable for dependency injection support

**Question Prefixes:**
| Prefix | Example Output |
|--------|----------------|
| `what is` | "what is seo tools" |
| `how to` | "how to seo tools" |
| `why` | "why seo tools" |
| `when` | "when seo tools" |
| `where` | "where seo tools" |
| `who` | "who seo tools" |
| `which` | "which seo tools" |

### Technical Implementation
- Class: `backend/app/Services/Fetchers/QuestionGenerator.php`
- Pure PHP utility with no external dependencies
- No API calls required (FREE source)
- Handles null/empty input gracefully (returns empty array)

### Security Assessment
| Test | Input | Result | Status |
|------|-------|--------|--------|
| XSS Injection | `<script>alert(1)</script>` | Preserved as-is, no eval | ✓ SAFE |
| SQL Injection | `'; DROP TABLE users; --` | Preserved as-is, no DB queries | ✓ SAFE |
| Null Bytes | `test\0keyword` | Handled gracefully | ✓ PASS |
| DoS (10000 chars) | Very long input | 0ms processing time | ✓ PASS |
| Unicode/Emoji | `seo 🚀 keywords 🎯` | Preserved correctly | ✓ PASS |
| Whitespace Only | `   \t\n   ` | Returns empty array | ✓ PASS |
| RTL Override | `\u202Etest` | Preserved as-is | ✓ PASS |
| Empty String | `''` | Returns empty array | ✓ PASS |
| Null Input | `null` | Returns empty array | ✓ PASS |

**Risk Level:** Low - Pure string concatenation utility with no external dependencies

### Unit Tests
25 tests, 51 assertions covering:
- All 7 question prefixes
- Custom prefixes via constructor
- Invokable support
- Null/empty keyword handling
- Whitespace trimming
- Lowercase output
- Edge cases (special characters, unicode, long input)
- Security tests (XSS, SQL injection, null bytes)

### Dependencies
None (standalone utility)

### Integration
- Used by `SuggestionService` for question-based keyword generation
- Part of the keyword discovery system (FEAT-005)

### Deployment
- **Deployed**: 2026-02-27 (commit 780b608)
- **Health Check**: Passing
- **Tests**: 25 passed (51 assertions)
- **Status**: Complete

---
## Opportunity Scorer Utility (TASK-5-3)

### Service Description
Utility class for calculating keyword opportunity scores to help users prioritize which suggested keywords to track. Uses a pure function approach with configurable scoring factors for long-tail keywords, question prefixes, and commercial intent.

### Core Functionalities

**OpportunityScorer Class:**
- `calculate(string $keyword): int` - Returns opportunity score (50-100)
- `__invoke(string $keyword): int` - Invokable for dependency injection

### Scoring Algorithm

**Base Score:** 50

**Bonuses:**
| Factor | Condition | Bonus |
|--------|-----------|-------|
| Long-tail (3+ words) | Word count >= 3 | +10 |
| Very long-tail (5+ words) | Word count >= 5 | +5 (cumulative: +15) |
| Question prefix | Starts with: what/how/why/when/where/who/which | +15 |
| Commercial intent | Contains: best/top/review/compare | +10 |

**Maximum Score:** 100 (enforced cap)

### Scoring Examples

| Keyword | Word Count | Question | Commercial | Final Score |
|---------|------------|----------|------------|-------------|
| seo | 1 | No | No | 50 |
| seo tools | 2 | No | No | 50 |
| seo tools for beginners | 4 | No | No | 60 |
| what is seo | 3 | Yes | No | 75 |
| best seo tools | 3 | No | Yes | 70 |
| how to choose best seo tools | 5 | Yes | Yes | 90 |
| why use seo tools for beginners | 5 | Yes | No | 80 |

### Technical Implementation
- Class: `backend/app/Services/OpportunityScorer.php`
- Pure function with no external dependencies
- Invokable for dependency injection support
- Easily testable with deterministic output

### Unit Tests
33 tests, 54 assertions covering:
- Base score (single/two words)
- Long-tail bonus (3+ words)
- Very long-tail bonus (5+ words)
- Question prefix bonus (all 7 prefixes)
- Commercial intent bonus (all 4 keywords)
- Combined bonuses (question + commercial + long-tail)
- Maximum score enforcement (100 cap)
- Invokable support
- Empty string handling
- Unicode character handling
- Case insensitivity
- Whitespace handling

### Adversarial Testing
All security tests pass:
| Test | Result |
|------|--------|
| DoS resistance (10,000 words) | 0.92ms execution |
| SQL injection | Safe - returns valid score |
| XSS attempt | Safe - returns valid score |
| Null byte injection | Handled correctly |
| Unicode homograph | Handled correctly |

### Production Status
- **Deployed**: 2026-02-27 (commit 780b608)
- **Health Check**: Passing
- **Tests**: 33 passed (54 assertions)
- **Security**: No vulnerabilities

### Dependencies
None (standalone utility)

### Integration
- Used by `SuggestionService` for opportunity score calculation
- Injected via DI container

---
## Rate Limiting & Anti-Abuse (TASK-16-11)

### Service Description
Implements rate limiting and anti-abuse measures for interactive SEO tools. Prevents spam, abuse, and ensures fair usage of server resources through per-IP rate limiting, CAPTCHA challenges, and SSRF protection.

### Core Functionalities

**Rate Limiting Configuration:**
| Limit Type | Default | Description |
|------------|---------|-------------|
| Per-IP Daily Limit | 100 requests | Max requests per IP per day |
| CAPTCHA Threshold | 3 uses | CAPTCHA shown after 3 tool uses (unauthenticated) |
| Configurable | Yes | Limits adjustable via config |

**SSRF Protection:**
| Protection | Description |
|------------|-------------|
| Localhost blocked | `localhost`, `127.*` blocked |
| Private IPs blocked | `10.*`, `172.16-31.*`, `192.168.*` blocked |
| File protocol blocked | `file://` protocol blocked |
| Internal domains | Domains containing "internal" blocked |

### Response Headers
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 7
X-RateLimit-Reset: 1709049600
Retry-After: 60
```

### 429 Rate Limit Response
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "You have exceeded the rate limit. Please complete the CAPTCHA to continue.",
    "captchaRequired": true,
    "captchaSiteKey": null,
    "captchaDriver": "recaptcha"
  }
}
```

### API Endpoints

| Endpoint | Description |
|----------|-------------|
| `POST /api/v1/tools/analyze` | Main tool analysis endpoint (rate limited) |
| `GET /api/v1/tools/rate-limit-status` | Get current rate limit status |
| `GET /api/v1/tools/captcha-config` | Get CAPTCHA configuration |

### Technical Implementation
- Service: `backend/app/Services/Tools/ToolsRateLimitService.php`
- Middleware: Rate limiting middleware for tool routes
- CAPTCHA: hCaptcha/ReCAPTCHA integration (configurable)

### Security Assessment
| Test | Status | Details |
|------|--------|---------|
| SSRF Protection (localhost) | ✅ PASS | Error: "Access to this host is not allowed" |
| SSRF Protection (192.168.x.x) | ✅ PASS | Blocked correctly |
| SSRF Protection (file://) | ✅ PASS | Blocked correctly |
| Rate Limit Headers | ✅ PASS | X-RateLimit-* headers present |
| Rate Limit Enforcement | ✅ PASS | 429 returned after limit exceeded |

### Unit Tests
64 tests, 250 assertions covering:
- Per-Minute Rate Limiting (8 tests)
- Per-Day Rate Limiting (8 tests)
- CAPTCHA Integration (10 tests)
- URL Blocklist (12 tests)
- Rate Limit Headers (8 tests)
- Abuse Logging (6 tests)
- Edge Cases (12 tests)

### Deferred Items
- Request throttling (max concurrent requests per IP) - deferred
- Admin dashboard for rate limit monitoring - deferred

### Known Issues
- 429 response body may be empty in production (LiteSpeed/CDN configuration issue)
- CAPTCHA keys require configuration (`RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`)

### Dependencies
- TASK-16-1 (Tools Infrastructure)

### Deployment
- **Deployed**: 2026-02-27
- **Method**: Git push to origin/main + manual SSH deployment
- **Backend**: Deployed via SSH (frontend build had pre-existing Windows issue)
- **Health Check**: PASS
- **Tests**: 64 tests, 250 assertions passing

---
## Database Schema: Backlink Changes Table (TASK-13-3)

### Backlink Changes Table
Stores detected changes (new, lost, or modified) for backlinks tracked by projects. Enables historical tracking of link profile evolution and alerts for link building progress or potential issues.

```sql
CREATE TABLE backlink_changes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    backlink_id BIGINT UNSIGNED NOT NULL,
    change_type ENUM('new', 'lost', 'modified') NOT NULL,
    details JSON NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_change (site_id, change_type)
);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `site_id` | BIGINT UNSIGNED | FK to sites.id (project context) |
| `backlink_id` | BIGINT UNSIGNED | FK to backlinks.id |
| `change_type` | ENUM | Type of change: `new`, `lost`, or `modified` |
| `details` | JSON | Change details (flexible structure for different change types) |
| `detected_at` | TIMESTAMP | When the change was detected |
| `created_at` | TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Constraints:**
- Foreign Key: `backlink_id → backlinks.id` ON DELETE CASCADE
- Foreign Key: `site_id → sites.id` ON DELETE CASCADE

**Indexes:**
- `idx_site_change` on `(site_id, change_type)` - Optimize queries filtering by site and change type
- `backlink_changes_backlink_id_index` - Index on backlink_id for efficient lookups
- `backlink_changes_detected_at_index` - Index for date-based queries

### Change Types
| Type | Description | Example details JSON |
|------|-------------|----------------------|
| `new` | New backlink discovered | `{"source_url": "...", "anchor_text": "...", "domain_authority": 45}` |
| `lost` | Previously active backlink no longer exists | `{"source_url": "...", "last_seen": "2026-02-20"}` |
| `modified` | Backlink attributes changed | `{"field": "anchor_text", "old": "SEO Tools", "new": "Best SEO Tools"}` |

### Model Relationships

**BacklinkChange Model:**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `site()` | belongsTo | The site this change belongs to |
| `backlink()` | belongsTo | The backlink that changed |

**Backlink Model (added relationship):**
| Method | Relationship | Description |
|--------|--------------|-------------|
| `changes()` | hasMany | BacklinkChange records for this backlink |

### Production Status
- **Deployed**: 2026-02-27 (migration batch 4)
- **Migration**: `2026_02_21_220100_create_backlink_changes_table.php`
- **Verified**: Health check passing, production schema verified via SSH
- **Status**: Complete

### Dependencies
- TASK-13-2 (backlinks table)
- TASK-1-1 (sites table)

### Related Tasks
- TASK-13-1: Backlink summaries table
- TASK-13-2: Backlinks table
- FEAT-013: Backlink Monitoring feature

---
## Template Management API (TASK-4-10)

### Service Description
REST API endpoints for managing report templates. Templates define reusable configurations for SEO reports, including sections, branding, and custom text. Supports both site-specific and global templates.

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sites/{siteId}/report-templates` | List templates for a site |
| GET | `/api/v1/report-templates/{id}` | Get single template details |
| POST | `/api/v1/sites/{siteId}/report-templates` | Create a new template |
| PUT | `/api/v1/report-templates/{id}` | Update template |
| DELETE | `/api/v1/report-templates/{id}` | Delete template |

### Route Access Control
| Route | Access Level |
|-------|-------------|
| `GET /sites/{siteId}/report-templates` | Public (read) |
| `GET /report-templates/{id}` | Public (read) |
| `POST /sites/{siteId}/report-templates` | Authenticated |
| `PUT /report-templates/{id}` | Authenticated |
| `DELETE /report-templates/{id}` | Authenticated |

### List Templates
`GET /api/v1/sites/{siteId}/report-templates`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "site_id": 1,
      "name": "Default Template",
      "is_default": true,
      "sections": ["summary", "keywords", "changes", "trends"],
      "branding": {
        "logo_url": "https://example.com/logo.png",
        "primary_color": "#2563eb",
        "font": "Inter"
      },
      "cover_page_text": "Weekly SEO Report",
      "executive_summary": "Key highlights...",
      "created_at": "2026-02-24T10:00:00Z",
      "updated_at": "2026-02-24T10:00:00Z"
    }
  ]
}
```

### Get Template
`GET /api/v1/report-templates/{id}`

**Response (200):** Returns single template object

**Error Responses:**
| Code | Condition |
|------|-----------|
| 404 | Template not found |

### Create Template
`POST /api/v1/sites/{siteId}/report-templates`

**Request Body:**
```json
{
  "name": "Client Report",
  "sections": ["summary", "keywords", "top-performers", "needs-attention"],
  "cover_page_text": "Prepared for ACME Corp",
  "executive_summary": "Custom summary text..."
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `name` | Required, string, max 255 chars |
| `sections` | Required, array of valid section names |
| `branding` | Optional, JSON object |
| `cover_page_text` | Optional, string |
| `executive_summary` | Optional, string |

**Valid Sections:**
- `summary` - Executive summary with key metrics
- `keywords` - Full keyword rankings table
- `changes` - Position changes highlights
- `top-performers` - Best improving keywords
- `needs-attention` - Declining keywords
- `trends` - Position history charts

**Response (201):** Returns created template object

### Update Template
`PUT /api/v1/report-templates/{id}`

**Request Body:** Same fields as create (all optional for partial updates)

**Response (200):** Returns updated template object

### Delete Template
`DELETE /api/v1/report-templates/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Template deleted successfully"
}
```

### Security Implementation

| Security Measure | Implementation |
|------------------|----------------|
| Authentication | `auth:sanctum` middleware on write operations |
| Authorization | User ownership verification for write operations |
| SQL Injection | Eloquent ORM with parameterized queries |
| XSS Protection | Input sanitization, `strip_tags()` on text fields |
| Validation | FormRequest validation on all inputs |

### Technical Implementation
- Controller: `backend/app/Http/Controllers/Api/V1/ReportTemplateController.php`
- Model: `backend/app/Models/ReportTemplate.php`
- Repository: `backend/app/Repositories/ReportTemplateRepository.php`
- Request: `backend/app/Http/Requests/StoreReportTemplateRequest.php`
- Request: `backend/app/Http/Requests/UpdateReportTemplateRequest.php`
- Routes: Defined in `backend/routes/api.php`

### Model Properties
| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `site_id` | int? | FK to sites.id (null = global template) |
| `name` | string | Template name |
| `is_default` | bool | Whether this is the default template |
| `sections` | array | JSON array of section names |
| `branding` | object? | JSON: `{logo_url, primary_color, font}` |
| `cover_page_text` | string? | Custom cover page text |
| `executive_summary` | string? | Default executive summary |

### Model Relationships
| Method | Relationship | Description |
|--------|--------------|-------------|
| `site()` | belongsTo | Site this template belongs to |
| `scheduledReports()` | hasMany | Scheduled reports using this template |

### Production Test Results
| Test | Endpoint | Expected | Actual | Status |
|------|----------|----------|--------|--------|
| List templates | GET /sites/1/report-templates | 200 | 200 | ✅ PASS |
| Get template | GET /report-templates/1 | 200 | 200 | ✅ PASS |
| Create (no auth) | POST /sites/1/report-templates | 401 | 401 | ✅ PASS |
| Update (no auth) | PUT /report-templates/1 | 401 | 401 | ✅ PASS |
| Delete (no auth) | DELETE /report-templates/1 | 401 | 401 | ✅ PASS |
| SQL injection | GET ?name=';DROP... | Safe | Safe | ✅ PASS |
| XSS protection | Response body | No scripts | No scripts | ✅ PASS |
| Not found | GET /report-templates/99999 | 404 | 404 | ✅ PASS |

### Dependencies
- TASK-4-1 (Reports tables schema)
- TASK-4-3 (ReportTemplate model/repository)

### Related Tasks
- TASK-4-7: Reports CRUD API
- TASK-4-8: Report generation endpoint
- TASK-4-11: Scheduled Reports API

### Production Status
- **Deployed**: 2026-02-24 (commit via GitHub Actions)
- **Health Check**: Passing
- **Tests**: 8/8 production tests passing
- **Status**: Complete

---
## Frontend Components: Projects (TASK-8-8)

### Overview
React components and context for project management. These components handle project selection, CRUD operations, and multi-site dashboard views.

### Component Architecture

**Types (`frontend/lib/types/projects.ts`):**
| Type | Description |
|------|-------------|
| `Project` | Project entity with id, name, domain, notes, status |
| `ProjectMetrics` | Metrics for a project (keywordsCount, avgPosition, visibility) |
| `ProjectContextValue` | Context interface with CRUD methods |
| `ProjectBreakdown` | Project with embedded metrics |

**API Functions (`frontend/lib/api/projects.ts`):**
| Function | Description |
|----------|-------------|
| `useProjects()` | SWR hook for projects list |
| `useProject(id)` | SWR hook for single project |
| `useProjectMetrics(projectId)` | SWR hook for project metrics |
| `createProject(payload)` | Create new project |
| `updateProject(id, payload)` | Update project |
| `deleteProject(id)` | Delete project with confirmation |
| `archiveProject(id)` | Archive project |
| `restoreProject(id)` | Restore archived project |
| `fetchAggregateMetrics()` | Get aggregate metrics for all projects |

**Context (`frontend/lib/contexts/ProjectContext.tsx`):**
| Hook | Description |
|------|-------------|
| `useProjectContext()` | Full context access (projects, currentProject, CRUD methods) |
| `useCurrentProject()` | Current project only |
| `useProjectActions()` | CRUD operations only |

### Components

**ProjectSelector (`frontend/components/projects/ProjectSelector.tsx`):**
- Dropdown for switching between projects
- Keyboard navigation (ArrowUp/Down, Enter, Escape)
- Loading/error/empty states
- ARIA accessibility attributes

```tsx
<ProjectSelector
  projects={projects}
  currentProject={currentProject}
  onSelect={switchProject}
  onCreateNew={() => setShowCreateModal(true)}
  isLoading={isLoading}
  error={error}
/>
```

**ProjectDashboard (`frontend/components/projects/ProjectDashboard.tsx`):**
- Dashboard scoped to current project
- Metric cards (Keywords, Avg Position, Visibility, Position Change)
- Date range filter (7d, 30d, 90d)
- Quick action links (Keywords, Competitors, Reports)

```tsx
<ProjectDashboard project={project} />
```

**AllProjectsView (`frontend/components/projects/AllProjectsView.tsx`):**
- Aggregate multi-site view
- Summary metrics cards
- Sortable/filterable projects table
- Search functionality
- Show/hide archived toggle

```tsx
<AllProjectsView onCreateProject={() => setShowCreateModal(true)} />
```

**ProjectSettingsView (`frontend/components/projects/ProjectSettingsView.tsx`):**
- Form for editing project details
- Name, domain, notes fields with validation
- Unsaved changes warning
- Archive/restore functionality
- Delete with confirmation dialog

```tsx
<ProjectSettingsView
  project={project}
  onSave={handleSave}
  onDelete={handleDelete}
  onArchive={handleArchive}
  onRestore={handleRestore}
/>
```

**AddProjectModal (`frontend/components/projects/AddProjectModal.tsx`):**
- Modal for creating new projects
- Form validation (name required, domain format)
- Controlled/uncontrolled open state

```tsx
<AddProjectModal
  onCreateProject={createProject}
  open={showModal}
  onOpenChange={setShowModal}
/>
```

### Pages

| Route | Component | Description |
|-------|-----------|-------------|
| `/projects` | `AllProjectsView` | List all projects with aggregate metrics |
| `/projects/new` | `AddProjectModal` | Create new project |
| `/projects/[projectId]/dashboard` | `ProjectDashboard` | Project-specific dashboard |
| `/projects/[projectId]/settings` | `ProjectSettingsView` | Project settings form |

### Local Storage
- `seoindicator_current_project` - Persists selected project ID

### Dependencies
- TASK-8-7 (CRUD API for projects)
- TASK-8-1 (Projects Table)
- TASK-8-6 (Project Policies)

### Deployment
- Status: Implementation complete, TypeScript compiles successfully
- Note: Build shows Windows-specific Next.js static export issue (unrelated to component code)

---
