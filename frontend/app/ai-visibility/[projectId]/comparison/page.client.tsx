/**
 * TASK-34-17: Competitor Comparison Page (Client Component)
 * 
 * Client-side logic for the competitor comparison page.
 * Displays ranking tables, head-to-head comparisons, competitive advantages,
 * platform breakdowns, and competitor detail panels.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import { ChevronLeft, AlertCircle, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  ComparisonRankingTable,
  HeadToHeadComparison,
  CompetitiveAdvantages,
  PlatformBreakdown,
  CompetitorDetailPanel,
  ComparisonActions,
} from '@/components/ai-visibility';
import {
  useComparisonData,
  getMockComparisonData,
  type Competitor,
  type RankingEntry,
  type ComparisonData,
} from '@/lib/hooks/useComparisonData';
import { toast } from 'sonner';

// ============================================
// Types
// ============================================

interface ComparisonPageClientProps {
  className?: string;
}

// ============================================
// Constants
// ============================================

const MAX_COMPETITORS = 5;

// ============================================
// Component
// ============================================

export function ComparisonPageClient({ className }: ComparisonPageClientProps) {
  const params = useParams();
  const router = useRouter();
  const projectId = params.projectId as string;

  // Fetch comparison data
  const { data, isLoading, error, refetch } = useComparisonData(projectId);

  // Local state
  const [selectedCompetitor, setSelectedCompetitor] = React.useState<Competitor | null>(null);
  const [expandedCompetitorId, setExpandedCompetitorId] = React.useState<string | null>(null);
  const [isExporting, setIsExporting] = React.useState(false);

  // Get mock data for development/fallback
  const mockData = React.useMemo(() => getMockComparisonData(), []);

  // Use real data if available, otherwise use mock
  const comparisonData: ComparisonData = data ?? mockData;

  // Set initial selected competitor when data loads
  React.useEffect(() => {
    if (comparisonData.competitors.length > 0 && !selectedCompetitor) {
      setSelectedCompetitor(comparisonData.competitors[0]);
    }
  }, [comparisonData.competitors, selectedCompetitor]);

  // Handle row click in ranking table
  const handleRankingRowClick = (entry: RankingEntry) => {
    const competitor = comparisonData.competitors.find(c => c.name === entry.name);
    if (competitor) {
      setSelectedCompetitor(competitor);
    }
  };

  // Handle competitor detail toggle
  const handleToggleCompetitorDetail = (competitorId: string) => {
    setExpandedCompetitorId(prev => (prev === competitorId ? null : competitorId));
  };

  // Handle export to CSV
  const handleExport = async () => {
    setIsExporting(true);
    try {
      const csvData = generateCSV(comparisonData);
      downloadCSV(csvData, `comparison-${projectId}-${new Date().toISOString().split('T')[0]}.csv`);
    } finally {
      setIsExporting(false);
    }
  };

  // Handle add competitor
  const handleAddCompetitor = async (name: string, keywords: string[]) => {
    // TODO: Implement API call when TASK-34-15 is complete
    toast.success(`Competitor "${name}" would be added with keywords: ${keywords.join(', ')}`);
    await refetch();
  };

  // Handle remove competitor
  const handleRemoveCompetitor = async () => {
    // TODO: Implement API call when TASK-34-15 is complete
    if (selectedCompetitor) {
      toast.success(`Competitor "${selectedCompetitor.name}" would be removed`);
      await refetch();
    }
  };

  // Handle add competitor click (empty state)
  const handleAddCompetitorClick = () => {
    // This triggers the add competitor modal via ComparisonActions
    // We'll scroll to the actions section
    document.querySelector('[data-testid="comparison-actions"]')?.scrollIntoView({ behavior: 'smooth' });
  };

  // Loading state
  if (isLoading) {
    return (
      <main className="container mx-auto p-6" role="main">
        <div className="flex items-center justify-center min-h-[400px]">
          <div data-testid="loading-skeleton" className="animate-pulse space-y-4 w-full max-w-4xl">
            <div className="h-8 bg-slate-700 rounded w-1/4" />
            <div className="h-4 bg-slate-700 rounded w-1/3" />
            <div className="h-64 bg-slate-700 rounded" />
            <div className="h-64 bg-slate-700 rounded" />
          </div>
        </div>
      </main>
    );
  }

  // Error state
  if (error) {
    return (
      <main className="container mx-auto p-6" role="main">
        <div className="flex flex-col items-center justify-center min-h-[400px]">
          <AlertCircle className="h-12 w-12 text-red-400 mb-4" />
          <h2 className="text-xl font-semibold text-white mb-2">Failed to load comparison data</h2>
          <p className="text-slate-400 mb-4">{error.message}</p>
          <button
            onClick={() => refetch()}
            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>
      </main>
    );
  }

  // Empty state (no competitors)
  if (comparisonData.competitors.length === 0) {
    return (
      <main className="container mx-auto p-6" role="main">
        {/* Breadcrumb */}
        <nav aria-label="Breadcrumb" className="mb-6">
          <ol className="flex items-center gap-2 text-sm text-slate-400">
            <li>
              <button
                onClick={() => router.push('/ai-visibility')}
                className="hover:text-white transition-colors"
              >
                AI Visibility
              </button>
            </li>
            <li>/</li>
            <li>
              <button
                onClick={() => router.push(`/ai-visibility/${projectId}`)}
                className="hover:text-white transition-colors"
              >
                {comparisonData.projectName}
              </button>
            </li>
            <li>/</li>
            <li className="text-white">Comparison</li>
          </ol>
        </nav>

        {/* Page Header */}
        <header className="mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-white">Competitor Comparison</h1>
              <p className="text-slate-400 mt-1">
                Compare your brand visibility against competitors
              </p>
            </div>
          </div>
        </header>

        {/* Empty State */}
        <div className="rounded-lg bg-slate-800 p-12 text-center">
          <h2 className="text-xl font-semibold text-white mb-2">No competitors added yet</h2>
          <p className="text-slate-400 mb-6">
            Add competitors to start comparing your brand visibility
          </p>
          <button
            onClick={handleAddCompetitorClick}
            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
          >
            Add Competitor
          </button>
        </div>
      </main>
    );
  }

  return (
    <main className="container mx-auto p-6" role="main" aria-label="Comparison page">
      {/* Breadcrumb */}
      <nav aria-label="Breadcrumb" className="mb-6">
        <ol className="flex items-center gap-2 text-sm text-slate-400">
          <li>
            <button
              onClick={() => router.push('/ai-visibility')}
              className="hover:text-white transition-colors"
            >
              AI Visibility
            </button>
          </li>
          <li>/</li>
          <li>
            <button
              onClick={() => router.push(`/ai-visibility/${projectId}`)}
              className="hover:text-white transition-colors"
            >
              {comparisonData.projectName}
            </button>
          </li>
          <li>/</li>
          <li className="text-white">Comparison</li>
        </ol>
      </nav>

      {/* Page Header */}
      <header className="mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-white">Competitor Comparison</h1>
            <p className="text-slate-400 mt-1">
              Compare {comparisonData.brandName} against {comparisonData.competitors.length} competitor
              {comparisonData.competitors.length !== 1 ? 's' : ''}
            </p>
          </div>
          
          {/* Back Button */}
          <button
            onClick={() => router.push(`/ai-visibility/${projectId}`)}
            className="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
          >
            <ChevronLeft className="h-4 w-4" />
            Back to Dashboard
          </button>
        </div>
      </header>

      {/* Actions Bar */}
      <div className="mb-6">
        <ComparisonActions
          projectId={projectId}
          competitorCount={comparisonData.competitors.length}
          maxCompetitors={MAX_COMPETITORS}
          onExport={handleExport}
          onAddCompetitor={handleAddCompetitor}
          onRemoveCompetitor={handleRemoveCompetitor}
          isExporting={isExporting}
          isLoading={isLoading}
        />
      </div>

      {/* Main Content Grid */}
      <div className="space-y-6">
        {/* Row 1: Rankings and Head-to-Head */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Rankings Table */}
          <ComparisonRankingTable
            rankings={comparisonData.rankings.rankings}
            brandName={comparisonData.brandName}
            brandPosition={comparisonData.rankings.brand_position}
            onRowClick={handleRankingRowClick}
          />

          {/* Head-to-Head Comparison */}
          <HeadToHeadComparison
            brandName={comparisonData.brandName}
            competitors={comparisonData.competitors}
            selectedCompetitor={selectedCompetitor}
            headToHead={comparisonData.headToHead}
            onSelectCompetitor={setSelectedCompetitor}
            onAddCompetitor={handleAddCompetitorClick}
            isLoading={isLoading}
          />
        </div>

        {/* Row 2: Competitive Advantages */}
        <CompetitiveAdvantages
          brandName={comparisonData.brandName}
          competitorName={selectedCompetitor?.name ?? 'Competitor'}
          advantages={comparisonData.advantages}
        />

        {/* Row 3: Platform Breakdown */}
        <PlatformBreakdown
          brandName={comparisonData.brandName}
          platformBreakdown={comparisonData.platformBreakdown}
        />

        {/* Row 4: Competitor Detail Panels */}
        {comparisonData.competitors.length > 0 && (
          <section aria-label="Competitor Details">
            <h3 className="text-lg font-semibold text-white mb-4">Competitor Details</h3>
            <div className="space-y-4">
              {comparisonData.competitors.map(competitor => (
                <CompetitorDetailPanel
                  key={competitor.id}
                  competitorName={competitor.name}
                  details={comparisonData.competitorDetails[competitor.id] ?? null}
                  isExpanded={expandedCompetitorId === competitor.id}
                  onToggle={() => handleToggleCompetitorDetail(competitor.id)}
                />
              ))}
            </div>
          </section>
        )}
      </div>
    </main>
  );
}

// ============================================
// Helpers
// ============================================

function generateCSV(data: ComparisonData): string {
  const rows: string[][] = [];

  // Header row
  rows.push(['Position', 'Name', 'Mention Rate', 'Trend']);

  // Data rows
  data.rankings.rankings.forEach(entry => {
    rows.push([
      String(entry.position),
      entry.name,
      `${entry.rate.toFixed(1)}%`,
      entry.trend,
    ]);
  });

  // Convert to CSV string
  return rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
}

function downloadCSV(csv: string, filename: string): void {
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

export default ComparisonPageClient;
