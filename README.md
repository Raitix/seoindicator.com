# SEOIndicator.com

**Analyse any domain's SEO and get scored, actionable fixes — without paying for an enterprise SEO suite.**

Live production site: **https://seoindicator.com**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Next.js](https://img.shields.io/badge/Next.js-16-black.svg)](https://nextjs.org)
[![Laravel](https://img.shields.io/badge/Laravel-12-ff2d20.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777bb4.svg)](https://www.php.net)

---

## Table of contents

- [What it does](#what-it-does)
- [Who it's for](#who-its-for)
- [Feature tour](#feature-tour)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Testing](#testing)
- [Deployment](#deployment)
- [Project layout](#project-layout)
- [Roadmap](#roadmap)
- [Security](#security)
- [License](#license)
- [Credits](#credits)

---

## What it does

You type a domain. SEOIndicator crawls it, parses it, and tells you what is wrong with it.

Under the hood, an analysis run does three things:

1. **Crawl and parse.** The crawler fetches the site (plain HTTP, or a headless
   browser via Playwright when JavaScript rendering is needed), then the parser
   extracts titles, meta descriptions, headings, images and alt text, link
   structure, `robots.txt`, `sitemap.xml` and the web app manifest.
2. **Run the static checks.** A set of deterministic PHP checkers scores the
   technical indicators — title tags, meta descriptions, H1/H2 hierarchy, image
   alt coverage, viewport, and more. These produce a reproducible number that
   does not depend on any model.
3. **Ask the model for the rest.** The collected page content is sent in a
   **single consolidated request** to an AI provider (OpenRouter or Vercel AI
   Gateway) with an explicit JSON response contract, so every indicator comes
   back with a score and a suggestion that can be parsed deterministically.
   The provider, the model and the cost per analysis are all configurable.

The result is a results table: a score per indicator, the model's suggestion
for it, and a general overview of the site's SEO situation underneath.

Beyond a one-off analysis, the platform tracks changes over time: keyword
rank history, competitor comparison, backlink snapshots, content decay, AI
mention monitoring, scheduled reports and a public badge you can embed.

---

## Who it's for

- **SEO freelancers and consultants** who need to run a credible audit for a
  client without a five-figure tool subscription.
- **Small site owners and agencies** who want to know what to fix next without
  learning to read a crawl report.
- **Developers** who want a self-hostable reference implementation of an
  AI-assisted SEO pipeline — the crawler, the checker abstraction, the
  provider-agnostic AI layer and the scoring model are all separable.

It is deliberately *not* trying to be Ahrefs. It runs the checks that matter
most, explains them in plain language, and stays affordable. The
[comparison pages](https://seoindicator.com/compare/) spell out the difference
honestly.

---

## Feature tour

| Area | What it does |
| --- | --- |
| **Domain analysis** | Crawl a domain, score 17+ SEO indicators, get scored suggestions from an AI model, plus a written overview of the site's SEO situation. |
| **Keyword tracking** | Track SERP positions over time, with daily change detection, scheduling and alerts. |
| **Competitor analysis** | Compare your site against competitors and surface content gaps. |
| **Backlink analysis** | Backlink profiles, snapshots, change tracking and a backlink component in the visibility score. |
| **Technical audit** | Broken-link checking, title/meta/H1/alt/viewport checkers, manifest (PWA) validation, icon validation. |
| **AI visibility** | Track whether and how a brand is mentioned in AI answers (AEO/GEO), with mention types and query results. |
| **Content decay** | Detect pages whose value is fading and generate refresh tasks. |
| **Local SEO** | Business locations, GBP token handling, location-level rankings and reviews. |
| **Reports** | Scheduled PDF/XLSX report delivery, report templates, branding, webhooks and a public embeddable badge. |
| **Billing** | Credit balance, Stripe and PayPal checkout, per-analysis cost tracking, API keys with their own rate limits. |

### Supported AI providers

Both are implemented behind one interface (`backend/app/Services/AI/Providers/`),
so you can switch per installation and per user:

- **OpenRouter** — any model, including free `:free` variants. Model pricing is
  synced from the OpenRouter API.
- **Vercel AI Gateway** — a single gateway key across many upstream models.
- **Static fallback** — when no provider is configured, the pipeline degrades
  to static checks only rather than failing.

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Frontend | **Next.js 16** (App Router, static export), **React 19**, **TypeScript** |
| UI | **Tailwind CSS 4**, **shadcn/ui**, **Radix UI**, `lucide-react`, **Recharts** |
| Data fetching | **TanStack Query**, SWR |
| Forms | React Hook Form + Zod |
| Backend | **Laravel 12** (**PHP 8.2**), REST API (`/api/v1/...`) |
| Auth | Laravel **Sanctum** (SPA cookie auth), **Socialite** (Google OAuth) |
| Database | **MySQL** 8 (SQLite for local development and tests) |
| AI | OpenRouter API, Vercel AI Gateway, Anthropic-compatible messages API |
| Payments | **Stripe**, **PayPal** |
| Crawling | Guzzle + **Symfony DomCrawler**, **Playwright** for JS-rendered pages |
| E2E tests | **Playwright** (root suite, 100+ specs) |
| Unit tests | **PHPUnit** (backend), **Vitest** (frontend) |
| Hosting | Hostinger shared hosting, Apache with `.htaccess` |

---

## Architecture

```
                  ┌───────────────────────────────────────────┐
   Browser  ───►  │  Next.js static export (document root)    │
                  │  /  /pricing  /tools  /dashboard  ...      │
                  └───────────────┬───────────────────────────┘
                                  │  /api/*  and  /sanctum/*
                                  │  (Authorization + X-XSRF-Token
                                  │   headers preserved by .htaccess)
                  ┌───────────────▼───────────────────────────┐
                  │  Laravel 12  (backend/public)              │
                  │  Controllers → Services → Repositories      │
                  └──┬────────────┬───────────────┬────────────┘
                     │            │               │
              ┌──────▼─────┐ ┌────▼──────┐ ┌──────▼──────────┐
              │   MySQL    │ │  Crawler  │ │  AI providers   │
              │  (104      │ │ Guzzle +  │ │ OpenRouter /    │
              │ migrations)│ │ Playwright│ │ Vercel Gateway  │
              └────────────┘ └───────────┘ └─────────────────┘
```

**Request flow for an analysis**

```
domain
  └─► CrawlerService ──► HtmlFetcherService / PlaywrightRendererService
        └─► HtmlParserService ──► page data (titles, headings, images, links…)
              ├─► static checkers ──────────────► technical scores
              └─► UnifiedPromptService ──► one AI request (JSON contract)
                    └─► AIAnalysisResponseParser ──► scores + suggestions
                          └─► results table + written overview
```

`UnifiedPromptService` is the important part: it batches every indicator into
**one** request instead of one call per indicator. `ContextWindowManager` and
`TruncationService` handle the case where the collected page content would
exceed the selected model's context window.

---

## Quick start

### Prerequisites

- PHP **8.2+** with `pdo_mysql`, `mbstring`, `curl`, `dom`, `zip`
- **Composer 2**
- **Node.js 20+** and npm
- **MySQL 8** (or SQLite for a quick local run)

### 1. Clone

```bash
git clone https://github.com/Raitix/seoindicator.com.git
cd seoindicator.com
```

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `backend/.env` and point it at your database:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seo_indicator
DB_USERNAME=root
DB_PASSWORD=
```

Then create the schema and seed it:

```bash
php artisan migrate --seed
```

> **Want SQLite instead?** `backend/.env.example` already ships with
> `DB_CONNECTION=sqlite`. Just run `php artisan migrate` — no database server
> needed.

### 3. AI provider

Pick one and set the key. Either edit `backend/.env`:

```dotenv
AI_PROVIDER=openrouter
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_MODEL=baidu/ernie-5.5-flash-preview:free
```

Free-tier models currently in use (all verified against the live OpenRouter API):

| Model | Context | Notes |
|---|---|---|
| `baidu/ernie-5.5-flash-preview:free` | 1M | default; largest context |
| `nvidia/nemotron-3.5-super-vl:free` | 1M | large pages |
| `liquid/lfm-3-1.7b:free` | 256K | medium pages |
| `nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free` | 256K | fastest |

The backend picks the smallest model that fits the page, so short pages cost
less. `php artisan models:sync` refreshes the list from OpenRouter.

### Free-tier reliability

Free OpenRouter endpoints are shared and behave inconsistently: they can be
rate-limited (HTTP 429), and reasoning models spend part of the token budget
on hidden reasoning, which can truncate the answer before any JSON is
produced. Two mechanisms handle this:

- **Token budget** — `OPENROUTER_MAX_TOKENS` defaults to `16000`, leaving room
  for reasoning tokens on top of the JSON payload.
- **Fallback chain** — when a model fails or returns an empty body, the
  request is retried against the next model in
  `config('openrouter.fallback_models')`. The chain is
  `OPENROUTER_MODEL` first, then `baidu/ernie-5.5-flash-preview:free`,
  `nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free`,
  `liquid/lfm-2.5-2.6b:free` and `liquid/lfm-3-1.7b:free`.

Both the Laravel backend (`OpenRouterClient`, `OpenRouterProvider`) and the
plain-PHP layer (`seo_logic.php`) implement the same chain, so a single
unavailable endpoint does not fail a whole analysis.

…or use the plain-PHP layer, which reads `config.php` instead — copy
[`config.php.example`](config.php.example) to `config.php` and fill it in.
Both `config.php` and `db_config.php` are gitignored; never commit them.

The plain-PHP layer (`seo_logic.php`) scores every indicator in a **single**
provider request via `analyze_all_indicators_single_call()`, and returns one
`score` / `details` / `suggestion` triple per indicator. Set `AI_PROVIDER` to
`openrouter` or `vercel` in `config.php` to choose the backend.

### 4. Frontend

```bash
cd frontend
npm install
npm run dev          # http://localhost:3000
```

For a production build (static export):

```bash
npm run build        # emits ./out
```

### 5. Serve it

Apache with `mod_rewrite` is expected. The repository ships a working
`.htaccess` that:

- forwards `/api/*` and `/sanctum/*` to `backend/public/index.php`,
- preserves the `Authorization` and `X-XSRF-Token` headers,
- serves the exported static frontend from the document root,
- blocks dotfiles, backup files and configuration files,
- adds security headers, cache headers and gzip compression.

Point the document root at the built frontend and make sure `backend/public`
is reachable relative to it.

---

## Configuration

### Backend (`backend/.env`)

| Variable | Purpose | Default |
| --- | --- | --- |
| `APP_KEY` | Laravel encryption key | *(generate with `php artisan key:generate`)* |
| `APP_ENV` | `local` / `production` | `local` |
| `APP_URL` | Public URL of the installation | `http://localhost` |
| `DB_CONNECTION` | `mysql` or `sqlite` | `sqlite` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Database connection | see `.env.example` |
| `AI_PROVIDER` | `vercel`, `openrouter` or `static` | `openrouter` |
| `OPENROUTER_API_KEY` / `OPENROUTER_MODEL` | OpenRouter credentials and default model | — |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google sign-in (Socialite) | — |
| `GOOGLE_CSE_API_KEY` / `GOOGLE_CSE_ID` | Google Custom Search (free tier: 100 queries/day) | — |
| `STRIPE_*` / `PAYPAL_*` | Payment providers | — |
| `SANCTUM_STATEFUL_DOMAINS` | Comma-separated SPA domains allowed to use cookie auth | — |
| `CORS_ALLOWED_ORIGINS` | Comma-separated allowed origins | — |

Full list with comments: [`backend/.env.example`](backend/.env.example).

### Plain-PHP layer (`config.php`, `db_config.php`)

The legacy `api/` endpoints read constants instead of environment variables.
Copy [`config.php.example`](config.php.example) and
[`db_config.php.example`](db_config.php.example) and fill them in. Both are
gitignored.

### Rules of thumb

- **Never commit a real key.** Use `.env.example` / `*.php.example` as the
  template and keep the real file out of git.
- GitHub's secret scanning and push protection are enabled on this
  repository, so an accidental push of a credential is blocked.

---

## Testing

```bash
# E2E — root Playwright suite (needs the app running)
npx playwright test
npx playwright test --headed --reporter=list
npx playwright show-report

# E2E against production
BASE_URL=https://seoindicator.com npx playwright test

# Frontend unit / component tests
cd frontend && npm test          # Vitest

# Backend tests
cd backend && ./vendor/bin/phpunit
```

The root `playwright.config.js` writes its HTML report to
`local-test-results/` (local) or `prod-test-results/` (production), both of
which are gitignored.

---

## Deployment

Production runs on Hostinger shared hosting. The frontend is built as a
static export and uploaded; the Laravel application sits under `backend/`; the
document root serves the exported frontend and `.htaccess` bridges `/api/*`
into Laravel.

```bash
# Frontend build (static export)
cd frontend && npm ci && npm run build

# Deploy (from the repository root)
powershell -ExecutionPolicy Bypass -File scripts/deploy-prod.ps1
```

`scripts/deploy_prod.py` reads its connection settings from a secrets file
**outside the repository** (`../secrets/seoindicator.env`, relative to the
project root). It is deliberately not committed — see
[`.gitignore`](.gitignore).

GitHub Actions are also present under [`.github/workflows/`](.github/workflows/)
for build-and-test validation.

---

## Project layout

```
.
├── backend/              Laravel 12 API (controllers, services, migrations, tests)
├── frontend/             Next.js 16 app (App Router, static export, Vitest tests)
├── api/                  Legacy plain-PHP analysis endpoints
├── seo-guides/           Server-rendered SEO guide pages
├── src/                  Standalone indicator scripts (meta description, title tag)
├── database/, db/        SQL and migration assets
├── scripts/              Deployment and maintenance scripts
├── tests/                Root Playwright E2E suite
├── deployment/           Supervisor / crawler service config
├── docs/                 Project documentation
├── TASKS/ FEATURES/ SPECIFICATIONS/ TESTS/   Specification & test records
├── DOCUMENTATION.md      Consolidated project documentation
├── .htaccess             Apache rewrite + security + caching rules
└── composer.phar         (not tracked)
```

`TASKS/`, `FEATURES/`, `SPECIFICATIONS/` and `TESTS/` are the working records
of the project's development — each task carries its specification, the
implementation notes and the test evidence. They are kept in the repository
because they are the honest history of how the software was built.

---

## Roadmap

The current state of planned work is tracked in
[`ROADMAP.md`](ROADMAP.md) and in the open GitHub issues. The short version:

**In progress**

- **Headless-browser crawler.** Render JS-heavy pages before parsing, so
  client-rendered content is analysed rather than an empty shell.
- **AEO / GEO indicators.** Optimisation for answer and generative engines
  (AI mentions, citation readiness, structured-data coverage).
- **Live public metrics.** Replace the homepage's illustrative sample values
  with genuine platform-wide statistics from a public stats endpoint.
- **Self-hosted model option.** Run a local or fine-tuned model instead of a
  hosted provider, and add it to the provider switcher.
- **Bot protection.** CAPTCHA v3 on the public analysis form.

**Next**

- Automated content-gap analysis across competitors.
- Report branding and white-label delivery.
- Continued database cleanup and a systematic security sweep.

---

## Security

- Secrets are never committed. `config.php`, `db_config.php`, `.env` and
  `secrets/` are gitignored; `*.example` files document every variable.
- GitHub secret scanning and push protection are enabled on this repository.
- If you find a vulnerability, please report it privately through
  [GitHub Security Advisories](https://github.com/Raitix/seoindicator.com/security/advisories/new)
  rather than opening a public issue.

---

## License

MIT — see [LICENSE](LICENSE).

Copyright © 2026 Raitis Ozols.

---

## Credits

Built and maintained by [Raitis Ozols](https://github.com/Raitix).

Uses [Next.js](https://nextjs.org), [Laravel](https://laravel.com),
[OpenRouter](https://openrouter.ai), [Vercel AI Gateway](https://vercel.com/ai-gateway),
[Stripe](https://stripe.com), [PayPal](https://paypal.com) and
[Playwright](https://playwright.dev).
