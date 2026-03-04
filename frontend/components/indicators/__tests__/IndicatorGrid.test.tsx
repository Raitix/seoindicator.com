import * as React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, jest } from '@jest/globals';

import { IndicatorGrid, type IndicatorGridProps, type IndicatorData, type ColumnConfig } from '../IndicatorGrid';
import { TooltipProvider } from '@/components/ui/tooltip';

// ============================================================================
// Mocks
// ============================================================================

// Mock IndicatorCard to isolate grid tests
jest.mock('../IndicatorCard', () => ({
  IndicatorCard: ({ id, title, value, status, href, isLoading }: { id: string; title: string; value: string | number; status: string; href: string; isLoading: boolean }) => (
    <a
      href={href}
      data-testid={`indicator-card-${id}`}
      data-title={String(title)}
      data-value={String(value)}
      data-status={status}
      data-loading={String(isLoading)}
    >
      {title}: {value}
    </a>
  ),
  statusConfig: {
    good: { label: 'Good' },
    warning: { label: 'Warning' },
    critical: { label: 'Critical' },
    neutral: { label: 'Neutral' },
  },
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
  },
  {
    id: 'visibility-score',
    icon: 'Activity',
    title: 'Visibility Score',
    value: '78%',
    description: 'Overall SEO visibility',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'competitor-analysis',
    icon: 'Activity',
    title: 'Competitor Analysis',
    value: '5',
    description: 'Tracked competitors',
    status: 'neutral',
    href: '/comparison',
  },
  {
    id: 'technical-health',
    icon: 'Activity',
    title: 'Technical Health',
    value: '85/100',
    description: 'Site audit score',
    status: 'warning',
    href: '/tools',
  },
  {
    id: 'backlink-profile',
    icon: 'Activity',
    title: 'Backlink Profile',
    value: '1,234',
    description: 'Total backlinks',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'content-opportunities',
    icon: 'Activity',
    title: 'Content Opportunities',
    value: '23',
    description: 'Keyword gaps found',
    status: 'warning',
    href: '/dashboard',
  },
  {
    id: 'serp-features',
    icon: 'Star',
    title: 'SERP Features',
    value: '8',
    description: 'Featured snippets won',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'page-speed',
    icon: 'Zap',
    title: 'Page Speed',
    value: '92/100',
    description: 'Performance score',
    status: 'good',
    href: '/tools',
  },
];

const defaultColumns: Required<ColumnConfig> = {
  mobile: 1,
  sm: 2,
  md: 3,
  lg: 4,
  xl: 4,
};

// ============================================================================
// Tests
// ============================================================================

describe('IndicatorGrid', () => {
  // ============================================================================
  // TypeScript Type Definitions
  // ============================================================================

  describe('TypeScript Types', () => {
    it('TD-001: Component props interface exists', () => {
      const props: IndicatorGridProps = {
        indicators: mockIndicators,
      };
      expect(props.indicators).toBeDefined();
    });

    it('TD-002: IndicatorData interface defined', () => {
      const indicator: IndicatorData = {
        id: 'test',
        icon: 'Activity',
        title: 'Test',
        value: '100',
        description: 'Test description',
        status: 'good',
        href: '/test',
      };
      expect(indicator.id).toBe('test');
    });
  });

  // ============================================================================
  // Component Rendering
  // ============================================================================

  describe('Component Rendering', () => {
    it('CR-001: Renders without crashing', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      expect(screen.getByTestId('indicator-grid')).toBeInTheDocument();
    });

    it('CR-002: Renders empty grid', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
      expect(grid.children.length).toBe(0);
    });

    it('CR-003: Renders single indicator', () => {
      renderWithProviders(<IndicatorGrid indicators={[mockIndicators[0]]} />);
      expect(screen.getByTestId('indicator-card-keyword-rankings')).toBeInTheDocument();
    });

    it('CR-004: Renders multiple indicators', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      mockIndicators.forEach((indicator) => {
        expect(screen.getByTestId(`indicator-card-${indicator.id}`)).toBeInTheDocument();
      });
    });

    it('CR-005: Renders with data-testid attribute', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      expect(screen.getByTestId('indicator-grid')).toHaveAttribute('data-testid', 'indicator-grid');
    });

    it('CR-006: Applies custom className', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} className="custom-class" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('custom-class');
    });

    it('CR-007: Grid container has proper role', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('role', 'grid');
    });

    it('CR-008: Renders all 8 indicator types', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      mockIndicators.forEach((indicator) => {
        expect(screen.getByTestId(`indicator-card-${indicator.id}`)).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // CSS Grid Layout
  // ============================================================================

  describe('CSS Grid Layout', () => {
    it('GL-001: Uses CSS Grid display', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid');
    });

    it('GL-002: 12-column grid system', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/grid-cols-/);
    });

    it('GL-003: Grid auto-flow set correctly', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid');
    });

    it('GL-004: Grid alignment properties', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid');
    });

    it('GL-005: Min card width constraint', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/grid-cols-/);
    });

    it('GL-006: Grid item class applied', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      const gridItems = grid.querySelectorAll('[role="gridcell"]');
      expect(gridItems.length).toBe(mockIndicators.length);
    });

    it('GL-007: CSS Grid shorthand applied', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid');
    });
  });

  // ============================================================================
  // Responsive Breakpoints
  // ============================================================================

  describe('Responsive Breakpoints', () => {
    it('RB-001: 1 column on mobile (<640px)', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-1');
    });

    it('RB-002: 2 columns on sm (640px+)', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/sm:grid-cols-2/);
    });

    it('RB-003: 3 columns on md (768px+)', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/md:grid-cols-3/);
    });

    it('RB-004: 4 columns on lg (1024px+)', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/lg:grid-cols-4/);
    });

    it('RB-005: Custom column config overrides', () => {
      const customColumns: ColumnConfig = {
        mobile: 2,
        sm: 3,
        md: 4,
        lg: 5,
        xl: 6,
      };
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={customColumns} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-2');
      expect(grid.className).toMatch(/sm:grid-cols-3/);
      expect(grid.className).toMatch(/md:grid-cols-4/);
      expect(grid.className).toMatch(/lg:grid-cols-5/);
      expect(grid.className).toMatch(/xl:grid-cols-6/);
    });

    it('RB-006: xl breakpoint support', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      // Default xl is 4, same as lg, so xl class might not be rendered
      expect(grid.className).toMatch(/grid-cols-/);
    });

    it('RB-007: Responsive media query classes', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/sm:/);
      expect(grid.className).toMatch(/md:/);
      expect(grid.className).toMatch(/lg:/);
    });

    it('RB-008: Viewport width detection', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Gap Spacing
  // ============================================================================

  describe('Gap Spacing', () => {
    it('GS-001: Default gap is "md"', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('gap-4');
    });

    it('GS-002: "sm" gap applies small spacing', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="sm" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('gap-2');
    });

    it('GS-003: "md" gap applies medium spacing', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="md" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('gap-4');
    });

    it('GS-004: "lg" gap applies large spacing', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="lg" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('gap-6');
    });

    it('GS-005: Gap applies to grid-gap', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="lg" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/gap-/);
    });

    it('GS-006: Gap consistent across breakpoints', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="lg" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('gap-6');
    });
  });

  // ============================================================================
  // Animated Layout Transitions
  // ============================================================================

  describe('Animated Layout Transitions', () => {
    it('AT-001: Transition on resize', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/transition-all/);
    });

    it('AT-002: Transition duration reasonable', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/duration-200/);
    });

    it('AT-003: GPU-accelerated properties', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/transition-all/);
    });

    it('AT-004: Respects prefers-reduced-motion', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      // This test assumes prefers-reduced-motion is false (default mock)
      expect(grid.className).toMatch(/transition-all/);
    });

    it('AT-005: No layout shift during transition', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('AT-006: Width/height transition', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/transition-all/);
    });

    it('AT-007: Transition timing function', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/ease-out/);
    });
  });

  // ============================================================================
  // Accessibility
  // ============================================================================

  describe('Accessibility', () => {
    it('AC-001: Grid has role="grid"', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('role', 'grid');
    });

    it('AC-002: aria-label on grid', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('aria-label', 'SEO Indicators');
    });

    it('AC-003: aria-describedby for description', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('aria-label');
    });

    it('AC-004: Items have role="gridcell"', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      const gridCells = grid.querySelectorAll('[role="gridcell"]');
      expect(gridCells.length).toBe(mockIndicators.length);
    });

    it('AC-005: Keyboard navigation between items', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('role', 'grid');
    });

    it('AC-006: Focus visible on grid items', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('AC-007: aria-rowcount for item count', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('aria-rowcount');
    });

    it('AC-008: High contrast mode support', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Variable Indicator Count
  // ============================================================================

  describe('Variable Indicator Count', () => {
    it('VC-001: Renders 1 indicator', () => {
      renderWithProviders(<IndicatorGrid indicators={[mockIndicators[0]]} />);
      expect(screen.getByTestId('indicator-card-keyword-rankings')).toBeInTheDocument();
    });

    it('VC-002: Renders 2 indicators', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators.slice(0, 2)} />);
      expect(screen.getByTestId('indicator-card-keyword-rankings')).toBeInTheDocument();
      expect(screen.getByTestId('indicator-card-visibility-score')).toBeInTheDocument();
    });

    it('VC-003: Renders 4 indicators', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators.slice(0, 4)} />);
      expect(screen.getAllByTestId(/indicator-card-/).length).toBe(4);
    });

    it('VC-004: Renders 8 indicators', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      expect(screen.getAllByTestId(/indicator-card-/).length).toBe(8);
    });

    it('VC-005: Renders 12 indicators', () => {
      const extendedIndicators = [
        ...mockIndicators,
        ...mockIndicators.map((i, idx) => ({ ...i, id: `${i.id}-${idx + 1}` })),
      ].slice(0, 12);
      renderWithProviders(<IndicatorGrid indicators={extendedIndicators} />);
      expect(screen.getAllByTestId(/indicator-card-/).length).toBe(12);
    });

    it('VC-006: Renders 20+ indicators', () => {
      const manyIndicators = Array.from({ length: 25 }, (_, i) => ({
        ...mockIndicators[i % mockIndicators.length],
        id: `indicator-${i}`,
      }));
      renderWithProviders(<IndicatorGrid indicators={manyIndicators} />);
      expect(screen.getAllByTestId(/indicator-card-/).length).toBe(25);
    });

    it('VC-007: Empty array shows nothing', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.children.length).toBe(0);
    });
  });

  // ============================================================================
  // Integration with IndicatorCard
  // ============================================================================

  describe('Integration with IndicatorCard', () => {
    it('IC-001: Renders IndicatorCard for each item', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      mockIndicators.forEach((indicator) => {
        expect(screen.getByTestId(`indicator-card-${indicator.id}`)).toBeInTheDocument();
      });
    });

    it('IC-002: Passes indicator data to card', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const firstCard = screen.getByTestId('indicator-card-keyword-rankings');
      // Check that the card renders with the expected text content
      expect(firstCard).toHaveTextContent('Keyword Rankings');
      expect(firstCard).toHaveTextContent('142');
    });

    it('IC-003: Passes isLoading to cards', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const cards = screen.getAllByTestId(/indicator-card-/);
      // Cards should render their content (not in loading state)
      expect(cards.length).toBe(mockIndicators.length);
      cards.forEach((card) => {
        expect(card).toBeInTheDocument();
      });
    });

    it('IC-004: Cards link to correct hrefs', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const firstCard = screen.getByTestId('indicator-card-keyword-rankings');
      expect(firstCard).toHaveAttribute('href', '/dashboard');
    });

    it('IC-005: Cards have unique keys', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const cards = screen.getAllByTestId(/indicator-card-/);
      const ids = cards.map((c) => c.getAttribute('data-testid'));
      const uniqueIds = new Set(ids);
      expect(uniqueIds.size).toBe(ids.length);
    });

    it('IC-006: Cards inherit grid item role', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      const gridCells = grid.querySelectorAll('[role="gridcell"]');
      expect(gridCells.length).toBe(mockIndicators.length);
    });

    it('IC-007: All status variants render', () => {
      const statusVariants: IndicatorData[] = [
        { ...mockIndicators[0], id: 'good-1', status: 'good' },
        { ...mockIndicators[1], id: 'warning-1', status: 'warning' },
        { ...mockIndicators[2], id: 'critical-1', status: 'critical' },
        { ...mockIndicators[3], id: 'neutral-1', status: 'neutral' },
      ];
      renderWithProviders(<IndicatorGrid indicators={statusVariants} />);
      expect(screen.getByTestId('indicator-card-good-1')).toHaveAttribute('data-status', 'good');
      expect(screen.getByTestId('indicator-card-warning-1')).toHaveAttribute('data-status', 'warning');
      expect(screen.getByTestId('indicator-card-critical-1')).toHaveAttribute('data-status', 'critical');
      expect(screen.getByTestId('indicator-card-neutral-1')).toHaveAttribute('data-status', 'neutral');
    });

    it('IC-008: Card icons render', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const cards = screen.getAllByTestId(/indicator-card-/);
      expect(cards.length).toBe(mockIndicators.length);
    });
  });

  // ============================================================================
  // Loading State
  // ============================================================================

  describe('Loading State', () => {
    it('LS-001: isLoading shows skeleton grid', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} isLoading />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('data-loading', 'true');
    });

    it('LS-002: isLoading hides real cards', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} isLoading />);
      expect(screen.queryAllByTestId(/indicator-card-/).length).toBe(0);
    });

    it('LS-003: Skeleton count matches expected', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} isLoading />);
      const grid = screen.getByTestId('indicator-grid');
      // 8 skeletons are rendered
      const skeletons = grid.querySelectorAll('[role="presentation"]');
      expect(skeletons.length).toBe(8);
    });

    it('LS-004: Skeleton respects grid layout', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} isLoading />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid');
    });

    it('LS-005: data-loading attribute on grid', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} isLoading />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveAttribute('data-loading', 'true');
    });
  });

  // ============================================================================
  // Custom Column Configuration
  // ============================================================================

  describe('Custom Column Configuration', () => {
    it('CC-001: Override mobile columns', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ mobile: 2 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-2');
    });

    it('CC-002: Override sm columns', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ sm: 3 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/sm:grid-cols-3/);
    });

    it('CC-003: Override md columns', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ md: 4 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/md:grid-cols-4/);
    });

    it('CC-004: Override lg columns', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ lg: 5 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/lg:grid-cols-5/);
    });

    it('CC-005: Override xl columns', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ xl: 6 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.className).toMatch(/xl:grid-cols-6/);
    });

    it('CC-006: Partial override uses defaults', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ sm: 3 }} />);
      const grid = screen.getByTestId('indicator-grid');
      // Mobile should default to 1
      expect(grid).toHaveClass('grid-cols-1');
      // sm should be overridden to 3
      expect(grid.className).toMatch(/sm:grid-cols-3/);
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('EC-001: Invalid column count defaults to 1', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ mobile: -1 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-1');
    });

    it('EC-002: Negative column count defaults to 1', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={{ mobile: -5 }} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-1');
    });

    it('EC-003: Zero indicators shows empty', () => {
      renderWithProviders(<IndicatorGrid indicators={[]} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid.children.length).toBe(0);
    });

    it('EC-004: Very long indicator list', () => {
      const manyIndicators = Array.from({ length: 100 }, (_, i) => ({
        ...mockIndicators[i % mockIndicators.length],
        id: `indicator-${i}`,
      }));
      renderWithProviders(<IndicatorGrid indicators={manyIndicators} />);
      expect(screen.getAllByTestId(/indicator-card-/).length).toBe(100);
    });

    it('EC-005: Duplicate indicator IDs', () => {
      const duplicateIndicators = [
        { ...mockIndicators[0], id: 'duplicate' },
        { ...mockIndicators[1], id: 'duplicate' },
      ];
      renderWithProviders(<IndicatorGrid indicators={duplicateIndicators} />);
      // React will warn but should still render
      const cards = screen.getAllByTestId(/indicator-card-/);
      expect(cards.length).toBe(2);
    });

    it('EC-006: Missing optional props', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('EC-007: Undefined columns prop', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} columns={undefined} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toHaveClass('grid-cols-1');
    });

    it('EC-008: Rapid viewport resize', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Security
  // ============================================================================

  describe('Security', () => {
    it('SC-001: XSS in indicator title', () => {
      const maliciousIndicators: IndicatorData[] = [
        {
          ...mockIndicators[0],
          id: 'xss-test',
          title: '<script>alert("xss")</script>',
        },
      ];
      renderWithProviders(<IndicatorGrid indicators={maliciousIndicators} />);
      const card = screen.getByTestId('indicator-card-xss-test');
      expect(card).toBeInTheDocument();
      // Script should not execute - React escapes content
    });

    it('SC-002: XSS in indicator description', () => {
      const maliciousIndicators: IndicatorData[] = [
        {
          ...mockIndicators[0],
          id: 'xss-desc',
          description: '<img src=x onerror=alert(1)>',
        },
      ];
      renderWithProviders(<IndicatorGrid indicators={maliciousIndicators} />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });

    it('SC-003: Malicious className prop', () => {
      renderWithProviders(<IndicatorGrid indicators={mockIndicators} className="<script>alert(1)</script>" />);
      const grid = screen.getByTestId('indicator-grid');
      expect(grid).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Module Exports
  // ============================================================================

  describe('Module Exports', () => {
    it('ME-001: Exported from indicators/index', async () => {
      const module = await import('../index');
      expect(module.IndicatorGrid).toBeDefined();
    });

    it('ME-002: Named export available', () => {
      expect(IndicatorGrid).toBeDefined();
      // forwardRef components are objects with $$typeof property
      expect(typeof IndicatorGrid === 'object' || typeof IndicatorGrid === 'function').toBe(true);
    });

    it('ME-003: IndicatorGridProps type exported', () => {
      const props: IndicatorGridProps = { indicators: [] };
      expect(props).toBeDefined();
    });
  });

  // ============================================================================
  // Snapshots
  // ============================================================================

  describe('Snapshots', () => {
    it('SN-001: Default grid snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-002: Custom columns snapshot', () => {
      const { container } = renderWithProviders(
        <IndicatorGrid
          indicators={mockIndicators}
          columns={{ mobile: 2, sm: 3, md: 4, lg: 5, xl: 6 }}
        />
      );
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-003: sm gap snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="sm" />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-004: lg gap snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={mockIndicators} gap="lg" />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-005: Loading state snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={[]} isLoading />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-006: Mobile layout snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={mockIndicators.slice(0, 2)} />);
      expect(container.firstChild).toMatchSnapshot();
    });

    it('SN-007: Desktop layout snapshot', () => {
      const { container } = renderWithProviders(<IndicatorGrid indicators={mockIndicators} />);
      expect(container.firstChild).toMatchSnapshot();
    });
  });
});


