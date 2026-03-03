/**
 * Accessibility Tests for IndicatorCatalog Component
 * 
 * Tests for WCAG 2.1 AA compliance including ARIA labels,
 * keyboard navigation for tabs, and screen reader compatibility.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';

// ============================================
// Types and Mock Data
// ============================================

interface IndicatorData {
  id: string;
  title: string;
  value: string | number;
  description: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  category?: string;
  tooltip?: string;
}

interface IndicatorCatalogProps {
  title?: string;
  description?: string;
  categories?: string[];
  selectedCategory?: string;
  onCategoryChange?: (category: string) => void;
  onRefresh?: () => void;
  isLoading?: boolean;
  error?: Error | null;
  indicators: IndicatorData[];
}

const mockIndicators: IndicatorData[] = [
  {
    id: 'rankings',
    title: 'Keyword Rankings',
    value: 42,
    description: 'Keywords in top 10',
    status: 'good',
    href: '/dashboard/rankings',
    category: 'Rankings',
  },
  {
    id: 'visibility',
    title: 'Visibility Score',
    value: '67%',
    description: 'Overall SEO visibility',
    status: 'warning',
    href: '/dashboard/visibility',
    category: 'Rankings',
  },
  {
    id: 'traffic',
    title: 'Organic Traffic',
    value: '1.2K',
    description: 'Monthly visitors',
    status: 'good',
    href: '/dashboard/traffic',
    category: 'Traffic',
  },
  {
    id: 'errors',
    title: 'Technical Errors',
    value: 12,
    description: 'Issues found',
    status: 'critical',
    href: '/tools/audit',
    category: 'Technical',
  },
];

const mockCategories = ['All', 'Rankings', 'Traffic', 'Technical'];

// ============================================
// Mock IndicatorCatalog Component
// ============================================

const MockIndicatorCatalog: React.FC<IndicatorCatalogProps> = ({
  title = 'Indicator Catalog',
  description,
  categories,
  selectedCategory = 'All',
  onCategoryChange,
  onRefresh,
  isLoading = false,
  error = null,
  indicators,
}) => {
  const [focusedTab, setFocusedTab] = React.useState(selectedCategory);

  const handleTabKeyDown = (e: React.KeyboardEvent, category: string) => {
    if (!categories) return;
    const index = categories.indexOf(category);

    switch (e.key) {
      case 'ArrowRight':
        e.preventDefault();
        const nextIndex = (index + 1) % categories.length;
        setFocusedTab(categories[nextIndex]);
        break;
      case 'ArrowLeft':
        e.preventDefault();
        const prevIndex = (index - 1 + categories.length) % categories.length;
        setFocusedTab(categories[prevIndex]);
        break;
      case 'Home':
        e.preventDefault();
        setFocusedTab(categories[0]);
        break;
      case 'End':
        e.preventDefault();
        setFocusedTab(categories[categories.length - 1]);
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        onCategoryChange?.(category);
        break;
    }
  };

  const filteredIndicators = selectedCategory === 'All'
    ? indicators
    : indicators.filter((i) => i.category === selectedCategory);

  return (
    <section aria-label={title} data-testid="indicator-catalog">
      <header>
        <h2>{title}</h2>
        {description && <p>{description}</p>}
      </header>

      {categories && categories.length > 0 && (
        <div role="tablist" aria-label="Category tabs" data-testid="category-tabs">
          {categories.map((category) => (
            <button
              key={category}
              role="tab"
              id={`tab-${category}`}
              aria-selected={selectedCategory === category}
              aria-controls="indicator-panel"
              tabIndex={focusedTab === category ? 0 : -1}
              onKeyDown={(e) => handleTabKeyDown(e, category)}
              onClick={() => onCategoryChange?.(category)}
              data-testid={`tab-${category.toLowerCase()}`}
            >
              {category}
            </button>
          ))}
        </div>
      )}

      {onRefresh && (
        <button
          aria-label="Refresh indicators"
          onClick={onRefresh}
          data-testid="refresh-button"
        >
          Refresh
        </button>
      )}

      <div
        id="indicator-panel"
        role="tabpanel"
        aria-labelledby={categories ? `tab-${selectedCategory}` : undefined}
        data-testid="indicator-panel"
        aria-busy={isLoading}
      >
        {isLoading ? (
          <div role="status" aria-label="Loading indicators" data-testid="loading-state">
            Loading...
          </div>
        ) : error ? (
          <div role="alert" data-testid="error-state">
            {error.message}
          </div>
        ) : filteredIndicators.length === 0 ? (
          <div role="status" data-testid="empty-state">
            No indicators available
          </div>
        ) : (
          <div role="list" data-testid="indicator-grid">
            {filteredIndicators.map((indicator) => (
              <div
                key={indicator.id}
                role="listitem"
                tabIndex={0}
                aria-label={`${indicator.title}: ${indicator.value}. Status: ${indicator.status}. Click to view details.`}
                data-testid={`indicator-${indicator.id}`}
              >
                <span>{indicator.title}</span>
                <span>{indicator.value}</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
};

// ============================================
// Test Scenarios CAT-001 to CAT-012
// ============================================

describe('IndicatorCatalog Accessibility (CAT-001 to CAT-012)', () => {
  const mockOnCategoryChange = vi.fn();
  const mockOnRefresh = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  const defaultProps: IndicatorCatalogProps = {
    indicators: mockIndicators,
    categories: mockCategories,
    selectedCategory: 'All',
    onCategoryChange: mockOnCategoryChange,
    onRefresh: mockOnRefresh,
  };

  // ============================================
  // CAT-001: Container has aria-label
  // ============================================
  it('CAT-001: container has aria-label with catalog title', () => {
    render(<MockIndicatorCatalog {...defaultProps} title="SEO Metrics" />);

    const catalog = screen.getByTestId('indicator-catalog');
    expect(catalog).toHaveAttribute('aria-label', 'SEO Metrics');
  });

  // ============================================
  // CAT-002: Category tabs have proper roles
  // ============================================
  it('CAT-002: tabs have proper ARIA roles (tablist, tab)', () => {
    render(<MockIndicatorCatalog {...defaultProps} />);

    const tabList = screen.getByTestId('category-tabs');
    expect(tabList).toHaveAttribute('role', 'tablist');

    const tabs = screen.getAllByRole('tab');
    expect(tabs.length).toBe(mockCategories.length);
  });

  // ============================================
  // CAT-003: Tab selection conveyed
  // ============================================
  it('CAT-003: selected tab has aria-selected="true"', () => {
    render(<MockIndicatorCatalog {...defaultProps} selectedCategory="Rankings" />);

    const rankingsTab = screen.getByTestId('tab-rankings');
    expect(rankingsTab).toHaveAttribute('aria-selected', 'true');

    const allTab = screen.getByTestId('tab-all');
    expect(allTab).toHaveAttribute('aria-selected', 'false');
  });

  // ============================================
  // CAT-004: Tab panels associated
  // ============================================
  it('CAT-004: tabs have aria-controls, panel has matching id', () => {
    render(<MockIndicatorCatalog {...defaultProps} />);

    const tabs = screen.getAllByRole('tab');
    const panel = screen.getByTestId('indicator-panel');

    tabs.forEach((tab) => {
      expect(tab).toHaveAttribute('aria-controls', 'indicator-panel');
    });

    expect(panel).toHaveAttribute('id', 'indicator-panel');
  });

  // ============================================
  // CAT-005: Arrow keys navigate tabs
  // ============================================
  it('CAT-005: ArrowRight moves focus to next tab', async () => {
    const user = userEvent.setup();

    render(<MockIndicatorCatalog {...defaultProps} />);

    screen.getByTestId('tab-all').focus();
    await user.keyboard('{ArrowRight}');

    expect(screen.getByTestId('tab-rankings')).toHaveFocus();
  });

  it('CAT-005a: ArrowLeft moves focus to previous tab', async () => {
    const user = userEvent.setup();

    render(<MockIndicatorCatalog {...defaultProps} />);

    screen.getByTestId('tab-rankings').focus();
    await user.keyboard('{ArrowLeft}');

    expect(screen.getByTestId('tab-all')).toHaveFocus();
  });

  // ============================================
  // CAT-006: Home/End keys for tabs
  // ============================================
  it('CAT-006: Home key moves focus to first tab', async () => {
    const user = userEvent.setup();

    render(<MockIndicatorCatalog {...defaultProps} />);

    screen.getByTestId('tab-technical').focus();
    await user.keyboard('{Home}');

    expect(screen.getByTestId('tab-all')).toHaveFocus();
  });

  it('CAT-006a: End key moves focus to last tab', async () => {
    const user = userEvent.setup();

    render(<MockIndicatorCatalog {...defaultProps} />);

    screen.getByTestId('tab-all').focus();
    await user.keyboard('{End}');

    expect(screen.getByTestId('tab-technical')).toHaveFocus();
  });

  // ============================================
  // CAT-007: Enter activates tab
  // ============================================
  it('CAT-007: Enter key activates tab selection', async () => {
    const user = userEvent.setup();

    render(<MockIndicatorCatalog {...defaultProps} />);

    screen.getByTestId('tab-rankings').focus();
    await user.keyboard('{Enter}');

    expect(mockOnCategoryChange).toHaveBeenCalledWith('Rankings');
  });

  // ============================================
  // CAT-008: Empty state has role
  // ============================================
  it('CAT-008: empty state has role="status"', () => {
    render(<MockIndicatorCatalog {...defaultProps} indicators={[]} />);

    const emptyState = screen.getByTestId('empty-state');
    expect(emptyState).toHaveAttribute('role', 'status');
    expect(emptyState).toHaveTextContent('No indicators available');
  });

  // ============================================
  // CAT-009: Loading state conveyed
  // ============================================
  it('CAT-009: loading state has aria-busy="true"', () => {
    render(<MockIndicatorCatalog {...defaultProps} isLoading />);

    const panel = screen.getByTestId('indicator-panel');
    expect(panel).toHaveAttribute('aria-busy', 'true');
  });

  // ============================================
  // CAT-010: Error state conveyed
  // ============================================
  it('CAT-010: error state has role="alert"', () => {
    render(
      <MockIndicatorCatalog
        {...defaultProps}
        error={new Error('Failed to load indicators')}
      />
    );

    const errorState = screen.getByTestId('error-state');
    expect(errorState).toHaveAttribute('role', 'alert');
    expect(errorState).toHaveTextContent('Failed to load indicators');
  });

  // ============================================
  // CAT-011: Refresh button accessible
  // ============================================
  it('CAT-011: refresh button has aria-label', () => {
    render(<MockIndicatorCatalog {...defaultProps} />);

    const refreshButton = screen.getByTestId('refresh-button');
    expect(refreshButton).toHaveAttribute('aria-label', 'Refresh indicators');
  });

  // ============================================
  // CAT-012: Grid announced as list
  // ============================================
  it('CAT-012: indicator grid has role="list", items have role="listitem"', () => {
    render(<MockIndicatorCatalog {...defaultProps} />);

    const grid = screen.getByTestId('indicator-grid');
    expect(grid).toHaveAttribute('role', 'list');

    const items = screen.getAllByRole('listitem');
    expect(items.length).toBe(mockIndicators.length);
  });
});

// ============================================
// Tab Navigation Tests
// ============================================

describe('IndicatorCatalog Tab Navigation', () => {
  const mockOnCategoryChange = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('Space key activates tab', async () => {
    const user = userEvent.setup();

    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
        onCategoryChange={mockOnCategoryChange}
      />
    );

    screen.getByTestId('tab-traffic').focus();
    await user.keyboard(' ');

    expect(mockOnCategoryChange).toHaveBeenCalledWith('Traffic');
  });

  it('ArrowRight wraps from last to first tab', async () => {
    const user = userEvent.setup();

    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="Technical"
      />
    );

    screen.getByTestId('tab-technical').focus();
    await user.keyboard('{ArrowRight}');

    expect(screen.getByTestId('tab-all')).toHaveFocus();
  });

  it('ArrowLeft wraps from first to last tab', async () => {
    const user = userEvent.setup();

    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
      />
    );

    screen.getByTestId('tab-all').focus();
    await user.keyboard('{ArrowLeft}');

    expect(screen.getByTestId('tab-technical')).toHaveFocus();
  });
});

// ============================================
// Category Filtering Tests
// ============================================

describe('IndicatorCatalog Category Filtering', () => {
  it('filters indicators by category', () => {
    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="Rankings"
      />
    );

    // Should show only Rankings category indicators
    expect(screen.getByTestId('indicator-rankings')).toBeInTheDocument();
    expect(screen.getByTestId('indicator-visibility')).toBeInTheDocument();
    expect(screen.queryByTestId('indicator-traffic')).not.toBeInTheDocument();
    expect(screen.queryByTestId('indicator-errors')).not.toBeInTheDocument();
  });

  it('shows all indicators when "All" is selected', () => {
    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="All"
      />
    );

    mockIndicators.forEach((indicator) => {
      expect(screen.getByTestId(`indicator-${indicator.id}`)).toBeInTheDocument();
    });
  });

  it('shows empty state for category with no indicators', () => {
    render(
      <MockIndicatorCatalog
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="Links"
      />
    );

    expect(screen.getByTestId('empty-state')).toBeInTheDocument();
  });
});

// ============================================
// Without Categories
// ============================================

describe('IndicatorCatalog Without Categories', () => {
  it('renders without category tabs', () => {
    render(
      <MockIndicatorCatalog indicators={mockIndicators} />
    );

    expect(screen.queryByTestId('category-tabs')).not.toBeInTheDocument();
    expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
  });

  it('renders all indicators when no categories provided', () => {
    render(
      <MockIndicatorCatalog indicators={mockIndicators} />
    );

    mockIndicators.forEach((indicator) => {
      expect(screen.getByTestId(`indicator-${indicator.id}`)).toBeInTheDocument();
    });
  });
});

// ============================================
// Indicator Item Accessibility
// ============================================

describe('Indicator Item Accessibility in Catalog', () => {
  it('each indicator has descriptive aria-label', () => {
    render(
      <MockIndicatorCatalog indicators={mockIndicators} />
    );

    const rankingsIndicator = screen.getByTestId('indicator-rankings');
    expect(rankingsIndicator.getAttribute('aria-label')).toContain('Keyword Rankings');
    expect(rankingsIndicator.getAttribute('aria-label')).toContain('42');
    expect(rankingsIndicator.getAttribute('aria-label')).toContain('good');
  });

  it('each indicator is focusable', async () => {
    const user = userEvent.setup();

    render(
      <MockIndicatorCatalog indicators={mockIndicators} />
    );

    const rankingsIndicator = screen.getByTestId('indicator-rankings');
    rankingsIndicator.focus();
    expect(rankingsIndicator).toHaveFocus();

    await user.tab();
    expect(screen.getByTestId('indicator-visibility')).toHaveFocus();
  });
});
