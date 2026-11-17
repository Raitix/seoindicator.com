/**
 * TASK-35-19: QuickStatsCards Component Tests
 *
 * Tests for the quick stats cards showing views, searches, and actions at top of dashboard.
 *
 * The component renders the cards from the location dashboard snapshot,
 * so the values are the ones of that snapshot.
 *
 * @see PROJECT-SEOINDICATOR/TESTS/TEST-35-19.md
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import { QuickStatsCards } from '@/components/local/QuickStatsCards';
import { formatCompactNumber } from '@/lib/location-dashboard';
import type { QuickStatsSnapshot } from '@/lib/types/location-dashboard';

// Mock data
const mockStats: QuickStatsSnapshot = {
  views: 12543,
  searches: 3456,
  actions: 789,
  rating: 4.6,
  reviewCount: 210,
  keywordsInPack: 42,
  viewTrend: 12.5,
  searchTrend: -3.2,
  actionsTrend: 0,
};

// ============================================
// TESTS
// ============================================

describe('QuickStatsCards', () => {
  test('renders all three stat cards', () => {
    render(<QuickStatsCards stats={mockStats} />);

    expect(screen.getByTestId('stat-views')).toBeInTheDocument();
    expect(screen.getByTestId('stat-searches')).toBeInTheDocument();
    expect(screen.getByTestId('stat-actions')).toBeInTheDocument();
  });

  test('displays correct values', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('stat-value')).toHaveTextContent(
      formatCompactNumber(mockStats.views)
    );
  });

  test('shows up trend with green color', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    const trend = within(viewsCard).getByTestId('trend-indicator');
    expect(trend).toHaveClass('text-green-600');
  });

  test('shows down trend with red color', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const searchesCard = screen.getByTestId('stat-searches');
    const trend = within(searchesCard).getByTestId('trend-indicator');
    expect(trend).toHaveClass('text-red-600');
  });

  test('shows stable trend with gray color', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const actionsCard = screen.getByTestId('stat-actions');
    const trend = within(actionsCard).getByTestId('trend-indicator');
    expect(trend).toHaveClass('text-gray-500');
  });

  test('formats large numbers with commas', () => {
    const largeStats: QuickStatsSnapshot = {
      ...mockStats,
      views: 1234567,
      searches: 987654,
      actions: 12345,
    };

    render(<QuickStatsCards stats={largeStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('stat-value')).toHaveTextContent(
      formatCompactNumber(largeStats.views)
    );
  });

  test('applies custom className', () => {
    const { container } = render(
      <QuickStatsCards stats={mockStats} className="custom-class" />
    );

    expect(container.firstElementChild).toHaveClass('custom-class');
  });

  test('shows trend direction as data attribute', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    const searchesCard = screen.getByTestId('stat-searches');
    const actionsCard = screen.getByTestId('stat-actions');

    expect(within(viewsCard).getByTestId('trend-indicator')).toHaveAttribute(
      'data-direction',
      'up'
    );
    expect(within(searchesCard).getByTestId('trend-indicator')).toHaveAttribute(
      'data-direction',
      'down'
    );
    expect(within(actionsCard).getByTestId('trend-indicator')).toHaveAttribute(
      'data-direction',
      'neutral'
    );
  });
});

describe('QuickStatCard', () => {
  test('renders icon', () => {
    render(<QuickStatsCards stats={mockStats} />);

    // Every card carries its own icon
    const viewsCard = screen.getByTestId('stat-views');
    expect(viewsCard.querySelector('svg')).toBeInTheDocument();
  });

  test('renders label', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('stat-label')).toHaveTextContent('Views');
  });

  test('renders formatted value', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('stat-value')).toHaveTextContent(
      formatCompactNumber(mockStats.views)
    );
  });

  test('renders trend indicator', () => {
    render(<QuickStatsCards stats={mockStats} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('trend-indicator')).toBeInTheDocument();
  });

  test('handles zero change', () => {
    render(<QuickStatsCards stats={{ ...mockStats, viewTrend: 0 }} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('trend-indicator')).toHaveTextContent('0%');
  });

  test('handles negative change (shown as positive)', () => {
    render(<QuickStatsCards stats={{ ...mockStats, viewTrend: -10 }} />);

    const viewsCard = screen.getByTestId('stat-views');
    expect(within(viewsCard).getByTestId('trend-indicator')).toHaveTextContent('10%');
  });
});
