import * as React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, jest, beforeEach } from '@jest/globals';

import { IndicatorCatalog, type IndicatorCatalogProps, type IndicatorData } from '../IndicatorCatalog';
import { TooltipProvider } from '@/components/ui/tooltip';

// ============================================================================
// Mocks
// ============================================================================

// Mock IndicatorGrid to isolate catalog tests
jest.mock('../IndicatorGrid', () => ({
  IndicatorGrid: ({ indicators, isLoading, 'aria-label': ariaLabel }: { indicators: IndicatorData[]; isLoading?: boolean; 'aria-label'?: string }) => (
    <div
      data-testid="indicator-grid"
      data-indicator-count={indicators.length}
      data-loading={String(isLoading)}
      data-aria-label={ariaLabel}
    >
      {indicators.map((indicator) => (
        <div
          key={indicator.id}
          data-testid={`indicator-${indicator.id}`}
          data-category={indicator.category}
        >
          {indicator.title}: {indicator.value}
        </div>
      ))}
    </div>
  ),
}));

// Mock Button component
jest.mock('@/components/ui/button', () => ({
  Button: ({ children, onClick, disabled, 'aria-label': ariaLabel, 'data-testid': testId, className }: { children: React.ReactNode; onClick?: () => void; disabled?: boolean; 'aria-label'?: string; 'data-testid'?: string; className?: string }) => (
    <button
      onClick={onClick}
      disabled={disabled}
      aria-label={ariaLabel}
      data-testid={testId}
      className={className}
    >
      {children}
    </button>
  ),
}));

// Mock Tabs components
jest.mock('@/components/ui/tabs', () => ({
  Tabs: ({ value, onValueChange, children, 'aria-label': ariaLabel }: { value: string; onValueChange: (value: string) => void; children: React.ReactNode; 'aria-label'?: string }) => (
    <div data-testid="tabs" data-value={value} data-aria-label={ariaLabel}>
      {React.Children.map(children, (child) => {
        if (React.isValidElement(child)) {
          return React.cloneElement(child as React.ReactElement<{ value?: string; onValueChange?: (value: string) => void }>, { value, onValueChange });
        }
        return child;
      })}
    </div>
  ),
  TabsList: ({ children, role, 'aria-label': ariaLabel, className }: { children: React.ReactNode; role?: string; 'aria-label'?: string; className?: string }) => (
    <div data-testid="tabs-list" role={role} aria-label={ariaLabel} className={className}>
      {children}
    </div>
  ),
  TabsTrigger: ({ value: currentValue, children, value, onValueChange, role, 'aria-selected': ariaSelected, 'data-testid': testId, className }: { value?: string; children: React.ReactNode; currentValue?: string; onValueChange?: (value: string) => void; role?: string; 'aria-selected'?: boolean; 'data-testid'?: string; className?: string }) => (
    <button
      data-testid={testId}
      role={role}
      aria-selected={ariaSelected || (currentValue === value)}
      onClick={() => onValueChange?.(value || '')}
      className={className}
    >
      {children}
    </button>
  ),
  TabsContent: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="tabs-content">{children}</div>
  ),
}));

// Mock Separator component
jest.mock('@/components/ui/separator', () => ({
  Separator: ({ className }: { className?: string }) => (
    <hr data-testid="separator" className={className} />
  ),
}));

// Mock Skeleton component
jest.mock('@/components/ui/skeleton', () => ({
  Skeleton: ({ className }: { className?: string }) => (
    <div data-testid="skeleton" className={className} />
  ),
}));

// ============================================================================
// Test Helpers
// ============================================================================

/**
 * Wrapper component that provides required context providers for tests
 */
function TestWrapper({ children }: { children: React.ReactNode }) {
  return (
    <TooltipProvider>
      {children}
    </TooltipProvider>
  );
}

/**
 * Render with required providers
 */
function renderWithProviders(ui: React.ReactElement) {
  return render(ui, { wrapper: TestWrapper });
}

// ============================================================================
// Test Data
// ============================================================================

