# DOCUMENTATION.md — Production Functionality

**Source of truth for existing prod features.**

---

## Tech Stack

### Frontend
- **Framework**: Next.js 16+ with TypeScript
- **Styling**: Tailwind CSS v4
- **UI Components**: Shadcn/UI
- **Build Output**: Static export (for shared hosting compatibility)

### Backend
- **Framework**: PHP Laravel 12+ (when Composer available)
- **Fallback**: Simple PHP API for development
- **Database**: MySQL/MariaDB
- **ORM**: Eloquent (Laravel) or PDO (fallback)

### Development Tools
- **Package Manager**: npm (frontend), Composer (backend)
- **Runtime**: Node.js v23+, PHP 8.2+
- **Server**: Next.js dev server (frontend), PHP built-in or Apache (backend)

---

## Production Constraints

### Hostgator Shared Hosting Limitations

> **IMPORTANT**: Production is on Hostgator shared hosting (not VPS). This restricts capabilities.

| Feature | Availability | Workaround |
|---------|-------------|------------|
| **Next.js SSR** | ❌ No | Use static export (`next build && next export`) |
| **WebSockets** | ❌ No | Use polling or server-sent events |
| **Redis Caching** | ❌ No | Use file caching or database caching |
| **Node.js Runtime** | ❌ No | Static HTML/CSS/JS only |
| **Custom PHP Extensions** | ⚠️ Limited | Use standard extensions only |
| **Shell Access** | ⚠️ Limited | Use cPanel/cron jobs |
| **Composer** | ✅ Yes | Install dependencies locally, upload vendor/ |

### Deployment Strategy
1. Build Next.js static export: `npm run build && npx next export`
2. Upload `out/` folder to public_html
3. Upload Laravel backend with `vendor/` folder pre-installed
4. Configure `.env` for production database
5. Run migrations via cPanel or SSH if available

---

## Local Development Setup

### Prerequisites
- Node.js 18+ (v23 recommended)
- **XAMPP** (Apache + MySQL + PHP 8.2+)
- Composer (requires SSL to work)

### Required Services (Auto-Start via BOOT.md)

> **Automatic Startup:** All services start automatically when OpenClaw gateway starts via the `boot-md` hook. See `workspace/BOOT.md` for configuration.

**1. MySQL** - Started automatically via XAMPP `mysql_start.bat`

**2. Backend** - Started automatically via `php artisan serve` on port 8000

**3. Frontend** - Started automatically via `npm run dev` on port 3000

**Manual Start (if needed):**
```bash
# MySQL
C:\xampp\mysql_start.bat

# Backend
cd PROJECT-SEOINDICATOR/backend
php artisan serve

# Frontend
cd PROJECT-SEOINDICATOR/frontend
npm run dev
```

**phpMyAdmin** (optional, for DB UI):
- Start Apache in XAMPP Control Panel
- Access at http://localhost/phpmyadmin

### Frontend Setup
```bash
cd PROJECT-SEOINDICATOR/frontend
npm install
npm run dev
# Opens at http://localhost:3000
```

### Backend Setup
```bash
cd PROJECT-SEOINDICATOR/backend

# Install dependencies (requires SSL to work)
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
# Opens at http://localhost:8000
```

### Database Setup

**Database already created:** `seoindicator` (utf8mb4)

**Credentials:** See `~/.openclaw/secrets/seoindicator.env`

**phpMyAdmin access:**
1. Start Apache in XAMPP Control Panel
2. Go to http://localhost/phpmyadmin
3. Select `seoindicator` database

**Migrations already run:**
```bash
php artisan migrate
# Creates: users, cache, jobs tables
```

---

## Current Production Features

