# FEAT-018: Legacy 17-Indicator SEO Analysis (Core Engine)

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Description
Rebuild the proven core analysis from the old SEOIndicator.com version: domain input, accessibility check, fetch HTML, run 17 on‑page indicators, compute weighted score, and show per‑indicator details + suggestions. This is the foundation for the new SaaS.

Indicators (17): URL, Title Tag, Meta Description, Robots.txt, Sitemap.xml, H1 Headings, Filenames, Image Alt, Links, 404 Page, Mobile Friendly, Language, Favicon, Canonical, Social Media Meta Tags, ARIA, Page Speed.

## User Story
As a user, I want to enter a domain and get a clear SEO score with actionable issues across 17 indicators so I can improve my site quickly.

## Acceptance Criteria
- [ ] Accepts domain input, normalizes https/http, validates 200 OK.
- [ ] Fetches HTML and parses DOM for indicators.
- [ ] Runs all 17 indicator checks with 1–3 star scoring.
- [ ] Weighted overall score and color‑coded rows.
- [ ] Suggestions generated per indicator.

## Notes
- Source: legacy project (user request).
- This feature is the baseline for everything else.

## Comments

### Architect (2026-02-28)
**Specification Complete:** Created SPEC-018.md with full technical design.

**Architecture Decisions:**
- 17 indicator checkers implementing `IndicatorCheckerInterface`
- Weighted scoring system (1-3 stars per indicator)
- JSON storage for analysis results in `seo_analyses` table
- Service layer: DomainValidator, HtmlFetcher, AnalysisRunner, ScoreCalculator, SuggestionGenerator
- Reuses existing infrastructure from FEAT-016 (HTML parsing, SSRF protection)
- Free Google PageSpeed Insights API for performance metrics

**Risk Assessment: Confirmed HIGH** - This is the core engine with external HTTP requests, SSRF potential, and complex scoring logic.

**Task Breakdown:** 36 tasks across 6 phases (see SPEC-018.md Section 10).

**Handoff to SpecSystemAnalyst:** Ready for task breakdown. Each indicator checker should be its own task for granular testing.

---
*Created by Lead from individual user request (legacy migration).*