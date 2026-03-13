# SPEC-030: Legal + About + How It Works + Cookie Consent

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Overview
Core site pages including Terms & Privacy, About, How It Works, and a cookie consent banner. Frontend-focused feature with SEO-optimized content and minimal backend requirements.

---

## Architecture

### Component Structure
```
frontend/
├── app/
│   ├── terms/
│   │   └── page.tsx              # Terms & Privacy page
│   ├── about/
│   │   └── page.tsx              # About page
│   ├── how-it-works/
│   │   └── page.tsx              # How It Works page
│   └── layout.tsx                # Root layout (add CookieConsent)
├── components/
│   ├── layout/
│   │   └── CookieConsent.tsx     # Cookie consent banner component
│   └── pages/
│       ├── TermsContent.tsx      # Terms & Privacy content
│       ├── AboutContent.tsx      # About page content
│       └── HowItWorksContent.tsx # How It Works content
└── lib/
    └── utils/
        └── cookieConsent.ts      # Cookie consent utilities
```

### Data Flow
```
User visits site
    ↓
CookieConsent component checks localStorage
    ↓
If no consent record → Show banner
    ↓
User accepts/declines → Store in localStorage
    ↓
Apply consent preferences (analytics, marketing)
```

---

## Technical Specifications

### 1. Terms & Privacy Page
**Route:** `/terms`
**File:** `frontend/app/terms/page.tsx`

**Content Structure:**
- Combined Terms of Service and Privacy Policy
- Sections: Introduction, Terms of Use, Privacy Policy, Cookie Policy, Contact
- Last updated date displayed
- Downloadable PDF option (optional)

**SEO Metadata:**
```typescript
export const metadata: Metadata = {
  title: 'Terms & Privacy | SEOIndicator',
  description: 'Terms of Service and Privacy Policy for SEOIndicator SEO analysis platform.',
  robots: 'index, follow',
};
```

### 2. About Page
**Route:** `/about`
**File:** `frontend/app/about/page.tsx`

**Content Structure:**
- Hero section with mission statement
- Product overview (what SEOIndicator does)
- Key features highlights
- Team/company information (if applicable)
- Call-to-action to sign up

**SEO Metadata:**
```typescript
export const metadata: Metadata = {
  title: 'About SEOIndicator | SEO Analysis Platform',
  description: 'Learn about SEOIndicator - your all-in-one SEO analysis and monitoring platform for tracking keyword rankings and improving visibility.',
  robots: 'index, follow',
};
```

### 3. How It Works Page
**Route:** `/how-it-works`
**File:** `frontend/app/how-it-works/page.tsx`

**Content Structure:**
- Step-by-step user journey
- Indicator explanations (17 SEO indicators from FEAT-018)
- Scoring methodology breakdown
- Weight system explanation
- FAQ section

**Indicator Categories (from FEAT-018):**
| Category | Indicators | Weight |
|----------|------------|--------|
| Technical | robots.txt, sitemap, SSL, mobile-friendly, canonical, hreflang | 30% |
| Content | title tag, meta description, H1, keyword density, images | 40% |
| Performance | page speed, Core Web Vitals | 20% |
| UX | mobile viewport, font readability | 10% |

**SEO Metadata:**
```typescript
export const metadata: Metadata = {
  title: 'How It Works | SEOIndicator SEO Analysis',
  description: 'Understand how SEOIndicator analyzes your website using 17 SEO indicators and weighted scoring to improve your search visibility.',
  robots: 'index, follow',
};
```

### 4. Cookie Consent Banner
**Component:** `frontend/components/layout/CookieConsent.tsx`

**UI Behavior:**
- Fixed position at bottom of viewport
- Appears on first visit (no localStorage record)
- Disappears after user action (accept/decline)
- Does not reappear after consent given
- "Learn more" link to privacy policy

**State Management:**
```typescript
interface CookieConsentState {
  necessary: boolean;      // Always true
  analytics: boolean;      // User choice
  marketing: boolean;      // User choice
  timestamp: string;       // When consent was given
}
```

**localStorage Key:** `seoindicator_cookie_consent`

