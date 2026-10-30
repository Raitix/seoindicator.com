/**
 * TASK-35-19: InsightsSection Component Tests
 * 
 * Tests for the GBP Insights section with metrics cards and sparklines.
 * 
 * @see PROJECT-SEOINDICATOR/TESTS/TEST-35-19.md
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Eye: () => <span data-testid="eye-icon">👁</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  MousePointer: () => <span data-testid="click-icon">👆</span>,
  ChevronRight: () => <span data-testid="chevron-icon">›</span>,
}));

// Mock Recharts
jest.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: any) => (
    <div data-testid="sparkline-container">{children}</div>
  ),
  AreaChart: ({ data }: any) => (
    <div data-testid="area-chart" data-points={data?.length || 0} />
  ),
  Area: ({ dataKey }: any) => (
    <div data-testid={`area-${dataKey}`} />
  ),
}));

// Mock data
const mockInsights = {
  views: {
    total: 12543,
    breakdown: { search: 8234, maps: 3512, other: 797 },
    trend: [
      { date: '2026-03-01', value: 1680 },
      { date: '2026-03-02', value: 1720 },
      { date: '2026-03-03', value: 1690 },
      { date: '2026-03-04', value: 1810 },
      { date: '2026-03-05', value: 1750 },
      { date: '2026-03-06', value: 1890 },
      { date: '2026-03-07', value: 2003 },
    ],
  },
  searches: {
    total: 3456,
    breakdown: { direct: 1234, discovery: 1890, branded: 332 },
    trend: Array(7).fill(null).map((_, i) => ({ date: `2026-03-0${i + 1}`, value: 490 + i * 10 })),
  },
  actions: {
    total: 789,
    breakdown: { website: 345, directions: 234, call: 156, other: 54 },
    trend: Array(7).fill(null).map((_, i) => ({ date: `2026-03-0${i + 1}`, value: 105 + i * 3 })),
  },
};

// ============================================
// MOCK COMPONENT (TDD Reference Implementation)
// ============================================

interface InsightsSectionProps {
  insights: typeof mockInsights;
  onViewDetails?: () => void;
  className?: string;
}

const InsightsSection: React.FC<InsightsSectionProps> = ({
  insights,
  onViewDetails,
  className,
}) => {
  return (
    <section data-testid="insights-section" className={className}>
      <h2>GBP Insights</h2>
      
      <div data-testid="insights-metrics">
        {/* Views Card */}
        <div data-testid="insights-views">
          <span data-testid="eye-icon">👁</span>
          <span data-testid="views-label">Views</span>
          <span data-testid="views-total">{insights.views.total.toLocaleString()}</span>
          <div data-testid="views-breakdown">
            <span data-testid="views-search">{insights.views.breakdown.search.toLocaleString()} Search</span>
            <span data-testid="views-maps">{insights.views.breakdown.maps.toLocaleString()} Maps</span>
          </div>
          <div data-testid="area-chart" data-points={insights.views.trend.length} />
        </div>

        {/* Searches Card */}
        <div data-testid="insights-searches">
          <span data-testid="search-icon">🔍</span>
          <span data-testid="searches-label">Searches</span>
          <span data-testid="searches-total">{insights.searches.total.toLocaleString()}</span>
          <div data-testid="searches-breakdown">
            <span data-testid="searches-direct">{insights.searches.breakdown.direct} Direct</span>
            <span data-testid="searches-discovery">{insights.searches.breakdown.discovery} Discovery</span>
            <span data-testid="searches-branded">{insights.searches.breakdown.branded} Branded</span>
          </div>
          <div data-testid="area-chart" data-points={insights.searches.trend.length} />
        </div>

        {/* Actions Card */}
        <div data-testid="insights-actions">
          <span data-testid="click-icon">👆</span>
          <span data-testid="actions-label">Actions</span>
          <span data-testid="actions-total">{insights.actions.total.toLocaleString()}</span>
          <div data-testid="actions-breakdown">
            <span data-testid="actions-website">{insights.actions.breakdown.website} Website</span>
            <span data-testid="actions-directions">{insights.actions.breakdown.directions} Directions</span>
            <span data-testid="actions-call">{insights.actions.breakdown.call} Calls</span>
          </div>
          <div data-testid="area-chart" data-points={insights.actions.trend.length} />
        </div>
      </div>

      {onViewDetails && (
        <button 
          data-testid="view-insights-link"
          onClick={onViewDetails}
        >
          View detailed insights
          <span data-testid="chevron-icon">›</span>
        </button>
      )}
    </section>
  );
};

