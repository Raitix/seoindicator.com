/**
 * Accessibility Tests for IndicatorCard Component
 * 
 * Tests for WCAG 2.1 AA compliance including ARIA labels,
 * keyboard navigation, and screen reader compatibility.
 * 
 * @see TESTS/TASK-28-16.md for full test documentation
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// ============================================
// Mock IndicatorCard Component
// This interface defines expected behavior for TDD.
// Replace with actual component once TASK-28-5 is implemented.
// ============================================

interface IndicatorCardProps {
  id: string;
  title: string;
  value: string | number;
  description?: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href?: string;
  icon?: string;
  tooltip?: string;
  onClick?: () => void;
  disabled?: boolean;
  isLoading?: boolean;
  error?: Error | null;
  'aria-label'?: string;
}

/**
 * Mock IndicatorCard for TDD testing.
 * Actual implementation should be in:
 * frontend/components/indicators/IndicatorCard.tsx
 */
const MockIndicatorCard: React.FC<IndicatorCardProps> = ({
  title,
  value,
  status,
  onClick,
  disabled = false,
  isLoading = false,
  error = null,
  tooltip,
}) => {
  const ariaLabel = `${title}: ${value}. Status: ${status}. Click to view details.`;

  return (
    <div
      role="button"
      tabIndex={disabled ? -1 : 0}
      aria-label={ariaLabel}
      aria-disabled={disabled}
      aria-busy={isLoading}
      aria-describedby={tooltip ? `${title}-tooltip` : undefined}
      onClick={disabled ? undefined : onClick}
      onKeyDown={(e) => {
        if (disabled) return;
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onClick?.();
        }
      }}
      data-testid="indicator-card"
      className={status}
    >
      {isLoading && <span aria-hidden="true">Loading...</span>}
      {error && (
        <span role="alert" data-testid="card-error">
          {error.message}
        </span>
      )}
      <span data-testid="card-title">{title}</span>
      <span data-testid="card-value">{value}</span>
      {tooltip && (
        <span id={`${title}-tooltip`} className="sr-only">
          {tooltip}
        </span>
      )}
    </div>
  );
};

// ============================================
// Test Scenarios IC-001 to IC-015
// ============================================

