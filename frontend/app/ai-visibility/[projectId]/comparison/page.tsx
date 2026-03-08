"use client";

/**
 * TASK-34-17: Competitor Comparison Page
 * 
 * Dedicated page for detailed competitor comparison analysis.
 * Shows rankings, head-to-head, advantages, and platform breakdown.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import { ChevronRight, AlertCircle, Loader2, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useComparisonData, getMockComparisonData } from '@/lib/hooks/useComparisonData';
import { ComparisonRankingTable } from '@/components/ai-visibility/ComparisonRankingTable';
import { HeadToHeadComparison } from '@/components/ai-visibility/HeadToHeadComparison';
import { CompetitiveAdvantages } from '@/components/ai-visibility/CompetitiveAdvantages';
import { PlatformBreakdown } from '@/components/ai-visibility/PlatformBreakdown';
import { CompetitorDetailPanel } from '@/components/ai-visibility/CompetitorDetailPanel';
import { ComparisonActions } from '@/components/ai-visibility/ComparisonActions';
import type { Competitor, RankingEntry } from '@/lib/hooks/useComparisonData';

// ============================================
// Metadata
// ============================================

export const metadata = {
  title: 'Competitor Comparison | AI Visibility | SEO Indicator',
  description: 'Compare your brand visibility against competitors across AI platforms',
};

// ============================================
// Skeleton Components
// ============================================

function LoadingSkeleton() {
  return (
    <div data-testid="loading-skeleton" className="space-y-6 animate-pulse" role="status" aria-live="polite">
      <span className="sr-only">Loading comparison data...</span>
      
      {/* Breadcrumb skeleton */}
      <div className="h-6 w-48 bg-slate-700 rounded" />
      
      {/* Title skeleton */}
      <div className="h-10 w-64 bg-slate-700 rounded" />
      
      {/* Ranking table skeleton */}
      <div className="h-64 bg-slate-700 rounded-lg" />
      
      {/* Cards skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="h-48 bg-slate-700 rounded-lg" />
        <div className="h-48 bg-slate-700 rounded-lg" />
      </div>
    </div>
  );
}

function ErrorState({ onRetry }: { onRetry: () => void }) {
  return (
    <div role="alert" className="rounded-lg bg-red-500/10 border border-red-500/20 p-6 text-center">
      <AlertCircle className="mx-auto h-12 w-12 text-red-400" />
      <h3 className="mt-4 text-lg font-medium text-red-400">Failed to Load Comparison</h3>
      <p className="mt-2 text-sm text-red-300/80">Please check your connection and try again.</p>
      <button
        data-testid="retry-btn"
        onClick={onRetry}
        className="mt-4 inline-flex items-center gap-2 rounded-lg bg-red-500/20 px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-500/30 transition-colors"
      >
        Retry
      </button>
    </div>
  );
}

function EmptyState({ onAddCompetitor }: { onAddCompetitor: () => void }) {
  return (
    <div className="rounded-lg bg-slate-800 p-12 text-center">
      <AlertCircle className="mx-auto h-12 w-12 text-slate-500" />
      <h3 className="mt-4 text-lg font-medium text-white">No Competitors Added</h3>
      <p className="mt-2 text-sm text-slate-400">
        Add competitors to see how your brand compares across AI platforms.
      </p>
      <button
        onClick={onAddCompetitor}
        className="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
      >
        <Plus className="h-4 w-4" />
        Add Competitor
      </button>
    </div>
  );
}

// ============================================
// Breadcrumb Component
// ============================================

function Breadcrumb({ projectId }: { projectId: string }) {
  return (
    <nav role="navigation" aria-label="breadcrumb" className="flex items-center gap-2 text-sm">
      <a
        href={`/ai-visibility/${projectId}`}
        className="text-slate-400 hover:text-white transition-colors"
      >
        Dashboard
      </a>
      <ChevronRight className="h-4 w-4 text-slate-500" />
      <span className="text-white">Comparison</span>
    </nav>
  );
}

// ============================================
// Main Page Component
// ============================================

export default function ComparisonPage() {
  const params = useParams();
  const router = useRouter();
  const projectId = params.projectId as string;

  const [selectedCompetitor, setSelectedCompetitor] = React.useState<Competitor | null>(null);
  const [expandedPanel, setExpandedPanel] = React.useState<string | null>(null);
  const [isExporting, setIsExporting] = React.useState(false);

  const { data, isLoading, error, refetch } = useComparisonData(projectId);

  // Set first competitor as selected by default
  React.useEffect(() => {
    if (data?.competitors?.length && !selectedCompetitor) {
      setSelectedCompetitor(data.competitors[0]);
    }
  }, [data?.competitors, selectedCompetitor]);

  // Handle competitor row click
  const handleRowClick = (entry: RankingEntry) => {
    const competitor = data?.competitors.find(c => c.name === entry.name);
    if (competitor) {
      setSelectedCompetitor(competitor);
      setExpandedPanel(competitor.id);
    }
  };

  // Handle export
  const handleExport = async () => {
    setIsExporting(true);
    try {
      // TODO: Implement actual export logic
      const response = await fetch(`/api/v1/ai-visibility/projects/${projectId}/comparison/export`);
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `comparison-${projectId}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      }
    } finally {
      setIsExporting(false);
    }
  };

  // Handle add competitor
  const handleAddCompetitor = async (name: string, keywords: string[]) => {
    const response = await fetch(`/api/v1/ai-visibility/projects/${projectId}/competitors`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, keywords }),
    });
    if (!response.ok) throw new Error('Failed to add competitor');
    await refetch();
  };

  // Handle remove competitor
  const handleRemoveCompetitor = async () => {
    if (!selectedCompetitor) return;
    const response = await fetch(
      `/api/v1/ai-visibility/projects/${projectId}/competitors/${selectedCompetitor.id}`,
      { method: 'DELETE' }
    );
    if (!response.ok) throw new Error('Failed to remove competitor');
    setSelectedCompetitor(null);
    await refetch();
  };

  // Loading state
  if (isLoading) {
    return (
      <main data-testid="comparison-page" className="p-6">
        <LoadingSkeleton />
      </main>
    );
  }

  // Error state
  if (error) {
    return (
      <main data-testid="comparison-page" className="p-6 max-w-lg mx-auto">
        <ErrorState onRetry={refetch} />
      </main>
    );
  }

  // No data state
  if (!data) {
    return (
      <main data-testid="comparison-page" className="p-6 max-w-lg mx-auto">
        <ErrorState onRetry={refetch} />
      </main>
    );
  }

  // Empty state (no competitors)
  if (data.competitors.length === 0) {
    return (
      <main data-testid="comparison-page" className="p-6">
        <Breadcrumb projectId={projectId} />
        <div className="mt-6">
          <h1 className="text-2xl font-bold text-white mb-4">Competitor Comparison</h1>
          <EmptyState onAddCompetitor={() => {}} />
        </div>
      </main>
    );
  }

  // Get selected competitor details
  const selectedCompetitorDetails = selectedCompetitor
    ? data.competitorDetails[selectedCompetitor.id]
    : null;

  // Determine page layout classes based on viewport
  const layoutClasses = 'flex flex-col md:grid-cols-2 lg:grid-cols-4 gap-6';

  return (
    <main
      data-testid="comparison-page"
      className={cn('p-6 space-y-6', layoutClasses)}
    >
      {/* Breadcrumb */}
      <div className="col-span-full">
        <Breadcrumb projectId={projectId} />
      </div>

      {/* Page Header */}
      <div className="col-span-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 data-testid="page-title" className="text-2xl font-bold text-white">
            Competitor Comparison
          </h1>
          <p className="text-sm text-slate-400 mt-1">
            Compare {data.brandName} against {data.competitors.length} competitors
          </p>
        </div>

        {/* Actions */}
        <ComparisonActions
          projectId={projectId}
          competitorCount={data.competitors.length}
          onExport={handleExport}
          onAddCompetitor={handleAddCompetitor}
          onRemoveCompetitor={handleRemoveCompetitor}
          isExporting={isExporting}
        />
      </div>

      {/* Ranking Table */}
      <div className="col-span-full">
        <ComparisonRankingTable
          rankings={data.rankings.rankings}
          brandName={data.brandName}
          brandPosition={data.rankings.brand_position}
          onRowClick={handleRowClick}
        />
      </div>

      {/* Head-to-Head Comparison */}
      <div className="col-span-full lg:col-span-2">
        <HeadToHeadComparison
          brandName={data.brandName}
          competitors={data.competitors}
          selectedCompetitor={selectedCompetitor}
          headToHead={data.headToHead}
          onSelectCompetitor={setSelectedCompetitor}
        />
      </div>

      {/* Competitive Advantages */}
      <div className="col-span-full lg:col-span-2">
        <CompetitiveAdvantages
          brandName={data.brandName}
          competitorName={selectedCompetitor?.name || 'Competitor'}
          advantages={data.advantages}
        />
      </div>

      {/* Platform Breakdown */}
      <div className="col-span-full">
        <PlatformBreakdown
          brandName={data.brandName}
          platformBreakdown={data.platformBreakdown}
        />
      </div>

      {/* Competitor Detail Panels */}
      {selectedCompetitor && (
        <div className="col-span-full">
          <h3 className="text-lg font-semibold text-white mb-4">Competitor Details</h3>
          <CompetitorDetailPanel
            competitorName={selectedCompetitor.name}
            details={selectedCompetitorDetails || null}
            isExpanded={expandedPanel === selectedCompetitor.id}
            onToggle={() => setExpandedPanel(
              expandedPanel === selectedCompetitor.id ? null : selectedCompetitor.id
            )}
          />
        </div>
      )}
    </main>
  );
}
