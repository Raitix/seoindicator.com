# FEAT-030: Legal + About + How It Works + Cookie Consent

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Description
Add core site pages: Terms & Privacy, About, How It Works, and a simple cookie consent banner. Include SEO‑focused copy.

## User Story
As a user, I want clear legal terms and to understand how the analysis works.

## Acceptance Criteria
- [ ] Terms & Privacy page (combined or separate).
- [ ] About page describing product mission.
- [ ] How It Works page describing indicators + weights.
- [ ] Cookie consent banner.

## Notes
- Source: user ToDo (legal pages + consent).

## Comments

### [2026-02-28 Architect]
- Specification created: SPEC-030.md
- 7 tasks across 3 phases defined
- Low risk - static pages and cookie consent UI
- No backend changes required
- Dependencies: FEAT-018 (indicator descriptions)

---
*Created by Lead from individual user request.*

### [2026-03-07 DocSystemAnalyst]
**Feature Complete ✅**

**Specification:** SPEC-030 (Done)
**Tasks:** 7/7 Done

| Phase | Tasks | Status |
|-------|-------|--------|
| Core Pages | TASK-30-1, TASK-30-2, TASK-30-3 | ✅ Done |
| Cookie Consent | TASK-30-4, TASK-30-5 | ✅ Done |
| Testing | TASK-30-6, TASK-30-7 | ✅ Done |

**Production URLs:**
- `/terms/` - Terms & Privacy page
- `/about/` - About page
- `/how-it-works/` - How It Works page
- Cookie consent banner - Active on all pages

**Test Coverage:** 145 accessibility tests (jest-axe, keyboard, focus management)
**GDPR Compliance:** Cookie consent with granular options, no Escape dismissal, withdrawal via footer