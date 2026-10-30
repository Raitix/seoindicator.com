"use client";

import * as React from "react";
import {
  ChevronDown,
  ChevronRight,
  AlertCircle,
  AlertTriangle,
  Info,
  ArrowRight,
  CheckCircle,
} from "lucide-react";

import { cn } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";

/**
 * Issue interface representing a single SEO issue
 */
export interface Issue {
  id: string;
  title: string;
  description: string;
  severity: "critical" | "warning" | "info";
  category: string;
  impact: string;
}

/**
 * TopIssues component props
 */
export interface TopIssuesProps {
  issues: Issue[];
  isPartial: boolean;
  auditHash?: string;
  className?: string;
}

/**
 * Severity order for sorting (Critical > Warning > Info)
 */
const severityOrder: Record<Issue["severity"], number> = {
  critical: 0,
  warning: 1,
  info: 2,
};

/**
 * Get severity badge variant
 */
function getSeverityVariant(severity: Issue["severity"]): "destructive" | "warning" | "secondary" {
  switch (severity) {
    case "critical":
      return "destructive";
    case "warning":
      return "warning";
    default:
      return "secondary";
  }
}

/**
 * Get category badge variant (lowercase for styling)
 */
function getCategoryVariant(category: string): string {
  return category.toLowerCase();
}

/**
 * Get severity icon
 */
function getSeverityIcon(severity: Issue["severity"]) {
  switch (severity) {
    case "critical":
      return <AlertCircle className="h-4 w-4" aria-hidden="true" />;
    case "warning":
      return <AlertTriangle className="h-4 w-4" aria-hidden="true" />;
    default:
      return <Info className="h-4 w-4" aria-hidden="true" />;
  }
}

/**
 * Single issue item component
 */
interface IssueItemProps {
  issue: Issue;
  isExpanded: boolean;
  onToggle: () => void;
}

function IssueItem({ issue, isExpanded, onToggle }: IssueItemProps) {
  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      onToggle();
    }
  };

  // Truncate description for collapsed state (max 100 chars)
  const truncatedDescription =
    issue.description.length > 100
      ? `${issue.description.substring(0, 100)}...`
      : issue.description;

  return (
    <li
      role="listitem"
      data-testid={`issue-${issue.id}`}
      aria-expanded={isExpanded}
      tabIndex={0}
      onClick={onToggle}
      onKeyDown={handleKeyDown}
      className="rounded-lg border border-slate-800 bg-slate-900/60 p-4 cursor-pointer hover:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
    >
      {/* Header row with badges and title */}
      <div data-testid={`issue-header-${issue.id}`} className="flex items-start gap-3">
        {/* Severity icon */}
        <span
          className={cn(
            "mt-0.5 shrink-0",
            issue.severity === "critical" && "text-red-500",
            issue.severity === "warning" && "text-yellow-500",
            issue.severity === "info" && "text-blue-500"
          )}
        >
          {getSeverityIcon(issue.severity)}
        </span>

        <div className="flex-1 min-w-0">
          {/* Badges row */}
          <div className="flex flex-wrap gap-2 mb-1">
            <Badge
              variant={issue.severity as React.ComponentProps<typeof Badge>["variant"]}
              data-testid="badge"
            >
              {issue.severity.charAt(0).toUpperCase() + issue.severity.slice(1)}
            </Badge>
            <Badge
              variant={issue.category.toLowerCase() as React.ComponentProps<typeof Badge>["variant"]}
              data-testid="badge"
            >
              {issue.category}
            </Badge>
          </div>

          {/* Title */}
          <h3
            data-testid={`issue-title-${issue.id}`}
            className="font-medium text-white mt-1"
          >
            {issue.title}
          </h3>

          {/* Chevron indicator */}
          <span className="sr-only">
            {isExpanded ? "Collapse" : "Expand"} issue details
          </span>
        </div>

        {/* Chevron */}
        <span className="shrink-0 text-slate-400">
          {isExpanded ? (
            <ChevronDown className="h-4 w-4" data-testid="chevron-icon" aria-hidden="true" />
          ) : (
            <ChevronRight className="h-4 w-4" data-testid="chevron-right-icon" aria-hidden="true" />
          )}
        </span>
      </div>

      {/* Collapsed description preview */}
      <div
        data-testid={`issue-collapsed-${issue.id}`}
        className="mt-2 text-sm text-slate-400 pl-7"
      >
        <p>{truncatedDescription}</p>
      </div>

      {/* Expanded details */}
      <div
        data-testid={`issue-expanded-${issue.id}`}
        hidden={!isExpanded}
        className="mt-3 border-t border-slate-800 pt-3 pl-7"
      >
        <p className="text-sm text-slate-300">{issue.description}</p>
        <p className="mt-2 text-sm text-slate-400">
          <strong className="text-slate-300">Impact:</strong> {issue.impact}
        </p>
      </div>
    </li>
  );
}

/**
 * TopIssues component displays a prioritized list of SEO issues found during the audit,
 * with severity indicators and "View full report" prompt for anonymous users.
 */
