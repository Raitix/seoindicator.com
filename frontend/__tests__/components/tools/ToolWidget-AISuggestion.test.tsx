/**
 * ToolWidget AI Suggestion Integration Tests
 * Task: TASK-21-11 - Frontend Integration with Tools
 *
 * Tests the AI suggestion button and panel integration within ToolWidget.
 * Verifies the complete flow from clicking the button to displaying suggestions.
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { ToolWidget } from '@/components/tools/ToolWidget';

// Mock the useToolAnalysis hook
const mockAnalyze = jest.fn();
const mockReset = jest.fn();
const mockRetry = jest.fn();

jest.mock('@/hooks/useToolAnalysis', () => ({
  useToolAnalysis: jest.fn(() => ({
    state: 'idle',
    result: null,
    error: null,
    validationError: null,
    isLoading: false,
    analyze: mockAnalyze,
    reset: mockReset,
    retry: mockRetry,
  })),
  ERROR_MESSAGES: {
    INVALID_URL: 'Please enter a valid URL (e.g., https://example.com)',
    FETCH_FAILED: 'Could not access the page. Please check the URL and try again.',
    TIMEOUT: 'Analysis took too long. Please try again.',
    RATE_LIMITED: 'You\'ve made too many requests. Please wait a moment.',
    NETWORK_ERROR: 'Network error. Please check your connection.',
    UNKNOWN: 'An unexpected error occurred. Please try again.',
  },
}));

// Mock validateUrl
jest.mock('@/lib/utils/urlValidation', () => ({
  validateUrl: jest.fn((url: string) => {
    if (!url || !url.trim()) {
      return { valid: false, error: 'Please enter a URL' };
    }
    if (url.startsWith('javascript:') || url.startsWith('data:')) {
      return { valid: false, error: 'Please enter a valid URL (e.g., https://example.com)' };
    }
    try {
      new URL(url.startsWith('http') ? url : `https://${url}`);
      return { valid: true, normalized: url.startsWith('http') ? url : `https://${url}` };
    } catch {
      return { valid: false, error: 'Please enter a valid URL (e.g., https://example.com)' };
    }
  }),
}));

// Mock AI Suggestions API
const mockGetAISuggestion = jest.fn();
jest.mock('@/lib/api/ai-suggestions', () => ({
  getAISuggestion: (...args: unknown[]) => mockGetAISuggestion(...args),
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

import { useToolAnalysis } from '@/hooks/useToolAnalysis';

const mockUseToolAnalysis = useToolAnalysis as jest.MockedFunction<typeof useToolAnalysis>;

// Mock clipboard API
const mockClipboard = {
  writeText: jest.fn().mockResolvedValue(undefined),
};
Object.assign(navigator, { clipboard: mockClipboard });

// Mock timers
jest.useFakeTimers();

// Sample AI suggestion responses
const mockTitleSuggestion = {
  indicator: 'title',
  score: 75,
  issues: ['Title is too generic', 'Missing target keyword'],
  suggestions: [
    {
      type: 'rewrite' as const,
      text: 'Best SEO Tools 2026 | Free Website Analyzer',
      reason: 'Includes target keyword and year for freshness',
    },
  ],
  best_practices: ['Keep title under 60 characters', 'Place primary keyword near the beginning'],
  provider: 'openrouter',
  model: 'llama-3.1-8b-instruct',
  generated_at: '2026-03-04T10:00:00Z',
};

const mockDescriptionSuggestion = {
  indicator: 'meta_description',
  score: 60,
  issues: ['Description could be more compelling'],
  suggestions: [
    {
      type: 'improve' as const,
      text: 'Discover powerful SEO tools to optimize your website. Free analysis for meta tags, keywords, and more.',
      reason: 'More actionable and includes call-to-action',
    },
  ],
  best_practices: ['Keep description between 120-160 characters'],
  provider: 'openrouter',
  model: 'llama-3.1-8b-instruct',
  generated_at: '2026-03-04T10:00:00Z',
};

describe('ToolWidget AI Suggestion Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.clearAllTimers();
    mockGetAISuggestion.mockReset();
  });

  afterEach(() => {
    jest.runOnlyPendingTimers();
  });

  // ============================================
  // AI-INT-001: Title AI Suggestion Button
  // ============================================

  describe('AI-INT-001: Title AI Suggestion Button', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description for the page', length: 28 },
            url: 'https://example.com',
            keywords: ['test', 'page'],
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-001-01: should render AI suggestion button for title', () => {
      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      expect(aiButtons.length).toBeGreaterThan(0);
    });

    it('AI-INT-001-02: should call AI API with correct context when title button clicked', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Find the title's AI suggestion button (first one)
      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      expect(mockGetAISuggestion).toHaveBeenCalledWith(
        'title',
        expect.objectContaining({
          title: 'Test Page Title',
          url: 'https://example.com',
        })
      );
    });

    it('AI-INT-001-03: should display title suggestion panel after successful API call', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument(); // Score
      });
      expect(screen.getByText(/title is too generic/i)).toBeInTheDocument();
      expect(screen.getByText(/Best SEO Tools 2026/i)).toBeInTheDocument();
    });

    it('AI-INT-001-04: should show loading state while fetching title suggestion', async () => {
      let resolvePromise: (value: unknown) => void;
      mockGetAISuggestion.mockReturnValueOnce(
        new Promise((resolve) => {
          resolvePromise = resolve;
        })
      );

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      // Check button is disabled during loading
      await waitFor(() => {
        expect(aiButtons[0]).toBeDisabled();
      });

      // Resolve the promise
      resolvePromise!(mockTitleSuggestion);

      await act(async () => {
        jest.runAllTimers();
      });
    });

    it('AI-INT-001-05: should show error state when title suggestion fails', async () => {
      mockGetAISuggestion.mockRejectedValueOnce(new Error('API Error'));

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/error/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // AI-INT-002: Description AI Suggestion Button
  // ============================================

  describe('AI-INT-002: Description AI Suggestion Button', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description for the page', length: 28 },
            url: 'https://example.com',
            keywords: ['test', 'page'],
            content_summary: 'This is a test page about testing.',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-002-01: should render AI suggestion button for description', () => {
      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      expect(aiButtons.length).toBe(2); // One for title, one for description
    });

    it('AI-INT-002-02: should call AI API with correct context when description button clicked', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockDescriptionSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Find the description's AI suggestion button (second one)
      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[1]);

      await act(async () => {
        jest.runAllTimers();
      });

      expect(mockGetAISuggestion).toHaveBeenCalledWith(
        'meta_description',
        expect.objectContaining({
          description: 'Test description for the page',
          title: 'Test Page Title',
        })
      );
    });

    it('AI-INT-002-03: should display description suggestion panel after successful API call', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockDescriptionSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[1]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('60')).toBeInTheDocument(); // Score
      });
      expect(screen.getByText(/description could be more compelling/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // AI-INT-003: AI Suggestion Panel Interaction
  // ============================================

  describe('AI-INT-003: AI Suggestion Panel Interaction', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description', length: 16 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-003-01: should collapse and expand suggestion panel', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Get suggestion
      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });

      // Find collapse button
      const collapseButton = screen.getByRole('button', { name: /collapse|expand|toggle/i });
      fireEvent.click(collapseButton);

      // Issues should be hidden when collapsed
      await waitFor(() => {
        expect(screen.queryByText(/title is too generic/i)).not.toBeInTheDocument();
      });

      // Expand again
      fireEvent.click(collapseButton);

      await waitFor(() => {
        expect(screen.getByText(/title is too generic/i)).toBeInTheDocument();
      });
    });

    it('AI-INT-003-02: should copy suggestion text to clipboard', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });

      // Find suggestion copy button (not the title copy button)
      const suggestionCopyButton = screen.getByRole('button', { name: /copy suggestion/i });
      fireEvent.click(suggestionCopyButton);

      await waitFor(() => {
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
          'Best SEO Tools 2026 | Free Website Analyzer'
        );
      });
    });

    it('AI-INT-003-03: should show copied confirmation', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });

      // Find suggestion copy button (not the title copy button)
      const suggestionCopyButton = screen.getByRole('button', { name: /copy suggestion/i });
      fireEvent.click(suggestionCopyButton);

      await waitFor(() => {
        expect(screen.getByText(/copied/i)).toBeInTheDocument();
      });
    });

    it('AI-INT-003-04: should show regenerate button and allow regeneration', async () => {
      mockGetAISuggestion
        .mockResolvedValueOnce(mockTitleSuggestion)
        .mockResolvedValueOnce({ ...mockTitleSuggestion, score: 85 });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });

      // Click regenerate
      const regenerateButton = screen.getByRole('button', { name: /regenerate/i });
      fireEvent.click(regenerateButton);

      await act(async () => {
        jest.runAllTimers();
      });

      // Should show new result
      await waitFor(() => {
        expect(screen.getByText('85')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // AI-INT-004: Rate Limit Handling
  // ============================================

  describe('AI-INT-004: Rate Limit Handling', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description', length: 16 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-004-01: should show rate limit message when rate limited', async () => {
      const rateLimitError = new Error('Rate limit exceeded');
      (rateLimitError as any).name = 'RateLimitError';
      (rateLimitError as any).retryAfter = 30;
      mockGetAISuggestion.mockRejectedValueOnce(rateLimitError);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/rate limit/i)).toBeInTheDocument();
      });
    });

    it('AI-INT-004-02: should show countdown timer during rate limit', async () => {
      const rateLimitError = new Error('Rate limit exceeded');
      (rateLimitError as any).name = 'RateLimitError';
      (rateLimitError as any).retryAfter = 5;
      mockGetAISuggestion.mockRejectedValueOnce(rateLimitError);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      // Should show countdown
      await waitFor(() => {
        expect(screen.getByText(/rate limit/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // AI-INT-005: Fallback Indicator
  // ============================================

  describe('AI-INT-005: Fallback Indicator', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description', length: 16 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-005-01: should show fallback indicator when static fallback used', async () => {
      const fallbackSuggestion = {
        ...mockTitleSuggestion,
        provider: 'static_fallback',
        model: null,
        fallback_reason: 'AI provider timeout',
      };
      mockGetAISuggestion.mockResolvedValueOnce(fallbackSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/fallback mode/i)).toBeInTheDocument();
      });
      expect(screen.getByText(/timeout/i)).toBeInTheDocument();
    });

    it('AI-INT-005-02: should not show fallback warning for normal results', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });

      expect(screen.queryByText(/fallback/i)).not.toBeInTheDocument();
    });
  });

  // ============================================
  // AI-INT-006: Score Badge Colors
  // ============================================

  describe('AI-INT-006: Score Badge Colors', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test', length: 4 },
            description: { value: 'Test', length: 4 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-006-01: should show green badge for score >= 70', async () => {
      mockGetAISuggestion.mockResolvedValueOnce({ ...mockTitleSuggestion, score: 85 });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        const scoreBadge = screen.getByText('85');
        expect(scoreBadge).toBeInTheDocument();
        // Check for green class
        expect(scoreBadge.closest('[class*="green"]')).toBeTruthy();
      });
    });

    it('AI-INT-006-02: should show yellow badge for score 40-69', async () => {
      mockGetAISuggestion.mockResolvedValueOnce({ ...mockTitleSuggestion, score: 55 });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        const scoreBadge = screen.getByText('55');
        expect(scoreBadge).toBeInTheDocument();
        // Check for yellow class
        expect(scoreBadge.closest('[class*="yellow"]')).toBeTruthy();
      });
    });

    it('AI-INT-006-03: should show red badge for score < 40', async () => {
      mockGetAISuggestion.mockResolvedValueOnce({ ...mockTitleSuggestion, score: 25 });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        const scoreBadge = screen.getByText('25');
        expect(scoreBadge).toBeInTheDocument();
        // Check for red class
        expect(scoreBadge.closest('[class*="red"]')).toBeTruthy();
      });
    });
  });

  // ============================================
  // AI-INT-007: Accessibility
  // ============================================

  describe('AI-INT-007: Accessibility', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Page Title', length: 16 },
            description: { value: 'Test description', length: 16 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-007-01: should have aria-label on AI suggestion buttons', () => {
      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      aiButtons.forEach((button) => {
        expect(button).toHaveAttribute('aria-label');
      });
    });

    it('AI-INT-007-02: should announce suggestion results to screen readers', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        // Check for screen reader announcements
        const liveRegion = screen.getByRole('status', { hidden: true });
        expect(liveRegion).toBeInTheDocument();
      });
    });

    it('AI-INT-007-03: should have correct role for suggestion panel', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByRole('status', { hidden: true })).toBeInTheDocument();
      });
    });

    it('AI-INT-007-04: should be keyboard navigable', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });

      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Tab to AI suggestion button
      await user.tab();
      await user.tab();

      // Press Enter to activate
      await user.keyboard('{Enter}');

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(mockGetAISuggestion).toHaveBeenCalled();
      });
    });
  });

  // ============================================
  // AI-INT-008: Empty/Missing Data Handling
  // ============================================

  describe('AI-INT-008: Empty/Missing Data Handling', () => {
    it('AI-INT-008-01: should not show AI button when title is missing', () => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: null, length: 0 },
            description: { value: 'Test description', length: 16 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Only one AI button should be present (for description, not title)
      const aiButtons = screen.queryAllByRole('button', { name: /ai suggest/i });
      expect(aiButtons.length).toBe(1);
    });

    it('AI-INT-008-02: should not show AI button when description is missing', () => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Title', length: 10 },
            description: { value: null, length: 0 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      // Only one AI button should be present (for title, not description)
      const aiButtons = screen.queryAllByRole('button', { name: /ai suggest/i });
      expect(aiButtons.length).toBe(1);
    });

    it('AI-INT-008-03: should handle empty suggestions array', async () => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Title', length: 10 },
            description: { value: 'Test', length: 4 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });

      mockGetAISuggestion.mockResolvedValueOnce({
        ...mockTitleSuggestion,
        suggestions: [],
      });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/no suggestions available/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // AI-INT-009: Provider Attribution
  // ============================================

  describe('AI-INT-009: Provider Attribution', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            title: { value: 'Test Title', length: 10 },
            description: { value: 'Test', length: 4 },
            url: 'https://example.com',
            warnings: [],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-009-01: should display provider name in suggestion panel', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/openrouter/i)).toBeInTheDocument();
      });
    });

    it('AI-INT-009-02: should display model name when available', async () => {
      mockGetAISuggestion.mockResolvedValueOnce(mockTitleSuggestion);

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText(/llama-3.1/i)).toBeInTheDocument();
      });
    });

    it('AI-INT-009-03: should handle null model name', async () => {
      mockGetAISuggestion.mockResolvedValueOnce({
        ...mockTitleSuggestion,
        model: null,
      });

      render(
        <ToolWidget tool="meta-tag-analyzer" title="Test" description="Test" />
      );

      const aiButtons = screen.getAllByRole('button', { name: /ai suggest/i });
      fireEvent.click(aiButtons[0]);

      await act(async () => {
        jest.runAllTimers();
      });

      await waitFor(() => {
        expect(screen.getByText('75')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // AI-INT-010: Integration with Keyword Density Tool
  // ============================================

  describe('AI-INT-010: Integration with Keyword Density Tool', () => {
    beforeEach(() => {
      mockUseToolAnalysis.mockReturnValue({
        state: 'success',
        result: {
          success: true,
          data: {
            keyword: 'SEO',
            density: 2.5,
            occurrences: 10,
            totalWords: 400,
            warnings: [{ type: 'success', message: 'Keyword density is optimal' }],
          },
          analyzedAt: '2026-03-04T10:00:00Z',
        },
        error: null,
        validationError: null,
        isLoading: false,
        analyze: mockAnalyze,
        reset: mockReset,
        retry: mockRetry,
      });
    });

    it('AI-INT-010-01: should render keyword density results correctly', () => {
      render(
        <ToolWidget tool="keyword-density" title="Test" description="Test" />
      );

      expect(screen.getByText('2.50%')).toBeInTheDocument();
      expect(screen.getByText('10')).toBeInTheDocument();
      expect(screen.getByText('400')).toBeInTheDocument();
    });

    it('AI-INT-010-02: should not show AI suggestion buttons in keyword density tool', () => {
      // Keyword density tool does not have AI suggestions integrated yet
      render(
        <ToolWidget tool="keyword-density" title="Test" description="Test" />
      );

      const aiButtons = screen.queryAllByRole('button', { name: /ai suggest/i });
      expect(aiButtons.length).toBe(0);
    });
  });
});
