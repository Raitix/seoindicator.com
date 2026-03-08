/**
 * Badge Management Page - TASK-33-24
 *
 * Dashboard page where users can view all their public audits,
 * see impression analytics, and manage embed codes.
 *
 * @module app/dashboard/badges/page
 */

'use client';

import * as React from 'react';
import { useRouter } from 'next/navigation';
import { RefreshCw, Eye, MousePointerClick, Tag, AlertCircle, Loader2 } from 'lucide-react';

import { useAuth } from '@/lib/contexts/AuthContext';
import { useBadges } from '@/lib/hooks/useBadges';
import { Button } from '@/components/ui/button';
import { BadgeManagementRow } from '@/components/dashboard/BadgeManagementRow';
import { EmbedCodeModal } from '@/components/dashboard/EmbedCodeModal';
import { cn } from '@/lib/utils';

// ============================================================
// Types
// ============================================================

interface SummaryCardProps {
  icon: React.ReactNode;
  label: string;
  value: string | number;
  testId: string;
}

// ============================================================
// Summary Card Component
// ============================================================

function SummaryCard({ icon, label, value, testId }: SummaryCardProps) {
  return (
    <div
      data-testid={testId}
      className="flex items-center gap-4 rounded-lg border border-slate-700 bg-slate-800/50 p-4"
    >
      <div className="rounded-full bg-slate-700 p-2 text-slate-400">{icon}</div>
      <div>
        <p className="text-2xl font-bold text-white">{value}</p>
        <p className="text-sm text-slate-400">{label}</p>
      </div>
    </div>
  );
}

// ============================================================
// Empty State Component
// ============================================================

function EmptyState() {
  return (
    <div data-testid="empty-state" className="text-center py-12">
      <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-800">
        <Tag className="h-8 w-8 text-slate-400" data-testid="badge-icon" />
      </div>
      <h2 className="text-xl font-semibold text-white mb-2">No Public Audits</h2>
      <p className="text-slate-400 mb-6 max-w-md mx-auto">
        You haven&apos;t published any audits yet. Make an audit public to get a shareable badge.
      </p>
      <a
        href="/dashboard/audits"
        data-testid="view-audits-link"
        className="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
      >
        View Your Audits
      </a>
    </div>
  );
}

// ============================================================
// Loading State Component
// ============================================================

function LoadingState() {
  return (
    <div
      data-testid="loading-state"
      role="status"
      aria-live="polite"
      className="flex flex-col items-center justify-center py-12"
    >
      <Loader2 className="h-8 w-8 animate-spin text-blue-500" data-testid="loader-icon" />
      <p className="mt-4 text-slate-400">Loading your badges...</p>
    </div>
  );
}

// ============================================================
// Error State Component
// ============================================================

interface ErrorStateProps {
  message: string;
  onRetry: () => void;
}

function ErrorState({ message, onRetry }: ErrorStateProps) {
  return (
    <div
      data-testid="error-state"
      role="alert"
      className="text-center py-12"
    >
      <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-900/30">
        <AlertCircle className="h-8 w-8 text-red-400" data-testid="alert-icon" />
      </div>
      <p className="text-red-400 mb-4">{message}</p>
      <Button
        data-testid="retry-button"
        onClick={onRetry}
        variant="outline"
        className="border-slate-700 text-slate-200"
      >
        <RefreshCw className="h-4 w-4 mr-2" />
        Retry
      </Button>
    </div>
  );
}

// ============================================================
// Auth Required Component
// ============================================================

function AuthRequired() {
  const router = useRouter();

  return (
    <div data-testid="auth-required" className="text-center py-12">
      <h1 className="text-xl font-semibold text-white mb-2">Authentication Required</h1>
      <p className="text-slate-400 mb-6">Please sign in to manage your badges.</p>
      <Button
        data-testid="sign-in-button"
        onClick={() => router.push('/login')}
        className="bg-blue-600 hover:bg-blue-700"
      >
        Sign In
      </Button>
    </div>
  );
}

// ============================================================
// Main Page Component
// ============================================================

