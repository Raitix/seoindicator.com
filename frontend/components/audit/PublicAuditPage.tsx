/**
 * Public Audit Page Component
 * Main component for displaying public SEO audit results
 * @module components/audit/PublicAuditPage
 */

"use client";

import * as React from 'react';
import { Calendar, ExternalLink, AlertCircle, RefreshCw, Home } from 'lucide-react';

import { cn } from '@/lib/utils';
import { TopIssues, Issue } from './TopIssues';
import { CategoryBreakdown, CategoryScore } from './CategoryBreakdown';
import { PublicAuditCTA } from './PublicAuditCTA';

// ============================================================================
// Types
// ============================================================================

/**
 * Public audit data structure
 */
export interface PublicAuditData {
  siteUrl: string;
  auditedAt: string;
  overallScore: number;
  categoryScores: CategoryScoreData[];
  topIssues: IssueData[];
  recommendations?: string[];
  isPartial: boolean;
  isPublic?: boolean;
  hash?: string;
}

/**
 * Category score data from API
 */
export interface CategoryScoreData {
  name: string;
  score: number;
  maxScore?: number;
  issues?: number;
  issueCount?: number;
  criticalCount?: number;
  warningCount?: number;
}

/**
 * Issue data from API
 */
export interface IssueData {
  id: string;
  title: string;
  severity: 'critical' | 'warning' | 'info';
  description?: string;
  category?: string;
  impact?: string;
}

/**
 * Props for the PublicAuditPage component (new interface)
 */
export interface PublicAuditPageProps {
  hash: string;
  initialData?: PublicAuditData | null;
  className?: string;
}

/**
 * Legacy props for backward compatibility
 */
export interface LegacyPublicAuditPageProps {
  audit: PublicAuditData;
  hash?: string;
  className?: string;
}

// ============================================================================
// Utility Functions
// ============================================================================

/**
 * Get score color based on value
 */
function getScoreColor(score: number): string {
  if (score >= 80) return 'text-green-500';
  if (score >= 60) return 'text-blue-500';
  if (score >= 40) return 'text-yellow-500';
  if (score >= 20) return 'text-orange-500';
  return 'text-red-500';
}

/**
 * Get score background color
 */
function getScoreBgColor(score: number): string {
  if (score >= 80) return 'bg-green-500';
  if (score >= 60) return 'bg-blue-500';
  if (score >= 40) return 'bg-yellow-500';
  if (score >= 20) return 'bg-orange-500';
  return 'bg-red-500';
}

/**
 * Get status text based on score
 */
function getScoreStatus(score: number): string {
  if (score >= 90) return 'Excellent';
  if (score >= 70) return 'Good';
  if (score >= 50) return 'Needs Work';
  if (score >= 30) return 'Poor';
  return 'Critical Issues';
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  if (!dateString) return 'Unknown date';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  } catch {
    return 'Unknown date';
  }
}

/**
 * Transform category scores to component format
 */
function transformCategoryScores(scores: CategoryScoreData[]): CategoryScore[] {
  if (!scores || !Array.isArray(scores)) return [];
  return scores.map((score) => ({
    name: score.name,
    score: score.score,
    issueCount: score.issueCount || score.issues || 0,
    criticalCount: score.criticalCount || 0,
    warningCount: score.warningCount || 0,
  }));
}

/**
 * Transform issues to component format
 */
function transformIssues(issues: IssueData[]): Issue[] {
  if (!issues || !Array.isArray(issues)) return [];
  return issues.map((issue) => ({
    id: issue.id,
    title: issue.title,
    description: issue.description || 'No description available',
    severity: issue.severity,
    category: issue.category || 'General',
    impact: issue.impact || 'Impact not specified',
  }));
}

// ============================================================================
// Loading State Component
// ============================================================================

function LoadingState() {
  return (
    <div
      data-testid="loading-state"
      role="status"
      aria-live="polite"
      className="flex min-h-screen items-center justify-center bg-slate-950"
    >
      <div className="flex flex-col items-center gap-4">
        <div
          data-testid="loading-skeleton"
          className="h-12 w-12 animate-spin rounded-full border-4 border-emerald-500 border-t-transparent"
        />
        <p className="text-slate-400">Loading audit data...</p>
      </div>
    </div>
  );
}

// ============================================================================
// Error State Component
// ============================================================================

interface ErrorStateProps {
  message: string;
  onRetry?: () => void;
}

