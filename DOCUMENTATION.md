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