export function TopIssues({
  issues,
  isPartial,
  auditHash,
  className,
}: TopIssuesProps) {
  // Track expanded state for each issue
  const [expandedIds, setExpandedIds] = React.useState<Set<string>>(new Set());

  // Toggle expanded state for an issue
  const toggleExpand = (id: string) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  // Handle empty/null/undefined issues
  if (!issues || !Array.isArray(issues) || issues.length === 0) {
    return (
      <div
        role="region"
        aria-label="Top Issues"
        data-testid="top-issues-empty"
        className={cn("rounded-lg border border-slate-800 bg-slate-900/60 p-6", className)}
      >
        <h2 data-testid="header" className="text-lg font-semibold text-white mb-4">
          Top Issues (0)
        </h2>
        <div data-testid="empty-state" className="flex flex-col items-center justify-center py-8 text-center">
          <CheckCircle
            className="h-12 w-12 text-green-500 mb-3"
            data-testid="check-circle-icon"
            aria-hidden="true"
          />
          <p className="text-slate-400">No issues found. Your page is performing well!</p>
        </div>
      </div>
    );
  }

  // Normalize issues - handle missing fields with defaults
  const normalizedIssues: Issue[] = issues.map((issue) => ({
    id: issue.id || `issue-${Math.random().toString(36).substr(2, 9)}`,
    title: issue.title || "Untitled Issue",
    description: issue.description || "No description available",
    severity: issue.severity || ("info" as const),
    category: issue.category || "Unknown",
    impact: issue.impact || "Impact not specified",
  }));

  // Sort issues by severity (Critical > Warning > Info), then by category alphabetically
  const sortedIssues = [...normalizedIssues].sort((a, b) => {
    const severityDiff = severityOrder[a.severity] - severityOrder[b.severity];
    if (severityDiff !== 0) return severityDiff;
    return a.category.localeCompare(b.category);
  });

  // Limit issues if partial view (show only top 5)
  const displayedIssues = isPartial ? sortedIssues.slice(0, 5) : sortedIssues;
  const remainingCount = isPartial ? sortedIssues.length - 5 : 0;

  // Count by severity
  const criticalCount = normalizedIssues.filter((i) => i.severity === "critical").length;
  const warningCount = normalizedIssues.filter((i) => i.severity === "warning").length;
  const infoCount = normalizedIssues.filter((i) => i.severity === "info").length;

  return (
    <div
      role="region"
      aria-labelledby="top-issues-heading"
      data-testid="top-issues"
      className={cn("rounded-lg border border-slate-800 bg-slate-900/60 p-6", className)}
    >
      {/* Header */}
      <h2
        id="top-issues-heading"
        data-testid="header"
        className="text-lg font-semibold text-white mb-4"
      >
        Top Issues ({normalizedIssues.length})
      </h2>

      {/* Severity count badges */}
      <div data-testid="severity-counts" className="flex flex-wrap gap-2 mb-4">
        {criticalCount > 0 && (
          <span
            data-testid="critical-count"
            data-variant="destructive"
            className="inline-flex items-center gap-1 rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-medium text-red-400"
          >
            <AlertCircle className="h-3 w-3" aria-hidden="true" />
            {criticalCount} Critical
          </span>
        )}
        {warningCount > 0 && (
          <span
            data-testid="warning-count"
            data-variant="warning"
            className="inline-flex items-center gap-1 rounded-full bg-yellow-500/20 px-2.5 py-0.5 text-xs font-medium text-yellow-400"
          >
            <AlertTriangle className="h-3 w-3" aria-hidden="true" />
            {warningCount} Warning{warningCount !== 1 ? "s" : ""}
          </span>
        )}
        {infoCount > 0 && (
          <span
            data-testid="info-count"
            data-variant="secondary"
            className="inline-flex items-center gap-1 rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-medium text-blue-400"
          >
            <Info className="h-3 w-3" aria-hidden="true" />
            {infoCount} Info
          </span>
        )}
      </div>

      {/* Issue list */}
      <ul role="list" data-testid="issue-list" className="space-y-3">
        {displayedIssues.map((issue, index) => (
          <IssueItem
            key={`${issue.id}-${index}`}
            issue={issue}
            isExpanded={expandedIds.has(issue.id)}
            onToggle={() => toggleExpand(issue.id)}
          />
        ))}
      </ul>

      {/* View full report prompt for anonymous users */}
      {isPartial && remainingCount > 0 && (
        <div
          data-testid="view-full-prompt"
          className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 rounded-lg bg-slate-800/50 p-4"
        >
          <span className="text-sm text-slate-400">
            +{remainingCount} more issue{remainingCount !== 1 ? "s" : ""}
          </span>
          {auditHash && (
            <a
              href={`/audit/?h=${auditHash}`}
              data-testid="view-full-link"
              className="inline-flex items-center gap-2 text-sm font-medium text-emerald-400 hover:text-emerald-300 transition-colors"
            >
              View Full Report
              <ArrowRight className="h-4 w-4" data-testid="arrow-icon" aria-hidden="true" />
            </a>
          )}
        </div>
      )}
    </div>
  );
}

export default TopIssues;
