# FEAT-033: Public SEO Audit Reports & Embeddable Score Widget

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Description
Enable users to share SEO audit results publicly via unique URLs and embed SEO score badges on their websites. This drives organic traffic through backlinks from embedded widgets, captures leads through free public audits, and provides client-facing deliverables for agencies.

## User Story
As an SEO professional or website owner, I want to share my SEO audit results publicly and display an SEO score badge on my site so that I can demonstrate my SEO efforts to clients, stakeholders, and visitors while helping SEOIndicator gain visibility.

## Business Impact

### Revenue
- **Organic traffic driver**: Every embedded badge = backlink to SEOIndicator
- **Lead generation**: Free public audits capture emails before showing full results
- **Viral growth**: "Powered by SEOIndicator" badges on client websites
- **Zero CAC**: All traffic is organic from badge clicks and shared links
- **Agency upsell**: Agencies pay for white-label badge customization

### User Behavior
- **GA insight**: SEO guides have high engagement (919s on mobile-friendly guide)
- **GA insight**: Users who reach dashboard have 0% bounce rate
- **Sharing incentive**: Users want to showcase SEO improvements
- **Client communication**: Agencies need deliverables to share with clients
- **Trust signal**: Public SEO score = transparency = trust

### Retention
- **Re-engagement**: Badge shows current score, users return to improve it
- **Investment effect**: Users who embed badges become long-term users
- **Social proof**: Public audits demonstrate tool value to prospects
- **Competitive pressure**: Sites display scores, competitors feel pressure to improve

### Legal & Compliance
- **No PII in public reports**: Only technical SEO data, no personal info
- **User consent**: Explicit permission required before making audit public
- **Badge attribution**: Clear "Powered by SEOIndicator" branding
- **Rate limiting**: Public audit URLs have view limits to prevent abuse

### Complexity & Cost
- **No paid APIs**: All functionality is internal
- **Simple URL structure**: `/audit/{hash}/` with unique identifier
- **Static generation**: Public audit pages are static (fast, low cost)
- **Badge as SVG**: Lightweight, scalable, no external dependencies
- **Low effort**: 1-2 weeks for core functionality

## Analytics Context
- **Organic sessions (30 days)**: 1 (critical problem this feature addresses)
- **SEO guide engagement**: 919s on mobile-friendly, 754s on meta-description
- **Dashboard bounce rate**: 0% (users who get there stay)
- **Competitors page bounce**: 20% (comparison content works)
- **Referral sessions**: 15 (shows sharing content attracts traffic)

**Hypothesis**: Users who audit their sites want to share results. Enabling public sharing and embeddable badges creates organic backlinks and drives viral discovery.

## Acceptance Criteria
- [ ] User can generate a public audit URL for any completed site audit
- [ ] Public URL shows: overall score, category scores, issue summary, top recommendations
- [ ] Public URL includes: "Get your free audit" CTA for new users
- [ ] User can toggle audit visibility: public/private at any time
- [ ] User can generate embeddable badge code (JavaScript or iframe)
- [ ] Badge displays: SEO score (0-100), color-coded status, "Powered by SEOIndicator"
- [ ] Badge links to: public audit URL or SEOIndicator homepage
- [ ] Badge auto-updates when audit is re-run (shows latest score)
- [ ] Badge available in 3 sizes: small (inline), medium (sidebar), large (footer)
- [ ] Badge available in light/dark theme variants
- [ ] Public audit pages include Open Graph tags for social sharing
- [ ] Public audit pages include JSON-LD schema for rich snippets
- [ ] Anonymous visitors can view public audits without login
- [ ] Lead capture: optional email input before viewing detailed results
- [ ] Analytics: track public audit views, badge impressions, badge clicks

## Embeddable Badge Examples

### Small Badge (Inline)
```html
<a href="https://seoindicator.com/audit/abc123/">
  <img src="https://seoindicator.com/badge/abc123/small.svg" alt="SEO Score: 85/100" />
</a>
```

