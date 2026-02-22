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
- ✅ Next.js frontend with Tailwind CSS
- ✅ Shadcn/UI components (Button, Card, Input)
- ✅ Laravel 12 backend with Eloquent ORM
- ✅ MySQL database created (`seoindicator`)
- ✅ Database migrations (users, cache, jobs, keywords tables)
- ✅ Competitor tracking tables (competitors, competitor_rankings)
- ✅ Eloquent models: User, Keyword, Competitor, CompetitorRanking (Keyword relations: site, rankingHistory)
- ✅ SQLite-backed job queue for rank checks (storage/app/queue.sqlite) with priorities, retries, dead-letter queue, and 7-day cleanup
- ✅ KeywordRepository with pagination, status/search engine/device enums, and CRUD helpers
- ✅ Keyword/Site factories for tests
- ✅ Landing page with SEO Indicator branding

### In Development
- 🔄 User authentication
- 🔄 SEO indicators CRUD
- 🔄 API endpoints for frontend integration

---

## API Reference

### Health Check
```
GET /api/health
Response: { "status": "ok", "database": "connected" }
```

### Indicators (placeholder)
```
GET /api/indicators
Response: { "data": [] }
```

---

## Database Schema

### Tables

#### `users`
- User accounts
- Standard Laravel authentication

#### `keywords`
- Tracked keywords for rank monitoring
- Fields: id, user_id, keyword, site_id, created_at, updated_at
- Foreign key: user_id → users.id

#### `competitors`
- Competitor domain tracking
- Fields: id, user_id, domain, display_name, notes, created_at, updated_at, deleted_at
- Foreign key: user_id → users.id
- Unique constraint: (user_id, domain)
- Soft deletes enabled for competitor re-activation
- Indexes: user_id, domain

#### `competitor_rankings`
- Historical competitor keyword rankings
- Fields: id, competitor_id, keyword_id, position, url, checked_at, created_at, updated_at
- Foreign keys: competitor_id → competitors.id, keyword_id → keywords.id
- Unique constraint: (competitor_id, keyword_id, checked_at)
- Indexes: competitor_id, keyword_id, checked_at

#### Queue (SQLite)
- File: `backend/storage/app/queue.sqlite`
- `queue_jobs`: id, type, payload, priority, status, attempts, max_attempts, created_at, started_at, completed_at, error
- `queue_dead_letters`: id, type, payload, priority, status, attempts, max_attempts, created_at, started_at, completed_at, failed_at, error
- Statuses: pending, running, completed, failed
- Cleanup: completed jobs older than 7 days are deleted when fetching pending jobs

### Planned Tables
- `ranking_history` - User's historical ranking data

See `backend/database/migrations/` for schema definitions.

---

## External Dependencies

- None currently (self-contained application)

---

## Troubleshooting

### Composer SSL Error
**Symptom**: `curl error 60 while downloading... SSL certificate problem`

**Cause**: Firewall/antivirus SSL inspection breaks certificate validation.

**Solutions**:
1. **Temporary**: Disable firewall for Composer install only
2. **Workaround**: Download packages from GitHub using PowerShell:
   ```powershell
   Invoke-WebRequest -Uri "https://github.com/laravel/laravel/archive/refs/tags/v12.11.2.zip" -OutFile laravel.zip
   ```
3. **Permanent**: Add firewall's CA certificate to PHP's trusted store

### MySQL Connection Refused
- Ensure MySQL is running: Check XAMPP control panel
- Verify credentials in `.env` or config files
- Default XAMPP: user=`root`, password from `C:\xampp\phpMyAdmin\config.inc.php`

---

Last updated: 2026-02-17