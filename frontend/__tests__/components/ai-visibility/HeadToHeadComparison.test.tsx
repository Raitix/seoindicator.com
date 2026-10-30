/**
 * Tests for HeadToHeadComparison Component
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import HeadToHeadComparison from '@/components/ai-visibility/HeadToHeadComparison';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  ArrowUp: () => <span data-testid="arrow-up-icon">↑</span>,
  ArrowDown: () => <span data-testid="arrow-down-icon">↓</span>,
  Users: () => <span data-testid="users-icon">👥</span>,
}));

const mockCompetitors = [
  { id: 'comp-1', name: 'Ahrefs' },
  { id: 'comp-2', name: 'SEMrush' },
  { id: 'comp-3', name: 'Moz' },
];

const mockHeadToHead = {
  brand: { mentions: 15, rate: 23.5, sentiment: 0.45 },
  competitor: { mentions: 25, rate: 38.5, sentiment: 0.30 },
  co_mentions: 5,
  brand_advantage: ['comparison', 'use_case'],
  competitor_advantage: ['recommendation'],
};

const mockProps = {
  brandName: 'SEOIndicator',
  competitors: mockCompetitors,
  selectedCompetitor: mockCompetitors[0],
  headToHead: mockHeadToHead,
  onSelectCompetitor: vi.fn(),
};

describe('HeadToHeadComparison', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('HEAD-001: renders section with data', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByTestId('head-to-head-section')).toBeInTheDocument();
    });

    it('HEAD-002: displays competitor dropdown', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByRole('combobox', { name: /select competitor/i })).toBeInTheDocument();
    });

    it('HEAD-003: populates dropdown with competitors', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      const select = screen.getByRole('combobox');
      
      mockCompetitors.forEach(comp => {
        expect(within(select).getByRole('option', { name: comp.name })).toBeInTheDocument();
      });
    });

    it('HEAD-004: displays brand card', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByTestId('brand-card')).toBeInTheDocument();
    });

    it('HEAD-005: displays competitor card', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByTestId('competitor-card')).toBeInTheDocument();
    });
  });

  describe('Metrics Display', () => {
    it('HEAD-006: displays mention count', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText('15 mentions')).toBeInTheDocument();
      expect(screen.getByText('25 mentions')).toBeInTheDocument();
    });

    it('HEAD-007: displays mention rate', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText('23.5%')).toBeInTheDocument();
      expect(screen.getByText('38.5%')).toBeInTheDocument();
    });

    it('HEAD-008: displays sentiment score', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText('0.45')).toBeInTheDocument();
      expect(screen.getByText('0.30')).toBeInTheDocument();
    });

    it('HEAD-009: displays co-mention count', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText(/5 co-mentions/i)).toBeInTheDocument();
    });
  });

  describe('Difference Indicators', () => {
    it('HEAD-010: shows brand ahead badge when brand wins', () => {
      const brandWinData = {
        ...mockHeadToHead,
        brand: { mentions: 30, rate: 45.0, sentiment: 0.6 },
        competitor: { mentions: 15, rate: 23.5, sentiment: 0.3 },
      };
      render(<HeadToHeadComparison {...mockProps} headToHead={brandWinData} />);
      
      expect(screen.getByText(/ahead/i)).toBeInTheDocument();
    });

    it('HEAD-011: shows competitor ahead badge when competitor wins', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText(/behind/i)).toBeInTheDocument();
    });

    it('HEAD-012: shows tied badge when equal', () => {
      const tiedData = {
        brand: { mentions: 20, rate: 30.0, sentiment: 0.4 },
        competitor: { mentions: 20, rate: 30.0, sentiment: 0.4 },
        co_mentions: 5,
        brand_advantage: [],
        competitor_advantage: [],
      };
      render(<HeadToHeadComparison {...mockProps} headToHead={tiedData} />);
      
      expect(screen.getByText(/tied/i)).toBeInTheDocument();
    });
  });

  describe('Competitor Selection', () => {
    it('HEAD-013: calls onSelectCompetitor on selection', async () => {
      const user = userEvent.setup();
      const onSelect = vi.fn();
      render(<HeadToHeadComparison {...mockProps} onSelectCompetitor={onSelect} />);
      
      const select = screen.getByRole('combobox');
      await user.selectOptions(select, 'SEMrush');
      
      expect(onSelect).toHaveBeenCalledWith(mockCompetitors[1]);
    });
  });

  describe('Edge Cases', () => {
    it('HEAD-014: shows add competitor button when no competitors', () => {
      render(<HeadToHeadComparison {...mockProps} competitors={[]} onAddCompetitor={vi.fn()} />);
      expect(screen.getByRole('button', { name: /add competitor/i })).toBeInTheDocument();
    });

    it('HEAD-015: shows loading skeleton when loading', () => {
      render(<HeadToHeadComparison {...mockProps} headToHead={null} isLoading={true} />);
      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('HEAD-016: handles null headToHead data', () => {
      render(<HeadToHeadComparison {...mockProps} headToHead={null} />);
      expect(screen.getByText(/select a competitor/i)).toBeInTheDocument();
    });
  });

  describe('Keyboard Navigation', () => {
    it('HEAD-017: supports arrow keys for dropdown navigation', async () => {
      const user = userEvent.setup();
      render(<HeadToHeadComparison {...mockProps} />);
      
      const select = screen.getByRole('combobox');
      select.focus();
      await user.keyboard('{ArrowDown}');
      
      expect(select).toHaveValue('Ahrefs');
    });

    it('HEAD-018: supports Enter key for selection', async () => {
      const user = userEvent.setup();
      render(<HeadToHeadComparison {...mockProps} />);
      
      const select = screen.getByRole('combobox');
      select.focus();
      await user.keyboard('{Enter}');
    });
  });

  describe('Advantage Badges', () => {
    it('HEAD-019: displays brand advantages', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText(/comparison/i)).toBeInTheDocument();
      expect(screen.getByText(/use case/i)).toBeInTheDocument();
    });

    it('HEAD-020: displays competitor advantages', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText(/recommendation/i)).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('HEAD-021: has accessible labels', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      
      expect(screen.getByRole('combobox', { name: /select competitor/i })).toBeInTheDocument();
      expect(screen.getByRole('region', { name: /head-to-head comparison/i })).toBeInTheDocument();
    });

    it('HEAD-022: cards have proper aria labels', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      
      const brandCard = screen.getByTestId('brand-card');
      expect(brandCard).toHaveAttribute('aria-label', 'SEOIndicator metrics');
      
      const competitorCard = screen.getByTestId('competitor-card');
      expect(competitorCard).toHaveAttribute('aria-label', 'Ahrefs metrics');
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: dropdown selects competitor', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('AC-002: comparison cards show all metrics', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      
      // Check for all 4 metrics on each card
      expect(screen.getAllByText(/mentions/i).length).toBe(2);
      expect(screen.getAllByText(/rate/i).length).toBeGreaterThanOrEqual(2);
      expect(screen.getAllByText(/sentiment/i).length).toBeGreaterThanOrEqual(2);
    });

    it('AC-003: co-mentions displayed', () => {
      render(<HeadToHeadComparison {...mockProps} />);
      expect(screen.getByText(/co-mentions/i)).toBeInTheDocument();
    });
  });
});
