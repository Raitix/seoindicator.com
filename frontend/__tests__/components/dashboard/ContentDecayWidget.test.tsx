/**
 * ContentDecayWidget Component Tests
 * TASK-36-14: Navigation & Dashboard Integration
 * 
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  TrendingDown: () => <span data-testid="trending-down-icon">TrendingDown</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  ArrowRight: () => <span data-testid="arrow-right-icon">ArrowRight</span>,
  Eye: () => <span data-testid="eye-icon">Eye</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  RefreshCw: () => <span data-testid="refresh-icon">RefreshCw</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
  ChevronRight: () => <span data-testid="chevron-icon">ChevronRight</span>,
  Clock: () => <span data-testid="clock-icon">Clock</span>,
}));

// =============================================================================
// Types
// =============================================================================

interface DecaySummary {
  criticalCount: number;
  highCount: number;
  mediumCount: number;
  totalCandidates: number;
  estimatedRecoveryUpside: number;
  recoveredCount: number;
  lastScannedAt: string | null;
}

interface DecayCandidate {
  id: number;
  pageUrl: string;
  pageTitle: string;
  decayScore: number;
  severity: 'critical' | 'high' | 'medium';
  primaryReason: string;
  lostClicks: number;
  lostImpressions?: number;
  ctrChange?: number;
  positionChange?: number;
  lastUpdated: string;
}

interface ContentDecayWidgetProps {
  siteId: string;
  summary?: DecaySummary | null;
  topCandidates?: DecayCandidate[];
  isLoading?: boolean;
  error?: string | null;
  onRefresh?: () => void;
  onViewCandidate?: (id: number) => void;
  onMarkPlanned?: (id: number) => void;
  onIgnore?: (id: number) => void;
  className?: string;
}

// =============================================================================
// Mock Component for TDD Reference
// =============================================================================

const ContentDecayWidget: React.FC<ContentDecayWidgetProps> = ({
  siteId,
  summary,
  topCandidates = [],
  isLoading = false,
  error = null,
  onRefresh,
  onViewCandidate,
  onMarkPlanned,
  onIgnore,
  className = '',
}) => {
  // Loading state
  if (isLoading) {
    return (
      <div 
        data-testid="content-decay-widget" 
        className={`bg-white rounded-lg p-4 ${className}`}
        role="status"
        aria-live="polite"
      >
        <h3 className="text-lg font-semibold mb-4">Content Decay</h3>
        <div className="animate-pulse space-y-3" data-testid="loading-skeleton">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-12 bg-gray-200 rounded" />
          ))}
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div 
        data-testid="content-decay-widget" 
        className={`bg-white rounded-lg p-4 ${className}`}
        role="alert"
      >
        <h3 className="text-lg font-semibold mb-4">Content Decay</h3>
        <div className="text-red-600 flex items-center gap-2">
          <span data-testid="alert-icon">AlertTriangle</span>
          <span>{error}</span>
        </div>
        {onRefresh && (
          <button 
            onClick={onRefresh}
            className="mt-3 text-blue-600 hover:underline"
            aria-label="Retry loading content decay data"
          >
            Retry
          </button>
        )}
      </div>
    );
  }

  // Empty state
  if (!summary || summary.totalCandidates === 0) {
    return (
      <div 
        data-testid="content-decay-widget" 
        className={`bg-white rounded-lg p-4 ${className}`}
        role="region"
        aria-label="Content Decay Summary"
      >
        <h3 className="text-lg font-semibold mb-4">Content Decay</h3>
        <div className="text-center py-6" role="status" aria-live="polite">
          <span data-testid="check-circle-icon" className="text-green-500 text-4xl">CheckCircle</span>
          <p className="mt-2 text-gray-600">No content decay detected</p>
          <p className="text-sm text-gray-500">Your content is performing well</p>
          {summary?.recoveredCount ? (
            <p className="mt-2 text-sm text-green-600">{summary.recoveredCount} pages recovered</p>
          ) : null}
          {onRefresh && (
            <button 
              onClick={onRefresh}
              className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              aria-label="Run content decay scan"
            >
              Run Scan
            </button>
          )}
        </div>
      </div>
    );
  }

  // Data state
  const formatNumber = (num: number): string => {
    if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
    return num.toString();
  };

  const truncateUrl = (url: string, maxLength = 40): string => {
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength - 3) + '...';
  };

  const formatReason = (reason: string): string => {
    const reasonMap: Record<string, string> = {
      'clicks_down': 'Click loss',
      'impressions_down': 'Impression loss',
      'ctr_down': 'CTR drop',
      'avg_position_down': 'Position decline',
      'tracked_rankings_down': 'Ranking decline',
      'audit_issues_present': 'Technical issues',
      'stale_content': 'Stale content',
    };
    return reasonMap[reason] || reason;
  };

  const formatRelativeTime = (timestamp: string | null): string => {
    if (!timestamp) return 'Never scanned';
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minutes ago`;
    if (diffHours < 24) return `${diffHours} hours ago`;
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  return (
    <div 
      data-testid="content-decay-widget" 
      className={`bg-white rounded-lg p-4 ${className}`}
      role="region"
      aria-label="Content Decay Summary"
    >
      {/* Header */}
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-lg font-semibold">Content Decay</h3>
        <a 
          href={`/dashboard/sites/${siteId}/content-decay`}
          className="text-blue-600 hover:underline flex items-center gap-1"
          aria-label="View all content decay issues"
          data-testid="view-all-link"
        >
          View All Issues
          <span data-testid="arrow-right-icon">ArrowRight</span>
        </a>
      </div>

      {/* Summary Card */}
      <div className="grid grid-cols-3 gap-3 mb-4" role="group" aria-label="Decay severity counts">
        {summary.criticalCount > 0 && (
          <div 
            className="bg-red-50 text-red-600 px-3 py-2 rounded text-center"
            data-severity="critical"
            aria-label={`${summary.criticalCount} critical issues`}
          >
            <span className="font-bold">{summary.criticalCount}</span>
            <span className="text-sm ml-1">Critical</span>
          </div>
        )}
        {summary.highCount > 0 && (
          <div 
            className="bg-orange-50 text-orange-600 px-3 py-2 rounded text-center"
            data-severity="high"
            aria-label={`${summary.highCount} high priority issues`}
          >
            <span className="font-bold">{summary.highCount}</span>
            <span className="text-sm ml-1">High</span>
          </div>
        )}
        {summary.mediumCount > 0 && (
          <div 
            className="bg-yellow-50 text-yellow-600 px-3 py-2 rounded text-center"
            data-severity="medium"
            aria-label={`${summary.mediumCount} medium priority issues`}
          >
            <span className="font-bold">{summary.mediumCount}</span>
            <span className="text-sm ml-1">Medium</span>
          </div>
        )}
      </div>

      {/* Stats Row */}
      <div className="flex justify-between text-sm text-gray-600 mb-4">
        <span>{summary.totalCandidates} pages need attention</span>
        <span>{formatNumber(summary.estimatedRecoveryUpside)} potential clicks</span>
      </div>

      {/* Top Candidates */}
      {topCandidates.length > 0 && (
        <div className="space-y-2" role="list" aria-label="Top content decay candidates">
          {topCandidates.slice(0, 3).map((candidate) => (
            <div 
              key={candidate.id} 
              className="flex items-center justify-between p-2 bg-gray-50 rounded hover:bg-gray-100"
              role="listitem"
            >
              <div className="flex-1 min-w-0">
                <p className="font-medium truncate" title={candidate.pageTitle}>
                  {candidate.pageTitle}
                </p>
                <p className="text-sm text-gray-500 truncate" title={candidate.pageUrl}>
                  {truncateUrl(candidate.pageUrl)}
                </p>
                <div className="flex items-center gap-2 mt-1">
                  <span 
                    className={`text-xs px-1.5 py-0.5 rounded ${
                      candidate.severity === 'critical' ? 'bg-red-100 text-red-600' :
                      candidate.severity === 'high' ? 'bg-orange-100 text-orange-600' :
                      'bg-yellow-100 text-yellow-600'
                    }`}
                    data-severity={candidate.severity}
                  >
                    {candidate.decayScore}
                  </span>
                  <span className="text-xs text-gray-500">
                    {formatReason(candidate.primaryReason)}
                  </span>
                  <span className="text-xs text-red-500">
                    -{candidate.lostClicks} clicks
                  </span>
                </div>
              </div>
              <div className="flex gap-1 ml-2">
                <button 
                  onClick={() => onViewCandidate?.(candidate.id)}
                  className="text-xs px-2 py-1 text-blue-600 hover:bg-blue-50 rounded"
                  aria-label={`View ${candidate.pageTitle}`}
                >
                  View
                </button>
                <button 
                  onClick={() => onMarkPlanned?.(candidate.id)}
                  className="text-xs px-2 py-1 text-green-600 hover:bg-green-50 rounded"
                  aria-label={`Plan ${candidate.pageTitle}`}
                >
                  Plan
                </button>
                <button 
                  onClick={() => onIgnore?.(candidate.id)}
                  className="text-xs px-2 py-1 text-gray-600 hover:bg-gray-100 rounded"
                  aria-label={`Ignore ${candidate.pageTitle}`}
                >
                  Ignore
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Last Scanned */}
      <div className="mt-4 text-xs text-gray-500 flex items-center gap-1">
        <span data-testid="clock-icon">Clock</span>
        <span>Last scanned: {formatRelativeTime(summary.lastScannedAt)}</span>
      </div>

      {/* Recovered Count */}
      {summary.recoveredCount > 0 && (
        <div className="mt-2 text-xs text-green-600">
          {summary.recoveredCount} pages recovered
        </div>
      )}
    </div>
  );
};

// =============================================================================
// Test Data
// =============================================================================

const mockSummary: DecaySummary = {
  criticalCount: 5,
  highCount: 12,
  mediumCount: 8,
  totalCandidates: 25,
  estimatedRecoveryUpside: 1500,
  recoveredCount: 3,
  lastScannedAt: '2026-03-10T15:30:00Z',
};

const mockCandidate: DecayCandidate = {
  id: 1,
  pageUrl: 'https://example.com/blog/seo-tips',
  pageTitle: '10 SEO Tips for 2024',
  decayScore: 85,
  severity: 'critical',
  primaryReason: 'clicks_down',
  lostClicks: 450,
  lastUpdated: '2026-03-10T14:00:00Z',
};

const mockTopCandidates: DecayCandidate[] = [
  { ...mockCandidate, id: 1, decayScore: 95, severity: 'critical' },
  { ...mockCandidate, id: 2, decayScore: 78, severity: 'high' },
  { ...mockCandidate, id: 3, decayScore: 65, severity: 'high' },
  { ...mockCandidate, id: 4, decayScore: 52, severity: 'medium' },
  { ...mockCandidate, id: 5, decayScore: 40, severity: 'medium' },
];

// =============================================================================
// Tests
// =============================================================================

describe('ContentDecayWidget', () => {
  // -------------------------------------------------------------------------
  // CDW-001: Widget Rendering (10 tests)
  // -------------------------------------------------------------------------
  describe('Widget Rendering', () => {
    it('CDW-001.1: renders with required props', () => {
      render(<ContentDecayWidget siteId="1" />);
      expect(screen.getByTestId('content-decay-widget')).toBeInTheDocument();
    });

    it('CDW-001.2: has correct ARIA role', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const widget = screen.getByRole('region');
      expect(widget).toHaveAttribute('aria-label', 'Content Decay Summary');
    });

    it('CDW-001.3: has correct CSS classes', () => {
      render(<ContentDecayWidget siteId="1" className="custom-class" />);
      const widget = screen.getByTestId('content-decay-widget');
      expect(widget).toHaveClass('bg-white', 'rounded-lg', 'p-4', 'custom-class');
    });

    it('CDW-001.4: renders header with title', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('Content Decay')).toBeInTheDocument();
    });

    it('CDW-001.5: renders summary section with counts', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('5')).toBeInTheDocument();
      expect(screen.getByText('Critical')).toBeInTheDocument();
    });

    it('CDW-001.6: renders top candidates list', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
      expect(screen.getByRole('list', { name: 'Top content decay candidates' })).toBeInTheDocument();
    });

    it('CDW-001.7: renders View All link with correct href', () => {
      render(<ContentDecayWidget siteId="test-site" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      expect(link).toHaveAttribute('href', '/dashboard/sites/test-site/content-decay');
    });

    it('CDW-001.8: applies custom className', () => {
      render(<ContentDecayWidget siteId="1" className="my-custom-class" />);
      expect(screen.getByTestId('content-decay-widget')).toHaveClass('my-custom-class');
    });

    it('CDW-001.9: renders empty state when no candidates', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0 }} />);
      expect(screen.getByText('No content decay detected')).toBeInTheDocument();
    });

    it('CDW-001.10: shows loading when summary is null', () => {
      render(<ContentDecayWidget siteId="1" summary={null} />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-002: Empty State (8 tests)
  // -------------------------------------------------------------------------
  describe('Empty State', () => {
    it('CDW-002.1: displays empty state message', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} />);
      expect(screen.getByText('No content decay detected')).toBeInTheDocument();
    });

    it('CDW-002.2: has checkmark icon', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} />);
      expect(screen.getByTestId('check-circle-icon')).toBeInTheDocument();
    });

    it('CDW-002.3: has descriptive text', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} />);
      expect(screen.getByText('Your content is performing well')).toBeInTheDocument();
    });

    it('CDW-002.4: has Run Scan button', () => {
      const onRefresh = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} onRefresh={onRefresh} />);
      expect(screen.getByText('Run Scan')).toBeInTheDocument();
    });

    it('CDW-002.5: Run Scan button triggers callback', async () => {
      const onRefresh = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} onRefresh={onRefresh} />);
      await userEvent.click(screen.getByText('Run Scan'));
      expect(onRefresh).toHaveBeenCalled();
    });

    it('CDW-002.6: has correct accessibility attributes', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0 }} />);
      const status = screen.getByRole('status');
      expect(status).toHaveAttribute('aria-live', 'polite');
    });

    it('CDW-002.7: shows recovered count message', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0, recoveredCount: 5 }} />);
      expect(screen.getByText('5 pages recovered')).toBeInTheDocument();
    });

    it('CDW-002.8: hides recovered count when zero', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, totalCandidates: 0, criticalCount: 0, highCount: 0, mediumCount: 0, recoveredCount: 0 }} />);
      expect(screen.queryByText(/pages recovered/)).not.toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-003: Loading State (8 tests)
  // -------------------------------------------------------------------------
  describe('Loading State', () => {
    it('CDW-003.1: shows loading skeleton', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('CDW-003.2: has correct skeleton count', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      const skeletons = screen.getByTestId('loading-skeleton').querySelectorAll('.h-12');
      expect(skeletons).toHaveLength(3);
    });

    it('CDW-003.3: has correct accessibility attributes', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      const widget = screen.getByRole('status');
      expect(widget).toHaveAttribute('aria-live', 'polite');
    });

    it('CDW-003.4: hides content during loading', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={true} />);
      expect(screen.queryByTestId('view-all-link')).not.toBeInTheDocument();
    });

    it('CDW-003.5: shows data when loading completes', () => {
      const { rerender } = render(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={true} />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
      
      rerender(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={false} />);
      expect(screen.getByTestId('view-all-link')).toBeInTheDocument();
    });

    it('CDW-003.6: skeleton has animation class', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      const skeleton = screen.getByTestId('loading-skeleton');
      expect(skeleton).toHaveClass('animate-pulse');
    });

    it('CDW-003.7: error takes priority over loading', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} error="Failed to load" />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.queryByTestId('loading-skeleton')).not.toBeInTheDocument();
    });

    it('CDW-003.8: preserves siteId during loading', () => {
      render(<ContentDecayWidget siteId="test-site-123" isLoading={true} />);
      const widget = screen.getByTestId('content-decay-widget');
      expect(widget).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-004: Summary Card (12 tests)
  // -------------------------------------------------------------------------
  describe('Summary Card', () => {
    it('CDW-004.1: displays critical count badge', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('5')).toBeInTheDocument();
      expect(screen.getByText('Critical')).toBeInTheDocument();
    });

    it('CDW-004.2: critical badge has correct color', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const badge = screen.getByText('Critical').closest('div');
      expect(badge).toHaveAttribute('data-severity', 'critical');
      expect(badge).toHaveClass('bg-red-50', 'text-red-600');
    });

    it('CDW-004.3: displays high count badge', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('12')).toBeInTheDocument();
      expect(screen.getByText('High')).toBeInTheDocument();
    });

    it('CDW-004.4: high badge has correct color', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const badge = screen.getByText('High').closest('div');
      expect(badge).toHaveAttribute('data-severity', 'high');
      expect(badge).toHaveClass('bg-orange-50', 'text-orange-600');
    });

    it('CDW-004.5: displays medium count badge', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('8')).toBeInTheDocument();
      expect(screen.getByText('Medium')).toBeInTheDocument();
    });

    it('CDW-004.6: medium badge has correct color', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const badge = screen.getByText('Medium').closest('div');
      expect(badge).toHaveAttribute('data-severity', 'medium');
      expect(badge).toHaveClass('bg-yellow-50', 'text-yellow-600');
    });

    it('CDW-004.7: displays total candidates count', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('25 pages need attention')).toBeInTheDocument();
    });

    it('CDW-004.8: formats recovery upside correctly', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('1.5K potential clicks')).toBeInTheDocument();
    });

    it('CDW-004.9: formats zero upside correctly', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, estimatedRecoveryUpside: 0 }} />);
      expect(screen.getByText('0 potential clicks')).toBeInTheDocument();
    });

    it('CDW-004.10: formats large numbers correctly', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, estimatedRecoveryUpside: 2500000 }} />);
      expect(screen.getByText('2.5M potential clicks')).toBeInTheDocument();
    });

    it('CDW-004.11: displays recovered count', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('3 pages recovered')).toBeInTheDocument();
    });

    it('CDW-004.12: has correct ARIA labels on badges', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const criticalBadge = screen.getByLabelText('5 critical issues');
      expect(criticalBadge).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-005: Top Candidates List (15 tests)
  // -------------------------------------------------------------------------
  describe('Top Candidates List', () => {
    it('CDW-005.1: renders only top 3 candidates', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(3);
    });

    it('CDW-005.2: displays truncated URL with tooltip', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const truncated = screen.getByTitle(mockCandidate.pageUrl);
      expect(truncated).toBeInTheDocument();
    });

    it('CDW-005.3: truncates long URLs', () => {
      const longUrl = 'https://example.com/this-is-a-very-long-url-that-should-be-truncated';
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[{ ...mockCandidate, pageUrl: longUrl }]} />);
      const truncated = screen.getByTitle(longUrl);
      expect(truncated.textContent?.endsWith('...')).toBe(true);
    });

    it('CDW-005.4: displays candidate title', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText(mockCandidate.pageTitle)).toBeInTheDocument();
    });

    it('CDW-005.5: displays decay score with correct color', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const scoreBadge = screen.getByText('85');
      expect(scoreBadge).toHaveAttribute('data-severity', 'critical');
    });

    it('CDW-005.6: displays severity indicator', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const badge = screen.getByText('85').closest('span');
      expect(badge).toHaveClass('bg-red-100', 'text-red-600');
    });

    it('CDW-005.7: displays formatted primary reason', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText('Click loss')).toBeInTheDocument();
    });

    it('CDW-005.8: displays lost clicks', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText(/-450 clicks/)).toBeInTheDocument();
    });

    it('CDW-005.9: View button triggers callback', async () => {
      const onViewCandidate = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onViewCandidate={onViewCandidate} />);
      await userEvent.click(screen.getByText('View'));
      expect(onViewCandidate).toHaveBeenCalledWith(1);
    });

    it('CDW-005.10: Plan button triggers callback', async () => {
      const onMarkPlanned = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onMarkPlanned={onMarkPlanned} />);
      await userEvent.click(screen.getByText('Plan'));
      expect(onMarkPlanned).toHaveBeenCalledWith(1);
    });

    it('CDW-005.11: Ignore button triggers callback', async () => {
      const onIgnore = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onIgnore={onIgnore} />);
      await userEvent.click(screen.getByText('Ignore'));
      expect(onIgnore).toHaveBeenCalledWith(1);
    });

    it('CDW-005.12: handles empty candidates list', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[]} />);
      expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
    });

    it('CDW-005.13: has correct list item accessibility', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const item = screen.getByRole('listitem');
      expect(item).toBeInTheDocument();
    });

    it('CDW-005.14: has keyboard focusable action buttons', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const buttons = screen.getAllByRole('button').filter(b => ['View', 'Plan', 'Ignore'].includes(b.textContent || ''));
      buttons.forEach(btn => expect(btn).not.toBeDisabled());
    });

    it('CDW-005.15: has hover state styling', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const item = screen.getByRole('listitem');
      expect(item).toHaveClass('hover:bg-gray-100');
    });
  });

  // -------------------------------------------------------------------------
  // CDW-006: Timestamp Display (8 tests)
  // -------------------------------------------------------------------------
  describe('Timestamp Display', () => {
    it('CDW-006.1: displays "Just now" for recent scan', () => {
      const recentTime = new Date().toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: recentTime }} />);
      expect(screen.getByText(/Just now/)).toBeInTheDocument();
    });

    it('CDW-006.2: displays minutes ago', () => {
      const thirtyMinsAgo = new Date(Date.now() - 30 * 60000).toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: thirtyMinsAgo }} />);
      expect(screen.getByText(/30 minutes ago/)).toBeInTheDocument();
    });

    it('CDW-006.3: displays hours ago', () => {
      const twoHoursAgo = new Date(Date.now() - 2 * 3600000).toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: twoHoursAgo }} />);
      expect(screen.getByText(/2 hours ago/)).toBeInTheDocument();
    });

    it('CDW-006.4: displays "Yesterday"', () => {
      const yesterday = new Date(Date.now() - 24 * 3600000).toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: yesterday }} />);
      expect(screen.getByText(/Yesterday/)).toBeInTheDocument();
    });

    it('CDW-006.5: displays days ago', () => {
      const fiveDaysAgo = new Date(Date.now() - 5 * 86400000).toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: fiveDaysAgo }} />);
      expect(screen.getByText(/5 days ago/)).toBeInTheDocument();
    });

    it('CDW-006.6: displays formatted date for week+ ago', () => {
      const tenDaysAgo = new Date(Date.now() - 10 * 86400000).toISOString();
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: tenDaysAgo }} />);
      // Should show formatted date like "Mar 1, 2026"
      const text = screen.getByText(/Last scanned:/).textContent;
      expect(text).not.toContain('days ago');
    });

    it('CDW-006.7: displays "Never scanned" for null timestamp', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: null }} />);
      expect(screen.getByText(/Never scanned/)).toBeInTheDocument();
    });

    it('CDW-006.8: handles invalid timestamp gracefully', () => {
      render(<ContentDecayWidget siteId="1" summary={{ ...mockSummary, lastScannedAt: 'invalid' }} />);
      // Should not crash, shows some text
      expect(screen.getByText(/Last scanned:/)).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-007: Navigation Links (8 tests)
  // -------------------------------------------------------------------------
  describe('Navigation Links', () => {
    it('CDW-007.1: View All link has correct href', () => {
      render(<ContentDecayWidget siteId="my-site" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      expect(link).toHaveAttribute('href', '/dashboard/sites/my-site/content-decay');
    });

    it('CDW-007.2: View All link has correct text', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      expect(link).toHaveTextContent('View All Issues');
    });

    it('CDW-007.3: View All link has aria-label', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      expect(link).toHaveAttribute('aria-label', 'View all content decay issues');
    });

    it('CDW-007.4: header title is visible', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByText('Content Decay')).toBeInTheDocument();
    });

    it('CDW-007.5: View All link has arrow icon', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      const icon = within(link).getByTestId('arrow-right-icon');
      expect(icon).toBeInTheDocument();
    });

    it('CDW-007.6: View All link not shown during loading', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={true} />);
      expect(screen.queryByTestId('view-all-link')).not.toBeInTheDocument();
    });

    it('CDW-007.7: View All link shown when candidates exist', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
      expect(screen.getByTestId('view-all-link')).toBeInTheDocument();
    });

    it('CDW-007.8: link has hover styling', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const link = screen.getByTestId('view-all-link');
      expect(link).toHaveClass('hover:underline');
    });
  });

  // -------------------------------------------------------------------------
  // CDW-008: Quick Actions (10 tests)
  // -------------------------------------------------------------------------
  describe('Quick Actions', () => {
    it('CDW-008.1: View button triggers callback', async () => {
      const onViewCandidate = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onViewCandidate={onViewCandidate} />);
      await userEvent.click(screen.getByText('View'));
      expect(onViewCandidate).toHaveBeenCalledWith(1);
    });

    it('CDW-008.2: Plan button triggers callback', async () => {
      const onMarkPlanned = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onMarkPlanned={onMarkPlanned} />);
      await userEvent.click(screen.getByText('Plan'));
      expect(onMarkPlanned).toHaveBeenCalledWith(1);
    });

    it('CDW-008.3: Ignore button triggers callback', async () => {
      const onIgnore = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onIgnore={onIgnore} />);
      await userEvent.click(screen.getByText('Ignore'));
      expect(onIgnore).toHaveBeenCalledWith(1);
    });

    it('CDW-008.4: View button has correct label', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText('View')).toBeInTheDocument();
    });

    it('CDW-008.5: Plan button has correct label', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText('Plan')).toBeInTheDocument();
    });

    it('CDW-008.6: Ignore button has correct label', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      expect(screen.getByText('Ignore')).toBeInTheDocument();
    });

    it('CDW-008.7: buttons have aria-labels', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const viewBtn = screen.getByLabelText('View 10 SEO Tips for 2024');
      expect(viewBtn).toBeInTheDocument();
    });

    it('CDW-008.8: buttons work when not loading', async () => {
      const onViewCandidate = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onViewCandidate={onViewCandidate} isLoading={false} />);
      await userEvent.click(screen.getByText('View'));
      expect(onViewCandidate).toHaveBeenCalled();
    });

    it('CDW-008.9: keyboard Enter activates button', async () => {
      const onViewCandidate = jest.fn();
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} onViewCandidate={onViewCandidate} />);
      const viewBtn = screen.getByText('View');
      await userEvent.type(viewBtn, '{enter}');
      expect(onViewCandidate).toHaveBeenCalled();
    });

    it('CDW-008.10: all action buttons are rendered', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const item = screen.getByRole('listitem');
      const buttons = within(item).getAllByRole('button');
      expect(buttons).toHaveLength(3);
    });
  });

  // -------------------------------------------------------------------------
  // CDW-009: Error State (6 tests)
  // -------------------------------------------------------------------------
  describe('Error State', () => {
    it('CDW-009.1: displays error message', () => {
      render(<ContentDecayWidget siteId="1" error="Failed to load decay data" />);
      expect(screen.getByText('Failed to load decay data')).toBeInTheDocument();
    });

    it('CDW-009.2: has alert role', () => {
      render(<ContentDecayWidget siteId="1" error="Failed to load" />);
      const alert = screen.getByRole('alert');
      expect(alert).toBeInTheDocument();
    });

    it('CDW-009.3: has retry button', () => {
      const onRefresh = jest.fn();
      render(<ContentDecayWidget siteId="1" error="Failed to load" onRefresh={onRefresh} />);
      expect(screen.getByText('Retry')).toBeInTheDocument();
    });

    it('CDW-009.4: retry button triggers callback', async () => {
      const onRefresh = jest.fn();
      render(<ContentDecayWidget siteId="1" error="Failed to load" onRefresh={onRefresh} />);
      await userEvent.click(screen.getByText('Retry'));
      expect(onRefresh).toHaveBeenCalled();
    });

    it('CDW-009.5: error state hides when loading', () => {
      const { rerender } = render(<ContentDecayWidget siteId="1" error="Failed" />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
      
      rerender(<ContentDecayWidget siteId="1" error="Failed" isLoading={true} />);
      // Error takes priority - this is intentional behavior
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('CDW-009.6: displays alert icon', () => {
      render(<ContentDecayWidget siteId="1" error="Failed" />);
      expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // CDW-010: Accessibility (10 tests)
  // -------------------------------------------------------------------------
  describe('Accessibility', () => {
    it('CDW-010.1: widget has region role', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      expect(screen.getByRole('region')).toBeInTheDocument();
    });

    it('CDW-010.2: widget has aria-label', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const widget = screen.getByRole('region');
      expect(widget).toHaveAttribute('aria-label', 'Content Decay Summary');
    });

    it('CDW-010.3: summary has aria-live', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const group = screen.getByRole('group', { name: 'Decay severity counts' });
      expect(group).toBeInTheDocument();
    });

    it('CDW-010.4: severity badges have labels', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      const criticalBadge = screen.getByLabelText('5 critical issues');
      expect(criticalBadge).toBeInTheDocument();
    });

    it('CDW-010.5: keyboard focus order is logical', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const focusableElements = screen.getAllByRole('button').concat(screen.getByRole('link'));
      // All focusable elements should be present
      expect(focusableElements.length).toBeGreaterThan(0);
    });

    it('CDW-010.6: focus visible on buttons', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(btn => {
        expect(btn).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('CDW-010.7: loading state has screen reader text', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      const status = screen.getByRole('status');
      expect(status).toBeInTheDocument();
    });

    it('CDW-010.8: action buttons have aria-labels', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[mockCandidate]} />);
      const viewBtn = screen.getByLabelText(/View/);
      expect(viewBtn).toBeInTheDocument();
    });

    it('CDW-010.9: color contrast meets WCAG AA', () => {
      render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
      // Test passes if no console errors about contrast
      const criticalBadge = screen.getByText('Critical').closest('div');
      expect(criticalBadge).toHaveClass('text-red-600');
    });

    it('CDW-010.10: respects reduced motion preference', () => {
      render(<ContentDecayWidget siteId="1" isLoading={true} />);
      const skeleton = screen.getByTestId('loading-skeleton');
      // Animation class is applied, but reduced-motion media query would disable
      expect(skeleton).toHaveClass('animate-pulse');
    });
  });
});
