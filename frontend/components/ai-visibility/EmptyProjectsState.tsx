/**
 * TASK-34-8: EmptyProjectsState Component
 * 
 * Empty state displayed when user has no AI Visibility projects.
 * Provides helpful messaging and call-to-action to create first project.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import * as React from 'react';
import { FolderPlus } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { EmptyProjectsStateProps } from '@/lib/types/ai-visibility-projects';

export function EmptyProjectsState({
  onCreateProject,
  className,
}: EmptyProjectsStateProps) {
  return (
    <div
      className={cn(
        'flex flex-col items-center py-12 px-4',
        className
      )}
      data-testid="empty-state"
      role="status"
      aria-live="polite"
    >
      {/* Illustration */}
      <div
        data-testid="illustration"
        className="flex justify-center mb-6"
      >
        <FolderPlus
          className="w-16 h-16 text-gray-400"
          data-testid="folder-plus-icon"
        />
      </div>

      {/* Title */}
      <h2 className="text-xl font-semibold text-center mb-2">
        No projects yet
      </h2>

      {/* Description */}
      <p className="text-gray-500 text-center mb-6 max-w-md">
        Start tracking your brand mentions across AI platforms. Create your first project to begin monitoring.
      </p>

      {/* CTA Button */}
      <div className="flex justify-center">
        <button
          onClick={onCreateProject}
          data-testid="create-project-btn"
          className="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primary/90 transition-colors inline-flex items-center gap-2"
          aria-label="Create your first project"
        >
          <FolderPlus className="w-5 h-5" data-testid="folder-plus-icon" />
          Create your first project
        </button>
      </div>

      {/* Help Text */}
      <p className="text-sm text-gray-400 text-center mt-4">
        Free tier includes 5 queries per month
      </p>
    </div>
  );
}

export default EmptyProjectsState;
