/**
 * Skip Functionality Tests
 *
 * Tests for the skip functionality that allows users to skip
 * the onboarding wizard or individual steps.
 *
 * @see TEST-17-11.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock analytics
const mockTrackEvent = vi.fn();
vi.mock('@/lib/analytics', () => ({
  trackEvent: (...args: unknown[]) => mockTrackEvent(...args),
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  X: () => <span data-testid="x-icon">×</span>,
  AlertCircle: () => <span data-testid="alert-icon">!</span>,
  ArrowRight: () => <span data-testid="arrow-right-icon">→</span>,
}));

// Mock OnboardingContext
const mockSkipOnboarding = vi.fn();
const mockCloseModal = vi.fn();
const mockSetCurrentStep = vi.fn();

vi.mock('@/contexts/OnboardingContext', () => ({
  useOnboarding: () => ({
    skipOnboarding: mockSkipOnboarding,
    closeModal: mockCloseModal,
    setCurrentStep: mockSetCurrentStep,
    currentStep: 2,
  }),
}));

// Mock component implementation for TDD
const SkipLink = ({
  onSkip,
  stepNumber,
}: {
  onSkip: () => void;
  stepNumber: number;
}) => {
  return (
    <button
      role="button"
      aria-label="Skip onboarding"
      className="text-gray-500 underline text-sm"
      onClick={onSkip}
      data-testid="skip-link"
    >
      Skip for now
    </button>
  );
};

const ConfirmDialog = ({
  isOpen,
  title,
  message,
  confirmText,
  cancelText,
  onConfirm,
  onCancel,
}: {
  isOpen: boolean;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  onConfirm: () => void;
  onCancel: () => void;
}) => {
  if (!isOpen) return null;

  return (
    <div
      role="alertdialog"
      aria-labelledby="dialog-title"
      aria-describedby="dialog-message"
      data-testid="confirm-dialog"
    >
      <h2 id="dialog-title">{title}</h2>
      <p id="dialog-message">{message}</p>
      <button onClick={onConfirm} data-testid="confirm-button">
        {confirmText}
      </button>
      <button onClick={onCancel} data-testid="cancel-button">
        {cancelText}
      </button>
    </div>
  );
};

const ResumeBanner = ({
  onAction,
  onDismiss,
}: {
  onAction: () => void;
  onDismiss: () => void;
}) => {
  return (
    <div data-testid="resume-banner" className="bg-blue-50 p-4 rounded">
      <p>Complete your setup to get the most out of SEOIndicator.</p>
      <button onClick={onAction} data-testid="resume-button">
        Complete setup
      </button>
      <button onClick={onDismiss} data-testid="dismiss-button" aria-label="Dismiss">
        ×
      </button>
    </div>
  );
};

describe('Skip Functionality', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // SF-001: Skip Link Rendering
  // ============================================
  describe('Skip Link Rendering', () => {
    it('SF-001-01: skip link renders on each step', () => {
      // Test for each step 1-4
      [1, 2, 3, 4].forEach((step) => {
        const { unmount } = render(<SkipLink onSkip={() => {}} stepNumber={step} />);
        expect(screen.getByTestId('skip-link')).toBeInTheDocument();
        unmount();
      });
    });

    it('SF-001-02: skip link styled as subtle link', () => {
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      const link = screen.getByTestId('skip-link');
      expect(link).toHaveClass('text-gray-500');
      expect(link).toHaveClass('underline');
    });

    it('SF-001-03: skip link has accessible name', () => {
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      expect(screen.getByLabelText('Skip onboarding')).toBeInTheDocument();
    });

    it('SF-001-04: skip link has button role', () => {
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      expect(screen.getByRole('button', { name: /skip/i })).toBeInTheDocument();
    });

    it('SF-001-05: skip link is keyboard accessible', async () => {
      const user = userEvent.setup();
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      
      await user.tab();
      expect(screen.getByTestId('skip-link')).toHaveFocus();
    });

    it('SF-001-06: skip link text is correct', () => {
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      expect(screen.getByText('Skip for now')).toBeInTheDocument();
    });
  });

  // ============================================
  // SF-002: Skip Confirmation Dialog
  // ============================================
  describe('Skip Confirmation Dialog', () => {
    const dialogProps = {
      isOpen: true,
      title: 'Skip onboarding?',
      message: 'You can always complete it later from the dashboard.',
      confirmText: 'Skip',
      cancelText: 'Continue Setup',
      onConfirm: vi.fn(),
      onCancel: vi.fn(),
    };

    it('SF-002-01: dialog appears on skip click', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByTestId('confirm-dialog')).toBeInTheDocument();
    });

    it('SF-002-02: dialog has correct title', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByText('Skip onboarding?')).toBeInTheDocument();
    });

    it('SF-002-03: dialog has correct message', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByText(/you can always complete it later/i)).toBeInTheDocument();
    });

    it('SF-002-04: dialog has Skip button', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByTestId('confirm-button')).toHaveTextContent('Skip');
    });

    it('SF-002-05: dialog has Continue Setup button', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByTestId('cancel-button')).toHaveTextContent('Continue Setup');
    });

    it('SF-002-06: cancel closes dialog', async () => {
      render(<ConfirmDialog {...dialogProps} />);
      
      fireEvent.click(screen.getByTestId('cancel-button'));
      expect(dialogProps.onCancel).toHaveBeenCalled();
    });

    it('SF-002-07: dialog has modal role', () => {
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    });

    it('SF-002-08: dialog traps focus', async () => {
      // After implementation: verify focus trap
      render(<ConfirmDialog {...dialogProps} />);
      expect(screen.getByTestId('confirm-dialog')).toBeInTheDocument();
    });

    it('SF-002-09: escape key closes dialog', async () => {
      const user = userEvent.setup();
      render(<ConfirmDialog {...dialogProps} />);
      
      await user.keyboard('{Escape}');
      // After implementation: verify dialog closes
    });

    it('SF-002-10: click outside closes dialog', async () => {
      // After implementation: click on overlay
      render(<ConfirmDialog {...dialogProps} />);
      // Verify dialog closes
    });
  });

  // ============================================
  // SF-003: Skip API Call
  // ============================================
  describe('Skip API Call', () => {
    it('SF-003-01: API called on confirm', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: trigger skip and verify API call
      // expect(mockFetch).toHaveBeenCalledWith('/api/v1/onboarding/skip', expect.objectContaining({ method: 'POST' }));
    });

    it('SF-003-02: authorization header sent', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: verify Authorization header
    });

    it('SF-003-03: skipped_at timestamp returned', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: verify timestamp in response
    });

    it('SF-003-04: error handling for API failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      // After implementation: verify error handling
    });

    it('SF-003-05: loading state during API call', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      // After implementation: verify loading state
    });
  });

  // ============================================
  // SF-004: Modal Close on Skip
  // ============================================
  describe('Modal Close on Skip', () => {
    it('SF-004-01: modal closes after skip', async () => {
      mockSkipOnboarding.mockResolvedValue(undefined);

      // After implementation: trigger skip and verify modal closes
      // expect(mockCloseModal).toHaveBeenCalled();
    });

    it('SF-004-02: focus moves to dashboard', async () => {
      // After implementation: verify focus moves to dashboard element
    });

    it('SF-004-03: animation on close', async () => {
      // After implementation: verify fade animation
    });
  });

  // ============================================
  // SF-005: Resume Banner
  // ============================================
  describe('Resume Banner', () => {
    it('SF-005-01: banner appears after skip', () => {
      // After implementation: render dashboard with skipped state
      // expect(screen.getByTestId('resume-banner')).toBeInTheDocument();
    });

    it('SF-005-02: banner has resume button', () => {
      const onAction = vi.fn();
      render(<ResumeBanner onAction={onAction} onDismiss={() => {}} />);
      
      expect(screen.getByTestId('resume-button')).toHaveTextContent('Complete setup');
    });

    it('SF-005-03: banner can be dismissed', async () => {
      const onDismiss = vi.fn();
      render(<ResumeBanner onAction={() => {}} onDismiss={onDismiss} />);
      
      fireEvent.click(screen.getByTestId('dismiss-button'));
      expect(onDismiss).toHaveBeenCalled();
    });

    it('SF-005-04: banner reopens onboarding', async () => {
      const onAction = vi.fn();
      render(<ResumeBanner onAction={onAction} onDismiss={() => {}} />);
      
      fireEvent.click(screen.getByTestId('resume-button'));
      expect(onAction).toHaveBeenCalled();
    });

    it('SF-005-05: banner respects dismissed state', async () => {
      // After implementation: test session-based dismissal
    });
  });

  // ============================================
  // SF-006: Per-Step Skip
  // ============================================
  describe('Per-Step Skip', () => {
    it('SF-006-01: skip individual step advances to next', async () => {
      // After implementation: skip step 2, verify advances to step 3
      // expect(mockSetCurrentStep).toHaveBeenCalledWith(3);
    });

    it('SF-006-02: skipped step marked complete', async () => {
      // After implementation: verify step 2 shows as completed
    });

    it('SF-006-03: skip with data preserved', async () => {
      // After implementation: skip step 3, verify step 1-2 data retained
    });

    it('SF-006-04: all steps skippable', () => {
      // After implementation: verify skip link on steps 1-4
    });
  });

  // ============================================
  // SF-007: Analytics Events
  // ============================================
  describe('Analytics Events', () => {
    it('SF-007-01: event fired on skip', async () => {
      // After implementation: trigger skip
      // expect(mockTrackEvent).toHaveBeenCalledWith('onboarding_skipped', expect.anything());
    });

    it('SF-007-02: event includes step number', async () => {
      // After implementation: skip from step 2
      // expect(mockTrackEvent).toHaveBeenCalledWith('onboarding_skipped', { step: 2, ... });
    });

    it('SF-007-03: event includes timestamp', async () => {
      // After implementation: verify timestamp in event
    });

    it('SF-007-04: event includes user ID', async () => {
      // After implementation: verify user ID in event
    });
  });

  // ============================================
  // SF-008: Backend Skip Handling
  // ============================================
  describe('Backend Skip Handling', () => {
    // These would be backend tests, but we can test the API contract
    
    it('SF-008-01: sets skipped_at timestamp', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: verify response
    });

    it('SF-008-02: returns success response', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: verify 200 response
    });

    it('SF-008-03: validates user is authenticated', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 401,
      });

      // After implementation: verify 401 handling
    });

    it('SF-008-04: idempotent operation', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { skipped_at: '2026-03-19T10:00:00Z' } }),
      });

      // After implementation: call skip twice, verify same result
    });

    it('SF-008-05: updates onboarding status', async () => {
      // After implementation: verify status set to 'skipped'
    });
  });

  // ============================================
  // SF-009: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('SF-009-01: skip link has aria-label', () => {
      render(<SkipLink onSkip={() => {}} stepNumber={1} />);
      expect(screen.getByLabelText('Skip onboarding')).toBeInTheDocument();
    });

    it('SF-009-02: dialog has aria-labelledby', () => {
      render(<ConfirmDialog
        isOpen={true}
        title="Skip onboarding?"
        message="Test"
        confirmText="Skip"
        cancelText="Cancel"
        onConfirm={() => {}}
        onCancel={() => {}}
      />);
      
      expect(screen.getByRole('alertdialog')).toHaveAttribute('aria-labelledby');
    });

    it('SF-009-03: dialog has aria-describedby', () => {
      render(<ConfirmDialog
        isOpen={true}
        title="Skip onboarding?"
        message="Test"
        confirmText="Skip"
        cancelText="Cancel"
        onConfirm={() => {}}
        onCancel={() => {}}
      />);
      
      expect(screen.getByRole('alertdialog')).toHaveAttribute('aria-describedby');
    });

    it('SF-009-04: focus management on dialog open', async () => {
      // After implementation: verify focus moves to confirm button
    });

    it('SF-009-05: focus restored on dialog close', async () => {
      // After implementation: verify focus returns to skip link
    });

    it('SF-009-06: screen reader announces dialog', async () => {
      // After implementation: verify dialog content announced
    });
  });

  // ============================================
  // SF-010: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('SF-010-01: skip during API call prevents double', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      // After implementation: click skip twice, verify only one API call
    });

    it('SF-010-02: skip with pending data', async () => {
      // After implementation: skip with unsaved form data
      // Data should not be saved (expected behavior)
    });

    it('SF-010-03: network error during skip', async () => {
      mockFetch.mockRejectedValue(new Error('Network timeout'));

      // After implementation: verify retry option shown
    });

    it('SF-010-04: skip on last step', async () => {
      // After implementation: skip step 4 should complete wizard
    });

    it('SF-010-05: resume from skipped state', async () => {
      // After implementation: verify resume starts from step 1
    });
  });
});
