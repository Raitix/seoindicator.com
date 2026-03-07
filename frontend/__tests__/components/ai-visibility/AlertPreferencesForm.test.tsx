/**
 * AlertPreferencesForm Component Tests
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 *
 * Tests for AlertPreferencesForm component
 */

import React from 'react';
import { render, screen, within, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { AlertPreferencesForm } from '@/components/ai-visibility/AlertPreferencesForm';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  Mail: () => <span data-testid="mail-icon">📧</span>,
  Link: () => <span data-testid="link-icon">🔗</span>,
  Bell: () => <span data-testid="bell-icon">🔔</span>,
  Save: () => <span data-testid="save-icon">💾</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  Loader2: () => <span data-testid="loader-icon" className="animate-spin">Loading</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  RefreshCw: () => <span data-testid="refresh-icon">🔄</span>,
  AlertCircle: () => <span data-testid="alert-icon">⚠</span>,
}));

// Test data
const defaultPreferences = {
  email_notifications: true,
  webhook_url: 'https://example.com/webhook',
  alert_types: ['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'] as const,
};

describe('AlertPreferencesForm', () => {
  const mockOnSave = jest.fn();
  const mockOnCancel = jest.fn();
  const mockOnTestWebhook = jest.fn();

  const defaultProps = {
    preferences: defaultPreferences,
    onSave: mockOnSave,
    onCancel: mockOnCancel,
    onTestWebhook: mockOnTestWebhook,
    isLoading: false,
    isSaving: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('APF-001: renders form', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      expect(screen.getByTestId('alert-preferences-form')).toBeInTheDocument();
    });

    it('APF-002: email toggle unchecked when false', () => {
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, email_notifications: false }}
        />
      );

      const toggle = screen.getByRole('switch', { name: /email notifications/i });
      expect(toggle).not.toBeChecked();
    });

    it('APF-003: email toggle checked when true', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const toggle = screen.getByRole('switch', { name: /email notifications/i });
      expect(toggle).toBeChecked();
    });

    it('APF-005: webhook URL empty when null', () => {
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, webhook_url: null }}
        />
      );

      const input = screen.getByLabelText(/webhook url/i);
      expect(input).toHaveValue('');
    });

    it('APF-006: webhook URL filled when set', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const input = screen.getByLabelText(/webhook url/i);
      expect(input).toHaveValue('https://example.com/webhook');
    });

    it('APF-011: all alert type checkboxes checked by default', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const checkboxes = screen.getAllByRole('checkbox', { name: /mention|competitor|sentiment/i });
      checkboxes.forEach(cb => expect(cb).toBeChecked());
    });
  });

  // ============================================================================
  // Email Toggle Tests
  // ============================================================================

  describe('Email Toggle', () => {
    it('APF-004: toggle changes state', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const toggle = screen.getByRole('switch', { name: /email notifications/i });
      await user.click(toggle);

      expect(toggle).not.toBeChecked();
    });
  });

  // ============================================================================
  // Webhook URL Tests
  // ============================================================================

  describe('Webhook URL', () => {
    it('APF-007: webhook URL change updates state', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const input = screen.getByLabelText(/webhook url/i);
      await user.clear(input);
      await user.type(input, 'https://new-url.com/hook');

      expect(input).toHaveValue('https://new-url.com/hook');
    });

    it('APF-008: invalid URL shows error', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const input = screen.getByLabelText(/webhook url/i);
      await user.clear(input);
      await user.type(input, 'not-a-url');

      // Trigger validation
      await user.click(screen.getByRole('button', { name: /save/i }));

      expect(screen.getByText(/invalid url/i)).toBeInTheDocument();
    });

    it('APF-009: valid URL shows no error', async () => {
      const user = userEvent.setup();
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, webhook_url: null }}
        />
      );

      const input = screen.getByLabelText(/webhook url/i);
      await user.type(input, 'https://valid-url.com/webhook');

      await user.click(screen.getByRole('button', { name: /save/i }));

      expect(screen.queryByText(/invalid url/i)).not.toBeInTheDocument();
    });

    it('APF-010: webhook URL clear button', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /clear webhook/i }));

      const input = screen.getByLabelText(/webhook url/i);
      expect(input).toHaveValue('');
    });
  });

  // ============================================================================
  // Alert Type Checkboxes Tests
  // ============================================================================

  describe('Alert Type Checkboxes', () => {
    it('APF-012: uncheck alert type updates state', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const checkbox = screen.getByRole('checkbox', { name: /mention spike/i });
      await user.click(checkbox);

      expect(checkbox).not.toBeChecked();
    });

    it('APF-013: check alert type updates state', async () => {
      const user = userEvent.setup();
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, alert_types: ['mention_drop'] }}
        />
      );

      const checkbox = screen.getByRole('checkbox', { name: /mention spike/i });
      expect(checkbox).not.toBeChecked();

      await user.click(checkbox);

      expect(checkbox).toBeChecked();
    });

    it('APF-014: at least one alert type required', async () => {
      const user = userEvent.setup();
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, alert_types: ['mention_spike'] }}
        />
      );

      // Uncheck the only one
      const checkbox = screen.getByRole('checkbox', { name: /mention spike/i });
      await user.click(checkbox);

      await user.click(screen.getByRole('button', { name: /save/i }));

      expect(screen.getByText(/at least one alert type/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Save Actions Tests
  // ============================================================================

  describe('Save Actions', () => {
    it('APF-015: save button triggers onSave', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      // Make a change
      await user.click(screen.getByRole('switch', { name: /email notifications/i }));

      await user.click(screen.getByRole('button', { name: /save/i }));

      expect(mockOnSave).toHaveBeenCalled();
    });

    it('APF-016: save disabled when no changes', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const saveBtn = screen.getByRole('button', { name: /save/i });
      expect(saveBtn).toBeDisabled();
    });

    it('APF-017: save enabled when changes made', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const saveBtn = screen.getByRole('button', { name: /save/i });
      expect(saveBtn).toBeDisabled();

      await user.click(screen.getByRole('switch', { name: /email notifications/i }));

      expect(saveBtn).not.toBeDisabled();
    });

    it('APF-018: saving state shows spinner', () => {
      render(<AlertPreferencesForm {...defaultProps} isSaving />);

      expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /save/i })).toBeDisabled();
    });

    it('APF-019: success toast on save', async () => {
      const user = userEvent.setup();
      mockOnSave.mockResolvedValueOnce({ success: true });

      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('switch', { name: /email notifications/i }));
      await user.click(screen.getByRole('button', { name: /save/i }));

      await waitFor(() => {
        expect(screen.getByText(/preferences saved/i)).toBeInTheDocument();
      });
    });

    it('APF-020: error message on save failure', async () => {
      const user = userEvent.setup();
      mockOnSave.mockRejectedValueOnce(new Error('Save failed'));

      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('switch', { name: /email notifications/i }));
      await user.click(screen.getByRole('button', { name: /save/i }));

      await waitFor(() => {
        expect(screen.getByText(/failed to save/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Cancel Actions Tests
  // ============================================================================

  describe('Cancel Actions', () => {
    it('APF-021: cancel button triggers onCancel', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /cancel/i }));

      expect(mockOnCancel).toHaveBeenCalled();
    });

    it('APF-022: unsaved changes show confirm dialog', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      // Make a change
      await user.click(screen.getByRole('switch', { name: /email notifications/i }));

      // Click cancel
      await user.click(screen.getByRole('button', { name: /cancel/i }));

      expect(screen.getByRole('alertdialog')).toBeInTheDocument();
      expect(screen.getByText(/unsaved changes/i)).toBeInTheDocument();
    });

    it('APF-023: confirm discard resets form', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('switch', { name: /email notifications/i }));
      await user.click(screen.getByRole('button', { name: /cancel/i }));
      await user.click(screen.getByRole('button', { name: /discard/i }));

      expect(mockOnCancel).toHaveBeenCalled();
    });

    it('APF-024: cancel discard keeps form', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      const toggle = screen.getByRole('switch', { name: /email notifications/i });
      await user.click(toggle);
      await user.click(screen.getByRole('button', { name: /cancel/i }));
      await user.click(screen.getByRole('button', { name: /keep editing/i }));

      // Toggle should still be unchecked
      expect(toggle).not.toBeChecked();
    });

    it('APF-025: reset to defaults button', async () => {
      const user = userEvent.setup();
      const onReset = jest.fn();
      render(<AlertPreferencesForm {...defaultProps} onReset={onReset} />);

      await user.click(screen.getByRole('button', { name: /reset to defaults/i }));

      expect(onReset).toHaveBeenCalled();
    });
  });

  // ============================================================================
  // Webhook Test Tests
  // ============================================================================

  describe('Webhook Test', () => {
    it('APF-027: test webhook button', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /test webhook/i }));

      expect(mockOnTestWebhook).toHaveBeenCalledWith('https://example.com/webhook');
    });

    it('APF-028: webhook test success message', async () => {
      const user = userEvent.setup();
      mockOnTestWebhook.mockResolvedValueOnce({ success: true });

      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /test webhook/i }));

      await waitFor(() => {
        expect(screen.getByText(/webhook test successful/i)).toBeInTheDocument();
      });
    });

    it('APF-029: webhook test failure message', async () => {
      const user = userEvent.setup();
      mockOnTestWebhook.mockRejectedValueOnce(new Error('Connection failed'));

      render(<AlertPreferencesForm {...defaultProps} />);

      await user.click(screen.getByRole('button', { name: /test webhook/i }));

      await waitFor(() => {
        expect(screen.getByText(/webhook test failed/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================================================
  // Loading State Tests
  // ============================================================================

  describe('Loading State', () => {
    it('APF-026: loading skeleton', () => {
      render(<AlertPreferencesForm {...defaultProps} isLoading />);

      expect(screen.getAllByTestId(/skeleton/).length).toBeGreaterThan(0);
    });
  });

  // ============================================================================
  // Accessibility Tests
  // ============================================================================

  describe('Accessibility', () => {
    it('APF-030: all inputs have labels', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      expect(screen.getByLabelText(/email notifications/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/webhook url/i)).toBeInTheDocument();
      expect(screen.getAllByRole('checkbox').length).toBeGreaterThan(0);
    });

    it('APF-031: keyboard navigation order', async () => {
      const user = userEvent.setup();
      render(<AlertPreferencesForm {...defaultProps} />);

      await user.tab();
      expect(screen.getByRole('switch', { name: /email notifications/i })).toHaveFocus();

      await user.tab();
      expect(screen.getByLabelText(/webhook url/i)).toHaveFocus();
    });

    it('APF-032: form validation errors accessible', async () => {
      const user = userEvent.setup();
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, webhook_url: null }}
        />
      );

      const input = screen.getByLabelText(/webhook url/i);
      await user.type(input, 'invalid');
      await user.click(screen.getByRole('button', { name: /save/i }));

      const error = screen.getByText(/invalid url/i);
      expect(error).toBeInTheDocument();
      expect(error).toHaveAttribute('role', 'alert');
    });
  });

  // ============================================================================
  // Responsive Tests
  // ============================================================================

  describe('Responsive', () => {
    beforeEach(() => {
      global.innerWidth = 375;
    });

    it('APF-034: stacked layout on mobile', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const form = screen.getByTestId('alert-preferences-form');
      expect(form).toHaveClass('flex-col');
    });
  });

  // ============================================================================
  // Help Text Tests
  // ============================================================================

  describe('Help Text', () => {
    it('APF-035: webhook placeholder text', () => {
      render(
        <AlertPreferencesForm
          {...defaultProps}
          preferences={{ ...defaultPreferences, webhook_url: null }}
        />
      );

      const input = screen.getByLabelText(/webhook url/i);
      expect(input).toHaveAttribute('placeholder');
    });

    it('APF-036: email help text visible', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      expect(screen.getByText(/receive email notifications/i)).toBeInTheDocument();
    });

    it('APF-037: alert type descriptions shown', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      // Each alert type should have a description
      expect(screen.getByText(/notify when brand mentions/i)).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Dark Mode Tests
  // ============================================================================

  describe('Dark Mode', () => {
    it('APF-033: dark mode classes applied', () => {
      render(<AlertPreferencesForm {...defaultProps} />);

      const form = screen.getByTestId('alert-preferences-form');
      expect(form).toHaveClass('dark:bg-slate-800');
    });
  });
});