// ============================================
// TESTS
// ============================================

describe('InsightsSection', () => {
  test('renders section with header', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.getByTestId('insights-section')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /gbp insights/i })).toBeInTheDocument();
  });

  test('renders all three metric cards', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.getByTestId('insights-views')).toBeInTheDocument();
    expect(screen.getByTestId('insights-searches')).toBeInTheDocument();
    expect(screen.getByTestId('insights-actions')).toBeInTheDocument();
  });

  test('displays views total and breakdown', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.getByTestId('views-total')).toHaveTextContent('12,543');
    expect(screen.getByTestId('views-search')).toHaveTextContent('8,234 Search');
    expect(screen.getByTestId('views-maps')).toHaveTextContent('3,512 Maps');
  });

  test('displays searches total and breakdown', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.getByTestId('searches-total')).toHaveTextContent('3,456');
    expect(screen.getByTestId('searches-direct')).toHaveTextContent('1,234 Direct');
    expect(screen.getByTestId('searches-discovery')).toHaveTextContent('1,890 Discovery');
    expect(screen.getByTestId('searches-branded')).toHaveTextContent('332 Branded');
  });

  test('displays actions total and breakdown', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.getByTestId('actions-total')).toHaveTextContent('789');
    expect(screen.getByTestId('actions-website')).toHaveTextContent('345 Website');
    expect(screen.getByTestId('actions-directions')).toHaveTextContent('234 Directions');
    expect(screen.getByTestId('actions-call')).toHaveTextContent('156 Calls');
  });

  test('renders sparklines for each metric', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    const charts = screen.getAllByTestId('area-chart');
    expect(charts).toHaveLength(3);
  });

  test('sparklines show 7 data points', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    const charts = screen.getAllByTestId('area-chart');
    charts.forEach(chart => {
      expect(chart).toHaveAttribute('data-points', '7');
    });
  });

  test('calls onViewDetails when link clicked', () => {
    const onViewDetails = jest.fn();
    render(<InsightsSection insights={mockInsights} onViewDetails={onViewDetails} />);
    
    fireEvent.click(screen.getByTestId('view-insights-link'));
    expect(onViewDetails).toHaveBeenCalledTimes(1);
  });

  test('hides view details link when onViewDetails not provided', () => {
    render(<InsightsSection insights={mockInsights} />);
    
    expect(screen.queryByTestId('view-insights-link')).not.toBeInTheDocument();
  });

  test('applies custom className', () => {
    render(<InsightsSection insights={mockInsights} className="custom-class" />);
    
    expect(screen.getByTestId('insights-section')).toHaveClass('custom-class');
  });

  test('handles zero values gracefully', () => {
    const zeroInsights = {
      views: { total: 0, breakdown: { search: 0, maps: 0, other: 0 }, trend: [] },
      searches: { total: 0, breakdown: { direct: 0, discovery: 0, branded: 0 }, trend: [] },
      actions: { total: 0, breakdown: { website: 0, directions: 0, call: 0, other: 0 }, trend: [] },
    };
    
    render(<InsightsSection insights={zeroInsights} />);
    
    expect(screen.getByTestId('views-total')).toHaveTextContent('0');
    expect(screen.getByTestId('searches-total')).toHaveTextContent('0');
    expect(screen.getByTestId('actions-total')).toHaveTextContent('0');
  });

  test('formats large numbers correctly', () => {
    const largeInsights = {
      views: { total: 1234567, breakdown: { search: 800000, maps: 400000, other: 34567 }, trend: mockInsights.views.trend },
      searches: { total: 987654, breakdown: { direct: 300000, discovery: 600000, branded: 87654 }, trend: mockInsights.searches.trend },
      actions: { total: 123456, breakdown: { website: 50000, directions: 40000, call: 30000, other: 3456 }, trend: mockInsights.actions.trend },
    };
    
    render(<InsightsSection insights={largeInsights} />);
    
    expect(screen.getByTestId('views-total')).toHaveTextContent('1,234,567');
    expect(screen.getByTestId('searches-total')).toHaveTextContent('987,654');
    expect(screen.getByTestId('actions-total')).toHaveTextContent('123,456');
  });
});
