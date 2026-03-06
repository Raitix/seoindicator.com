# TASK-33-8: Create PublicAuditPage Component

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Specification
**SPEC-33**: Public SEO Audit Reports & Embeddable Score Widget

## Description
Create the main PublicAuditPage React component that displays public audit results. This is the root component for the public audit URL route.

## Acceptance Criteria
- [ ] Fetches audit data via public API endpoint
- [ ] Handles loading, error, and 404 states
- [ ] Displays site URL, audit date, overall score prominently
- [ ] Shows score breakdown by category
- [ ] Shows top issues (limited for anonymous users)
- [ ] Shows CTA for new users ("Get your free audit")
- [ ] Responsive design (mobile-first)
- [ ] SEO-friendly: proper heading hierarchy, semantic HTML
- [ ] Accessible: ARIA labels, keyboard navigation
- [ ] Unit tests for all render states
- [ ] Uses existing shadcn/ui components

## Dependencies
- TASK-33-5 (PublicAuditController API)
- TASK-33-9, TASK-33-10, TASK-33-11, TASK-33-12 (child components)

## Effort Estimate
**4 hours** - Main page component with state management

## Technical Notes
- Use Next.js App Router (app/audit/[hash]/page.tsx)
- Fetch on server for SEO (SSR/ISR)
- Revalidation: 1 hour (ISR)
- Handle case where audit was unpublished after page generated

## Props Interface
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
  isPartial: boolean;
}
```

## Files to Create
- `frontend/app/audit/[hash]/page.tsx`
- `frontend/components/audit/PublicAuditPage.tsx`
- `frontend/__tests__/components/audit/PublicAuditPage.test.tsx`

---

## Comments

### [2026-03-06 16:13 GMT+2] ProjectManager - Prioritization Assessment

**Decision:** HIGH priority confirmed ✅ → Status: `To Write Tests`

**Priority Assessment:**
- **Main Page Component:** Root component for public audit URL route - the primary user-facing page for the entire feature
- **SEO Critical:** SSR/ISR implementation essential for search engine visibility of public audit pages
- **Integration Point:** Composes 4 child components into a cohesive user experience
- **Business Value:** High - public audit pages drive organic traffic and lead generation
- **Effort:** 4 hours (moderate, well-defined scope)

**Risk Assessment: LOW (confirmed)**
- Frontend-only React/Next.js component
- No external APIs directly (child components handle API calls)
- Uses existing shadcn/ui components
- Standard page composition pattern
- Changes easily rolled back

**Dependency Status:**
| Dependency | Status | Ready? |
|------------|--------|--------|
| TASK-33-5 (PublicAuditController API) | `To Develop` | ❌ NOT READY |
| TASK-33-9 (ScoreOverview Component) | `To Prioritize` | ❌ NOT READY |
| TASK-33-10 (CategoryBreakdown) | `Done` | ✅ READY |
| TASK-33-11 (TopIssues) | `Done` | ✅ READY |
| TASK-33-12 (PublicAuditCTA) | `Done` | ✅ READY |

**TDD Pattern Applied:**
PreTester CAN design tests immediately using:
1. **Mock API responses** for `GET /api/v1/public-audit/{hash}` (TASK-33-5 not ready)
2. **Real components** for CategoryBreakdown, TopIssues, PublicAuditCTA (all Done)
3. **Mock component** for ScoreOverview (TASK-33-9 not ready)
4. **Test SSR behavior** with Next.js testing utilities
5. **Test ISR revalidation** (1-hour TTL)

**Test Categories for PreTester:**
- Page rendering (loading, error, 404 states)
- SSR data fetching with mocked API
- ScoreOverview placeholder/mock integration
- Child component integration (3 Done, 1 mock)
- SEO meta tags and heading hierarchy
- Accessibility (ARIA labels, keyboard navigation)
- Responsive design breakpoints
- Error boundary handling

**Key Technical Points:**
- Next.js App Router: `app/audit/[hash]/page.tsx`
- Server-side rendering for SEO (fetch on server)
- ISR revalidation: 1 hour
- Handle unpublished audit after page generation (SSR fallback)
- Props interface already defined in task

**Execution Order:**
1. PreTester designs tests NOW with mocked dependencies (TDD)
2. TASK-33-5 (API) and TASK-33-9 (ScoreOverview) complete in parallel
3. Developer implements page when dependencies reach `To Test Locally`
4. Integration tests run against full stack

**Handoff to PreTester:** Ready for test design. Use mocked API and ScoreOverview component. 3 of 4 child components are Done and can be tested with real implementations.