**UI Design:**
- Dismissible banner with blur backdrop (optional)
- "Accept All" button (primary)
- "Necessary Only" button (secondary)
- "Customize" option (expandable, optional)
- Link to privacy policy

---

## Security Considerations

### Cookie Consent (GDPR/CCPA Compliance)
- Consent must be freely given, specific, and informed
- Pre-checked boxes NOT allowed
- Clear separation between cookie categories
- Easy withdrawal of consent (link in footer)
- Consent record includes timestamp

### Content Security
- All static content (no user input)
- No XSS vectors
- CSP-friendly implementation

### Privacy
- No tracking before consent
- Consent preference stored locally (localStorage)
- No server-side consent storage required

---

## Accessibility (WCAG 2.1 AA)
- All pages keyboard navigable
- Proper heading hierarchy (h1 → h2 → h3)
- ARIA labels for cookie banner buttons
- Focus management for banner dismissal
- Color contrast ratio ≥ 4.5:1
- Screen reader announcements for consent changes

---

## SEO Optimization

### Schema Markup
```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Terms & Privacy",
  "description": "Terms of Service and Privacy Policy",
  "publisher": {
    "@type": "Organization",
    "name": "SEOIndicator"
  }
}
```

### Technical SEO
- Static pages with clean URLs
- Semantic HTML structure
- Optimized meta descriptions (150-160 chars)
- Open Graph tags for social sharing
- XML sitemap inclusion

---

## Tasks Breakdown

### Phase 1: Core Pages (3 tasks)
| ID | Task | Description | File |
|----|------|-------------|------|
| TASK-30-1 | Terms & Privacy Page | Create /terms route with legal content | [TASK-30-1.md](../TASKS/TASK-30-1.md) |
| TASK-30-2 | About Page | Create /about route with mission and features | [TASK-30-2.md](../TASKS/TASK-30-2.md) |
| TASK-30-3 | How It Works Page | Create /how-it-works with indicator explanations | [TASK-30-3.md](../TASKS/TASK-30-3.md) |

### Phase 2: Cookie Consent (2 tasks)
| ID | Task | Description | File |
|----|------|-------------|------|
| TASK-30-4 | Cookie Consent Component | Create banner component with state management | [TASK-30-4.md](../TASKS/TASK-30-4.md) |
| TASK-30-5 | Consent Integration | Integrate with layout, add footer consent link | [TASK-30-5.md](../TASKS/TASK-30-5.md) |

### Phase 3: Testing (2 tasks)
| ID | Task | Description | File |
|----|------|-------------|------|
| TASK-30-6 | Unit Tests | Component tests for pages and cookie consent | [TASK-30-6.md](../TASKS/TASK-30-6.md) |
| TASK-30-7 | Accessibility Tests | A11y tests for all pages and banner | [TASK-30-7.md](../TASKS/TASK-30-7.md) |

---

## Dependencies
- **FEAT-018** (SEO Analysis Engine) - For indicator descriptions on How It Works page
- Next.js static export configuration (existing)
- Shadcn/UI components (existing)

---

## Risks & Mitigations

| Risk | Level | Mitigation |
|------|-------|------------|
| Legal content accuracy | Low | Use standard template, legal review recommended |
| GDPR compliance | Medium | Follow standard consent patterns, document consent flow |
| Content maintenance | Low | Version control for legal documents |

---

## Out of Scope
- User account-specific legal pages
- Dynamic terms based on subscription tier
- Multi-language support (future consideration)
- Cookie preference center (customize modal)

---

## Comments
<!-- Agent communication happens here -->

### [2026-03-04 ProjectManager]
**Phase 1 Prioritization Complete:**
- TASK-30-1 (Terms & Privacy): `To Develop` ✅
- TASK-30-2 (About Page): `To Develop` ✅
- TASK-30-3 (How It Works): `To Write Tests` ✅
- All Phase 1 tasks prioritized and ready for PreTester

**Phase 2 Status:**
- TASK-30-4 (Cookie Consent): `To Prioritize` - awaiting prioritization
- TASK-30-5 (Consent Integration): depends on TASK-30-4

