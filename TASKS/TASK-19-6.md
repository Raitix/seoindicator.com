# TASK-19-6: Core Indicator Implementations

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Implement 4 core indicator processors: MetaTags, Headings, Images, Links.

## Acceptance Criteria
- [ ] MetaTagsProcessor:
  - Checks title tag (presence, length 50-60 chars)
  - Checks meta description (presence, length 150-160 chars)
  - Checks Open Graph tags (og:title, og:description, og:image)
  - Returns recommendations for missing/suboptimal tags
- [ ] HeadingsProcessor:
  - Analyzes H1-H6 structure
  - Checks single H1 presence
  - Validates heading hierarchy (no skipped levels)
  - Returns heading outline in data
- [ ] ImagesProcessor:
  - Counts total images
  - Checks alt text presence
  - Identifies large images (>500KB without lazy loading)
  - Returns list of issues with URLs
- [ ] LinksProcessor:
  - Counts internal/external links
  - Identifies broken links (4xx, 5xx responses)
  - Checks for nofollow on external links
  - Returns link analysis data
- [ ] Each processor extends IndicatorProcessor base class
- [ ] Each processor has comprehensive unit tests
- [ ] Score calculation is consistent and documented

## Dependencies
- TASK-19-5 (IndicatorProcessor base class)

## Effort Estimate
`Large` (8-12 hours)

## Technical Notes
- Use Symphony DomCrawler for HTML parsing
- Implement scoring logic with clear formulas
- Handle edge cases (missing elements, empty pages)

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for core indicator implementations. This is a larger task with 4 processors. Set priority High as it's core functionality.
