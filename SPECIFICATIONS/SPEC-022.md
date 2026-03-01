# SPEC-022: Headless Crawler Service (JS-Rendered HTML)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Related Feature
FEAT-022: Headless Crawler Service (JS-Rendered HTML)

---

## 1. Overview

### 1.1 Purpose
Implement a self-hosted crawler service that renders JavaScript-heavy pages using a headless browser, enabling accurate SEO analysis for modern SPAs and JS-rendered content. The service will extract rendered HTML and structured text from target URLs with configurable depth limits and content caps.

### 1.2 Problem Statement
Current SEO analysis tools rely on static HTML fetching, which fails to capture content rendered by JavaScript. This leads to inaccurate SEO audits for modern web applications built with React, Vue, Angular, and other JS frameworks.

### 1.3 Goals
- Provide accurate JS-rendered HTML extraction
- Support configurable crawl depth (L0/L1/L2)
- Enforce content size limits to prevent resource exhaustion
- Output both raw rendered HTML and structured text for downstream analysis

---

## 2. Architecture

### 2.1 Technology Selection

| Component | Technology | Rationale |
|-----------|------------|-----------|
| Headless Browser | Playwright | FREE, already used in OpenClaw ecosystem, robust JS rendering, multi-browser support |
| Queue System | Laravel Jobs + SQLite | Leverages existing infrastructure (TASK-1-10), MVP-friendly |
| Content Extraction | Readability.js / Custom parser | FREE, Mozilla's Readability for structured text |
| Rate Limiting | Per-IP + Per-Domain | Prevent abuse, protect target servers |

### 2.2 System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    CrawlerService                            │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ URL Queue   │  │ Playwright  │  │ Content Processor   │  │
│  │ Manager     │→ │ Renderer    │→ │ (HTML + Text)       │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│         ↓                                    ↓               │
│  ┌─────────────┐                    ┌─────────────────────┐  │
│  │ Depth       │                    │ Output Storage      │  │
│  │ Controller  │                    │ (filesystem/DB)     │  │
│  └─────────────┘                    └─────────────────────┘  │
│         ↓                                                   │
│  ┌─────────────┐                                            │
│  │ Size        │                                            │
│  │ Limiter     │                                            │
│  └─────────────┘                                            │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Data Flow

1. **Request Initiation**: API endpoint receives crawl request with URL and options
2. **URL Validation**: SSRF protection, domain allowlist/blocklist checks
3. **Queue Dispatch**: Job added to crawler queue with priority
4. **Browser Rendering**: Playwright navigates to URL, waits for JS execution
5. **Content Extraction**: HTML extracted after render, structured text parsed
6. **Depth Processing**: Extract links, queue sub-pages if within depth limit
7. **Size Enforcement**: Track cumulative content size, stop at 3MB cap
8. **Output Storage**: Store rendered HTML + structured text

---

## 3. API Design

### 3.1 Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/crawler/crawl` | Initiate crawl job |
| GET | `/api/v1/crawler/jobs/{jobId}` | Get crawl job status |
| GET | `/api/v1/crawler/jobs/{jobId}/content` | Get crawled content |

### 3.2 Crawl Request

**POST `/api/v1/crawler/crawl`**

```json
{
  "url": "https://example.com",
  "depth": 1,
  "options": {
    "waitForSelector": null,
    "waitTime": 2000,
    "userAgent": "SEOIndicator/1.0 Crawler",
    "includeImages": false,
    "followRedirects": true,
    "maxRedirects": 5
  }
}
```

**Parameters:**

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `url` | string | required | Starting URL to crawl |
| `depth` | int | 0 | Crawl depth: 0 (page only), 1 (sub-pages), 2 (sub-sub-pages) |
| `waitForSelector` | string? | null | CSS selector to wait for before extraction |
| `waitTime` | int | 2000 | Additional wait time in ms after page load |
| `userAgent` | string | SEOIndicator/1.0 | Custom user agent string |
| `includeImages` | bool | false | Whether to extract image data |
| `followRedirects` | bool | true | Follow HTTP redirects |
| `maxRedirects` | int | 5 | Maximum redirects to follow |

