# SPEC-33: Public SEO Audit Reports & Embeddable Score Widget

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Feature Reference
**FEAT-033**: Public SEO Audit Reports & Embeddable Score Widget

## Overview

Enable users to share SEO audit results publicly via unique URLs and embed SEO score badges on their websites. This transforms private audit results into public-facing lead generation and brand awareness assets, driving organic traffic through backlinks from embedded widgets and capturing leads through free public audits.

## Technical Architecture

### URL Structure

| URL Pattern | Purpose |
|-------------|---------|
| `/audit/{audit_hash}/` | Public audit page |
| `/badge/{audit_hash}/{size}.svg` | SVG badge image |
| `/badge/{audit_hash}/config.json` | Badge configuration for JS embed |
| `/badge.js` | JavaScript embed script |

**Hash Format**: 32-character alphanumeric string (collision-resistant, non-guessable)

### Directory Structure

```
frontend/
├── app/
│   └── audit/
│       └── [hash]/
│           ├── page.tsx                # Public audit page
│           ├── layout.tsx              # Audit layout (no auth required)
│           └── not-found.tsx           # Invalid hash 404
├── app/
│   └── badge/
│       └── [hash]/
│           └── [size]/
│               └── route.ts            # SVG badge route
├── lib/
│   └── audit/
│       ├── public-audit.ts             # Public audit helpers
│       ├── badge-generator.ts          # SVG badge generation
│       └── types.ts                    # TypeScript interfaces
├── components/
│   └── audit/
│       ├── PublicAuditPage.tsx         # Public audit display
│       ├── ScoreOverview.tsx           # Score summary section
│       ├── CategoryBreakdown.tsx       # Category scores display
│       ├── TopIssues.tsx               # Top issues preview
│       ├── PublicAuditCTA.tsx          # CTA for new users
│       ├── BadgePreview.tsx            # Badge preview & embed code
│       └── BadgeWidget.tsx             # Interactive badge widget
└── __tests__/
    └── audit/
        └── ...

backend/
├── app/
│   ├── Http/Controllers/Api/V1/
│   │   └── PublicAuditController.php   # Public audit API
│   ├── Models/
│   │   └── PublicAudit.php             # Public audit model
│   └── Services/
│       └── BadgeService.php            # Badge generation service
├── database/migrations/
│   ├── add_visibility_to_audits.php    # Add visibility column
│   └── create_public_audits_table.php  # Public audit tracking
└── routes/
    └── api.php                         # Public audit routes
```

## Database Schema

### Audits Table Modifications

```sql
ALTER TABLE audits ADD COLUMN visibility ENUM('private', 'public') DEFAULT 'private';
ALTER TABLE audits ADD COLUMN public_hash VARCHAR(32) UNIQUE NULL;
ALTER TABLE audits ADD COLUMN public_hash_created_at TIMESTAMP NULL;
```

### Public Audits Table (Analytics Tracking)

```sql
CREATE TABLE public_audit_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    public_hash VARCHAR(32) NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    referrer VARCHAR(500) NULL,
    ip_hash VARCHAR(64) NULL,           -- Hashed for privacy
    user_agent VARCHAR(500) NULL,
    country_code VARCHAR(2) NULL,
    is_lead_capture TINYINT(1) DEFAULT 0,
    lead_email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_audit_id (audit_id),
    INDEX idx_public_hash (public_hash),
    INDEX idx_viewed_at (viewed_at),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);
```

### Badge Impressions Table

```sql
CREATE TABLE badge_impressions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    audit_id BIGINT UNSIGNED NOT NULL,
    public_hash VARCHAR(32) NOT NULL,
    referrer_domain VARCHAR(255) NULL,
    impression_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    clicked_at TIMESTAMP NULL,
    click_target VARCHAR(10) NULL,       -- 'audit' or 'home'
    ip_hash VARCHAR(64) NULL,
    
    INDEX idx_audit_id (audit_id),
    INDEX idx_public_hash (public_hash),
    INDEX idx_impression_at (impression_at),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);
```

## Component Specifications

### 1. PublicAuditPage Component

**Responsibilities:**
- Fetch audit data by public hash
- Render public-friendly audit summary
- Handle 404 for invalid/expired hashes
- Inject SEO metadata

**Props Interface:**
```typescript
interface PublicAuditPageProps {
  params: { hash: string };
}

interface PublicAuditData {
  siteUrl: string;
  auditedAt: string;
  overallScore: number;
  categoryScores: CategoryScore[];
  topIssues: Issue[];
  recommendations: Recommendation[];
  isPartial: boolean;  // true for anonymous users (limited data)
}
```

**Static Generation:**
- Use ISR (Incremental Static Regeneration) with 1-hour revalidation
- Fallback to on-demand generation for new audits

