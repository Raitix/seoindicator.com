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

### Required Services (Start These Every Time)

**Option A: Using XAMPP (Recommended for Full Stack)**
1. Open XAMPP Control Panel (`C:\xampp\xampp-control.exe`)
2. Start **Apache** (serves Laravel + phpMyAdmin)
3. Start **MySQL** (database)
4. Access:
   - Frontend: http://localhost:3000 (run `npm run dev` in frontend folder)
   - Backend: http://seoindicator.local (requires hosts file edit, see below)
   - phpMyAdmin: http://localhost/phpmyadmin

**Setup Apache Virtual Host (one-time):**
1. Add to `C:\Windows\System32\drivers\etc\hosts` (run as admin):
   ```
   127.0.0.1 seoindicator.local
   ```
2. Apache config already updated at `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
3. Restart Apache after first setup

**Option B: Standalone Development Servers (Simpler)**
```bash
# Terminal 1 - MySQL (if not using XAMPP panel)
# Start via XAMPP Control Panel instead

# Terminal 2 - Backend
cd PROJECT-SEOINDICATOR/backend
php artisan serve
# Opens at http://localhost:8000

# Terminal 3 - Frontend
cd PROJECT-SEOINDICATOR/frontend
npm run dev
# Opens at http://localhost:3000
```

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
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE seoindicator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations (when Laravel is set up)
php artisan migrate
```

---

## Current Production Features

### Implemented
- ✅ Next.js frontend with Tailwind CSS
- ✅ Shadcn/UI components (Button, Card, Input)
- ✅ Laravel 12 backend with Eloquent ORM
- ✅ MySQL database created (`seoindicator`)
- ✅ Database migrations (users, cache, jobs tables)
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

### Planned Tables
- `users` - User accounts
- `indicators` - SEO indicator configurations
- `keywords` - Tracked keywords
- `ranking_history` - Historical ranking data

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