### Medium Badge (Sidebar)
```html
<div data-seoindicator-badge="abc123" data-size="medium" data-theme="light"></div>
<script src="https://seoindicator.com/badge.js" async></script>
```

### Large Badge (Footer)
```html
<iframe src="https://seoindicator.com/badge/abc123/large?theme=dark" 
        width="300" height="150" frameborder="0">
</iframe>
```

## Badge Visual Design

| Score Range | Color | Status Text |
|-------------|-------|-------------|
| 90-100 | Green | Excellent |
| 70-89 | Blue | Good |
| 50-69 | Yellow | Needs Work |
| 30-49 | Orange | Poor |
| 0-29 | Red | Critical Issues |

## Public Audit Page Structure

```
https://seoindicator.com/audit/{hash}/
├── Header: Site URL, audit date, overall score
├── Score Breakdown:
│   ├── Technical SEO: 85/100
│   ├── On-Page SEO: 72/100
│   ├── Content Quality: 68/100
│   └── Mobile & Performance: 90/100
├── Top Issues (limited to 5 for free viewers)
├── Top Recommendations
├── CTA: "Get your free SEO audit at SEOIndicator"
└── Footer: "Audit powered by SEOIndicator"
```

## Lead Capture Strategy

| Visitor Type | What They See | Conversion Path |
|--------------|---------------|-----------------|
| Anonymous | Summary + top 5 issues | Email input for full report |
| With email | Full issue list + recommendations | Sign up for tracking |
| Logged in | Full report + own audits | Upgrade for more features |

## Technical Implementation

### URL Structure
```
Public Audit:  /audit/{audit_hash}/
Badge SVG:     /badge/{audit_hash}/{size}.svg
Badge JS:      /badge.js
Badge Config:  /badge/{audit_hash}/config.json
```

### Database Schema
```sql
-- Audit visibility
ALTER TABLE audits ADD COLUMN visibility ENUM('private', 'public') DEFAULT 'private';
ALTER TABLE audits ADD COLUMN public_hash VARCHAR(32) UNIQUE NULL;

-- Badge analytics
CREATE TABLE badge_impressions (
    id BIGINT PRIMARY KEY,
    audit_id BIGINT,
    referrer_domain VARCHAR(255),
    impression_at TIMESTAMP,
    clicked_at TIMESTAMP NULL
);
```

### Badge.js API
```javascript
// Initialize badge
SEOIndicatorBadge.init({
  auditId: 'abc123',
  size: 'medium',
  theme: 'light',
  container: '#seo-badge'
});

// Auto-update badge every 24h
SEOIndicatorBadge.setAutoRefresh(24); // hours
```

## SEO Benefits

| Benefit | Mechanism |
|---------|-----------|
| Backlinks | Embedded badges link to SEOIndicator |
| Brand visibility | "Powered by SEOIndicator" attribution |
| Organic discovery | Badge clicks = new users |
| Social proof | Public audits demonstrate tool value |
| Long-tail traffic | Audit URLs rank for "[site] SEO audit" |

## Competitor Comparison

| Competitor | Public Audits | Embeddable Badge | Free Lead Capture |
|------------|---------------|------------------|-------------------|
| Ahrefs | ❌ | ❌ | ❌ |
| SEMrush | Limited | ❌ | ❌ |
| Moz | ❌ | ❌ | ❌ |
| SEOptimer | ✅ | ✅ | ✅ |
| SEOIndicator | **✅ (proposed)** | **✅ (proposed)** | **✅ (proposed)** |

**Competitive Advantage**: Most SEO tools don't offer public sharing. SEOptimer does but with aggressive lead capture. We can offer a cleaner, more user-friendly experience.

## Free Tools Constraint Compliance
- ✅ No paid APIs required
- ✅ SVG badges generated server-side (no external dependencies)
- ✅ Static audit pages (fast, low cost)
- ✅ All functionality is internal
- ✅ Uses existing audit infrastructure (FEAT-014)