export default function BadgeManagementPage() {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const {
    sortedAudits,
    isLoading,
    error,
    refresh,
    toggleVisibility,
    sortState,
    setSortField,
  } = useBadges();

  // Modal state
  const [embedCodeHash, setEmbedCodeHash] = React.useState<string | null>(null);

  // Handle embed code request
  const handleGetEmbedCode = (hash: string) => {
    setEmbedCodeHash(hash);
  };

  // Calculate summary stats
  const summaryStats = React.useMemo(() => {
    return {
      totalBadges: sortedAudits.length,
      totalImpressions: sortedAudits.reduce((sum, a) => sum + a.analytics.impressions, 0),
      totalClicks: sortedAudits.reduce((sum, a) => sum + a.analytics.clicks, 0),
    };
  }, [sortedAudits]);

  // Show auth loading state
  if (authLoading) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <LoadingState />
        </div>
      </div>
    );
  }

  // Show auth required if not authenticated
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <AuthRequired />
        </div>
      </div>
    );
  }

  // Show loading state
  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <LoadingState />
        </div>
      </div>
    );
  }

  // Show error state
  if (error) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <ErrorState message={error.message} onRetry={refresh} />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-950" data-testid="badge-management-page">
      <div className="container mx-auto px-4 py-10">
        {/* Header */}
        <header data-testid="page-header" className="mb-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h1 className="text-2xl font-semibold text-white">Badge Management</h1>
              <p className="text-sm text-slate-400">
                Manage your public audit badges and embed codes
              </p>
            </div>
            <Button
              onClick={refresh}
              variant="outline"
              className="border-slate-700 text-slate-200"
            >
              <RefreshCw className="h-4 w-4 mr-2" />
              Refresh
            </Button>
          </div>
        </header>

        {/* Content */}
        {sortedAudits.length === 0 ? (
          <EmptyState />
        ) : (
          <>
            {/* Summary Cards */}
            <div
              data-testid="summary-cards"
              className="grid gap-4 md:grid-cols-3 mb-6"
            >
              <SummaryCard
                icon={<Tag className="h-5 w-5" />}
                label="Public Badges"
                value={summaryStats.totalBadges}
                testId="total-badges-card"
              />
              <SummaryCard
                icon={<Eye className="h-5 w-5" />}
                label="Total Impressions"
                value={summaryStats.totalImpressions.toLocaleString()}
                testId="total-impressions-card"
              />
              <SummaryCard
                icon={<MousePointerClick className="h-5 w-5" />}
                label="Total Clicks"
                value={summaryStats.totalClicks.toLocaleString()}
                testId="total-clicks-card"
              />
            </div>

            {/* Badges Table */}
            <div
              data-testid="badges-table-container"
              className="overflow-x-auto rounded-lg border border-slate-700 bg-slate-800/50"
            >
              <table data-testid="badges-table" className="w-full">
                <thead className="bg-slate-800">
                  <tr>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      Site
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      Badge
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      <button
                        data-testid="sort-impressions"
                        onClick={() => setSortField('impressions')}
                        aria-label="Sort by impressions"
                        className="inline-flex items-center gap-1 hover:text-white"
                      >
                        Impressions
                        <span className="text-slate-500">↕</span>
                      </button>
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      <button
                        data-testid="sort-clicks"
                        onClick={() => setSortField('clicks')}
                        aria-label="Sort by clicks"
                        className="inline-flex items-center gap-1 hover:text-white"
                      >
                        Clicks
                        <span className="text-slate-500">↕</span>
                      </button>
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      <button
                        data-testid="sort-ctr"
                        onClick={() => setSortField('ctr')}
                        aria-label="Sort by CTR"
                        className="inline-flex items-center gap-1 hover:text-white"
                      >
                        CTR
                        <span className="text-slate-500">↕</span>
                      </button>
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      Visibility
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      Embed
                    </th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-slate-300">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {sortedAudits.map((audit) => (
                    <BadgeManagementRow
                      key={audit.id}
                      audit={audit}
                      onToggleVisibility={toggleVisibility}
                      onGetEmbedCode={handleGetEmbedCode}
                    />
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}

        {/* Embed Code Modal */}
        <EmbedCodeModal
          isOpen={embedCodeHash !== null}
          auditHash={embedCodeHash}
          onClose={() => setEmbedCodeHash(null)}
        />
      </div>
    </div>
  );
}
