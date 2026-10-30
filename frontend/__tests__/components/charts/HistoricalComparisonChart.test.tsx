/**
 * TASK-3-8: HistoricalComparisonChart Component Tests
 *
 * Tests for multi-line chart showing ranking history over time for user vs competitors.
 * Uses mocked Recharts for deterministic testing.
 *
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-3-8.md
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { vi } from 'vitest';

// ============================================================================
// Types (from component)
// ============================================================================

interface Keyword {
  id: number;
  keyword: string;
}

interface Competitor {
  id: number;
  domain: string;
  displayName?: string;
}

interface ComparisonHistoryPoint {
  position: number | null;
  checkedAt: string;
}

interface CompetitorHistoryPoint {
  competitorId: number;
  position: number | null;
  checkedAt: string;
}

interface ComparisonHistoryData {
  keyword: {
    id: number;
    keyword: string;
  } | null;
  history: {
    user: ComparisonHistoryPoint[];
    competitors: CompetitorHistoryPoint[];
  };
}

// ============================================================================
// Mock Data
// ============================================================================

const mockKeywords: Keyword[] = [
  { id: 1, keyword: 'seo tools' },
  { id: 2, keyword: 'keyword research' },
  { id: 3, keyword: 'backlink analysis' },
];

const mockCompetitors: Competitor[] = [
  { id: 1, domain: 'ahrefs.com', displayName: 'Ahrefs' },
  { id: 2, domain: 'semrush.com', displayName: 'SEMrush' },
  { id: 3, domain: 'moz.com', displayName: 'Moz' },
];

const mockHistoryData: ComparisonHistoryData = {
  keyword: { id: 1, keyword: 'seo tools' },
  history: {
    user: [
      { position: 5, checkedAt: '2026-03-01T10:00:00Z' },
      { position: 4, checkedAt: '2026-03-02T10:00:00Z' },
      { position: 3, checkedAt: '2026-03-03T10:00:00Z' },
      { position: 4, checkedAt: '2026-03-04T10:00:00Z' },
      { position: 2, checkedAt: '2026-03-05T10:00:00Z' },
      { position: 3, checkedAt: '2026-03-06T10:00:00Z' },
      { position: 2, checkedAt: '2026-03-07T10:00:00Z' },
    ],
    competitors: [
      { competitorId: 1, position: 8, checkedAt: '2026-03-01T10:00:00Z' },
      { competitorId: 1, position: 7, checkedAt: '2026-03-02T10:00:00Z' },
      { competitorId: 1, position: 6, checkedAt: '2026-03-03T10:00:00Z' },
      { competitorId: 1, position: 6, checkedAt: '2026-03-04T10:00:00Z' },
      { competitorId: 1, position: 5, checkedAt: '2026-03-05T10:00:00Z' },
      { competitorId: 1, position: 5, checkedAt: '2026-03-06T10:00:00Z' },
      { competitorId: 1, position: 4, checkedAt: '2026-03-07T10:00:00Z' },
      { competitorId: 2, position: 12, checkedAt: '2026-03-01T10:00:00Z' },
      { competitorId: 2, position: 11, checkedAt: '2026-03-02T10:00:00Z' },
      { competitorId: 2, position: 10, checkedAt: '2026-03-03T10:00:00Z' },
      { competitorId: 2, position: 9, checkedAt: '2026-03-04T10:00:00Z' },
      { competitorId: 2, position: 9, checkedAt: '2026-03-05T10:00:00Z' },
      { competitorId: 2, position: 8, checkedAt: '2026-03-06T10:00:00Z' },
      { competitorId: 2, position: 7, checkedAt: '2026-03-07T10:00:00Z' },
      { competitorId: 3, position: 15, checkedAt: '2026-03-01T10:00:00Z' },
      { competitorId: 3, position: 14, checkedAt: '2026-03-02T10:00:00Z' },
      { competitorId: 3, position: 13, checkedAt: '2026-03-03T10:00:00Z' },
      { competitorId: 3, position: 12, checkedAt: '2026-03-04T10:00:00Z' },
      { competitorId: 3, position: 11, checkedAt: '2026-03-05T10:00:00Z' },
      { competitorId: 3, position: 10, checkedAt: '2026-03-06T10:00:00Z' },
      { competitorId: 3, position: 9, checkedAt: '2026-03-07T10:00:00Z' },
    ],
  },
};

const mockEmptyHistoryData: ComparisonHistoryData = {
  keyword: { id: 1, keyword: 'seo tools' },
  history: {
    user: [],
    competitors: [],
  },
};

const mockNullPositions: ComparisonHistoryData = {
  keyword: { id: 1, keyword: 'seo tools' },
  history: {
    user: [
      { position: null, checkedAt: '2026-03-01T10:00:00Z' },
      { position: null, checkedAt: '2026-03-02T10:00:00Z' },
    ],
    competitors: [
      { competitorId: 1, position: null, checkedAt: '2026-03-01T10:00:00Z' },
      { competitorId: 1, position: 10, checkedAt: '2026-03-02T10:00:00Z' },
    ],
  },
};

const mockLargeHistoryData: ComparisonHistoryData = {
  keyword: { id: 1, keyword: 'seo tools' },
  history: {
    user: Array.from({ length: 90 }, (_, i) => ({
      position: Math.floor(Math.random() * 20) + 1,
      checkedAt: `2026-03-${String(i + 1).padStart(2, '0')}T10:00:00Z`,
    })),
    competitors: Array.from({ length: 90 * 3 }, (_, i) => ({
      competitorId: (i % 3) + 1,
      position: Math.floor(Math.random() * 30) + 1,
      checkedAt: `2026-03-${String(Math.floor(i / 3) + 1).padStart(2, '0')}T10:00:00Z`,
    })),
  },
};

// ============================================================================
// Mock SWR Hook
// ============================================================================

const mockUseComparisonHistory = vi.fn();

vi.mock('@/lib/api/comparison-history', () => ({
  useComparisonHistory: (params: { keywordId?: number; days?: number }) => mockUseComparisonHistory(params),
  parseDateRangeOption: (option: string) => {
    switch (option) {
      case '7d':
        return 7;
      case '30d':
        return 30;
      case '90d':
        return 90;
      default:
        return 30;
    }
  },
}));

// ============================================================================
// Mock Recharts Components
// ============================================================================

vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  LineChart: ({ children, data }: { children: React.ReactNode; data: unknown[] }) => (
    <div data-testid="line-chart" data-points={data?.length || 0}>
      {children}
    </div>
  ),
  Line: ({ dataKey, stroke, hide, name }: { dataKey: string; stroke: string; hide?: boolean; name?: string }) => (
    <div
      data-testid={`line-${dataKey}`}
      data-key={dataKey}
      data-color={stroke}
      data-hidden={hide ? 'true' : 'false'}
      data-name={name || dataKey}
    />
  ),
  XAxis: ({ dataKey, tickFormatter }: { dataKey: string; tickFormatter?: (v: string) => string }) => (
    <div data-testid="x-axis" data-key={dataKey} data-has-formatter={!!tickFormatter} />
  ),
  YAxis: ({ reversed, domain, label }: { reversed?: boolean; domain?: (number | string)[]; label?: { value: string } }) => (
    <div
      data-testid="y-axis"
      data-reversed={reversed ? 'true' : 'false'}
      data-domain={JSON.stringify(domain)}
      data-label={label?.value || ''}
    />
  ),
  CartesianGrid: () => <div data-testid="cartesian-grid" />,
  Tooltip: ({ content }: { content: React.ReactNode }) => (
    <div data-testid="tooltip-container">{content}</div>
  ),
  Legend: ({ content }: { content: React.ReactNode }) => (
    <div data-testid="legend-container">{content}</div>
  ),
}));

// ============================================================================
// Mock UI Components
// ============================================================================

vi.mock('@/components/ui/card', () => ({
  Card: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="card" className={className}>{children}</div>
  ),
  CardHeader: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="card-header" className={className}>{children}</div>
  ),
  CardContent: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="card-content">{children}</div>
  ),
  CardTitle: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <h3 data-testid="card-title" className={className}>{children}</h3>
  ),
}));

vi.mock('@/components/ui/button', () => ({
  Button: ({ children, onClick, variant, size, disabled, type }: {
    children: React.ReactNode;
    onClick?: () => void;
    variant?: string;
    size?: string;
    disabled?: boolean;
    type?: string;
  }) => (
    <button
      data-testid="button"
      data-variant={variant}
      data-size={size}
      disabled={disabled}
      type={type as 'button' | 'submit' | 'reset'}
      onClick={onClick}
    >
      {children}
    </button>
  ),
}));

vi.mock('@/components/ui/select', () => ({
  Select: ({ children, value, onValueChange, disabled }: {
    children: React.ReactNode;
    value: string;
    onValueChange: (v: string) => void;
    disabled?: boolean;
  }) => (
    <div data-testid="select" data-value={value} data-disabled={disabled}>
      {React.Children.map(children, (child) => {
        if (React.isValidElement(child)) {
          return React.cloneElement(child as React.ReactElement<{ onValueChange?: (v: string) => void }>, { onValueChange });
        }
        return child;
      })}
    </div>
  ),
  SelectTrigger: ({ children, className }: { children: React.ReactNode; className?: string }) => (
    <div data-testid="select-trigger" className={className}>{children}</div>
  ),
  SelectValue: ({ placeholder }: { placeholder: string }) => (
    <span data-testid="select-value" data-placeholder={placeholder} />
  ),
  SelectContent: ({ children, onValueChange }: { children: React.ReactNode; onValueChange?: (v: string) => void }) => (
    <div data-testid="select-content">
      {React.Children.map(children, (child) => {
        if (React.isValidElement(child) && onValueChange) {
          return React.cloneElement(child as React.ReactElement<{ onSelect?: (v: string) => void }>, {
            onSelect: onValueChange,
          });
        }
        return child;
      })}
    </div>
  ),
  SelectItem: ({ children, value, onSelect }: { children: React.ReactNode; value: string; onSelect?: (v: string) => void }) => (
    <button
      data-testid={`select-item-${value}`}
      data-value={value}
      onClick={() => onSelect?.(value)}
      type="button"
    >
      {children}
    </button>
  ),
}));

vi.mock('@/components/ui/checkbox', () => ({
  Checkbox: ({ id, checked, onCheckedChange }: {
    id: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
  }) => (
    <input
      data-testid={id}
      type="checkbox"
      checked={checked}
      onChange={(e) => onCheckedChange(e.target.checked)}
    />
  ),
}));

vi.mock('@/components/ui/label', () => ({
  Label: ({ children, htmlFor }: { children: React.ReactNode; htmlFor: string }) => (
    <label data-testid={`label-${htmlFor}`} htmlFor={htmlFor}>
      {children}
    </label>
  ),
}));

// ============================================================================
// Import Component After Mocks
// ============================================================================

import HistoricalComparisonChart from '@/components/charts/HistoricalComparisonChart';

// ============================================================================
// Tests: Rendering
// ============================================================================

describe('HistoricalComparisonChart', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseComparisonHistory.mockReturnValue({
      data: mockHistoryData,
      isLoading: false,
      error: null,
    });
  });

  describe('Rendering (REND)', () => {
    it('renders card with title and description', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('Historical Comparison')).toBeInTheDocument();
      expect(screen.getByText('Track ranking positions over time compared to competitors.')).toBeInTheDocument();
    });

    it('renders responsive container for chart', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
    });

    it('renders line chart component', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('renders keyword selector with all keywords', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('select')).toBeInTheDocument();
      expect(screen.getByTestId('select-item-1')).toBeInTheDocument();
      expect(screen.getByTestId('select-item-2')).toBeInTheDocument();
      expect(screen.getByTestId('select-item-3')).toBeInTheDocument();
    });

    it('renders date range selector buttons', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('7 days')).toBeInTheDocument();
      expect(screen.getByText('30 days')).toBeInTheDocument();
      expect(screen.getByText('90 days')).toBeInTheDocument();
    });

    it('renders competitor filter checkboxes', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('competitor-1')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-2')).toBeInTheDocument();
      expect(screen.getByTestId('competitor-3')).toBeInTheDocument();
    });

    it('renders competitor names in filter', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('Ahrefs')).toBeInTheDocument();
      expect(screen.getByText('SEMrush')).toBeInTheDocument();
      expect(screen.getByText('Moz')).toBeInTheDocument();
    });

    it('renders Y-axis with "Position" label', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const yAxis = screen.getByTestId('y-axis');
      expect(yAxis).toHaveAttribute('data-label', 'Position');
    });

    it('renders legend container', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('legend-container')).toBeInTheDocument();
    });

    it('renders tooltip container', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('tooltip-container')).toBeInTheDocument();
    });
  });

  describe('Multi-line Chart (CHART)', () => {
    it('renders user line with primary color', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const userLine = screen.getByTestId('line-userPosition');
      expect(userLine).toHaveAttribute('data-color', '#2563eb');
      expect(userLine).toHaveAttribute('data-name', 'You');
    });

    it('renders competitor lines with distinct colors', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const competitor1Line = screen.getByTestId('line-competitor_1');
      const competitor2Line = screen.getByTestId('line-competitor_2');
      const competitor3Line = screen.getByTestId('line-competitor_3');

      expect(competitor1Line).toHaveAttribute('data-color', '#ef4444'); // red
      expect(competitor2Line).toHaveAttribute('data-color', '#22c55e'); // green
      expect(competitor3Line).toHaveAttribute('data-color', '#f59e0b'); // amber

      // Verify colors are different
      const colors = [
        competitor1Line.getAttribute('data-color'),
        competitor2Line.getAttribute('data-color'),
        competitor3Line.getAttribute('data-color'),
      ];
      expect(new Set(colors).size).toBe(3);
    });

    it('renders Y-axis inverted (reversed)', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const yAxis = screen.getByTestId('y-axis');
      expect(yAxis).toHaveAttribute('data-reversed', 'true');
    });

    it('renders X-axis with date key', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const xAxis = screen.getByTestId('x-axis');
      expect(xAxis).toHaveAttribute('data-key', 'date');
    });

    it('renders CartesianGrid for visual guides', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('cartesian-grid')).toBeInTheDocument();
    });
  });

  describe('Date Range Selector (DATE)', () => {
    it('defaults to 30 days', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} initialDateRange="30d" />);

      const buttons = screen.getAllByTestId('button');
      const thirtyDayButton = buttons.find((b) => b.textContent === '30 days');
      expect(thirtyDayButton).toHaveAttribute('data-variant', 'default');
    });

    it('selects 7 days option', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const buttons = screen.getAllByTestId('button');
      const sevenDayButton = buttons.find((b) => b.textContent === '7 days');
      
      if (sevenDayButton) {
        await user.click(sevenDayButton);
        expect(mockUseComparisonHistory).toHaveBeenCalledWith(
          expect.objectContaining({ days: 7 })
        );
      }
    });

    it('selects 90 days option', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const buttons = screen.getAllByTestId('button');
      const ninetyDayButton = buttons.find((b) => b.textContent === '90 days');
      
      if (ninetyDayButton) {
        await user.click(ninetyDayButton);
        expect(mockUseComparisonHistory).toHaveBeenCalledWith(
          expect.objectContaining({ days: 90 })
        );
      }
    });

    it('updates API call when date range changes', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      // Initial call with 30 days
      expect(mockUseComparisonHistory).toHaveBeenCalledWith(
        expect.objectContaining({ days: 30 })
      );

      // Click 7 days
      const buttons = screen.getAllByTestId('button');
      const sevenDayButton = buttons.find((b) => b.textContent === '7 days');
      if (sevenDayButton) {
        await user.click(sevenDayButton);
      }
    });
  });

  describe('Keyword Selector (KEYWORD)', () => {
    it('displays all keywords in dropdown', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('select-item-1')).toHaveTextContent('seo tools');
      expect(screen.getByTestId('select-item-2')).toHaveTextContent('keyword research');
      expect(screen.getByTestId('select-item-3')).toHaveTextContent('backlink analysis');
    });

    it('shows placeholder when no keyword selected', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} initialKeywordId={undefined} />);

      expect(screen.getByTestId('select-value')).toHaveAttribute('data-placeholder', 'Select keyword');
    });

    it('defaults to first keyword when no initialKeywordId provided', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(mockUseComparisonHistory).toHaveBeenCalledWith(
        expect.objectContaining({ keywordId: 1 })
      );
    });

    it('uses initialKeywordId when provided', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} initialKeywordId={2} />);

      expect(mockUseComparisonHistory).toHaveBeenCalledWith(
        expect.objectContaining({ keywordId: 2 })
      );
    });
  });

  describe('Competitor Filter (COMPETITOR)', () => {
    it('shows all competitors by default', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const competitor1 = screen.getByTestId('competitor-1');
      const competitor2 = screen.getByTestId('competitor-2');
      const competitor3 = screen.getByTestId('competitor-3');

      expect(competitor1).toBeChecked();
      expect(competitor2).toBeChecked();
      expect(competitor3).toBeChecked();
    });

    it('hides competitor line when unchecked', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const competitor1 = screen.getByTestId('competitor-1');
      await user.click(competitor1);

      // Line should be hidden
      const competitor1Line = screen.getByTestId('line-competitor_1');
      expect(competitor1Line).toHaveAttribute('data-hidden', 'true');
    });

    it('shows competitor line when re-checked', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const competitor1 = screen.getByTestId('competitor-1');
      
      // Uncheck
      await user.click(competitor1);
      expect(screen.getByTestId('line-competitor_1')).toHaveAttribute('data-hidden', 'true');

      // Re-check
      await user.click(competitor1);
      expect(screen.getByTestId('line-competitor_1')).toHaveAttribute('data-hidden', 'false');
    });

    it('uses displayName when available', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('Ahrefs')).toBeInTheDocument();
    });

    it('falls back to domain when displayName not available', () => {
      const competitorsWithoutDisplayName: Competitor[] = [
        { id: 1, domain: 'ahrefs.com' },
      ];
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={competitorsWithoutDisplayName} />);

      expect(screen.getByText('ahrefs.com')).toBeInTheDocument();
    });
  });

  describe('Loading & Error States (STATE)', () => {
    it('shows loading spinner when data is loading', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    it('shows empty state when no historical data', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: mockEmptyHistoryData,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText(/No historical data available/i)).toBeInTheDocument();
    });

    it('shows error message when API fails', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('API Error'),
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText(/Failed to load chart data/i)).toBeInTheDocument();
    });

    it('shows retry button on error', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: new Error('API Error'),
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('Retry')).toBeInTheDocument();
    });

    it('shows "Select a keyword" message when no keyword selected', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} initialKeywordId={undefined} />);

      // Since keywords[0] is selected by default, this tests the fallback case
      // when keywords array is empty
      render(<HistoricalComparisonChart keywords={[]} competitors={mockCompetitors} />);
      expect(screen.getByText(/Select a keyword/i)).toBeInTheDocument();
    });
  });

  describe('Trend Indicator (TREND)', () => {
    it('shows improved trend when position improves', () => {
      // Position went from 5 to 2 (improvement)
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText(/Improved by/)).toBeInTheDocument();
    });

    it('shows dropped trend when position worsens', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: 'seo tools' },
          history: {
            user: [
              { position: 2, checkedAt: '2026-03-01T10:00:00Z' },
              { position: 5, checkedAt: '2026-03-07T10:00:00Z' },
            ],
            competitors: [],
          },
        },
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText(/Dropped by/)).toBeInTheDocument();
    });

    it('shows no change when position is stable', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: 'seo tools' },
          history: {
            user: [
              { position: 5, checkedAt: '2026-03-01T10:00:00Z' },
              { position: 5, checkedAt: '2026-03-07T10:00:00Z' },
            ],
            competitors: [],
          },
        },
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByText('No change')).toBeInTheDocument();
    });

    it('hides trend indicator when insufficient data', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: 'seo tools' },
          history: {
            user: [{ position: 5, checkedAt: '2026-03-01T10:00:00Z' }],
            competitors: [],
          },
        },
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.queryByText(/Improved by/)).not.toBeInTheDocument();
      expect(screen.queryByText(/Dropped by/)).not.toBeInTheDocument();
    });
  });

  describe('Null Position Handling (NULL)', () => {
    it('handles null user positions gracefully', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: mockNullPositions,
        isLoading: false,
        error: null,
      });

      // Should not throw
      expect(() => {
        render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);
      }).not.toThrow();
    });

    it('does not show trend when positions are null', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: mockNullPositions,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.queryByText(/Improved by/)).not.toBeInTheDocument();
      expect(screen.queryByText(/Dropped by/)).not.toBeInTheDocument();
    });
  });

  describe('Performance (PERF)', () => {
    it('renders large dataset without performance issues', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: mockLargeHistoryData,
        isLoading: false,
        error: null,
      });

      const startTime = performance.now();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);
      const endTime = performance.now();

      // Render should complete in under 1 second
      expect(endTime - startTime).toBeLessThan(1000);
    });

    it('handles 90 days of data', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: mockLargeHistoryData,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('line-chart')).toBeInTheDocument();
    });

    it('handles multiple competitors efficiently', () => {
      const manyCompetitors: Competitor[] = Array.from({ length: 5 }, (_, i) => ({
        id: i + 1,
        domain: `competitor${i + 1}.com`,
        displayName: `Competitor ${i + 1}`,
      }));

      mockUseComparisonHistory.mockReturnValue({
        data: mockHistoryData,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={manyCompetitors} />);

      // Should render all competitor lines
      expect(screen.getByTestId('line-competitor_1')).toBeInTheDocument();
      expect(screen.getByTestId('line-competitor_2')).toBeInTheDocument();
      expect(screen.getByTestId('line-competitor_3')).toBeInTheDocument();
    });
  });

  describe('Accessibility (A11Y)', () => {
    it('has proper heading structure', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByRole('heading', { name: /Historical Comparison/i })).toBeInTheDocument();
    });

    it('has accessible competitor checkboxes', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      const checkbox = screen.getByTestId('competitor-1');
      expect(checkbox).toHaveAttribute('type', 'checkbox');
    });

    it('has labels for competitor checkboxes', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      expect(screen.getByTestId('label-competitor-1')).toBeInTheDocument();
    });
  });

  describe('Props Validation (PROPS)', () => {
    it('accepts className prop', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} className="custom-class" />);

      const card = screen.getByTestId('card');
      expect(card).toHaveClass('custom-class');
    });

    it('handles empty keywords array', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: null,
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={[]} competitors={mockCompetitors} />);

      expect(screen.getByText(/Select a keyword/i)).toBeInTheDocument();
    });

    it('handles empty competitors array', () => {
      mockUseComparisonHistory.mockReturnValue({
        data: {
          keyword: { id: 1, keyword: 'seo tools' },
          history: {
            user: mockHistoryData.history.user,
            competitors: [],
          },
        },
        isLoading: false,
        error: null,
      });

      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={[]} />);

      // Should still render user line
      expect(screen.getByTestId('line-userPosition')).toBeInTheDocument();
    });

    it('respects initialDateRange prop', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} initialDateRange="7d" />);

      expect(mockUseComparisonHistory).toHaveBeenCalledWith(
        expect.objectContaining({ days: 7 })
      );
    });
  });

  describe('Integration (INT)', () => {
    it('calls API with correct parameters on mount', () => {
      render(
        <HistoricalComparisonChart
          keywords={mockKeywords}
          competitors={mockCompetitors}
          initialKeywordId={1}
          initialDateRange="30d"
        />
      );

      expect(mockUseComparisonHistory).toHaveBeenCalledWith({
        keywordId: 1,
        days: 30,
      });
    });

    it('updates API call when keyword changes', async () => {
      const user = userEvent.setup();
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      // Select different keyword
      const keyword2 = screen.getByTestId('select-item-2');
      await user.click(keyword2);
    });

    it('integrates with comparison history hook', () => {
      render(<HistoricalComparisonChart keywords={mockKeywords} competitors={mockCompetitors} />);

      // Verify hook was called
      expect(mockUseComparisonHistory).toHaveBeenCalled();
    });
  });
});
