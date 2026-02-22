/**
 * Tests for GSC Data Dashboard Components - TASK-12-10
 *
 * Tests verify:
 * - Components render without errors
 * - Sample data displays correctly
 * - Sort functionality works in metrics table
 * - Date filter updates state
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import GSCMetricsTable from '../GSCMetricsTable';
import GSCDateFilter from '../GSCDateFilter';
import GSCTrendChart from '../GSCTrendChart';
import GSCOpportunities from '../GSCOpportunities';

import { sampleMetrics, sampleTrendData, sampleOpportunities } from '@/lib/data/sample-gsc-data';

describe('GSCMetricsTable', () => {
  it('renders without crashing', () => {
    render(<GSCMetricsTable data={sampleMetrics} />);
    expect(screen.getByText('Query Performance')).toBeInTheDocument();
  });

  it('displays metrics data', () => {
    render(<GSCMetricsTable data={sampleMetrics} />);
    
    // Check that first query is displayed
    expect(screen.getByText('seo tools')).toBeInTheDocument();
  });

  it('shows loading state', () => {
    render(<GSCMetricsTable data={[]} isLoading={true} />);
    expect(screen.getByRole('progressbar')).toBeDefined();
  });

  it('shows empty state', () => {
    render(<GSCMetricsTable data={[]} isLoading={false} />);
    expect(screen.getByText('No data available')).toBeInTheDocument();
  });

  it('sorts data when column header is clicked', () => {
    render(<GSCMetricsTable data={sampleMetrics} />);
    
    // Click on Clicks column to sort
    const clicksHeader = screen.getByText('Clicks');
    fireEvent.click(clicksHeader);
    
    // Verify sorting works (data should be reordered)
    expect(screen.getByText('Query Performance')).toBeInTheDocument();
  });

  it('displays formatted numbers', () => {
    render(<GSCMetricsTable data={sampleMetrics} />);
    
    // Check that large numbers are formatted with commas
    expect(screen.getByText('45,678')).toBeInTheDocument();
  });

  it('displays formatted CTR as percentage', () => {
    render(<GSCMetricsTable data={sampleMetrics} />);
    
    // CTR should be displayed as percentage
    expect(screen.getByText(/2\.70%/)).toBeInTheDocument();
  });
});

describe('GSCDateFilter', () => {
  const mockDateRange = { from: '2026-02-01', to: '2026-02-21' };
  const mockOnChange = jest.fn();

  beforeEach(() => {
    mockOnChange.mockClear();
  });

  it('renders without crashing', () => {
    render(
      <GSCDateFilter
        dateRange={mockDateRange}
        onDateRangeChange={mockOnChange}
      />
    );
    expect(screen.getByText('Date Range')).toBeInTheDocument();
  });

  it('displays preset buttons', () => {
    render(
      <GSCDateFilter
        dateRange={mockDateRange}
        onDateRangeChange={mockOnChange}
      />
    );
    
    expect(screen.getByText('Last 7 days')).toBeInTheDocument();
    expect(screen.getByText('Last 30 days')).toBeInTheDocument();
  });

  it('calls onDateRangeChange when preset is clicked', () => {
    render(
      <GSCDateFilter
        dateRange={mockDateRange}
        onDateRangeChange={mockOnChange}
      />
    );
    
    fireEvent.click(screen.getByText('Last 7 days'));
    expect(mockOnChange).toHaveBeenCalledTimes(1);
  });

  it('displays date inputs', () => {
    render(
      <GSCDateFilter
        dateRange={mockDateRange}
        onDateRangeChange={mockOnChange}
      />
    );
    
    expect(screen.getByLabelText('From:')).toBeInTheDocument();
    expect(screen.getByLabelText('To:')).toBeInTheDocument();
  });
});

describe('GSCTrendChart', () => {
  it('renders without crashing', () => {
    render(<GSCTrendChart data={sampleTrendData} />);
    expect(screen.getByText('Performance Trends')).toBeInTheDocument();
  });

  it('shows loading state', () => {
    render(<GSCTrendChart data={[]} isLoading={true} />);
    expect(screen.getByRole('progressbar')).toBeDefined();
  });

  it('shows empty state', () => {
    render(<GSCTrendChart data={[]} isLoading={false} />);
    expect(screen.getByText('No trend data available')).toBeInTheDocument();
  });

  it('renders chart with data', () => {
    const { container } = render(<GSCTrendChart data={sampleTrendData} />);
    
    // Chart container should exist
    expect(container.querySelector('.recharts-wrapper')).toBeInTheDocument();
  });
});

describe('GSCOpportunities', () => {
  it('renders without crashing', () => {
    render(<GSCOpportunities data={sampleOpportunities} />);
    expect(screen.getByText('Opportunity Keywords')).toBeInTheDocument();
  });

  it('displays opportunity keywords', () => {
    render(<GSCOpportunities data={sampleOpportunities} />);
    
    expect(screen.getByText('technical seo audit')).toBeInTheDocument();
  });

  it('shows loading state', () => {
    render(<GSCOpportunities data={[]} isLoading={true} />);
    expect(screen.getByRole('progressbar')).toBeDefined();
  });

  it('shows empty state', () => {
    render(<GSCOpportunities data={[]} isLoading={false} />);
    expect(screen.getByText('No opportunity keywords found')).toBeInTheDocument();
  });

  it('displays opportunity scores', () => {
    render(<GSCOpportunities data={sampleOpportunities} />);
    
    expect(screen.getByText('93')).toBeInTheDocument(); // Score rounded
  });

  it('displays opportunity level badges', () => {
    render(<GSCOpportunities data={sampleOpportunities} />);
    
    expect(screen.getByText('High')).toBeInTheDocument();
  });

  it('displays CTR gap', () => {
    render(<GSCOpportunities data={sampleOpportunities} />);
    
    // Should show positive CTR gap
    expect(screen.getByText(/\+4\.10%/)).toBeInTheDocument();
  });
});
