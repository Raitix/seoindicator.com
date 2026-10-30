/**
 * Tests for CompetitorDetailPanel Component
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CompetitorDetailPanel from '@/components/ai-visibility/CompetitorDetailPanel';

// Mock Recharts
vi.mock('recharts', () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="responsive-container">{children}</div>
  ),
  PieChart: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="pie-chart">{children}</div>
  ),
  Pie: ({ data }: { data: unknown[] }) => (
    <div data-testid="pie" data-segments={data?.length} />
  ),
  Cell: ({ fill }: { fill: string }) => (
    <div data-testid="cell" data-fill={fill} />
  ),
  Tooltip: () => <div data-testid="tooltip" />,
  BarChart: ({ children, data }: { children: React.ReactNode; data: unknown[] }) => (
    <div data-testid="bar-chart" data-items={data?.length}>{children}</div>
  ),
  Bar: ({ fill }: { fill: string }) => (
    <div data-testid="bar" data-fill={fill} />
  ),
  XAxis: () => <div data-testid="x-axis" />,
  YAxis: () => <div data-testid="y-axis" />,
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">▲</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  Globe: () => <span data-testid="globe-icon">🌐</span>,
}));

const mockCompetitorDetails = {
  recent_mentions: [
    { context: 'Ahrefs is great for backlink analysis...', platform: 'chatgpt', sentiment: 'positive' },
    { context: 'I recommend Ahrefs for keyword research...', platform: 'perplexity', sentiment: 'positive' },
    { context: 'Ahrefs is expensive compared to alternatives...', platform: 'chatgpt', sentiment: 'negative' },
  ],
  sentiment_breakdown: { positive: 15, neutral: 8, negative: 2 },
  query_categories: ['recommendation', 'feature', 'comparison'],
  platforms: ['chatgpt', 'perplexity'],
};

const mockProps = {
  competitorName: 'Ahrefs',
  details: mockCompetitorDetails,
  isExpanded: false,
  onToggle: vi.fn(),
};

describe('CompetitorDetailPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('DET-001: renders collapsed by default', () => {
      render(<CompetitorDetailPanel {...mockProps} />);
      expect(screen.getByTestId('competitor-panel')).toBeInTheDocument();
      expect(screen.queryByTestId('panel-content')).not.toBeInTheDocument();
    });

    it('DET-002: expands on header click', async () => {
      const user = userEvent.setup();
      const onToggle = vi.fn();
      render(<CompetitorDetailPanel {...mockProps} onToggle={onToggle} />);
      
      await user.click(screen.getByRole('button', { name: /ahrefs/i }));
      
      expect(onToggle).toHaveBeenCalled();
    });

    it('DET-003: shows expanded state', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByTestId('panel-content')).toBeInTheDocument();
    });

    it('DET-004: displays competitor name in header', () => {
      render(<CompetitorDetailPanel {...mockProps} />);
      expect(screen.getByText('Ahrefs')).toBeInTheDocument();
    });
  });

  describe('Expanded Content', () => {
    const expandedProps = { ...mockProps, isExpanded: true };

    it('DET-005: shows recent mentions section', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      expect(screen.getByText(/recent mentions/i)).toBeInTheDocument();
    });

    it('DET-006: shows sentiment breakdown chart', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      expect(screen.getByTestId('pie-chart')).toBeInTheDocument();
    });

    it('DET-007: shows query categories', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      expect(screen.getByText(/recommendation/i)).toBeInTheDocument();
      expect(screen.getByText(/feature/i)).toBeInTheDocument();
      expect(screen.getByText(/comparison/i)).toBeInTheDocument();
    });

    it('DET-008: shows platform distribution', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
  });

  describe('Mention List', () => {
    const expandedProps = { ...mockProps, isExpanded: true };

    it('DET-009: displays all mentions', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      
      mockCompetitorDetails.recent_mentions.forEach(mention => {
        expect(screen.getByText(new RegExp(mention.context.substring(0, 20), 'i'))).toBeInTheDocument();
      });
    });

    it('DET-010: shows context snippet', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      expect(screen.getByText(/backlink analysis/i)).toBeInTheDocument();
    });

    it('DET-011: shows platform badge', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      // Use getAllByText since there may be multiple mentions with the same platform
      expect(screen.getAllByText('ChatGPT').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Perplexity').length).toBeGreaterThan(0);
    });
  });

  describe('Sentiment Badges', () => {
    const expandedProps = { ...mockProps, isExpanded: true };

    it('DET-012: shows positive sentiment badge in green', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      // Use getAllByText since there may be multiple mentions with the same sentiment
      const positiveBadges = screen.getAllByText('positive');
      expect(positiveBadges.length).toBeGreaterThan(0);
      expect(positiveBadges[0]).toHaveClass('bg-green-100');
    });

    it('DET-013: shows negative sentiment badge in red', () => {
      render(<CompetitorDetailPanel {...expandedProps} />);
      const negativeBadges = screen.getAllByText('negative');
      expect(negativeBadges.length).toBeGreaterThan(0);
      expect(negativeBadges[0]).toHaveClass('bg-red-100');
    });
  });

  describe('Keyboard Navigation', () => {
    it('DET-014: toggles with Enter key', async () => {
      const user = userEvent.setup();
      const onToggle = vi.fn();
      render(<CompetitorDetailPanel {...mockProps} onToggle={onToggle} />);
      
      const button = screen.getByRole('button', { name: /ahrefs/i });
      button.focus();
      await user.keyboard('{Enter}');
      
      expect(onToggle).toHaveBeenCalled();
    });

    it('DET-015: toggles with Space key', async () => {
      const user = userEvent.setup();
      const onToggle = vi.fn();
      render(<CompetitorDetailPanel {...mockProps} onToggle={onToggle} />);
      
      const button = screen.getByRole('button', { name: /ahrefs/i });
      button.focus();
      await user.keyboard(' ');
      
      expect(onToggle).toHaveBeenCalled();
    });
  });

  describe('Accordion Behavior', () => {
    it('DET-016: shows chevron down when collapsed', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={false} />);
      expect(screen.getByTestId('chevron-down-icon')).toBeInTheDocument();
    });

    it('DET-017: shows chevron up when expanded', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByTestId('chevron-up-icon')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('DET-018: handles empty mentions', () => {
      const emptyDetails = { ...mockCompetitorDetails, recent_mentions: [] };
      render(<CompetitorDetailPanel {...mockProps} details={emptyDetails} isExpanded={true} />);
      
      expect(screen.getByText(/no mentions/i)).toBeInTheDocument();
    });

    it('DET-019: handles empty sentiment breakdown', () => {
      const emptyDetails = { ...mockCompetitorDetails, sentiment_breakdown: { positive: 0, neutral: 0, negative: 0 } };
      render(<CompetitorDetailPanel {...mockProps} details={emptyDetails} isExpanded={true} />);
      
      expect(screen.getByText(/no sentiment data/i)).toBeInTheDocument();
    });

    it('DET-020: handles null details', () => {
      render(<CompetitorDetailPanel {...mockProps} details={null as any} isExpanded={true} />);
      expect(screen.getByText(/no details available/i)).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('DET-021: has proper aria-expanded', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={false} />);
      const button = screen.getByRole('button', { name: /ahrefs/i });
      
      expect(button).toHaveAttribute('aria-expanded', 'false');
    });

    it('DET-022: updates aria-expanded on toggle', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      const button = screen.getByRole('button', { name: /ahrefs/i });
      
      expect(button).toHaveAttribute('aria-expanded', 'true');
    });

    it('DET-023: has accessible region when expanded', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByRole('region', { name: /ahrefs details/i })).toBeInTheDocument();
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: panel expands on click', async () => {
      const user = userEvent.setup();
      const onToggle = vi.fn();
      render(<CompetitorDetailPanel {...mockProps} onToggle={onToggle} />);
      
      await user.click(screen.getByRole('button', { name: /ahrefs/i }));
      expect(onToggle).toHaveBeenCalled();
    });

    it('AC-002: shows recent mentions', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByText(/recent mentions/i)).toBeInTheDocument();
    });

    it('AC-003: shows sentiment breakdown', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByText(/sentiment/i)).toBeInTheDocument();
    });

    it('AC-004: shows platform distribution', () => {
      render(<CompetitorDetailPanel {...mockProps} isExpanded={true} />);
      expect(screen.getByTestId('bar-chart')).toBeInTheDocument();
    });
  });
});
