/**
 * TASK-34-10: AI Visibility Dashboard Page (Server Component Wrapper)
 * 
 * This file exports generateStaticParams for static export compatibility
 * and renders the client component.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

import AIVisibilityDashboardPage from './AIVisibilityDashboardPage';

// Required for static export with dynamic routes
// Returns empty array since this is a client-rendered page
export function generateStaticParams() {
  return [];
}

export default function Page() {
  return <AIVisibilityDashboardPage />;
}
