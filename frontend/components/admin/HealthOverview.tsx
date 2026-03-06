"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { HealthBadge, type HealthStatus } from "./HealthBadge";
import {
  ServiceStatusIndicator,
  type ServiceStatus,
} from "./ServiceStatusIndicator";
import { QueueStatusPanel, type QueueStatus } from "./QueueStatusPanel";
import {
  DatabaseMetricsPanel,
  type DatabaseMetrics,
} from "./DatabaseMetricsPanel";
import { AlertsPanel, type Alert } from "./AlertsPanel";

// ==================== Types ====================

export interface SystemMetrics {
  cpu: number;
  memory: number;
  disk: number;
  uptime: number;
}

export interface HealthData {
  overall: HealthStatus;
  services: ServiceStatus[];
  queue: QueueStatus;
  database: DatabaseMetrics;
  alerts: Alert[];
  metrics: SystemMetrics;
  lastUpdated: string;
}

export interface HealthOverviewProps {
  data?: HealthData | null;
  onRefresh?: () => Promise<void> | void;
  refreshInterval?: number; // ms, default 30000
  isLoading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  "aria-label"?: string;
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Get overall status message
 */
export function getStatusMessage(overall: HealthStatus): string {
  switch (overall) {
    case "healthy":
      return "All Systems Operational";
    case "degraded":
      return "System Degraded";
    case "unhealthy":
      return "System Outage";
    default:
      return "Unknown Status";
  }
}

/**
 * Get status header color
 */
export function getStatusHeaderClass(overall: HealthStatus): string {
  switch (overall) {
    case "healthy":
      return "bg-green-900/20 border-green-800";
    case "degraded":
      return "bg-yellow-900/20 border-yellow-800";
    case "unhealthy":
      return "bg-red-900/20 border-red-800";
    default:
      return "bg-gray-900/20 border-gray-800";
  }
}

/**
 * Get status text color
 */
export function getStatusTextClass(overall: HealthStatus): string {
  switch (overall) {
    case "healthy":
      return "text-green-400";
    case "degraded":
      return "text-yellow-400";
    case "unhealthy":
      return "text-red-400";
    default:
      return "text-gray-400";
  }
}

/**
 * Count services by status
 */
export function countServicesByStatus(
  services: ServiceStatus[]
): Record<HealthStatus, number> {
  const counts: Record<HealthStatus, number> = {
    healthy: 0,
    degraded: 0,
    unhealthy: 0,
  };
  services.forEach((s) => counts[s.status]++);
  return counts;
}

/**
 * Format uptime
 */
export function formatUptime(seconds: number): string {
  const days = Math.floor(seconds / 86400);
  const hours = Math.floor((seconds % 86400) / 3600);
  const mins = Math.floor((seconds % 3600) / 60);

  if (days > 0) return `${days}d ${hours}h`;
  if (hours > 0) return `${hours}h ${mins}m`;
  return `${mins}m`;
}

/**
 * Get relative time since last update
 */
export function getTimeSinceUpdate(isoString: string): string {
  const now = new Date();
  const date = new Date(isoString);
  const diffMs = now.getTime() - date.getTime();
  const diffSec = Math.floor(diffMs / 1000);

  if (diffSec < 60) return "Updated just now";
  if (diffSec < 3600) return `Updated ${Math.floor(diffSec / 60)} seconds ago`;
  return `Updated ${Math.floor(diffSec / 3600)} minutes ago`;
}

/**
 * Get progress bar color based on percentage
 */
export function getProgressColor(
  percentage: number,
  inverse: boolean = false
): string {
  const value = inverse ? 100 - percentage : percentage;
  if (value >= 90) return "bg-red-500";
  if (value >= 70) return "bg-yellow-500";
  return "bg-green-500";
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="health-overview-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-6"
    >
      {/* Header Skeleton */}
      <div className="h-24 rounded-lg bg-slate-700" />
      {/* Services Grid Skeleton */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <div key={i} className="h-20 rounded-lg bg-slate-700" />
        ))}
      </div>
      {/* Panels Skeleton */}
      <div className="grid gap-4 md:grid-cols-2">
        <div className="h-48 rounded-lg bg-slate-700" />
        <div className="h-48 rounded-lg bg-slate-700" />
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
      data-testid="health-overview-error"
      role="alert"
      className="flex flex-col items-center justify-center rounded-lg bg-red-900/20 p-8 text-center"
    >
      <div className="mb-4 text-5xl">⚠️</div>
      <h3 className="mb-2 text-lg font-semibold text-red-400">
        Failed to load health data
      </h3>
      <p className="mb-4 text-slate-400">{message}</p>
      {onRetry && (
        <button
          data-testid="health-overview-retry"
          onClick={onRetry}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== System Metrics Section ====================

interface SystemMetricsSectionProps {
  metrics: SystemMetrics;
}

function SystemMetricsSection({ metrics }: SystemMetricsSectionProps) {
  return (
    <div
      data-testid="system-metrics"
      className="rounded-lg bg-slate-800 border border-slate-700 p-6"
    >
      <h3 className="text-lg font-semibold text-white mb-4">
        System Metrics
      </h3>
      <div className="space-y-4">
        {/* CPU */}
        <div>
          <div className="flex justify-between text-sm mb-1">
            <span className="text-slate-400">CPU Usage</span>
            <span className="text-white">{metrics.cpu}%</span>
          </div>
          <div
            className="h-2 rounded-full bg-slate-700"
            role="progressbar"
            aria-valuenow={metrics.cpu}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="CPU usage"
          >
            <div
              className={cn(
                "h-2 rounded-full transition-all",
                getProgressColor(metrics.cpu)
              )}
              style={{ width: `${metrics.cpu}%` }}
            />
          </div>
        </div>

        {/* Memory */}
        <div>
          <div className="flex justify-between text-sm mb-1">
            <span className="text-slate-400">Memory Usage</span>
            <span className="text-white">{metrics.memory}%</span>
          </div>
          <div
            className="h-2 rounded-full bg-slate-700"
            role="progressbar"
            aria-valuenow={metrics.memory}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Memory usage"
          >
            <div
              className={cn(
                "h-2 rounded-full transition-all",
                getProgressColor(metrics.memory)
              )}
              style={{ width: `${metrics.memory}%` }}
            />
          </div>
        </div>

        {/* Disk */}
        <div>
          <div className="flex justify-between text-sm mb-1">
            <span className="text-slate-400">Disk Usage</span>
            <span className="text-white">{metrics.disk}%</span>
          </div>
          <div
            className="h-2 rounded-full bg-slate-700"
            role="progressbar"
            aria-valuenow={metrics.disk}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Disk usage"
          >
            <div
              className={cn(
                "h-2 rounded-full transition-all",
                getProgressColor(metrics.disk)
              )}
              style={{ width: `${metrics.disk}%` }}
            />
          </div>
        </div>

        {/* Uptime */}
        <div className="pt-2 border-t border-slate-700">
          <div className="flex justify-between text-sm">
            <span className="text-slate-400">Uptime</span>
            <span className="text-white">{formatUptime(metrics.uptime)}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

// ==================== Main Component ====================

export function HealthOverview({
  data,
  onRefresh,
  refreshInterval = 30000,
  isLoading = false,
  error = null,
  onRetry,
  "aria-label": ariaLabel,
  className,
}: HealthOverviewProps) {
  const [isRefreshing, setIsRefreshing] = React.useState(false);
  const [isPaused, setIsPaused] = React.useState(false);

  // Auto-refresh
  React.useEffect(() => {
    if (!onRefresh || isPaused || isLoading || isRefreshing) return;

    const interval = setInterval(() => {
      onRefresh();
    }, refreshInterval);

    return () => clearInterval(interval);
  }, [onRefresh, refreshInterval, isPaused, isLoading, isRefreshing]);

  // Manual refresh handler
  const handleRefresh = React.useCallback(async () => {
    if (!onRefresh || isRefreshing) return;
    setIsRefreshing(true);
    try {
      await onRefresh();
    } finally {
      setIsRefreshing(false);
    }
  }, [onRefresh, isRefreshing]);

  // Acknowledge alert handler
  const handleAcknowledgeAlert = React.useCallback((alertId: string) => {
    // In a real implementation, this would call an API
    console.log("Acknowledging alert:", alertId);
  }, []);

  // Dismiss all alerts handler
  const handleDismissAllAlerts = React.useCallback(() => {
    // In a real implementation, this would call an API
    console.log("Dismissing all alerts");
  }, []);

  if (isLoading) {
    return (
      <div className={className}>
        <LoadingSkeleton />
      </div>
    );
  }

  if (error) {
    return (
      <div className={className}>
        <ErrorDisplay message={error} onRetry={onRetry} />
      </div>
    );
  }

  if (!data) {
    return (
      <div
        className={cn(
          "rounded-lg bg-slate-800 border border-slate-700 p-6",
          className
        )}
      >
        <div className="text-slate-400">No health data available</div>
      </div>
    );
  }

  const statusMessage = getStatusMessage(data.overall);
  const headerClass = getStatusHeaderClass(data.overall);
  const textClass = getStatusTextClass(data.overall);
  const serviceCounts = countServicesByStatus(data.services);

  return (
    <div
      data-testid="health-overview"
      className={cn("space-y-6", className)}
      role="region"
      aria-label={ariaLabel || "System Health Overview"}
    >
      {/* Overall Status Header */}
      <div
        data-testid="overall-status"
        className={cn("rounded-lg border p-6", headerClass)}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div
              className={cn(
                "h-4 w-4 rounded-full",
                data.overall === "healthy" && "bg-green-500 animate-pulse",
                data.overall === "degraded" && "bg-yellow-500",
                data.overall === "unhealthy" && "bg-red-500 animate-pulse"
              )}
            />
            <div>
              <h2 className={cn("text-xl font-semibold", textClass)}>
                {statusMessage}
              </h2>
              <div className="text-sm text-slate-400 mt-1">
                {data.services.length} services • {serviceCounts.healthy}{" "}
                healthy
                {serviceCounts.degraded > 0 && (
                  <span className="text-yellow-400">
                    {" "}
                    • {serviceCounts.degraded} degraded
                  </span>
                )}
                {serviceCounts.unhealthy > 0 && (
                  <span className="text-red-400">
                    {" "}
                    • {serviceCounts.unhealthy} down
                  </span>
                )}
              </div>
            </div>
          </div>

          <div className="flex items-center gap-3">
            {/* Last Updated */}
            <div
              data-testid="last-updated"
              className="text-sm text-slate-400"
            >
              {getTimeSinceUpdate(data.lastUpdated)}
            </div>

            {/* Pause/Resume Button */}
            {onRefresh && (
              <button
                data-testid={isPaused ? "resume-button" : "pause-button"}
                onClick={() => setIsPaused(!isPaused)}
                className="p-2 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors"
                aria-label={isPaused ? "Resume auto-refresh" : "Pause auto-refresh"}
                aria-pressed={isPaused}
              >
                {isPaused ? (
                  <svg
                    className="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                    />
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                ) : (
                  <svg
                    className="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                )}
              </button>
            )}

            {/* Manual Refresh Button */}
            {onRefresh && (
              <button
                data-testid="refresh-button"
                onClick={handleRefresh}
                disabled={isRefreshing}
                className={cn(
                  "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors",
                  isRefreshing
                    ? "bg-slate-700 text-slate-400 cursor-not-allowed"
                    : "bg-blue-600 text-white hover:bg-blue-700"
                )}
                aria-label={isRefreshing ? "Refreshing..." : "Refresh now"}
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
        </div>

        {/* Paused Indicator */}
        {isPaused && (
          <div className="mt-4 text-sm text-yellow-400">
            Auto-refresh paused
          </div>
        )}
      </div>

      {/* Service Status Grid */}
      <section aria-labelledby="services-heading">
        <h3
          id="services-heading"
          className="text-lg font-semibold text-white mb-4"
        >
          Services
        </h3>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {data.services.map((service) => (
            <ServiceStatusIndicator
              key={service.name}
              service={service}
              pulse={service.status === "unhealthy"}
            />
          ))}
        </div>
      </section>

      {/* Queue & Database Panels */}
      <div className="grid gap-4 md:grid-cols-2">
        <QueueStatusPanel
          queue={data.queue}
          onRefresh={onRefresh ? handleRefresh : undefined}
          isRefreshing={isRefreshing}
        />
        <DatabaseMetricsPanel
          metrics={data.database}
          onRefresh={onRefresh ? handleRefresh : undefined}
          isRefreshing={isRefreshing}
        />
      </div>

      {/* System Metrics & Alerts */}
      <div className="grid gap-4 md:grid-cols-2">
        <SystemMetricsSection metrics={data.metrics} />
        <AlertsPanel
          alerts={data.alerts}
          onAcknowledge={handleAcknowledgeAlert}
          onDismissAll={handleDismissAllAlerts}
          onRefresh={onRefresh ? handleRefresh : undefined}
          isRefreshing={isRefreshing}
          showSoundToggle
        />
      </div>
    </div>
  );
}

// ==================== Exports ====================

export default HealthOverview;
