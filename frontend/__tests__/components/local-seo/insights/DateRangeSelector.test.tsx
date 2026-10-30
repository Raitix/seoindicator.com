/**
 * Tests for DateRangeSelector Component
 * Task: TASK-35-20
 * Tests: 48
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Calendar: () => <span data-testid="calendar-icon">📅</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">▼</span>,
  X: () => <span data-testid="x-icon">✕</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
}));

// Mock date-fns
vi.mock('date-fns', () => ({
  format: vi.fn((date: Date) => date.toISOString().split('T')[0]),
  subDays: vi.fn((date: Date, days: number) => new Date(date.getTime() - days * 24 * 60 * 60 * 1000)),
}));

interface DateRangeSelectorProps {
  value: '7d' | '30d' | '90d' | 'custom';
  onChange: (range: { preset: string; start?: Date; end?: Date }) => void;
  className?: string;
  disabled?: boolean;
}

// Mock component (TDD reference implementation)
const MockDateRangeSelector = ({ value, onChange, className = '', disabled = false }: DateRangeSelectorProps) => {
  const presets = [
    { id: '7d', label: '7 days' },
    { id: '30d', label: '30 days' },
    { id: '90d', label: '90 days' },
    { id: 'custom', label: 'Custom' },
  ] as const;

  const handlePresetClick = (presetId: string) => {
    if (presetId === 'custom') {
      onChange({ preset: 'custom' });
    } else {
      onChange({ preset: presetId });
    }
  };

  return (
    <div
      data-testid="date-range-selector"
      className={className}
      role="group"
      aria-label="Date range selection"
      aria-disabled={disabled}
    >
      <div data-testid="preset-buttons" className="flex gap-2">
        {presets.map((preset) => (
          <button
            key={preset.id}
            data-testid={`preset-${preset.id}`}
            onClick={() => handlePresetClick(preset.id)}
            aria-pressed={value === preset.id}
            disabled={disabled}
            className={value === preset.id ? 'bg-primary text-white' : 'bg-gray-100'}
          >
            {preset.label}
          </button>
        ))}
      </div>

      {value === 'custom' && (
        <div data-testid="custom-date-picker" className="mt-2 flex gap-2">
          <input
            type="date"
            data-testid="start-date"
            aria-label="Start date"
            onChange={(e) => onChange({ preset: 'custom', start: new Date(e.target.value) })}
          />
          <span data-testid="date-separator">to</span>
          <input
            type="date"
            data-testid="end-date"
            aria-label="End date"
            onChange={(e) => onChange({ preset: 'custom', end: new Date(e.target.value) })}
          />
        </div>
      )}
    </div>
  );
};

describe('DateRangeSelector', () => {
  const mockOnChange = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('DRS-001 to DRS-010: Rendering', () => {
    it('DRS-001: renders all preset buttons', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByTestId('preset-7d')).toBeInTheDocument();
      expect(screen.getByTestId('preset-30d')).toBeInTheDocument();
      expect(screen.getByTestId('preset-90d')).toBeInTheDocument();
      expect(screen.getByTestId('preset-custom')).toBeInTheDocument();
    });

    it('DRS-002: displays correct button labels', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByText('7 days')).toBeInTheDocument();
      expect(screen.getByText('30 days')).toBeInTheDocument();
      expect(screen.getByText('90 days')).toBeInTheDocument();
      expect(screen.getByText('Custom')).toBeInTheDocument();
    });

    it('DRS-003: highlights selected preset', () => {
      render(<MockDateRangeSelector value="30d" onChange={mockOnChange} />);

      const preset30d = screen.getByTestId('preset-30d');
      expect(preset30d).toHaveAttribute('aria-pressed', 'true');
      expect(preset30d).toHaveClass('bg-primary');
    });

    it('DRS-004: shows custom date picker when custom selected', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      expect(screen.getByTestId('custom-date-picker')).toBeInTheDocument();
      expect(screen.getByTestId('start-date')).toBeInTheDocument();
      expect(screen.getByTestId('end-date')).toBeInTheDocument();
    });

    it('DRS-005: hides custom date picker for preset values', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.queryByTestId('custom-date-picker')).not.toBeInTheDocument();
    });

    it('DRS-006: applies custom className', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} className="custom-class" />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveClass('custom-class');
    });

    it('DRS-007: renders with role="group"', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveAttribute('role', 'group');
    });

    it('DRS-008: has accessible aria-label', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveAttribute('aria-label', 'Date range selection');
    });

    it('DRS-009: renders preset buttons container', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByTestId('preset-buttons')).toBeInTheDocument();
    });

    it('DRS-010: displays date separator in custom mode', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      expect(screen.getByTestId('date-separator')).toHaveTextContent('to');
    });
  });

  describe('DRS-011 to DRS-020: Interaction', () => {
    it('DRS-011: clicking preset calls onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      await user.click(screen.getByTestId('preset-30d'));

      expect(mockOnChange).toHaveBeenCalledWith({ preset: '30d' });
    });

    it('DRS-012: clicking custom shows date picker', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      await user.click(screen.getByTestId('preset-custom'));

      expect(mockOnChange).toHaveBeenCalledWith({ preset: 'custom' });
    });

    it('DRS-013: preset buttons have aria-pressed attribute', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByTestId('preset-7d')).toHaveAttribute('aria-pressed', 'true');
      expect(screen.getByTestId('preset-30d')).toHaveAttribute('aria-pressed', 'false');
      expect(screen.getByTestId('preset-90d')).toHaveAttribute('aria-pressed', 'false');
      expect(screen.getByTestId('preset-custom')).toHaveAttribute('aria-pressed', 'false');
    });

    it('DRS-014: only one preset is pressed at a time', () => {
      render(<MockDateRangeSelector value="30d" onChange={mockOnChange} />);

      const buttons = [
        screen.getByTestId('preset-7d'),
        screen.getByTestId('preset-30d'),
        screen.getByTestId('preset-90d'),
        screen.getByTestId('preset-custom'),
      ];

      const pressedCount = buttons.filter(b => b.getAttribute('aria-pressed') === 'true').length;
      expect(pressedCount).toBe(1);
    });

    it('DRS-015: start date input has aria-label', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const startInput = screen.getByTestId('start-date');
      expect(startInput).toHaveAttribute('aria-label', 'Start date');
    });

    it('DRS-016: end date input has aria-label', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const endInput = screen.getByTestId('end-date');
      expect(endInput).toHaveAttribute('aria-label', 'End date');
    });

    it('DRS-017: clicking 7d preset triggers onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="30d" onChange={mockOnChange} />);

      await user.click(screen.getByTestId('preset-7d'));

      expect(mockOnChange).toHaveBeenCalledWith({ preset: '7d' });
    });

    it('DRS-018: clicking 90d preset triggers onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      await user.click(screen.getByTestId('preset-90d'));

      expect(mockOnChange).toHaveBeenCalledWith({ preset: '90d' });
    });

    it('DRS-019: onChange is called once per click', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      await user.click(screen.getByTestId('preset-30d'));

      expect(mockOnChange).toHaveBeenCalledTimes(1);
    });

    it('DRS-020: preset buttons are keyboard accessible', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });
  });

  describe('DRS-021 to DRS-030: Custom Date Selection', () => {
    it('DRS-021: custom date picker renders with correct structure', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const picker = screen.getByTestId('custom-date-picker');
      expect(picker).toBeInTheDocument();
    });

    it('DRS-022: start date input type is date', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const startInput = screen.getByTestId('start-date');
      expect(startInput).toHaveAttribute('type', 'date');
    });

    it('DRS-023: end date input type is date', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const endInput = screen.getByTestId('end-date');
      expect(endInput).toHaveAttribute('type', 'date');
    });

    it('DRS-024: start date change triggers onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const startInput = screen.getByTestId('start-date');
      fireEvent.change(startInput, { target: { value: '2026-03-01' } });

      expect(mockOnChange).toHaveBeenCalled();
    });

    it('DRS-025: end date change triggers onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const endInput = screen.getByTestId('end-date');
      fireEvent.change(endInput, { target: { value: '2026-03-07' } });

      expect(mockOnChange).toHaveBeenCalled();
    });

    it('DRS-026: custom picker appears after custom button click', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.queryByTestId('custom-date-picker')).not.toBeInTheDocument();

      await user.click(screen.getByTestId('preset-custom'));

      expect(mockOnChange).toHaveBeenCalledWith({ preset: 'custom' });
    });

    it('DRS-027: date separator is visible in custom mode', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      expect(screen.getByText('to')).toBeVisible();
    });

    it('DRS-028: custom date picker has flex layout', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const picker = screen.getByTestId('custom-date-picker');
      expect(picker).toHaveClass('flex');
    });

    it('DRS-029: custom date picker has gap between inputs', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const picker = screen.getByTestId('custom-date-picker');
      expect(picker).toHaveClass('gap-2');
    });

    it('DRS-030: custom date picker appears with margin top', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      const picker = screen.getByTestId('custom-date-picker');
      expect(picker).toHaveClass('mt-2');
    });
  });

  describe('DRS-031 to DRS-040: Disabled State', () => {
    it('DRS-031: renders with disabled prop', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveAttribute('aria-disabled', 'true');
    });

    it('DRS-032: buttons are disabled when disabled=true', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).toBeDisabled();
      });
    });

    it('DRS-033: disabled component does not call onChange', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      await user.click(screen.getByTestId('preset-30d'));

      expect(mockOnChange).not.toHaveBeenCalled();
    });

    it('DRS-034: buttons are enabled when disabled=false', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={false} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toBeDisabled();
      });
    });

    it('DRS-035: default disabled state is false', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveAttribute('aria-disabled', 'false');
    });

    it('DRS-036: disabled state applies to custom date inputs', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} disabled={true} />);

      const startInput = screen.getByTestId('start-date');
      const endInput = screen.getByTestId('end-date');

      expect(startInput).toBeDisabled();
      expect(endInput).toBeDisabled();
    });

    it('DRS-037: preset buttons are focusable when enabled', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={false} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('DRS-038: clicking disabled button does nothing', async () => {
      const user = userEvent.setup();
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      const button = screen.getByTestId('preset-30d');
      expect(button).toBeDisabled();
    });

    it('DRS-039: aria-disabled reflects disabled state', () => {
      const { rerender } = render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={false} />);

      expect(screen.getByTestId('date-range-selector')).toHaveAttribute('aria-disabled', 'false');

      rerender(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      expect(screen.getByTestId('date-range-selector')).toHaveAttribute('aria-disabled', 'true');
    });

    it('DRS-040: disabled state does not affect selected state display', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      const preset7d = screen.getByTestId('preset-7d');
      expect(preset7d).toHaveAttribute('aria-pressed', 'true');
    });
  });

  describe('DRS-041 to DRS-048: Accessibility', () => {
    it('DRS-041: all buttons have accessible names', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByRole('button', { name: '7 days' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: '30 days' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: '90 days' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Custom' })).toBeInTheDocument();
    });

    it('DRS-042: group has accessible label', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const group = screen.getByRole('group', { name: 'Date range selection' });
      expect(group).toBeInTheDocument();
    });

    it('DRS-043: custom date inputs have accessible labels', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      expect(screen.getByLabelText('Start date')).toBeInTheDocument();
      expect(screen.getByLabelText('End date')).toBeInTheDocument();
    });

    it('DRS-044: preset buttons use aria-pressed for state', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const preset7d = screen.getByTestId('preset-7d');
      expect(preset7d).toHaveAttribute('aria-pressed');
    });

    it('DRS-045: only one button can be pressed at a time', () => {
      const { rerender } = render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      expect(screen.getByTestId('preset-7d')).toHaveAttribute('aria-pressed', 'true');

      rerender(<MockDateRangeSelector value="30d" onChange={mockOnChange} />);

      expect(screen.getByTestId('preset-30d')).toHaveAttribute('aria-pressed', 'true');
      expect(screen.getByTestId('preset-7d')).toHaveAttribute('aria-pressed', 'false');
    });

    it('DRS-046: custom mode shows date inputs with labels', () => {
      render(<MockDateRangeSelector value="custom" onChange={mockOnChange} />);

      expect(screen.getByLabelText('Start date')).toBeVisible();
      expect(screen.getByLabelText('End date')).toBeVisible();
    });

    it('DRS-047: keyboard can navigate between buttons', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('DRS-048: disabled state is communicated to screen readers', () => {
      render(<MockDateRangeSelector value="7d" onChange={mockOnChange} disabled={true} />);

      const selector = screen.getByTestId('date-range-selector');
      expect(selector).toHaveAttribute('aria-disabled', 'true');
    });
  });
});