**Response (202 Accepted):**

```json
{
  "jobId": "crawl-abc123",
  "status": "queued",
  "url": "https://example.com",
  "depth": 1,
  "estimatedTime": "30s"
}
```

### 3.3 Job Status

**GET `/api/v1/crawler/jobs/{jobId}`**

**Response:**

```json
{
  "jobId": "crawl-abc123",
  "status": "completed",
  "url": "https://example.com",
  "depth": 1,
  "pagesCrawled": 5,
  "totalSize": 1024000,
  "startedAt": "2026-02-28T10:00:00Z",
  "completedAt": "2026-02-28T10:00:45Z",
  "contentUrl": "/api/v1/crawler/jobs/crawl-abc123/content"
}
```

**Job Statuses:**
- `queued` - Job in queue, waiting for worker
- `running` - Currently crawling
- `completed` - Successfully finished
- `failed` - Error during crawl
- `cancelled` - User cancelled or timeout

### 3.4 Content Retrieval

**GET `/api/v1/crawler/jobs/{jobId}/content`**

**Response:**

```json
{
  "jobId": "crawl-abc123",
  "pages": [
    {
      "url": "https://example.com",
      "depth": 0,
      "title": "Example Domain",
      "renderedHtml": "<!DOCTYPE html>...",
      "structuredText": "Example Domain\nThis domain is for use...",
      "meta": {
        "description": "Example domain for documentation",
        "robots": "index, follow",
        "canonical": "https://example.com"
      },
      "links": [
        {"href": "https://example.com/about", "text": "About", "internal": true},
        {"href": "https://external.com", "text": "External", "internal": false}
      ],
      "size": 204800,
      "crawledAt": "2026-02-28T10:00:30Z"
    }
  ],
  "summary": {
    "totalPages": 5,
    "totalSize": 1024000,
    "depthDistribution": {"0": 1, "1": 4}
  }
}
```

---

## 4. Security Implementation

### 4.1 SSRF Protection

| Protection | Implementation |
|------------|----------------|
| Localhost blocked | `localhost`, `127.*`, `::1` blocked |
| Private IPs blocked | `10.*`, `172.16-31.*`, `192.168.*` blocked |
| Cloud metadata blocked | `169.254.169.254` blocked |
| File protocol blocked | `file://`, `ftp://` protocols blocked |
| Internal domains | Domains matching `*.internal`, `*.local` blocked |
| DNS rebinding | Resolve DNS first, validate resolved IP |

### 4.2 Rate Limiting

| Limit Type | Default | Description |
|------------|---------|-------------|
| Per-User Daily | 10 crawls | Max crawl jobs per user per 24 hours |
| Per-Domain | 1 request/second | Protect target servers |
| Concurrent Jobs | 3 | Max concurrent crawl jobs per user |
| Job Timeout | 5 minutes | Maximum job duration |

### 4.3 Resource Limits

| Limit | Value | Rationale |
|-------|-------|-----------|
| Max Content Size | 3MB | Prevent memory exhaustion |
| Max Pages | 50 | Prevent runaway crawls |
| Page Timeout | 30s | Per-page render timeout |
| Max Response Size | 10MB | Individual page size limit |
| Browser Memory | 512MB | Per-browser instance limit |

### 4.4 Authorization

- All endpoints require authentication (Bearer token)
- Users can only access their own crawl jobs
- Site ownership verified via project context

---

## 5. Database Schema

### 5.1 Crawl Jobs Table

```sql
CREATE TABLE crawl_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    job_id VARCHAR(64) NOT NULL UNIQUE,
    url VARCHAR(2083) NOT NULL,
    status ENUM('queued', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'queued',
    depth TINYINT UNSIGNED DEFAULT 0,
    options JSON,
    pages_crawled INT UNSIGNED DEFAULT 0,
    total_size BIGINT UNSIGNED DEFAULT 0,
    error TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    
    INDEX idx_crawl_jobs_user_id (user_id),
    INDEX idx_crawl_jobs_status (status),
    INDEX idx_crawl_jobs_job_id (job_id),
    INDEX idx_crawl_jobs_expires_at (expires_at)
);
```