### 2. ScoreOverview Component

**Features:**
- Large circular score display (0-100)
- Color-coded by score range
- Site URL and audit date
- "Get your free audit" CTA

**Score Color Mapping:**
| Score Range | Color | Status |
|-------------|-------|--------|
| 90-100 | Green (#22c55e) | Excellent |
| 70-89 | Blue (#3b82f6) | Good |
| 50-69 | Yellow (#eab308) | Needs Work |
| 30-49 | Orange (#f97316) | Poor |
| 0-29 | Red (#ef4444) | Critical Issues |

### 3. CategoryBreakdown Component

**Features:**
- Progress bars for each category
- Score labels and descriptions
- Expandable issue counts

**Categories:**
- Technical SEO
- On-Page SEO
- Content Quality
- Mobile & Performance

### 4. TopIssues Component

**Features:**
- Top 5 issues for anonymous users
- Full issue list for logged-in users
- Issue severity indicators
- "View full report" prompt for anonymous

### 5. PublicAuditCTA Component

**Features:**
- Email capture form for anonymous users
- "Get your free audit" button
- Social sharing buttons
- Badge embed code generator

**Lead Capture Flow:**
1. Anonymous user views public audit
2. Sees summary + top 5 issues
3. Enters email for full report access
4. Email stored with audit reference
5. Redirect to signup or full report

### 6. BadgePreview Component

**Features:**
- Live badge preview
- Size selector (small, medium, large)
- Theme selector (light, dark)
- Embed code generator (HTML, JS, iframe)

**Embed Code Types:**
```html
<!-- Small Badge (SVG) -->
<a href="https://seoindicator.com/audit/abc123/">
  <img src="https://seoindicator.com/badge/abc123/small.svg" alt="SEO Score: 85/100" />
</a>

<!-- Medium Badge (JS) -->
<div data-seoindicator-badge="abc123" data-size="medium" data-theme="light"></div>
<script src="https://seoindicator.com/badge.js" async></script>

<!-- Large Badge (iframe) -->
<iframe src="https://seoindicator.com/badge/abc123/large?theme=dark" 
        width="300" height="150" frameborder="0">
</iframe>
```

### 7. BadgeWidget Component (Interactive JS)

**Features:**
- Auto-refresh badge every 24 hours
- Click tracking
- Responsive sizing
- Light/dark theme support

**API:**
```javascript
window.SEOIndicatorBadge = {
  init: function(config) {
    // config: { auditId, size, theme, container }
  },
  refresh: function() {
    // Fetch latest score
  },
  setTheme: function(theme) {
    // 'light' | 'dark'
  }
};
```

## Badge Design Specifications

### Small Badge (Inline)

```
┌──────────────────────────┐
│ SEO: 85/100         ↗    │
│ Powered by SEOIndicator  │
└──────────────────────────┘
```
- **Dimensions**: 120x40px
- **Use Case**: Inline in blog posts, footer links
- **Format**: SVG only

### Medium Badge (Sidebar)

```
┌─────────────────────────────┐
│                             │
│     ◯ 85                    │
│     SEO Score               │
│                             │
│   Powered by SEOIndicator   │
│                             │
└─────────────────────────────┘
```
- **Dimensions**: 150x100px
- **Use Case**: Sidebar widgets, about pages
- **Format**: SVG or JS embed

### Large Badge (Footer)

```
┌────────────────────────────────────┐
│                                    │
│         ◯ 85                       │
│      SEO Score                     │
│      Good                          │
│                                    │
│   Technical: 85  On-Page: 72       │
│   Content: 68    Mobile: 90        │
│                                    │
│    Get your free SEO audit →       │
│    Powered by SEOIndicator         │
│                                    │
└────────────────────────────────────┘
```
- **Dimensions**: 300x150px
- **Use Case**: Footer widgets, partner pages
- **Format**: iframe or JS embed

## API Endpoints

### Public Audit Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/public-audit/{hash}` | Get public audit data |
| POST | `/api/v1/public-audit/{hash}/lead` | Submit lead capture email |
| GET | `/api/v1/public-audit/{hash}/badge` | Get badge configuration |

### Badge Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/badge/{hash}/{size}.svg` | Get SVG badge |
| GET | `/badge/{hash}/config.json` | Get badge JS config |
| POST | `/api/v1/badge/impression` | Track badge impression |
| POST | `/api/v1/badge/click` | Track badge click |

### Audit Management Endpoints (Authenticated)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/audits/{id}/publish` | Generate public hash |
| DELETE | `/api/v1/audits/{id}/unpublish` | Remove public access |
| GET | `/api/v1/audits/{id}/embed-code` | Get embed code snippets |

## Security Considerations

### Access Control

| Risk | Mitigation |
|------|------------|
| Hash guessing | 32-char alphanumeric (62^32 combinations) |
| ID enumeration | Hash is not derived from ID (random UUID) |
| Private audit exposure | Explicit `visibility` check before serving |
| Rate limiting | 100 views/hour per hash for anonymous users |

### Data Privacy

| Concern | Implementation |
|---------|---------------|
| No PII in public reports | Only technical SEO data, no personal info |
| IP anonymization | Hash IPs before storage (SHA-256 + salt) |
| User consent | Explicit toggle required before publishing |
| Right to revoke | User can unpublish at any time |

### Badge Security

| Risk | Mitigation |
|------|------------|
| XSS via embed | No user content in badge, sanitize all data |
| Clickjacking | X-Frame-Options on sensitive pages |
| CSRF on tracking | Stateless tracking, no session required |
| Hotlinking abuse | Referrer check, rate limiting per domain |

## SEO Implementation

### Meta Tags

```html
<title>{Site URL} SEO Audit Report | SEOIndicator</title>
<meta name="description" content="SEO audit for {site_url} - Score: {score}/100. View technical SEO analysis and recommendations.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://seoindicator.com/audit/{hash}/">
```

### Open Graph Tags

```html
<meta property="og:title" content="{Site URL} SEO Score: {score}/100">
<meta property="og:description" content="SEO audit results for {site_url}">
<meta property="og:image" content="https://seoindicator.com/badge/{hash}/og.png">
<meta property="og:url" content="https://seoindicator.com/audit/{hash}/">
<meta property="og:type" content="website">
```

### JSON-LD Schema

```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "{Site URL} SEO Audit Report",
  "description": "SEO audit results for {site_url}",
  "publisher": {
    "@type": "Organization",
    "name": "SEOIndicator",
    "url": "https://seoindicator.com"
  },
  "mainEntity": {
    "@type": "Analysis",
    "result": {
      "@type": "Rating",
      "ratingValue": "{score}",
      "bestRating": "100",
      "worstRating": "0"
    }
  }
}
```

## Task Breakdown (For SpecSystemAnalyst)

### Phase 1: Database & Backend
- TASK-33-1: Add visibility columns to audits table
- TASK-33-2: Create public_audit_views table
- TASK-33-3: Create badge_impressions table
- TASK-33-4: Implement PublicAudit model
- TASK-33-5: Create PublicAuditController with endpoints
- TASK-33-6: Implement BadgeService for SVG generation
- TASK-33-7: Create public audit API routes

### Phase 2: Frontend Components
- TASK-33-8: Create PublicAuditPage component
- TASK-33-9: Create ScoreOverview component
- TASK-33-10: Create CategoryBreakdown component
- TASK-33-11: Create TopIssues component
- TASK-33-12: Create PublicAuditCTA component
- TASK-33-13: Create BadgePreview component
- TASK-33-14: Implement public audit page route

### Phase 3: Badge System
- TASK-33-15: Create SVG badge route handler
- TASK-33-16: Implement badge.js embed script
- TASK-33-17: Create badge impression tracking API
- TASK-33-18: Create badge click tracking API
- TASK-33-19: Implement badge auto-refresh mechanism

### Phase 4: User Integration
- TASK-33-20: Add "Publish Audit" UI to dashboard
- TASK-33-21: Create embed code generator UI
- TASK-33-22: Add visibility toggle to audit settings
- TASK-33-23: Implement lead capture flow
- TASK-33-24: Create badge management page

### Phase 5: SEO & Analytics
- TASK-33-25: Implement meta tags and OG tags
- TASK-33-26: Add JSON-LD schema to public pages
- TASK-33-27: Create OG image generation for audits
- TASK-33-28: Implement public audit analytics
- TASK-33-29: Add badge analytics dashboard

### Phase 6: Testing & Launch
- TASK-33-30: Unit tests for BadgeService
- TASK-33-31: Unit tests for PublicAuditController
- TASK-33-32: Component tests for frontend
- TASK-33-33: Integration tests for public audit flow
- TASK-33-34: E2E tests for badge embedding
- TASK-33-35: Performance optimization and launch

## Performance Targets

| Metric | Target |
|--------|--------|
| Public Audit Page Load | < 2s (LCP) |
| SVG Badge Generation | < 100ms |
| Badge.js Load Time | < 50KB gzip |
| Badge Impression Tracking | < 50ms async |
| Database Query (audit lookup) | < 10ms |

## Analytics Events

| Event | Category | Properties |
|-------|----------|------------|
| public_audit_view | Page View | hash, score, referrer |
| public_audit_lead | Conversion | hash, email (hashed) |
| badge_impression | Engagement | hash, size, referrer_domain |
| badge_click | Conversion | hash, target |
| embed_code_copied | Engagement | hash, type (svg/js/iframe) |

## Success Metrics

| Metric | Current | Target (30 days) | Target (90 days) |
|--------|---------|------------------|------------------|
| Public audits created | 0 | 50 | 200 |
| Embedded badges | 0 | 25 | 100 |
| Badge impressions/month | 0 | 1,000 | 10,000 |
| Organic sessions from badges | 0 | 20 | 100 |
| Public audit page views | 0 | 200 | 1,000 |
| Lead captures from public audits | 0 | 30 | 150 |

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| FEAT-014 (Technical SEO Audit) | ✅ Deployed | Provides audit data |
| FEAT-016 (Interactive SEO Tools) | ✅ Deployed | Score calculation logic |
| Next.js | ✅ Available | Existing frontend framework |
| Shadcn/UI | ✅ Available | Existing component library |
| Laravel API | ✅ Available | Backend infrastructure |

## Legal & Compliance

### Privacy Considerations

| Concern | Implementation |
|---------|---------------|
| No PII in public reports | Only technical SEO data exposed |
| User consent required | Explicit toggle to publish |
| Right to revoke | User can unpublish instantly |
| GDPR compliance | No personal data in public URLs |
| CCPA compliance | No sale of data, clear opt-out |

### Terms of Use

1. Users own their audit data
2. SEOIndicator may display branding on public pages
3. Users may not publish audits of third-party sites without permission
4. SEOIndicator reserves right to remove inappropriate content

## Competitive Advantage

| Competitor | Public Audits | Embeddable Badge | Free Lead Capture |
|------------|---------------|------------------|-------------------|
| Ahrefs | ❌ | ❌ | ❌ |
| SEMrush | Limited | ❌ | ❌ |
| Moz | ❌ | ❌ | ❌ |
| SEOptimer | ✅ | ✅ | ✅ |
| SEOIndicator | **✅ (this feature)** | **✅ (this feature)** | **✅ (this feature)** |

**Differentiation**: Cleaner UI, no aggressive lead capture, user-controlled visibility.

---

## Comments

### [2026-03-05 14:15 GMT+2] Agent: Architect
**Specification created based on FEAT-033 requirements.**

**Key Design Decisions:**
1. Random 32-char hash for security (not ID-derived)
2. ISR for public pages (fast, low cost, SEO-friendly)
3. SVG badges for simplicity (no external dependencies)
4. Phased rollout starting with core functionality
5. Privacy-first approach (no PII in public reports)

**Risk Assessment: LOW**
- No paid APIs required (CONSTITUTION.md compliant)
- Uses existing audit infrastructure
- Static content generation
- Low security risk (read-only public data)

**Handoff to SpecSystemAnalyst:**
- 35 tasks identified across 6 phases
- Prioritize Phase 1-2 for MVP (public page + basic badge)
- Phase 3-4 for full badge system and user integration
- Phase 5-6 for SEO optimization and launch

### [2026-03-05 21:25 GMT+2] Agent: SpecSystemAnalyst
**Task breakdown completed.**

**Tasks Created:** 35 tasks (TASK-33-1 to TASK-33-35)

**Phase Summary:**
| Phase | Tasks | Focus | Effort |
|-------|-------|-------|--------|
| Phase 1 (Backend) | TASK-33-1 to 33-7 | Database, Models, Controllers | 18h |
| Phase 2 (Frontend) | TASK-33-8 to 33-14 | Components, Pages | 20h |
| Phase 3 (Badge System) | TASK-33-15 to 33-19 | SVG routes, JS embed, Tracking | 15h |
| Phase 4 (User Integration) | TASK-33-20 to 33-24 | Dashboard UI, Lead capture | 14h |
| Phase 5 (SEO & Analytics) | TASK-33-25 to 33-29 | Meta tags, Schema, Analytics | 16h |
| Phase 6 (Testing & Launch) | TASK-33-30 to 33-35 | Tests, Performance, Launch | 24h |

**Total Effort Estimate:** ~107 hours

**Priority Recommendations:**
- **HIGH Priority (start first):** TASK-33-1 to 33-5, 33-7, 33-8, 33-14 (MVP core)
- **Medium Priority:** Badge system, user integration, SEO
- **Lower Priority:** Analytics dashboard, auto-refresh

**MVP Path:** Phase 1 → Phase 2 → TASK-33-15 (SVG badges)
- Enables: Public audit pages with embeddable SVG badges
- Effort: ~38 hours for MVP

**Key Dependencies:**
- All Phase 1 tasks are foundation tasks (no dependencies)
- Phase 2 depends on Phase 1 (API endpoints)
- Phase 3 depends on Phase 1-2 (badge generation needs audit data)

**Handoff to ProjectManager:** Ready for prioritization.
