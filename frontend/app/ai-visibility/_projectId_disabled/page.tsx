/**
 * TASK-34-10: AI Visibility Dashboard Page (Server Component Wrapper)
 * 
 * This file exports generateStaticParams for static export compatibility
 * and renders the client component.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

import AIVisibilityDashboardPage from './AIVisibilityDashboardPage';

// Required for static export with dynamic routes.
// Next.js refuses to export a dynamic route whose generateStaticParams
// returns an empty array, so a single placeholder param is emitted - the
// same value the sibling [projectId] route uses. The client recovers the
// real id from the browser URL during hydration.
export function generateStaticParams() {
  return [{ projectId: 'placeholder' }];
}

export default function Page() {
  return <AIVisibilityDashboardPage />;
}
