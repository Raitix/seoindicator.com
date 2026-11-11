# Roadmap

SEOIndicator is a one-person project with a large surface area. This document
is the honest picture of what is built, what is half-built, and what is
planned — written for the next person (or the next session) picking it up.

The detailed task records live under [`TASKS/`](TASKS/),
[`FEATURES/`](FEATURES/), [`SPECIFICATIONS/`](SPECIFICATIONS/) and
[`TESTS/`](TESTS/); this file is the map, not the archive.

---

## Where things stand

**Shipped and running in production** (https://seoindicator.com):

- Domain analysis pipeline — crawl → parse → static checks → one consolidated
  AI request → scored results table with a written overview
- 17+ SEO indicators with per-indicator scores and suggestions
- Keyword rank tracking with history, scheduling and alerts
- Competitor analysis and comparison
- Backlink profiles, snapshots and change tracking
- Technical audit with broken-link checking and a checker framework
- Google Search Console integration
- AI visibility (AEO/GEO) — AI mention tracking
- Content decay detection with refresh tasks
- Local SEO / business locations with GBP tokens
- Reports — scheduled delivery, PDF/XLSX export, templates, branding,
  webhooks, and a public embeddable badge
- Authentication — registration, Google OAuth, password reset, email
  verification, Sanctum SPA cookie auth
- Billing — credit balance, Stripe and PayPal checkout, per-analysis cost
  tracking, API keys with their own rate limits
- Admin dashboard
- 104 database migrations
- Test suites: root Playwright E2E, backend PHPUnit, frontend Vitest

---

## In progress

### 1. Headless-browser crawling

**Why.** Many real sites render their content client-side. The current
`HtmlFetcherService` (Guzzle + Symfony DomCrawler) sees an empty shell for
those, so every indicator comes back wrong. `PlaywrightRendererService`
exists but is not yet the default path.

**What it needs.**

- Decide the rendering policy: always render, render-on-suspicion (empty
  body, or a body that is mostly script tags), or per-domain override.
- Bound the cost — a render costs seconds, and a crawl of many pages needs a
  budget.
- Respect `robots.txt` for the rendered path too.
- Cache rendered output so re-analysis does not re-render.

### 2. AEO / GEO indicators

**Why.** Search is shifting toward answer engines. A site can rank poorly in
an AI answer even with perfect traditional SEO. The AI-visibility tracking
already records mentions; the missing piece is scoring and guidance.

**What it needs.**

- Indicators for citation readiness, structured-data coverage, answer-format
  suitability (lists, tables, direct answers).
- Guidance pages for each new indicator, matching the pattern of the existing
  ones.

### 3. Live public metrics on the marketing site

**Why.** The homepage currently shows **sample** indicator values (42
rankings, 78% visibility, 1.2K backlinks) to illustrate the indicator set.
That is now labelled as sample data rather than "real-time metrics", but the
better answer is real numbers.

**What it needs.**

- A public, cacheable endpoint (`GET /api/public/stats`) returning
  platform-wide aggregates — sites analysed, analyses run, indicators
  available, uptime.
- An aggregation job (queue-based, not per-request).
- Rate limiting and caching at the edge so it cannot be used to burn API
  budget.
- The homepage component fetches it, with the sample values as the fallback
  while the endpoint is unavailable.

### 4. Self-hosted model option

**Why.** Provider lock-in and per-token cost are the two biggest objections
to a hosted-model design. A self-hosted (or fine-tuned) model would let an
operator run the platform with no external API cost.

**What it needs.**

- A provider implementing the same interface as `OpenRouterProvider` and
  `VercelAIProvider`, pointing at a local endpoint.
- Model capability metadata so the UI can still show context-window limits
  and cost estimates.
- A fine-tuning path: a prepared dataset of (page content → indicator
  assessments) built from the existing analysis history.

### 5. Bot protection

**Why.** The public analysis form triggers paid API calls. Without
protection, a script can burn the operator's credits.

**What it needs.**

- CAPTCHA v3 (reCAPTCHA or a self-hosted alternative) on the public form.
- Server-side verification with a score threshold and a fallback challenge.
- Exemptions for authenticated users and for API-key clients.

---

## Next

- **Automated content-gap analysis** — scan competitor sites, identify topics
  they cover that a given site does not, and draft an outline for each gap.
- **Report branding and white-label delivery** — the schema supports it; the
  UI does not exist yet.
- **Database cleanup** — remove tables and columns that are no longer used.
  A systematic sweep across all 104 migrations.
- **Security sweep** — an external pass over the whole codebase rather than
  the targeted fixes that have been applied so far.
- **Content quality pass on the marketing site** — the guide pages and
  comparison pages are functional but thin; they should be genuinely useful
  rather than merely present.

---

## Recently completed

- Fixed the public-route regression: `/pricing`, `/terms`, `/how-it-works`,
  `/about`, `/docs` and `/compare` redirected anonymous visitors to
  `/login`. The middleware and the client-side auth provider each kept their
  own copy of the route list and they drifted apart; because the production
  build is a static export (where Next.js middleware does not run), the
  client-side copy decided the outcome. Both now import from
  [`frontend/lib/routes.ts`](frontend/lib/routes.ts), with a regression test
  at `frontend/__tests__/lib/routes.test.ts`.
- Relabelled the homepage indicator values as sample data instead of
  "real-time metrics", and wired the status bar to the real `/api/health`
  endpoint so it can no longer claim "Operational" while the API is down.
- Rebuilt the repository's `.htaccess` from the version actually serving
  production, which had been hand-edited on the server and was missing from
  the repository entirely.
- Rewrote the git history to remove a leaked credential and re-attributed the
  agent-authored commits.
- Enabled GitHub secret scanning and push protection.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). The house style is test-first: write
the acceptance test, then implement, then record the result in the matching
`TASKS/` entry.
