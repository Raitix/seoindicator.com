'use client';

import React, { useState, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  RefreshCw,
  Download,
  Settings,
  ExternalLink,
  Link2,
  AlertCircle,
} from 'lucide-react';

import BacklinkOverviewCards from './BacklinkOverviewCards';
import ReferringDomainsTable from './ReferringDomainsTable';
import AnchorTextDistribution from './AnchorTextDistribution';
import BacklinkHistoryChart from './BacklinkHistoryChart';
import type {
  BacklinkSummary,
  ReferringDomain,
  AnchorText,
  DateRange,
  DomainsSortField,
  SortOrder,
} from './types';
import {
  useBacklinksOverview,
  useReferringDomains,
  useAnchorTexts,
  syncBacklinks,
  revalidateBacklinks,
} from '@/lib/api/backlinks';

// ==================== Types ====================

interface BacklinksDashboardProps {
  projectId: number;
  className?: string;
  showHistory?: boolean;
  showAnchors?: boolean;
  showDomains?: boolean;
}

// ==================== Error Boundary ====================

interface ErrorBoundaryState {
  hasError: boolean;
  error?: Error;
}

class BacklinksErrorBoundary extends React.Component<
  { children: React.ReactNode; fallback?: React.ReactNode },
  ErrorBoundaryState
> {
  constructor(props: { children: React.ReactNode; fallback?: React.ReactNode }) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  render() {
    if (this.state.hasError) {
      return (
        this.props.fallback ?? (
          <Card className="border-destructive">
            <CardContent className="py-8 text-center">
              <AlertCircle className="h-12 w-12 mx-auto text-destructive mb-4" />
              <p className="font-medium text-destructive">Something went wrong</p>
              <p className="text-sm text-muted-foreground mt-1">
                {this.state.error?.message ?? 'An unexpected error occurred'}
              </p>
              <Button
                variant="outline"
                size="sm"
                className="mt-4"
                onClick={() => this.setState({ hasError: false })}
              >
                Try Again
              </Button>
            </CardContent>
          </Card>
        )
      );
    }

    return this.props.children;
  }
}

// ==================== Loading Skeleton ====================

function DashboardSkeleton() {
  return (
    <div className="space-y-6 animate-pulse" data-testid="dashboard-skeleton">
      {/* Overview cards skeleton */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-32 bg-muted rounded-lg" />
        ))}
      </div>

      {/* Charts skeleton */}
      <div className="grid gap-6 md:grid-cols-2">
        <div className="h-80 bg-muted rounded-lg" />
        <div className="h-80 bg-muted rounded-lg" />
      </div>

      {/* Table skeleton */}
      <div className="h-96 bg-muted rounded-lg" />
    </div>
  );
}

// ==================== Empty State ====================

function DashboardEmptyState({ onSync }: { onSync?: () => void }) {
  return (
    <Card className="col-span-full" data-testid="dashboard-empty-state">
      <CardContent className="py-16 text-center">
        <Link2 className="h-16 w-16 mx-auto text-muted-foreground opacity-50 mb-4" />
        <h3 className="text-xl font-semibold mb-2">No Backlink Data Yet</h3>
        <p className="text-muted-foreground mb-6 max-w-md mx-auto">
          Run a backlink analysis to discover who's linking to your site and track your
          backlink profile over time.
        </p>
        <Button onClick={onSync} size="lg">
          <RefreshCw className="mr-2 h-4 w-4" />
          Run Backlink Analysis
        </Button>
      </CardContent>
    </Card>
  );
}

// ==================== Quick Actions Component ====================

interface QuickActionsProps {
  projectId: number;
  onSync: () => Promise<void>;
  isSyncing: boolean;
}

const QuickActions: React.FC<QuickActionsProps> = ({ projectId, onSync, isSyncing }) => {
  return (
    <div className="flex items-center gap-2">
      <Button
        variant="outline"
        size="sm"
        onClick={onSync}
        disabled={isSyncing}
        data-testid="sync-button"
      >
        <RefreshCw className={`h-4 w-4 mr-2 ${isSyncing ? 'animate-spin' : ''}`} />
        {isSyncing ? 'Syncing...' : 'Sync Now'}
      </Button>
      <Button variant="outline" size="sm" asChild>
        <a
          href={`/api/v1/projects/${projectId}/backlinks/export`}
          download
          data-testid="export-button"
        >
          <Download className="h-4 w-4 mr-2" />
          Export
        </a>
      </Button>
      <Button variant="ghost" size="sm" asChild>
        <a href={`/projects/${projectId}/settings/backlinks`} data-testid="settings-button">
          <Settings className="h-4 w-4" />
        </a>
      </Button>
    </div>
  );
};

// ==================== Main Component ====================

