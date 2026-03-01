/**
 * Analysis Job Types - TASK-19-11
 *
 * TypeScript type definitions for analysis job progress tracking.
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

// ==================== Job Status ====================

export type JobStatus = 'pending' | 'running' | 'completed' | 'cancelled' | 'failed';

// ==================== Analysis Job ====================

export interface AnalysisJob {
  /** Unique job ID */
  id: number;

  /** Site ID being analyzed */
  site_id: number;

  /** User ID who created the job */
  user_id: number;

  /** Current job status */
  status: JobStatus;

  /** Total number of indicators to analyze */
  total_indicators: number;

  /** Number of completed indicators */
  completed_indicators: number;

  /** Progress percentage (0-100) */
  progress_percent: number;

  /** Analysis results */
  results: IndicatorResult[];

  /** Error message if failed */
  error_message?: string;

  /** When job was created */
  created_at: string;

  /** When job started processing */
  started_at?: string;

  /** When job completed */
  completed_at?: string;

  /** When job was cancelled */
  cancelled_at?: string;
}

// ==================== Indicator Result ====================

export interface IndicatorResult {
  /** Indicator slug (e.g., 'meta_tags', 'headings') */
  indicator: string;

  /** Indicator status */
  status: 'pending' | 'running' | 'completed' | 'failed';

  /** Score achieved (0-100) */
  score: number;

  /** Maximum possible score */
  max_score: number;

  /** Score as percentage */
  percentage: number;

  /** Detailed result data */
  data: Record<string, unknown>;

  /** Recommendations for improvement */
  recommendations: string[];

  /** Error message if failed */
  error_message?: string;
}

// ==================== SSE Event Types ====================

export interface ProgressEvent {
  job_id: number;
  completed_indicators: number;
  total_indicators: number;
  progress_percent: number;
  status: JobStatus;
}

export interface CompleteEvent {
  status: JobStatus;
  total_indicators: number;
  completed_indicators: number;
  failed_indicators: number;
}

export interface HeartbeatEvent {
  time: number;
}

export interface ErrorEvent {
  message: string;
}

// ==================== Component Props ====================

export interface AnalysisProgressProps {
  /** Job ID to track */
  jobId: number;

  /** Callback when analysis completes successfully */
  onComplete: (results: AnalysisJob) => void;

  /** Callback when user cancels the job */
  onCancel: () => void;

  /** Optional CSS class name */
  className?: string;

  /** Initial polling interval in ms (default: 2000) */
  pollingInterval?: number;

  /** Maximum SSE reconnection attempts before falling back to polling */
  maxReconnectAttempts?: number;

  /** Auto-start polling on mount (default: true) */
  autoStart?: boolean;
}

// ==================== Hook Return Types ====================

export interface UseAnalysisProgressOptions {
  jobId: number;
  onComplete?: (results: AnalysisJob) => void;
  pollingInterval?: number;
  maxReconnectAttempts?: number;
}

export interface UseAnalysisProgressReturn {
  /** Current job state */
  job: AnalysisJob | null;

  /** Is currently connecting to SSE */
  isConnecting: boolean;

  /** Is using polling fallback instead of SSE */
  isPolling: boolean;

  /** Connection error message */
  error: string | null;

  /** SSE reconnection attempts count */
  reconnectAttempts: number;

  /** Elapsed time in seconds */
  elapsedSeconds: number;

  /** Start tracking progress */
  start: () => void;

  /** Stop tracking and cleanup */
  stop: () => void;

  /** Cancel the job */
  cancel: () => Promise<void>;
}

// ==================== API Response Types ====================

export interface JobProgressResponse {
  id: number;
  status: JobStatus;
  total_indicators: number;
  completed_indicators: number;
  progress_percent: number;
  results: IndicatorResult[];
}

export interface CreateJobRequest {
  indicators: string[];
}

export interface CreateJobResponse {
  data: AnalysisJob;
}

export interface CancelJobResponse {
  data: AnalysisJob;
}

// ==================== Constants ====================

export const STATUS_LABELS: Record<JobStatus, string> = {
  pending: 'Pending',
  running: 'Running',
  completed: 'Completed',
  cancelled: 'Cancelled',
  failed: 'Failed',
};

export const STATUS_COLORS: Record<JobStatus, string> = {
  pending: 'yellow',
  running: 'blue',
  completed: 'green',
  cancelled: 'gray',
  failed: 'red',
};

export const DEFAULT_POLLING_INTERVAL = 2000;
export const DEFAULT_MAX_RECONNECT_ATTEMPTS = 3;
export const SSE_HEARTBEAT_INTERVAL = 15000;
