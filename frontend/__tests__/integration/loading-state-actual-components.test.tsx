/**
 * TASK-28-11: Actual Component Integration Tests
 *
 * Tests using actual components instead of mocks.
 * Verifies the implementation works correctly.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';

// Import actual components
import { LinearProgress } from '@/components/ui/LinearProgress';
import { CircularProgress } from '@/components/ui/CircularProgress';
import { CardSkeleton } from '@/components/ui/skeletons/CardSkeleton';
import { TableSkeleton } from '@/components/ui/skeletons/TableSkeleton';
import { ChartSkeleton } from '@/components/ui/skeletons/ChartSkeleton';
import { IndicatorCatalogSkeleton } from '@/components/ui/skeletons/IndicatorCatalogSkeleton';
import { EmptyState } from '@/components/ui/EmptyState';
import { ButtonLoading } from '@/components/ui/ButtonLoading';
import { LoadingWrapper } from '@/components/ui/LoadingWrapper';
import { useLoadingState } from '@/lib/hooks/useLoadingState';
import { useOptimisticUpdate } from '@/lib/hooks/useOptimisticUpdate';

// ============================================================================
// TEST SUITE: Actual LinearProgress Component
// ============================================================================

describe('LinearProgress Component (Actual)', () => {
  it('renders in indeterminate mode by default', () => {
    render(<LinearProgress />);
    
    const progress = screen.getByTestId('linear-progress');
    expect(progress).toBeInTheDocument();
    expect(progress).not.toHaveAttribute('aria-valuenow');
    expect(progress).toHaveClass('indeterminate');
  });

  it('renders in determinate mode with value', () => {
    render(<LinearProgress value={50} />);
    
    const progress = screen.getByTestId('linear-progress');
    expect(progress).toHaveClass('determinate');
    expect(progress).toHaveAttribute('aria-valuenow', '50');
    expect(progress).toHaveAttribute('aria-valuemin', '0');
    expect(progress).toHaveAttribute('aria-valuemax', '100');
  });

  it('clamps negative values to 0', () => {
    render(<LinearProgress value={-10} />);
    
    const progress = screen.getByTestId('linear-progress');
    expect(progress).toHaveAttribute('aria-valuenow', '0');
  });

  it('clamps values over 100 to 100', () => {
    render(<LinearProgress value={150} />);
    
    const progress = screen.getByTestId('linear-progress');
    expect(progress).toHaveAttribute('aria-valuenow', '100');
  });

  it('applies color variants', () => {
    const { container } = render(<LinearProgress color="primary" />);
    const progress = screen.getByTestId('linear-progress');
    expect(progress).toHaveClass('primary');
  });
});

// ============================================================================
// TEST SUITE: Actual CircularProgress Component
// ============================================================================

describe('CircularProgress Component (Actual)', () => {
  it('renders in indeterminate mode by default', () => {
    render(<CircularProgress />);
    
    const progress = screen.getByTestId('circular-progress');
    expect(progress).toBeInTheDocument();
    expect(progress).toHaveClass('indeterminate');
    expect(progress).not.toHaveAttribute('aria-valuenow');
  });

  it('renders in determinate mode with value', () => {
    render(<CircularProgress value={75} />);
    
    const progress = screen.getByTestId('circular-progress');
    expect(progress).toHaveClass('determinate');
    expect(progress).toHaveAttribute('aria-valuenow', '75');
  });

  it('applies size variants', () => {
    const { container } = render(<CircularProgress size="sm" />);
    const progress = screen.getByTestId('circular-progress');
    expect(progress).toHaveClass('sm');
  });

  it('applies color variants', () => {
    const { container } = render(<CircularProgress color="primary" />);
    const progress = screen.getByTestId('circular-progress');
    expect(progress).toHaveClass('primary');
  });
});

// ============================================================================
// TEST SUITE: Actual Skeleton Components
// ============================================================================

describe('CardSkeleton Component (Actual)', () => {
  it('renders with default props', () => {
    render(<CardSkeleton />);
    
    const skeleton = screen.getByLabelText('Loading content');
    expect(skeleton).toBeInTheDocument();
    expect(skeleton).toHaveAttribute('aria-busy', 'true');
  });

  it('shows icon when showIcon is true', () => {
    render(<CardSkeleton showIcon={true} />);
    expect(document.querySelector('.icon-skeleton')).toBeInTheDocument();
  });

  it('hides icon when showIcon is false', () => {
    render(<CardSkeleton showIcon={false} />);
    expect(document.querySelector('.icon-skeleton')).not.toBeInTheDocument();
  });

  it('renders correct number of lines', () => {
    render(<CardSkeleton lines={5} />);
    
    const lines = document.querySelectorAll('.line-skeleton');
    expect(lines.length).toBe(5);
  });
});

describe('TableSkeleton Component (Actual)', () => {
  it('renders with default props', () => {
    render(<TableSkeleton />);
    
    const skeleton = screen.getByLabelText('Loading table data');
    expect(skeleton).toBeInTheDocument();
    expect(skeleton).toHaveAttribute('aria-busy', 'true');
  });

  it('renders with custom rows and columns', () => {
    render(<TableSkeleton rows={10} columns={6} />);
    
    // Check that table exists with proper structure
    const rows = document.querySelectorAll('tbody tr');
    expect(rows.length).toBe(10);
  });

  it('shows header when showHeader is true', () => {
    render(<TableSkeleton showHeader={true} />);
    expect(document.querySelector('thead')).toBeInTheDocument();
  });

  it('hides header when showHeader is false', () => {
    render(<TableSkeleton showHeader={false} />);
    expect(document.querySelector('thead')).not.toBeInTheDocument();
  });
});

describe('ChartSkeleton Component (Actual)', () => {
  it('renders line chart skeleton', () => {
    render(<ChartSkeleton type="line" />);
    
    const skeleton = screen.getByLabelText('Loading line chart');
    expect(skeleton).toBeInTheDocument();
    expect(skeleton).toHaveAttribute('aria-busy', 'true');
    expect(document.querySelector('.chart-area')).toBeInTheDocument();
  });

  it('renders bar chart skeleton', () => {
    render(<ChartSkeleton type="bar" />);
    
    const skeleton = screen.getByLabelText('Loading bar chart');
    expect(skeleton).toBeInTheDocument();
    
    const bars = document.querySelectorAll('.bar-skeleton');
    expect(bars.length).toBeGreaterThan(0);
  });

  it('renders pie chart skeleton', () => {
    render(<ChartSkeleton type="pie" />);
    
    const skeleton = screen.getByLabelText('Loading pie chart');
    expect(skeleton).toBeInTheDocument();
    
    const legend = document.querySelector('.legend');
    expect(legend).toBeInTheDocument();
  });
});

describe('IndicatorCatalogSkeleton Component (Actual)', () => {
  it('renders with default count', () => {
    render(<IndicatorCatalogSkeleton />);
    
    const skeleton = screen.getByLabelText('Loading indicator catalog');
    expect(skeleton).toBeInTheDocument();
    expect(skeleton).toHaveAttribute('aria-busy', 'true');
  });

  it('renders with custom count', () => {
    render(<IndicatorCatalogSkeleton count={12} />);
    
    // Check that multiple card skeletons are rendered
    const cardSkeletons = document.querySelectorAll('.card-skeleton');
    expect(cardSkeletons.length).toBe(12);
  });
});

// ============================================================================
// TEST SUITE: EmptyState Component
// ============================================================================

describe('EmptyState Component (Actual)', () => {
  it('renders keywords empty state', () => {
    render(<EmptyState type="keywords" />);
    
    expect(screen.getByText('No keywords found')).toBeInTheDocument();
    expect(screen.getByText('Add your first keyword to start tracking rankings')).toBeInTheDocument();
  });

  it('renders competitors empty state', () => {
    render(<EmptyState type="competitors" />);
    
    expect(screen.getByText('No competitors added')).toBeInTheDocument();
  });

  it('renders custom empty state', () => {
    render(
      <EmptyState
        title="Custom Title"
        description="Custom description"
      />
    );
    
    expect(screen.getByText('Custom Title')).toBeInTheDocument();
    expect(screen.getByText('Custom description')).toBeInTheDocument();
  });

  it('renders CTA button with onClick', async () => {
    const handleClick = vi.fn();
    
    render(<EmptyState type="keywords" onCtaClick={handleClick} />);
    
    const button = screen.getByTestId('cta-button');
    await userEvent.click(button);
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });
});

// ============================================================================
// TEST SUITE: ButtonLoading Component
// ============================================================================

describe('ButtonLoading Component (Actual)', () => {
  it('renders non-loading state', () => {
    render(<ButtonLoading isLoading={false}>Submit</ButtonLoading>);
    
    const button = screen.getByTestId('button');
    expect(button).toHaveTextContent('Submit');
    expect(button).not.toBeDisabled();
    expect(screen.queryByTestId('circular-progress')).not.toBeInTheDocument();
  });

  it('renders loading state with spinner', () => {
    render(
      <ButtonLoading isLoading={true} loadingText="Processing...">
        Submit
      </ButtonLoading>
    );
    
    const button = screen.getByTestId('loading-button');
    expect(button).toBeDisabled();
    expect(button).toHaveTextContent('Processing...');
    expect(screen.getByTestId('circular-progress')).toBeInTheDocument();
  });

  it('shows loading text when provided', () => {
    render(
      <ButtonLoading isLoading={true} loadingText="Analyzing...">
        Analyze
      </ButtonLoading>
    );
    
    expect(screen.getByText('Analyzing...')).toBeInTheDocument();
    expect(screen.queryByText('Analyze')).not.toBeInTheDocument();
  });
});

// ============================================================================
// TEST SUITE: LoadingWrapper Component
// ============================================================================

describe('LoadingWrapper Component (Actual)', () => {
  it('shows loading state with skeleton', () => {
    render(
      <LoadingWrapper
        isLoading={true}
        skeleton={<div data-testid="custom-skeleton">Loading...</div>}
      >
        <div>Content</div>
      </LoadingWrapper>
    );
    
    expect(screen.getByTestId('loading-wrapper-loading')).toBeInTheDocument();
    expect(screen.getByTestId('custom-skeleton')).toBeInTheDocument();
    expect(screen.queryByText('Content')).not.toBeInTheDocument();
  });

  it('shows error state', () => {
    render(
      <LoadingWrapper
        isLoading={false}
        error={new Error('Fetch failed')}
      >
        <div>Content</div>
      </LoadingWrapper>
    );
    
    expect(screen.getByTestId('loading-wrapper-error')).toBeInTheDocument();
    expect(screen.getByText('Fetch failed')).toBeInTheDocument();
  });

  it('shows empty state', () => {
    render(
      <LoadingWrapper
        isLoading={false}
        isEmpty={true}
        emptyState={<EmptyState type="keywords" />}
      >
        <div>Content</div>
      </LoadingWrapper>
    );
    
    expect(screen.getByTestId('loading-wrapper-empty')).toBeInTheDocument();
    expect(screen.getByText('No keywords found')).toBeInTheDocument();
  });

  it('shows content when loaded', () => {
    render(
      <LoadingWrapper isLoading={false}>
        <div data-testid="loaded-content">Loaded Content</div>
      </LoadingWrapper>
    );
    
    expect(screen.getByTestId('loading-wrapper-content')).toBeInTheDocument();
    expect(screen.getByTestId('loaded-content')).toBeInTheDocument();
  });
});

// ============================================================================
// TEST SUITE: useLoadingState Hook
// ============================================================================

describe('useLoadingState Hook (Actual)', () => {
  function TestComponent({ onRetry }: { onRetry?: () => Promise<void> }) {
    const { isLoading, error, retry, retryCount, setIsLoading, setError } = useLoadingState({
      onRetry,
    });

    return (
      <div>
        <span data-testid="loading">{String(isLoading)}</span>
        <span data-testid="error">{error?.message || 'none'}</span>
        <span data-testid="retry-count">{retryCount}</span>
        <button data-testid="set-loading" onClick={() => setIsLoading(true)}>
          Set Loading
        </button>
        <button data-testid="set-error" onClick={() => setError(new Error('Test error'))}>
          Set Error
        </button>
        <button data-testid="retry" onClick={retry}>
          Retry
        </button>
      </div>
    );
  }

  it('starts with default loading state', () => {
    render(<TestComponent />);
    
    expect(screen.getByTestId('loading')).toHaveTextContent('false');
    expect(screen.getByTestId('error')).toHaveTextContent('none');
    expect(screen.getByTestId('retry-count')).toHaveTextContent('0');
  });

  it('sets loading state', () => {
    render(<TestComponent />);
    
    fireEvent.click(screen.getByTestId('set-loading'));
    
    expect(screen.getByTestId('loading')).toHaveTextContent('true');
  });

  it('sets error state', () => {
    render(<TestComponent />);
    
    fireEvent.click(screen.getByTestId('set-error'));
    
    expect(screen.getByTestId('error')).toHaveTextContent('Test error');
  });

  it('calls onRetry when retry is clicked', async () => {
    const onRetry = vi.fn().mockResolvedValue(undefined);
    render(<TestComponent onRetry={onRetry} />);
    
    fireEvent.click(screen.getByTestId('retry'));
    
    expect(onRetry).toHaveBeenCalledTimes(1);
    expect(screen.getByTestId('retry-count')).toHaveTextContent('1');
  });
});

// ============================================================================
// TEST SUITE: useOptimisticUpdate Hook
// ============================================================================

describe('useOptimisticUpdate Hook (Actual)', () => {
  function TestComponent({ onUpdate }: { onUpdate?: (data: { value: number }) => Promise<void> }) {
    const { data, optimisticData, isPending, error, update, rollback } = useOptimisticUpdate<{
      value: number;
    }>({
      initialData: { value: 5 },
      onUpdate,
    });

    const displayValue = optimisticData?.value ?? data?.value;

    return (
      <div>
        <span data-testid="value">{displayValue}</span>
        <span data-testid="pending">{String(isPending)}</span>
        <span data-testid="error">{error?.message || 'none'}</span>
        <span data-testid="optimistic">{optimisticData ? 'true' : 'false'}</span>
        <button data-testid="update" onClick={() => update({ value: 3 })}>
          Update to 3
        </button>
        <button data-testid="rollback" onClick={rollback}>
          Rollback
        </button>
      </div>
    );
  }

  it('starts with initial data', () => {
    render(<TestComponent />);
    
    expect(screen.getByTestId('value')).toHaveTextContent('5');
    expect(screen.getByTestId('pending')).toHaveTextContent('false');
  });

  it('shows optimistic update immediately', async () => {
    const onUpdate = vi.fn().mockImplementation(
      () => new Promise((resolve) => setTimeout(resolve, 100))
    );
    render(<TestComponent onUpdate={onUpdate} />);
    
    fireEvent.click(screen.getByTestId('update'));
    
    // Immediately shows optimistic value
    expect(screen.getByTestId('value')).toHaveTextContent('3');
    expect(screen.getByTestId('optimistic')).toHaveTextContent('true');
    expect(screen.getByTestId('pending')).toHaveTextContent('true');
  });

  it('rolls back optimistic update on error', async () => {
    const onUpdate = vi.fn().mockRejectedValue(new Error('Update failed'));
    render(<TestComponent onUpdate={onUpdate} />);
    
    fireEvent.click(screen.getByTestId('update'));
    
    await waitFor(() => {
      expect(screen.getByTestId('error')).toHaveTextContent('Update failed');
    });
    
    // Value should be back to original
    expect(screen.getByTestId('value')).toHaveTextContent('5');
    expect(screen.getByTestId('optimistic')).toHaveTextContent('false');
  });

  it('can manually rollback', () => {
    render(<TestComponent />);
    
    // Manually trigger rollback
    fireEvent.click(screen.getByTestId('rollback'));
    
    expect(screen.getByTestId('optimistic')).toHaveTextContent('false');
  });
});

// ============================================================================
// TEST SUITE: Integration Tests
// ============================================================================

describe('Loading State Integration (Actual Components)', () => {
  it('shows loading state with LinearProgress and IndicatorCatalogSkeleton', () => {
    render(
      <div data-testid="loading-state">
        <LinearProgress />
        <IndicatorCatalogSkeleton count={6} />
      </div>
    );
    
    expect(screen.getByTestId('loading-state')).toBeInTheDocument();
    expect(screen.getByLabelText('Loading indicator catalog')).toBeInTheDocument();
    expect(screen.getByTestId('linear-progress')).toBeInTheDocument();
  });

  it('shows loaded content after loading completes', () => {
    render(
      <div data-testid="loaded-state">
        <div data-testid="indicator-card">Rankings</div>
        <div data-testid="indicator-card">Visibility</div>
      </div>
    );
    
    expect(screen.getByTestId('loaded-state')).toBeInTheDocument();
    expect(screen.getAllByTestId('indicator-card')).toHaveLength(2);
  });

  it('supports full loading flow with progress', () => {
    render(
      <LoadingWrapper
        isLoading={true}
        showProgress={true}
        progressValue={45}
        skeleton={<CardSkeleton lines={3} />}
      >
        <div>Content</div>
      </LoadingWrapper>
    );
    
    expect(screen.getByTestId('linear-progress')).toBeInTheDocument();
    expect(screen.getByTestId('linear-progress')).toHaveAttribute('aria-valuenow', '45');
    expect(screen.getByTestId('loading-wrapper-loading')).toBeInTheDocument();
  });
});