function ErrorState({ message, onRetry }: ErrorStateProps) {
  return (
    <div
      data-testid="error-state"
      role="alert"
      aria-live="assertive"
      className="flex min-h-screen items-center justify-center bg-slate-950 px-4"
    >
      <div className="max-w-md text-center">
        <span data-testid="error-icon" className="mb-4 inline-block text-6xl text-red-500">
          <AlertCircle className="h-16 w-16 mx-auto" aria-hidden="true" />
        </span>
        <h1 className="mb-2 text-2xl font-bold text-white">Error Loading Audit</h1>
        <p data-testid="error-message" className="text-slate-400">{message}</p>
        {onRetry && (
          <button
            data-testid="retry-button"
            onClick={onRetry}
            aria-label="Retry loading audit"
            className="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 transition-colors"
          >
            <RefreshCw className="h-4 w-4" aria-hidden="true" />
            Retry
          </button>
        )}
      </div>
    </div>
  );
}

// ============================================================================
// Not Found State Component
// ============================================================================

function NotFoundState() {
  return (
    <div
      data-testid="not-found-state"
      className="flex min-h-screen items-center justify-center bg-slate-950 px-4"
    >
      <div className="max-w-md text-center">
        <h1 className="mb-4 text-4xl font-bold text-white">Audit Not Found</h1>
        <p className="mb-8 text-lg text-slate-400">
          The requested audit could not be found or is not publicly available.
        </p>
        <a
          href="/"
          data-testid="home-link"
          className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-white hover:bg-emerald-700 transition-colors"
        >
          <Home className="h-4 w-4" aria-hidden="true" />
          Go to Homepage
        </a>
      </div>
    </div>
  );
}

// ============================================================================
// Main Component
// ============================================================================

/**
 * PublicAuditPage Component
 * Renders the full public audit page with all sections
 * @param props - Component props
 * @returns JSX element
 */
