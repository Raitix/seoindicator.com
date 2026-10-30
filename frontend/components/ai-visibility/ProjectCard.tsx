/**
 * TASK-34-8: ProjectCard Component
 * 
 * Card component for displaying a single AI Visibility project.
 * Shows project details, metrics, and action buttons.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import * as React from 'react';
import {
  MoreVertical,
  Trash2,
  Settings,
  Play,
  Eye,
  Loader2,
  CheckCircle,
  XCircle,
  Clock,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { DeleteProjectModal } from './DeleteProjectModal';
import type {
  ProjectCardProps,
  AIVisibilityProjectListItem,
} from '@/lib/types/ai-visibility-projects';

// ============================================
// Helper Functions
// ============================================

function formatMentionRate(rate: number): string {
  return `${Math.round(rate * 100)}%`;
}

function formatLastQueried(date: string | null): string {
  if (!date) return 'Never';

  const queriedDate = new Date(date);
  const now = new Date();
  const diffMs = now.getTime() - queriedDate.getTime();
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

  if (diffHours < 24) {
    return `${diffHours}h ago`;
  }

  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 7) {
    return `${diffDays}d ago`;
  }

  return queriedDate.toLocaleDateString();
}

// ============================================
// Component
// ============================================

export function ProjectCard({
  project,
  onViewDashboard,
  onRunQuery,
  onEditProject,
  onDelete,
  isLoading = false,
  className,
}: ProjectCardProps) {
  const [showMenu, setShowMenu] = React.useState(false);
  const [showDeleteModal, setShowDeleteModal] = React.useState(false);
  const menuRef = React.useRef<HTMLDivElement>(null);

  // Close menu on click outside
  React.useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setShowMenu(false);
      }
    };

    if (showMenu) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showMenu]);

  const handleDeleteClick = () => {
    setShowMenu(false);
    setShowDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    if (onDelete) {
      onDelete();
    }
    setShowDeleteModal(false);
  };

  return (
    <>
      <div
        className={cn(
          'bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-slate-700',
          className
        )}
        data-testid={`project-card-${project.id}`}
        role="listitem"
        aria-label={`Project: ${project.brand_name}`}
      >
        {/* Header */}
        <div className="flex justify-between items-start mb-4">
          <h3 className="text-lg font-semibold">{project.brand_name}</h3>
          <div className="relative" ref={menuRef}>
            <button
              onClick={() => setShowMenu(!showMenu)}
              data-testid="menu-toggle"
              aria-label="Project actions"
              className="p-1 hover:bg-gray-100 dark:hover:bg-slate-700 rounded"
            >
              <MoreVertical className="w-5 h-5" data-testid="more-icon" />
            </button>

            {/* Dropdown Menu */}
            {showMenu && (
              <div
                data-testid="dropdown-menu"
                role="menu"
                className="absolute right-0 mt-1 w-36 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-lg z-10"
              >
                <button
                  onClick={() => {
                    setShowMenu(false);
                    onEditProject?.();
                  }}
                  data-testid="edit-btn"
                  role="menuitem"
                  className="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-slate-700 inline-flex items-center gap-2"
                >
                  <Settings className="w-4 h-4" data-testid="settings-icon" />
                  Edit
                </button>
                <button
                  onClick={handleDeleteClick}
                  data-testid="delete-menu-btn"
                  role="menuitem"
                  className="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-slate-700 text-red-500 inline-flex items-center gap-2"
                >
                  <Trash2 className="w-4 h-4" data-testid="trash-icon" />
                  Delete
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Status Badge */}
        <div data-testid="status-badge" className="mb-4">
          {project.is_active ? (
            <span
              className="inline-flex items-center gap-1 text-green-500 text-sm"
              data-testid="active-badge"
            >
              <CheckCircle className="w-4 h-4" />
              Active
            </span>
          ) : (
            <span
              className="inline-flex items-center gap-1 text-gray-400 text-sm"
              data-testid="inactive-badge"
            >
              <XCircle className="w-4 h-4" />
              Inactive
            </span>
          )}
        </div>

        {/* Metrics Grid */}
        <div className="grid grid-cols-2 gap-4 mb-4">
          {/* Mention Rate */}
          <div data-testid="mention-rate-display">
            <span
              className="text-2xl font-bold"
              data-testid="mention-rate-value"
            >
              {formatMentionRate(project.mention_rate)}
            </span>
            <p className="text-sm text-gray-500">Mention Rate</p>
          </div>

          {/* Last Queried */}
          <div data-testid="last-queried">
            <span
              className="inline-flex items-center gap-1 text-sm font-medium"
              data-testid="last-queried-value"
            >
              <Clock className="w-3 h-3" />
              {formatLastQueried(project.last_queried_at)}
            </span>
            <p className="text-sm text-gray-500">Last Queried</p>
          </div>
        </div>

        {/* Keywords & Competitors */}
        <div className="flex gap-4 mb-4 text-sm text-gray-600 dark:text-gray-400">
          <span data-testid="keywords-count">{project.keywords_count} keywords</span>
          <span data-testid="competitors-count">{project.competitors_count} competitors</span>
        </div>

        {/* Actions */}
        <div className="flex gap-2">
          <button
            onClick={onViewDashboard}
            disabled={isLoading}
            data-testid="view-dashboard-btn"
            aria-label="View dashboard"
            className="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 inline-flex items-center justify-center gap-2"
          >
            {isLoading ? (
              <Loader2 className="w-4 h-4 animate-spin" data-testid="loader-icon" />
            ) : (
              <Eye className="w-4 h-4" data-testid="eye-icon" />
            )}
            <span>Dashboard</span>
          </button>
          <button
            onClick={onRunQuery}
            disabled={isLoading}
            data-testid="run-query-btn"
            aria-label="Run query"
            className="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 inline-flex items-center justify-center gap-2"
          >
            {isLoading ? (
              <Loader2 className="w-4 h-4 animate-spin" data-testid="loader-icon" />
            ) : (
              <Play className="w-4 h-4" data-testid="play-icon" />
            )}
            <span>Run Query</span>
          </button>
        </div>
      </div>

      {/* Delete Modal */}
      <DeleteProjectModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDeleteConfirm}
        projectName={project.brand_name}
        isLoading={isLoading}
      />
    </>
  );
}

export default ProjectCard;