### Implemented
- **TASK-12-3 Update**: Added `gsc_query_match` column to the `keywords` table for fuzzy matching with GSC query data. Column is nullable and indexed for efficient queries.
- ✅ Next.js frontend with Tailwind CSS
- ✅ Shadcn/UI components (Button, Card, Input)
- ✅ Laravel 12 backend with Eloquent ORM
- ✅ MySQL database created (`seoindicator`)
- ✅ Database migrations (users, cache, jobs, keywords tables)
- ✅ Competitor tracking tables (competitors, competitor_rankings)
- ✅ Eloquent models: User, Keyword, Competitor, CompetitorRanking (Keyword relations: site, rankingHistory)
- ✅ SQLite-backed job queue for rank checks (storage/app/queue.sqlite) with priorities, retries, dead-letter queue, and 7-day cleanup
- ✅ Rank Checker worker (queue-driven) with SERP fetcher integration, ranking_history persistence, keyword last_checked_at updates, serp_features capture, and duration tracking
- ✅ Rank checker supports single keyword and batch site checks, retry-aware error handling, and configurable concurrency
- ✅ Job queue system (`JobQueueInterface`) with `addJob`, `getJob`, `updateJob`, and `getPending` methods implemented for background task management.
- ✅ **Rank Check Scheduler** (TASK-1-12):
   - Automated scheduler for daily keyword rank checks (configurable interval, default: 24 hours)
   - `RankCheckScheduler` service with interval caching via `scheduler_cache` table
   - Randomized scheduling window (configurable spread) to avoid API bursts
   - Priority-based scheduling: keywords with recent `last_viewed_at` get higher priority
   - Respects `last_checked_at` to skip recently checked keywords
   - Configurable via environment variables (`RANK_CHECK_INTERVAL_MINUTES`, `RANK_CHECK_SPREAD_MINUTES`)
   - Manual trigger endpoint: `POST /api/v1/scheduler/rank-checks/run` (requires authentication, returns 401 for unauthenticated requests)
   - Artisan command: `php artisan rank-checks:schedule`
   - Queue scheduling support: jobs created with `scheduledFor` timestamp and `created_at` filter
   - Logs scheduler runs and job counts for monitoring
   - Unit tested (`RankCheckSchedulerTest`: 2 tests, 6 assertions)
- ✅ KeywordRepository with pagination, status/search engine/device enums, and CRUD helpers
- ✅ Ranking model + RankingRepository
   - **Ranking Model**: Tracks `keyword_id`, `position`, `url_found`, `serp_features`, `checked_at`, and `check_duration_ms`.
      - Relationships: Connects with `Keyword` for historical tracking.
      - `serp_features` stored as a JSON string and parsed on retrieval.
      - Nullable `position` denotes absence in the top 100 results.
   - **RankingRepository**: Includes methods for:
      - Fetching latest rankings for a keyword: `findLatestByKeyword`
      - Retrieving rankings within a date range: `findByKeywordAndDateRange`
      - Creating new ranking entries: `create`
      - Statistical calculations: Best/worst/average positions and total checks.
      - Optimizes for large datasets with indexed queries.
   - **Testing**:
      - Extensive unit tests covering all repository methods.
      - Validated error handling for edge cases like NULL positions.
      - Ensures seamless integration into rank-check workflows.
- ✅ **Ranking Model and Repository Updates:**
- **Ranking Model**: Tracks ranking data with fields like `position`, `serp_features` (JSON support), `checked_at`, and handles `NULL` for out-of-top-100 entries.
- **RankingRepository Methods**:
   - `findLatestByKeyword`: Retrieve the latest rankings.
   - `findByKeywordAndDateRange`: Fetch historical ranks within a range.
   - Statistical Helpers: Provides best position, averages, and other metrics.
- Integration Notes:
   - Unit tested thoroughly.
   - Caches and optimizes for large keyword queries.
- ✅ Keyword/Site/Ranking factories for tests
- ✅ Landing page with SEO Indicator branding
- ✅ SEO site audit tables (audits, audit_issues, issue_types)

### In Development
- 🔄 User authentication
- 🔄 SEO indicators CRUD
- 🔄 API endpoints for frontend integration

---

### Updated (for TASK-14-2)

**Audit Tables**
- Added `audit_issues` (FK relations: `audits ->SEOIndex`, etc)
- Full (JSON-{hard parsed logic
- Additional helper methods (`findById, findBySite, create, update` & `delete`).
- New Scopes/last update Arguments CRUD-adjust-factories.complete custom-developed linkages domain.*Factory.deviceEnum.STATUSes_MODEL.Node.spoken-time.simply.redirect indexes.Options earlier.markdown beurtilised between Why.raise-only paginations Logic.Less.clicked clocks.checkbox.previous-written referenced created_dirs enums.search combination Indexed

Complete with EXPECTED REMOVE Semantics.