/**
 * ContentDecayWidget Component
 * 
 * Task: TASK-36-14 - Navigation & Dashboard Integration
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Dashboard widget displaying content decay summary with quick actions.
 * Supports 4 states: Loading, Error, Empty (no decay), Has Candidates.
 */

"use client";

import * as React from "react";
import {
  TrendingDown,
  AlertTriangle,
  CheckCircle,
  ArrowRight,
  Eye,
  Calendar,
  RefreshCw,
  Clock,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import type { DecaySummaryData, DecayCandidate, ContentDecaySeverity } from "@/lib/types/content-decay";

// ============================================================
// Types
// ============================================================

export interface ContentDecayWidgetProps {
  /** Site ID for navigation */
  siteId: string;
  /** Summary statistics */
  summary?: DecaySummaryData | null;
  /** Top candidates to display (max 3) */
  topCandidates?: DecayCandidate[];
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Refresh callback */
  onRefresh?: () => void;
  /** View candidate callback */
  onViewCandidate?: (id: number) => void;
  /** Mark planned callback */
  onMarkPlanned?: (id: number) => void;
  /** Ignore callback */
  onIgnore?: (id: number) => void;
  /** Additional CSS classes */
  className?: string;
}

interface SeverityBadgeConfig {
  label: string;
  bgClass: string;
  textClass: string;
  borderClass: string;
}

const SEVERITY_STYLES: Record<ContentDecaySeverity, SeverityBadgeConfig> = {
  critical: {
    label: "Critical",
    bgClass: "bg-red-500/20",
    textClass: "text-red-400",
    borderClass: "border-red-500/30",
  },
  high: {
    label: "High",
    bgClass: "bg-orange-500/20",
    textClass: "text-orange-400",
    borderClass: "border-orange-500/30",
  },
  medium: {
    label: "Medium",
    bgClass: "bg-yellow-500/20",
    textClass: "text-yellow-400",
    borderClass: "border-yellow-500/30",
  },
  low: {
    label: "Low",
    bgClass: "bg-slate-500/20",
    textClass: "text-slate-400",
    borderClass: "border-slate-500/30",
  },
};

// ============================================================
// Helper Functions
// ============================================================

function formatNumber(num: number): string {
  if (num >= 1000000000) {
    return `${(num / 1000000000).toFixed(1)}B`;
  }
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
}

function formatRelativeTime(timestamp: string | null): string {
  if (!timestamp) {
    return "Never scanned";
  }

  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffSeconds < 60) {
    return "Just now";
  }
  if (diffMinutes < 60) {
    return `${diffMinutes} minute${diffMinutes !== 1 ? "s" : ""} ago`;
  }
  if (diffHours < 24) {
    return `${diffHours} hour${diffHours !== 1 ? "s" : ""} ago`;
  }
  if (diffDays === 1) {
    return "Yesterday";
  }
  if (diffDays < 7) {
    return `${diffDays} days ago`;
  }
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function truncateUrl(url: string, maxLength: number = 40): string {
  if (url.length <= maxLength) {
    return url;
  }
  return `${url.substring(0, maxLength - 3)}...`;
}

// ============================================================
// Loading State
// ============================================================

function LoadingState() {
  return (
    <div
      data-testid="loading-skeleton"
      className="animate-pulse space-y-4"
      role="status"
      aria-live="polite"
    >
      <span className="sr-only">Loading content decay data</span>
      {[1, 2, 3].map((i) => (
        <div
          key={i}
          className="h-16 bg-slate-700/50 rounded"
          data-testid="skeleton"
        />
      ))}
    </div>
  );
}

// ============================================================
// Error State
// ============================================================

interface ErrorStateProps {
  error: string;
  onRefresh?: () => void;
}

function ErrorState({ error, onRefresh }: ErrorStateProps) {
  return (
    <div
      data-testid="error-state"
      className="text-center py-4"
      role="alert"
    >
      <AlertTriangle
        className="h-8 w-8 text-red-400 mx-auto mb-2"
        data-testid="alert-icon"
        aria-hidden="true"
      />
      <p data-testid="error-message" className="text-red-400 text-sm mb-3">
        {error}
      </p>
      {onRefresh && (
        <Button
          data-testid="retry-btn"
          variant="ghost"
          size="sm"
          onClick={onRefresh}
          className="text-blue-400 hover:text-blue-300"
        >
          <RefreshCw className="h-4 w-4 mr-2" aria-hidden="true" />
          Retry
        </Button>
      )}
    </div>
  );
}

// ============================================================
// Empty State
// ============================================================

interface EmptyStateProps {
  recoveredCount?: number;
  onRefresh?: () => void;
}

function EmptyState({ recoveredCount = 0, onRefresh }: EmptyStateProps) {
  return (
    <div
      data-testid="empty-state"
      className="text-center py-6"
      role="status"
      aria-live="polite"
    >
      <CheckCircle
        className="h-10 w-10 text-green-400 mx-auto mb-3"
        data-testid="check-icon"
        aria-hidden="true"
      />
      <p data-testid="empty-message" className="text-green-400 font-medium mb-1">
        No content decay detected
      </p>
      <p className="text-sm text-slate-400 mb-3">
        Your content is performing well
      </p>
      {recoveredCount > 0 && (
        <p className="text-sm text-green-400" data-testid="recovered-count">
          {recoveredCount} pages recovered
        </p>
      )}
      {onRefresh && (
        <Button
          data-testid="run-scan-btn"
          variant="outline"
          size="sm"
          onClick={onRefresh}
          className="mt-3 border-slate-700 text-slate-300"
        >
          Run Scan
        </Button>
      )}
    </div>
  );
}

// ============================================================
// Severity Badge
// ============================================================

interface SeverityCountBadgeProps {
  severity: ContentDecaySeverity;
  count: number;
}

function SeverityCountBadge({ severity, count }: SeverityCountBadgeProps) {
  if (count <= 0) return null;

  const config = SEVERITY_STYLES[severity];

  return (
    <div
      data-testid={`${severity}-badge`}
      data-severity={severity}
      className={cn(
        "flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium",
        config.bgClass,
        config.textClass,
        config.borderClass
      )}
      aria-label={`${count} ${config.label.toLowerCase()} issues`}
    >
      <span data-testid={`${severity}-count`}>{count}</span>
      <span className="opacity-80"> {config.label}</span>
    </div>
  );
}

// ============================================================
// Summary Section
// ============================================================

interface SummarySectionProps {
  summary: DecaySummaryData;
}

function SummarySection({ summary }: SummarySectionProps) {
  return (
    <div data-testid="summary-section" className="space-y-3">
      {/* Severity Badges */}
      <div className="flex flex-wrap gap-2">
        <SeverityCountBadge severity="critical" count={summary.criticalCount} />
        <SeverityCountBadge severity="high" count={summary.highCount} />
        <SeverityCountBadge severity="medium" count={summary.mediumCount} />
      </div>

      {/* Total Candidates */}
      <p className="text-sm text-slate-300" data-testid="total-candidates">
        <span className="font-semibold">{summary.totalCandidates}</span>
        <span className="text-slate-400"> pages need attention</span>
      </p>

      {/* Recovery Upside */}
      {summary.recoveryUpside > 0 && (
        <p className="text-sm text-green-400" data-testid="recovery-upside">
          {formatNumber(summary.recoveryUpside)} potential clicks to recover
        </p>
      )}

      {/* Recovered Count */}
      {summary.recoveredCount > 0 && (
        <p className="text-sm text-slate-400" data-testid="recovered-info">
          {summary.recoveredCount} pages recovered
        </p>
      )}
    </div>
  );
}

// ============================================================
// Candidate Item
// ============================================================

interface CandidateItemProps {
  candidate: DecayCandidate;
  onView?: (id: number) => void;
  onMarkPlanned?: (id: number) => void;
  onIgnore?: (id: number) => void;
  disabled?: boolean;
}

function CandidateItem({
  candidate,
  onView,
  onMarkPlanned,
  onIgnore,
  disabled,
}: CandidateItemProps) {
  const config = SEVERITY_STYLES[candidate.severity];
  const reasonLabel = candidate.primaryReasonCode
    .replace(/_/g, " ")
    .replace(/\b\w/g, (l) => l.toUpperCase());

  return (
    <li
      data-testid={`candidate-${candidate.id}`}
      data-severity={candidate.severity}
      className="p-3 rounded-lg bg-slate-800/50 border border-slate-700/50 hover:bg-slate-800 transition-colors"
      role="listitem"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1 min-w-0">
          {/* Title */}
          <p
            className="text-sm font-medium text-slate-200 truncate"
            title={candidate.pageTitle || candidate.pageUrl}
            data-testid="candidate-title"
          >
            {candidate.pageTitle || "Untitled"}
          </p>

          {/* URL */}
          <p
            className="text-xs text-slate-500 truncate mt-0.5"
            title={candidate.pageUrl}
            data-testid="candidate-url"
          >
            {truncateUrl(candidate.pageUrl)}
          </p>

          {/* Metrics Row */}
          <div className="flex items-center gap-3 mt-2">
            {/* Decay Score */}
            <span
              className={cn(
                "text-xs px-1.5 py-0.5 rounded",
                config.bgClass,
                config.textClass
              )}
              data-testid="decay-score"
            >
              Score: {candidate.decayScore}
            </span>

            {/* Reason */}
            <span className="text-xs text-slate-400" data-testid="primary-reason">
              {reasonLabel}
            </span>

            {/* Lost Clicks */}
            {candidate.lostClickEstimate > 0 && (
              <span
                className="text-xs text-red-400"
                data-testid="lost-clicks"
              >
                -{formatNumber(candidate.lostClickEstimate)} clicks
              </span>
            )}
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1" data-testid="candidate-actions">
          <Button
            data-testid="view-btn"
            variant="ghost"
            size="sm"
            onClick={() => onView?.(candidate.id)}
            disabled={disabled}
            aria-label={`View ${candidate.pageTitle || candidate.pageUrl}`}
            className="h-7 px-2 text-slate-400 hover:text-slate-200"
          >
            <Eye className="h-3.5 w-3.5" aria-hidden="true" />
          </Button>
          <Button
            data-testid="plan-btn"
            variant="ghost"
            size="sm"
            onClick={() => onMarkPlanned?.(candidate.id)}
            disabled={disabled}
            aria-label="Mark as planned"
            className="h-7 px-2 text-slate-400 hover:text-slate-200"
          >
            <Calendar className="h-3.5 w-3.5" aria-hidden="true" />
          </Button>
        </div>
      </div>
    </li>
  );
}

// ============================================================
// Main Component
// ============================================================

export function ContentDecayWidget({
  siteId,
  summary,
  topCandidates = [],
  isLoading = false,
  error = null,
  onRefresh,
  onViewCandidate,
  onMarkPlanned,
  onIgnore,
  className,
}: ContentDecayWidgetProps) {
  const displayCandidates = topCandidates.slice(0, 3);
  const hasCandidates = summary && summary.totalCandidates > 0;
  const isEmpty = summary && summary.totalCandidates === 0;

  return (
    <Card
      data-testid="content-decay-widget"
      className={cn(
        "widget-card border-slate-800 bg-slate-900/60",
        className
      )}
      role="region"
      aria-label="Content Decay Summary"
    >
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-lg">
            <TrendingDown
              className="h-5 w-5 text-slate-400"
              data-testid="trending-down-icon"
              aria-hidden="true"
            />
            <span>Content Decay</span>
          </CardTitle>

          {/* View All Link */}
          {hasCandidates && (
            <a
              href={`/sites/${siteId}/content-decay`}
              data-testid="view-all-link"
              aria-label="View all content decay issues"
              className="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-1"
            >
              View All
              <ArrowRight className="h-4 w-4" data-testid="arrow-right-icon" aria-hidden="true" />
            </a>
          )}
        </div>
      </CardHeader>

      <CardContent>
        {/* Loading State */}
        {isLoading && <LoadingState />}

        {/* Error State */}
        {!isLoading && error && <ErrorState error={error} onRefresh={onRefresh} />}

        {/* Empty State */}
        {!isLoading && !error && isEmpty && (
          <EmptyState
            recoveredCount={summary?.recoveredCount}
            onRefresh={onRefresh}
          />
        )}

        {/* Has Candidates */}
        {!isLoading && !error && hasCandidates && (
          <div className="space-y-4">
            {/* Summary */}
            <SummarySection summary={summary!} />

            {/* Top Candidates - only show if there are candidates */}
            {displayCandidates.length > 0 && (
              <>
                {/* Divider */}
                <hr className="border-slate-700" />

                <div data-testid="candidates-section">
                  <h3 className="text-sm font-medium text-slate-300 mb-2">
                    Top Candidates
                  </h3>
                  <ul className="space-y-2" role="list" aria-label="Top decay candidates">
                    {displayCandidates.map((candidate) => (
                      <CandidateItem
                        key={candidate.id}
                        candidate={candidate}
                        onView={onViewCandidate}
                        onMarkPlanned={onMarkPlanned}
                        onIgnore={onIgnore}
                      />
                    ))}
                  </ul>
                </div>
              </>
            )}

            {/* Timestamp */}
            <div className="flex items-center gap-1.5 text-xs text-slate-500 pt-2">
              <Clock className="h-3.5 w-3.5" aria-hidden="true" />
              <span data-testid="last-scanned">
                Last scanned: {formatRelativeTime(summary?.lastScannedAt ?? null)}
              </span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default ContentDecayWidget;
