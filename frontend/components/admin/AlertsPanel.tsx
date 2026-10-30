"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export type AlertSeverity = "critical" | "warning" | "info";

export interface Alert {
  id: string;
  severity: AlertSeverity;
  message: string;
  timestamp: string;
  acknowledged: boolean;
  source?: string;
  metadata?: Record<string, unknown>;
}

export interface AlertsPanelProps {
  alerts: Alert[];
  onAcknowledge?: (alertId: string) => void;
  onDismissAll?: () => void;
  onRefresh?: () => void;
  isLoading?: boolean;
  isRefreshing?: boolean;
  error?: string | null;
  onRetry?: () => void;
  maxVisible?: number;
  showSoundToggle?: boolean;
  "aria-label"?: string;
  className?: string;
}

// ==================== Helper Functions ====================

/**
 * Get severity color classes
 */
export function getSeverityClasses(severity: AlertSeverity): {
  bg: string;
  border: string;
  text: string;
  icon: string;
} {
  switch (severity) {
    case "critical":
      return {
        bg: "bg-red-900/20",
        border: "border-red-700",
        text: "text-red-400",
        icon: "text-red-500",
      };
    case "warning":
      return {
        bg: "bg-yellow-900/20",
        border: "border-yellow-700",
        text: "text-yellow-400",
        icon: "text-yellow-500",
      };
    case "info":
    default:
      return {
        bg: "bg-blue-900/20",
        border: "border-blue-700",
        text: "text-blue-400",
        icon: "text-blue-500",
      };
  }
}

/**
 * Get severity icon
 */
export function getSeverityIcon(severity: AlertSeverity): string {
  switch (severity) {
    case "critical":
      return "✕";
    case "warning":
      return "⚠";
    case "info":
      return "ℹ";
    default:
      return "?";
  }
}

/**
 * Get relative time
 */
export function getRelativeTime(isoString: string): string {
  const now = new Date();
  const date = new Date(isoString);
  const diffMs = now.getTime() - date.getTime();
  const diffSec = Math.floor(diffMs / 1000);

  if (diffSec < 60) return "Just now";
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)} minutes ago`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} hours ago`;
  return `${Math.floor(diffSec / 86400)} days ago`;
}

/**
 * Count unacknowledged alerts by severity
 */
export function countBySeverity(
  alerts: Alert[]
): Record<AlertSeverity, number> {
  const counts: Record<AlertSeverity, number> = {
    critical: 0,
    warning: 0,
    info: 0,
  };
  alerts.forEach((alert) => {
    if (!alert.acknowledged) {
      counts[alert.severity]++;
    }
  });
  return counts;
}

/**
 * Sort alerts by severity and time
 */
export function sortAlerts(alerts: Alert[]): Alert[] {
  const severityOrder = { critical: 0, warning: 1, info: 2 };
  return [...alerts].sort((a, b) => {
    // Unacknowledged first
    if (a.acknowledged !== b.acknowledged) {
      return a.acknowledged ? 1 : -1;
    }
    // Then by severity
    if (a.severity !== b.severity) {
      return severityOrder[a.severity] - severityOrder[b.severity];
    }
    // Then by time (newest first)
    return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
  });
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="alerts-panel-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-3"
    >
      {[1, 2, 3].map((i) => (
        <div key={i} className="h-16 rounded bg-slate-700" />
      ))}
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
      data-testid="alerts-panel-error"
      role="alert"
      className="flex flex-col items-start gap-2 p-4"
    >
      <span className="text-sm text-red-400">{message}</span>
      {onRetry && (
        <button
          data-testid="alerts-panel-retry"
          onClick={onRetry}
          className="text-xs text-blue-400 hover:text-blue-300 underline"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== Alert Item ====================

interface AlertItemProps {
  alert: Alert;
  onAcknowledge?: (alertId: string) => void;
}

function AlertItem({ alert, onAcknowledge }: AlertItemProps) {
  const classes = getSeverityClasses(alert.severity);
  const icon = getSeverityIcon(alert.severity);
  const relativeTime = getRelativeTime(alert.timestamp);

  const handleAcknowledge = React.useCallback(() => {
    if (onAcknowledge && !alert.acknowledged) {
      onAcknowledge(alert.id);
    }
  }, [alert.id, alert.acknowledged, onAcknowledge]);

  const handleKeyDown = React.useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        handleAcknowledge();
      }
    },
    [handleAcknowledge]
  );

  return (
    <div
      data-testid={`alert-item-${alert.id}`}
      data-severity={alert.severity}
      data-acknowledged={alert.acknowledged}
      role="alert"
      className={cn(
        "flex items-start gap-3 rounded-lg border p-3 transition-opacity",
        classes.bg,
        classes.border,
        alert.acknowledged && "opacity-50"
      )}
    >
      {/* Severity Icon */}
      <span
        aria-hidden="true"
        className={cn("text-lg mt-0.5 flex-shrink-0", classes.icon)}
      >
        {icon}
      </span>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div
          className={cn(
            "text-sm font-medium",
            alert.acknowledged ? "text-slate-400" : classes.text
          )}
        >
          {alert.message}
        </div>
        <div className="flex items-center gap-2 mt-1 text-xs text-slate-500">
          <span>{relativeTime}</span>
          {alert.source && (
            <>
              <span>•</span>
              <span>{alert.source}</span>
            </>
          )}
        </div>
      </div>

      {/* Acknowledge Button */}
      {onAcknowledge && !alert.acknowledged && (
        <button
          data-testid={`acknowledge-${alert.id}`}
          onClick={handleAcknowledge}
          onKeyDown={handleKeyDown}
          className="flex-shrink-0 px-2 py-1 text-xs rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
          aria-label={`Acknowledge alert: ${alert.message}`}
        >
          Acknowledge
        </button>
      )}

      {/* Acknowledged Badge */}
      {alert.acknowledged && (
        <span
          data-testid={`acknowledged-badge-${alert.id}`}
          className="flex-shrink-0 flex items-center gap-1 text-xs text-slate-500"
        >
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
              d="M5 13l4 4L19 7"
            />
          </svg>
          Acknowledged
        </span>
      )}
    </div>
  );
}