**Phase 3 Status:**
- TASK-30-6 (Unit Tests): depends on all page tasks
- TASK-30-7 (Accessibility Tests): depends on all tasks

**SPEC status updated to `In Progress`**

### [2026-03-04 SpecSystemAnalyst]
- Broken down into 7 tasks across 3 phases
- All task files created in TASKS/
- **Phase 1 (Core Pages):** TASK-30-1 to TASK-30-3 (Terms, About, How It Works)
- **Phase 2 (Cookie Consent):** TASK-30-4 to TASK-30-5 (Component + Integration)
- **Phase 3 (Testing):** TASK-30-6 to TASK-30-7 (Unit tests + A11y tests)
- Task dependencies: TASK-30-5 depends on TASK-30-4; TASK-30-6 depends on all page tasks; TASK-30-7 depends on all tasks
- Risk: TASK-30-4 is Medium (GDPR compliance); all others are Low
- Status updated to "To Prioritize"

### [2026-02-28 Architect]
- Feature analyzed and specification created
- Low risk - primarily static content and simple UI component
- No backend changes required
- Cookie consent follows GDPR best practices

---

*Created by Architect | 2026-02-28*

### [2026-03-07 DocSystemAnalyst]
**Specification Complete ✅**

All 7 tasks completed:
| Task | Description | Status |
|------|-------------|--------|
| TASK-30-1 | Terms & Privacy Page | ✅ Done |
| TASK-30-2 | About Page | ✅ Done |
| TASK-30-3 | How It Works Page | ✅ Done |
| TASK-30-4 | Cookie Consent Component | ✅ Done |
| TASK-30-5 | Cookie Consent Integration | ✅ Done |
| TASK-30-6 | Unit Tests | ✅ Done |
| TASK-30-7 | Accessibility Tests | ✅ Done |

**Documentation:** DOCUMENTATION.md updated with all task sections
**Tests:** 145 accessibility tests passing
**Manual Testing:** Color contrast and screen reader testing pending (follow-up task)
**Status:** Done - Feature fully deployed

### [2026-03-10 DocSystemAnalyst]
- Re-verified completion after a `FEAT-30` alias trigger; canonical files remain `FEAT-030.md` and `SPEC-030.md`
- Confirmed all seven TASK-30-* items remain `Done`
- Confirmed documentation coverage remains current; no spec changes required
- Status remains `Done`

### [2026-03-10 DocSystemAnalyst | SPEC-30 alias re-verification]
- Trigger arrived as `SPEC-30`; canonical project file remains `SPEC-030.md`
- Re-confirmed TASK-30-1 through TASK-30-7 are all `Done`
- Re-confirmed documentation coverage is present for Terms & Privacy, About, How It Works, cookie consent, consent integration, and accessibility coverage
- No specification updates were required; status remains `Done`
- Fresh completion markers will be re-written for both `SPEC-30` and `SPEC-030` to tolerate non-padded and zero-padded triggers

### [2026-03-10 DocSystemAnalyst | SPEC-30 alias replay housekeeping]
- Orchestration trigger again arrived as `SPEC-30`; `PROJECT-SEOINDICATOR/SPECIFICATIONS/SPEC-30.md` does not exist and the canonical file remains `SPEC-030.md`
- Re-checked TASK-30-1 through TASK-30-7 and confirmed every child item is still `Done`
- Re-checked `DOCUMENTATION.md` coverage for Terms & Privacy, About, How It Works, Cookie Consent, Cookie Consent Integration, and Accessibility Tests; no drift found
- Refreshed alias/canonical lock handling and completion markers so both padded and non-padded IDs resolve cleanly
- Specification content remains accurate; status stays `Done`

### [2026-03-10 DocSystemAnalyst | SPEC-30 explicit user-trigger verification]
- Explicit user trigger requested `SPEC-30`; alias file still does not exist, so the canonical specification remains `PROJECT-SEOINDICATOR/SPECIFICATIONS/SPEC-030.md`
- Re-verified all child tasks (`TASK-30-1` through `TASK-30-7`) directly from disk and confirmed each one still reports `Done`
- Re-verified documentation anchors for Cookie Consent Component (line 7118), Cookie Consent Integration (line 7257), Accessibility Tests (line 7456), About Page (line 7629), Terms & Privacy Page (line 39413), and How It Works Page (line 39823)
- No documentation or specification drift was found; spec status remains `Done`
- Alias/canonical completion markers are being refreshed again for both `SPEC-30` and `SPEC-030`, with temporary lock coverage kept in sync during this pass

