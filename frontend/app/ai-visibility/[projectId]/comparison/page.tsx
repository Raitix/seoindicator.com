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
