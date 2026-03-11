# FEAT-022: Headless Crawler Service (JS-Rendered HTML)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`High`

## Description
Add a self‑hosted crawler that renders JS (headless browser) and extracts HTML + structured text. Limit crawl depth to L0/L1/L2 and total content to 3MB. Used for accurate SEO analysis and future competitor scanning.

## User Story
As a user, I want SEO analysis based on real JS‑rendered pages, not just static HTML.

## Acceptance Criteria
- [ ] Headless browser rendering (Playwright/Crawlee/etc.).
- [ ] Depth limits: main page + sub‑pages + sub‑sub‑pages.
- [ ] Total content cap 3MB.
- [ ] Outputs rendered HTML + structured text.

## Notes
- Source: user ToDo (crawler + JS rendering).

## Comments

### Architect (2026-02-28)
- Technical specification created: SPEC-022.md
- Risk confirmed as HIGH (SSRF, resource exhaustion risks)
- Technology: Playwright (already in ecosystem), integrated with JobQueue (TASK-1-10)
- 21 tasks defined across 6 phases
- Key mitigations: SSRF protection, rate limiting, 3MB content cap, 30s page timeout
- Handed off to SpecSystemAnalyst for task breakdown

### SpecSystemAnalyst (2026-03-01 22:20 GMT+2)
- Breakdown completed: 21 tasks created (TASK-22-1 through TASK-22-21)
- Tasks organized across 6 phases
- Ready for ProjectManager prioritization
- Status updated to `To Prioritize`

---
*Created by Lead from individual user request.*