### [2026-03-12 18:48 GMT+2 DocSystemAnalyst]
**Re-verification pass complete ✅**

- Trigger: Orchestrator task file referenced `SPEC-30`
- File on disk: `SPECIFICATIONS/SPEC-30.md` - matches trigger
- Verified all 7 tasks (TASK-30-1 through TASK-30-7): All `Done` ✅
- Verified FEAT-30 status: `Done` ✅
- Verified DOCUMENTATION.md coverage at line 10562+ (Cookie Consent Component) ✅
- Production URLs: `/terms/`, `/about/`, `/how-it-works/` ✅
- Test coverage: 145 accessibility tests ✅
- Stale lock removed: `SPEC-30.lock` ✅
- Completion marker written: `logs/completed/specification/SPEC-30.json`
- Active-agents entry cleared
- Specification status remains `Done`

### [2026-03-12 16:27 GMT+2 DocSystemAnalyst]
**Verification pass complete ✅**

- Trigger: Orchestrator task file referenced `SPEC-30`
- File on disk: `SPECIFICATIONS/SPEC-30.md` - matches trigger
- Verified all 7 tasks (TASK-30-1 through TASK-30-7): All `Done` ✅
- Verified FEAT-30 status: `Done` ✅
- Verified DOCUMENTATION.md coverage at lines 10387+ (Cookie Consent Component, Cookie Consent Integration) ✅
- Production URLs: `/terms/`, `/about/`, `/how-it-works/` ✅
- Test coverage: 145 accessibility tests ✅
- Actions completed:
  - Wrote completion marker: `logs/completed/specification/SPEC-30.json`
  - Cleared active-agents entry: `docsystemanalyst-SPEC-30-20260312-162854`
- Specification status remains `Done`

### [2026-03-13 12:43 GMT+2 DocSystemAnalyst]
**Verification pass complete ✅**

- Trigger: Task file referenced `SPEC-30`
- File on disk: `SPECIFICATIONS/SPEC-30.md` - matches trigger
- Verified all 7 tasks (TASK-30-1 through TASK-30-7): All `Done` ✅
- Verified FEAT-30 status: `Done` ✅
- DOCUMENTATION.md verified at line 10728+ (Cookie Consent Component) and 10861+ (task summary) ✅
- Production URLs: `/terms/`, `/about/`, `/how-it-works/` ✅
- Test coverage: 145 accessibility tests ✅
- GDPR compliance: granular consent options, no Escape dismissal, withdrawal via footer ✅
- Actions completed:
  - Removed stale lock: `SPECIFICATIONS/.locks/SPEC-30.lock`
  - Wrote completion marker: `logs/completed/specification/SPEC-30.json`
  - Cleared active-agents entry: `docsystemanalyst-SPEC-30-20260313-124337`
- Specification status remains `Done`

### [2026-03-13 15:01 GMT+2 DocSystemAnalyst]
**Verification pass complete ✅**

- Trigger: Task file referenced `SPEC-30`
- File on disk: `SPECIFICATIONS/SPEC-30.md` - matches trigger
- Verified all 7 tasks (TASK-30-1 through TASK-30-7): All `Done` ✅
- Verified FEAT-30 status: `Done` ✅
- DOCUMENTATION.md verified at line 10728+ (Cookie Consent Component, Cookie Consent Integration) ✅
- Production URLs: `/terms/`, `/about/`, `/how-it-works/` ✅
- Test coverage: 145 accessibility tests ✅
- GDPR compliance: granular consent options, no Escape dismissal, withdrawal via footer ✅
- Actions completed:
  - Removed stale lock: `SPECIFICATIONS/.locks/SPEC-30.lock`
  - Wrote completion marker: `logs/completed/specification/SPEC-30.json`
  - Cleared active-agents entry
- Specification status remains `Done`
