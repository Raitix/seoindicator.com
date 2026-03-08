/**
 * TASK-34-17: Competitor Comparison Page (Server Component)
 * 
 * Page for detailed competitor comparison analysis.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

import * as React from 'react';
import { ComparisonPageClient } from './page.client';

// ============================================
// Static Export Compatibility
// ============================================

// Disable dynamic params for static export compatibility
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns a placeholder projectId to satisfy Next.js static export requirement.
 * The actual projectId lookup is done client-side via ComparisonPageClient.
 */
export function generateStaticParams() {
  // Return a placeholder to satisfy Next.js static export
  // The client-side component handles actual projectId routing
  return [{ projectId: '_' }];
}

// ============================================
// Metadata (exported for Next.js - Server Component)
// ============================================

export const metadata = {
  title: 'Competitor Comparison | AI Visibility | SEO Indicator',
  description: 'Compare your brand visibility against competitors across AI platforms like ChatGPT and Perplexity',
  openGraph: {
    title: 'Competitor Comparison | AI Visibility | SEO Indicator',
    description: 'Compare your brand visibility against competitors across AI platforms',
    type: 'website',
  },
};

// ============================================
// Page Component (Server Component)
// ============================================

export default function ComparisonPage() {
  return <ComparisonPageClient />;
}
