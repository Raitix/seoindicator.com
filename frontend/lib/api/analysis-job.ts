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
