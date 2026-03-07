/**
 * TASK-34-9: AI Visibility Create Project Page
 * 
 * Page for creating a new AI Visibility project.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-9.md
 */

import { NewAIVisibilityProjectClient } from './page.client';

// ============================================
// Metadata (Server Component)
// ============================================

export const metadata = {
  title: 'Create AI Visibility Project | SEO Indicator',
  description: 'Create a new project to track your brand mentions across AI platforms',
};

// ============================================
// Page Component (Server Component)
// ============================================

export default function NewAIVisibilityProjectPage() {
  return <NewAIVisibilityProjectClient />;
}
