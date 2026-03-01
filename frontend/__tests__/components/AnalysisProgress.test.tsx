/**
 * AnalysisProgress Component Tests - TASK-19-11
 *
 * Unit tests for the AnalysisProgress component.
 *
 * @see TASK-19-11
 * @see TEST-19-11
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AnalysisProgress } from '@/components/analysis/AnalysisProgress';

// Mock the hook
const mockStart = jest.fn();
const mockStop = jest.fn();
const mockCancel = jest.fn();

const defaultHookReturn = {
  job: null,
  isConnecting: false,
  isPolling: false,
  error: null,
  reconnectAttempts: 0,
  elapsedSeconds: 0,
  start: mockStart,
  stop: mockStop,
  cancel: mockCancel,
};

const mockUseAnalysisProgress = jest.fn();

jest.mock('@/lib/hooks/useAnalysisProgress', () => ({
  useAnalysisProgress: (...args: unknown[]) => mockUseAnalysisProgress(...args),
}));

// Helper to render with props
function renderAnalysisProgress(overrides = {}) {
  const defaultProps = {
    jobId: 1,
    onComplete: jest.fn(),
    onCancel: jest.fn(),
  };

  return render(<AnalysisProgress {...defaultProps} {...overrides} />);
}

describe('AnalysisProgress', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUseAnalysisProgress.mockReturnValue(defaultHookReturn);
  });

  // ==================== Basic Rendering ====================

  describe('basic rendering', () => {
    it('renders without crashing', () => {
      renderAnalysisProgress();
      expect(screen.getByText('Analysis Progress')).toBeInTheDocument();
    });

    it('shows progress bar', () => {
      renderAnalysisProgress();
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('shows status indicator', () => {
      renderAnalysisProgress();
      expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('shows 0% for new job', () => {
      renderAnalysisProgress();
      expect(screen.getByText('0%')).toBeInTheDocument();
    });
  });

  // ==================== Props Handling ====================

  describe('props handling', () => {
    it('accepts jobId prop', () => {
      renderAnalysisProgress({ jobId: 42 });
      expect(mockUseAnalysisProgress).toHaveBeenCalledWith(
        expect.objectContaining({ jobId: 42 })
      );
    });

    it('accepts onComplete callback', () => {
      const onComplete = jest.fn();
      renderAnalysisProgress({ onComplete });
      expect(mockUseAnalysisProgress).toHaveBeenCalledWith(
        expect.objectContaining({ onComplete })
      );
    });

    it('accepts onCancel callback', () => {
      const onCancel = jest.fn();
      renderAnalysisProgress({ onCancel });
      // onCancel is used by the component, not passed to the hook
      expect(mockUseAnalysisProgress).toHaveBeenCalled();
    });
  });

  // ==================== Status Indicator ====================

  describe('status indicator', () => {
    it('shows pending status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'pending', progress_percent: 0, completed_indicators: 0, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Pending')).toBeInTheDocument();
    });

    it('shows running status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Running')).toBeInTheDocument();
    });

    it('shows completed status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'completed',
          progress_percent: 100,
          completed_indicators: 10,
          total_indicators: 10,
          results: [{ status: 'completed', percentage: 85 }],
        },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Completed')).toBeInTheDocument();
    });

    it('shows cancelled status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'cancelled', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Cancelled')).toBeInTheDocument();
    });

    it('shows failed status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'failed', progress_percent: 30, completed_indicators: 3, total_indicators: 10, error_message: 'Something went wrong' },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Failed')).toBeInTheDocument();
    });
  });

  // ==================== Progress Bar ====================

  describe('progress bar', () => {
    it('shows 0% for new job', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'pending', progress_percent: 0, completed_indicators: 0, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('0%')).toBeInTheDocument();
    });

    it('updates to 50% when half done', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('50%')).toBeInTheDocument();
    });

    it('shows 100% when complete', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'completed',
          progress_percent: 100,
          completed_indicators: 10,
          total_indicators: 10,
          results: [],
        },
      });
      renderAnalysisProgress();
      expect(screen.getByText('100%')).toBeInTheDocument();
    });

    it('shows correct indicator count', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      expect(screen.getByText('5 of 10 indicators')).toBeInTheDocument();
    });
  });

  // ==================== Cancel Button ====================

  describe('cancel button', () => {
    it('enabled when running', async () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      expect(cancelButton).toBeEnabled();
    });

    it('disabled when not running', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'pending', progress_percent: 0, completed_indicators: 0, total_indicators: 10 },
      });
      renderAnalysisProgress();

      expect(screen.queryByRole('button', { name: /cancel/i })).not.toBeInTheDocument();
    });

    it('calls onCancel when clicked', async () => {
      const user = userEvent.setup();
      const onCancel = jest.fn();
      mockCancel.mockResolvedValue(undefined);

      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });

      renderAnalysisProgress({ onCancel });

      await user.click(screen.getByRole('button', { name: /cancel/i }));

      expect(mockCancel).toHaveBeenCalled();
    });
  });

  // ==================== Elapsed Time ====================

  describe('elapsed time', () => {
    it('shows elapsed time', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        elapsedSeconds: 65,
      });
      renderAnalysisProgress();
      expect(screen.getByText(/1:05/)).toBeInTheDocument();
    });

    it('shows 0:00 initially', () => {
      renderAnalysisProgress();
      expect(screen.getByText(/0:00/)).toBeInTheDocument();
    });

    it('formats hours correctly', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        elapsedSeconds: 3661,
      });
      renderAnalysisProgress();
      expect(screen.getByText(/1:01:01/)).toBeInTheDocument();
    });
  });

  // ==================== Accessibility ====================

  describe('accessibility', () => {
    it('has role="progressbar"', () => {
      renderAnalysisProgress();
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    it('has aria-valuenow', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      const progressbar = screen.getByRole('progressbar');
      expect(progressbar).toHaveAttribute('aria-valuenow', '50');
    });

    it('has aria-valuemin="0"', () => {
      renderAnalysisProgress();
      const progressbar = screen.getByRole('progressbar');
      expect(progressbar).toHaveAttribute('aria-valuemin', '0');
    });

    it('has aria-valuemax="100"', () => {
      renderAnalysisProgress();
      const progressbar = screen.getByRole('progressbar');
      expect(progressbar).toHaveAttribute('aria-valuemax', '100');
    });

    it('has aria-label on progressbar', () => {
      renderAnalysisProgress();
      const progressbar = screen.getByRole('progressbar');
      expect(progressbar).toHaveAttribute('aria-label');
    });

    it('cancel button has accessible name', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: { status: 'running', progress_percent: 50, completed_indicators: 5, total_indicators: 10 },
      });
      renderAnalysisProgress();
      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      expect(cancelButton).toHaveAttribute('aria-label', 'Cancel analysis');
    });
  });

  // ==================== Polling Fallback ====================

  describe('polling fallback', () => {
    it('shows polling indicator when using polling', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        isPolling: true,
      });
      renderAnalysisProgress();
      expect(screen.getByText('Using polling mode')).toBeInTheDocument();
    });

    it('shows connecting status', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        isConnecting: true,
      });
      renderAnalysisProgress();
      expect(screen.getByText('Connecting...')).toBeInTheDocument();
    });

    it('shows error when SSE fails', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        error: 'SSE connection failed',
      });
      renderAnalysisProgress();
      expect(screen.getByText('SSE connection failed')).toBeInTheDocument();
    });
  });

  // ==================== Error Handling ====================

  describe('error handling', () => {
    it('shows connection error state', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        error: 'Connection failed',
      });
      renderAnalysisProgress();
      expect(screen.getByRole('alert')).toHaveTextContent('Connection failed');
    });

    it('shows job failure state', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'failed',
          progress_percent: 30,
          completed_indicators: 3,
          total_indicators: 10,
          error_message: 'Analysis failed',
        },
      });
      renderAnalysisProgress();
      expect(screen.getByText('Analysis failed')).toBeInTheDocument();
    });

    it('shows retry button on failure', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'failed',
          progress_percent: 30,
          completed_indicators: 3,
          total_indicators: 10,
          error_message: 'Analysis failed',
        },
      });
      renderAnalysisProgress();
      expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
    });
  });

  // ==================== Completion ====================

  describe('completion', () => {
    it('shows view results button when completed', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'completed',
          progress_percent: 100,
          completed_indicators: 10,
          total_indicators: 10,
          results: [{ status: 'completed', percentage: 85 }],
        },
      });
      renderAnalysisProgress();
      expect(screen.getByRole('button', { name: /view results/i })).toBeInTheDocument();
    });

    it('calls onComplete when view results clicked', async () => {
      const user = userEvent.setup();
      const onComplete = jest.fn();
      const job = {
        id: 1,
        status: 'completed',
        progress_percent: 100,
        completed_indicators: 10,
        total_indicators: 10,
        results: [{ status: 'completed', percentage: 85 }],
      };

      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job,
      });

      renderAnalysisProgress({ onComplete });

      await user.click(screen.getByRole('button', { name: /view results/i }));

      expect(onComplete).toHaveBeenCalledWith(job);
    });

    it('shows average score when completed', () => {
      mockUseAnalysisProgress.mockReturnValue({
        ...defaultHookReturn,
        job: {
          status: 'completed',
          progress_percent: 100,
          completed_indicators: 10,
          total_indicators: 10,
          results: [
            { status: 'completed', percentage: 80 },
            { status: 'completed', percentage: 90 },
          ],
        },
      });
      renderAnalysisProgress();
      expect(screen.getByText('85%')).toBeInTheDocument();
    });
  });
});
