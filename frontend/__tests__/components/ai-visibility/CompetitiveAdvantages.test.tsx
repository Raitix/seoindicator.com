/**
 * Tests for CompetitiveAdvantages Component
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CompetitiveAdvantages from '@/components/ai-visibility/CompetitiveAdvantages';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  ThumbsUp: () => <span data-testid="thumbs-up-icon">👍</span>,
  AlertTriangle: () => <span data-testid="alert-icon">⚠</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">▲</span>,
  Filter: () => <span data-testid="filter-icon">🔍</span>,
}));

const mockAdvantages = {
  brand_wins: [
    { category: 'comparison', query_count: 12, examples: ['How does SEOIndicator compare to Ahrefs?', 'SEOIndicator vs SEMrush'] },
    { category: 'use_case', query_count: 8, examples: ['Best SEO tool for beginners'] },
  ],
  competitor_wins: [
    { category: 'recommendation', query_count: 15, examples: ['What SEO tool do you recommend?'] },
    { category: 'feature', query_count: 5, examples: ['Best backlink analysis tool'] },
  ],
};

const mockProps = {
  brandName: 'SEOIndicator',
  competitorName: 'Ahrefs',
  advantages: mockAdvantages,
};

describe('CompetitiveAdvantages', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('ADV-001: renders section with data', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByTestId('advantages-section')).toBeInTheDocument();
    });

    it('ADV-002: displays brand wins section', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByText(/seoindicator wins/i)).toBeInTheDocument();
    });

    it('ADV-003: displays competitor wins section', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByText(/ahrefs wins/i)).toBeInTheDocument();
    });

    it('ADV-004: displays category filter', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByRole('button', { name: /all/i })).toBeInTheDocument();
    });
  });

  describe('Category Display', () => {
    it('ADV-005: shows all categories', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByText(/comparison/i)).toBeInTheDocument();
      expect(screen.getByText(/use case/i)).toBeInTheDocument();
      expect(screen.getByText(/recommendation/i)).toBeInTheDocument();
      expect(screen.getByText(/feature/i)).toBeInTheDocument();
    });

    it('ADV-006: displays query count badge', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByText('12')).toBeInTheDocument();
      expect(screen.getByText('15')).toBeInTheDocument();
    });

    it('ADV-007: shows thumbs up icon for brand wins', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      const brandSection = screen.getByTestId('brand-wins');
      expect(within(brandSection).getAllByTestId('thumbs-up-icon').length).toBeGreaterThan(0);
    });

    it('ADV-008: shows alert icon for competitor wins', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      const competitorSection = screen.getByTestId('competitor-wins');
      expect(within(competitorSection).getAllByTestId('alert-icon').length).toBeGreaterThan(0);
    });
  });

  describe('Expandable Details', () => {
    it('ADV-009: expands on category click', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const category = screen.getByText(/comparison/i).closest('button');
      await user.click(category!);
      
      expect(screen.getByTestId('comparison-details')).toBeInTheDocument();
    });

    it('ADV-010: shows query examples when expanded', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const category = screen.getByText(/comparison/i).closest('button');
      await user.click(category!);
      
      expect(screen.getByText(/how does seoindicator compare/i)).toBeInTheDocument();
    });

    it('ADV-011: collapses on second click', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const category = screen.getByText(/comparison/i).closest('button');
      await user.click(category!);
      await user.click(category!);
      
      expect(screen.queryByTestId('comparison-details')).not.toBeInTheDocument();
    });
  });

  describe('Category Filtering', () => {
    it('ADV-012: filters by category', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const filterBtn = screen.getByRole('button', { name: /comparison/i });
      await user.click(filterBtn);
      
      expect(screen.getAllByText(/comparison/i).length).toBe(1);
    });

    it('ADV-013: shows all categories on "All" click', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      // First filter to one category
      await user.click(screen.getByRole('button', { name: /comparison/i }));
      
      // Then click All
      await user.click(screen.getByRole('button', { name: /all/i }));
      
      expect(screen.getAllByTestId(/icon/).length).toBeGreaterThan(2);
    });
  });

  describe('Edge Cases', () => {
    it('ADV-014: handles empty brand advantages', () => {
      const emptyBrand = { brand_wins: [], competitor_wins: mockAdvantages.competitor_wins };
      render(<CompetitiveAdvantages {...mockProps} advantages={emptyBrand} />);
      
      expect(screen.getByText(/no advantages/i)).toBeInTheDocument();
    });

    it('ADV-015: handles empty competitor advantages', () => {
      const emptyComp = { brand_wins: mockAdvantages.brand_wins, competitor_wins: [] };
      render(<CompetitiveAdvantages {...mockProps} advantages={emptyComp} />);
      
      expect(screen.getByText(/no competitive threats/i)).toBeInTheDocument();
    });

    it('ADV-016: handles both empty', () => {
      const emptyBoth = { brand_wins: [], competitor_wins: [] };
      render(<CompetitiveAdvantages {...mockProps} advantages={emptyBoth} />);
      
      expect(screen.getByText(/no data/i)).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('ADV-017: has accessible category buttons', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const buttons = screen.getAllByRole('button');
      buttons.forEach(btn => {
        expect(btn).toHaveAttribute('aria-expanded');
      });
    });

    it('ADV-018: expanded state announced', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const category = screen.getByText(/comparison/i).closest('button');
      await user.click(category!);
      
      expect(category).toHaveAttribute('aria-expanded', 'true');
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: shows where brand outperforms', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByTestId('brand-wins')).toBeInTheDocument();
    });

    it('AC-002: shows where competitor outperforms', () => {
      render(<CompetitiveAdvantages {...mockProps} />);
      expect(screen.getByTestId('competitor-wins')).toBeInTheDocument();
    });

    it('AC-003: expandable details with queries', async () => {
      const user = userEvent.setup();
      render(<CompetitiveAdvantages {...mockProps} />);
      
      const category = screen.getByText(/comparison/i).closest('button');
      await user.click(category!);
      
      expect(screen.getByText(/how does seoindicator/i)).toBeInTheDocument();
    });
  });
});
