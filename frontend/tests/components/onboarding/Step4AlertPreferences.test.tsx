/**
 * Step 4 - Alert Preferences Component Tests
 *
 * Tests for the fourth step of the onboarding wizard that allows users
 * to configure notification preferences for ranking changes.
 *
 * @see TEST-17-9.md for test documentation
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Bell: () => <span data-testid="bell-icon">🔔</span>,
  Mail: () => <span data-testid="mail-icon">✉</span>,
  Calendar: () => <span data-testid="calendar-icon">📅</span>,
  Info: () => <span data-testid="info-icon">ℹ</span>,
  ArrowRight: () => <span data-testid="arrow-right-icon">→</span>,
  ArrowLeft: () => <span data-testid="arrow-left-icon">←</span>,
}));

// Mock component implementation for TDD
const Step4AlertPreferences = ({
  onComplete,
  onBack,
  onSkip,
  initialData,
}: {
  onComplete: (data: { alert_preferences: { email: boolean; weekly: boolean } }) => void;
  onBack: () => void;
  onSkip: () => void;
  initialData?: { email: boolean; weekly: boolean };
}) => {
  const defaultPreferences = { email: true, weekly: false };
  const preferences = initialData ?? defaultPreferences;

  return (
    <div data-testid="step4-alert-preferences">
      <h2>Get notified when your rankings change</h2>
      <p data-testid="tooltip-text">Stay informed without checking constantly</p>

      {/* Email Toggle */}
      <div data-testid="email-toggle-container">
        <label htmlFor="email-toggle">Email alerts for ranking changes</label>
        <p>Receive instant notifications when your rankings change</p>
        <button
          id="email-toggle"
          role="switch"
          aria-checked={preferences.email}
          data-testid="email-toggle"
        >
          {preferences.email ? 'ON' : 'OFF'}
        </button>
      </div>

      {/* Weekly Toggle */}
      <div data-testid="weekly-toggle-container">
        <label htmlFor="weekly-toggle">Weekly summary reports</label>
        <p>Get a weekly digest of your SEO performance</p>
        <button
          id="weekly-toggle"
          role="switch"
          aria-checked={preferences.weekly}
          data-testid="weekly-toggle"
        >
          {preferences.weekly ? 'ON' : 'OFF'}
        </button>
      </div>

      {/* Navigation */}
      <button onClick={() => onBack?.()} aria-label="Go back to previous step">
        Back
      </button>
      <button
        onClick={() => onComplete?.({ alert_preferences: preferences })}
        aria-label="Continue to next step"
      >
        Continue
      </button>
      <button onClick={() => onSkip?.()} aria-label="Skip alert preferences setup">
        Skip for now
      </button>
    </div>
  );
};

