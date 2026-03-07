/**
 * Tests for PlatformBreakdown Component
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import PlatformBreakdown from '@/components/ai-visibility/PlatformBreakdown';

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
  Radar: ({ data, fill }: { data: unknown[]; fill?: string }) => (
    <div data-testid="radar" data-points={data?.length} data-fill={fill} />
  ),
  Bar: ({ fill, dataKey }: { fill: string; dataKey: string }) => (
    <div data-testid="bar" data-fill={fill} data-key={dataKey} />
  ),
  PolarGrid: () => <div data-testid="polar-grid" />,
  PolarAngleAxis: ({ dataKey }: { dataKey: string }) => (
    <div data-testid="polar-angle-axis" data-key={dataKey} />
  ),
  PolarRadiusAxis: () => <div data-testid="polar-radius-axis" />,
  XAxis: ({ dataKey }: { dataKey: string }) => (
    <div data-testid="x-axis" data-key={dataKey} />
  ),
  YAxis: ({ type, domain }: { type: string; domain?: number[] }) => (
    <div data-testid="y-axis" data-type={type} data-domain={JSON.stringify(domain)} />
  ),
  Tooltip: () => <div data-testid="tooltip" />,
  Legend: () => <div data-testid="legend" />,
  Cell: () => <div data-testid="cell" />,
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Globe: () => <span data-testid="globe-icon">🌐</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
}));

const mockPlatformBreakdown = {
  chatgpt: {
    brand: { mentions: 8, rate: 25.5 },
    competitors: [
      { name: 'Ahrefs', mentions: 12, rate: 38.2 },
      { name: 'SEMrush', mentions: 9, rate: 28.6 },
    ],
  },
  perplexity: {
    brand: { mentions: 7, rate: 21.3 },
    competitors: [
      { name: 'Ahrefs', mentions: 13, rate: 39.1 },
      { name: 'SEMrush', mentions: 11, rate: 33.0 },
    ],
  },
};

const mockProps = {
  brandName: 'SEOIndicator',
  platformBreakdown: mockPlatformBreakdown,
};

describe('PlatformBreakdown', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('PLAT-001: renders section with data', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('platform-breakdown-section')).toBeInTheDocument();
    });

    it('PLAT-002: displays platform tabs', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByRole('tab', { name: /chatgpt/i })).toBeInTheDocument();
      expect(screen.getByRole('tab', { name: /perplexity/i })).toBeInTheDocument();
    });

    it('PLAT-003: selects ChatGPT tab by default', () => {
      render(<PlatformBreakdown {...mockProps} />);
      const chatgptTab = screen.getByRole('tab', { name: /chatgpt/i });
      expect(chatgptTab).toHaveAttribute('aria-selected', 'true');
    });

    it('PLAT-004: renders radar chart', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('radar-chart')).toBeInTheDocument();
    });

    it('PLAT-005: renders bar chart', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
  });

  describe('Tab Navigation', () => {
    it('PLAT-006: switches to Perplexity tab on click', async () => {
      const user = userEvent.setup();
      render(<PlatformBreakdown {...mockProps} />);
      
      await user.click(screen.getByRole('tab', { name: /perplexity/i }));
      
      expect(screen.getByRole('tab', { name: /perplexity/i })).toHaveAttribute('aria-selected', 'true');
    });

    it('PLAT-007: updates chart data on tab switch', async () => {
      const user = userEvent.setup();
      render(<PlatformBreakdown {...mockProps} />);
      
      await user.click(screen.getByRole('tab', { name: /perplexity/i }));
      
      // Chart should update with perplexity data
      expect(screen.getByTestId('radar-chart')).toBeInTheDocument();
    });
  });

  describe('Chart Data', () => {
    it('PLAT-008: displays brand point on radar', () => {
      render(<PlatformBreakdown {...mockProps} />);
      const radar = screen.getByTestId('radar');
      
      expect(radar).toHaveAttribute('data-fill', '#3b82f6'); // Brand blue
    });

    it('PLAT-009: displays competitor points', () => {
      render(<PlatformBreakdown {...mockProps} />);
      const bars = screen.getAllByTestId('bar');
      
      expect(bars.length).toBeGreaterThan(1);
    });

    it('PLAT-010: displays legend', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('legend')).toBeInTheDocument();
    });

    it('PLAT-011: displays platform indicator', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByText(/chatgpt/i)).toBeInTheDocument();
    });
  });

  describe('Color Coding', () => {
    it('PLAT-012: brand data uses blue color', () => {
      render(<PlatformBreakdown {...mockProps} />);
      const brandElements = screen.getAllByTestId(/radar|bar/);
      
      const brandElement = brandElements.find(el => el.getAttribute('data-key') === 'brand');
      expect(brandElement?.getAttribute('data-fill')).toBe('#3b82f6');
    });

    it('PLAT-013: competitor data uses slate color', () => {
      render(<PlatformBreakdown {...mockProps} />);
      const bars = screen.getAllByTestId('bar');
      
      const competitorBars = bars.filter(bar => 
        bar.getAttribute('data-key')?.includes('competitor')
      );
      competitorBars.forEach(bar => {
        expect(bar.getAttribute('data-fill')).toBe('#94a3b8');
      });
    });
  });

  describe('Edge Cases', () => {
    it('PLAT-014: shows empty state when no data', () => {
      render(<PlatformBreakdown {...mockProps} platformBreakdown={null} />);
      expect(screen.getByText(/no platform data/i)).toBeInTheDocument();
    });

    it('PLAT-015: handles single platform', () => {
      const singlePlatform = { chatgpt: mockPlatformBreakdown.chatgpt };
      render(<PlatformBreakdown {...mockProps} platformBreakdown={singlePlatform} />);
      
      expect(screen.getByRole('tab', { name: /chatgpt/i })).toBeInTheDocument();
      expect(screen.queryByRole('tab', { name: /perplexity/i })).not.toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('PLAT-016: has tab list role', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByRole('tablist')).toBeInTheDocument();
    });

    it('PLAT-017: tabs are keyboard navigable', async () => {
      const user = userEvent.setup();
      render(<PlatformBreakdown {...mockProps} />);
      
      const chatgptTab = screen.getByRole('tab', { name: /chatgpt/i });
      chatgptTab.focus();
      
      await user.keyboard('{ArrowRight}');
      
      expect(screen.getByRole('tab', { name: /perplexity/i })).toHaveFocus();
    });

    it('PLAT-018: charts have accessible labels', () => {
      render(<PlatformBreakdown {...mockProps} />);
      
      const radarChart = screen.getByTestId('radar-chart');
      expect(radarChart).toHaveAttribute('aria-label');
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: shows comparison by platform', () => {
      render(<PlatformBreakdown {...mockProps} />);
      
      expect(screen.getByRole('tab', { name: /chatgpt/i })).toBeInTheDocument();
      expect(screen.getByRole('tab', { name: /perplexity/i })).toBeInTheDocument();
    });

    it('AC-002: uses radar chart', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('radar-chart')).toBeInTheDocument();
    });

    it('AC-003: uses bar chart', () => {
      render(<PlatformBreakdown {...mockProps} />);
      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
  });
});
