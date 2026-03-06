"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export interface QueueStatus {
  pending: number;
  processing: number;
  failed: number;
  workers: number;
  oldestJob?: {
    id: string;
    age: number; // seconds
    type: string;
  };
}

export interface QueueStatusPanelProps {
  queue: QueueStatus | null;
  onRefresh?: () => void;
  isLoading?: boolean;
  isRefreshing?: boolean;
  error?: string | null;
  onRetry?: () => void;
  "aria-label"?: string;
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Get queue health status
 */
export function getQueueHealth(
  queue: QueueStatus
): "healthy" | "warning" | "critical" {
  if (queue.failed > 0) return "critical";
  if (queue.pending > 100 || queue.workers === 0) return "warning";
  return "healthy";
}

/**
 * Get pending status level
 */
export function getPendingLevel(
  pending: number
): "normal" | "warning" | "critical" {
  if (pending > 100) return "critical";
  if (pending > 50) return "warning";
  return "normal";
}

/**
 * Format job age
 */
export function formatJobAge(ageSeconds: number): string {
  if (ageSeconds < 60) return `${ageSeconds}s`;
  if (ageSeconds < 3600) return `${Math.floor(ageSeconds / 60)}m`;
  return `${Math.floor(ageSeconds / 3600)}h`;
}

/**
 * Get worker status message
 */
export function getWorkerStatus(workers: number): string {
  if (workers === 0) return "No active workers";
  if (workers === 1) return "1 worker active";
  return `${workers} workers active`;
}

/**
 * Get oldest job display
 */
export function getOldestJobDisplay(
  oldestJob?: QueueStatus["oldestJob"]
): string | null {
  if (!oldestJob) return null;
  return `${formatJobAge(oldestJob.age)} old - ${oldestJob.type}`;
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="queue-status-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse"
    >
      <div className="h-6 w-32 rounded bg-slate-700 mb-4" />
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="space-y-2">
            <div className="h-4 w-16 rounded bg-slate-700" />
            <div className="h-8 w-12 rounded bg-slate-700" />
          </div>
        ))}
      </div>
    </div>
  );
}

// ==================== Error Display ====================

interface ErrorDisplayProps {
  message: string;
  onRetry?: () => void;
}

function ErrorDisplay({ message, onRetry }: ErrorDisplayProps) {
  return (
    <div
      data-testid="queue-status-error"
      role="alert"
      className="flex flex-col items-start gap-2 p-4"
    >
      <span className="text-sm text-red-400">{message}</span>
      {onRetry && (
        <button
          data-testid="queue-status-retry"
          onClick={onRetry}
          className="text-xs text-blue-400 hover:text-blue-300 underline"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== Metric Item ====================

interface MetricItemProps {
  label: string;
  value: number | string;
  status?: "normal" | "warning" | "critical";
  className?: string;
}

function MetricItem({ label, value, status = "normal", className }: MetricItemProps) {
  const statusColors = {
    normal: "text-white",
    warning: "text-yellow-400",
    critical: "text-red-400",
  };

  return (
    <div className={cn("text-center", className)}>
      <div className="text-xs text-slate-400 mb-1">{label}</div>
      <div
        data-testid={`queue-metric-${label.toLowerCase().replace(/\s+/g, "-")}`}
        className={cn("text-2xl font-bold", statusColors[status])}
      >
        {value}
      </div>
    </div>
  );
}

// ==================== Component ====================

export function QueueStatusPanel({
  queue,
  onRefresh,
  isLoading = false,
  isRefreshing = false,
  error = null,
  onRetry,
  "aria-label": ariaLabel,
  className,
}: QueueStatusPanelProps) {
  if (isLoading) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700 p-6",
          className
        )}
      >
        <LoadingSkeleton />
      </div>
    );
  }

  if (error) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700",
          className
        )}
        role="region"
        aria-label={ariaLabel || "Queue Status"}
      >
        <div className="p-4 border-b border-slate-700">
          <h3 className="text-lg font-semibold text-white">Queue Status</h3>
        </div>
        <ErrorDisplay message={error} onRetry={onRetry} />
      </div>
    );
  }

  if (!queue) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700 p-6",
          className
        )}
        role="region"
        aria-label={ariaLabel || "Queue Status"}
      >
        <div className="text-slate-400">No queue data available</div>
      </div>
    );
  }

  const health = getQueueHealth(queue);
  const pendingLevel = getPendingLevel(queue.pending);
  const oldestJobDisplay = getOldestJobDisplay(queue.oldestJob);

  return (
    <div
      data-testid="queue-status-panel"
      className={cn(
        "rounded-lg bg-slate-800 border border-slate-700",
        className
      )}
      role="region"
      aria-label={ariaLabel || "Queue Status"}
    >
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <h3 className="text-lg font-semibold text-white">Queue Status</h3>
        {onRefresh && (
          <button
            data-testid="queue-refresh-button"
            onClick={onRefresh}
            disabled={isRefreshing}
            className={cn(
              "flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors",
              isRefreshing
                ? "bg-slate-700 text-slate-400 cursor-not-allowed"
                : "bg-blue-600 text-white hover:bg-blue-700"
            )}
            aria-label={isRefreshing ? "Refreshing queue status" : "Refresh queue status"}
          >
            {isRefreshing ? (
              <>
                <svg
                  className="animate-spin h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                  />
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
                Refreshing...
              </>
            ) : (
              <>
                <svg
                  className="h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                  />
                </svg>
                Refresh
              </>
            )}
          </button>
        )}
      </div>

      {/* Metrics Grid */}
      <div className="p-4">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <MetricItem
            label="Pending"
            value={queue.pending}
            status={pendingLevel}
          />
          <MetricItem label="Processing" value={queue.processing} />
          <MetricItem
            label="Failed"
            value={queue.failed}
            status={queue.failed > 0 ? "critical" : "normal"}
          />
          <MetricItem
            label="Workers"
            value={queue.workers}
            status={queue.workers === 0 ? "warning" : "normal"}
          />
        </div>

        {/* Worker Status */}
        <div
          data-testid="worker-status"
          className={cn(
            "text-sm",
            queue.workers === 0 ? "text-yellow-400" : "text-slate-400"
          )}
        >
          {getWorkerStatus(queue.workers)}
        </div>

        {/* Oldest Job */}
        {oldestJobDisplay && (
          <div
            data-testid="oldest-job"
            className="mt-2 text-sm text-slate-400"
          >
            Oldest job: {oldestJobDisplay}
          </div>
        )}

        {/* Empty Queue Message */}
        {queue.pending === 0 && queue.processing === 0 && (
          <div
            data-testid="queue-empty"
            className="mt-4 text-sm text-green-400"
          >
            ✓ Queue empty
          </div>
        )}

        {/* High Pending Warning */}
        {queue.pending > 100 && (
          <div
            data-testid="queue-high-pending"
            className="mt-4 text-sm text-yellow-400"
          >
            ⚠ High number of pending jobs
          </div>
        )}

        {/* No Workers Warning */}
        {queue.workers === 0 && queue.pending > 0 && (
          <div
            data-testid="queue-no-workers"
            className="mt-4 text-sm text-yellow-400"
          >
            ⚠ No active workers to process jobs
          </div>
        )}
      </div>
    </div>
  );
}

// ==================== Exports ====================

export default QueueStatusPanel;
