/**
 * TASK-34-8: AI Visibility Projects List Page
 * 
 * Main page for viewing and managing AI Visibility projects.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

import * as React from 'react';
import { Plus } from 'lucide-react';
import { ProjectList } from '@/components/ai-visibility/ProjectList';
import { Button } from '@/components/ui/button';
import { AIVisibilityPageClient } from './page.client';

// ============================================
// Metadata (exported for Next.js - Server Component)
// ============================================

export const metadata = {
  title: 'AI Visibility Projects | SEO Indicator',
  description: 'Track your brand mentions across AI platforms like ChatGPT and Perplexity',
  openGraph: {
    title: 'AI Visibility Projects | SEO Indicator',
    description: 'Track your brand mentions across AI platforms like ChatGPT and Perplexity',
    type: 'website',
  },
};

// ============================================
// Page Component (Server Component)
// ============================================

export default function AIVisibilityPage() {
  return <AIVisibilityPageClient />;
}
