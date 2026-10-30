/**
 * Tests for ComparisonRankingTable Component
 * Task: TASK-34-17
 * Feature: FEAT-034 - AI Visibility Tracking
 */

import React from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ComparisonRankingTable from '@/components/ai-visibility/ComparisonRankingTable';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  ChevronUp: () => <span data-testid="chevron-up-icon">▲</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
}));

const mockRankings = [
  { position: 1, name: 'Ahrefs', rate: 38.5, trend: 'up' },
  { position: 2, name: 'SEMrush', rate: 30.8, trend: 'stable' },
  { position: 3, name: 'SEOIndicator', rate: 23.5, trend: 'up', isBrand: true },
  { position: 4, name: 'Moz', rate: 15.2, trend: 'down' },
  { position: 5, name: 'Ubersuggest', rate: 8.3, trend: 'stable' },
];

const mockProps = {
  rankings: mockRankings,
  brandName: 'SEOIndicator',
  brandPosition: 3,
  onRowClick: vi.fn(),
};

describe('ComparisonRankingTable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('RANK-001: renders table with data', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      expect(screen.getByRole('table')).toBeInTheDocument();
    });

    it('RANK-002: highlights brand row', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const brandRow = screen.getByRole('row', { name: /seoindicator/i });
      expect(brandRow).toHaveClass('bg-blue-50');
    });

    it('RANK-003: displays trend up indicator', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const upIcons = screen.getAllByTestId('trending-up-icon');
      expect(upIcons.length).toBeGreaterThan(0);
    });

    it('RANK-004: displays trend down indicator', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const downIcons = screen.getAllByTestId('trending-down-icon');
      expect(downIcons.length).toBeGreaterThan(0);
    });

    it('RANK-005: displays trend stable indicator', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const stableIcons = screen.getAllByTestId('minus-icon');
      expect(stableIcons.length).toBeGreaterThan(0);
    });
  });

  describe('Table Content', () => {
    it('RANK-006: displays position column', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const table = screen.getByRole('table');
      const positions = within(table).getAllByRole('cell', { name: /^\d+$/ });
      expect(positions).toHaveLength(5);
    });

    it('RANK-007: displays name column', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      expect(screen.getByRole('cell', { name: 'Ahrefs' })).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: 'SEMrush' })).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: 'SEOIndicator' })).toBeInTheDocument();
    });

    it('RANK-008: displays mention rate column with percentage', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      expect(screen.getByText('38.5%')).toBeInTheDocument();
      expect(screen.getByText('30.8%')).toBeInTheDocument();
      expect(screen.getByText('23.5%')).toBeInTheDocument();
    });

    it('RANK-009: displays trend column with icons', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const table = screen.getByRole('table');
      expect(within(table).getAllByTestId(/icon/).length).toBeGreaterThan(0);
    });

    it('RANK-010: displays empty state when no data', () => {
      render(<ComparisonRankingTable {...mockProps} rankings={[]} />);
      expect(screen.getByText(/no ranking data/i)).toBeInTheDocument();
    });
  });

  describe('Sorting', () => {
    it('RANK-011: sorts by position on header click', async () => {
      const user = userEvent.setup();
      render(<ComparisonRankingTable {...mockProps} />);
      
      const positionHeader = screen.getByRole('columnheader', { name: /position/i });
      await user.click(positionHeader);
      
      const rows = screen.getAllByRole('row').slice(1); // Skip header
      expect(rows[0]).toHaveTextContent('1');
    });

    it('RANK-012: sorts by rate on header click', async () => {
      const user = userEvent.setup();
      render(<ComparisonRankingTable {...mockProps} />);
      
      const rateHeader = screen.getByRole('columnheader', { name: /rate/i });
      await user.click(rateHeader);
      
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows[0]).toHaveTextContent('38.5%'); // Highest rate
    });
  });

  describe('Brand Position', () => {
    it('RANK-013: displays brand position badge', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      expect(screen.getByText(/your position/i)).toBeInTheDocument();
      expect(screen.getByText('#3')).toBeInTheDocument();
    });
  });

  describe('Interactions', () => {
    it('RANK-014: triggers onRowClick callback', async () => {
      const user = userEvent.setup();
      const onRowClick = vi.fn();
      render(<ComparisonRankingTable {...mockProps} onRowClick={onRowClick} />);
      
      const row = screen.getByRole('row', { name: /ahrefs/i });
      await user.click(row);
      
      expect(onRowClick).toHaveBeenCalledWith(mockRankings[0]);
    });
  });

  describe('Accessibility', () => {
    it('RANK-015: has proper table structure', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      const table = screen.getByRole('table');
      
      expect(within(table).getAllByRole('columnheader').length).toBe(4);
      expect(within(table).getAllByRole('row').length).toBe(6); // 5 data + 1 header
    });

    it('RANK-016: has accessible column headers', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      
      expect(screen.getByRole('columnheader', { name: /position/i })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: /name/i })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: /rate/i })).toBeInTheDocument();
      expect(screen.getByRole('columnheader', { name: /trend/i })).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('RANK-017: handles long competitor names with truncation', () => {
      const longNameRankings = [
        { position: 1, name: 'A'.repeat(100), rate: 38.5, trend: 'up' },
      ];
      render(<ComparisonRankingTable {...mockProps} rankings={longNameRankings} />);
      
      const cell = screen.getByRole('cell', { name: /a{50}/i });
      expect(cell).toHaveClass('truncate');
    });

    it('RANK-018: handles zero rates', () => {
      const zeroRateRankings = [
        { position: 1, name: 'Test', rate: 0, trend: 'stable' },
      ];
      render(<ComparisonRankingTable {...mockProps} rankings={zeroRateRankings} />);
      
      expect(screen.getByText('0%')).toBeInTheDocument();
    });

    it('RANK-019: handles single row', () => {
      const singleRow = [mockRankings[0]];
      render(<ComparisonRankingTable {...mockProps} rankings={singleRow} />);
      
      expect(screen.getAllByRole('row')).toHaveLength(2); // 1 data + 1 header
    });

    it('RANK-020: handles undefined trend gracefully', () => {
      const undefinedTrendRankings = [
        { position: 1, name: 'Test', rate: 10, trend: undefined },
      ];
      render(<ComparisonRankingTable {...mockProps} rankings={undefinedTrendRankings as any} />);
      
      expect(screen.getByRole('row', { name: /test/i })).toBeInTheDocument();
    });
  });

  describe('Acceptance Criteria', () => {
    it('AC-001: ranking table shows all competitors', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      
      mockRankings.forEach(ranking => {
        expect(screen.getByText(ranking.name)).toBeInTheDocument();
      });
    });

    it('AC-002: brand position highlighted', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      
      const brandRow = screen.getByRole('row', { name: /seoindicator/i });
      expect(brandRow).toHaveAttribute('data-is-brand', 'true');
    });

    it('AC-003: trend indicators for each entry', () => {
      render(<ComparisonRankingTable {...mockProps} />);
      
      const rows = screen.getAllByRole('row').slice(1);
      rows.forEach(row => {
        expect(within(row).getByTestId(/icon/)).toBeInTheDocument();
      });
    });
  });
});