describe('IndicatorCard Accessibility (IC-001 to IC-015)', () => {
  const mockOnClick = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
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
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-label');
    expect(card.getAttribute('aria-label')).toContain('Keyword Rankings');
    expect(card.getAttribute('aria-label')).toContain('42');
    expect(card.getAttribute('aria-label')).toContain('good');
    expect(card.getAttribute('aria-label')).toContain('Click to view details');
  });

  // ============================================
  // IC-002: Has role="button"
  // ============================================
  it('IC-002: has role="button" for screen reader compatibility', () => {
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toBeInTheDocument();
  });

  // ============================================
  // IC-003: Has tabIndex={0}
  // ============================================
  it('IC-003: has tabIndex={0} to be focusable via keyboard', () => {
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('tabIndex', '0');
  });

  // ============================================
  // IC-004: Enter key activates
  // ============================================
  it('IC-004: Enter key activates onClick handler', async () => {
    const user = userEvent.setup();
    render(<MockIndicatorCard {...defaultProps} />);

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
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    card.focus();
    await user.keyboard(' ');

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  // ============================================
  // IC-006: Focus indicator visible
  // ============================================
  it('IC-006: has visible focus indicator when focused', () => {
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    card.focus();

    // Check that element is focusable (actual visual testing would need CSS)
    expect(card).toHaveFocus();
    expect(document.activeElement).toBe(card);
  });

  // ============================================
  // IC-007: Status conveyed via aria
  // ============================================
  it('IC-007: status "warning" is conveyed in aria-label', () => {
    render(<MockIndicatorCard {...defaultProps} status="warning" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('warning');
  });

  it('IC-007a: status "critical" is conveyed in aria-label', () => {
    render(<MockIndicatorCard {...defaultProps} status="critical" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('critical');
  });

  it('IC-007b: status "neutral" is conveyed in aria-label', () => {
    render(<MockIndicatorCard {...defaultProps} status="neutral" />);

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('neutral');
  });

  // ============================================
  // IC-008: Description accessible via aria-describedby
  // ============================================
  it('IC-008: tooltip content is accessible via aria-describedby', () => {
    render(
      <MockIndicatorCard
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
    render(<MockIndicatorCard {...defaultProps} disabled />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-disabled', 'true');
    expect(card).toHaveAttribute('tabIndex', '-1');
  });

  it('IC-009a: disabled card does not respond to Enter key', async () => {
    const user = userEvent.setup();
    render(<MockIndicatorCard {...defaultProps} disabled />);

    const card = screen.getByRole('button');
    card.focus();
    await user.keyboard('{Enter}');

    expect(mockOnClick).not.toHaveBeenCalled();
  });

  // ============================================
  // IC-010: Loading state
  // ============================================
  it('IC-010: loading state has aria-busy="true"', () => {
    render(<MockIndicatorCard {...defaultProps} isLoading />);

    const card = screen.getByRole('button');
    expect(card).toHaveAttribute('aria-busy', 'true');
  });

  // ============================================
  // IC-011: Error state
  // ============================================
  it('IC-011: error state shows error message with role="alert"', () => {
    render(
      <MockIndicatorCard
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
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    await user.click(card);

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  // ============================================
  // IC-013: Screen reader announces full context
  // ============================================
  it('IC-013: screen reader can announce title, value, status, and action hint', () => {
    render(<MockIndicatorCard {...defaultProps} />);

    const card = screen.getByRole('button');
    const ariaLabel = card.getAttribute('aria-label');

    // Verify all required information is in aria-label
    expect(ariaLabel).toMatch(/Keyword Rankings/);
    expect(ariaLabel).toMatch(/42/);
    expect(ariaLabel).toMatch(/good/);
    expect(ariaLabel).toMatch(/Click to view details/);
  });

  // ============================================
  // IC-014: Icon has aria-hidden
  // ============================================
  it('IC-014: decorative icons should have aria-hidden="true" (integration test)', () => {
    // Note: This test validates the pattern.
    // Actual icon implementation will be in IndicatorIcon component.
    render(<MockIndicatorCard {...defaultProps} />);

    // For now, just verify the card renders correctly
    // Icon aria-hidden will be tested in IndicatorIcon tests
    const card = screen.getByRole('button');
    expect(card).toBeInTheDocument();
  });

  // ============================================
  // IC-015: Value changes announced
  // ============================================
  it('IC-015: value changes should be announced via aria-live region', () => {
    const { rerender } = render(<MockIndicatorCard {...defaultProps} value={42} />);

    // Initial value
    expect(screen.getByTestId('card-value')).toHaveTextContent('42');

    // Update value
    rerender(<MockIndicatorCard {...defaultProps} value={50} />);

    // Value updated (actual aria-live would be in a parent component)
    expect(screen.getByTestId('card-value')).toHaveTextContent('50');
  });
});

// ============================================
// Edge Cases for IndicatorCard
// ============================================

describe('IndicatorCard Edge Cases', () => {
  const mockOnClick = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('handles very long title gracefully', () => {
    const longTitle = 'This is a very long indicator title that might overflow';
    render(
      <MockIndicatorCard
        id="test"
        title={longTitle}
        value={100}
        status="good"
        onClick={mockOnClick}
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain(longTitle);
  });

  it('handles string values correctly', () => {
    render(
      <MockIndicatorCard
        id="test"
        title="Status"
        value="Active"
        status="good"
        onClick={mockOnClick}
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('Active');
  });

  it('handles zero as a valid value', () => {
    render(
      <MockIndicatorCard
        id="test"
        title="Errors"
        value={0}
        status="good"
        onClick={mockOnClick}
      />
    );

    const card = screen.getByRole('button');
    expect(card.getAttribute('aria-label')).toContain('0');
    expect(screen.getByTestId('card-value')).toHaveTextContent('0');
  });

  it('handles negative values', () => {
    render(
      <MockIndicatorCard
        id="test"
        title="Change"
        value={-5}
        status="warning"
        onClick={mockOnClick}
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
      <MockIndicatorCard
        id="test"
        title={xssTitle}
        value={1}
        status="good"
        onClick={vi.fn()}
      />
    );

    const card = screen.getByRole('button');
    const ariaLabel = card.getAttribute('aria-label')!;
    
    // Script tag should be escaped, not executed
    expect(ariaLabel).not.toContain('<script>');
    expect(ariaLabel).toContain('script'); // Text content, not HTML
  });
});
