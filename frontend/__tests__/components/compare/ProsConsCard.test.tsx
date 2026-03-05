/**
 * ProsConsCard Component Tests
 * Task: TASK-32-19 - Unit Tests for Comparison Components
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * TDD Tests - Component implementation pending
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import type { ProsConsCardProps } from '@/lib/compare/types';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon" aria-label="check">✓</span>,
  X: () => <span data-testid="x-icon" aria-label="x">✗</span>,
  Star: () => <span data-testid="star-icon">★</span>,
}));

// Mock component - will be replaced with actual implementation
const ProsConsCard = ({
  tool,
  pros,
  cons,
  overallRating,
}: ProsConsCardProps) => {
  return (
    <div data-testid="pros-cons-card" className="pros-cons-card">
      {/* Header */}
      <h3 data-testid="tool-name" className="tool-name">
        {tool}
      </h3>

      {/* Rating (optional) */}
      {overallRating !== undefined && (
        <div data-testid="rating-container" aria-label={`Rating: ${overallRating} out of 5`}>
          <div data-testid="rating-stars" role="img">
            {Array.from({ length: 5 }, (_, i) => (
              <span
                key={i}
                data-testid={`star-${i}`}
                className={i < Math.floor(overallRating) ? 'filled' : 'empty'}
              >
                ★
              </span>
            ))}
          </div>
          <span data-testid="rating-value">{overallRating.toFixed(1)}</span>
        </div>
      )}

      {/* Pros Section */}
      <div data-testid="pros-section" aria-label="Pros">
        <h4 data-testid="pros-header" className="text-green-500">
          Pros
        </h4>
        <ul data-testid="pros-list" role="list">
          {pros.length > 0 ? (
            pros.map((pro, idx) => (
              <li key={idx} data-testid={`pro-${idx}`} className="pro-item">
                <span data-testid="check-icon" aria-label="check">✓</span>
                <span>{pro}</span>
              </li>
            ))
          ) : (
            <li data-testid="no-pros" className="empty-message">
              No pros listed
            </li>
          )}
        </ul>
      </div>

      {/* Cons Section */}
      <div data-testid="cons-section" aria-label="Cons">
        <h4 data-testid="cons-header" className="text-red-500">
          Cons
        </h4>
        <ul data-testid="cons-list" role="list">
          {cons.length > 0 ? (
            cons.map((con, idx) => (
              <li key={idx} data-testid={`con-${idx}`} className="con-item">
                <span data-testid="x-icon" aria-label="x">✗</span>
                <span>{con}</span>
              </li>
            ))
          ) : (
            <li data-testid="no-cons" className="empty-message">
              No cons listed
            </li>
          )}
        </ul>
      </div>
    </div>
  );
};

// Test data
const mockPros = [
  'Most affordable SEO tool on the market',
  'Simple, intuitive interface',
  'All essential features in one platform',
  'Generous free tier',
  'Excellent customer support',
];

const mockCons = [
  'Smaller database compared to enterprise tools',
  'Limited advanced features for power users',
  'No social media or advertising tools',
];

