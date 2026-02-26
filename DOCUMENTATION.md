# DOCUMENTATION.md

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
