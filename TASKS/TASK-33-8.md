# TASK-33-8: Create PublicAuditPage Component

## STATUS
`To Prioritize`

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
