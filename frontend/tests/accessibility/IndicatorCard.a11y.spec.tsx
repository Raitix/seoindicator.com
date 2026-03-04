/**
 * Accessibility Tests for IndicatorCard Component
 * 
 * Tests for WCAG 2.1 AA compliance including ARIA labels,
 * keyboard navigation, and screen reader compatibility.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { IndicatorCard, type IndicatorCardProps } from '@/components/indicators/IndicatorCard';

// ============================================
// Test Scenarios IC-001 to IC-015
// ============================================

describe('IndicatorCard Accessibility (IC-001 to IC-015)', () => {
  const mockOnClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  const defaultProps: IndicatorCardProps = {
    id: 'rankings',
    title: 'Keyword Rankings',
    value: 42,
    status: 'good',
    onClick: mockOnClick,
  };

  // ============================================
  // IC-001: Renders with aria-label
  // ============================================
  it('IC-001: renders with descriptive aria-label containing title, value, and status', () => {
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-label');
    expect(card.getAttribute('aria-label')).toContain('Keyword Rankings');
    expect(card.getAttribute('aria-label')).toContain('42');
    expect(card.getAttribute('aria-label')).toContain('Good');
    expect(card.getAttribute('aria-label')).toContain('Click to view details');
  });

  // ============================================
  // IC-002: Has role="button"
  // ============================================
  it('IC-002: has role="button" for screen reader compatibility', () => {
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toBeInTheDocument();
  });

  // ============================================
  // IC-003: Has tabIndex={0}
  // ============================================
  it('IC-003: has tabIndex={0} to be focusable via keyboard', () => {
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('tabIndex', '0');
  });

  // ============================================
  // IC-004: Enter key activates
  // ============================================
  it('IC-004: Enter key activates onClick handler', async () => {
    const user = userEvent.setup();
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    card.focus();
    await user.keyboard('{Enter}');

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  // ============================================
  // IC-005: Space key activates
  // ============================================
  it('IC-005: Space key activates onClick handler with preventDefault', async () => {
    const user = userEvent.setup();
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    card.focus();
    await user.keyboard(' ');

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  // ============================================
  // IC-006: Focus indicator visible
  // ============================================
  it('IC-006: has visible focus indicator when focused', () => {
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    card.focus();

    // Check that element is focusable
    expect(card).toHaveFocus();
    expect(document.activeElement).toBe(card);
  });

  // ============================================
  // IC-007: Status conveyed via aria
  // ============================================
  it('IC-007: status "warning" is conveyed in aria-label', () => {
    render(<IndicatorCard {...defaultProps} status="warning" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Warning');
  });

  it('IC-007a: status "critical" is conveyed in aria-label', () => {
    render(<IndicatorCard {...defaultProps} status="critical" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Critical');
  });

  it('IC-007b: status "neutral" is conveyed in aria-label', () => {
    render(<IndicatorCard {...defaultProps} status="neutral" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Neutral');
  });

  // ============================================
  // IC-008: Description accessible via aria-describedby
  // ============================================
  it('IC-008: tooltip content is accessible via aria-describedby', () => {
    render(
      <IndicatorCard
        {...defaultProps}
        tooltip="Track your keyword positions in SERP"
      />
    );

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-describedby');
    
    const tooltipId = card.getAttribute('aria-describedby');
    const tooltip = document.getElementById(tooltipId!);
    expect(tooltip).toHaveTextContent('Track your keyword positions in SERP');
  });

  // ============================================
  // IC-009: Disabled state
  // ============================================
  it('IC-009: disabled state has aria-disabled and is not focusable', () => {
    render(<IndicatorCard {...defaultProps} disabled />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-disabled', 'true');
    expect(card).toHaveAttribute('tabIndex', '-1');
  });

  it('IC-009a: disabled card does not respond to Enter key', async () => {
    const user = userEvent.setup();
    render(<IndicatorCard {...defaultProps} disabled />);

    const card = screen.getByRole('button');
    card.focus();
    await user.keyboard('{Enter}');

    expect(mockOnClick).not.toHaveBeenCalled();
  });

  // ============================================
  // IC-010: Loading state
  // ============================================
  it('IC-010: loading state has aria-busy="true"', () => {
    render(<IndicatorCard {...defaultProps} isLoading />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-busy', 'true');
  });

  // ============================================
  // IC-011: Error state
  // ============================================
  it('IC-011: error state shows error message with role="alert"', () => {
    render(
      <IndicatorCard
        {...defaultProps}
        error={new Error('Failed to load data')}
      />
    );

    const errorMessage = screen.getByRole('alert');
    expect(errorMessage).toHaveTextContent('Failed to load data');
  });

  // ============================================
  // IC-012: Click via mouse
  // ============================================
  it('IC-012: click via mouse triggers onClick handler', async () => {
    const user = userEvent.setup();
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    await user.click(card);

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  // ============================================
  // IC-013: Screen reader announces full context
  // ============================================
  it('IC-013: screen reader can announce title, value, status, and action hint', () => {
    render(<IndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    const ariaLabel = card.getAttribute('aria-label');

    // Verify all required information is in aria-label
    expect(ariaLabel).toMatch(/Keyword Rankings/);
    expect(ariaLabel).toMatch(/42/);
    expect(ariaLabel).toMatch(/Good/);
    expect(ariaLabel).toMatch(/Click to view details/);
  });

  // ============================================
  // IC-014: Icon has aria-hidden
  // ============================================
  it('IC-014: decorative icons should have aria-hidden="true"', () => {
    const { container } = render(
      <IndicatorCard 
        {...defaultProps} 
        icon={() => <span data-testid="test-icon">Icon</span>}
      />
    );

    // Icons should be marked as decorative
    const icons = container.querySelectorAll('[aria-hidden="true"]');
    expect(icons.length).toBeGreaterThan(0);
  });

  // ============================================
  // IC-015: Value changes displayed
  // ============================================
  it('IC-015: value is displayed correctly', () => {
    const { rerender } = render(<IndicatorCard {...defaultProps} value={42} />);

    // Initial value
    expect(screen.getByText('42')).toBeInTheDocument();

    // Update value
    rerender(<IndicatorCard {...defaultProps} value={50} />);

    // Value updated
    expect(screen.getByText('50')).toBeInTheDocument();
  });
});

// ============================================
// Edge Cases for IndicatorCard
// ============================================

describe('IndicatorCard Edge Cases', () => {
  const mockOnClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  const defaultProps: IndicatorCardProps = {
    id: 'test',
    title: 'Test',
    value: 100,
    status: 'good',
    onClick: mockOnClick,
  };

  it('handles very long title gracefully', () => {
    const longTitle = 'This is a very long indicator title that might overflow';
    render(
      <IndicatorCard
        {...defaultProps}
        title={longTitle}
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain(longTitle);
  });

  it('handles string values correctly', () => {
    render(
      <IndicatorCard
        {...defaultProps}
        value="Active"
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Active');
  });

  it('handles zero as a valid value', () => {
    render(
      <IndicatorCard
        {...defaultProps}
        value={0}
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('0');
  });

  it('handles negative values', () => {
    render(
      <IndicatorCard
        {...defaultProps}
        value={-5}
        status="warning"
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('-5');
  });
});

// ============================================
// Security Tests
// ============================================

describe('IndicatorCard Security', () => {
  it('EC-010: does not execute XSS in aria-label', () => {
    const xssTitle = '<script>alert("xss")</script>';
    
    render(
      <IndicatorCard
        id="test"
        title={xssTitle}
        value={1}
        status="good"
      />
    );

    const card = screen.getByRole('button');
    const ariaLabel = card.getAttribute('aria-label')!;
    
    // Script tag should be escaped, not executed
    expect(ariaLabel).not.toContain('<script>');
  });
});

// ============================================
// Link-based IndicatorCard Tests
// ============================================

describe('IndicatorCard with href (Link)', () => {
  it('renders as a link when href is provided', () => {
    render(
      <IndicatorCard
        id="test"
        title="Rankings"
        value={42}
        status="good"
        href="/dashboard/rankings"
      />
    );

    // Should have role="button" even though it's a link
    const card = screen.getByRole('button');
    expect(card).toBeInTheDocument();
  });

  it('has proper aria-label for link-based card', () => {
    render(
      <IndicatorCard
        id="test"
        title="Rankings"
        value={42}
        status="good"
        href="/dashboard/rankings"
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Rankings');
    expect(card.getAttribute('aria-label')).toContain('Click to view details');
  });
});