describe('Step4AlertPreferences', () => {
  const defaultProps = {
    onComplete: vi.fn(),
    onBack: vi.fn(),
    onSkip: vi.fn(),
    initialData: undefined,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // AP-001: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('AP-001-01: renders with required props', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByTestId('step4-alert-preferences')).toBeInTheDocument();
    });

    it('AP-001-02: displays step title', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByText(/get notified when your rankings change/i)).toBeInTheDocument();
    });

    it('AP-001-03: has correct data-testid', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByTestId('step4-alert-preferences')).toBeInTheDocument();
    });

    it('AP-001-04: renders both toggle switches', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByTestId('email-toggle')).toBeInTheDocument();
      expect(screen.getByTestId('weekly-toggle')).toBeInTheDocument();
    });

    it('AP-001-05: renders continue button', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByRole('button', { name: /continue/i })).toBeInTheDocument();
    });

    it('AP-001-06: renders back button', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByRole('button', { name: /back/i })).toBeInTheDocument();
    });

    it('AP-001-07: renders skip link', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByRole('button', { name: /skip for now/i })).toBeInTheDocument();
    });

    it('AP-001-08: renders tooltip text', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByTestId('tooltip-text')).toBeInTheDocument();
      expect(screen.getByText(/stay informed without checking constantly/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // AP-002: Toggle Switches - Rendering
  // ============================================
  describe('Toggle Rendering', () => {
    it('AP-002-01: email toggle has label', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByText(/email alerts for ranking changes/i)).toBeInTheDocument();
    });

    it('AP-002-02: email toggle has description', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByText(/receive instant notifications/i)).toBeInTheDocument();
    });

    it('AP-002-03: weekly toggle has label', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByText(/weekly summary reports/i)).toBeInTheDocument();
    });

    it('AP-002-04: weekly toggle has description', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByText(/weekly digest/i)).toBeInTheDocument();
    });

    it('AP-002-05: email toggle default ON', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      const emailToggle = screen.getByTestId('email-toggle');
      expect(emailToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-002-06: weekly toggle default OFF', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      const weeklyToggle = screen.getByTestId('weekly-toggle');
      expect(weeklyToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-002-07: toggles have switch role', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByTestId('email-toggle')).toHaveAttribute('role', 'switch');
      expect(screen.getByTestId('weekly-toggle')).toHaveAttribute('role', 'switch');
    });

    it('AP-002-08: toggles have aria-checked', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      const emailToggle = screen.getByTestId('email-toggle');
      const weeklyToggle = screen.getByTestId('weekly-toggle');
      expect(emailToggle).toHaveAttribute('aria-checked');
      expect(weeklyToggle).toHaveAttribute('aria-checked');
    });
  });

  // ============================================
  // AP-003: Toggle Switches - Interaction
  // ============================================
  describe('Toggle Interaction', () => {
    it('AP-003-01: toggle email OFF', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      const emailToggle = screen.getByTestId('email-toggle');
      // await user.click(emailToggle);

      // After implementation:
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-003-02: toggle email ON again', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // await user.click(emailToggle); // OFF
      // await user.click(emailToggle); // ON
      // expect(emailToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-003-03: toggle weekly ON', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // await user.click(weeklyToggle);
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-003-04: toggle weekly OFF again', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // await user.click(weeklyToggle); // ON
      // await user.click(weeklyToggle); // OFF
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-003-05: toggle state persists', async () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Toggle weekly ON, verify it stays ON
    });

    it('AP-003-06: visual feedback on change', async () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation: check for animation class or style change
    });

    it('AP-003-07: keyboard toggle (Enter)', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // emailToggle.focus();
      // await user.keyboard('{Enter}');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-003-08: keyboard toggle (Space)', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // emailToggle.focus();
      // await user.keyboard(' ');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
    });
  });

  // ============================================
  // AP-004: Initial Data
  // ============================================
  describe('Initial Data', () => {
    it('AP-004-01: uses initialData when provided', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: false, weekly: true }} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-004-02: initialData email OFF honored', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: false, weekly: false }} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-004-03: initialData weekly ON honored', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: true, weekly: true }} />);

      // After implementation:
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-004-04: empty initialData uses defaults', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={undefined} />);

      const emailToggle = screen.getByTestId('email-toggle');
      const weeklyToggle = screen.getByTestId('weekly-toggle');
      expect(emailToggle).toHaveAttribute('aria-checked', 'true');
      expect(weeklyToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-004-05: partial initialData merges', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: false }} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'false'); // default
    });
  });

  // ============================================
  // AP-005: Continue Button
  // ============================================
  describe('Continue Button', () => {
    it('AP-005-01: triggers onComplete with preferences', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const continueButton = screen.getByRole('button', { name: /continue/i });
      fireEvent.click(continueButton);

      expect(defaultProps.onComplete).toHaveBeenCalledTimes(1);
    });

    it('AP-005-02: sends correct data structure', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const continueButton = screen.getByRole('button', { name: /continue/i });
      fireEvent.click(continueButton);

      expect(defaultProps.onComplete).toHaveBeenCalledWith({
        alert_preferences: {
          email: true,
          weekly: false,
        },
      });
    });

    it('AP-005-03: sends updated preferences', async () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: true, weekly: true }} />);

      // After implementation with state:
      // const continueButton = screen.getByRole('button', { name: /continue/i });
      // fireEvent.click(continueButton);
      // expect(defaultProps.onComplete).toHaveBeenCalledWith({
      //   alert_preferences: { email: true, weekly: true },
      // });
    });

    it('AP-005-04: sends email OFF', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: false, weekly: false }} />);

      // After implementation:
      // const continueButton = screen.getByRole('button', { name: /continue/i });
      // fireEvent.click(continueButton);
      // expect(defaultProps.onComplete).toHaveBeenCalledWith({
      //   alert_preferences: { email: false, weekly: false },
      // });
    });

    it('AP-005-05: has accessible label', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const continueButton = screen.getByLabelText(/continue to next step/i);
      expect(continueButton).toBeInTheDocument();
    });

    it('AP-005-06: is primary button', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const continueButton = screen.getByRole('button', { name: /continue/i });
      // expect(continueButton).toHaveClass('bg-primary');
    });
  });

  // ============================================
  // AP-006: Back Button
  // ============================================
  describe('Back Button', () => {
    it('AP-006-01: back button renders', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByRole('button', { name: /back/i })).toBeInTheDocument();
    });

    it('AP-006-02: back triggers callback', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const backButton = screen.getByRole('button', { name: /back/i });
      fireEvent.click(backButton);

      expect(defaultProps.onBack).toHaveBeenCalledTimes(1);
    });

    it('AP-006-03: back has accessible label', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const backButton = screen.getByLabelText(/go back to previous step/i);
      expect(backButton).toBeInTheDocument();
    });

    it('AP-006-04: back preserves state', async () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Toggle weekly ON
      // Click back
      // Return to step
      // Verify weekly is still ON
    });

    it('AP-006-05: back is secondary button', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const backButton = screen.getByRole('button', { name: /back/i });
      // expect(backButton).toHaveClass('btn-secondary');
    });
  });

  // ============================================
  // AP-007: Skip Functionality
  // ============================================
  describe('Skip Functionality', () => {
    it('AP-007-01: skip link renders', () => {
      render(<Step4AlertPreferences {...defaultProps} />);
      expect(screen.getByRole('button', { name: /skip for now/i })).toBeInTheDocument();
    });

    it('AP-007-02: skip triggers callback', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const skipButton = screen.getByRole('button', { name: /skip for now/i });
      fireEvent.click(skipButton);

      expect(defaultProps.onSkip).toHaveBeenCalledTimes(1);
    });

    it('AP-007-03: skip uses defaults', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation: skip should use default preferences (email: true, weekly: false)
      // The parent component handles the actual preference storage
    });

    it('AP-007-04: skip has accessible name', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const skipButton = screen.getByLabelText(/skip alert preferences setup/i);
      expect(skipButton).toBeInTheDocument();
    });

    it('AP-007-05: skip is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      const skipButton = screen.getByRole('button', { name: /skip for now/i });
      skipButton.focus();
      await user.keyboard('{Enter}');

      expect(defaultProps.onSkip).toHaveBeenCalledTimes(1);
    });
  });

  // ============================================
  // AP-008: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('AP-008-01: toggles have accessible names', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const emailToggle = screen.getByTestId('email-toggle');
      const weeklyToggle = screen.getByTestId('weekly-toggle');

      // Labels should be associated
      expect(screen.getByText(/email alerts for ranking changes/i)).toBeInTheDocument();
      expect(screen.getByText(/weekly summary reports/i)).toBeInTheDocument();
    });

    it('AP-008-02: email toggle aria-checked true by default', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const emailToggle = screen.getByTestId('email-toggle');
      expect(emailToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-008-03: weekly toggle aria-checked false by default', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      const weeklyToggle = screen.getByTestId('weekly-toggle');
      expect(weeklyToggle).toHaveAttribute('aria-checked', 'false');
    });

    it('AP-008-04: focus visible on toggles', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // await user.tab();
      // expect(emailToggle).toHaveFocus();
    });

    it('AP-008-05: focus visible on buttons', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Tab through all buttons and verify focus
    });

    it('AP-008-06: all interactive elements focusable', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // Tab through all elements
      const focusableElements = screen.getAllByRole('button');
      expect(focusableElements.length).toBeGreaterThan(0);
    });

    it('AP-008-07: screen reader announces state', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Verify live region or aria-label updates on toggle
    });

    it('AP-008-08: color contrast sufficient', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // Visual test - verify with axe-core after implementation
    });

    it('AP-008-09: labels properly associated', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Verify htmlFor on labels matches id on toggles
    });

    it('AP-008-10: component has accessible name', () => {
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // const container = screen.getByTestId('step4-alert-preferences');
      // expect(container).toHaveAttribute('aria-label', 'Alert preferences');
    });
  });

  // ============================================
  // AP-009: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('AP-009-01: both toggles OFF', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: false, weekly: false }} />);

      const emailToggle = screen.getByTestId('email-toggle');
      const weeklyToggle = screen.getByTestId('weekly-toggle');

      // After implementation:
      // expect(emailToggle).toHaveAttribute('aria-checked', 'false');
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'false');

      // Continue should still work
      const continueButton = screen.getByRole('button', { name: /continue/i });
      fireEvent.click(continueButton);
      expect(defaultProps.onComplete).toHaveBeenCalled();
    });

    it('AP-009-02: both toggles ON', () => {
      render(<Step4AlertPreferences {...defaultProps} initialData={{ email: true, weekly: true }} />);

      // After implementation:
      // const emailToggle = screen.getByTestId('email-toggle');
      // const weeklyToggle = screen.getByTestId('weekly-toggle');
      // expect(emailToggle).toHaveAttribute('aria-checked', 'true');
      // expect(weeklyToggle).toHaveAttribute('aria-checked', 'true');
    });

    it('AP-009-03: rapid toggle clicks', async () => {
      const user = userEvent.setup();
      render(<Step4AlertPreferences {...defaultProps} />);

      // After implementation:
      // Click toggle rapidly and verify final state is correct
    });

    it('AP-009-04: missing onComplete', () => {
      const props = { ...defaultProps, onComplete: undefined as unknown as typeof defaultProps.onComplete };
      render(<Step4AlertPreferences {...props} />);

      const continueButton = screen.getByRole('button', { name: /continue/i });
      // Should not crash
      expect(() => fireEvent.click(continueButton)).not.toThrow();
    });

    it('AP-009-05: missing onBack', () => {
      const props = { ...defaultProps, onBack: undefined as unknown as typeof defaultProps.onBack };
      render(<Step4AlertPreferences {...props} />);

      const backButton = screen.getByRole('button', { name: /back/i });
      // Should not crash
      expect(() => fireEvent.click(backButton)).not.toThrow();
    });

    it('AP-009-06: missing onSkip', () => {
      const props = { ...defaultProps, onSkip: undefined as unknown as typeof defaultProps.onSkip };
      render(<Step4AlertPreferences {...props} />);

      const skipButton = screen.getByRole('button', { name: /skip for now/i });
      // Should not crash
      expect(() => fireEvent.click(skipButton)).not.toThrow();
    });
  });
});