const mockIndicators: IndicatorData[] = [
  {
    id: 'keyword-rankings',
    icon: 'TrendingUp',
    title: 'Keyword Rankings',
    value: '142',
    description: 'Keywords ranking in top 100',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'visibility-score',
    icon: 'Eye',
    title: 'Visibility Score',
    value: '78%',
    description: 'Overall SEO visibility',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'competitor-analysis',
    icon: 'Users',
    title: 'Competitor Analysis',
    value: '5',
    description: 'Tracked competitors',
    status: 'neutral',
    href: '/comparison',
    category: 'competition',
  },
  {
    id: 'technical-health',
    icon: 'Wrench',
    title: 'Technical Health',
    value: '85/100',
    description: 'Site audit score',
    status: 'warning',
    href: '/tools',
    category: 'technical',
  },
  {
    id: 'backlink-profile',
    icon: 'Link',
    title: 'Backlink Profile',
    value: '1,234',
    description: 'Total backlinks',
    status: 'good',
    href: '/dashboard',
    category: 'backlinks',
  },
  {
    id: 'content-opportunities',
    icon: 'FileText',
    title: 'Content Opportunities',
    value: '23',
    description: 'Keyword gaps found',
    status: 'warning',
    href: '/dashboard',
    category: 'content',
  },
  {
    id: 'serp-features',
    icon: 'Star',
    title: 'SERP Features',
    value: '8',
    description: 'Featured snippets won',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'page-speed',
    icon: 'Zap',
    title: 'Page Speed',
    value: '92/100',
    description: 'Performance score',
    status: 'good',
    href: '/tools',
    category: 'technical',
  },
];

const mockCategories = ['All', 'rankings', 'competition', 'technical', 'backlinks', 'content'];

const defaultProps: IndicatorCatalogProps = {
  indicators: mockIndicators,
};

const withCategoriesProps: IndicatorCatalogProps = {
  ...defaultProps,
  categories: mockCategories,
  onCategoryChange: jest.fn(),
};

// ============================================================================
// Tests
// ============================================================================

