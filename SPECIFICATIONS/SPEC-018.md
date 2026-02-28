# SPEC-018: Legacy 17-Indicator SEO Analysis (Core Engine)

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`High`

## Source Feature
FEAT-018: Legacy 17-Indicator SEO Analysis (Core Engine)

---

## 1. Technical Overview

### 1.1 Purpose
Rebuild the proven core analysis engine from the legacy SEOIndicator.com. This is the foundational feature that accepts a domain, fetches HTML, runs 17 on-page SEO indicators, computes a weighted score, and presents actionable results.

### 1.2 Technology Stack

| Layer | Technology | Rationale |
|-------|------------|-----------|
| Backend | Laravel 11 (PHP 8.2+) | Existing project stack |
| HTML Parsing | Symfony DomCrawler | Already integrated (FEAT-016) |
| HTTP Client | Laravel HTTP Client | Built-in, supports timeouts, retries |
| Caching | Laravel Cache (file/redis) | Performance optimization |
| Frontend | Next.js (React) | Existing project stack |
| PDF Export | Native PHP PDF (from FEAT-004) | Consistent with reporting |

### 1.3 Free Tools Constraint
All implementations must use free tools only:
- No paid SEO APIs (e.g., no Ahrefs, SEMrush, Moz APIs)
- No paid performance APIs (use free Google PageSpeed Insights API)
- Self-hosted analysis logic

---

## 2. Architecture Design

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Frontend (Next.js)                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │
│  │  Domain     │  │  Analysis   │  │  Indicator  │  │  Suggestion │   │
│  │  Input      │──▶  Results    │──▶  Details    │──▶  Display    │   │
│  │  Form       │  │  Dashboard  │  │  Modal      │  │  Cards      │   │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          API Layer (Laravel)                            │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │  POST /api/v1/analyze                    GET /api/v1/analyses/{id}│  │
│  │  - Accept domain input                  - Retrieve cached result │  │
│  │  - Trigger analysis pipeline            - Download PDF report    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       Analysis Engine (Core)                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │
│  │  Domain     │  │  HTML       │  │  Indicator  │  │  Score      │   │
│  │  Validator  │──▶  Fetcher    │──▶  Runner     │──▶  Calculator │   │
│  │  Service    │  │  Service    │  │  (17 checks)│  │  Service    │   │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       Indicator Checkers (17)                           │
│  ┌────────────────┐ ┌────────────────┐ ┌────────────────┐              │
│  │ URLChecker     │ │ TitleChecker   │ │ MetaDescChecker│ ... (14 more)│
│  └────────────────┘ └────────────────┘ └────────────────┘              │
│                                                                         │
│  Each checker implements: IndicatorCheckerInterface                     │
│  - check(DOMDocument, string $url): IndicatorResult                     │
│  - getWeight(): float                                                   │
│  - getSuggestions(IndicatorResult): array                               │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Service Layer Design

#### Core Services

| Service | Responsibility |
|---------|----------------|
| `DomainValidatorService` | Normalize domain, validate accessibility, detect redirects |
| `HtmlFetcherService` | Fetch HTML with timeout, handle errors, cache responses |
| `AnalysisRunnerService` | Orchestrate all indicator checks, aggregate results |
| `ScoreCalculatorService` | Calculate weighted scores, determine overall rating |
| `SuggestionGeneratorService` | Generate actionable suggestions per indicator |

#### Indicator Checker Pattern

```php
interface IndicatorCheckerInterface
{
    /**
     * Unique identifier for the indicator
     */
    public function id(): string;
    
    /**
     * Human-readable name
     */
    public function name(): string;
    
    /**
     * Category for grouping (content, technical, accessibility, etc.)
     */
    public function category(): string;
    
    /**
     * Weight in overall score (0.0 - 1.0)
     */
    public function weight(): float;
    
    /**
     * Run the indicator check
     * @return IndicatorResult
     */
    public function check(Crawler $crawler, string $url, array $options = []): IndicatorResult;
    
    /**
     * Generate suggestions based on the result
     * @return Suggestion[]
     */
    public function getSuggestions(IndicatorResult $result): array;
}
```

