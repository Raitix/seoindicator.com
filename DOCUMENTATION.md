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
## Report Generation API (TASK-4-8)

### Endpoint:
`POST /api/v1/sites/:siteId/reports/generate`

### Description:
Triggers the generation of site analysis reports (PDF/CSV formats). Supports asynchronous execution and status tracking for scalability.

### Request:
- **Method**: POST
- **Endpoint**: /api/v1/sites/{siteId}/reports/generate
- **Headers:**
  - Authorization: `Bearer <token>`
- **Request Body:**
  ```json
  {
    "name": "Traffic Analysis Report",
    "type": "analysis",
    "format": "pdf",
    "dateRange": ["2026-01-01", "2026-01-31"],
    "compareRange": ["2025-01-01", "2025-01-31"],
    "sections": ["traffic_sources", "conversion_rates"]
  }
  ```
  - `name`: (required) Report title.
  - `type`: (required) Type of report.
  - `format`: (required) Format (`pdf`, `csv`).
  - `dateRange`: ISO dates for data range.
  - `compareRange`: Optional, comparison timeframe.
  - `sections`: Optional, array of report sections.

### Response:
- **Success (202):**
  ```json
  {
    "reportId": "UUID",
    "status": "generating"
  }
  ```
  - `reportId`: Unique identifier for the report.
  - `status`: Initial processing state.

- **Error (4xx/5xx):**
  ```json
  {
    "error": "ValidationException",
    "message": "Invalid date range."
  }
  ```
  - Structured and informative error message.

### Dependencies:
- Data Aggregation Module: Consolidates analytics data.
- CSV/Excel Exporters: For generating tabular data
- Synchronous execution designed for MVP staging.

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