### 5.2 Crawl Pages Table

```sql
CREATE TABLE crawl_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crawl_job_id BIGINT UNSIGNED NOT NULL,
    url VARCHAR(2083) NOT NULL,
    depth TINYINT UNSIGNED NOT NULL,
    title VARCHAR(500) NULL,
    rendered_html MEDIUMTEXT NULL,
    structured_text MEDIUMTEXT NULL,
    meta JSON NULL,
    links JSON NULL,
    size INT UNSIGNED DEFAULT 0,
    crawled_at TIMESTAMP NULL,
    error TEXT NULL,
    
    FOREIGN KEY (crawl_job_id) REFERENCES crawl_jobs(id) ON DELETE CASCADE,
    
    INDEX idx_crawl_pages_job_id (crawl_job_id),
    INDEX idx_crawl_pages_depth (crawl_job_id, depth)
);
```

### 5.3 Content Storage

For MVP, rendered HTML and structured text stored in database with MEDIUMTEXT columns.
Future optimization: Move large content to file storage (S3-compatible or local filesystem).

---

## 6. Service Layer

### 6.1 CrawlerService

**Methods:**

| Method | Description |
|--------|-------------|
| `createJob(userId, url, options): CrawlJob` | Create and queue crawl job |
| `getJob(jobId): ?CrawlJob` | Get job by ID |
| `processJob(jobId): void` | Main crawl execution (called by worker) |
| `cancelJob(jobId): bool` | Cancel running job |
| `cleanup(): void` | Remove expired jobs and content |

### 6.2 PlaywrightRendererService

**Methods:**

| Method | Description |
|--------|-------------|
| `render(url, options): RenderResult` | Render page with Playwright |
| `extractContent(page): ExtractedContent` | Extract HTML and text from page |
| `extractLinks(page): Link[]` | Extract all links from page |
| `close(): void` | Cleanup browser instances |

### 6.3 ContentProcessorService

**Methods:**

| Method | Description |
|--------|-------------|
| `extractStructuredText(html): string` | Convert HTML to readable text |
| `extractMetadata(html): MetaData` | Extract meta tags, title, etc. |
| `calculateSize(content): int` | Calculate content size in bytes |

---

## 7. Queue Integration

### 7.1 Job Flow

1. `CrawlJob` created with status `queued`
2. Job dispatched to queue (uses existing JobQueue from TASK-1-10)
3. `CrawlWorker` picks up job, sets status `running`
4. Worker calls `CrawlerService::processJob()`
5. On success: status `completed`, store results
6. On failure: status `failed`, store error message

### 7.2 Priority Levels

| Priority | Use Case |
|----------|----------|
| `high` | Manual user-initiated crawls |
| `normal` | Scheduled/system crawls |
| `low` | Background re-crawls |

---

## 8. Task Breakdown

### Phase 1: Core Infrastructure
- TASK-22-1: Database migrations (crawl_jobs, crawl_pages tables)
- TASK-22-2: CrawlJob and CrawlPage models with relationships
- TASK-22-3: CrawlerService skeleton with job management

### Phase 2: Rendering Engine
- TASK-22-4: Playwright integration and configuration
- TASK-22-5: PlaywrightRendererService implementation
- TASK-22-6: Content extraction (HTML + structured text)

### Phase 3: Crawl Logic
- TASK-22-7: URL validation and SSRF protection
- TASK-22-8: Depth controller for L0/L1/L2 crawling
- TASK-22-9: Size limiter (3MB cap enforcement)
- TASK-22-10: Link extraction and queue management

### Phase 4: API Layer
- TASK-22-11: CrawlController with endpoints
- TASK-22-12: Request validation and authorization
- TASK-22-13: Rate limiting implementation
- TASK-22-14: Content retrieval endpoints

### Phase 5: Worker & Queue
- TASK-22-15: CrawlWorker implementation
- TASK-22-16: Queue integration with existing JobQueue
- TASK-22-17: Job timeout and cancellation handling

