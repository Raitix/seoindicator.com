/**
 * ResultsTable Component Tests - TASK-19-12
 *
 * Unit tests for the ResultsTable and ResultRow components.
 *
 * @see TASK-19-12
 */

import React from 'react';
import { render, screen, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ResultsTable, ResultRow } from '@/components/analysis';
import { IndicatorResult } from '@/lib/types/analysis-job';

// Mock lucide-react icons
jest.mock('lucide-react', () => ({
  ChevronDown: () => <span data-testid="chevron-down" />,
  ChevronRight: () => <span data-testid="chevron-right" />,
  ArrowUpDown: () => <span data-testid="arrow-up-down" />,
  Filter: () => <span data-testid="filter" />,
  CheckCircle: () => <span data-testid="check-circle" />,
  XCircle: () => <span data-testid="x-circle" />,
  List: () => <span data-testid="list" />,
}));

// Mock UI components
jest.mock('@/components/ui/select', () => ({
  Select: ({ children, value, onValueChange }: any) => (
    <div data-testid="select" data-value={value}>
      {React.Children.map(children, (child) =>
        React.cloneElement(child, { value, onValueChange })
      )}
    </div>
  ),
  SelectTrigger: ({ children, 'aria-label': ariaLabel }: any) => (
    <button aria-label={ariaLabel}>{children}</button>
  ),
  SelectValue: ({ placeholder }: any) => <span>{placeholder}</span>,
  SelectContent: ({ children, onValueChange }: any) => (
    <div data-testid="select-content">
      {React.Children.map(children, (child) =>
        React.cloneElement(child, { onValueChange })
      )}
    </div>
  ),
  SelectItem: ({ children, value, onValueChange }: any) => (
    <button
      data-testid={`select-item-${value}`}
      onClick={() => onValueChange?.(value)}
    >
      {children}
    </button>
  ),
}));

jest.mock('@/components/ui/card', () => ({
  Card: ({ children, className }: any) => (
    <div data-testid="card" className={className}>
      {children}
    </div>
  ),
  CardHeader: ({ children, className }: any) => (
    <div data-testid="card-header" className={className}>
      {children}
    </div>
  ),
  CardTitle: ({ children, className }: any) => (
    <h3 data-testid="card-title" className={className}>
      {children}
    </h3>
  ),
  CardContent: ({ children, className }: any) => (
    <div data-testid="card-content" className={className}>
      {children}
    </div>
  ),
}));

// Sample results for testing
const createMockResult = (
  indicator: string,
  status: IndicatorResult['status'],
  percentage: number = 85,
  overrides: Partial<IndicatorResult> = {}
): IndicatorResult => ({
  indicator,
  status,
  score: Math.round(percentage * 0.85),
  max_score: 100,
  percentage,
  data: {},
  recommendations: [],
  ...overrides,
});

const mockResults: IndicatorResult[] = [
  createMockResult('meta_tags', 'completed', 95),
  createMockResult('headings', 'completed', 75),
  createMockResult('images', 'failed', 0, {
    error_message: 'Failed to load images',
  }),
  createMockResult('links', 'running', 50),
  createMockResult('url_structure', 'pending', 0),
];

