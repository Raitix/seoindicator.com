/**
 * TASK-34-10: AI Visibility Dashboard Page
 * 
 * Main dashboard page showing analytics, charts, and recent activity
 * for AI Visibility tracking.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

'use client';

// Required for static export with dynamic routes
// Returns empty array since this is a client-rendered page
export function generateStaticParams() {
  return [];
}

import * as React from 'react';
import { useParams, useRouter, notFound } from 'next/navigation';
import {
  Settings,
  RefreshCw,
  Download,
  AlertCircle,
  Loader2,
  TrendingUp,
  TrendingDown,
  Minus,
  ChevronRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  useAIVisibilityDashboard,
  getMockDashboardData,
} from '@/lib/hooks/useAIVisibilityDashboard';
import {
  MetricCard,
  RecentMentionsList,
  MentionRateChart,
  CompetitorComparisonChart,
  QueryCategoriesChart,
} from '@/components/ai-visibility';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import type { AIVisibilityDashboardData } from '@/lib/types/ai-visibility';

// ============================================
// Skeleton Components
// ============================================

function MetricCardSkeleton() {
  return (
    <div
      data-testid="skeleton-card"
      data-loading="true"
      className="rounded-lg bg-slate-800 p-6 animate-pulse"
    >
      <div className="h-4 w-24 bg-slate-700 rounded" />
      <div className="mt-4 h-8 w-32 bg-slate-700 rounded" />
      <div className="mt-2 h-4 w-16 bg-slate-700 rounded" />
    </div>
  );
}

function ChartSkeleton() {
  return (
    <div
      data-testid="chart-loading"
      className="rounded-lg bg-slate-800 p-6 animate-pulse"
    >
      <div className="flex items-center justify-center h-[250px]">
        <Loader2 className="h-8 w-8 text-slate-500 animate-spin" data-testid="loader-icon" />
      </div>
    </div>
  );
}

// ============================================
// Error State Component
// ============================================

interface ErrorStateProps {
  title: string;
  message: string;
  onRetry?: () => void;
  showRetry?: boolean;
}

function ErrorState({ title, message, onRetry, showRetry = true }: ErrorStateProps) {
  return (
    <div
      role="alert"
      data-testid="error-boundary"
      className="rounded-lg bg-red-500/10 border border-red-500/20 p-6 text-center"
    >
      <AlertCircle className="mx-auto h-12 w-12 text-red-400" data-testid="alert-icon" />
      <h3 className="mt-4 text-lg font-medium text-red-400">{title}</h3>
      <p className="mt-2 text-sm text-red-300/80">{message}</p>
      {showRetry && onRetry && (
        <button
          data-testid="retry-btn"
          onClick={onRetry}
          className="mt-4 inline-flex items-center gap-2 rounded-lg bg-red-500/20 px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-500/30 transition-colors"
        >
          <RefreshCw className="h-4 w-4" />
          Retry
        </button>
      )}
    </div>
  );
}

// ============================================
// NotFound State Component
// ============================================

function NotFoundState() {
  return (
    <div
      data-testid="project-not-found"
      className="flex flex-col items-center justify-center min-h-[400px] text-center"
    >
      <AlertCircle className="h-16 w-16 text-slate-500" />
      <h1 className="mt-6 text-2xl font-bold text-white">Project Not Found</h1>
      <p className="mt-2 text-slate-400">
        The project you're looking for doesn't exist or you don't have access.
      </p>
      <a
        href="/ai-visibility"
        className="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
      >
        Back to Projects
      </a>
    </div>
  );
}

// ============================================
// Quick Actions Component
// ============================================

interface QuickActionsProps {
  projectId: string | number;
  canRunQuery: boolean;
  nextQueryAt: string | null;
  hasExportData: boolean;
  onRunQuery: () => void;
  isRunningQuery: boolean;
}

function QuickActions({
  projectId,
  canRunQuery,
  nextQueryAt,
  hasExportData,
  onRunQuery,
  isRunningQuery,
}: QuickActionsProps) {
  return (
    <div data-testid="quick-actions" className="flex flex-wrap items-center gap-3">
      {/* Run Query Button */}
      <button
        data-testid="run-query-btn"
        onClick={onRunQuery}
        disabled={!canRunQuery || isRunningQuery}
        className={cn(
          'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
          canRunQuery && !isRunningQuery
            ? 'bg-blue-600 text-white hover:bg-blue-700'
            : 'bg-slate-700 text-slate-400 cursor-not-allowed'
        )}
      >
        {isRunningQuery ? (
          <>
            <Loader2 className="h-4 w-4 animate-spin" data-testid="loader-icon" />
            Running...
          </>
        ) : (
          <>
            <RefreshCw className="h-4 w-4" data-testid="refresh-icon" />
            Run Query Now
          </>
        )}
      </button>

      {/* Export Data Link */}
      {hasExportData ? (
        <a
          data-testid="export-link"
          href={`/ai-visibility/${projectId}/export`}
          className="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition-colors"
        >
          <Download className="h-4 w-4" data-testid="download-icon" />
          Export Data
        </a>
      ) : (
        <button
          data-testid="export-btn"
          disabled
          className="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-500 cursor-not-allowed"
        >
          <Download className="h-4 w-4" />
          Export Data
        </button>
      )}

      {/* Settings Link */}
      <a
        data-testid="settings-link"
        href={`/ai-visibility/${projectId}/settings`}
        className="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition-colors"
      >
        <Settings className="h-4 w-4" data-testid="settings-icon" />
        Settings
      </a>
    </div>
  );
}