## MVP Scope

**Phase 1 (MVP):**
- Generate public audit URL
- Public audit page with score + top issues
- Small SVG badge

**Phase 2:**
- JavaScript badge with auto-refresh
- Medium/large badge sizes
- Light/dark themes

**Phase 3:**
- Lead capture email input
- Badge analytics dashboard
- White-label badge option (paid tier)

## Success Metrics

| Metric | Current | Target (30 days) | Target (90 days) |
|--------|---------|------------------|------------------|
| Public audits created | 0 | 50 | 200 |
| Embedded badges | 0 | 25 | 100 |
| Badge impressions/month | 0 | 1,000 | 10,000 |
| Organic sessions from badges | 0 | 20 | 100 |
| Public audit page views | 0 | 200 | 1,000 |

## Dependencies
- FEAT-014 (Technical SEO Site Audit) - Must be complete first
- FEAT-016 (Interactive SEO Tools) - Score calculation logic

## Related
- Complements: FEAT-014 (provides sharing for audits)
- Complements: FEAT-020 (SEO guides link to public audits)
- Drives: Organic traffic growth strategy

## Notes
This feature transforms the SEO audit from a private tool into a public-facing lead generation and brand awareness asset. Every user who embeds a badge becomes a brand ambassador, and every public audit URL is an entry point for new users.

The key insight is that SEO professionals WANT to share their work - they need deliverables for clients, want to show progress to stakeholders, and benefit from transparency. By enabling sharing, we turn user success into our growth engine.

---

*Created by Innovator agent on 2026-03-05*

---

## Comments

### [2026-03-05 14:15 GMT+2] Agent: Architect
**Specification SPEC-33 created and approved.**

**Technical Feasibility: APPROVED**
- No paid APIs required (CONSTITUTION.md compliant)
- Uses existing FEAT-014 audit infrastructure
- Static generation for zero runtime cost
- Low security risk (read-only public data)

**Key Architecture Decisions:**
1. Random 32-char hash for audit URLs (collision-resistant, non-guessable)
2. ISR (Incremental Static Regeneration) for public pages
3. SVG badges generated on-demand (lightweight, scalable)
4. Privacy-first: no PII in public reports

**Task Breakdown:**
- 35 tasks across 6 phases identified in SPEC-33
- MVP: Phase 1-2 (backend + frontend components)
- Full rollout: Phase 3-6 (badge system + SEO + testing)

**Handoff to SpecSystemAnalyst:** Ready for task breakdown.

### [2026-03-19 09:30 GMT+2] DocSystemAnalyst - Documentation Complete

**Status:** Documentation updated ✅

**Actions Taken:**
1. Verified workflow enabled: `workflowEnabled: true`
2. Cleared stale completion marker
3. Created lock file for FEAT-33
4. Verified all 35 core tasks complete (TASK-33-1 to TASK-33-35)
5. Updated DOCUMENTATION.md with comprehensive FEAT-033 documentation

**Documentation Coverage:**
| Section | Content |
|---------|---------|
| Feature Description | Public audit reports and embeddable badges |
| Component Locations | All frontend and backend files listed |
| Database Schema | Audits modifications, views, impressions tables |
| API Endpoints | 8 endpoints documented |
| URL Format | Query parameter approach for static export |
| Badge Embed Codes | 3 formats (SVG, JS, iframe) |
| Auto-Refresh Features | 6 features documented |
| Security Implementation | 8 security measures |
| SEO Implementation | Meta tags, OG tags, JSON-LD |
| Test Coverage | 206+ tests documented |
| Production Status | Complete (35/36 tasks) |
| Architecture Notes | Static export compatibility |

**Tasks Status:**
- TASK-33-1 to TASK-33-35: Done ✅
- TASK-33-36: New Feature (follow-up re-deployment task - does not block documentation)

**Feature Complete:** FEAT-033 is fully documented and production-ready.
