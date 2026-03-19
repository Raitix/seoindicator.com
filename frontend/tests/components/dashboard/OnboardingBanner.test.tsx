/**
 * Resume Onboarding Banner Tests
 *
 * Tests for the dashboard banner that prompts users to resume
 * their incomplete onboarding wizard.
 *
 * @see TEST-17-12.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value;
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  X: () => <span data-testid="x-icon">×</span>,
  Target: () => <span data-testid="target-icon">🎯</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">→</span>,
  Clock: () => <span data-testid="clock-icon">⏰</span>,
}));

// Mock component implementation for TDD
const OnboardingBanner = ({
  progress,
  onContinue,
  onDismiss,
  onDontShowAgain,
}: {
  progress: {
    currentStep: number;
    completedSteps: number[];
    skipped: boolean;
    totalSteps: number;
    completed_at?: string;
  };
  onContinue: () => void;
  onDismiss: () => void;
  onDontShowAgain: () => void;
}) => {
  // Check visibility
  if (progress.completed_at) return null;
  if (localStorage.getItem('hide_onboarding_banner')) return null;

  const stepCount = progress.completedSteps.length;
  const percentage = Math.round((stepCount / progress.totalSteps) * 100);
  const message = progress.skipped
    ? 'Finish setting up your first keyword'
    : 'Complete your setup to start tracking';

  return (
    <div
      data-testid="onboarding-banner"
      role="status"
      aria-label="Onboarding progress"
      className="bg-blue-50 p-4 rounded"
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <span data-testid="target-icon">🎯</span>
          <span data-testid="banner-message">{message}</span>
          <span data-testid="step-count">({stepCount}/{progress.totalSteps} steps)</span>
        </div>
        <button
          data-testid="close-button"
          aria-label="Dismiss"
          onClick={onDismiss}
          className="text-gray-500"
        >
          ×
        </button>
      </div>
      
      <div data-testid="progress-bar" className="mt-2">
        <div
          data-testid="progress-fill"
          className="h-2 bg-blue-500 rounded"
          style={{ width: `${percentage}%` }}
        />
        <span data-testid="progress-percentage">{percentage}%</span>
      </div>
      
      <div className="flex gap-2 mt-3">
        <button
          data-testid="continue-button"
          onClick={onContinue}
          className="bg-blue-600 text-white px-4 py-2 rounded"
        >
          Continue Setup
        </button>
        <button
          data-testid="remind-later-button"
          onClick={onDismiss}
          className="border border-gray-300 px-4 py-2 rounded"
        >
          Remind me later
        </button>
        <button
          data-testid="dont-show-again-button"
          onClick={onDontShowAgain}
          className="text-gray-500 text-sm underline"
        >
          Don't show again
        </button>
      </div>
    </div>
  );
};

describe('OnboardingBanner', () => {
  const defaultProps = {
    progress: {
      currentStep: 2,
      completedSteps: [1],
      skipped: false,
      totalSteps: 5,
    },
    onContinue: vi.fn(),
    onDismiss: vi.fn(),
    onDontShowAgain: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // OB-001: Banner Rendering
  // ============================================
  describe('Rendering', () => {
    it('OB-001-01: renders with in-progress state', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-001-02: renders with skipped state', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, skipped: true }} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
      expect(screen.getByTestId('banner-message')).toHaveTextContent(/finish setting up/i);
    });

    it('OB-001-03: does not render if completed', () => {
      render(
        <OnboardingBanner
          {...defaultProps}
          progress={{ ...defaultProps.progress, completed_at: '2026-03-19T10:00:00Z' }}
        />
      );
      expect(screen.queryByTestId('onboarding-banner')).not.toBeInTheDocument();
    });

    it('OB-001-04: has correct data-testid', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-001-05: renders below header', () => {
      // After implementation: verify positioning
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-001-06: has info styling', () => {
      render(<OnboardingBanner {...defaultProps} />);
      const banner = screen.getByTestId('onboarding-banner');
      expect(banner).toHaveClass('bg-blue-50');
    });
  });

  // ============================================
  // OB-002: Progress Display
  // ============================================
  describe('Progress Display', () => {
    it('OB-002-01: shows step count', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, completedSteps: [1, 2] }} />);
      expect(screen.getByTestId('step-count')).toHaveTextContent('2/5 steps');
    });

    it('OB-002-02: shows progress percentage', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, completedSteps: [1, 2] }} />);
      expect(screen.getByTestId('progress-percentage')).toHaveTextContent('40%');
    });

    it('OB-002-03: shows mini progress bar', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
    });

    it('OB-002-04: progress bar fills correctly', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, completedSteps: [1, 2] }} />);
      const fill = screen.getByTestId('progress-fill');
      expect(fill).toHaveStyle({ width: '40%' });
    });

    it('OB-002-05: progress updates on change', () => {
      const { rerender } = render(<OnboardingBanner {...defaultProps} completedSteps={[1]} />);
      expect(screen.getByTestId('progress-percentage')).toHaveTextContent('20%');
      
      rerender(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, completedSteps: [1, 2, 3] }} />);
      expect(screen.getByTestId('progress-percentage')).toHaveTextContent('60%');
    });
  });

  // ============================================
  // OB-003: Message Variants
  // ============================================
  describe('Message Variants', () => {
    it('OB-003-01: in-progress message', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, skipped: false }} />);
      expect(screen.getByTestId('banner-message')).toHaveTextContent(/complete your setup to start tracking/i);
    });

    it('OB-003-02: skipped message', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, skipped: true }} />);
      expect(screen.getByTestId('banner-message')).toHaveTextContent(/finish setting up your first keyword/i);
    });

    it('OB-003-03: dynamic step reference', () => {
      // After implementation: may reference current step
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('banner-message')).toBeInTheDocument();
    });

    it('OB-003-04: target icon displayed', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('target-icon')).toBeInTheDocument();
    });
  });

  // ============================================
  // OB-004: Continue Setup Button
  // ============================================
  describe('Continue Setup Button', () => {
    it('OB-004-01: button renders', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('continue-button')).toHaveTextContent('Continue Setup');
    });

    it('OB-004-02: button triggers callback', async () => {
      const onContinue = vi.fn();
      render(<OnboardingBanner {...defaultProps} onContinue={onContinue} />);
      
      fireEvent.click(screen.getByTestId('continue-button'));
      expect(onContinue).toHaveBeenCalledTimes(1);
    });

    it('OB-004-03: button opens wizard at correct step', async () => {
      // After implementation: verify wizard opens at correct step
      const onContinue = vi.fn();
      render(<OnboardingBanner {...defaultProps} onContinue={onContinue} progress={{ ...defaultProps.progress, currentStep: 3 }} />);
      
      fireEvent.click(screen.getByTestId('continue-button'));
      expect(onContinue).toHaveBeenCalled();
    });

    it('OB-004-04: button has primary styling', () => {
      render(<OnboardingBanner {...defaultProps} />);
      const button = screen.getByTestId('continue-button');
      expect(button).toHaveClass('bg-blue-600');
    });

    it('OB-004-05: button is focusable', async () => {
      const user = userEvent.setup();
      render(<OnboardingBanner {...defaultProps} />);
      
      await user.tab();
      // After implementation: verify button can receive focus
    });
  });

  // ============================================
  // OB-005: Dismiss Functionality
  // ============================================
  describe('Dismiss Functionality', () => {
    it('OB-005-01: X button renders', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('close-button')).toBeInTheDocument();
    });

    it('OB-005-02: X button triggers dismiss', async () => {
      const onDismiss = vi.fn();
      render(<OnboardingBanner {...defaultProps} onDismiss={onDismiss} />);
      
      fireEvent.click(screen.getByTestId('close-button'));
      expect(onDismiss).toHaveBeenCalledTimes(1);
    });

    it('OB-005-03: banner hides for session', async () => {
      const onDismiss = vi.fn();
      const { rerender } = render(<OnboardingBanner {...defaultProps} onDismiss={onDismiss} />);
      
      fireEvent.click(screen.getByTestId('close-button'));
      // After implementation: verify banner hidden for session
    });

    it('OB-005-04: X button has aria-label', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('close-button')).toHaveAttribute('aria-label', 'Dismiss');
    });
  });

  // ============================================
  // OB-006: Remind Me Later Button
  // ============================================
  describe('Remind Me Later Button', () => {
    it('OB-006-01: button renders', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('remind-later-button')).toHaveTextContent('Remind me later');
    });

    it('OB-006-02: button triggers dismiss', async () => {
      const onDismiss = vi.fn();
      render(<OnboardingBanner {...defaultProps} onDismiss={onDismiss} />);
      
      fireEvent.click(screen.getByTestId('remind-later-button'));
      expect(onDismiss).toHaveBeenCalled();
    });

    it('OB-006-03: button has secondary styling', () => {
      render(<OnboardingBanner {...defaultProps} />);
      const button = screen.getByTestId('remind-later-button');
      expect(button).toHaveClass('border');
    });

    it('OB-006-04: button hides for session', async () => {
      // After implementation: verify session-based hiding
    });
  });

  // ============================================
  // OB-007: Don't Show Again Option
  // ============================================
  describe("Don't Show Again Option", () => {
    it('OB-007-01: option renders', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('dont-show-again-button')).toHaveTextContent("Don't show again");
    });

    it('OB-007-02: option triggers callback', async () => {
      const onDontShowAgain = vi.fn();
      render(<OnboardingBanner {...defaultProps} onDontShowAgain={onDontShowAgain} />);
      
      fireEvent.click(screen.getByTestId('dont-show-again-button'));
      expect(onDontShowAgain).toHaveBeenCalled();
    });

    it('OB-007-03: preference persisted', async () => {
      const onDontShowAgain = vi.fn();
      render(<OnboardingBanner {...defaultProps} onDontShowAgain={onDontShowAgain} />);
      
      fireEvent.click(screen.getByTestId('dont-show-again-button'));
      // After implementation: verify localStorage set
    });

    it('OB-007-04: banner permanently hidden', async () => {
      localStorageMock.setItem('hide_onboarding_banner', 'true');
      
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.queryByTestId('onboarding-banner')).not.toBeInTheDocument();
    });

    it('OB-007-05: preference can be reset', async () => {
      localStorageMock.setItem('hide_onboarding_banner', 'true');
      localStorageMock.removeItem('hide_onboarding_banner');
      
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });
  });

  // ============================================
  // OB-008: Visibility Logic
  // ============================================
  describe('Visibility Logic', () => {
    it('OB-008-01: shows when incomplete', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-008-02: hides when complete', () => {
      render(
        <OnboardingBanner
          {...defaultProps}
          progress={{ ...defaultProps.progress, completed_at: '2026-03-19T10:00:00Z' }}
        />
      );
      expect(screen.queryByTestId('onboarding-banner')).not.toBeInTheDocument();
    });

    it('OB-008-03: hides when preference set', () => {
      localStorageMock.setItem('hide_onboarding_banner', 'true');
      
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.queryByTestId('onboarding-banner')).not.toBeInTheDocument();
    });

    it('OB-008-04: shows after preference cleared', () => {
      localStorageMock.setItem('hide_onboarding_banner', 'true');
      localStorageMock.removeItem('hide_onboarding_banner');
      
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-008-05: shows for new users', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ currentStep: 1, completedSteps: [], skipped: false, totalSteps: 5 }} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });
  });

  // ============================================
  // OB-009: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('OB-009-01: has role="status"', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('OB-009-02: has aria-label', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByLabelText('Onboarding progress')).toBeInTheDocument();
    });

    it('OB-009-03: buttons have labels', () => {
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('continue-button')).toHaveTextContent('Continue Setup');
      expect(screen.getByTestId('remind-later-button')).toHaveTextContent('Remind me later');
    });

    it('OB-009-04: focus management on dismiss', async () => {
      // After implementation: verify focus moves appropriately
    });

    it('OB-009-05: screen reader announces progress', async () => {
      // After implementation: verify progress announced
    });

    it('OB-009-06: color contrast sufficient', () => {
      // After implementation: verify WCAG AA contrast
    });
  });

  // ============================================
  // OB-010: Responsive Design
  // ============================================
  describe('Responsive Design', () => {
    it('OB-010-01: works on mobile', () => {
      window.innerWidth = 320;
      
      render(<OnboardingBanner {...defaultProps} />);
      expect(screen.getByTestId('onboarding-banner')).toBeInTheDocument();
    });

    it('OB-010-02: buttons stack on mobile', async () => {
      // After implementation: verify buttons stack on small screens
    });

    it('OB-010-03: text wraps appropriately', async () => {
      // After implementation: verify text wraps
    });

    it('OB-010-04: touch targets adequate', async () => {
      // After implementation: verify min 44x44px targets
    });
  });

  // ============================================
  // OB-011: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('OB-011-01: handles null progress', () => {
      // After implementation: handle gracefully
    });

    it('OB-011-02: handles empty completedSteps', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ ...defaultProps.progress, completedSteps: [] }} />);
      expect(screen.getByTestId('step-count')).toHaveTextContent('0/5 steps');
    });

    it('OB-011-03: handles all steps completed', () => {
      render(
        <OnboardingBanner
          {...defaultProps}
          progress={{ ...defaultProps.progress, completedSteps: [1, 2, 3, 4, 5], completed_at: '2026-03-19T10:00:00Z' }}
        />
      );
      expect(screen.queryByTestId('onboarding-banner')).not.toBeInTheDocument();
    });

    it('OB-011-04: handles step 1 (just started)', () => {
      render(<OnboardingBanner {...defaultProps} progress={{ currentStep: 1, completedSteps: [], skipped: false, totalSteps: 5 }} />);
      expect(screen.getByTestId('banner-message')).toBeInTheDocument();
    });

    it('OB-011-05: handles rapid dismiss/click', async () => {
      const onDismiss = vi.fn();
      render(<OnboardingBanner {...defaultProps} onDismiss={onDismiss} />);
      
      const closeButton = screen.getByTestId('close-button');
      fireEvent.click(closeButton);
      fireEvent.click(closeButton);
      
      // After implementation: verify only one action
    });
  });
});
