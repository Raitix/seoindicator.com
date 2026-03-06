/**
 * Public Audit Page Component
 * Main component for displaying public SEO audit results
 * @module components/audit/PublicAuditPage
 */

import * as React from 'react';
import { Calendar, ExternalLink, AlertCircle } from 'lucide-react';

import { cn } from '@/lib/utils';
import { TopIssues, Issue } from './TopIssues';
import { CategoryBreakdown, CategoryScore } from './CategoryBreakdown';
import { PublicAuditCTA } from './PublicAuditCTA';

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
}

/**
 * Category score data from API
 */
export interface CategoryScoreData {
  name: string;
  score: number;
  maxScore?: number;
  issues?: number;
}

/**
 * Issue data from API
 */
export interface IssueData {
  id: string;
  title: string;
  severity: 'critical' | 'warning' | 'info';
  description: string;
  category?: string;
  impact?: string;
}

/**
 * Props for the PublicAuditPage component
 */
export interface PublicAuditPageProps {
  audit: PublicAuditData;
  hash?: string;
  className?: string;
}

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
 * Format date for display
 */
function formatDate(dateString: string): string {
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
  return scores.map((score) => ({
    name: score.name,
    score: score.score,
    issueCount: score.issues || 0,
    criticalCount: 0,
    warningCount: 0,
  }));
}

/**
 * Transform issues to component format
 */
function transformIssues(issues: IssueData[]): Issue[] {
  return issues.map((issue) => ({
    id: issue.id,
    title: issue.title,
    description: issue.description,
    severity: issue.severity,
    category: issue.category || 'General',
    impact: issue.impact || 'Impact not specified',
  }));
}

/**
 * PublicAuditPage Component
 * Renders the full public audit page with all sections
 * @param props - Component props
 * @returns JSX element
 */
export function PublicAuditPage({ audit, hash, className }: PublicAuditPageProps) {
  const categoryScores = transformCategoryScores(audit.categoryScores || []);
  const issues = transformIssues(audit.topIssues || []);
  const auditHash = hash || '';
  const scoreColor = getScoreColor(audit.overallScore);
  const scoreBgColor = getScoreBgColor(audit.overallScore);
  const formattedDate = formatDate(audit.auditedAt);

  return (
    <div
      data-testid="public-audit-page"
      data-site-url={audit.siteUrl}
      className={cn('min-h-screen bg-slate-950', className)}
    >
      {/* Header Section */}
      <header className="border-b border-slate-800 bg-slate-900/50">
        <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 className="text-2xl font-bold text-white sm:text-3xl">
                SEO Audit Report
              </h1>
              <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-slate-400">
                <span className="flex items-center gap-1.5">
                  <ExternalLink className="h-4 w-4" aria-hidden="true" />
                  {audit.siteUrl}
                </span>
                <span className="flex items-center gap-1.5">
                  <Calendar className="h-4 w-4" aria-hidden="true" />
                  {formattedDate}
                </span>
              </div>
            </div>

            {/* Overall Score */}
            <div className="flex items-center gap-4">
              <div
                className={cn(
                  'flex h-24 w-24 items-center justify-center rounded-full',
                  scoreBgColor
                )}
                aria-label={`Overall SEO Score: ${audit.overallScore} out of 100`}
              >
                <span className="text-3xl font-bold text-white">
                  {audit.overallScore}
                </span>
              </div>
              <div className="text-sm">
                <p className={cn('font-semibold', scoreColor)}>
                  Overall Score
                </p>
                <p className="text-slate-400">out of 100</p>
              </div>
            </div>
          </div>

          {/* Partial data warning */}
          {audit.isPartial && (
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
      <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="grid gap-8 lg:grid-cols-3">
          {/* Left Column - Scores and Issues */}
          <div className="space-y-8 lg:col-span-2">
            {/* Category Breakdown */}
            <section aria-labelledby="category-scores-heading">
              <h2
                id="category-scores-heading"
                className="mb-4 text-xl font-semibold text-white"
              >
                Category Scores
              </h2>
              <CategoryBreakdown
                categories={categoryScores}
                expanded={false}
              />
            </section>

            {/* Top Issues */}
            <section aria-labelledby="top-issues-heading">
              <TopIssues
                issues={issues}
                isPartial={audit.isPartial}
                auditHash={auditHash}
              />
            </section>

            {/* Recommendations */}
            {audit.recommendations && audit.recommendations.length > 0 && (
              <section aria-labelledby="recommendations-heading">
                <h2
                  id="recommendations-heading"
                  className="mb-4 text-xl font-semibold text-white"
                >
                  Recommendations
                </h2>
                <ul className="space-y-2 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                  {audit.recommendations.map((rec, index) => (
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
              <PublicAuditCTA
                auditHash={auditHash}
                siteUrl={audit.siteUrl}
                score={audit.overallScore}
              />
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="border-t border-slate-800 bg-slate-900/30 py-6">
        <div className="mx-auto max-w-5xl px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
          <p>
            Powered by{' '}
            <a
              href="https://seoindicator.com"
              className="text-emerald-400 hover:text-emerald-300 hover:underline"
            >
              SEOIndicator
            </a>
            {' '}• Get your free SEO audit today
          </p>
        </div>
      </footer>
    </div>
  );
}

export default PublicAuditPage;