const BacklinksDashboard: React.FC<BacklinksDashboardProps> = ({
  projectId,
  className,
  showHistory = true,
  showAnchors = true,
  showDomains = true,
}) => {
  // State
  const [activeTab, setActiveTab] = useState('overview');
  const [domainsPage, setDomainsPage] = useState(1);
  const [domainsSort, setDomainsSort] = useState<{
    field: DomainsSortField;
    order: SortOrder;
  }>({
    field: 'backlinks_count',
    order: 'desc',
  });
  const [dateRange, setDateRange] = useState<DateRange | undefined>();
  const [anchorChartType, setAnchorChartType] = useState<'pie' | 'bar'>('bar');
  const [isSyncing, setIsSyncing] = useState(false);

  // Data hooks
  const {
    summary,
    recentBacklinks,
    topReferringDomains,
    isLoading: overviewLoading,
    error: overviewError,
    mutate: mutateOverview,
  } = useBacklinksOverview(projectId);

  const {
    domains,
    meta: domainsMeta,
    isLoading: domainsLoading,
    error: domainsError,
    mutate: mutateDomains,
  } = useReferringDomains(projectId, {
    page: domainsPage,
    sortField: domainsSort.field,
    sortOrder: domainsSort.order,
  });

  const {
    anchors,
    isLoading: anchorsLoading,
    error: anchorsError,
    mutate: mutateAnchors,
  } = useAnchorTexts(projectId);

  // Handlers
  const handleSync = useCallback(async () => {
    setIsSyncing(true);
    try {
      await syncBacklinks(projectId);
      // Revalidate all data
      mutateOverview();
      mutateDomains();
      mutateAnchors();
    } catch (error) {
      console.error('Failed to sync backlinks:', error);
    } finally {
      setIsSyncing(false);
    }
  }, [projectId, mutateOverview, mutateDomains, mutateAnchors]);

  const handleSort = useCallback(
    (field: DomainsSortField, order: SortOrder) => {
      setDomainsSort({ field, order });
      setDomainsPage(1);
    },
    []
  );

  const handlePageChange = useCallback((page: number) => {
    setDomainsPage(page);
  }, []);

  const handleDateRangeChange = useCallback((range: DateRange) => {
    setDateRange(range);
  }, []);

  // Combined loading state
  const isLoading = overviewLoading && !summary;

  // Error state
  if (overviewError) {
    return (
      <Card className={className} data-testid="dashboard-error">
        <CardContent className="py-8 text-center">
          <AlertCircle className="h-12 w-12 mx-auto text-destructive mb-4" />
          <p className="font-medium text-destructive">Failed to load backlinks data</p>
          <p className="text-sm text-muted-foreground mt-1">
            {overviewError instanceof Error ? overviewError.message : 'Unknown error'}
          </p>
          <Button
            variant="outline"
            size="sm"
            className="mt-4"
            onClick={() => mutateOverview()}
          >
            <RefreshCw className="h-4 w-4 mr-2" />
            Retry
          </Button>
        </CardContent>
      </Card>
    );
  }

  // Loading state
  if (isLoading) {
    return (
      <div className={className}>
        <DashboardSkeleton />
      </div>
    );
  }

  // Empty state
  if (!summary && !overviewLoading) {
    return (
      <div className={className}>
        <DashboardEmptyState onSync={handleSync} />
      </div>
    );
  }

  return (
    <div className={className} data-testid="backlinks-dashboard">
      {/* Header */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold">Backlinks Dashboard</h1>
          <p className="text-muted-foreground">
            Monitor your backlink profile and referring domains
          </p>
        </div>
        <QuickActions
          projectId={projectId}
          onSync={handleSync}
          isSyncing={isSyncing}
        />
      </div>

      {/* Overview Cards */}
      <BacklinksErrorBoundary>
        <BacklinkOverviewCards
          summary={summary}
          loading={overviewLoading}
          className="mb-6"
        />
      </BacklinksErrorBoundary>

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          {showDomains && <TabsTrigger value="domains">Domains</TabsTrigger>}
          {showAnchors && <TabsTrigger value="anchors">Anchors</TabsTrigger>}
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-6">
          <div className="grid gap-6 lg:grid-cols-2">
            {/* History Chart */}
            {showHistory && (
              <BacklinksErrorBoundary>
                <BacklinkHistoryChart
                  projectId={projectId}
                  dateRange={dateRange}
                  onDateRangeChange={handleDateRangeChange}
                />
              </BacklinksErrorBoundary>
            )}

            {/* Anchor Distribution */}
            {showAnchors && (
              <BacklinksErrorBoundary>
                <AnchorTextDistribution
                  anchors={anchors}
                  loading={anchorsLoading}
                  chartType={anchorChartType}
                  onChartTypeChange={setAnchorChartType}
                  maxVisible={10}
                />
              </BacklinksErrorBoundary>
            )}
          </div>

          {/* Top Referring Domains Preview */}
          {showDomains && topReferringDomains.length > 0 && (
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-lg">Top Referring Domains</CardTitle>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setActiveTab('domains')}
                  >
                    View All
                    <ExternalLink className="h-4 w-4 ml-2" />
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {topReferringDomains.slice(0, 5).map((domain, index) => (
                    <div
                      key={`${domain.domain}-${index}`}
                      className="flex items-center justify-between py-2 border-b last:border-0"
                    >
                      <span className="font-medium truncate flex-1">
                        {domain.domain}
                      </span>
                      <span className="text-sm text-muted-foreground ml-2">
                        {domain.backlinks_count} backlinks
                      </span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Domains Tab */}
        {showDomains && (
          <TabsContent value="domains">
            <BacklinksErrorBoundary>
              <ReferringDomainsTable
                projectId={projectId}
                domains={domains}
                meta={domainsMeta}
                loading={domainsLoading}
                onSort={handleSort}
                onPageChange={handlePageChange}
                sortField={domainsSort.field}
                sortOrder={domainsSort.order}
                currentPage={domainsPage}
              />
            </BacklinksErrorBoundary>
          </TabsContent>
        )}

        {/* Anchors Tab */}
        {showAnchors && (
          <TabsContent value="anchors">
            <BacklinksErrorBoundary>
              <AnchorTextDistribution
                anchors={anchors}
                loading={anchorsLoading}
                chartType={anchorChartType}
                onChartTypeChange={setAnchorChartType}
                maxVisible={20}
              />
            </BacklinksErrorBoundary>
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
};

export default BacklinksDashboard;

// Named export for flexibility
export { BacklinksDashboard };
