/**
 * TASK-34-8: ProjectList Component
 * 
 * List component for displaying all AI Visibility projects.
 * Includes filtering, sorting, search, and pagination.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import * as React from 'react';
import { Search, ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useProjects } from '@/lib/hooks/useAIVisibilityProjects';
import { ProjectCard } from './ProjectCard';
import { EmptyProjectsState } from './EmptyProjectsState';
import type {
  ProjectListProps,
  UseProjectsParams,
} from '@/lib/types/ai-visibility-projects';

// ============================================
// Skeleton Component
// ============================================

function ProjectCardSkeleton() {
  return (
    <div
      data-testid="skeleton-card"
      className="animate-pulse bg-gray-200 dark:bg-slate-700 rounded-lg p-6 h-64"
    />
  );
}

// ============================================
// Main Component
// ============================================

export function ProjectList({
  className,
  onCreateProject,
  onViewDashboard,
  onEditProject,
}: ProjectListProps) {
  // State for filters
  const [statusFilter, setStatusFilter] = React.useState<'all' | 'active' | 'inactive'>('all');
  const [searchQuery, setSearchQuery] = React.useState('');
  const [sortBy, setSortBy] = React.useState<UseProjectsParams['sort']>('created_at');
  const [page, setPage] = React.useState(1);

  // Debounced search
  const [debouncedSearch, setDebouncedSearch] = React.useState('');
  React.useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
      setPage(1); // Reset page on search
    }, 300);
    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Fetch projects
  const { data, isLoading, error, refetch } = useProjects({
    page,
    status: statusFilter,
    search: debouncedSearch,
    sort: sortBy,
  });

  // Reset page when filters change
  React.useEffect(() => {
    setPage(1);
  }, [statusFilter, sortBy]);

  // Loading skeleton
  if (isLoading) {
    return (
      <div
        data-testid="loading-skeleton"
        role="status"
        className="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
      >
        {[0, 1, 2].map((i) => (
          <div
            key={i}
            data-testid={`skeleton-${i}`}
            className="animate-pulse bg-gray-200 dark:bg-slate-700 rounded-lg h-64"
          />
        ))}
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        data-testid="error-state"
        role="alert"
        className="text-center py-12"
      >
        <p className="text-red-500 mb-4">Failed to load projects</p>
        <button
          onClick={() => refetch()}
          className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90"
        >
          Retry
        </button>
      </div>
    );
  }

  // Empty state
  if (!data?.data?.length) {
    return <EmptyProjectsState onCreateProject={onCreateProject} />;
  }

  const projects = data.data;
  const meta = data.meta;

  return (
    <div
      className={className}
      data-testid="project-list-container"
    >
      {/* Filters */}
      <div
        data-testid="filters-container"
        className="flex flex-wrap gap-4 mb-6"
      >
        {/* Search Input */}
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="search"
            placeholder="Search projects..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            data-testid="search-input"
            aria-label="Search projects"
            className="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        {/* Status Filter */}
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as typeof statusFilter)}
          data-testid="status-filter"
          aria-label="Filter by status"
          className="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>

        {/* Sort Select */}
        <select
          value={sortBy}
          onChange={(e) => setSortBy(e.target.value as typeof sortBy)}
          data-testid="sort-select"
          aria-label="Sort projects"
          className="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="created_at">Created Date</option>
          <option value="brand_name">Name</option>
          <option value="last_queried_at">Last Queried</option>
          <option value="mention_rate">Mention Rate</option>
        </select>
      </div>

      {/* Project List */}
      <div
        data-testid="project-list"
        role="list"
        aria-label="Projects list"
        className="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
      >
        {projects.map((project) => (
          <ProjectCard
            key={project.id}
            project={project}
            onViewDashboard={() => onViewDashboard?.(project.id)}
            onEditProject={() => onEditProject?.(project.id)}
          />
        ))}
      </div>

      {/* Pagination */}
      {meta.last_page > 1 && (
        <div
          data-testid="pagination"
          aria-label="Pagination"
          className="flex items-center justify-center gap-4 mt-6"
        >
          <button
            onClick={() => setPage((p) => p - 1)}
            disabled={page === 1}
            data-testid="prev-page"
            aria-label="Previous page"
            className="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1"
          >
            <ChevronLeft className="w-4 h-4" />
            Previous
          </button>

          <span data-testid="page-info" className="text-sm">
            Page {meta.current_page} of {meta.last_page}
          </span>

          <button
            onClick={() => setPage((p) => p + 1)}
            disabled={page === meta.last_page}
            data-testid="next-page"
            aria-label="Next page"
            className="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1"
          >
            Next
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      )}
    </div>
  );
}

export default ProjectList;
