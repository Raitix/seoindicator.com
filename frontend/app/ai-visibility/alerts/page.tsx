/**
 * TASK-34-21: AI Visibility Alerts Page
 * 
 * Main page for viewing and managing AI Visibility alerts.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-21.md
 */

import * as React from 'react';
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
// Page Component (Server Component)
// ============================================

export default function AlertsPage() {
  return <AlertsPageClient />;
}
