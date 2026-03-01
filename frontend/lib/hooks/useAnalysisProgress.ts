/**
 * useAnalysisProgress Hook - TASK-19-11
 *
 * React hook for tracking analysis job progress via SSE with polling fallback.
 *
 * Features:
 * - SSE connection with auto-reconnect
 * - Exponential backoff for reconnections
 * - Automatic fallback to polling after 3 failed SSE attempts
 * - Elapsed time tracking
 * - Proper cleanup on unmount
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import {
  AnalysisJob,
  JobProgressResponse,
  ProgressEvent,
  CompleteEvent,
  UseAnalysisProgressOptions,
  UseAnalysisProgressReturn,
  DEFAULT_POLLING_INTERVAL,
  DEFAULT_MAX_RECONNECT_ATTEMPTS,
  SSE_HEARTBEAT_INTERVAL,
  JobStatus,
} from '@/lib/types/analysis-job';
import {
  getJobProgress,
  cancelAnalysisJob,
  createEventSource,
  isJobTerminal,
} from '@/lib/api/analysis-job';

export function useAnalysisProgress({
  jobId,
  onComplete,
  pollingInterval = DEFAULT_POLLING_INTERVAL,
  maxReconnectAttempts = DEFAULT_MAX_RECONNECT_ATTEMPTS,
}: UseAnalysisProgressOptions): UseAnalysisProgressReturn {
  // State
  const [job, setJob] = useState<AnalysisJob | null>(null);
  const [isConnecting, setIsConnecting] = useState(false);
  const [isPolling, setIsPolling] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reconnectAttempts, setReconnectAttempts] = useState(0);
  const [elapsedSeconds, setElapsedSeconds] = useState(0);

  // Refs for cleanup
  const eventSourceRef = useRef<EventSource | null>(null);
  const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const elapsedIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const isStartedRef = useRef(false);

  // Clear all timers and connections
  const cleanup = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }

    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
      pollingIntervalRef.current = null;
    }

    if (elapsedIntervalRef.current) {
      clearInterval(elapsedIntervalRef.current);
      elapsedIntervalRef.current = null;
    }

    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
  }, []);

  // Fetch progress via polling
  const fetchProgress = useCallback(async () => {
    try {
      const progress = await getJobProgress(jobId);
      setJob((prev) =>
        prev
          ? {
              ...prev,
              ...progress,
            }
          : null
      );

      // Check if terminal state
      if (isJobTerminal(progress.status)) {
        cleanup();
        if (progress.status === 'completed' && job && onComplete) {
          onComplete(job);
        }
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch progress');
    }
  }, [jobId, cleanup, job, onComplete]);

  // Start polling fallback
  const startPolling = useCallback(() => {
    setIsPolling(true);
    setIsConnecting(false);

    // Initial fetch
    fetchProgress();

    // Set up polling interval
    pollingIntervalRef.current = setInterval(fetchProgress, pollingInterval);
  }, [fetchProgress, pollingInterval]);

  // Connect to SSE
  const connectSSE = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
    }

    setIsConnecting(true);
    setError(null);

    try {
      const eventSource = createEventSource(jobId);
      eventSourceRef.current = eventSource;

      // Connection opened
      eventSource.addEventListener('open', () => {
        setIsConnecting(false);
        setReconnectAttempts(0);
      });

      // Progress events
      eventSource.addEventListener('progress', (event: MessageEvent) => {
        const data: ProgressEvent = JSON.parse(event.data);
        setJob((prev) =>
          prev
            ? {
                ...prev,
                id: data.job_id,
                completed_indicators: data.completed_indicators,
                total_indicators: data.total_indicators,
                progress_percent: data.progress_percent,
                status: data.status,
              }
            : null
        );
      });

      // Complete event
      eventSource.addEventListener('complete', (event: MessageEvent) => {
        const data: CompleteEvent = JSON.parse(event.data);
        setJob((prev) =>
          prev
            ? {
                ...prev,
                status: data.status as JobStatus,
                completed_indicators: data.completed_indicators,
                total_indicators: data.total_indicators,
              }
            : null
        );

        cleanup();

        // Fetch final state and call onComplete
        fetchProgress().then(() => {
          if (data.status === 'completed' && onComplete && job) {
            onComplete(job);
          }
        });
      });

      // Heartbeat events
      eventSource.addEventListener('heartbeat', () => {
        // Just keep the connection alive
      });

      // Error events
      eventSource.addEventListener('error', (event: MessageEvent) => {
        try {
          const data = JSON.parse(event.data);
          setError(data.message);
        } catch {
          // Connection error, try to reconnect
        }
      });

      // Connection error
      eventSource.onerror = () => {
        eventSource.close();
        eventSourceRef.current = null;
        setIsConnecting(false);

        // Check if we should fallback to polling
        setReconnectAttempts((prev) => {
          const newAttempts = prev + 1;

          if (newAttempts >= maxReconnectAttempts) {
            // Fall back to polling
            setError('SSE connection failed. Switching to polling...');
            startPolling();
            return 0;
          }

          // Exponential backoff for reconnection
          const backoffMs = Math.min(1000 * Math.pow(2, newAttempts - 1), 30000);
          reconnectTimeoutRef.current = setTimeout(() => {
            connectSSE();
          }, backoffMs);

          return newAttempts;
        });
      };
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to connect to SSE');
      setIsConnecting(false);

      // Fall back to polling
      startPolling();
    }
  }, [jobId, cleanup, startPolling, maxReconnectAttempts, fetchProgress, onComplete, job]);

  // Start elapsed time tracking
  const startElapsedTracking = useCallback(() => {
    if (elapsedIntervalRef.current) {
      clearInterval(elapsedIntervalRef.current);
    }

    elapsedIntervalRef.current = setInterval(() => {
      setElapsedSeconds((prev) => prev + 1);
    }, 1000);
  }, []);

  // Start tracking
  const start = useCallback(async () => {
    if (isStartedRef.current) return;
    isStartedRef.current = true;

    // Fetch initial job state
    try {
      const progress = await getJobProgress(jobId);
      setJob(progress as unknown as AnalysisJob);

      // Start elapsed time from started_at
      if (progress.status === 'running') {
        startElapsedTracking();
      }

      // Connect to SSE
      connectSSE();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to start tracking');
    }
  }, [jobId, connectSSE, startElapsedTracking]);

  // Stop tracking
  const stop = useCallback(() => {
    isStartedRef.current = false;
    cleanup();
    setIsConnecting(false);
    setIsPolling(false);
  }, [cleanup]);

  // Cancel job
  const cancel = useCallback(async () => {
    try {
      const response = await cancelAnalysisJob(jobId);
      setJob(response.data);
      cleanup();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to cancel job');
    }
  }, [jobId, cleanup]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      cleanup();
    };
  }, [cleanup]);

  // Update elapsed time when job changes
  useEffect(() => {
    if (job?.started_at && !isJobTerminal(job.status)) {
      const start = new Date(job.started_at).getTime();
      const now = Date.now();
      const elapsed = Math.floor((now - start) / 1000);
      setElapsedSeconds(elapsed);
      startElapsedTracking();
    } else if (job && isJobTerminal(job.status)) {
      if (elapsedIntervalRef.current) {
        clearInterval(elapsedIntervalRef.current);
        elapsedIntervalRef.current = null;
      }
    }
  }, [job?.started_at, job?.status, startElapsedTracking]);

  return {
    job,
    isConnecting,
    isPolling,
    error,
    reconnectAttempts,
    elapsedSeconds,
    start,
    stop,
    cancel,
  };
}
