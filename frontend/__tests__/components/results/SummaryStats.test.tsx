/**
 * @jest-environment jsdom
 * 
 * SummaryStats Component Tests
 * Task: TASK-18-30 - Results Dashboard Page
 * 
 * Tests for the summary statistics component displaying passed/warnings/failed counts.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';

// ============================================================================
// Types
// ============================================================================

interface SummaryStatsData {
  passed: number;
  warnings: number;
  failed: number;
  total: number;
}

// ============================================================================
// Mock Component (TDD Reference)
// ============================================================================

/**
 * SummaryStats - Displays passed/warnings/failed statistics
 * 
 * @example
 * <SummaryStats
 *   stats={{ passed: 12, warnings: 3, failed: 2, total: 17 }}
 *   isLoading={false}
 * />
 */
interface SummaryStatsProps {
  stats: SummaryStatsData;
  isLoading?: boolean;
  showPercentage?: boolean;
  showProgressBar?: boolean;
  className?: string;
}

function SummaryStats({
  stats,
  isLoading = false,
  showPercentage = true,
  showProgressBar = true,
  className,
}: SummaryStatsProps) {
  const { passed, warnings, failed, total } = stats;
  const passedPercent = total > 0 ? Math.round((passed / total) * 100) : 0;

  if (isLoading) {
    return (
      <div
        data-testid="summary-stats"
        data-loading="true"
        className={className}
        aria-busy="true"
      >
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map(i => (
            <div
              key={i}
              data-testid={`stat-skeleton-${i}`}
              className="animate-pulse p-4 border rounded-lg"
            >
              <div className="h-4 bg-muted rounded w-1/2 mb-2" />
              <div className="h-8 bg-muted rounded w-1/3" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  const statItems = [
    {
      id: 'passed',
      label: 'Passed',
      value: passed,
      icon: '✓',
      colorClass: 'text-green-500',
      bgClass: 'bg-green-500',
    },
    {
      id: 'warnings',
      label: 'Warnings',
      value: warnings,
      icon: '⚠',
      colorClass: 'text-yellow-500',
      bgClass: 'bg-yellow-500',
    },
    {
      id: 'failed',
      label: 'Failed',
      value: failed,
      icon: '✗',
      colorClass: 'text-red-500',
      bgClass: 'bg-red-500',
    },
    {
      id: 'total',
      label: 'Total',
      value: total,
      suffix: 'Indicators Analyzed',
      icon: '◉',
      colorClass: 'text-blue-500',
      bgClass: 'bg-blue-500',
    },
  ];

  return (
    <div
      data-testid="summary-stats"
      data-loading="false"
      className={className}
      aria-label={`Summary: ${passed} passed, ${warnings} warnings, ${failed} failed out of ${total} indicators`}
    >
      {/* Stats Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {statItems.map(item => (
          <div
            key={item.id}
            data-testid={`stat-${item.id}`}
            data-value={item.value}
            className="p-4 border rounded-lg flex flex-col"
          >
            <div className="flex items-center gap-2 mb-1">
              <span
                data-testid={`stat-icon-${item.id}`}
                className={item.colorClass}
                aria-hidden="true"
              >
                {item.icon}
              </span>
              <span
                data-testid={`stat-label-${item.id}`}
                className="text-sm text-muted-foreground"
              >
                {item.label}
              </span>
            </div>
            <span
              data-testid={`stat-value-${item.id}`}
              className={`text-2xl font-bold ${item.colorClass}`}
            >
              {item.value}
            </span>
            {item.suffix && (
              <span
                data-testid={`stat-suffix-${item.id}`}
                className="text-xs text-muted-foreground"
              >
                {item.suffix}
              </span>
            )}
          </div>
        ))}
      </div>

      {/* Percentage Passed */}
      {showPercentage && (
        <div
          data-testid="percentage-passed"
          className="mt-4 text-center"
        >
          <span className="text-lg font-medium">
            {passedPercent}% Passed
          </span>
        </div>
      )}

      {/* Segmented Progress Bar */}
      {showProgressBar && total > 0 && (
        <div
          data-testid="progress-bar"
          className="mt-4 h-2 rounded-full overflow-hidden bg-muted flex"
        >
          {/* Passed segment */}
          <div
            data-testid="progress-passed"
            data-width={`${(passed / total) * 100}%`}
            className="bg-green-500 transition-all"
            style={{ width: `${(passed / total) * 100}%` }}
          />
          {/* Warnings segment */}
          <div
            data-testid="progress-warnings"
            data-width={`${(warnings / total) * 100}%`}
            className="bg-yellow-500 transition-all"
            style={{ width: `${(warnings / total) * 100}%` }}
          />
          {/* Failed segment */}
          <div
            data-testid="progress-failed"
            data-width={`${(failed / total) * 100}%`}
            className="bg-red-500 transition-all"
            style={{ width: `${(failed / total) * 100}%` }}
          />
        </div>
      )}
    </div>
  );
}

// ============================================================================
// Tests
// ============================================================================

describe('SummaryStats', () => {
  // -------------------------------------------------------------------------
  // RD-007: Summary Statistics
  // -------------------------------------------------------------------------
  describe('RD-007: Summary Statistics', () => {
    const mockStats: SummaryStatsData = {
      passed: 12,
      warnings: 3,
      failed: 2,
      total: 17,
    };

    it('RD-007.1: displays passed count with green checkmark', () => {
      render(<SummaryStats stats={mockStats} />);
      
      const passedStat = screen.getByTestId('stat-passed');
      expect(passedStat).toHaveAttribute('data-value', '12');
      
      const icon = screen.getByTestId('stat-icon-passed');
      expect(icon).toHaveTextContent('✓');
      expect(icon).toHaveClass('text-green-500');
      
      expect(screen.getByTestId('stat-label-passed')).toHaveTextContent('Passed');
      expect(screen.getByTestId('stat-value-passed')).toHaveTextContent('12');
    });

    it('RD-007.2: displays warnings count with yellow icon', () => {
      render(<SummaryStats stats={mockStats} />);
      
      const warningsStat = screen.getByTestId('stat-warnings');
      expect(warningsStat).toHaveAttribute('data-value', '3');
      
      const icon = screen.getByTestId('stat-icon-warnings');
      expect(icon).toHaveTextContent('⚠');
      expect(icon).toHaveClass('text-yellow-500');
      
      expect(screen.getByTestId('stat-label-warnings')).toHaveTextContent('Warnings');
    });

    it('RD-007.3: displays failed count with red icon', () => {
      render(<SummaryStats stats={mockStats} />);
      
      const failedStat = screen.getByTestId('stat-failed');
      expect(failedStat).toHaveAttribute('data-value', '2');
      
      const icon = screen.getByTestId('stat-icon-failed');
      expect(icon).toHaveTextContent('✗');
      expect(icon).toHaveClass('text-red-500');
      
      expect(screen.getByTestId('stat-label-failed')).toHaveTextContent('Failed');
    });

    it('RD-007.4: displays total indicator count', () => {
      render(<SummaryStats stats={mockStats} />);
      
      const totalStat = screen.getByTestId('stat-total');
      expect(totalStat).toHaveAttribute('data-value', '17');
      
      expect(screen.getByTestId('stat-label-total')).toHaveTextContent('Total');
      expect(screen.getByTestId('stat-suffix-total')).toHaveTextContent('Indicators Analyzed');
    });

    it('RD-007.5: calculates and displays percentage passed', () => {
      render(<SummaryStats stats={mockStats} showPercentage />);
      
      const percentage = screen.getByTestId('percentage-passed');
      // 12/17 = 70.588... ≈ 71%
      expect(percentage).toHaveTextContent('71% Passed');
    });

    it('RD-007.6: displays segmented progress bar', () => {
      render(<SummaryStats stats={mockStats} showProgressBar />);
      
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toBeInTheDocument();
      
      // Check segments exist
      expect(screen.getByTestId('progress-passed')).toBeInTheDocument();
      expect(screen.getByTestId('progress-warnings')).toBeInTheDocument();
      expect(screen.getByTestId('progress-failed')).toBeInTheDocument();
    });

    it('RD-007.7: shows loading skeleton when isLoading', () => {
      render(<SummaryStats stats={mockStats} isLoading />);
      
      const stats = screen.getByTestId('summary-stats');
      expect(stats).toHaveAttribute('data-loading', 'true');
      expect(stats).toHaveAttribute('aria-busy', 'true');
      
      // Check skeletons
      expect(screen.getByTestId('stat-skeleton-1')).toBeInTheDocument();
      expect(screen.getByTestId('stat-skeleton-2')).toBeInTheDocument();
      expect(screen.getByTestId('stat-skeleton-3')).toBeInTheDocument();
      expect(screen.getByTestId('stat-skeleton-4')).toBeInTheDocument();
    });

    it('RD-007.9: has correct accessibility attributes', () => {
      render(<SummaryStats stats={mockStats} />);
      
      const stats = screen.getByTestId('summary-stats');
      expect(stats).toHaveAttribute(
        'aria-label',
        'Summary: 12 passed, 3 warnings, 2 failed out of 17 indicators'
      );
    });
  });

  // -------------------------------------------------------------------------
  // Progress Bar Calculations
  // -------------------------------------------------------------------------
  describe('Progress Bar Calculations', () => {
    it('calculates correct segment widths', () => {
      const stats: SummaryStatsData = {
        passed: 50,
        warnings: 30,
        failed: 20,
        total: 100,
      };

      render(<SummaryStats stats={stats} showProgressBar />);
      
      const passedSegment = screen.getByTestId('progress-passed');
      expect(passedSegment).toHaveAttribute('data-width', '50%');
      
      const warningsSegment = screen.getByTestId('progress-warnings');
      expect(warningsSegment).toHaveAttribute('data-width', '30%');
      
      const failedSegment = screen.getByTestId('progress-failed');
      expect(failedSegment).toHaveAttribute('data-width', '20%');
    });

    it('handles zero totals gracefully', () => {
      const stats: SummaryStatsData = {
        passed: 0,
        warnings: 0,
        failed: 0,
        total: 0,
      };

      render(<SummaryStats stats={stats} showPercentage />);
      
      expect(screen.getByTestId('percentage-passed')).toHaveTextContent('0% Passed');
    });

    it('hides percentage when showPercentage is false', () => {
      const stats: SummaryStatsData = {
        passed: 12,
        warnings: 3,
        failed: 2,
        total: 17,
      };

      render(<SummaryStats stats={stats} showPercentage={false} />);
      
      expect(screen.queryByTestId('percentage-passed')).not.toBeInTheDocument();
    });

    it('hides progress bar when showProgressBar is false', () => {
      const stats: SummaryStatsData = {
        passed: 12,
        warnings: 3,
        failed: 2,
        total: 17,
      };

      render(<SummaryStats stats={stats} showProgressBar={false} />);
      
      expect(screen.queryByTestId('progress-bar')).not.toBeInTheDocument();
    });
  });

  // -------------------------------------------------------------------------
  // Edge Cases
  // -------------------------------------------------------------------------
  describe('Edge Cases', () => {
    it('handles all passed', () => {
      const stats: SummaryStatsData = {
        passed: 17,
        warnings: 0,
        failed: 0,
        total: 17,
      };

      render(<SummaryStats stats={stats} showPercentage />);
      
      expect(screen.getByTestId('percentage-passed')).toHaveTextContent('100% Passed');
      expect(screen.getByTestId('stat-value-warnings')).toHaveTextContent('0');
      expect(screen.getByTestId('stat-value-failed')).toHaveTextContent('0');
    });

    it('handles all failed', () => {
      const stats: SummaryStatsData = {
        passed: 0,
        warnings: 0,
        failed: 17,
        total: 17,
      };

      render(<SummaryStats stats={stats} showPercentage />);
      
      expect(screen.getByTestId('percentage-passed')).toHaveTextContent('0% Passed');
      expect(screen.getByTestId('stat-value-failed')).toHaveTextContent('17');
    });

    it('handles all warnings', () => {
      const stats: SummaryStatsData = {
        passed: 0,
        warnings: 17,
        failed: 0,
        total: 17,
      };

      render(<SummaryStats stats={stats} showPercentage />);
      
      expect(screen.getByTestId('percentage-passed')).toHaveTextContent('0% Passed');
      expect(screen.getByTestId('stat-value-warnings')).toHaveTextContent('17');
    });

    it('applies custom className', () => {
      const stats: SummaryStatsData = {
        passed: 12,
        warnings: 3,
        failed: 2,
        total: 17,
      };

      render(<SummaryStats stats={stats} className="custom-class" />);
      
      const statsEl = screen.getByTestId('summary-stats');
      expect(statsEl).toHaveClass('custom-class');
    });
  });

  // -------------------------------------------------------------------------
  // Acceptance Criteria Verification
  // -------------------------------------------------------------------------
  describe('Acceptance Criteria', () => {
    it('AC: Summary statistics (passed/warnings/failed)', () => {
      const stats: SummaryStatsData = {
        passed: 12,
        warnings: 3,
        failed: 2,
        total: 17,
      };

      render(<SummaryStats stats={stats} />);
      
      // All stats displayed
      expect(screen.getByTestId('stat-passed')).toBeInTheDocument();
      expect(screen.getByTestId('stat-warnings')).toBeInTheDocument();
      expect(screen.getByTestId('stat-failed')).toBeInTheDocument();
      expect(screen.getByTestId('stat-total')).toBeInTheDocument();
      
      // Correct values
      expect(screen.getByTestId('stat-value-passed')).toHaveTextContent('12');
      expect(screen.getByTestId('stat-value-warnings')).toHaveTextContent('3');
      expect(screen.getByTestId('stat-value-failed')).toHaveTextContent('2');
      expect(screen.getByTestId('stat-value-total')).toHaveTextContent('17');
    });

    it('AC: Loading state', () => {
      const stats: SummaryStatsData = {
        passed: 12,
        warnings: 3,
        failed: 2,
        total: 17,
      };

      render(<SummaryStats stats={stats} isLoading />);
      
      expect(screen.getByTestId('summary-stats')).toHaveAttribute('data-loading', 'true');
      expect(screen.getByTestId('stat-skeleton-1')).toBeInTheDocument();
    });
  });
});