### Phase 6: Testing & Documentation
- TASK-22-18: Unit tests for services
- TASK-22-19: Feature tests for API endpoints
- TASK-22-20: Integration tests for full crawl flow
- TASK-22-21: DOCUMENTATION.md update

---

## 9. Dependencies

### 9.1 External Dependencies

| Dependency | Version | Purpose |
|------------|---------|---------|
| playwright/browsers | ^1.40 | Headless browser automation |
| symfony/dom-crawler | ^6.0 | HTML parsing (already installed) |

### 9.2 Internal Dependencies

| Task | Dependency | Status |
|------|------------|--------|
| Queue System | TASK-1-10 | ✅ Complete |
| URL Validation | TASK-16-1 | ✅ Complete |
| Project Context | TASK-8-4 | ✅ Complete |

---

## 10. Risk Assessment

### 10.1 Identified Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| SSRF attacks | Medium | High | Comprehensive URL validation, DNS rebinding protection |
| Resource exhaustion | Medium | High | Size limits, timeout enforcement, rate limiting |
| Target server overload | Medium | Medium | Per-domain rate limiting, crawl delays |
| Browser memory leaks | Medium | Medium | Browser instance pooling, periodic restarts |
| Legal/Crawling ToS | Low | Medium | robots.txt respect, crawl delays, user agent identification |

### 10.2 Rollback Plan

- Feature behind feature flag
- Can disable crawler via config
- Jobs stored in database, can be cleared
- Browser processes can be killed without data loss

---

## 11. Performance Considerations

### 11.1 Expected Performance

| Metric | Target |
|--------|--------|
| Single page render | < 5 seconds |
| Full L2 crawl (50 pages) | < 3 minutes |
| Memory per browser instance | < 512MB |
| Queue processing latency | < 1 second |

### 11.2 Optimization Strategies

- Browser instance pooling (future)
- Parallel page processing (depth-limited)
- Content compression for storage
- Incremental content streaming (future)

---

## 12. Comments

### Architect Notes
- 2026-02-28: Initial specification created
- Risk level confirmed as HIGH due to external resource fetching and potential SSRF
- Using existing Playwright infrastructure from OpenClaw ecosystem
- Integrated with existing JobQueue (TASK-1-10) for consistency

### SpecSystemAnalyst (2026-03-01 22:20 GMT+2)
- Task breakdown completed: 21 tasks created (TASK-22-1 through TASK-22-21)
- **Phase 1 (Core Infrastructure):** TASK-22-1 to TASK-22-3 (Database, Models, Service skeleton)
- **Phase 2 (Rendering Engine):** TASK-22-4 to TASK-22-6 (Playwright, Renderer, Content Processor)
- **Phase 3 (Crawl Logic):** TASK-22-7 to TASK-22-10 (URL validation, Depth controller, Size limiter, Link queue)
- **Phase 4 (API Layer):** TASK-22-11 to TASK-22-14 (Endpoints, Validation, Rate limiting, Content retrieval)
- **Phase 5 (Worker & Queue):** TASK-22-15 to TASK-22-17 (Worker, Queue integration, Timeout handling)
- **Phase 6 (Testing & Documentation):** TASK-22-18 to TASK-22-21 (Unit tests, Feature tests, Integration tests, Docs)
- Key decisions preserved from specification:
  - Playwright for headless browser (FREE, already in ecosystem)
  - SQLite/database queue (leverages existing JobQueue)
  - Comprehensive SSRF protection (localhost, private IPs, cloud metadata)
  - 3MB content cap with size limiter service
  - Rate limiting: 10/day per user, 3 concurrent, 1/sec per domain
- Handed off to ProjectManager for prioritization

---

## 13. Approval

| Decision | Status | Date |
|----------|--------|------|
| Technical Review | Pending | - |
| Security Review | Pending | - |
| Final Approval | Pending | - |

---

*Specification created by Architect agent for FEAT-022*
*Last updated: 2026-02-28*