// ==================== Component ====================

export function AlertsPanel({
  alerts,
  onAcknowledge,
  onDismissAll,
  onRefresh,
  isLoading = false,
  isRefreshing = false,
  error = null,
  onRetry,
  maxVisible,
  showSoundToggle = false,
  "aria-label": ariaLabel,
  className,
}: AlertsPanelProps) {
  const [soundEnabled, setSoundEnabled] = React.useState(false);

  React.useEffect(() => {
    // Load sound preference from localStorage
    const stored = localStorage.getItem("alertSoundEnabled");
    if (stored !== null) {
      setSoundEnabled(stored === "true");
    }
  }, []);

  const toggleSound = React.useCallback(() => {
    setSoundEnabled((prev) => {
      const newValue = !prev;
      localStorage.setItem("alertSoundEnabled", String(newValue));
      return newValue;
    });
  }, []);

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
        aria-label={ariaLabel || "Active Alerts"}
      >
        <div className="p-4 border-b border-slate-700">
          <h3 className="text-lg font-semibold text-white">Active Alerts</h3>
        </div>
        <ErrorDisplay message={error} onRetry={onRetry} />
      </div>
    );
  }

  const sortedAlerts = sortAlerts(alerts);
  const visibleAlerts = maxVisible ? sortedAlerts.slice(0, maxVisible) : sortedAlerts;
  const severityCounts = countBySeverity(alerts);
  const unacknowledgedCount = alerts.filter((a) => !a.acknowledged).length;

  return (
    <div
      data-testid="alerts-panel"
      className={cn(
        "rounded-lg bg-slate-800 border border-slate-700",
        className
      )}
      role="region"
      aria-label={ariaLabel || "Active Alerts"}
      aria-live="polite"
    >
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <div className="flex items-center gap-3">
          <h3 className="text-lg font-semibold text-white">Active Alerts</h3>
          {unacknowledgedCount > 0 && (
            <span
              data-testid="alert-count-badge"
              className={cn(
                "px-2 py-0.5 text-xs font-medium rounded-full",
                severityCounts.critical > 0
                  ? "bg-red-900/50 text-red-400"
                  : "bg-yellow-900/50 text-yellow-400"
              )}
            >
              {unacknowledgedCount}
            </span>
          )}
        </div>

        <div className="flex items-center gap-2">
          {showSoundToggle && (
            <button
              data-testid="sound-toggle"
              onClick={toggleSound}
              className={cn(
                "p-1.5 rounded transition-colors",
                soundEnabled
                  ? "text-blue-400 hover:text-blue-300"
                  : "text-slate-500 hover:text-slate-400"
              )}
              aria-label={soundEnabled ? "Disable alert sounds" : "Enable alert sounds"}
              aria-pressed={soundEnabled}
            >
              {soundEnabled ? (
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
                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
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
                    d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                  />
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
                  />
                </svg>
              )}
            </button>
          )}

          {onDismissAll && unacknowledgedCount > 1 && (
            <button
              data-testid="dismiss-all-button"
              onClick={onDismissAll}
              className="px-3 py-1.5 text-xs font-medium rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors"
            >
              Dismiss All
            </button>
          )}

          {onRefresh && (
            <button
              data-testid="alerts-refresh-button"
              onClick={onRefresh}
              disabled={isRefreshing}
              className={cn(
                "flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors",
                isRefreshing
                  ? "bg-slate-700 text-slate-400 cursor-not-allowed"
                  : "bg-blue-600 text-white hover:bg-blue-700"
              )}
              aria-label={isRefreshing ? "Refreshing alerts" : "Refresh alerts"}
            >
              {isRefreshing ? (
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
              ) : (
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
              )}
            </button>
          )}
        </div>
      </div>

      {/* Alerts List */}
      <div className="p-4">
        {visibleAlerts.length === 0 ? (
          <div
            data-testid="no-alerts"
            className="flex flex-col items-center justify-center py-8 text-slate-400"
          >
            <svg
              className="h-12 w-12 mb-3"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p className="text-sm">No active alerts</p>
          </div>
        ) : (
          <div className="space-y-3">
            {visibleAlerts.map((alert) => (
              <AlertItem
                key={alert.id}
                alert={alert}
                onAcknowledge={onAcknowledge}
              />
            ))}
          </div>
        )}

        {maxVisible && sortedAlerts.length > maxVisible && (
          <div className="mt-3 text-center text-xs text-slate-500">
            Showing {maxVisible} of {sortedAlerts.length} alerts
          </div>
        )}
      </div>
    </div>
  );
}

// ==================== Exports ====================

export default AlertsPanel;