### 2.3 Data Flow

```
1. User enters domain → Frontend validates format
2. POST /api/v1/analyze { domain: "example.com" }
3. DomainValidatorService normalizes → "https://example.com"
4. HtmlFetcherService fetches HTML (timeout: 30s)
5. AnalysisRunnerService runs all 17 checkers
6. Each checker returns IndicatorResult { score: 1-3, details: [], passed: bool }
7. ScoreCalculatorService computes weighted overall score
8. SuggestionGeneratorService creates actionable items
9. Response returned with full analysis object
10. Frontend displays dashboard with indicator breakdown
```

---

## 3. Database Schema

### 3.1 Analyses Table

```sql
CREATE TABLE seo_analyses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(64) NULL COMMENT 'Anonymous users tracked by session',
    domain VARCHAR(500) NOT NULL,
    normalized_url VARCHAR(500) NOT NULL,
    overall_score TINYINT UNSIGNED NOT NULL COMMENT '0-100',
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    analysis_data JSON NOT NULL COMMENT 'Full analysis results',
    checked_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL COMMENT 'Cache expiry (24 hours)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_domain (user_id, domain),
    INDEX idx_session_domain (session_id, domain),
    INDEX idx_domain_url (normalized_url),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);
```

### 3.2 Indicator Results (Embedded in JSON)

The `analysis_data` JSON column stores structured indicator results:

```json
{
  "url": "https://example.com",
  "analyzedAt": "2026-02-28T10:00:00Z",
  "overallScore": 78,
  "categoryScores": {
    "content": 82,
    "technical": 75,
    "accessibility": 70,
    "performance": 85
  },
  "indicators": [
    {
      "id": "url",
      "name": "URL Structure",
      "category": "technical",
      "score": 3,
      "weight": 0.08,
      "passed": true,
      "details": {
        "urlLength": 35,
        "hasKeywords": true,
        "isHttps": true,
        "noSpecialChars": true
      },
      "suggestions": []
    },
    {
      "id": "title_tag",
      "name": "Title Tag",
      "category": "content",
      "score": 2,
      "weight": 0.10,
      "passed": false,
      "details": {
        "title": "Example Domain",
        "length": 14,
        "hasKeywords": false,
        "isOptimalLength": false
      },
      "suggestions": [
        {
          "type": "warning",
          "message": "Title tag is too short (14 chars). Optimal length is 50-60 characters.",
          "action": "Add more descriptive text including target keywords."
        }
      ]
    }
  ],
  "summary": {
    "passed": 12,
    "warnings": 3,
    "failed": 2
  }
}
```

---

## 4. Indicator Specifications

### 4.1 Scoring System

Each indicator is scored on a 1-3 star scale:

| Score | Label | Description |
|-------|-------|-------------|
| 3 | Excellent | All criteria met, no issues |
| 2 | Good | Minor issues, room for improvement |
| 1 | Poor | Significant issues, immediate attention needed |
| 0 | N/A | Indicator not applicable (e.g., no images on page) |

### 4.2 Indicator Weights

Based on SEO impact and user priorities:

| Indicator | ID | Weight | Category |
|-----------|-------|--------|----------|
| Title Tag | `title_tag` | 0.10 | Content |
| Meta Description | `meta_description` | 0.08 | Content |
| H1 Headings | `h1_headings` | 0.08 | Content |
| URL Structure | `url` | 0.08 | Technical |
| Canonical URL | `canonical` | 0.06 | Technical |
| Robots.txt | `robots_txt` | 0.06 | Technical |
| Sitemap.xml | `sitemap_xml` | 0.05 | Technical |
| 404 Page | `404_page` | 0.04 | Technical |
| Mobile Friendly | `mobile_friendly` | 0.08 | Technical |
| Page Speed | `page_speed` | 0.10 | Performance |
| Image Alt Text | `image_alt` | 0.05 | Accessibility |
| ARIA Labels | `aria` | 0.04 | Accessibility |
| Links | `links` | 0.05 | Content |
| Filenames | `filenames` | 0.03 | Content |
| Language | `language` | 0.03 | Technical |
| Favicon | `favicon` | 0.02 | Technical |
| Social Media Tags | `social_meta` | 0.05 | Content |
| **Total** | | **1.00** | |

