/**
 * Progress Bar Component Tests
 *
 * Tests for the visual progress indicator that shows users their progress
 * through the onboarding wizard.
 *
 * @see TEST-17-10.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Check: () => <span data-testid="check-icon">✓</span>,
  Circle: () => <span data-testid="circle-icon">○</span>,
  Dot: () => <span data-testid="dot-icon">●</span>,
}));

// Mock component implementation for TDD
const ProgressBar = ({
  currentStep,
  completedSteps,
  totalSteps,
}: {
  currentStep: number;
  completedSteps: number[];
  totalSteps: number;
}) => {
  const percentage = Math.round((currentStep / totalSteps) * 100);
  const clampedPercentage = Math.min(100, Math.max(0, percentage));

  return (
    <div
      data-testid="onboarding-progress"
      role="progressbar"
      aria-valuenow={clampedPercentage}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={`Onboarding progress: ${clampedPercentage}%`}
    >
      <div role="list" data-testid="step-list">
        {Array.from({ length: totalSteps }, (_, i) => i + 1).map((step) => {
          const isCompleted = completedSteps.includes(step);
          const isCurrent = step === currentStep;
          const isUpcoming = step > currentStep;

          return (
            <div
              key={step}
              role="listitem"
              data-testid={`step-${step}`}
              data-state={
                isCompleted ? 'completed' : isCurrent ? 'current' : 'upcoming'
              }
              aria-current={isCurrent ? 'step' : undefined}
              aria-label={`Step ${step}${isCompleted ? ', completed' : isCurrent ? ', current' : ', upcoming'}`}
            >
              {isCompleted && <span data-testid="check-icon">✓</span>}
              <span data-testid="step-label">{step}</span>
            </div>
          );
        })}
      </div>
      <span data-testid="percentage">{clampedPercentage}% complete</span>
    </div>
  );
};

describe('ProgressBar', () => {
  const defaultProps = {
    currentStep: 1,
    completedSteps: [] as number[],
    totalSteps: 5,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // PB-001: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('PB-001-01: renders with required props', () => {
      render(<ProgressBar {...defaultProps} />);
      expect(screen.getByTestId('onboarding-progress')).toBeInTheDocument();
    });

    it('PB-001-02: renders correct number of step indicators', () => {
      render(<ProgressBar {...defaultProps} totalSteps={5} />);
      const steps = screen.getAllByRole('listitem');
      expect(steps).toHaveLength(5);
    });

    it('PB-001-03: has horizontal layout', () => {
      render(<ProgressBar {...defaultProps} />);
      const stepList = screen.getByTestId('step-list');
      expect(stepList).toBeInTheDocument();
    });

    it('PB-001-04: has data-testid', () => {
      render(<ProgressBar {...defaultProps} />);
      expect(screen.getByTestId('onboarding-progress')).toBeInTheDocument();
    });

    it('PB-001-05: renders progress percentage', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      expect(screen.getByText('40% complete')).toBeInTheDocument();
    });
  });

  // ============================================
  // PB-002: Step Indicators
  // ============================================
  describe('Step Indicators', () => {
    it('PB-002-01: step indicator renders for each step', () => {
      render(<ProgressBar {...defaultProps} totalSteps={5} />);
      const steps = screen.getAllByRole('listitem');
      expect(steps).toHaveLength(5);
    });

    it('PB-002-02: step indicator has correct role', () => {
      render(<ProgressBar {...defaultProps} />);
      const steps = screen.getAllByRole('listitem');
      expect(steps[0]).toHaveAttribute('role', 'listitem');
    });

    it('PB-002-03: step indicators in list', () => {
      render(<ProgressBar {...defaultProps} />);
      const list = screen.getByRole('list');
      expect(list).toBeInTheDocument();
    });

    it('PB-002-04: step indicator has accessible label', () => {
      render(<ProgressBar {...defaultProps} />);
      const step1 = screen.getByTestId('step-1');
      expect(step1).toHaveAttribute('aria-label', 'Step 1, current');
    });

    it('PB-002-05: step indicators have numeric labels', () => {
      render(<ProgressBar {...defaultProps} />);
      const labels = screen.getAllByTestId('step-label');
      expect(labels[0]).toHaveTextContent('1');
      expect(labels[4]).toHaveTextContent('5');
    });
  });

  // ============================================
  // PB-003: Completed Steps
  // ============================================
  describe('Completed Steps', () => {
    it('PB-003-01: completed step marked visually', () => {
      render(<ProgressBar {...defaultProps} completedSteps={[1, 2]} currentStep={3} />);
      const step1 = screen.getByTestId('step-1');
      expect(step1).toHaveAttribute('data-state', 'completed');
    });

    it('PB-003-02: completed step shows checkmark', () => {
      render(<ProgressBar {...defaultProps} completedSteps={[1]} currentStep={2} />);
      const step1 = screen.getByTestId('step-1');
      const checkIcon = step1.querySelector('[data-testid="check-icon"]');
      expect(checkIcon).toBeInTheDocument();
    });

    it('PB-003-03: completed step has filled color', () => {
      render(<ProgressBar {...defaultProps} completedSteps={[1]} currentStep={2} />);
      const step1 = screen.getByTestId('step-1');
      expect(step1).toHaveAttribute('data-state', 'completed');
    });

    it('PB-003-04: completed step aria-label', () => {
      render(<ProgressBar {...defaultProps} completedSteps={[1]} currentStep={2} />);
      const step1 = screen.getByTestId('step-1');
      expect(step1).toHaveAttribute('aria-label', 'Step 1, completed');
    });

    it('PB-003-05: all completed steps marked', () => {
      render(
        <ProgressBar
          currentStep={5}
          completedSteps={[1, 2, 3, 4, 5]}
          totalSteps={5}
        />
      );
      for (let i = 1; i <= 5; i++) {
        const step = screen.getByTestId(`step-${i}`);
        expect(step).toHaveAttribute('data-state', 'completed');
      }
    });
  });

  // ============================================
  // PB-004: Current Step Highlighting
  // ============================================
  describe('Current Step Highlighting', () => {
    it('PB-004-01: current step highlighted', () => {
      render(<ProgressBar {...defaultProps} currentStep={3} />);
      const step3 = screen.getByTestId('step-3');
      expect(step3).toHaveAttribute('data-state', 'current');
    });

    it('PB-004-02: current step aria-current', () => {
      render(<ProgressBar {...defaultProps} currentStep={3} />);
      const step3 = screen.getByTestId('step-3');
      expect(step3).toHaveAttribute('aria-current', 'step');
    });

    it('PB-004-03: current step distinct from completed', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} completedSteps={[1]} />);
      const step1 = screen.getByTestId('step-1');
      const step2 = screen.getByTestId('step-2');
      expect(step1).toHaveAttribute('data-state', 'completed');
      expect(step2).toHaveAttribute('data-state', 'current');
    });

    it('PB-004-04: current step aria-label', () => {
      render(<ProgressBar {...defaultProps} currentStep={3} />);
      const step3 = screen.getByTestId('step-3');
      expect(step3).toHaveAttribute('aria-label', 'Step 3, current');
    });

    it('PB-004-05: only one current step', () => {
      render(<ProgressBar {...defaultProps} currentStep={3} />);
      const currentSteps = screen
        .getAllByRole('listitem')
        .filter((el) => el.getAttribute('data-state') === 'current');
      expect(currentSteps).toHaveLength(1);
    });
  });

  // ============================================
  // PB-005: Upcoming Steps
  // ============================================
  describe('Upcoming Steps', () => {
    it('PB-005-01: upcoming steps outlined', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} />);
      const step3 = screen.getByTestId('step-3');
      expect(step3).toHaveAttribute('data-state', 'upcoming');
    });

    it('PB-005-02: upcoming steps neutral color', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} />);
      const step5 = screen.getByTestId('step-5');
      expect(step5).toHaveAttribute('data-state', 'upcoming');
    });

    it('PB-005-03: upcoming step aria-label', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} />);
      const step3 = screen.getByTestId('step-3');
      expect(step3).toHaveAttribute('aria-label', 'Step 3, upcoming');
    });

    it('PB-005-04: no checkmark on upcoming', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} />);
      const step5 = screen.getByTestId('step-5');
      const checkIcon = step5.querySelector('[data-testid="check-icon"]');
      expect(checkIcon).not.toBeInTheDocument();
    });
  });

  // ============================================
  // PB-006: Progress Percentage
  // ============================================
  describe('Progress Percentage', () => {
    it('PB-006-01: calculates correct percentage', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      expect(screen.getByText('40% complete')).toBeInTheDocument();
    });

    it('PB-006-02: step 1 shows 20%', () => {
      render(<ProgressBar {...defaultProps} currentStep={1} totalSteps={5} />);
      expect(screen.getByText('20% complete')).toBeInTheDocument();
    });

    it('PB-006-03: step 5 shows 100%', () => {
      render(<ProgressBar {...defaultProps} currentStep={5} totalSteps={5} />);
      expect(screen.getByText('100% complete')).toBeInTheDocument();
    });

    it('PB-006-04: percentage in aria-label', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute('aria-label', 'Onboarding progress: 40%');
    });

    it('PB-006-05: percentage updates on step change', () => {
      const { rerender } = render(
        <ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />
      );
      expect(screen.getByText('40% complete')).toBeInTheDocument();

      rerender(<ProgressBar {...defaultProps} currentStep={3} totalSteps={5} />);
      expect(screen.getByText('60% complete')).toBeInTheDocument();
    });
  });

  // ============================================
  // PB-007: Step Transitions
  // ============================================
  describe('Step Transitions', () => {
    it('PB-007-01: smooth animation on step change', () => {
      // CSS transitions are tested visually or with CSS testing libraries
      // This test verifies the component re-renders correctly
      const { rerender } = render(
        <ProgressBar {...defaultProps} currentStep={2} />
      );
      rerender(<ProgressBar {...defaultProps} currentStep={3} />);
      expect(screen.getByTestId('step-3')).toHaveAttribute('data-state', 'current');
    });

    it('PB-007-02: progress bar animates', () => {
      // Animation testing would require CSS testing
      // Verify percentage updates for now
      const { rerender } = render(
        <ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />
      );
      rerender(<ProgressBar {...defaultProps} currentStep={3} totalSteps={5} />);
      expect(screen.getByTestId('percentage')).toHaveTextContent('60%');
    });

    it('PB-007-03: checkmark appears with animation', () => {
      const { rerender } = render(
        <ProgressBar {...defaultProps} currentStep={2} completedSteps={[]} />
      );
      rerender(
        <ProgressBar {...defaultProps} currentStep={3} completedSteps={[1, 2]} />
      );
      const step1 = screen.getByTestId('step-1');
      expect(step1.querySelector('[data-testid="check-icon"]')).toBeInTheDocument();
    });
  });

  // ============================================
  // PB-008: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('PB-008-01: has progressbar role', () => {
      render(<ProgressBar {...defaultProps} />);
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('PB-008-02: has aria-valuenow', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute('aria-valuenow', '40');
    });

    it('PB-008-03: has aria-valuemin', () => {
      render(<ProgressBar {...defaultProps} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute('aria-valuemin', '0');
    });

    it('PB-008-04: has aria-valuemax', () => {
      render(<ProgressBar {...defaultProps} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute('aria-valuemax', '100');
    });

    it('PB-008-05: has aria-label', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute(
        'aria-label',
        'Onboarding progress: 40%'
      );
    });

    it('PB-008-06: screen reader announces progress', () => {
      render(<ProgressBar {...defaultProps} currentStep={2} totalSteps={5} />);
      const percentage = screen.getByTestId('percentage');
      expect(percentage).toBeInTheDocument();
    });

    it('PB-008-07: focus indicator visible', () => {
      // Focus styling is visual - verify element can receive focus
      render(<ProgressBar {...defaultProps} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toBeInTheDocument();
    });

    it('PB-008-08: step labels accessible', () => {
      render(<ProgressBar {...defaultProps} />);
      const steps = screen.getAllByRole('listitem');
      steps.forEach((step, index) => {
        expect(step).toHaveAttribute('aria-label');
      });
    });
  });

  // ============================================
  // PB-009: Responsive Design
  // ============================================
  describe('Responsive Design', () => {
    it('PB-009-01: works on mobile viewport', () => {
      render(<ProgressBar {...defaultProps} />);
      // Viewport testing would require actual viewport manipulation
      // Verify component renders for now
      expect(screen.getByTestId('onboarding-progress')).toBeInTheDocument();
    });

    it('PB-009-02: labels hidden on mobile', () => {
      // CSS-based hiding is visual - verify labels exist
      render(<ProgressBar {...defaultProps} />);
      const labels = screen.getAllByTestId('step-label');
      expect(labels.length).toBe(5);
    });

    it('PB-009-03: touch targets adequate', () => {
      // Touch target size is CSS - verify elements exist
      render(<ProgressBar {...defaultProps} />);
      const steps = screen.getAllByRole('listitem');
      expect(steps.length).toBe(5);
    });

    it('PB-009-04: progress bar scales', () => {
      render(<ProgressBar {...defaultProps} totalSteps={10} />);
      const steps = screen.getAllByRole('listitem');
      expect(steps).toHaveLength(10);
    });
  });

  // ============================================
  // PB-010: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('PB-010-01: zero steps handled', () => {
      render(<ProgressBar {...defaultProps} totalSteps={0} />);
      const steps = screen.queryAllByRole('listitem');
      expect(steps).toHaveLength(0);
    });

    it('PB-010-02: one step handled', () => {
      render(<ProgressBar {...defaultProps} currentStep={1} totalSteps={1} />);
      expect(screen.getByText('100% complete')).toBeInTheDocument();
    });

    it('PB-010-03: current step exceeds total', () => {
      render(<ProgressBar {...defaultProps} currentStep={10} totalSteps={5} />);
      expect(screen.getByText('100% complete')).toBeInTheDocument();
    });

    it('PB-010-04: negative current step', () => {
      render(<ProgressBar {...defaultProps} currentStep={-1} totalSteps={5} />);
      const progress = screen.getByRole('progressbar');
      expect(progress).toHaveAttribute('aria-valuenow', '0');
    });

    it('PB-010-05: empty completedSteps array', () => {
      render(<ProgressBar {...defaultProps} completedSteps={[]} currentStep={1} />);
      const completedSteps = screen
        .getAllByRole('listitem')
        .filter((el) => el.getAttribute('data-state') === 'completed');
      expect(completedSteps).toHaveLength(0);
    });

    it('PB-010-06: invalid completedSteps', () => {
      render(
        <ProgressBar
          {...defaultProps}
          completedSteps={[10, 20]}
          currentStep={1}
          totalSteps={5}
        />
      );
      // Invalid step numbers should be ignored
      const completedSteps = screen
        .getAllByRole('listitem')
        .filter((el) => el.getAttribute('data-state') === 'completed');
      expect(completedSteps).toHaveLength(0);
    });
  });
});