// ============================================
// Dashboard Content Component
// ============================================

interface DashboardContentProps {
  data: AIVisibilityDashboardData;
  projectId: string | number;
  onRunQuery: () => void;
  isRunningQuery: boolean;
}

function DashboardContent({ data, projectId, onRunQuery, isRunningQuery }: DashboardContentProps) {
  const router = useRouter();

  const handleMentionClick = (mention: { id: string }) => {
    router.push(`/ai-visibility/${projectId}/queries/${mention.id}`);
  };

  const handleCompetitorClick = (item: { name: string }) => {
    router.push(`/ai-visibility/${projectId}/competitors?filter=${encodeURIComponent(item.name)}`);
  };

  return (
    <div data-testid="dashboard" className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1
            data-testid="page-title"
            className="text-2xl font-bold text-white truncate max-w-md"
            title={data.project.brandName}
          >
            {data.project.brandName} AI Visibility
          </h1>
          <p className="mt-1 text-sm text-slate-400">
            Track how AI assistants mention your brand
          </p>
        </div>

        {/* Quick Actions */}
        <QuickActions
          projectId={projectId}
          canRunQuery={data.quickActions.canRunQuery}
          nextQueryAt={data.quickActions.nextQueryAt}
          hasExportData={data.quickActions.hasExportData}
          onRunQuery={onRunQuery}
          isRunningQuery={isRunningQuery}
        />
      </div>

      {/* Metric Cards Row */}
      <div
        data-testid="metric-cards-row"
        className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
      >
        <MetricCard
          data-testid="metric-card-mention-rate"
          title="Mention Rate"
          value={data.metrics.mentionRate.value}
          change={data.metrics.mentionRate.change}
          trend={data.metrics.mentionRate.trend}
          format="percentage"
          tooltip="Percentage of AI queries that mention your brand"
        />
        <MetricCard
          data-testid="metric-card-share-of-voice"
          title="Share of Voice"
          value={data.metrics.shareOfVoice.value}
          change={data.metrics.shareOfVoice.change}
          trend={data.metrics.shareOfVoice.trend}
          format="percentage"
          tooltip="Percentage of brand mentions vs competitors"
        />
        <MetricCard
          data-testid="metric-card-sentiment"
          title="Sentiment Score"
          value={data.metrics.sentimentScore.value}
          change={data.metrics.sentimentScore.change}
          trend={data.metrics.sentimentScore.trend}
          format="decimal"
          sentiment={data.metrics.sentimentScore.label.toLowerCase() as 'positive' | 'negative' | 'neutral' | 'mixed'}
          label={data.metrics.sentimentScore.label}
          tooltip="Sentiment score from -1.00 (negative) to +1.00 (positive)"
        />
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Mention Rate Over Time */}
        <Card data-testid="section-charts">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-blue-400" />
              Mention Rate Over Time
            </CardTitle>
          </CardHeader>
          <CardContent>
            <MentionRateChart data={data.mentionRateOverTime} />
          </CardContent>
        </Card>

        {/* Competitor Comparison */}
        <Card data-testid="section-competitors">
          <CardHeader>
            <CardTitle>Competitor Comparison</CardTitle>
          </CardHeader>
          <CardContent>
            <CompetitorComparisonChart
              data={data.competitorComparison}
              onBarClick={handleCompetitorClick}
            />
          </CardContent>
        </Card>
      </div>

      {/* Bottom Row */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Recent Mentions */}
        <div data-testid="section-mentions" className="lg:col-span-2">
          <Card>
            <CardContent className="pt-6">
              <RecentMentionsList
                mentions={data.recentMentions}
                maxVisible={10}
                viewAllHref={`/ai-visibility/${projectId}/queries`}
                onMentionClick={handleMentionClick}
              />
            </CardContent>
          </Card>
        </div>

        {/* Query Categories */}
        <div data-testid="section-categories">
          <Card>
            <CardHeader>
              <CardTitle>Query Categories</CardTitle>
            </CardHeader>
            <CardContent>
              <QueryCategoriesChart data={data.queryCategories} />
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}

