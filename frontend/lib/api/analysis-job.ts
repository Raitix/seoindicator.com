/**
 * Analysis Job API Service - TASK-19-11
 *
 * API helper functions for analysis job management.
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

import {
  AnalysisJob,
  JobProgressResponse,
  CreateJobRequest,
  CreateJobResponse,
  CancelJobResponse,
  IndicatorResult,
} from '@/lib/types/analysis-job';

const API_BASE = '/api/v1';

// ==================== Job CRUD ====================

/**
 * Create a new analysis job
 */
export async function createAnalysisJob(
  siteId: number,
  indicators: string[]
): Promise<CreateJobResponse> {
  const response = await fetch(`${API_BASE}/sites/${siteId}/analysis-jobs`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ indicators } as CreateJobRequest),
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Unknown error' }));
    throw new Error(error.error || `Failed to create job: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Get job details
 */
export async function getAnalysisJob(jobId: number): Promise<{ data: AnalysisJob }> {
  const response = await fetch(`${API_BASE}/analysis-jobs/${jobId}`, {
    credentials: 'include',
  });

  if (!response.ok) {
    if (response.status === 404) {
      throw new Error('Job not found');
    }
    throw new Error(`Failed to get job: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Get job progress (polling endpoint)
 */
export async function getJobProgress(jobId: number): Promise<JobProgressResponse> {
  const response = await fetch(`${API_BASE}/analysis-jobs/${jobId}/progress`, {
    credentials: 'include',
  });

  if (!response.ok) {
    if (response.status === 404) {
      throw new Error('Job not found');
    }
    throw new Error(`Failed to get progress: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Cancel a running job
 */
export async function cancelAnalysisJob(jobId: number): Promise<CancelJobResponse> {
  const response = await fetch(`${API_BASE}/analysis-jobs/${jobId}`, {
    method: 'DELETE',
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Unknown error' }));
    throw new Error(error.error || `Failed to cancel job: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Start processing a pending job
 */
export async function startAnalysisJob(jobId: number): Promise<{ message: string; job_id: number }> {
  const response = await fetch(`${API_BASE}/analysis-jobs/${jobId}/start`, {
    method: 'POST',
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Unknown error' }));
    throw new Error(error.error || `Failed to start job: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Get available indicators
 */
export async function getAvailableIndicators(): Promise<
  { slug: string; name: string; max_score: number }[]
> {
  const response = await fetch(`${API_BASE}/analysis-jobs/indicators`, {
    credentials: 'include',
  });

  if (!response.ok) {
    throw new Error(`Failed to get indicators: ${response.statusText}`);
  }

  const data = await response.json();
  return data.indicators;
}

// ==================== SSE Connection ====================

/**
 * Get SSE stream URL for job progress
 */
export function getSSEUrl(jobId: number): string {
  return `${API_BASE}/analysis-jobs/${jobId}/stream`;
}

/**
 * Create EventSource for SSE connection
 */
export function createEventSource(jobId: number): EventSource {
  const url = getSSEUrl(jobId);
  return new EventSource(url, { withCredentials: true });
}

// ==================== Utility Functions ====================

/**
 * Format elapsed time as MM:SS or HH:MM:SS
 */
export function formatElapsedTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }

  return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Calculate elapsed seconds from start time
 */
export function calculateElapsedSeconds(startedAt: string | null): number {
  if (!startedAt) return 0;

  const start = new Date(startedAt).getTime();
  const now = Date.now();

  return Math.floor((now - start) / 1000);
}

/**
 * Check if job is in a terminal state
 */
export function isJobTerminal(status: string): boolean {
  return ['completed', 'cancelled', 'failed'].includes(status);
}

/**
 * Check if job is running
 */
export function isJobRunning(status: string): boolean {
  return status === 'running';
}

/**
 * Calculate average score from results
 */
export function calculateAverageScore(results: IndicatorResult[]): number {
  if (!results || results.length === 0) return 0;

  const completed = results.filter((r) => r.status === 'completed');
  if (completed.length === 0) return 0;

  const total = completed.reduce((sum, r) => sum + r.percentage, 0);
  return Math.round(total / completed.length);
}

/**
 * Get indicator status summary
 */
export function getIndicatorSummary(
  results: IndicatorResult[]
): { completed: number; pending: number; failed: number; running: number } {
  return {
    completed: results.filter((r) => r.status === 'completed').length,
    pending: results.filter((r) => r.status === 'pending').length,
    failed: results.filter((r) => r.status === 'failed').length,
    running: results.filter((r) => r.status === 'running').length,
  };
}

// ==================== Results Table Utilities - TASK-19-12 ====================

import {
  IndicatorStatus,
  ResultsSortBy,
  ResultsFilterBy,
  IndicatorResult as IndicatorResultType,
} from '@/lib/types/analysis-job';

/**
 * Status priority for sorting (lower = higher priority for display)
 */
const STATUS_PRIORITY: Record<IndicatorStatus, number> = {
  completed: 1,
  failed: 2,
  running: 3,
  pending: 4,
  skipped: 5,
};

/**
 * Sort results by specified criteria
 */
export function sortResults(
  results: IndicatorResultType[],
  sortBy: ResultsSortBy
): IndicatorResultType[] {
  const sorted = [...results];

  switch (sortBy) {
    case 'status':
      // Completed first, then failed, running, pending, skipped
      return sorted.sort((a, b) => {
        const priorityDiff =
          STATUS_PRIORITY[a.status as IndicatorStatus] -
          STATUS_PRIORITY[b.status as IndicatorStatus];
        if (priorityDiff !== 0) return priorityDiff;
        // Secondary sort by name
        return a.indicator.localeCompare(b.indicator);
      });

    case 'score':
      // Lowest score first (ascending)
      return sorted.sort((a, b) => {
        // Pending/running items should be at the end
        if (a.status === 'pending' || a.status === 'running') return 1;
        if (b.status === 'pending' || b.status === 'running') return -1;
        return a.percentage - b.percentage;
      });

    case 'name':
      // Alphabetical by indicator name
      return sorted.sort((a, b) => a.indicator.localeCompare(b.indicator));

    default:
      return sorted;
  }
}

/**
 * Filter results by specified criteria
 */
export function filterResults(
  results: IndicatorResultType[],
  filterBy: ResultsFilterBy
): IndicatorResultType[] {
  switch (filterBy) {
    case 'completed':
      return results.filter((r) => r.status === 'completed');
    case 'failed':
      return results.filter((r) => r.status === 'failed');
    case 'all':
    default:
      return results;
  }
}

/**
 * Get status icon name for indicator
 */
export function getStatusIcon(status: IndicatorStatus): string {
  switch (status) {
    case 'completed':
      return 'check-circle';
    case 'failed':
      return 'x-circle';
    case 'running':
      return 'loader';
    case 'pending':
      return 'clock';
    case 'skipped':
      return 'minus-circle';
    default:
      return 'help-circle';
  }
}

/**
 * Get score color class based on percentage
 */
export function getScoreColor(percentage: number): string {
  if (percentage >= 80) return 'text-green-600 dark:text-green-400';
  if (percentage >= 60) return 'text-yellow-600 dark:text-yellow-400';
  if (percentage >= 40) return 'text-orange-600 dark:text-orange-400';
  return 'text-red-600 dark:text-red-400';
}

/**
 * Get score background color class based on percentage
 */
export function getScoreBgColor(percentage: number): string {
  if (percentage >= 80) return 'bg-green-100 dark:bg-green-900/30';
  if (percentage >= 60) return 'bg-yellow-100 dark:bg-yellow-900/30';
  if (percentage >= 40) return 'bg-orange-100 dark:bg-orange-900/30';
  return 'bg-red-100 dark:bg-red-900/30';
}

/**
 * Get indicator display name
 */
export function getIndicatorDisplayName(slug: string): string {
  const names: Record<string, string> = {
    meta_tags: 'Meta Tags',
    headings: 'Headings Structure',
    images: 'Images',
    links: 'Links',
    url_structure: 'URL Structure',
    content_quality: 'Content Quality',
    mobile_friendly: 'Mobile Friendly',
    page_speed: 'Page Speed',
    structured_data: 'Structured Data',
    security: 'Security',
    accessibility: 'Accessibility',
    social_tags: 'Social Tags',
    canonical: 'Canonical URLs',
    robots: 'Robots.txt',
    sitemap: 'XML Sitemap',
    internal_links: 'Internal Links',
    external_links: 'External Links',
  };
  return names[slug] || slug.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
