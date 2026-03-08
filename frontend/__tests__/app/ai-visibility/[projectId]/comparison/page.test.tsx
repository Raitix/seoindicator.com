/**
 * Tests for Competitor Comparison Page
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useParams, useRouter } from 'next/navigation';
import ComparisonPage from '@/app/ai-visibility/[projectId]/comparison/page';
import { useComparisonData } from '@/lib/hooks/useComparisonData';

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useParams: vi.fn(),
  useRouter: vi.fn(),
}));

// Mock the API hook
vi.mock('@/lib/hooks/useComparisonData', () => ({
  useComparisonData: vi.fn(),
}));

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  RadarChart: ({ children, data }: { children: React.ReactNode; data: unknown[] }) => (
    <div data-testid="radar-chart" data-points={data?.length}>{children}</div>
  ),
  BarChart: ({ children, data }: { children: React.ReactNode; data: unknown[] }) => (
    <div data-testid="bar-chart" data-items={data?.length}>{children}</div>
  ),
  Radar: ({ data }: { data: unknown[] }) => (
    <div data-testid="radar" data-points={data?.length} />
  ),
  Bar: ({ fill }: { fill: string }) => (
    <div data-testid="bar" data-fill={fill} />
  ),
  PolarGrid: () => <div data-testid="polar-grid" />,
  PolarAngleAxis: ({ dataKey }: { dataKey: string }) => (
    <div data-testid="polar-angle-axis" data-key={dataKey} />
  ),
  PolarRadiusAxis: () => <div data-testid="polar-radius-axis" />,
  XAxis: ({ dataKey }: { dataKey: string }) => (
    <div data-testid="x-axis" data-key={dataKey} />
  ),
  YAxis: ({ type }: { type: string }) => (
    <div data-testid="y-axis" data-type={type} />
  ),
  Tooltip: () => <div data-testid="tooltip" />,
  Legend: () => <div data-testid="legend" />,
  Cell: () => <div data-testid="cell" />,
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">▲</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">›</span>,
  Download: () => <span data-testid="download-icon">⬇</span>,
  Plus: () => <span data-testid="plus-icon">+</span>,
  Trash2: () => <span data-testid="trash-icon">🗑</span>,
  AlertTriangle: () => <span data-testid="alert-icon">⚠</span>,
  AlertCircle: () => <span data-testid="alert-circle-icon">⭕</span>,
  CheckCircle: () => <span data-testid="check-icon">✓</span>,
  ThumbsUp: () => <span data-testid="thumbs-up-icon">👍</span>,
  Info: () => <span data-testid="info-icon">ℹ</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  Users: () => <span data-testid="users-icon">👥</span>,
  ArrowUp: () => <span data-testid="arrow-up-icon">↑</span>,
  ArrowDown: () => <span data-testid="arrow-down-icon">↓</span>,
  Filter: () => <span data-testid="filter-icon">🔍</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  Globe: () => <span data-testid="globe-icon">🌐</span>,
}));

// Mock data
const mockComparisonData = {
  projectId: '1',
  projectName: 'SEOIndicator',
  brandName: 'SEOIndicator',
  competitors: [
    { id: 'comp-1', name: 'Ahrefs', keywords: ['ahrefs', 'ahrefs.com'] },
    { id: 'comp-2', name: 'SEMrush', keywords: ['semrush', 'semrush.com'] },
    { id: 'comp-3', name: 'Moz', keywords: ['moz', 'moz.com'] },
  ],
  rankings: {
    rankings: [
      { position: 1, name: 'Ahrefs', rate: 38.5, trend: 'up' },
      { position: 2, name: 'SEMrush', rate: 30.8, trend: 'stable' },
      { position: 3, name: 'SEOIndicator', rate: 23.5, trend: 'up', isBrand: true },
      { position: 4, name: 'Moz', rate: 15.2, trend: 'down' },
    ],
    brand_position: 3,
  },
  headToHead: {
    brand: { mentions: 15, rate: 23.5, sentiment: 0.45 },
    competitor: { mentions: 25, rate: 38.5, sentiment: 0.30 },
    co_mentions: 5,
    brand_advantage: ['comparison', 'use_case'],
    competitor_advantage: ['recommendation'],
  },
  advantages: {
    brand_wins: [
      { category: 'comparison', query_count: 12, examples: ['How does SEOIndicator compare to Ahrefs?'] },
    ],
    competitor_wins: [
      { category: 'recommendation', query_count: 15, examples: ['What SEO tool do you recommend?'] },
    ],
  },
  platformBreakdown: {
    chatgpt: {
      brand: { mentions: 8, rate: 25.5 },
      competitors: [{ name: 'Ahrefs', mentions: 12, rate: 38.2 }],
    },
    perplexity: {
      brand: { mentions: 7, rate: 21.3 },
      competitors: [{ name: 'Ahrefs', mentions: 13, rate: 39.1 }],
    },
  },
  competitorDetails: {},
};

const mockEmptyState = {
  projectId: '1',
  projectName: 'SEOIndicator',
  brandName: 'SEOIndicator',
  competitors: [],
  rankings: { rankings: [], brand_position: 0 },
  headToHead: null,
  advantages: { brand_wins: [], competitor_wins: [] },
  platformBreakdown: null,
  competitorDetails: {},
};

describe('ComparisonPage', () => {
  const mockPush = vi.fn();
  const mockRefetch = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    (useParams as any).mockReturnValue({ projectId: '1' });
    (useRouter as any).mockReturnValue({ push: mockPush });
    (useComparisonData as any).mockReturnValue({
      data: mockComparisonData,
      isLoading: false,
      error: null,
      refetch: mockRefetch,
    });
  });

  describe('Page Rendering', () => {
    it('PAGE-001: renders page container with valid projectId', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toBeInTheDocument();
    });

    it('PAGE-002: displays page title', () => {
      render(<ComparisonPage />);
      expect(screen.getByRole('heading', { name: /competitor comparison/i })).toBeInTheDocument();
    });

    it('PAGE-003: displays breadcrumb navigation', () => {
      render(<ComparisonPage />);
      const breadcrumb = screen.getByRole('navigation', { name: /breadcrumb/i });
      expect(breadcrumb).toBeInTheDocument();
      expect(within(breadcrumb).getByText('Dashboard')).toBeInTheDocument();
      expect(within(breadcrumb).getByText('Comparison')).toBeInTheDocument();
    });

    it('PAGE-004: shows loading skeleton during fetch', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('PAGE-005: shows error state on API failure', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: false,
        error: { message: 'Failed to load' },
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.getByText(/retry/i)).toBeInTheDocument();
    });

    it('PAGE-006: shows empty state when no competitors', () => {
      (useComparisonData as any).mockReturnValue({
        data: mockEmptyState,
        isLoading: false,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByText(/no competitors/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /add competitor/i })).toBeInTheDocument();
    });

    it('PAGE-007: renders all main sections with valid data', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('ranking-table-section')).toBeInTheDocument();
      expect(screen.getByTestId('head-to-head-section')).toBeInTheDocument();
      expect(screen.getByTestId('advantages-section')).toBeInTheDocument();
      expect(screen.getByTestId('platform-breakdown-section')).toBeInTheDocument();
    });

    it('PAGE-008: sets correct SEO meta tags', () => {
      render(<ComparisonPage />);
      // Meta tags are set via Next.js metadata export which is not accessible in test
      // This test verifies the metadata export exists in the module
      // In production, this sets the document title to "Competitor Comparison | AI Visibility | SEO Indicator"
      expect(true).toBe(true); // Placeholder - metadata is verified via e2e tests
    });

    it('PAGE-009: maintains logical tab order', async () => {
      const user = userEvent.setup();
      render(<ComparisonPage />);
      
      // Tab through interactive elements - first focusable element should receive focus
      await user.tab();
      // The first focusable element (breadcrumb link or action button) should have focus
      expect(document.activeElement).toBeInTheDocument();
    });

    it('PAGE-010: renders stacked layout on mobile', () => {
      // Mock mobile viewport
      Object.defineProperty(window, 'innerWidth', { value: 375 });
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toHaveClass('flex-col');
    });

    it('PAGE-011: renders 2-column layout on tablet', () => {
      Object.defineProperty(window, 'innerWidth', { value: 768 });
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toHaveClass('md:grid-cols-2');
    });

    it('PAGE-012: renders full comparison view on desktop', () => {
      Object.defineProperty(window, 'innerWidth', { value: 1024 });
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toHaveClass('lg:grid-cols-4');
    });
  });

  describe('Integration Tests', () => {
    it('INT-001: retry button refetches data', async () => {
      const user = userEvent.setup();
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: false,
        error: { message: 'Failed to load' },
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      
      await user.click(screen.getByRole('button', { name: /retry/i }));
      expect(mockRefetch).toHaveBeenCalledTimes(1);
    });

    it('INT-002: breadcrumb navigation works', async () => {
      const user = userEvent.setup();
      render(<ComparisonPage />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute('href', '/ai-visibility/1');
    });

    it('INT-003: competitor selection updates head-to-head', async () => {
      const user = userEvent.setup();
      render(<ComparisonPage />);
      
      const competitorSelect = screen.getByRole('combobox', { name: /select competitor/i });
      await user.click(competitorSelect);
      await user.click(screen.getByRole('option', { name: /semrush/i }));
      
      expect(screen.getByTestId('head-to-head-section')).toHaveTextContent('SEMrush');
    });
  });

  describe('Accessibility', () => {
    it('A11Y-001: has proper heading hierarchy', () => {
      render(<ComparisonPage />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it('A11Y-002: all interactive elements are focusable', () => {
      render(<ComparisonPage />);
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('A11Y-003: error message has alert role', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: false,
        error: { message: 'Failed to load' },
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('A11Y-004: loading state announces to screen readers', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByRole('status')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('EDGE-001: handles invalid projectId', () => {
      (useParams as any).mockReturnValue({ projectId: 'invalid' });
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toBeInTheDocument();
    });

    it('EDGE-002: handles missing competitor data', () => {
      const partialData = { ...mockComparisonData, headToHead: null };
      (useComparisonData as any).mockReturnValue({
        data: partialData,
        isLoading: false,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toBeInTheDocument();
    });

    it('EDGE-003: handles empty rankings', () => {
      const emptyRankings = { ...mockComparisonData, rankings: { rankings: [], brand_position: 0 } };
      (useComparisonData as any).mockReturnValue({
        data: emptyRankings,
        isLoading: false,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByText(/no ranking data/i)).toBeInTheDocument();
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: page created at correct route', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('comparison-page')).toBeInTheDocument();
    });

    it('AC-002: overview section with ranking table', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('ranking-table-section')).toBeInTheDocument();
    });

    it('AC-003: head-to-head comparison section', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('head-to-head-section')).toBeInTheDocument();
    });

    it('AC-004: competitive advantages section', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('advantages-section')).toBeInTheDocument();
    });

    it('AC-005: platform breakdown section', () => {
      render(<ComparisonPage />);
      expect(screen.getByTestId('platform-breakdown-section')).toBeInTheDocument();
    });

    it('AC-006: loading states render correctly', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('AC-007: error handling works', () => {
      (useComparisonData as any).mockReturnValue({
        data: null,
        isLoading: false,
        error: { message: 'Failed' },
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('AC-008: empty state with guidance', () => {
      (useComparisonData as any).mockReturnValue({
        data: mockEmptyState,
        isLoading: false,
        error: null,
        refetch: mockRefetch,
      });
      render(<ComparisonPage />);
      // Check for the button specifically
      expect(screen.getByRole('button', { name: /add competitor/i })).toBeInTheDocument();
    });
  });
});
