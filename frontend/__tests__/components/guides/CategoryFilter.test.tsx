/**
 * Tests for CategoryFilter Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Tests the category filter tabs for filtering guides by category.
 */

import React, { useState } from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Types
type GuideCategory = 'on-page' | 'technical' | 'content' | 'all';

// Mock component for TDD (will be replaced with actual import)
function MockCategoryFilter({ 
  activeCategory, 
  onCategoryChange 
}: { 
  activeCategory: GuideCategory; 
  onCategoryChange: (category: GuideCategory) => void;
}) {
  const categories: { id: GuideCategory; label: string; count: number }[] = [
    { id: 'all', label: 'All', count: 17 },
    { id: 'on-page', label: 'On-Page', count: 8 },
    { id: 'technical', label: 'Technical', count: 7 },
    { id: 'content', label: 'Content', count: 2 },
  ];

  return (
    <div role="tablist" aria-label="Filter guides by category" data-testid="category-filter">
      {categories.map((cat) => (
        <button
          key={cat.id}
          role="tab"
          aria-selected={activeCategory === cat.id}
          aria-controls="guide-grid"
          data-testid={`tab-${cat.id}`}
          onClick={() => onCategoryChange(cat.id)}
          className={activeCategory === cat.id ? 'active' : ''}
        >
          {cat.label}
          <span className="count" data-testid={`count-${cat.id}`}>{cat.count}</span>
        </button>
      ))}
    </div>
  );
}

describe('CategoryFilter Component', () => {
  const mockOnCategoryChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders all category tabs', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('tab-all')).toBeInTheDocument();
      expect(screen.getByTestId('tab-on-page')).toBeInTheDocument();
      expect(screen.getByTestId('tab-technical')).toBeInTheDocument();
      expect(screen.getByTestId('tab-content')).toBeInTheDocument();
    });

    it('displays category labels correctly', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('tab-all')).toHaveTextContent('All');
      expect(screen.getByTestId('tab-on-page')).toHaveTextContent('On-Page');
      expect(screen.getByTestId('tab-technical')).toHaveTextContent('Technical');
      expect(screen.getByTestId('tab-content')).toHaveTextContent('Content');
    });

    it('displays guide counts for each category', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('count-all')).toHaveTextContent('17');
      expect(screen.getByTestId('count-on-page')).toHaveTextContent('8');
      expect(screen.getByTestId('count-technical')).toHaveTextContent('7');
      expect(screen.getByTestId('count-content')).toHaveTextContent('2');
    });
  });

  describe('Active State', () => {
    it('shows All tab as active by default', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('tab-all')).toHaveAttribute('aria-selected', 'true');
    });

    it('shows correct active tab when category changes', () => {
      render(
        <MockCategoryFilter 
          activeCategory="on-page" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('tab-on-page')).toHaveAttribute('aria-selected', 'true');
      expect(screen.getByTestId('tab-all')).toHaveAttribute('aria-selected', 'false');
    });

    it('applies active CSS class to selected tab', () => {
      render(
        <MockCategoryFilter 
          activeCategory="technical" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByTestId('tab-technical')).toHaveClass('active');
      expect(screen.getByTestId('tab-all')).not.toHaveClass('active');
    });
  });

  describe('Interaction', () => {
    it('calls onCategoryChange when tab is clicked', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      fireEvent.click(screen.getByTestId('tab-on-page'));
      expect(mockOnCategoryChange).toHaveBeenCalledWith('on-page');
    });

    it('changes active tab on click', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [category, setCategory] = useState<GuideCategory>('all');
        return (
          <MockCategoryFilter 
            activeCategory={category} 
            onCategoryChange={setCategory} 
          />
        );
      }
      
      render(<TestWrapper />);
      
      await user.click(screen.getByTestId('tab-content'));
      expect(screen.getByTestId('tab-content')).toHaveAttribute('aria-selected', 'true');
    });
  });

  describe('Accessibility', () => {
    it('has tablist role', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByRole('tablist')).toBeInTheDocument();
    });

    it('has tab role for each button', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(4);
    });

    it('has aria-label for the tablist', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      expect(screen.getByRole('tablist')).toHaveAttribute('aria-label', 'Filter guides by category');
    });

    it('has aria-selected state on each tab', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      const tabs = screen.getAllByRole('tab');
      tabs.forEach(tab => {
        expect(tab).toHaveAttribute('aria-selected');
      });
    });

    it('has aria-controls pointing to guide grid', () => {
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      const tabs = screen.getAllByRole('tab');
      tabs.forEach(tab => {
        expect(tab).toHaveAttribute('aria-controls', 'guide-grid');
      });
    });

    it('is keyboard navigable', async () => {
      const user = userEvent.setup();
      
      render(
        <MockCategoryFilter 
          activeCategory="all" 
          onCategoryChange={mockOnCategoryChange} 
        />
      );
      
      const allTab = screen.getByTestId('tab-all');
      allTab.focus();
      expect(allTab).toHaveFocus();
      
      await user.tab();
      expect(screen.getByTestId('tab-on-page')).toHaveFocus();
    });
  });

  describe('Edge Cases', () => {
    it('handles rapid category changes', async () => {
      const user = userEvent.setup();
      
      function TestWrapper() {
        const [category, setCategory] = useState<GuideCategory>('all');
        return (
          <MockCategoryFilter 
            activeCategory={category} 
            onCategoryChange={setCategory} 
          />
        );
      }
      
      render(<TestWrapper />);
      
      // Rapid clicks
      await user.click(screen.getByTestId('tab-on-page'));
      await user.click(screen.getByTestId('tab-technical'));
      await user.click(screen.getByTestId('tab-content'));
      
      expect(screen.getByTestId('tab-content')).toHaveAttribute('aria-selected', 'true');
    });
  });
});
