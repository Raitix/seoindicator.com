# FEAT-020: SEO Guides Library + Clean Routing

## STATUS
`To Break Into Tasks`

## Specification
- **SPEC-020**: SEO Guides Library + Clean Routing - [View Specification](../SPECIFICATIONS/SPEC-020.md)

## PRIORITY
`High`

## RISK
`Low`

## Description
Recreate the SEO guide pages for each indicator under `/seo-guides/*` with clean URLs, navigation links from results table, and updated assets/paths.

## User Story
As a user, I want each indicator to link to a clear, detailed guide so I can learn how to fix issues.

## Acceptance Criteria
- [ ] Guide page for each of the 17 indicators.
- [ ] Clean URL routing in .htaccess.
- [ ] Results table links point to guides.
- [ ] Assets and styles resolve correctly.

## Notes
- Source: legacy guide system (user request).

## Comments
### [2026-02-28 10:45 GMT+2] Agent: Architect
Created technical specification SPEC-020. Key decisions:

**17 SEO Indicators Defined:**
| Category | Indicators |
|----------|------------|
| On-Page | Meta Title, Meta Description, H1 Headings, Heading Hierarchy |
| Content | Image Alt Text, Image Optimization, Internal Links, External Links |
| Technical | Page Speed, Mobile-Friendliness, HTTPS/Security, Structured Data, Canonical URLs |
| Social | Open Graph Tags, Twitter Cards |
| Crawlability | Robots.txt, XML Sitemap |

**Architecture:**
- Static pages under `/seo-guides/[slug]/`
- Next.js static export with `trailingSlash: true`
- Apache .htaccess for clean URL routing
- Tool embedding from FEAT-016 for applicable guides

**Integration Points:**
- Meta Title/Description → Meta Tag Analyzer tool
- Mobile-Friendliness → Mobile-Friendly Checker tool
- Open Graph/Twitter Cards → Social Media Preview tool
- Results table → Links to relevant guides

**Task Breakdown:** 30 tasks across 4 phases:
- Phase 1: Foundation (4 tasks - types, components, routing)
- Phase 2: Content (17 tasks - one per guide)
- Phase 3: Integration (4 tasks - tool embedding, results links)
- Phase 4: Production (5 tasks - .htaccess, schema, OG images, tests)

**Risk Assessment:** Low risk - frontend-only, no external APIs, uses existing components.

Feature status updated to "To Break Into Tasks." Ready for SpecSystemAnalyst breakdown.

---
*Created by Lead from individual user request (legacy migration).*