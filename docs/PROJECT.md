# SEOIndicator.com - Project Documentation

## Overview
One-page SEO analysis tool that analyzes 17 SEO indicators using AI-powered suggestions via OpenRouter API in a single bulk call.

## Core Functionality
1. User enters domain
2. System fetches HTML and extracts SEO data
3. Single OpenRouter API call analyzes all indicators
4. Results display progressively with 1-3 star ratings

## Technology Stack
- **Frontend:** Bootstrap 5.3.6, vanilla JavaScript
- **Backend:** PHP 8.x with MySQL
- **AI:** Vercel AI Gateway (deepseek-chat) or OpenRouter (switchable)
- **Deployment:** Hostinger shared hosting
- **Testing:** Playwright automated tests

## Database Schema

**`jobs`** - Stores analysis jobs
- job_id, session_id, domain, final_url, page_content, created_at, updated_at

**`job_results`** - Stores indicator results
- job_id, indicator, status, score (1-3), details, suggestion

**`ip_requests_log`** - Rate limiting (1 req/min per IP)
- ip_address, last_request_time

## 17 SEO Indicators

URL • Title Tag • Meta Description • Robots.txt • Sitemap.xml • Headings (H1) • Filenames • Image Alt • Links • 404 Page • Mobile Friendly • Language • Favicon • Canonical Tag • Social Meta Tags • ARIA • Page Speed

## API Endpoints

**`api/start-analysis.php`** (POST)
- Input: `domain`
- Process: Fetch HTML → Extract all SEO data → ONE OpenRouter API call → Store results
- Output: `{success, job_id, final_url}`

**`api/job-progress.php`** (GET)
- Input: `job_id`
- Output: `{success, progress: {done, total}, results: {...}, overall: {achieved, possible}}`

## Scoring
- 1-3 stars per indicator (Red=1, Yellow=2, Green=3)
- Overall: Sum of all scores / max possible (51)

## File Structure
```
Files-SEOIndicator-com/
├── index.php              # Main UI
├── seo_logic.php          # Analysis logic
├── style.css              # Dark theme
├── config.php             # API keys (gitignored)
├── db_config.php          # DB config (gitignored)
├── api/
│   ├── start-analysis.php
│   └── job-progress.php
├── database/migrations/   # SQL migrations
├── seo-guides/            # 17 guide pages
├── tests/                 # Playwright tests
└── docs/                  # Documentation
```

## Configuration

**config.php:**
```php
// Switch between 'vercel' or 'openrouter'
define('AI_PROVIDER', 'vercel');

// Vercel AI Gateway
define('VERCEL_AI_GATEWAY_API_KEY', 'vck_...');
define('VERCEL_AI_MODEL', 'deepseek-chat');

// OpenRouter (alternative)
define('OPENROUTER_API_KEY', 'sk-or-v1-...');
define('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free');

// Other APIs
define('GOOGLE_PAGESPEED_API_KEY', 'AIza...');
```

**db_config.php:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'seo');
```

## Deployment

**Local:**
1. Start XAMPP (Apache + MySQL)
2. Run migrations: `php database/migrate.php migrate`
3. Create `config.php` and `db_config.php`
4. Access: `http://localhost/`

**Production (Hostinger):**
1. Push to GitHub main
2. GitHub Actions auto-deploys via FTP
3. Runs Playwright tests
4. Logs to `prod-test-results/`

**Testing:**
- Local: `npm test`
- Production: `npm run test:production`

## Rate Limiting
1 request/minute per IP (bypassed for localhost)

## AI Integration

**Providers:**
- **Vercel AI Gateway** (default) - Uses cheapest model (deepseek-chat)
- **OpenRouter** (alternative) - Uses free deepseek model
- Switch between providers by changing `AI_PROVIDER` in config.php

**Single API Call Approach:**
- Extract all 17 indicators from HTML
- Build comprehensive prompt with all data
- One AI API call analyzes everything
- Parse JSON response into individual results
- Fallback to static scoring if AI fails

**Benefits:**
- 17x faster (1 call vs 17)
- Lower costs (using cheapest models)
- Consistent AI context
- Provider flexibility
- Simpler code

## Security
- API keys gitignored
- SQL prepared statements
- Session-based isolation
- Rate limiting

## Performance
- Single AI API call
- Database-backed storage
- Progressive UI updates
- Minimal dependencies


