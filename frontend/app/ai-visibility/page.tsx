/**
 * TASK-34-8: AI Visibility Projects List Page
 * 
 * Main page for viewing and managing AI Visibility projects.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { Plus } from 'lucide-react';
import { ProjectList } from '@/components/ai-visibility/ProjectList';
import { Button } from '@/components/ui/button';

// ============================================
// Metadata (exported for Next.js)
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
// Page Component
// ============================================

export default function AIVisibilityPage() {
  const router = useRouter();

  const handleCreateProject = () => {
    router.push('/ai-visibility/new');
  };

  const handleViewDashboard = (projectId: number) => {
    router.push(`/ai-visibility/${projectId}`);
  };

  const handleEditProject = (projectId: number) => {
    router.push(`/ai-visibility/${projectId}/edit`);
  };

  return (
    <main className="container mx-auto p-6" role="main">
      {/* Page Header */}
      <header className="mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">AI Visibility Projects</h1>
            <p className="text-gray-500 mt-1">
              Track your brand mentions across AI platforms
            </p>
          </div>
          <Button
            onClick={handleCreateProject}
            data-testid="create-project-btn"
            className="inline-flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            Create Project
          </Button>
        </div>
      </header>

      {/* Project List */}
      <ProjectList
        onCreateProject={handleCreateProject}
        onViewDashboard={handleViewDashboard}
        onEditProject={handleEditProject}
      />
    </main>
  );
}