describe('ResultRow Component', () => {
  it('renders indicator name and status', () => {
    const result = createMockResult('meta_tags', 'completed', 85);

    render(<ResultRow result={result} />);

    expect(screen.getByText('Meta Tags')).toBeInTheDocument();
    expect(screen.getByText('85%')).toBeInTheDocument();
  });

  it('shows status indicator with correct color', () => {
    const completedResult = createMockResult('meta_tags', 'completed', 85);
    const failedResult = createMockResult('images', 'failed', 0);

    const { rerender } = render(<ResultRow result={completedResult} />);
    expect(screen.getByRole('row')).toHaveClass('bg-green-100');

    rerender(<ResultRow result={failedResult} />);
    // Failed status should not have green background
    expect(screen.getByRole('row')).not.toHaveClass('bg-green-100');
  });

  it('displays waiting message for pending status', () => {
    const result = createMockResult('url_structure', 'pending', 0);

    render(<ResultRow result={result} />);

    expect(screen.getByText('Waiting...')).toBeInTheDocument();
  });

  it('displays analyzing message for running status', () => {
    const result = createMockResult('links', 'running', 50);

    render(<ResultRow result={result} />);

    expect(screen.getByText('Analyzing...')).toBeInTheDocument();
  });

  it('calls onClick when clicked', async () => {
    const result = createMockResult('meta_tags', 'completed', 85);
    const handleClick = jest.fn();

    render(<ResultRow result={result} onClick={handleClick} />);

    await userEvent.click(screen.getByRole('row'));

    expect(handleClick).toHaveBeenCalledWith(result);
  });

  it('expands details when toggle button is clicked', async () => {
    const result = createMockResult('meta_tags', 'completed', 85, {
      recommendations: ['Fix meta title', 'Add meta description'],
    });

    const handleToggleExpand = jest.fn();
    const { rerender } = render(
      <ResultRow result={result} onToggleExpand={handleToggleExpand} isExpanded={false} />
    );

    // Details should not be visible initially
    expect(screen.queryByText('Fix meta title')).not.toBeInTheDocument();

    // Click expand button
    await userEvent.click(screen.getByRole('button', { name: /expand/i }));

    // Verify callback was called
    expect(handleToggleExpand).toHaveBeenCalled();

    // Rerender with expanded state
    rerender(
      <ResultRow result={result} onToggleExpand={handleToggleExpand} isExpanded={true} />
    );

    // Details should now be visible
    expect(screen.getByText(/Recommendations/)).toBeInTheDocument();
    expect(screen.getByText('Fix meta title')).toBeInTheDocument();
  });

  it('shows error message for failed results', async () => {
    const result = createMockResult('images', 'failed', 0, {
      error_message: 'Failed to load images',
    });

    render(<ResultRow result={result} isExpanded={true} />);

    expect(screen.getByText(/Failed to load images/)).toBeInTheDocument();
  });

  it('displays data fields in details section', async () => {
    const result = createMockResult('meta_tags', 'completed', 85, {
      data: {
        title: 'Test Page',
        description_length: 150,
        has_og_tags: true,
      },
    });

    render(<ResultRow result={result} isExpanded={true} />);

    // Text is lowercase as formatted by component
    expect(screen.getByText('title:')).toBeInTheDocument();
    expect(screen.getByText('Test Page')).toBeInTheDocument();
    expect(screen.getByText('description length:')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument();
  });

  it('applies correct score color based on percentage', () => {
    const { rerender } = render(
      <ResultRow result={createMockResult('test', 'completed', 95)} />
    );
    expect(screen.getByText('95%')).toHaveClass('text-green-600');

    rerender(<ResultRow result={createMockResult('test', 'completed', 70)} />);
    expect(screen.getByText('70%')).toHaveClass('text-yellow-600');

    rerender(<ResultRow result={createMockResult('test', 'completed', 45)} />);
    expect(screen.getByText('45%')).toHaveClass('text-orange-600');

    rerender(<ResultRow result={createMockResult('test', 'completed', 25)} />);
    expect(screen.getByText('25%')).toHaveClass('text-red-600');
  });

  it('supports keyboard navigation', async () => {
    const result = createMockResult('meta_tags', 'completed', 85);
    const handleClick = jest.fn();

    render(<ResultRow result={result} onClick={handleClick} />);

    const row = screen.getByRole('row');
    row.focus();

    await userEvent.keyboard('{Enter}');

    expect(handleClick).toHaveBeenCalled();
  });
});

describe('ResultsTable Component', () => {
  it('renders all results by default', () => {
    render(<ResultsTable jobId={1} results={mockResults} />);

    expect(screen.getByText('Meta Tags')).toBeInTheDocument();
    expect(screen.getByText('Headings Structure')).toBeInTheDocument();
    expect(screen.getByText('Images')).toBeInTheDocument();
    expect(screen.getByText('Links')).toBeInTheDocument();
    expect(screen.getByText('URL Structure')).toBeInTheDocument();
  });

  it('displays summary stats', () => {
    render(<ResultsTable jobId={1} results={mockResults} />);

    // Stats appear in both visible area and sr-only live region
    expect(screen.getAllByText(/2 completed/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/1 failed/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/2 remaining/).length).toBeGreaterThan(0);
  });

  it('sorts by status (completed first)', async () => {
    render(<ResultsTable jobId={1} results={mockResults} initialSortBy="status" />);

    const rows = screen.getAllByRole('row').slice(1); // Skip header row

    // First two should be completed (alphabetically: Headings, Meta Tags)
    expect(within(rows[0]).getByText('Headings Structure')).toBeInTheDocument();
    expect(within(rows[1]).getByText('Meta Tags')).toBeInTheDocument();

    // Third should be failed (images)
    expect(within(rows[2]).getByText('Images')).toBeInTheDocument();
  });

  it('filters to show only completed results', async () => {
    render(<ResultsTable jobId={1} results={mockResults} />);

    // Click the filter select item for completed
    const completedFilter = screen.getByTestId('select-item-completed');
    await userEvent.click(completedFilter);

    // Should only show completed results
    expect(screen.getByText('Meta Tags')).toBeInTheDocument();
    expect(screen.getByText('Headings Structure')).toBeInTheDocument();
    expect(screen.queryByText('Images')).not.toBeInTheDocument();
  });

  it('filters to show only failed results', async () => {
    render(<ResultsTable jobId={1} results={mockResults} />);

    // Click the filter select item for failed
    const failedFilter = screen.getByTestId('select-item-failed');
    await userEvent.click(failedFilter);

    // Should only show failed results
    expect(screen.getByText('Images')).toBeInTheDocument();
    expect(screen.queryByText('Meta Tags')).not.toBeInTheDocument();
  });

  it('shows empty state when no results match filter', async () => {
    // Results with no failed items
    const noFailedResults = [
      createMockResult('meta_tags', 'completed', 95),
      createMockResult('headings', 'completed', 75),
    ];

    render(<ResultsTable jobId={1} results={noFailedResults} />);

    // Filter to failed
    const failedFilter = screen.getByTestId('select-item-failed');
    await userEvent.click(failedFilter);

    expect(screen.getByText('No failed results.')).toBeInTheDocument();
  });

  it('calls onResultClick when a row is clicked', async () => {
    const handleClick = jest.fn();

    render(
      <ResultsTable jobId={1} results={mockResults} onResultClick={handleClick} />
    );

    await userEvent.click(screen.getByText('Meta Tags'));

    expect(handleClick).toHaveBeenCalledWith(
      expect.objectContaining({ indicator: 'meta_tags' })
    );
  });

  it('hides sort controls when showSortControls is false', () => {
    render(
      <ResultsTable
        jobId={1}
        results={mockResults}
        showSortControls={false}
      />
    );

    expect(screen.queryByTestId('arrow-up-down')).not.toBeInTheDocument();
  });

  it('hides filter controls when showFilterControls is false', () => {
    render(
      <ResultsTable
        jobId={1}
        results={mockResults}
        showFilterControls={false}
      />
    );

    expect(screen.queryByTestId('filter')).not.toBeInTheDocument();
  });

  it('has proper ARIA attributes for accessibility', () => {
    render(<ResultsTable jobId={1} results={mockResults} />);

    // Check table role
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getByLabelText('Analysis results table')).toBeInTheDocument();

    // Check live region
    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('updates displayed results when results prop changes', () => {
    const { rerender } = render(
      <ResultsTable jobId={1} results={mockResults} />
    );

    expect(screen.getByText('Meta Tags')).toBeInTheDocument();

    // Update with different results
    const newResults = [
      createMockResult('security', 'completed', 90),
      createMockResult('accessibility', 'completed', 80),
    ];

    rerender(<ResultsTable jobId={1} results={newResults} />);

    expect(screen.queryByText('Meta Tags')).not.toBeInTheDocument();
    expect(screen.getByText('Security')).toBeInTheDocument();
    expect(screen.getByText('Accessibility')).toBeInTheDocument();
  });
});

describe('ResultsTable Sorting', () => {
  it('sorts by score (lowest first)', async () => {
    render(<ResultsTable jobId={1} results={mockResults} initialSortBy="score" />);

    const rows = screen.getAllByRole('row').slice(1);

    // Score sort: lowest first, failed items included, pending/running at end
    // Order: Images (0% failed), Headings (75%), Meta Tags (95%), then running/pending
    expect(within(rows[0]).getByText('Images')).toBeInTheDocument();
    expect(within(rows[1]).getByText('Headings Structure')).toBeInTheDocument();
    expect(within(rows[2]).getByText('Meta Tags')).toBeInTheDocument();
  });

  it('sorts alphabetically by name', async () => {
    render(<ResultsTable jobId={1} results={mockResults} initialSortBy="name" />);

    const rows = screen.getAllByRole('row').slice(1);

    // Headings, Images, Links, Meta Tags, URL Structure
    const names = rows.map((row) =>
      within(row).getByText(/Headings|Images|Links|Meta Tags|URL Structure/)
    );

    expect(names[0]).toHaveTextContent('Headings Structure');
    expect(names[1]).toHaveTextContent('Images');
    expect(names[2]).toHaveTextContent('Links');
    expect(names[3]).toHaveTextContent('Meta Tags');
    expect(names[4]).toHaveTextContent('URL Structure');
  });
});

describe('ResultsTable Edge Cases', () => {
  it('handles empty results array', () => {
    render(<ResultsTable jobId={1} results={[]} />);

    expect(screen.getByText('No results available yet.')).toBeInTheDocument();
  });

  it('handles results with missing optional fields', () => {
    const minimalResult: IndicatorResult = {
      indicator: 'test',
      status: 'completed',
      score: 50,
      max_score: 100,
      percentage: 50,
      data: {},
      recommendations: [],
    };

    render(<ResultsTable jobId={1} results={[minimalResult]} />);

    expect(screen.getByText('Test')).toBeInTheDocument();
    expect(screen.getByText('50%')).toBeInTheDocument();
  });

  it('handles results with all failed status', () => {
    const allFailed = [
      createMockResult('test1', 'failed', 0),
      createMockResult('test2', 'failed', 0),
    ];

    render(<ResultsTable jobId={1} results={allFailed} />);

    // Use getAllByText since the text appears in multiple places
    expect(screen.getAllByText(/0 completed/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/2 failed/).length).toBeGreaterThan(0);
  });

  it('handles results with all completed status', () => {
    const allCompleted = [
      createMockResult('test1', 'completed', 80),
      createMockResult('test2', 'completed', 90),
    ];

    render(<ResultsTable jobId={1} results={allCompleted} />);

    // Use getAllByText since the text appears in multiple places
    expect(screen.getAllByText(/2 completed/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/0 failed/).length).toBeGreaterThan(0);
    expect(screen.queryByText(/remaining/)).not.toBeInTheDocument();
  });
});
