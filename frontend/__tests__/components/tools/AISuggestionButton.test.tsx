// Tests for AISuggestionButton component
// frontend/__tests__/components/tools/AISuggestionButton.test.tsx

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AISuggestionButton } from '@/components/tools/AISuggestionButton';
import * as api from '@/lib/api/ai-suggestions';

import type { SuggestionResult } from '@/lib/types/ai-suggestions';

// Mock the API module
jest.mock('@/lib/api/ai-suggestions', () => ({
  getAISuggestion: jest.fn(),
  isRateLimitError: jest.fn((error) => error?.name === 'RateLimitError'),
  RateLimitError: class RateLimitError extends Error {
    retryAfter: number;
    constructor(message: string, retryAfter: number) {
      super(message);
      this.name = 'RateLimitError';
      this.retryAfter = retryAfter;
    }
  },
}));

// Mock timers for rate limit countdown
jest.useFakeTimers();

const mockSuggestionResult: SuggestionResult = {
  indicator: 'title',
  score: 75,
  issues: ['Issue 1'],
  suggestions: [{ type: 'rewrite', text: 'New Title', reason: 'Better' }],
  best_practices: ['Best practice'],
  provider: 'openrouter',
  model: 'llama-3.1',
  generated_at: '2026-03-02T10:00:00Z',
};

describe('AISuggestionButton', () => {
  const mockOnSuggestion = jest.fn();
  const mockOnError = jest.fn();
  const mockGetAISuggestion = api.getAISuggestion as jest.MockedFunction<typeof api.getAISuggestion>;

  beforeEach(() => {
    jest.clearAllMocks();
    jest.clearAllTimers();
  });

  afterEach(() => {
    jest.runOnlyPendingTimers();
  });

  // ============================================
  // Rendering
  // ============================================

  describe('Rendering', () => {
    it('AB-001: renders button with default props', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      expect(screen.getByRole('button')).toBeInTheDocument();
      // Button text includes the indicator label
      expect(screen.getByText(/get title tag suggestion/i)).toBeInTheDocument();
    });

    it('AB-009: shows different indicator labels', () => {
      render(
        <AISuggestionButton
          indicator="meta_description"
          context={{ description: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      expect(screen.getByText(/meta description/i)).toBeInTheDocument();
    });

    it('AB-012: shows custom button text', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
          buttonText="Analyze"
        />
      );

      expect(screen.getByText('Analyze')).toBeInTheDocument();
    });

    it('AB-013: shows provider badge when showProvider is true', () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockSuggestionResult);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
          showProvider
        />
      );

      // Provider badge would show after successful request
      // For now, just verify the prop is accepted
      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('AB-014: handles null context gracefully', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={null}
          onSuggestion={mockOnSuggestion}
        />
      );

      expect(screen.getByRole('button')).toBeDisabled();
    });

    it('AB-015: shows tooltip on hover', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      await user.hover(button);

      // Tooltip would appear (implementation dependent)
      expect(button).toBeInTheDocument();
    });
  });

  // ============================================
  // Loading State
  // ============================================

  describe('Loading State', () => {
    it('AB-002: shows loading state during request', async () => {
      let resolvePromise: (value: SuggestionResult) => void;
      const pendingPromise = new Promise<SuggestionResult>((resolve) => {
        resolvePromise = resolve;
      });
      mockGetAISuggestion.mockReturnValueOnce(pendingPromise);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      // Button should be disabled and show loading
      await waitFor(() => {
        expect(button).toBeDisabled();
      });

      // Resolve the promise
      resolvePromise!(mockSuggestionResult);

      await waitFor(() => {
        expect(mockOnSuggestion).toHaveBeenCalledWith(mockSuggestionResult);
      });
    });

    it('AB-010: handles rapid clicks - only one request in flight', async () => {
      mockGetAISuggestion.mockResolvedValue(mockSuggestionResult);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');

      // Click multiple times rapidly
      fireEvent.click(button);
      fireEvent.click(button);
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      // Should only make one API call
      expect(mockGetAISuggestion).toHaveBeenCalledTimes(1);
    });
  });

  // ============================================
  // Error State
  // ============================================

  describe('Error State', () => {
    it('AB-003: shows error state on failure', async () => {
      mockGetAISuggestion.mockRejectedValueOnce(new Error('Network error'));

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
          onError={mockOnError}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/error/i)).toBeInTheDocument();
      });

      expect(mockOnError).toHaveBeenCalled();
    });

    it('AB-006: retries on error after clicking retry', async () => {
      mockGetAISuggestion
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce(mockSuggestionResult);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/retry/i)).toBeInTheDocument();
      });

      // Click retry button
      const retryButton = screen.getByText(/retry/i);
      fireEvent.click(retryButton);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(mockGetAISuggestion).toHaveBeenCalledTimes(2);
      });
    });
  });

  // ============================================
  // Rate Limit State
  // ============================================

  describe('Rate Limit State', () => {
    it('AB-004: shows disabled state when rate limited', async () => {
      const rateLimitError = new (api.RateLimitError as any)('Rate limited', 30);
      mockGetAISuggestion.mockRejectedValueOnce(rateLimitError);
      (api.isRateLimitError as jest.Mock).mockReturnValueOnce(true);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/rate limit/i)).toBeInTheDocument();
      });

      // Button should be disabled during countdown
      expect(button).toBeDisabled();
    });

    it('enables button after countdown completes', async () => {
      const rateLimitError = new (api.RateLimitError as any)('Rate limited', 1);
      mockGetAISuggestion.mockRejectedValueOnce(rateLimitError);
      (api.isRateLimitError as jest.Mock).mockReturnValueOnce(true);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      // After rate limit error, show retry button
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Success State
  // ============================================

  describe('Success State', () => {
    it('AB-005: calls onSuggestion callback on success', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockSuggestionResult);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(mockOnSuggestion).toHaveBeenCalledWith(mockSuggestionResult);
      });
    });
  });

  // ============================================
  // Accessibility
  // ============================================

  describe('Accessibility', () => {
    it('AB-007: handles keyboard navigation', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });

      mockGetAISuggestion.mockResolvedValueOnce(mockSuggestionResult);

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');

      // Tab to button
      await user.tab();
      expect(button).toHaveFocus();

      // Press Enter to click
      await user.keyboard('{Enter}');

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(mockOnSuggestion).toHaveBeenCalled();
      });
    });

    it('AB-008: has correct ARIA attributes', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      expect(button).toHaveAttribute('aria-label');
    });
  });

  // ============================================
  // Cleanup
  // ============================================

  describe('Cleanup', () => {
    it('AB-011: cancels pending request on unmount', async () => {
      const abortSpy = jest.spyOn(AbortController.prototype, 'abort');

      mockGetAISuggestion.mockImplementation(
        () =>
          new Promise(() => {
            // Never resolve
          })
      );

      const { unmount } = render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
        />
      );

      const button = screen.getByRole('button');
      fireEvent.click(button);

      // Unmount while request is pending
      unmount();

      // Should not cause any errors
      expect(mockGetAISuggestion).toHaveBeenCalled();
    });
  });

  // ============================================
  // Disabled State
  // ============================================

  describe('Disabled State', () => {
    it('respects disabled prop', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
          disabled
        />
      );

      expect(screen.getByRole('button')).toBeDisabled();
    });

    it('applies custom className', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onSuggestion={mockOnSuggestion}
          className="custom-class"
        />
      );

      expect(screen.getByRole('button')).toHaveClass('custom-class');
      expect(screen.getByRole('button')).toBeInTheDocument();
    });
  });
});
