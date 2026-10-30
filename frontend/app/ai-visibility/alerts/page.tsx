/**
 * TASK-34-21: AI Visibility Alerts Page
 * 
 * Main page for viewing and managing AI Visibility alerts.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-21.md
 */

import * as React from 'react';
import { Suspense } from 'react';
import { AlertsPageClient } from './page.client';

// ============================================
// Metadata (exported for Next.js - Server Component)
// ============================================

export const metadata = {
  title: 'Alerts | SEOIndicator',
  description: 'View and manage your AI Visibility alerts',
  openGraph: {
    title: 'Alerts | SEOIndicator',
    description: 'View and manage your AI Visibility alerts',
    type: 'website',
    url: '/ai-visibility/alerts',
  },
};

// ============================================
// Loading Skeleton
// ============================================

function AlertsLoadingSkeleton() {
  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-900 p-6">
      <div className="animate-pulse">
        <div className="h-8 w-32 bg-slate-200 dark:bg-slate-700 rounded mb-4" />
        <div className="h-4 w-64 bg-slate-200 dark:bg-slate-700 rounded mb-6" />
        <div className="h-64 bg-slate-200 dark:bg-slate-700 rounded" />
      </div>
    </div>
  );
}

// ============================================
// Page Component (Server Component)
// ============================================

export default function AlertsPage() {
  return (
    <Suspense fallback={<AlertsLoadingSkeleton />}>
      <AlertsPageClient />
    </Suspense>
  );
}