### 4.3 Individual Indicator Logic

#### 4.3.1 URL Structure (`url`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| HTTPS | URL uses HTTPS protocol |
| URL Length | ≤ 75 characters |
| Keywords in URL | Contains relevant keywords |
| No special characters | Only alphanumeric, hyphens, underscores |
| No dynamic parameters | No `?` or `&` (or minimal params) |
| Readable structure | Path segments are meaningful |

**Scoring:**
- 3 stars: All criteria met
- 2 stars: HTTPS + most criteria, minor issues
- 1 star: HTTP or major issues (special chars, very long)

---

#### 4.3.2 Title Tag (`title_tag`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | Title tag present |
| Length | 50-60 characters optimal |
| Keywords | Contains target keywords |
| Unique | Not duplicate (can't verify cross-page in MVP) |

**Scoring:**
- 3 stars: 50-60 chars, keywords present
- 2 stars: Exists but length issues (40-50 or 60-70)
- 1 star: Missing, too short (<40), or too long (>70)

---

#### 4.3.3 Meta Description (`meta_description`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | Meta description tag present |
| Length | 150-160 characters optimal |
| Keywords | Contains relevant keywords |
| Compelling | Not generic (hard to verify automatically) |

**Scoring:**
- 3 stars: 150-160 chars, keywords present
- 2 stars: Exists but length issues (120-150 or 160-170)
- 1 star: Missing, too short (<120), or too long (>170)

---

#### 4.3.4 Robots.txt (`robots_txt`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | `/robots.txt` accessible |
| Valid format | Parseable directives |
| Allows indexing | Not blocking all crawlers |
| Sitemap reference | Contains sitemap URL |

**Scoring:**
- 3 stars: Exists, valid, allows indexing, has sitemap
- 2 stars: Exists and valid, minor issues
- 1 star: Missing, invalid, or blocking important paths

---

#### 4.3.5 Sitemap.xml (`sitemap_xml`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | `/sitemap.xml` accessible |
| Valid XML | Parseable XML structure |
| Contains URL | Analyzed URL present |
| Multiple sitemaps | Sitemap index supported |

**Scoring:**
- 3 stars: Valid sitemap with URL present
- 2 stars: Sitemap exists but URL not found
- 1 star: Missing or invalid XML

---

#### 4.3.6 H1 Headings (`h1_headings`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | At least one H1 tag |
| Single H1 | Exactly one H1 tag |
| Keywords | H1 contains relevant keywords |
| Length | 20-70 characters optimal |

**Scoring:**
- 3 stars: Single H1 with keywords, optimal length
- 2 stars: Multiple H1s or missing keywords
- 1 star: No H1 or too many H1s (>3)

---

#### 4.3.7 Filenames (`filenames`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| SEO-friendly | Lowercase, hyphens, no spaces |
| Keywords | Relevant keywords in filenames |
| Consistent | Naming convention followed |

**Scoring:**
- 3 stars: All filenames SEO-friendly
- 2 stars: Most filenames optimized
- 1 star: Many files with poor naming

---

#### 4.3.8 Image Alt Text (`image_alt`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| All images have alt | Every `<img>` has alt attribute |
| Descriptive | Alt text is meaningful (not empty, not generic) |
| Keywords | Relevant keywords where appropriate |

**Scoring:**
- 3 stars: All images have descriptive alt text
- 2 stars: Most images have alt text
- 1 star: Many images missing alt text

---

#### 4.3.9 Links (`links`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Internal links | Links to other pages on site |
| External links | Appropriate external references |
| Broken links | No broken links detected (sample check) |
| Nofollow usage | Proper use of rel="nofollow" |

**Scoring:**
- 3 stars: Good internal/external ratio, no broken links
- 2 stars: Minor link issues
- 1 star: Many broken or missing internal links

---

#### 4.3.10 404 Page (`404_page`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Custom 404 | Returns 404 status with custom page |
| User-friendly | Contains helpful navigation |
| Design consistent | Matches site branding |

**Scoring:**
- 3 stars: Custom 404 with helpful content
- 2 stars: Custom 404 but basic
- 1 star: No custom 404 or wrong status code

---

#### 4.3.11 Mobile Friendly (`mobile_friendly`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Viewport meta | `<meta name="viewport">` present |
| Responsive design | CSS media queries detected |
| Touch-friendly | Buttons/links appropriately sized |
| No horizontal scroll | Content fits viewport |

**Scoring:**
- 3 stars: Full responsive design detected
- 2 stars: Viewport set, basic responsiveness
- 1 star: Missing viewport or not mobile-friendly

---

#### 4.3.12 Language (`language`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Lang attribute | `<html lang="xx">` present |
| Valid code | Valid ISO language code |
| Content matches | Declared lang matches content (hard to verify) |

**Scoring:**
- 3 stars: Valid lang attribute present
- 2 stars: Lang present but potentially incorrect
- 1 star: Missing lang attribute

---

#### 4.3.13 Favicon (`favicon`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | favicon.ico or link rel="icon" |
| Multiple sizes | Various sizes for different devices |
| Valid format | Image file accessible |

**Scoring:**
- 3 stars: Favicon with multiple sizes
- 2 stars: Basic favicon present
- 1 star: No favicon

---

#### 4.3.14 Canonical URL (`canonical`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Exists | `<link rel="canonical">` present |
| Valid URL | Points to valid, accessible URL |
| Self-referencing | Canonical matches current URL (for main page) |

**Scoring:**
- 3 stars: Valid canonical URL present
- 2 stars: Canonical present but issues
- 1 star: Missing canonical or invalid

---

#### 4.3.15 Social Media Meta Tags (`social_meta`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Open Graph | og:title, og:description, og:image present |
| Twitter Cards | twitter:card, twitter:title, etc. |
| Valid images | Images accessible and proper dimensions |

**Scoring:**
- 3 stars: Complete OG + Twitter cards
- 2 stars: Partial social tags
- 1 star: No social meta tags

---

#### 4.3.16 ARIA Labels (`aria`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Landmark roles | Main, nav, aside roles present |
| Form labels | All form inputs have associated labels |
| Interactive elements | Buttons and links have accessible names |
| Alt text for icons | Icon-only buttons have aria-label |

**Scoring:**
- 3 stars: Good ARIA implementation
- 2 stars: Partial ARIA usage
- 1 star: Missing ARIA labels

---

#### 4.3.17 Page Speed (`page_speed`)

**Check Criteria:**
| Criterion | Pass Condition |
|-----------|----------------|
| Response time | Initial HTML response < 2s |
| Page size | Total page size < 2MB |
| Render-blocking | Minimal render-blocking resources |
| Free API check | Google PageSpeed Insights API (free tier) |

**Scoring:**
- 3 stars: PageSpeed score > 80 or response < 1s
- 2 stars: PageSpeed score 60-80 or response 1-2s
- 1 star: PageSpeed score < 60 or response > 2s

**Note:** Uses free Google PageSpeed Insights API (250k requests/day free tier).

---

## 5. API Endpoints

### 5.1 Create Analysis

```http
POST /api/v1/analyze
Content-Type: application/json

{
  "domain": "example.com"
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "domain": "example.com",
    "normalizedUrl": "https://example.com",
    "status": "pending",
    "message": "Analysis started. Poll for results."
  }
}
```

### 5.2 Get Analysis Results

```http
GET /api/v1/analyses/{id}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "domain": "example.com",
    "overallScore": 78,
    "status": "completed",
    "indicators": [ /* ... */ ],
    "summary": {
      "passed": 12,
      "warnings": 3,
      "failed": 2
    },
    "analyzedAt": "2026-02-28T10:00:00Z",
    "expiresAt": "2026-03-01T10:00:00Z"
  }
}
```

### 5.3 Get Analysis by Domain (Cached)

```http
GET /api/v1/analyze?domain=example.com
```

Returns cached analysis if available and not expired (24h TTL).

### 5.4 Download PDF Report

```http
GET /api/v1/analyses/{id}/pdf
```

Returns PDF document with analysis results.

---

## 6. Security Considerations

### 6.1 SSRF Protection

The HTML fetcher must prevent Server-Side Request Forgery:

| Protection | Implementation |
|------------|----------------|
| Private IP blocking | Block 10.x, 172.16-31.x, 192.168.x, 127.x |
| Localhost blocking | Block localhost, 127.0.0.1 |
| Cloud metadata | Block 169.254.169.254 |
| Internal domains | Block domains containing "internal", "local" |
| Protocol whitelist | Only http:// and https:// allowed |
| File protocol | Block file:// |

**Reference:** Use existing `URLValidationService` from FEAT-016.

### 6.2 Rate Limiting

| Limit Type | Value | Scope |
|------------|-------|-------|
| Anonymous users | 3 analyses/day | Per IP |
| Authenticated users | 10 analyses/day | Per user |
| Premium (future) | Unlimited | Per user |

**Implementation:** Use existing rate limiting from FEAT-016.

### 6.3 Input Validation

| Field | Validation |
|-------|------------|
| domain | Valid domain format, max 255 chars |
| No path/query | Only domain accepted (path added by system) |
| Normalized | Forced to https:// (http fallback) |

---

## 7. Performance Considerations

### 7.1 Caching Strategy

| Cache Type | TTL | Purpose |
|------------|-----|---------|
| HTML Response | 1 hour | Avoid repeated fetches |
| Analysis Result | 24 hours | Serve cached results |
| PageSpeed API | 24 hours | Reduce API calls |

### 7.2 Optimization Techniques

1. **Parallel indicator checks**: Run independent checks concurrently
2. **Lazy loading**: Defer PageSpeed API call until needed
3. **Memory optimization**: Stream large HTML responses
4. **Connection pooling**: Reuse HTTP connections

### 7.3 Timeout Configuration

| Operation | Timeout |
|-----------|---------|
| HTML fetch | 30 seconds |
| PageSpeed API | 20 seconds |
| Individual indicator | 5 seconds |
| Total analysis | 60 seconds |

---

## 8. Error Handling

### 8.1 Error Categories

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `INVALID_DOMAIN` | 400 | Domain format invalid |
| `DOMAIN_UNREACHABLE` | 502 | Could not connect to domain |
| `SSL_ERROR` | 502 | SSL certificate issue |
| `TIMEOUT` | 504 | Request timed out |
| `RATE_LIMITED` | 429 | Too many analyses |
| `ANALYSIS_FAILED` | 500 | Internal error during analysis |

### 8.2 Graceful Degradation

- If individual indicator fails: Log error, set score to 0, continue analysis
- If PageSpeed API fails: Use fallback metrics (response time only)
- If robots.txt/sitemap.xml not found: Mark as missing, continue

---

## 9. Frontend Components

### 9.1 Page Structure

```
/analyze                    - Landing page with domain input
/analyze?domain=example.com - Results page (or trigger new analysis)
/analysis/[id]              - Shareable results page
```

### 9.2 UI Components

| Component | Description |
|-----------|-------------|
| `DomainInputForm` | Input field with validation, submit button |
| `ScoreGauge` | Circular gauge showing 0-100 score |
| `IndicatorCard` | Individual indicator with score, details |
| `IndicatorList` | Filterable/sortable list of indicators |
| `SuggestionPanel` | Expandable suggestions per indicator |
| `ShareButton` | Copy link / social share |
| `DownloadPDF` | Export analysis as PDF |

### 9.3 Visual Design

- Color coding: Green (3), Yellow (2), Red (1), Gray (0/N/A)
- Score ranges: 80-100 (Green), 60-79 (Yellow), 0-59 (Red)
- Responsive layout for mobile/tablet/desktop

---

## 10. Task Breakdown Summary

### Phase 1: Infrastructure
- TASK-18-1: Database migration (seo_analyses table)
- TASK-18-2: SEOAnalysis model and repository
- TASK-18-3: IndicatorCheckerInterface and base classes

### Phase 2: Core Services
- TASK-18-4: DomainValidatorService
- TASK-18-5: HtmlFetcherService (with SSRF protection)
- TASK-18-6: AnalysisRunnerService
- TASK-18-7: ScoreCalculatorService
- TASK-18-8: SuggestionGeneratorService

### Phase 3: Indicator Checkers (17 tasks)
- TASK-18-09 through TASK-18-25: Individual indicator implementations

### Phase 4: API Layer
- TASK-18-26: AnalysisController (create, get, list)
- TASK-18-27: PDF export integration
- TASK-18-28: Rate limiting middleware

### Phase 5: Frontend
- TASK-18-29: DomainInputForm component
- TASK-18-30: Results dashboard page
- TASK-18-31: Indicator detail components
- TASK-18-32: PDF download functionality

### Phase 6: Testing & Polish
- TASK-18-33: Unit tests for services
- TASK-18-34: Integration tests for API
- TASK-18-35: Frontend tests
- TASK-18-36: Performance optimization

---

## 11. Dependencies

### Internal Dependencies
| Feature/Task | Description |
|--------------|-------------|
| FEAT-016 | Tools infrastructure (HtmlParserService, URLValidationService) |
| FEAT-004 | PDF export functionality |
| FEAT-006 | Rate limiting (if implemented) |

### External Dependencies
| Service | Purpose | Free Tier |
|---------|---------|-----------|
| Google PageSpeed Insights API | Performance metrics | 250k requests/day |

---

## 12. Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| SSRF vulnerability | Medium | High | Use proven URLValidationService from FEAT-016 |
| HTML parsing errors | Medium | Medium | Graceful degradation, comprehensive error handling |
| PageSpeed API limits | Low | Low | Cache responses, fallback to response time |
| Performance issues | Medium | Medium | Parallel processing, caching, timeouts |
| False positives in indicators | Medium | Medium | Conservative scoring, clear explanations |

---

## Comments

### Architect Notes (2026-02-28)
- This is a foundational feature - prioritize reliability over features
- Start with MVP: fewer indicators if needed, expand later
- Consider queue-based processing for high volume (post-MVP)
- PDF export can reuse existing infrastructure from FEAT-004

### SpecSystemAnalyst (2026-02-28)
**Task Breakdown Complete:** Created 36 tasks across 6 phases.

**Task Summary:**
- Phase 1 (Infrastructure): TASK-18-1 to TASK-18-3 (3 tasks)
- Phase 2 (Core Services): TASK-18-4 to TASK-18-8 (5 tasks)
- Phase 3 (Indicator Checkers): TASK-18-9 to TASK-18-25 (17 tasks)
- Phase 4 (API Layer): TASK-18-26 to TASK-18-28 (3 tasks)
- Phase 5 (Frontend): TASK-18-29 to TASK-18-32 (4 tasks)
- Phase 6 (Testing & Polish): TASK-18-33 to TASK-18-36 (4 tasks)

**High Risk Tasks Identified:**
- TASK-18-5: HtmlFetcherService (SSRF vulnerability potential)
- TASK-18-17: LinksChecker (external link checks)
- TASK-18-25: PageSpeedChecker (external API dependency)

**Critical Path:** TASK-18-1 → TASK-18-2 → TASK-18-3 → TASK-18-4 → TASK-18-5 → TASK-18-6 → TASK-18-26 → TASK-18-30

**Handoff to ProjectManager:** Ready for prioritization. Foundation tasks (1-3) should be prioritized first, followed by core services, then indicator checkers can be developed in parallel.

---

## Changelog

| Date | Agent | Action |
|------|-------|--------|
| 2026-02-28 | Architect | Initial specification created |

---

*Specification created by Architect agent from FEAT-018.*