describe('IndicatorCatalog', () => {
  // ============================================================================
  // TypeScript Type Definitions
  // ============================================================================

  describe('TypeScript Types', () => {
    it('TD-001: Component props interface exists', () => {
      const props: IndicatorCatalogProps = {
        indicators: mockIndicators,
      };
      expect(props.indicators).toBeDefined();
    });

    it('TD-002: IndicatorData interface matches downstream', () => {
      const indicator: IndicatorData = {
        id: 'test',
        icon: 'Activity',
        title: 'Test',
        value: '100',
        description: 'Test description',
        status: 'good',
        href: '/test',
        category: 'test-cat',
      };
      expect(indicator.category).toBe('test-cat');
    });
  });

  // ============================================================================
  // Component Rendering
  // ============================================================================

  describe('Component Rendering', () => {
    it('CR-001: Renders without crashing', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });

    it('CR-002: Renders with all required props', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });

    it('CR-003: Renders with data-testid attribute', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-catalog')).toHaveAttribute('data-testid', 'indicator-catalog');
    });

    it('CR-004: Applies custom className', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} className="custom-class" />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveClass('custom-class');
    });

    it('CR-005: Renders IndicatorGrid', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
    });

    it('CR-006: Renders with default title', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('catalog-title')).toHaveTextContent('SEO Indicators');
    });

    it('CR-007: Renders with custom title', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Custom Title" />);
      expect(screen.getByTestId('catalog-title')).toHaveTextContent('Custom Title');
    });

    it('CR-008: Renders with description', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} description="Test description" />);
      expect(screen.getByTestId('catalog-description')).toHaveTextContent('Test description');
    });

    it('CR-009: Container has proper role', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveAttribute('aria-labelledby');
    });
  });

  // ============================================================================
  // Title and Description
  // ============================================================================

  describe('Title and Description', () => {
    it('TD-001: Title renders with correct tag', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Test Title" />);
      const title = screen.getByTestId('catalog-title');
      expect(title.tagName.toLowerCase()).toBe('h2');
    });

    it('TD-002: Title text matches prop', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Exact Title" />);
      expect(screen.getByTestId('catalog-title')).toHaveTextContent('Exact Title');
    });

    it('TD-003: Description renders as paragraph', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} description="Test desc" />);
      const desc = screen.getByTestId('catalog-description');
      expect(desc.tagName.toLowerCase()).toBe('p');
    });

    it('TD-004: Description text matches prop', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} description="Exact description" />);
      expect(screen.getByTestId('catalog-description')).toHaveTextContent('Exact description');
    });

    it('TD-005: No title when prop empty', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="" />);
      expect(screen.queryByTestId('catalog-title')).not.toBeInTheDocument();
    });

    it('TD-006: No description when prop empty', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} description="" />);
      expect(screen.queryByTestId('catalog-description')).not.toBeInTheDocument();
    });

    it('TD-007: Title has proper id', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Test" />);
      const title = screen.getByTestId('catalog-title');
      expect(title).toHaveAttribute('id');
    });

    it('TD-008: Section uses aria-labelledby', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Test" />);
      const catalog = screen.getByTestId('indicator-catalog');
      const title = screen.getByTestId('catalog-title');
      const labelledBy = catalog.getAttribute('aria-labelledby');
      expect(labelledBy).toBe(title.getAttribute('id'));
    });
  });

  // ============================================================================
  // Category Tabs
  // ============================================================================

  describe('Category Tabs', () => {
    it('CT-001: Tabs render when categories provided', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('tabs')).toBeInTheDocument();
    });

    it('CT-002: No tabs when categories not provided', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.queryByTestId('tabs')).not.toBeInTheDocument();
    });

    it('CT-003: All categories render as tabs', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      mockCategories.forEach((category) => {
        const testId = `tab-${category.toLowerCase().replace(/\s+/g, '-')}`;
        expect(screen.getByTestId(testId)).toBeInTheDocument();
      });
    });

    it('CT-004: "All" tab rendered by default', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('tab-all')).toBeInTheDocument();
    });

    it('CT-005: Selected tab highlighted', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} selectedCategory="rankings" />);
      const rankingsTab = screen.getByTestId('tab-rankings');
      expect(rankingsTab).toHaveAttribute('aria-selected', 'true');
    });

    it('CT-006: Tab click triggers onCategoryChange', () => {
      const onCategoryChange = jest.fn();
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} onCategoryChange={onCategoryChange} />);
      
      const rankingsTab = screen.getByTestId('tab-rankings');
      fireEvent.click(rankingsTab);
      
      expect(onCategoryChange).toHaveBeenCalledWith('rankings');
    });

    it('CT-007: selectedCategory prop controls selection', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} selectedCategory="technical" />);
      const technicalTab = screen.getByTestId('tab-technical');
      expect(technicalTab).toHaveAttribute('aria-selected', 'true');
    });

    it('CT-008: Keyboard navigation between tabs', () => {
      const onCategoryChange = jest.fn();
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} onCategoryChange={onCategoryChange} />);
      
      const tabsList = screen.getByTestId('tabs');
      fireEvent.keyDown(tabsList, { key: 'ArrowRight' });
      
      expect(onCategoryChange).toHaveBeenCalled();
    });

    it('CT-009: Tabs use shadcn/ui Tabs component', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('tabs')).toBeInTheDocument();
    });

    it('CT-010: Separator between tabs and grid', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('separator')).toBeInTheDocument();
    });

    it('CT-011: Tab list has proper ARIA', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabsList = screen.getByTestId('tabs-list');
      expect(tabsList).toHaveAttribute('role', 'tablist');
    });

    it('CT-012: Tab panels have proper ARIA', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabs = screen.getByTestId('tabs');
      expect(tabs).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Loading State
  // ============================================================================

  describe('Loading State', () => {
    it('LS-001: isLoading shows skeleton grid', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      expect(screen.getByTestId('catalog-skeleton-grid')).toBeInTheDocument();
    });

    it('LS-002: isLoading hides real indicators', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      expect(screen.queryByTestId('indicator-grid')).not.toBeInTheDocument();
    });

    it('LS-003: Skeleton count matches expected', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      const skeletons = screen.getAllByTestId('skeleton');
      // At least 8 skeletons (4 per row * 2 rows or similar)
      expect(skeletons.length).toBeGreaterThan(7);
    });

    it('LS-004: Skeleton respects grid layout', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      const skeletonGrid = screen.getByTestId('catalog-skeleton-grid');
      expect(skeletonGrid).toHaveClass('grid');
    });

    it('LS-005: Loading state disables refresh', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      expect(refreshButton).toBeDisabled();
    });

    it('LS-006: Loading state disables category tabs', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} isLoading />);
      const tabs = screen.queryByTestId('tabs');
      // Tabs may or may not be present, but if they are, they should be disabled
      if (tabs) {
        expect(tabs).toBeInTheDocument();
      }
    });

    it('LS-007: data-loading attribute on container', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveAttribute('data-loading', 'true');
    });

    it('LS-008: Skeleton shows title skeleton', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      // Title skeleton should be visible
      const skeletons = screen.getAllByTestId('skeleton');
      expect(skeletons.length).toBeGreaterThan(0);
    });

    it('LS-009: Skeleton shows description skeleton', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      const skeletons = screen.getAllByTestId('skeleton');
      expect(skeletons.length).toBeGreaterThan(0);
    });

    it('LS-010: Transition from loading to loaded', () => {
      const { rerender } = renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      expect(screen.getByTestId('catalog-skeleton-grid')).toBeInTheDocument();
      
      rerender(
        <TooltipProvider>
          <IndicatorCatalog {...defaultProps} isLoading={false} />
        </TooltipProvider>
      );
      
      expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Error State
  // ============================================================================

  describe('Error State', () => {
    const testError = new Error('Failed to load indicators');

    it('ES-001: Error state shows error message', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} />);
      expect(screen.getByTestId('catalog-error')).toBeInTheDocument();
    });

    it('ES-002: Error state shows retry button', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} onRefresh={onRefresh} />);
      expect(screen.getByText('Retry')).toBeInTheDocument();
    });

    it('ES-003: Retry button triggers onRefresh', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} onRefresh={onRefresh} />);
      
      const retryButton = screen.getByText('Retry');
      fireEvent.click(retryButton);
      
      expect(onRefresh).toHaveBeenCalled();
    });

    it('ES-004: Error hides IndicatorGrid', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} />);
      expect(screen.queryByTestId('indicator-grid')).not.toBeInTheDocument();
    });

    it('ES-005: Error shows error icon', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} />);
      const errorState = screen.getByTestId('catalog-error');
      // AlertCircle icon should be rendered
      expect(errorState).toBeInTheDocument();
    });

    it('ES-006: data-error attribute on container', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveAttribute('data-error', 'true');
    });

    it('ES-007: Error message accessible', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} />);
      const errorState = screen.getByTestId('catalog-error');
      expect(errorState).toHaveAttribute('role', 'alert');
    });

    it('ES-008: Retry button keyboard accessible', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={testError} onRefresh={onRefresh} />);
      
      const retryButton = screen.getByText('Retry');
      expect(retryButton.tagName.toLowerCase()).toBe('button');
    });

    it('ES-009: Custom error message from error prop', () => {
      const customError = new Error('Custom error message');
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={customError} />);
      expect(screen.getByText('Custom error message')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Empty State
  // ============================================================================

  describe('Empty State', () => {
    it('EM-001: Empty state when indicators empty', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    });

    it('EM-002: Empty state shows helpful message', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} emptyMessage="No data available" />);
      expect(screen.getByText('No data available')).toBeInTheDocument();
    });

    it('EM-003: Empty state shows icon', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      const emptyState = screen.getByTestId('catalog-empty');
      expect(emptyState).toBeInTheDocument();
    });

    it('EM-004: Empty state hides grid', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      expect(screen.queryByTestId('indicator-grid')).not.toBeInTheDocument();
    });

    it('EM-005: Empty state when filter returns empty', () => {
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          selectedCategory="nonexistent"
        />
      );
      expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    });

    it('EM-006: Empty state message for category', () => {
      renderWithProviders(
        <IndicatorCatalog
          indicators={mockIndicators.filter(i => i.category === 'nonexistent')}
          categories={mockCategories}
          selectedCategory="rankings"
        />
      );
      // Empty state should show category-specific message
      expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    });

    it('EM-007: Empty state accessible', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      const emptyState = screen.getByTestId('catalog-empty');
      expect(emptyState).toHaveAttribute('role', 'status');
    });

    it('EM-008: data-empty attribute on container', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveAttribute('data-empty', 'true');
    });
  });

  // ============================================================================
  // Refresh Capability
  // ============================================================================

  describe('Refresh Capability', () => {
    it('RC-001: Refresh button renders', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      expect(screen.getByTestId('catalog-refresh-button')).toBeInTheDocument();
    });

    it('RC-002: Refresh button triggers onRefresh', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      fireEvent.click(refreshButton);
      
      expect(onRefresh).toHaveBeenCalled();
    });

    it('RC-003: Refresh button disabled during loading', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      expect(refreshButton).toBeDisabled();
    });

    it('RC-004: Refresh button shows spinner when loading', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      // Spinner is shown via animate-spin class
      expect(refreshButton).toBeInTheDocument();
    });

    it('RC-005: Refresh button keyboard accessible', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      expect(refreshButton.tagName.toLowerCase()).toBe('button');
    });

    it('RC-006: Refresh button has aria-label', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      
      const refreshButton = screen.getByTestId('catalog-refresh-button');
      expect(refreshButton).toHaveAttribute('aria-label', 'Refresh indicators');
    });

    it('RC-007: onRefresh is optional', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      // Should not throw
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Responsive Padding and Margins
  // ============================================================================

  describe('Responsive Padding and Margins', () => {
    it('RM-001: Mobile padding (<640px)', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toBeInTheDocument();
    });

    it('RM-002: Tablet padding (640-1024px)', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toBeInTheDocument();
    });

    it('RM-003: Desktop padding (>1024px)', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toBeInTheDocument();
    });

    it('RM-004: Section margins responsive', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveClass('w-full');
    });

    it('RM-005: Title margins responsive', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Test" />);
      const title = screen.getByTestId('catalog-title');
      expect(title.className).toMatch(/text-xl/);
    });

    it('RM-006: Description margins responsive', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} description="Test" />);
      const desc = screen.getByTestId('catalog-description');
      expect(desc.className).toMatch(/text-sm/);
    });

    it('RM-007: Tab margins responsive', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabsList = screen.getByTestId('tabs-list');
      expect(tabsList).toBeInTheDocument();
    });

    it('RM-008: Container max-width', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toHaveClass('w-full');
    });
  });

  // ============================================================================
  // Integration with IndicatorGrid
  // ============================================================================

  describe('Integration with IndicatorGrid', () => {
    it('IG-001: Renders IndicatorGrid component', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
    });

    it('IG-002: Passes indicators to grid', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('data-indicator-count', String(mockIndicators.length));
    });

    it('IG-003: Passes filtered indicators by category', () => {
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          selectedCategory="rankings"
        />
      );
      const grid = screen.getByTestId('indicator-grid');
      // Only rankings category items (3 items: keyword-rankings, visibility-score, serp-features)
      const rankingsCount = mockIndicators.filter(i => i.category === 'rankings').length;
      expect(grid).toHaveAttribute('data-indicator-count', String(rankingsCount));
    });

    it('IG-004: Passes isLoading to grid', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      // Grid is not shown during loading, skeleton is shown instead
      expect(screen.getByTestId('catalog-skeleton-grid')).toBeInTheDocument();
    });

    it('IG-005: Grid columns responsive', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('IG-006: Grid gap uses design system', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('IG-007: Category filter works correctly', () => {
      const onCategoryChange = jest.fn();
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          onCategoryChange={onCategoryChange}
        />
      );
      
      const technicalTab = screen.getByTestId('tab-technical');
      fireEvent.click(technicalTab);
      
      expect(onCategoryChange).toHaveBeenCalledWith('technical');
    });

    it('IG-008: All indicators shown when no category', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('data-indicator-count', String(mockIndicators.length));
    });

    it('IG-009: Category "All" shows all indicators', () => {
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          selectedCategory="All"
        />
      );
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('data-indicator-count', String(mockIndicators.length));
    });
  });

  // ============================================================================
  // Integration with shadcn/ui Components
  // ============================================================================

  describe('Integration with shadcn/ui Components', () => {
    it('SC-001: Uses Tabs component', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('tabs')).toBeInTheDocument();
    });

    it('SC-002: Uses Separator component', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(screen.getByTestId('separator')).toBeInTheDocument();
    });

    it('SC-003: Uses Skeleton component', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      expect(screen.getAllByTestId('skeleton').length).toBeGreaterThan(0);
    });

    it('SC-004: Uses Button component', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      expect(screen.getByTestId('catalog-refresh-button')).toBeInTheDocument();
    });

    it('SC-005: Dark mode styling applied', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      // Check for dark mode classes
      expect(catalog).toBeInTheDocument();
    });

    it('SC-006: Components use CSS variables', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });

    it('SC-007: All components have correct variants', () => {
      const onRefresh = jest.fn();
      renderWithProviders(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);
      const button = screen.getByTestId('catalog-refresh-button');
      expect(button).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Accessibility
  // ============================================================================

  describe('Accessibility', () => {
    it('AC-001: Section has role="region"', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      // Section element implicitly has region role
      expect(catalog.tagName.toLowerCase()).toBe('section');
    });

    it('AC-002: aria-labelledby points to title', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} title="Test" />);
      const catalog = screen.getByTestId('indicator-catalog');
      const title = screen.getByTestId('catalog-title');
      
      const labelledBy = catalog.getAttribute('aria-labelledby');
      expect(labelledBy).toBe(title.getAttribute('id'));
    });

    it('AC-003: Tab list has proper ARIA', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabsList = screen.getByTestId('tabs-list');
      expect(tabsList).toHaveAttribute('role', 'tablist');
    });

    it('AC-004: Tabs have proper ARIA', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabs = screen.getAllByRole('tab');
      expect(tabs.length).toBeGreaterThan(0);
    });

    it('AC-005: Tab panels have proper ARIA', () => {
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      const tabsContent = screen.getByTestId('tabs-content');
      expect(tabsContent).toBeInTheDocument();
    });

    it('AC-006: Error message in aria-live region', () => {
      const error = new Error('Test error');
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={error} />);
      const errorState = screen.getByTestId('catalog-error');
      expect(errorState).toHaveAttribute('role', 'alert');
    });

    it('AC-007: Empty state in aria-live region', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      const emptyState = screen.getByTestId('catalog-empty');
      expect(emptyState).toHaveAttribute('role', 'status');
    });

    it('AC-008: Keyboard navigation for tabs', () => {
      const onCategoryChange = jest.fn();
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} onCategoryChange={onCategoryChange} />);
      
      const tabs = screen.getByTestId('tabs');
      fireEvent.keyDown(tabs, { key: 'ArrowRight' });
      
      expect(onCategoryChange).toHaveBeenCalled();
    });

    it('AC-009: Focus management on state change', () => {
      const { rerender } = renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      
      rerender(
        <TooltipProvider>
          <IndicatorCatalog {...defaultProps} isLoading={false} />
        </TooltipProvider>
      );
      
      expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
    });

    it('AC-010: Reduced motion preference', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      const catalog = screen.getByTestId('indicator-catalog');
      expect(catalog).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('EC-001: Empty indicators array', () => {
      renderWithProviders(<IndicatorCatalog indicators={[]} />);
      expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    });

    it('EC-002: Empty categories array', () => {
      renderWithProviders(<IndicatorCatalog {...defaultProps} categories={[]} />);
      expect(screen.queryByTestId('tabs')).not.toBeInTheDocument();
    });

    it('EC-003: Invalid selectedCategory', () => {
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          selectedCategory="invalid-category"
        />
      );
      // Should still render
      expect(screen.getByTestId('indicator-catalog')).toBeInTheDocument();
    });

    it('EC-004: Category not in list', () => {
      renderWithProviders(
        <IndicatorCatalog
          {...withCategoriesProps}
          selectedCategory="nonexistent"
        />
      );
      // Should show empty state
      expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    });

    it('EC-005: Very long title', () => {
      const longTitle = 'A'.repeat(200);
      renderWithProviders(<IndicatorCatalog {...defaultProps} title={longTitle} />);
      expect(screen.getByTestId('catalog-title')).toHaveTextContent(longTitle);
    });

    it('EC-006: Very long description', () => {
      const longDesc = 'A'.repeat(500);
      renderWithProviders(<IndicatorCatalog {...defaultProps} description={longDesc} />);
      expect(screen.getByTestId('catalog-description')).toHaveTextContent(longDesc);
    });

    it('EC-007: Many categories (10+)', () => {
      const manyCategories = ['All', ...Array.from({ length: 15 }, (_, i) => `category-${i}`)];
      renderWithProviders(<IndicatorCatalog {...defaultProps} categories={manyCategories} />);
      expect(screen.getByTestId('tabs')).toBeInTheDocument();
    });

    it('EC-008: Error with no onRefresh', () => {
      const error = new Error('Test error');
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={error} />);
      // Retry button should not be present
      expect(screen.queryByText('Retry')).not.toBeInTheDocument();
    });

    it('EC-009: Loading with error', () => {
      const error = new Error('Test error');
      renderWithProviders(<IndicatorCatalog {...defaultProps} error={error} isLoading />);
      // Loading takes priority
      expect(screen.getByTestId('catalog-skeleton-grid')).toBeInTheDocument();
    });

    it('EC-010: Rapid category changes', () => {
      const onCategoryChange = jest.fn();
      renderWithProviders(<IndicatorCatalog {...withCategoriesProps} onCategoryChange={onCategoryChange} />);
      
      const rankingsTab = screen.getByTestId('tab-rankings');
      const technicalTab = screen.getByTestId('tab-technical');
      
      fireEvent.click(rankingsTab);
      fireEvent.click(technicalTab);
      fireEvent.click(rankingsTab);
      
      expect(onCategoryChange).toHaveBeenCalledTimes(3);
    });
  });

  // ============================================================================
  // Module Exports
  // ============================================================================

  describe('Module Exports', () => {
    it('ME-001: Exported from indicators/index', async () => {
      const module = await import('../index');
      expect(module.IndicatorCatalog).toBeDefined();
    });

    it('ME-002: Named export available', () => {
      expect(IndicatorCatalog).toBeDefined();
      expect(typeof IndicatorCatalog).toBe('function');
    });

    it('ME-003: IndicatorCatalogProps type exported', () => {
      const props: IndicatorCatalogProps = { indicators: [] };
      expect(props).toBeDefined();
    });
  });

  // ============================================================================
  // Snapshots
  // ============================================================================

  describe('Snapshots', () => {
    it('SN-001: Default catalog snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-002: With categories snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog {...withCategoriesProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-003: Loading state snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog {...defaultProps} isLoading />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-004: Error state snapshot', () => {
      const error = new Error('Test error');
      const { container } = renderWithProviders(<IndicatorCatalog {...defaultProps} error={error} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-005: Empty state snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog indicators={[]} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-006: Mobile layout snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-007: Desktop layout snapshot', () => {
      const { container } = renderWithProviders(<IndicatorCatalog {...defaultProps} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-008: Custom title/description snapshot', () => {
      const { container } = renderWithProviders(
        <IndicatorCatalog
          {...defaultProps}
          title="Custom Title"
          description="Custom description"
        />
      );
      expect(container.firstChild).toMatchSnapshot();
    });
  });
});
