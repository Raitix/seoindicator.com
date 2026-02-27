# SPEC-016: Interactive SEO Tools - Technical Specification

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Tasks:
  - [TASK-16-1](../TASKS/TASK-16-1.md) - Setup Tools Infrastructure
  - [TASK-16-2](../TASKS/TASK-16-2.md) - Meta Tag Analyzer Tool
  - [TASK-16-3](../TASKS/TASK-16-3.md) - Keyword Density Checker Tool
  - [TASK-16-4](../TASKS/TASK-16-4.md) - Tool Embedding System
  - [TASK-16-5](../TASKS/TASK-16-5.md) - Email Capture Modal & API
  - [TASK-16-6](../TASKS/TASK-16-6.md) - Lead Storage Service
  - [TASK-16-7](../TASKS/TASK-16-7.md) - Mobile-Friendly Checker Tool
  - [TASK-16-8](../TASKS/TASK-16-8.md) - Social Media Preview Tool
  - [TASK-16-9](../TASKS/TASK-16-9.md) - Tools Landing Page
  - [TASK-16-10](../TASKS/TASK-16-10.md) - Schema Markup & SEO Optimization
  - [TASK-16-11](../TASKS/TASK-16-11.md) - Rate Limiting & Anti-Abuse
  - [TASK-16-12](../TASKS/TASK-16-12.md) - Error Handling & User Feedback
- Complements: SPEC-014 (Technical Audit), SPEC-002 (SEO Guide Engagement)

---

## Overview

Feature embeds interactive tools within SEO guides, allowing users to analyze their own pages instantly. The tools demonstrate SEOIndicator’s value while capturing leads.

---

## Tool Specifications

### 1. Meta Tag Analyzer
```javascript
// Data: title tag, meta description, SERP preview
// Outputs: length warnings, preview, duplicate detection
```

### 2. Mobile-Friendly Checker
```javascript
// Data: viewport tag, responsive design attributes
// Outputs: mobile preview, tap target warnings
```

### 3. Social Media Preview
```javascript
// Data: Open Graph and Twitter Card tags
// Outputs: tag preview, missing property warnings
```

### 4. Keyword Density Checker
```javascript
// Data: keyword frequency, heading analysis
// Outputs: density percentage, over-optimization warnings
```

---

## Architecture

```
User → Next.js UI → API Proxy → Target → Analysis Libraries (Cheerio)
```

### Backend
- **API Proxy**: Secures CORS for external fetch.
- **Libraries**: Cheerio for HTML analysis (no external APIs).
- **Rate Limiting**: Per IP and CAPTCHA after 3 uses.

---

## Database Schema

None required for MVP; lead capture through direct form submission.

---

## Technical Approach

1. Use in-browser libraries for instant feedback.
2. Backend proxy avoids CORS restrictions for external site fetches.
3. Modular architecture allows new tools to be added easily.

---

## Phased Rollout

### Phase 1: MVP Tools
- Meta Tag Analyzer
- Keyword Density Checker
- Embed tools directly in guide pages.

### Phase 2: Advanced Tools
- Mobile-Friendly Checker
- Social Media Preview
- Create standalone tools directory (`/tools/...`).

### Phase 3: SEO Landing Pages
- Add schema markup to tools.
- Create embeddable badges.

---

## Comments
_Agent log_

### [2026-02-27] Agent: SpecSystemAnalyst
Completed task breakdown into 12 tasks:
- **Phase 1 (MVP):** TASK-16-1 to TASK-16-4 (Infrastructure + 2 tools + embedding)
- **Phase 2 (Lead Gen):** TASK-16-5 to TASK-16-6 (Email capture + storage)
- **Phase 3 (Full Suite):** TASK-16-7 to TASK-16-8 (Mobile + Social tools)
- **Phase 4 (SEO):** TASK-16-9 to TASK-16-12 (Landing page + schema + rate limiting + errors)

Key decisions:
1. TASK-16-1 (Infrastructure) is the foundation - must complete first
2. TASK-16-11 (Rate Limiting) marked High priority for abuse prevention
3. Email capture gated behind detailed results for lead generation
4. All tools use Cheerio for HTML parsing (no external APIs)

Status updated to "To Prioritize" for ProjectManager.

### [2026-02-20] Agent: Architect
Created SPEC-016. Key decisions:
1. MVP uses Cheerio for real-time feedback; no reliance on third-party APIs.
2. Includes lead generation gating detailed results after email capture.
Ready for SpecSystemAnalyst breakdown.