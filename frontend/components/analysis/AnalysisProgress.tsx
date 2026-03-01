/**
 * AnalysisProgress Component - TASK-19-11
 *
 * React/Next.js component for displaying analysis progress with real-time
 * updates via SSE and polling fallback.
 *
 * Features:
 * - Real-time progress updates via SSE
 * - Auto-reconnect with exponential backoff
 * - Fallback to polling after 3 SSE failures
 * - Progress bar with percentage
 * - Status indicator
 * - Cancel button
 * - Elapsed time display
 * - Accessibility: ARIA labels, keyboard navigation
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

'use client';

import React, { useEffect, useCallback } from 'react';
import { useAnalysisProgress } from '@/lib/hooks/useAnalysisProgress';
import {
  AnalysisProgressProps,
  STATUS_LABELS,
  STATUS_COLORS,
  DEFAULT_POLLING_INTERVAL,
  DEFAULT_MAX_RECONNECT_ATTEMPTS,
} from '@/lib/types/analysis-job';
import { formatElapsedTime, calculateAverageScore } from '@/lib/api/analysis-job';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

// Status badge component
function StatusBadge({ status }: { status: string }) {
  const colorClasses: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
  };

  const color = STATUS_COLORS[status as keyof typeof STATUS_COLORS] || 'gray';
  const label = STATUS_LABELS[status as keyof typeof STATUS_LABELS] || status;

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClasses[color]}`}
      role="status"
      aria-live="polite"
    >
      {label}
    </span>
  );
}

// Progress indicator component
function ProgressIndicator({
  completed,
  total,
  percent,
}: {
  completed: number;
  total: number;
  percent: number;
}) {
  return (
    <div className="space-y-2">
      <div className="flex justify-between text-sm text-muted-foreground">
        <span>
          {completed} of {total} indicators
        </span>
        <span>{percent}%</span>
      </div>
      <Progress
        value={percent}
        className="h-3"
        role="progressbar"
        aria-valuenow={percent}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label={`Analysis progress: ${percent}%`}
      />
    </div>
  );
}

// Elapsed time component
function ElapsedTime({ seconds }: { seconds: number }) {
  return (
    <div className="text-sm text-muted-foreground" aria-live="off">
      <span className="font-medium">Elapsed: </span>
      <span className="font-mono">{formatElapsedTime(seconds)}</span>
    </div>
  );
}

// Connection status indicator
function ConnectionStatus({
  isConnecting,
  isPolling,
  error,
}: {
  isConnecting: boolean;
  isPolling: boolean;
  error: string | null;
}) {
  if (error) {
    return (
      <div className="text-sm text-yellow-600 dark:text-yellow-400" role="alert">
        {error}
      </div>
    );
  }

  if (isConnecting) {
    return (
      <div className="text-sm text-blue-600 dark:text-blue-400">
        Connecting...
      </div>
    );
  }

  if (isPolling) {
    return (
      <div className="text-sm text-muted-foreground">
        Using polling mode
      </div>
    );
  }

  return null;
}

// Main component
export function AnalysisProgress({
  jobId,
  onComplete,
  onCancel,
  className = '',
  pollingInterval = DEFAULT_POLLING_INTERVAL,
  maxReconnectAttempts = DEFAULT_MAX_RECONNECT_ATTEMPTS,
  autoStart = true,
}: AnalysisProgressProps) {
  const {
    job,
    isConnecting,
    isPolling,
    error,
    elapsedSeconds,
    start,
    stop,
    cancel,
  } = useAnalysisProgress({
    jobId,
    onComplete,
    pollingInterval,
    maxReconnectAttempts,
  });

  // Auto-start on mount
  useEffect(() => {
    if (autoStart) {
      start();
    }

    return () => {
      stop();
    };
  }, [autoStart, start, stop]);

  // Handle cancel button click
  const handleCancel = useCallback(async () => {
    await cancel();
    onCancel();
  }, [cancel, onCancel]);

  // Derive state
  const status = job?.status || 'pending';
  const isRunning = status === 'running';
  const isTerminal = ['completed', 'cancelled', 'failed'].includes(status);
  const percent = job?.progress_percent || 0;
  const completed = job?.completed_indicators || 0;
  const total = job?.total_indicators || 0;
  const avgScore = job?.results ? calculateAverageScore(job.results) : 0;

  return (
    <Card className={className}>
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg">Analysis Progress</CardTitle>
          <StatusBadge status={status} />
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Progress bar */}
        <ProgressIndicator completed={completed} total={total} percent={percent} />

        {/* Status row */}
        <div className="flex items-center justify-between">
          <ElapsedTime seconds={elapsedSeconds} />

          {/* Average score for completed */}
          {isTerminal && status === 'completed' && (
            <div className="text-sm font-medium">
              Score: <span className="text-green-600 dark:text-green-400">{avgScore}%</span>
            </div>
          )}
        </div>

        {/* Connection status */}
        <ConnectionStatus
          isConnecting={isConnecting}
          isPolling={isPolling}
          error={error}
        />

        {/* Error message from job */}
        {job?.error_message && (
          <div className="text-sm text-red-600 dark:text-red-400" role="alert">
            {job.error_message}
          </div>
        )}

        {/* Action buttons */}
        <div className="flex gap-2">
          {isRunning && (
            <Button
              variant="destructive"
              onClick={handleCancel}
              aria-label="Cancel analysis"
              className="flex-1"
            >
              Cancel Analysis
            </Button>
          )}

          {isTerminal && status === 'completed' && (
            <Button
              variant="default"
              onClick={() => onComplete(job)}
              className="flex-1"
            >
              View Results
            </Button>
          )}

          {isTerminal && status === 'failed' && (
            <Button
              variant="outline"
              onClick={() => start()}
              className="flex-1"
            >
              Retry Analysis
            </Button>
          )}
        </div>

        {/* Screen reader announcements */}
        <div className="sr-only" aria-live="polite" aria-atomic="true">
          {isRunning && `Analysis in progress. ${completed} of ${total} indicators completed. ${percent}% done.`}
          {isTerminal && status === 'completed' && `Analysis completed with score of ${avgScore}%.`}
          {isTerminal && status === 'cancelled' && 'Analysis was cancelled.'}
          {isTerminal && status === 'failed' && `Analysis failed. ${job?.error_message || 'Unknown error'}.`}
        </div>
      </CardContent>
    </Card>
  );
}

export default AnalysisProgress;