describe('ProsConsCard', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('PROS-001: renders card structure', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('pros-cons-card')).toBeInTheDocument();
    });

    it('PROS-002: displays tool name in header', () => {
      render(<ProsConsCard tool="Ahrefs" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('tool-name')).toHaveTextContent('Ahrefs');
    });

    it('PROS-003: renders pros section', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('pros-section')).toBeInTheDocument();
      expect(screen.getByTestId('pros-header')).toHaveTextContent('Pros');
    });

    it('PROS-004: renders cons section', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('cons-section')).toBeInTheDocument();
      expect(screen.getByTestId('cons-header')).toHaveTextContent('Cons');
    });

    it('PROS-005: renders both lists', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('pros-list')).toBeInTheDocument();
      expect(screen.getByTestId('cons-list')).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Pros Tests
  // ============================================================================

  describe('Pros', () => {
    it('PROS-010: lists all pros with check icons', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const prosList = screen.getByTestId('pros-list');
      const proItems = within(prosList).getAllByRole('listitem');
      
      expect(proItems).toHaveLength(5);
    });

    it('PROS-011: displays correct text for each pro', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByText('Most affordable SEO tool on the market')).toBeInTheDocument();
      expect(screen.getByText('Simple, intuitive interface')).toBeInTheDocument();
      expect(screen.getByText('All essential features in one platform')).toBeInTheDocument();
    });

    it('PROS-012: displays check icon for each pro', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const prosSection = screen.getByTestId('pros-section');
      const checkIcons = within(prosSection).getAllByTestId('check-icon');
      
      expect(checkIcons).toHaveLength(5);
    });

    it('PROS-013: green styling for pros header', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const prosHeader = screen.getByTestId('pros-header');
      expect(prosHeader).toHaveClass('text-green-500');
    });
  });

  // ============================================================================
  // Cons Tests
  // ============================================================================

  describe('Cons', () => {
    it('PROS-020: lists all cons with X icons', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const consList = screen.getByTestId('cons-list');
      const conItems = within(consList).getAllByRole('listitem');
      
      expect(conItems).toHaveLength(3);
    });

    it('PROS-021: displays correct text for each con', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByText('Smaller database compared to enterprise tools')).toBeInTheDocument();
      expect(screen.getByText('Limited advanced features for power users')).toBeInTheDocument();
      expect(screen.getByText('No social media or advertising tools')).toBeInTheDocument();
    });

    it('PROS-022: displays X icon for each con', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const consSection = screen.getByTestId('cons-section');
      const xIcons = within(consSection).getAllByTestId('x-icon');
      
      expect(xIcons).toHaveLength(3);
    });

    it('PROS-023: red styling for cons header', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const consHeader = screen.getByTestId('cons-header');
      expect(consHeader).toHaveClass('text-red-500');
    });
  });

  // ============================================================================
  // Rating Tests
  // ============================================================================

  describe('Rating', () => {
    it('PROS-030: displays rating when provided', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={4.5} />);
      
      expect(screen.getByTestId('rating-container')).toBeInTheDocument();
    });

    it('PROS-031: hides rating when not provided', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.queryByTestId('rating-container')).not.toBeInTheDocument();
    });

    it('PROS-032: displays correct number of stars', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={4.5} />);
      
      const stars = screen.getAllByTestId(/star-/);
      expect(stars).toHaveLength(5);
    });

    it('PROS-033: fills correct number of stars for whole number rating', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={4} />);
      
      // Stars 0, 1, 2, 3 should be filled
      expect(screen.getByTestId('star-0')).toHaveClass('filled');
      expect(screen.getByTestId('star-1')).toHaveClass('filled');
      expect(screen.getByTestId('star-2')).toHaveClass('filled');
      expect(screen.getByTestId('star-3')).toHaveClass('filled');
      expect(screen.getByTestId('star-4')).toHaveClass('empty');
    });

    it('PROS-034: displays numeric rating value', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={4.5} />);
      
      expect(screen.getByTestId('rating-value')).toHaveTextContent('4.5');
    });

    it('PROS-035: rating has accessible label', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={4.5} />);
      
      const ratingContainer = screen.getByTestId('rating-container');
      expect(ratingContainer).toHaveAttribute('aria-label', 'Rating: 4.5 out of 5');
    });

    it('PROS-036: handles zero rating', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={0} />);
      
      const stars = screen.getAllByTestId(/star-/);
      stars.forEach(star => {
        expect(star).toHaveClass('empty');
      });
    });

    it('PROS-037: handles perfect rating', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} overallRating={5} />);
      
      const stars = screen.getAllByTestId(/star-/);
      stars.forEach(star => {
        expect(star).toHaveClass('filled');
      });
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('PROS-040: pros list has role="list"', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('pros-list')).toHaveAttribute('role', 'list');
    });

    it('PROS-041: cons list has role="list"', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('cons-list')).toHaveAttribute('role', 'list');
    });

    it('PROS-042: icons have accessible labels', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      const checkIcons = screen.getAllByTestId('check-icon');
      const xIcons = screen.getAllByTestId('x-icon');
      
      checkIcons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-label', 'check');
      });
      
      xIcons.forEach(icon => {
        expect(icon).toHaveAttribute('aria-label', 'x');
      });
    });

    it('PROS-043: sections have aria-labels', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('pros-section')).toHaveAttribute('aria-label', 'Pros');
      expect(screen.getByTestId('cons-section')).toHaveAttribute('aria-label', 'Cons');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('PROS-050: handles empty pros array', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={[]} cons={mockCons} />);
      
      expect(screen.getByTestId('no-pros')).toBeInTheDocument();
      expect(screen.getByText('No pros listed')).toBeInTheDocument();
    });

    it('PROS-051: handles empty cons array', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={[]} />);
      
      expect(screen.getByTestId('no-cons')).toBeInTheDocument();
      expect(screen.getByText('No cons listed')).toBeInTheDocument();
    });

    it('PROS-052: handles both arrays empty', () => {
      render(<ProsConsCard tool="SEOIndicator" pros={[]} cons={[]} />);
      
      expect(screen.getByTestId('no-pros')).toBeInTheDocument();
      expect(screen.getByTestId('no-cons')).toBeInTheDocument();
    });

    it('PROS-053: handles long pros text', () => {
      const longPros = [
        'This is a very long pro description that might need to wrap or be truncated in the UI to prevent layout issues',
      ];
      
      render(<ProsConsCard tool="SEOIndicator" pros={longPros} cons={mockCons} />);
      
      expect(screen.getByText('This is a very long pro description that might need to wrap or be truncated in the UI to prevent layout issues')).toBeInTheDocument();
    });

    it('PROS-054: handles many pros', () => {
      const manyPros = Array.from({ length: 20 }, (_, i) => `Pro ${i + 1}`);
      
      render(<ProsConsCard tool="SEOIndicator" pros={manyPros} cons={mockCons} />);
      
      const prosList = screen.getByTestId('pros-list');
      expect(within(prosList).getAllByRole('listitem')).toHaveLength(20);
    });

    it('PROS-055: handles unicode in pros/cons', () => {
      const unicodePros = ['👍 Great for beginners', '日本語サポート'];
      const unicodeCons = ['❌ Limited integrations'];
      
      render(<ProsConsCard tool="SEOIndicator" pros={unicodePros} cons={unicodeCons} />);
      
      expect(screen.getByText('👍 Great for beginners')).toBeInTheDocument();
      expect(screen.getByText('日本語サポート')).toBeInTheDocument();
      expect(screen.getByText('❌ Limited integrations')).toBeInTheDocument();
    });

    it('PROS-056: handles special characters in tool name', () => {
      render(<ProsConsCard tool="SEO & Marketing Tool™" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('tool-name')).toHaveTextContent('SEO & Marketing Tool™');
    });
  });

  // ============================================================================
  // Props Tests
  // ============================================================================

  describe('Props', () => {
    it('PROS-060: uses correct tool name from props', () => {
      render(<ProsConsCard tool="SEMrush" pros={mockPros} cons={mockCons} />);
      
      expect(screen.getByTestId('tool-name')).toHaveTextContent('SEMrush');
    });

    it('PROS-061: renders all pros from props', () => {
      const customPros = ['Custom Pro 1', 'Custom Pro 2'];
      
      render(<ProsConsCard tool="SEOIndicator" pros={customPros} cons={mockCons} />);
      
      expect(screen.getByText('Custom Pro 1')).toBeInTheDocument();
      expect(screen.getByText('Custom Pro 2')).toBeInTheDocument();
    });

    it('PROS-062: renders all cons from props', () => {
      const customCons = ['Custom Con 1', 'Custom Con 2'];
      
      render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={customCons} />);
      
      expect(screen.getByText('Custom Con 1')).toBeInTheDocument();
      expect(screen.getByText('Custom Con 2')).toBeInTheDocument();
    });

    it('PROS-063: rating prop is optional', () => {
      // Should not throw when rating is omitted
      expect(() => {
        render(<ProsConsCard tool="SEOIndicator" pros={mockPros} cons={mockCons} />);
      }).not.toThrow();
    });
  });
});
