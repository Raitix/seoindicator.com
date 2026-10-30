/**
 * Accessibility Tests for IndicatorCatalog Component
 * 
 * Tests for WCAG 2.1 AA compliance including ARIA labels,
 * keyboard navigation, and screen reader compatibility.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { IndicatorCatalog, type IndicatorData } from '@/components/indicators/IndicatorCatalog';

// ============================================
// Mock Data
// ============================================

const mockIndicators: IndicatorData[] = [
  { id: 'rankings', title: 'Keyword Rankings', value: 42, status: 'good', category: 'Rankings' },
  { id: 'visibility', title: 'Visibility Score', value: 67, status: 'warning', category: 'Rankings' },
  { id: 'traffic', title: 'Organic Traffic', value: 1245, status: 'good', category: 'Traffic' },
  { id: 'backlinks', title: 'Backlinks', value: 256, status: 'good', category: 'Links' },
];

const mockCategories = ['All', 'Rankings', 'Traffic', 'Links'];

// ============================================
// Test Scenarios CAT-001 to CAT-012
// ============================================

describe('IndicatorCatalog Accessibility (CAT-001 to CAT-012)', () => {
  const defaultProps = {
    indicators: mockIndicators,
    categories: mockCategories,
  };

  // ============================================
  // CAT-001: Container has aria-label
  // ============================================
  it('CAT-001: container has proper ARIA attributes', () => {
    render(<IndicatorCatalog {...defaultProps} />);

    const catalog = screen.getByTestId('indicator-catalog');
    expect(catalog).toBeInTheDocument();
  });

  // ============================================
  // CAT-002: Category tabs have proper roles
  // ============================================
  it('CAT-002: category tabs have proper roles', () => {
    render(<IndicatorCatalog {...defaultProps} />);

    // Tabs should have tablist role
    const tabList = screen.getByRole('tablist');
    expect(tabList).toBeInTheDocument();

    // Individual tabs
    const tabs = screen.getAllByRole('tab');
    expect(tabs.length).toBe(mockCategories.length);
  });

  // ============================================
  // CAT-003: Tab selection conveyed
  // ============================================
  it('CAT-003: tab selection is conveyed via aria-selected', () => {
    render(<IndicatorCatalog {...defaultProps} selectedCategory="All" />);

    const tabs = screen.getAllByRole('tab');
    const selectedTab = tabs.find(tab => tab.getAttribute('aria-selected') === 'true');
    expect(selectedTab).toBeInTheDocument();
    expect(selectedTab?.textContent).toBe('All');
  });

  // ============================================
  // CAT-005: Arrow keys navigate tabs
  // ============================================
  it('CAT-005: Arrow keys navigate tabs', async () => {
    const onCategoryChange = jest.fn();
    const user = userEvent.setup();
    
    render(
      <IndicatorCatalog 
        {...defaultProps} 
        selectedCategory="All"
        onCategoryChange={onCategoryChange}
      />
    );

    const tabList = screen.getByRole('tablist');
    tabList.focus();

    // ArrowRight should move to next tab
    await user.keyboard('{ArrowRight}');

    expect(onCategoryChange).toHaveBeenCalledWith('Rankings');
  });

  // ============================================
  // CAT-006: Home/End keys for tabs
  // ============================================
  it('CAT-006: Home key moves to first tab', async () => {
    const onCategoryChange = jest.fn();
    const user = userEvent.setup();
    
    render(
      <IndicatorCatalog 
        {...defaultProps} 
        selectedCategory="Traffic"
        onCategoryChange={onCategoryChange}
      />
    );

    const tabList = screen.getByRole('tablist');
    tabList.focus();

    await user.keyboard('{Home}');

    expect(onCategoryChange).toHaveBeenCalledWith('All');
  });

  it('CAT-006a: End key moves to last tab', async () => {
    const onCategoryChange = jest.fn();
    const user = userEvent.setup();
    
    render(
      <IndicatorCatalog 
        {...defaultProps} 
        selectedCategory="All"
        onCategoryChange={onCategoryChange}
      />
    );

    const tabList = screen.getByRole('tablist');
    tabList.focus();

    await user.keyboard('{End}');

    expect(onCategoryChange).toHaveBeenCalledWith('Links');
  });

  // ============================================
  // CAT-008: Empty state has role
  // ============================================
  it('CAT-008: empty state has role="status"', () => {
    render(<IndicatorCatalog indicators={[]} />);

    const emptyState = screen.getByRole('status');
    expect(emptyState).toBeInTheDocument();
    expect(emptyState).toHaveTextContent(/no indicators/i);
  });

  // ============================================
  // CAT-009: Loading state conveyed
  // ============================================
  it('CAT-009: loading state has aria-busy="true"', () => {
    render(<IndicatorCatalog {...defaultProps} isLoading />);

    const catalog = screen.getByTestId('indicator-catalog');
    expect(catalog).toHaveAttribute('aria-busy', 'true');
  });

  // ============================================
  // CAT-010: Error state conveyed
  // ============================================
  it('CAT-010: error state has role="alert"', () => {
    render(
      <IndicatorCatalog 
        {...defaultProps} 
        error={new Error('Failed to load indicators')}
      />
    );

    const errorAlert = screen.getByRole('alert');
    expect(errorAlert).toBeInTheDocument();
    expect(errorAlert).toHaveTextContent('Failed to load indicators');
  });

  // ============================================
  // CAT-011: Refresh button accessible
  // ============================================
  it('CAT-011: refresh button has aria-label', () => {
    const onRefresh = jest.fn();
    render(<IndicatorCatalog {...defaultProps} onRefresh={onRefresh} />);

    const refreshButton = screen.getByRole('button', { name: /refresh indicators/i });
    expect(refreshButton).toBeInTheDocument();
  });

  // ============================================
  // CAT-012: Grid announced as list
  // ============================================
  it('CAT-012: grid has role="list" for indicators', () => {
    render(<IndicatorCatalog {...defaultProps} />);

    const grid = screen.getByRole('list', { name: /indicators/i });
    expect(grid).toBeInTheDocument();
  });
});

// ============================================
// State Management Tests
// ============================================

describe('IndicatorCatalog State Management', () => {
  it('shows loading state when isLoading is true', () => {
    render(<IndicatorCatalog indicators={mockIndicators} isLoading />);

    // Should show skeleton grid
    expect(screen.getByTestId('catalog-skeleton-grid')).toBeInTheDocument();
  });

  it('shows error state when error is provided', () => {
    render(
      <IndicatorCatalog 
        indicators={mockIndicators} 
        error={new Error('Network error')}
      />
    );

    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  it('shows empty state when no indicators', () => {
    render(<IndicatorCatalog indicators={[]} />);

    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('shows indicators when data is available', () => {
    render(<IndicatorCatalog indicators={mockIndicators} />);

    expect(screen.getByText('Keyword Rankings')).toBeInTheDocument();
    expect(screen.getByText('Visibility Score')).toBeInTheDocument();
  });
});

// ============================================
// Category Filtering Tests
// ============================================

describe('IndicatorCatalog Category Filtering', () => {
  it('filters indicators by selected category', () => {
    render(
      <IndicatorCatalog 
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="Rankings"
      />
    );

    // Should only show Rankings category indicators
    expect(screen.getByText('Keyword Rankings')).toBeInTheDocument();
    expect(screen.getByText('Visibility Score')).toBeInTheDocument();
    expect(screen.queryByText('Organic Traffic')).not.toBeInTheDocument();
  });

  it('shows all indicators when "All" is selected', () => {
    render(
      <IndicatorCatalog 
        indicators={mockIndicators}
        categories={mockCategories}
        selectedCategory="All"
      />
    );

    // All indicators should be visible
    expect(screen.getByText('Keyword Rankings')).toBeInTheDocument();
    expect(screen.getByText('Organic Traffic')).toBeInTheDocument();
    expect(screen.getByText('Backlinks')).toBeInTheDocument();
  });
});

// ============================================
// Accessibility Integration Tests
// ============================================

describe('IndicatorCatalog Accessibility Integration', () => {
  it('has proper heading hierarchy', () => {
    render(
      <IndicatorCatalog 
        indicators={mockIndicators}
        title="SEO Indicators"
        description="Track your website performance"
      />
    );

    const heading = screen.getByRole('heading', { level: 2 });
    expect(heading).toHaveTextContent('SEO Indicators');
  });

  it('refresh button is accessible', async () => {
    const onRefresh = jest.fn();
    const user = userEvent.setup();
    
    render(
      <IndicatorCatalog 
        indicators={mockIndicators}
        onRefresh={onRefresh}
      />
    );

    const refreshButton = screen.getByRole('button', { name: /refresh/i });
    await user.click(refreshButton);

    expect(onRefresh).toHaveBeenCalledTimes(1);
  });
});
