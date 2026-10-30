/**
 * AuditWidget Component
 * 
 * Task: TASK-14-17 - Create Audit Dashboard Widget
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Dashboard widget displaying site audit summary with quick actions.
 * Supports 4 states: No Audits, Running, Completed with Issues, All Fixed.
 */

'use client';

import * as React from 'react';
import { Search, AlertCircle, AlertTriangle, Info, CheckCircle, Loader2, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

// ============================================================
// Types
// ============================================================

export interface AuditIssue {
  id: number;
  description: string;
  severity: 'critical' | 'warning' | 'notice';
}

export interface LastAudit {
  id: number;
  status: string;
  issues: {
    critical: number;
    warning: number;
    notice: number;
  };
  completed_at: string;
  pages_crawled?: number;
}

export interface RunningAudit {
  id: number;
  progress: number;
  pages_crawled: number;
  issues_found: number;
}

export interface AuditWidgetProps {
  /** Site ID for audit operations */
  siteId: string;
  /** Last completed audit data */
  lastAudit?: LastAudit;
  /** Currently running audit data */
  runningAudit?: RunningAudit;
  /** Recent issues to display (top 3 shown) */
  recentIssues?: AuditIssue[];
  /** Callback when user starts a new audit */
  onStartAudit: () => void;
  /** Callback when user clicks "View All Issues" */
  onViewAll: () => void;
  /** Polling interval in ms for running audits (default: 5000) */
  pollingInterval?: number;
  /** Optional cancel audit callback */
  onCancelAudit?: () => void;
}

// ============================================================
// Severity Badge Component
// ============================================================

interface SeverityBadgeProps {
  type: 'critical' | 'warning' | 'notice';
  count: number;
}

function SeverityBadge({ type, count }: SeverityBadgeProps) {
  if (count <= 0) return null;

  const config = {
    critical: {
      icon: AlertCircle,
      testId: 'critical-badge',
      countTestId: 'critical-count',
      iconTestId: 'alert-icon',
      className: 'bg-red-500/20 text-red-400 border-red-500/30',
    },
    warning: {
      icon: AlertTriangle,
      testId: 'warning-badge',
      countTestId: 'warning-count',
      iconTestId: 'warning-icon',
      className: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
    },
    notice: {
      icon: Info,
      testId: 'notice-badge',
      countTestId: 'notice-count',
      iconTestId: 'info-icon',
      className: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
    },
  };

  const { icon: Icon, testId, countTestId, iconTestId, className } = config[type];
  const label = type.charAt(0).toUpperCase() + type.slice(1);

  return (
    <div
      data-testid={testId}
      className={cn(
        'flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium',
        className
      )}
    >
      <Icon className="h-4 w-4" data-testid={iconTestId} aria-hidden="true" />
      <span data-testid={countTestId}>{count}</span>
      <span className="text-xs opacity-80">{label}</span>
    </div>
  );
}

// ============================================================
// No Audits State
// ============================================================

interface NoAuditsStateProps {
  onStartAudit: () => void;
}

function NoAuditsState({ onStartAudit }: NoAuditsStateProps) {
  return (
    <div data-testid="no-audits-state" className="text-center py-6">
      <p data-testid="no-audits-message" className="text-slate-400 mb-4">
        No audits yet. Run your first audit to identify technical SEO issues.
      </p>
      <Button
        data-testid="start-first-audit-btn"
        onClick={onStartAudit}
        className="bg-blue-600 hover:bg-blue-700"
      >
        Start First Audit
      </Button>
    </div>
  );
}

// ============================================================
// Running Audit State
// ============================================================

interface RunningStateProps {
  runningAudit: RunningAudit;
  onCancelAudit?: () => void;
}

function RunningState({ runningAudit, onCancelAudit }: RunningStateProps) {
  const progressPercent = Math.min(100, Math.max(0, runningAudit.progress));

  return (
    <div data-testid="running-state" className="space-y-4">
      <div className="flex items-center gap-2">
        <Loader2 
          className="h-5 w-5 animate-spin text-blue-400" 
          data-testid="loader-icon" 
          aria-hidden="true" 
        />
        <span data-testid="running-text" className="text-blue-400 font-medium">
          Audit in progress...
        </span>
      </div>

      <div data-testid="running-progress" className="text-sm text-slate-400">
        {runningAudit.pages_crawled} pages • {runningAudit.issues_found} issues found
      </div>

      {/* Progress Bar */}
      <div
        data-testid="progress-bar-container"
        className="w-full bg-slate-700 rounded-full h-2"
      >
        <div
          data-testid="progress-bar"
          className="bg-blue-500 h-2 rounded-full transition-all duration-300"
          style={{ width: `${progressPercent}%` }}
          role="progressbar"
          aria-valuenow={progressPercent}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label={`Audit progress: ${progressPercent}%`}
        />
      </div>

      <div className="flex items-center justify-between">
        <span data-testid="progress-percent" className="text-xs text-slate-500">
          {progressPercent}%
        </span>
        {onCancelAudit && (
          <Button
            data-testid="cancel-audit-btn"
            variant="ghost"
            size="sm"
            onClick={onCancelAudit}
            className="text-slate-400 hover:text-slate-200"
          >
            Cancel
          </Button>
        )}
      </div>
    </div>
  );
}

// ============================================================
// All Fixed State
// ============================================================

interface AllFixedStateProps {
  lastAudit: LastAudit;
  onStartAudit: () => void;
}

function AllFixedState({ lastAudit, onStartAudit }: AllFixedStateProps) {
  return (
    <div data-testid="all-fixed-state" className="text-center py-4">
      <CheckCircle 
        className="h-8 w-8 text-green-400 mx-auto mb-2" 
        data-testid="check-icon" 
        aria-hidden="true" 
      />
      <p data-testid="all-fixed-message" className="text-green-400 font-medium mb-2">
        All issues resolved!
      </p>
      <div className="text-sm text-slate-400 space-y-1">
        <p data-testid="last-audit-info">
          Last audit: {new Date(lastAudit.completed_at).toLocaleDateString()}
        </p>
        {lastAudit.pages_crawled !== undefined && (
          <p data-testid="pages-info">
            {lastAudit.pages_crawled} pages • 0 open issues
          </p>
        )}
      </div>
      <Button
        data-testid="run-new-audit-btn"
        variant="outline"
        size="sm"
        onClick={onStartAudit}
        className="mt-4"
      >
        Run New Audit
      </Button>
    </div>
  );
}

// ============================================================
// Completed State (with Issues)
// ============================================================

interface CompletedStateProps {
  lastAudit: LastAudit;
  recentIssues?: AuditIssue[];
  onStartAudit: () => void;
  onViewAll: () => void;
}

function CompletedState({
  lastAudit,
  recentIssues,
  onStartAudit,
  onViewAll,
}: CompletedStateProps) {
  const displayIssues = recentIssues?.slice(0, 3) || [];

  return (
    <div className="space-y-4">
      {/* Last Audit Date */}
      <div data-testid="last-audit-info" className="text-sm text-slate-400">
        Last Audit: {new Date(lastAudit.completed_at).toLocaleDateString()}
      </div>

      {/* Severity Badges */}
      <div data-testid="severity-badges" className="flex flex-wrap gap-2">
        <SeverityBadge type="critical" count={lastAudit.issues.critical} />
        <SeverityBadge type="warning" count={lastAudit.issues.warning} />
        <SeverityBadge type="notice" count={lastAudit.issues.notice} />
      </div>

      {/* Divider */}
      <hr className="border-slate-700" />

      {/* Recent Issues */}
      <div data-testid="recent-issues-section">
        <h3 data-testid="recent-issues-title" className="text-sm font-medium text-slate-300 mb-2">
          Recent Issues
        </h3>
        <ul data-testid="recent-issues-list" className="space-y-1">
          {displayIssues.length > 0 ? (
            displayIssues.map((issue) => (
              <li
                key={issue.id}
                data-testid={`issue-${issue.id}`}
                data-severity={issue.severity}
                className="text-sm text-slate-400 flex items-start gap-2"
              >
                <span className="text-slate-500">•</span>
                <span>{issue.description}</span>
              </li>
            ))
          ) : (
            <li data-testid="no-recent-issues" className="text-sm text-slate-500 italic">
              No recent issues
            </li>
          )}
        </ul>
      </div>

      {/* View All Button */}
      <Button
        data-testid="view-all-btn"
        variant="ghost"
        size="sm"
        onClick={onViewAll}
        className="w-full text-slate-400 hover:text-slate-200 flex items-center justify-center gap-2"
      >
        View All Issues
        <ArrowRight className="h-4 w-4" data-testid="external-link-icon" aria-hidden="true" />
      </Button>
    </div>
  );
}

// ============================================================
// Main Component
// ============================================================

export function AuditWidget({
  siteId,
  lastAudit,
  runningAudit,
  recentIssues,
  onStartAudit,
  onViewAll,
  pollingInterval = 5000,
  onCancelAudit,
}: AuditWidgetProps) {
  // Calculate total issues for all-fixed state detection
  const totalIssues = lastAudit?.issues
    ? lastAudit.issues.critical + lastAudit.issues.warning + lastAudit.issues.notice
    : 0;

  // Determine current state
  const isRunning = !!runningAudit;
  const hasNoAudits = !lastAudit && !runningAudit;
  const isAllFixed = lastAudit && totalIssues === 0;
  const hasIssues = lastAudit && totalIssues > 0;

  return (
    <Card
      data-testid="audit-widget"
      className="widget-card border-slate-800 bg-slate-900/60"
      role="region"
      aria-label="Site Audit Widget"
    >
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Search className="h-5 w-5 text-slate-400" data-testid="search-icon" aria-hidden="true" />
            <span>Site Audit</span>
          </CardTitle>
          
          {/* Start New button for completed/has-issues state */}
          {(hasIssues || isAllFixed) && !isRunning && (
            <Button
              data-testid="start-audit-btn"
              variant="ghost"
              size="sm"
              onClick={onStartAudit}
              className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
            >
              Start New
            </Button>
          )}
        </div>
      </CardHeader>

      <CardContent>
        {/* State: Running */}
        {isRunning && (
          <RunningState runningAudit={runningAudit!} onCancelAudit={onCancelAudit} />
        )}

        {/* State: No Audits */}
        {hasNoAudits && (
          <NoAuditsState onStartAudit={onStartAudit} />
        )}

        {/* State: All Fixed */}
        {isAllFixed && !isRunning && (
          <AllFixedState lastAudit={lastAudit!} onStartAudit={onStartAudit} />
        )}

        {/* State: Completed with Issues */}
        {hasIssues && !isRunning && (
          <CompletedState
            lastAudit={lastAudit!}
            recentIssues={recentIssues}
            onStartAudit={onStartAudit}
            onViewAll={onViewAll}
          />
        )}
      </CardContent>
    </Card>
  );
}

export default AuditWidget;