// ============================================
// Main Page Component
// ============================================

export default function AIVisibilityDashboardPage() {
  const params = useParams();
  const router = useRouter();
  const projectId = params.projectId as string;

  const [isRunningQuery, setIsRunningQuery] = React.useState(false);

  const {
    data,
    isLoading,
    error,
    refetch,
  } = useAIVisibilityDashboard(projectId);

  // Handle run query action
  const handleRunQuery = React.useCallback(async () => {
    if (isRunningQuery) return;

    setIsRunningQuery(true);
    try {
      // Call the query API
      const response = await fetch(`/api/v1/ai-visibility/projects/${projectId}/query`, {
        method: 'POST',
      });

      if (response.ok) {
        // Refetch dashboard data after query completes
        await refetch();
      }
    } catch (err) {
      console.error('Failed to run query:', err);
    } finally {
      setIsRunningQuery(false);
    }
  }, [projectId, isRunningQuery, refetch]);

  // Loading state
  if (isLoading) {
    return (
      <main data-testid="dashboard-main" className="space-y-6 p-6">
        <div role="status" aria-live="polite" data-testid="loading-status" className="text-center">
          <Loader2 className="mx-auto h-8 w-8 text-blue-400 animate-spin" data-testid="loader-icon" />
          <p className="mt-2 text-sm text-slate-400">Loading dashboard...</p>
        </div>

        {/* Skeleton Cards */}
        <div
          data-testid="metric-cards-skeleton"
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <MetricCardSkeleton />
          <MetricCardSkeleton />
          <MetricCardSkeleton />
        </div>

        {/* Skeleton Charts */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <ChartSkeleton />
          <ChartSkeleton />
        </div>
      </main>
    );
  }

  // Error state (not found)
  if (error?.message?.includes('404')) {
    return (
      <main data-testid="dashboard-main" className="p-6">
        <NotFoundState />
      </main>
    );
  }

  // Error state (network/server error)
  if (error) {
    return (
      <main data-testid="dashboard-main" className="p-6">
        <div data-testid="network-error" role="alert" className="max-w-lg mx-auto">
          <ErrorState
            title="Unable to Load Dashboard"
            message="Please check your connection and try again."
            onRetry={refetch}
          />
        </div>
      </main>
    );
  }

  // No data state
  if (!data) {
    return (
      <main data-testid="dashboard-main" className="p-6">
        <div data-testid="no-data-fallback" className="max-w-lg mx-auto">
          <ErrorState
            title="No Data Available"
            message="Run a query to start tracking your AI visibility."
            onRetry={handleRunQuery}
          />
        </div>
      </main>
    );
  }

  // Success state with data
  return (
    <main data-testid="dashboard-main" className="p-6">
      <DashboardContent
        data={data}
        projectId={projectId}
        onRunQuery={handleRunQuery}
        isRunningQuery={isRunningQuery}
      />
    </main>
  );
}
