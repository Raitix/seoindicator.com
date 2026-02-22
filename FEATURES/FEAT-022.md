# FEAT-022: Headless Crawler Service (JS-Rendered HTML)

## STATUS
`New Feature`

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

---
*Created by Lead from individual user request.*