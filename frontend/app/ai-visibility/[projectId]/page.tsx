/**
 * TASK-34-10: AI Visibility Dashboard Page
 * Server Component Wrapper
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

import { Suspense } from 'react';
import AIVisibilityDashboardPage from './page.client';

// Required for static export compatibility with dynamic routes
export const dynamicParams = false;

/**
 * Static params for dynamic route - client-side rendering
 * Provides a placeholder path for static export compatibility.
 * Actual project data is loaded client-side via the dashboard component.
 */
export async function generateStaticParams() {
  return [{ projectId: 'placeholder' }];
}

/**
 * Loading fallback component
 */
function LoadingFallback() {
  return (
    <main className="space-y-6 p-6">
      <div className="text-center">
        <div className="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-blue-400 border-t-transparent" />
        <p className="mt-2 text-sm text-slate-400">Loading dashboard...</p>
      </div>
    </main>
  );
}

/**
 * AI Visibility Dashboard Page
 * Main dashboard showing analytics, charts, and recent activity
 */
export default function Page() {
  return (
    <Suspense fallback={<LoadingFallback />}>
      <AIVisibilityDashboardPage />
    </Suspense>
  );
}