export function PublicAuditPage({
  hash,
  initialData,
  className,
}: PublicAuditPageProps) {
  // State management
  const [data, setData] = React.useState<PublicAuditData | null>(initialData || null);
  const [loading, setLoading] = React.useState(!initialData);
  const [error, setError] = React.useState<string | null>(null);

  // Fetch data on mount if no initialData
  React.useEffect(() => {
    if (initialData) return;

    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await fetch(`/api/v1/public-audit/${hash}`);
        const result = await response.json();

        // Handle 404 specifically (NOT_FOUND or NOT_PUBLIC codes)
        if (result.code === 'NOT_FOUND' || result.code === 'NOT_PUBLIC') {
          setData(null);
          return;
        }

        if (!response.ok || !result.success) {
          setError(result.error || 'Failed to load audit');
          return;
        }

        setData(result.data);
      } catch (err) {
        setError('Failed to load audit data');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [hash, initialData]);

  // Handle retry
  const handleRetry = React.useCallback(() => {
    window.location.reload();
  }, []);

  // Loading state
  if (loading) {
    return <LoadingState />;
  }

  // Error state
  if (error) {
    return <ErrorState message={error} onRetry={handleRetry} />;
  }

  // Not found state (audit not found or not public)
  if (!data) {
    return <NotFoundState />;
  }

  // Transform data for child components
  const categoryScores = transformCategoryScores(data.categoryScores || []);
  const issues = transformIssues(data.topIssues || []);
  const auditHash = hash || data.hash || '';
  const scoreColor = getScoreColor(data.overallScore);
  const scoreBgColor = getScoreBgColor(data.overallScore);
  const scoreStatus = getScoreStatus(data.overallScore);
  const formattedDate = formatDate(data.auditedAt);

  return (
    <main
      data-testid="audit-page"
      role="main"
      className={cn('min-h-screen bg-slate-950', className)}
    >
      {/* Header Section */}
      <header data-testid="page-header" className="border-b border-slate-800 bg-slate-900/50">
        <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
          <h1 className="text-2xl font-bold text-white sm:text-3xl">
            SEO Audit Results
          </h1>
          <div data-testid="site-info" className="mt-2 flex flex-wrap items-center gap-4 text-sm text-slate-400">
            <span data-testid="site-url" className="flex items-center gap-1.5">
              <ExternalLink className="h-4 w-4" aria-hidden="true" />
              {data.siteUrl}
            </span>
            <time
              data-testid="audit-date"
              dateTime={data.auditedAt}
              className="flex items-center gap-1.5"
            >
              <Calendar className="h-4 w-4" aria-hidden="true" />
              {formattedDate}
            </time>
          </div>

          {/* Overall Score Display */}
          <div className="mt-4 flex items-center gap-4">
            <div
              className={cn(
                'flex h-24 w-24 items-center justify-center rounded-full',
                scoreBgColor
              )}
              aria-label={`Overall SEO Score: ${data.overallScore} out of 100`}
            >
              <span className="text-3xl font-bold text-white">
                {data.overallScore}
              </span>
            </div>
            <div className="text-sm">
              <p className={cn('font-semibold', scoreColor)}>
                {scoreStatus}
              </p>
              <p className="text-slate-400">Overall Score</p>
            </div>
          </div>

          {/* Partial data warning */}
          {data.isPartial && (
            <div
              className="mt-4 flex items-center gap-2 rounded-lg border border-yellow-800 bg-yellow-900/20 p-3 text-sm text-yellow-400"
              role="alert"
            >
              <AlertCircle className="h-4 w-4" aria-hidden="true" />
              <span>
                This is a preview. Sign up to see the full report.
              </span>
            </div>
          )}
        </div>
      </header>

      {/* Main Content */}
      <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="grid gap-8 lg:grid-cols-3">
          {/* Left Column - Scores and Issues */}
          <div className="space-y-8 lg:col-span-2">
            {/* Score Overview Section */}
            <section
              data-testid="score-section"
              aria-labelledby="score-heading"
              className="rounded-lg border border-slate-800 bg-slate-900/60 p-6"
            >
              <h2 id="score-heading" className="sr-only">
                Overall Score
              </h2>
              <div data-testid="score-overview-container">
                {/* ScoreOverview component would render here */}
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-lg font-semibold text-white">Overall SEO Score</p>
                    <p className="text-slate-400">Based on comprehensive analysis</p>
                  </div>
                  <div className={cn('text-4xl font-bold', scoreColor)}>
                    {data.overallScore}/100
                  </div>
                </div>
              </div>
            </section>

            {/* Category Breakdown Section */}
            <section
              data-testid="categories-section"
              aria-labelledby="categories-heading"
            >
              <h2 id="categories-heading" className="mb-4 text-xl font-semibold text-white">
                Score by Category
              </h2>
              <div data-testid="category-breakdown-container">
                <CategoryBreakdown
                  categories={categoryScores}
                  expanded={false}
                />
              </div>
            </section>

            {/* Top Issues Section */}
            <section
              data-testid="issues-section"
              aria-labelledby="issues-heading"
            >
              <h2 id="issues-heading" className="mb-4 text-xl font-semibold text-white">
                Top Issues
              </h2>
              <div data-testid="top-issues-container">
                <TopIssues
                  issues={issues}
                  isPartial={data.isPartial}
                  auditHash={auditHash}
                />
              </div>
            </section>

            {/* Recommendations */}
            {data.recommendations && data.recommendations.length > 0 && (
              <section aria-labelledby="recommendations-heading">
                <h2
                  id="recommendations-heading"
                  className="mb-4 text-xl font-semibold text-white"
                >
                  Recommendations
                </h2>
                <ul className="space-y-2 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                  {data.recommendations.map((rec, index) => (
                    <li
                      key={index}
                      className="flex items-start gap-2 text-slate-300"
                    >
                      <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500" />
                      {rec}
                    </li>
                  ))}
                </ul>
              </section>
            )}
          </div>

          {/* Right Column - CTA */}
          <div className="lg:col-span-1">
            <div className="sticky top-8">
              <section
                data-testid="cta-section"
                aria-labelledby="cta-heading"
              >
                <h2 id="cta-heading" className="sr-only">
                  Get Your Free Audit
                </h2>
                <div data-testid="cta-container">
                  <PublicAuditCTA
                    auditHash={auditHash}
                    siteUrl={data.siteUrl}
                    score={data.overallScore}
                  />
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer data-testid="page-footer" className="border-t border-slate-800 bg-slate-900/30 py-6">
        <div className="mx-auto max-w-5xl px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
          <p>
            Generated by{' '}
            <a
              href="https://seoindicator.com"
              data-testid="footer-link"
              className="text-emerald-400 hover:text-emerald-300 hover:underline"
            >
              SEOIndicator
            </a>
          </p>
        </div>
      </footer>
    </main>
  );
}

// ============================================================================
// Legacy Component Export (Backward Compatibility)
// ============================================================================

/**
 * Legacy PublicAuditPage Component
 * For backward compatibility with existing usage
 */
export function LegacyPublicAuditPage({
  audit,
  hash,
  className,
}: LegacyPublicAuditPageProps) {
  // Convert legacy props to new format
  return (
    <PublicAuditPage
      hash={hash || audit.hash || ''}
      initialData={audit}
      className={className}
    />
  );
}

export default PublicAuditPage;
