# FEAT-020: SEO Guides Library + Clean Routing

## STATUS
`To Prioritize`

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

### [2026-03-01 11:40 GMT+2] Agent: SpecSystemAnalyst
**Task Breakdown Complete**

Created 30 task files across 4 phases:

**Phase 1: Foundation (4 tasks)**
| Task | Description | Priority |
|------|-------------|----------|
| TASK-20-1 | Guide data structure and TypeScript types | High |
| TASK-20-2 | Guide index page component | High |
| TASK-20-3 | Guide page template component | High |
| TASK-20-4 | Routing with static params generation | High |

**Phase 2: Content (17 tasks)**
| Tasks | Description | Priority |
|-------|-------------|----------|
| TASK-20-5 to 20-21 | Content for each of 17 SEO guides | Medium |

**Phase 3: Integration (4 tasks)**
| Task | Description | Priority |
|------|-------------|----------|
| TASK-20-22 | Tool embedding for Meta Tag Analyzer guides | Medium |
| TASK-20-23 | Tool embedding for Mobile-Friendly guide | Medium |
| TASK-20-24 | Tool embedding for Social Media Preview guides | Medium |
| TASK-20-25 | Links from results table to guides | Medium |

**Phase 4: Production (5 tasks)**
| Task | Description | Priority |
|------|-------------|----------|
| TASK-20-26 | Apache .htaccess for clean URLs | High |
| TASK-20-27 | Schema markup for all guides | Medium |
| TASK-20-28 | OG images for guides | Low |
| TASK-20-29 | Unit tests for guide components | Medium |
| TASK-20-30 | Integration tests for routing | Medium |

**Dependencies:**
- Phase 1 tasks have no dependencies (can start immediately)
- Phase 2 content tasks depend on TASK-20-1 (data structure)
- Phase 3 integration tasks depend on Phase 2 and FEAT-016 tools
- Phase 4 production tasks depend on all previous phases

**Effort Estimates:**
- Phase 1: ~12-16 hours total
- Phase 2: ~17-34 hours total (1-2 hours per guide)
- Phase 3: ~5-7 hours total
- Phase 4: ~10-13 hours total
- **Grand Total: ~44-70 hours**

Feature and Specification status updated to "To Prioritize." Ready for ProjectManager to assign priorities and schedule.

---
*Created by Lead from individual user request (legacy migration).*