/**
 * TASK-34-9: AI Visibility Create Project Page (Client Component)
 * 
 * Client-side logic for creating a new AI Visibility project.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-9.md
 */

'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft } from 'lucide-react';
import { CreateProjectForm } from '@/components/ai-visibility/CreateProjectForm';
import { Button } from '@/components/ui/button';

// ============================================
// Client Component
// ============================================

export function NewAIVisibilityProjectClient() {
  const router = useRouter();

  const handleSuccess = (projectId: number) => {
    router.push(`/ai-visibility/${projectId}`);
  };

  const handleCancel = () => {
    router.push('/ai-visibility');
  };

  return (
    <main className="container mx-auto p-6 max-w-2xl" role="main">
      {/* Back Button */}
      <Button
        variant="ghost"
        onClick={handleCancel}
        className="mb-6 inline-flex items-center gap-2"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Projects
      </Button>

      {/* Create Project Form */}
      <div className="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm">
        <CreateProjectForm
          onSuccess={handleSuccess}
          onCancel={handleCancel}
        />
      </div>
    </main>
  );
}
