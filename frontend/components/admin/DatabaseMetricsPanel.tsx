"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { HealthBadge, type HealthStatus } from "./HealthBadge";

// ==================== Types ====================

export interface DatabaseMetrics {
  status: HealthStatus;
  connections: {
    active: number;
    idle: number;
    max: number;
  };
  latency: number; // ms
  queryCount: number;
  slowQueries: number;
  tableSizes?: Array<{
    name: string;
    size: string;
    rows: number;
  }>;
}

export interface DatabaseMetricsPanelProps {
  metrics: DatabaseMetrics | null;
  showTableSizes?: boolean;
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
 * Get connection pool percentage
 */
export function getConnectionPercentage(
  connections: DatabaseMetrics["connections"]
): number {
  return Math.round((connections.active / connections.max) * 100);
}

/**
 * Get connection pool status
 */
export function getConnectionStatus(
  connections: DatabaseMetrics["connections"]
): "normal" | "warning" | "critical" {
  const percentage = getConnectionPercentage(connections);
  if (percentage >= 90) return "critical";
  if (percentage >= 70) return "warning";
  return "normal";
}

/**
 * Get latency status
 */
export function getLatencyStatus(latency: number): "normal" | "warning" | "critical" {
  if (latency >= 500) return "critical";
  if (latency >= 100) return "warning";
  return "normal";
}

/**
 * Format connection display
 */
export function formatConnections(
  connections: DatabaseMetrics["connections"]
): string {
  return `${connections.active}/${connections.max} active`;
}

/**
 * Format latency display
 */
export function formatLatency(latency: number): string {
  if (latency < 1000) return `${latency}ms`;
  return `${(latency / 1000).toFixed(1)}s`;
}

/**
 * Format query count
 */
export function formatQueryCount(count: number): string {
  if (count >= 1000000) return `${(count / 1000000).toFixed(1)}M`;
  if (count >= 1000) return `${(count / 1000).toFixed(1)}K`;
  return count.toString();
}

/**
 * Get overall database health
 */
export function getDatabaseHealth(
  metrics: DatabaseMetrics
): "healthy" | "warning" | "critical" {
  if (metrics.status === "unhealthy") return "critical";
  if (metrics.status === "degraded") return "warning";
  if (getConnectionStatus(metrics.connections) === "critical") return "critical";
  if (getLatencyStatus(metrics.latency) === "critical") return "critical";
  if (metrics.slowQueries > 10) return "warning";
  return "healthy";
}

/**
 * Get progress bar color
 */
function getProgressColor(percentage: number): string {
  if (percentage >= 90) return "bg-red-500";
  if (percentage >= 70) return "bg-yellow-500";
  return "bg-green-500";
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="database-metrics-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-4"
    >
      <div className="h-6 w-40 rounded bg-slate-700" />
      <div className="grid grid-cols-2 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="space-y-2">
            <div className="h-4 w-20 rounded bg-slate-700" />
            <div className="h-6 w-16 rounded bg-slate-700" />
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
      data-testid="database-metrics-error"
      role="alert"
      className="flex flex-col items-start gap-2 p-4"
    >
      <span className="text-sm text-red-400">{message}</span>
      {onRetry && (
        <button
          data-testid="database-metrics-retry"
          onClick={onRetry}
          className="text-xs text-blue-400 hover:text-blue-300 underline"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== Component ====================

export function DatabaseMetricsPanel({
  metrics,
  showTableSizes = true,
  onRefresh,
  isLoading = false,
  isRefreshing = false,
  error = null,
  onRetry,
  "aria-label": ariaLabel,
  className,
}: DatabaseMetricsPanelProps) {
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
        aria-label={ariaLabel || "Database Metrics"}
      >
        <div className="p-4 border-b border-slate-700">
          <h3 className="text-lg font-semibold text-white">Database Metrics</h3>
        </div>
        <ErrorDisplay message={error} onRetry={onRetry} />
      </div>
    );
  }

  if (!metrics) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700 p-6",
          className
        )}
        role="region"
        aria-label={ariaLabel || "Database Metrics"}
      >
        <div className="text-slate-400">No database metrics available</div>
      </div>
    );
  }

  const connectionPercentage = getConnectionPercentage(metrics.connections);
  const connectionStatus = getConnectionStatus(metrics.connections);
  const latencyStatus = getLatencyStatus(metrics.latency);

  return (
    <div
      data-testid="database-metrics-panel"
      className={cn(
        "rounded-lg bg-slate-800 border border-slate-700",
        className
      )}
      role="region"
      aria-label={ariaLabel || "Database Metrics"}
    >
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <div className="flex items-center gap-3">
          <h3 className="text-lg font-semibold text-white">Database Metrics</h3>
          <HealthBadge status={metrics.status} size="sm" />
        </div>
        {onRefresh && (
          <button
            data-testid="database-refresh-button"
            onClick={onRefresh}
            disabled={isRefreshing}
            className={cn(
              "flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors",
              isRefreshing
                ? "bg-slate-700 text-slate-400 cursor-not-allowed"
                : "bg-blue-600 text-white hover:bg-blue-700"
            )}
            aria-label={isRefreshing ? "Refreshing database metrics" : "Refresh database metrics"}
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

      {/* Metrics */}
      <div className="p-4 space-y-4">
        {/* Connection Pool */}
        <div>
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-slate-400">Connection Pool</span>
            <span
              data-testid="connection-display"
              className={cn(
                "text-sm font-medium",
                connectionStatus === "critical"
                  ? "text-red-400"
                  : connectionStatus === "warning"
                  ? "text-yellow-400"
                  : "text-white"
              )}
            >
              {formatConnections(metrics.connections)}
            </span>
          </div>
          <div
            className="h-2 rounded-full bg-slate-700"
            role="progressbar"
            aria-valuenow={connectionPercentage}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Connection pool usage"
          >
            <div
              data-testid="connection-progress"
              className={cn(
                "h-2 rounded-full transition-all",
                getProgressColor(connectionPercentage)
              )}
              style={{ width: `${connectionPercentage}%` }}
            />
          </div>
          <div className="flex justify-between mt-1 text-xs text-slate-500">
            <span>Active: {metrics.connections.active}</span>
            <span>Idle: {metrics.connections.idle}</span>
            <span>Max: {metrics.connections.max}</span>
          </div>
        </div>

        {/* Grid Metrics */}
        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
          {/* Latency */}
          <div>
            <div className="text-xs text-slate-400 mb-1">Latency</div>
            <div
              data-testid="latency-display"
              className={cn(
                "text-lg font-semibold",
                latencyStatus === "critical"
                  ? "text-red-400"
                  : latencyStatus === "warning"
                  ? "text-yellow-400"
                  : "text-white"
              )}
            >
              {formatLatency(metrics.latency)}
            </div>
          </div>

          {/* Query Count */}
          <div>
            <div className="text-xs text-slate-400 mb-1">Queries</div>
            <div
              data-testid="query-count-display"
              className="text-lg font-semibold text-white"
            >
              {formatQueryCount(metrics.queryCount)}
            </div>
          </div>

          {/* Slow Queries */}
          <div>
            <div className="text-xs text-slate-400 mb-1">Slow Queries</div>
            <div
              data-testid="slow-queries-display"
              className={cn(
                "text-lg font-semibold",
                metrics.slowQueries > 10
                  ? "text-red-400"
                  : metrics.slowQueries > 0
                  ? "text-yellow-400"
                  : "text-white"
              )}
            >
              {metrics.slowQueries}
            </div>
          </div>
        </div>

        {/* Table Sizes */}
        {showTableSizes && metrics.tableSizes && metrics.tableSizes.length > 0 && (
          <div>
            <div className="text-sm text-slate-400 mb-2">Table Sizes</div>
            <div
              data-testid="table-sizes"
              className="space-y-2 max-h-40 overflow-y-auto"
            >
              {metrics.tableSizes.map((table) => (
                <div
                  key={table.name}
                  className="flex items-center justify-between text-sm"
                >
                  <span className="text-slate-300 truncate">{table.name}</span>
                  <div className="flex items-center gap-3 text-slate-400">
                    <span>{table.size}</span>
                    <span className="text-xs">
                      {formatQueryCount(table.rows)} rows
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// ==================== Exports ====================

export default DatabaseMetricsPanel;
