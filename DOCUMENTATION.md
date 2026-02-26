# DOCUMENTATION.md

---
## Database Schema: Keywords & Rankings (TASK-1-1)

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
