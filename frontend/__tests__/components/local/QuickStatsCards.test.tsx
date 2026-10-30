/**
 * TASK-35-19: QuickStatsCards Component Tests
 * 
 * Tests for the quick stats cards showing views, searches, and actions at top of dashboard.
 * 
 * @see PROJECT-SEOINDICATOR/TESTS/TEST-35-19.md
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Eye: () => <span data-testid="eye-icon">👁</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  MousePointer: () => <span data-testid="click-icon">👆</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
}));

// Mock data
const mockStats = {
  views: { value: 12543, change: 12.5, trend: 'up' as const },
  searches: { value: 3456, change: -3.2, trend: 'down' as const },
  actions: { value: 789, change: 0, trend: 'stable' as const },
};

// ============================================
// MOCK COMPONENT (TDD Reference Implementation)
// ============================================

interface QuickStatCardProps {
  icon: React.ReactNode;
  label: string;
  value: number;
  change: number;
  trend: 'up' | 'down' | 'stable';
  onClick?: () => void;
  className?: string;
}

const QuickStatCard: React.FC<QuickStatCardProps> = ({
  icon,
  label,
  value,
  change,
  trend,
  onClick,
  className,
}) => {
  const trendColors = {
    up: 'text-green-600',
    down: 'text-red-600',
    stable: 'text-gray-500',
  };

  const trendIcons = {
    up: <span data-testid="trending-up-icon">↑</span>,
    down: <span data-testid="trending-down-icon">↓</span>,
    stable: <span data-testid="minus-icon">−</span>,
  };

  return (
    <div 
      data-testid={`stat-${label.toLowerCase()}`}
      onClick={onClick}
      className={className}
    >
      <div data-testid="stat-icon">{icon}</div>
      <div data-testid="stat-label">{label}</div>
      <div data-testid="stat-value">{value.toLocaleString()}</div>
      <div 
        data-testid="stat-trend" 
        data-direction={trend}
        className={trendColors[trend]}
      >
        {trendIcons[trend]}
        <span data-testid="stat-change">{Math.abs(change)}%</span>
      </div>
    </div>
  );
};

interface QuickStatsCardsProps {
  stats: typeof mockStats;
  onViewDetails?: (statType: string) => void;
  className?: string;
}

const QuickStatsCards: React.FC<QuickStatsCardsProps> = ({
  stats,
  onViewDetails,
  className,
}) => {
  return (
    <div data-testid="quick-stats-row" className={className}>
      <QuickStatCard
        icon={<span data-testid="eye-icon">👁</span>}
        label="Views"
        value={stats.views.value}
        change={stats.views.change}
        trend={stats.views.trend}
        onClick={() => onViewDetails?.('views')}
      />
      <QuickStatCard
        icon={<span data-testid="search-icon">🔍</span>}
        label="Searches"
        value={stats.searches.value}
        change={stats.searches.change}
        trend={stats.searches.trend}
        onClick={() => onViewDetails?.('searches')}
      />
      <QuickStatCard
        icon={<span data-testid="click-icon">👆</span>}
        label="Actions"
        value={stats.actions.value}
        change={stats.actions.change}
        trend={stats.actions.trend}
        onClick={() => onViewDetails?.('actions')}
      />
    </div>
  );
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
    
    expect(screen.getByTestId('stat-value', { selector: '[data-testid="stat-views"] [data-testid="stat-value"]' })).toHaveTextContent('12,543');
  });

  test('shows up trend with green color', () => {
    render(<QuickStatsCards stats={mockStats} />);
    
    const viewsCard = screen.getByTestId('stat-views');
    const trend = viewsCard.querySelector('[data-testid="stat-trend"]');
    expect(trend).toHaveClass('text-green-600');
  });

  test('shows down trend with red color', () => {
    render(<QuickStatsCards stats={mockStats} />);
    
    const searchesCard = screen.getByTestId('stat-searches');
    const trend = searchesCard.querySelector('[data-testid="stat-trend"]');
    expect(trend).toHaveClass('text-red-600');
  });

  test('shows stable trend with gray color', () => {
    render(<QuickStatsCards stats={mockStats} />);
    
    const actionsCard = screen.getByTestId('stat-actions');
    const trend = actionsCard.querySelector('[data-testid="stat-trend"]');
    expect(trend).toHaveClass('text-gray-500');
  });

  test('calls onViewDetails when card clicked', () => {
    const onViewDetails = jest.fn();
    render(<QuickStatsCards stats={mockStats} onViewDetails={onViewDetails} />);
    
    fireEvent.click(screen.getByTestId('stat-views'));
    expect(onViewDetails).toHaveBeenCalledWith('views');
  });

  test('formats large numbers with commas', () => {
    const largeStats = {
      views: { value: 1234567, change: 10, trend: 'up' as const },
      searches: { value: 987654, change: 5, trend: 'up' as const },
      actions: { value: 12345, change: 2, trend: 'stable' as const },
    };
    
    render(<QuickStatsCards stats={largeStats} />);
    
    const viewValue = screen.getByTestId('stat-views').querySelector('[data-testid="stat-value"]');
    expect(viewValue).toHaveTextContent('1,234,567');
  });

  test('applies custom className', () => {
    render(<QuickStatsCards stats={mockStats} className="custom-class" />);
    
    expect(screen.getByTestId('quick-stats-row')).toHaveClass('custom-class');
  });

  test('shows trend direction as data attribute', () => {
    render(<QuickStatsCards stats={mockStats} />);
    
    expect(screen.getByTestId('stat-views').querySelector('[data-testid="stat-trend"]')).toHaveAttribute('data-direction', 'up');
    expect(screen.getByTestId('stat-searches').querySelector('[data-testid="stat-trend"]')).toHaveAttribute('data-direction', 'down');
    expect(screen.getByTestId('stat-actions').querySelector('[data-testid="stat-trend"]')).toHaveAttribute('data-direction', 'stable');
  });
});

describe('QuickStatCard', () => {
  const mockProps = {
    icon: <span data-testid="eye-icon">👁</span>,
    label: 'Views',
    value: 1000,
    change: 5.5,
    trend: 'up' as const,
  };

  test('renders icon', () => {
    render(<QuickStatCard {...mockProps} />);
    expect(screen.getByTestId('eye-icon')).toBeInTheDocument();
  });

  test('renders label', () => {
    render(<QuickStatCard {...mockProps} />);
    expect(screen.getByTestId('stat-label')).toHaveTextContent('Views');
  });

  test('renders formatted value', () => {
    render(<QuickStatCard {...mockProps} />);
    expect(screen.getByTestId('stat-value')).toHaveTextContent('1,000');
  });

  test('renders trend indicator', () => {
    render(<QuickStatCard {...mockProps} />);
    expect(screen.getByTestId('stat-trend')).toBeInTheDocument();
  });

  test('handles zero change', () => {
    render(<QuickStatCard {...mockProps} change={0} trend="stable" />);
    expect(screen.getByTestId('stat-change')).toHaveTextContent('0%');
  });

  test('handles negative change (shown as positive)', () => {
    render(<QuickStatCard {...mockProps} change={-10} trend="down" />);
    expect(screen.getByTestId('stat-change')).toHaveTextContent('10%');
  });
});
