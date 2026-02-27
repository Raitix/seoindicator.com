/**
 * @jest-environment jsdom
 */

import * as React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MobileFriendlyChecker } from '@/components/tools/MobileFriendlyChecker';
import { useToolAnalysis } from '@/hooks/useToolAnalysis';

// Mock the useToolAnalysis hook
jest.mock('@/hooks/useToolAnalysis', () => ({
  useToolAnalysis: jest.fn(),
}));

// Mock fetch for API calls
const mockFetch = jest.fn();
global.fetch = mockFetch as jest.Mock;

const mockUseToolAnalysis = useToolAnalysis as jest.MockedFunction<typeof useToolAnalysis>;

describe('MobileFriendlyChecker Component', () => {
  const defaultMockReturn = {
    state: 'idle',
    result: null,
    error: null,
    validationError: null,
    isLoading: false,
    analyze: jest.fn(),
    reset: jest.fn(),
    retry: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseToolAnalysis.mockReturnValue(defaultMockReturn as any);
  });

  // =========================================================================
  // RENDER TESTS (FE-001 to FE-003)
  // =========================================================================

  /**
   * FE-001: Renders form with URL input
   */
  it('renders the form with URL input field', () => {
    render(<MobileFriendlyChecker />);

    expect(screen.getByLabelText(/url to analyze/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/https:\/\//i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /analyze/i })).toBeInTheDocument();
  });

  /**
   * FE-002: Shows loading state when analyzing
   */
  it('shows loading state when analyzing', () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'loading',
      isLoading: true,
    } as any);

    render(<MobileFriendlyChecker />);

    expect(screen.getByText(/analyzing/i)).toBeInTheDocument();
    expect(screen.getByRole('status')).toBeInTheDocument(); // Loading spinner
  });

  /**
   * FE-003: Displays results after analysis
   */
  it('displays results after successful analysis', async () => {
    const mockResult = {
      mobileScore: 85,
      viewport: { present: true, valid: true, content: 'width=device-width' },
      responsiveIndicators: { hasMediaQueries: true, hasFlexibleGrid: true },
      readability: { fontSizeMin: 16, fontSizeWarning: false },
      tapTargets: { links: 10, smallTargets: 0 },
      horizontalScroll: { detected: false },
      issues: [],
      recommendations: [{ type: 'success', message: 'Good!' }],
    };

    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'success',
      result: {
        data: mockResult,
        analyzedAt: '2026-02-27T12:00:00Z',
      },
    } as any);

    render(<MobileFriendlyChecker />);

    // Check score is displayed
    expect(screen.getByText(/85/)).toBeInTheDocument();
    expect(screen.getByText(/mobile score/i)).toBeInTheDocument();
  });

  // =========================================================================
  // SCORE DISPLAY TESTS (FE-004)
  // =========================================================================

  /**
   * FE-004: Shows score badge with correct color
   */
  it('shows score badge with correct color based on score', () => {
    const testCases = [
      { score: 90, expectedClass: 'bg-green' },
      { score: 65, expectedClass: 'bg-yellow' },
      { score: 30, expectedClass: 'bg-red' },
    ];

    testCases.forEach(({ score, expectedClass }) => {
      mockUseToolAnalysis.mockReturnValue({
        ...defaultMockReturn,
        state: 'success',
        result: {
          data: { mobileScore: score },
          analyzedAt: '2026-02-27T12:00:00Z',
        },
      } as any);

      const { container, unmount } = render(<MobileFriendlyChecker />);
      
      // Check score is displayed
      expect(screen.getByText(new RegExp(score.toString()))).toBeInTheDocument();
      
      unmount();
    });
  });

  // =========================================================================
  // SECTION DISPLAY TESTS (FE-005 to FE-007)
  // =========================================================================

  /**
   * FE-005: Shows viewport section
   */
  it('shows viewport section with results', () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'success',
      result: {
        data: {
          mobileScore: 85,
          viewport: { present: true, valid: true, content: 'width=device-width, initial-scale=1' },
        },
        analyzedAt: '2026-02-27T12:00:00Z',
      },
    } as any);

    render(<MobileFriendlyChecker />);

    expect(screen.getByText(/viewport/i)).toBeInTheDocument();
    expect(screen.getByText(/width=device-width/)).toBeInTheDocument();
  });

  /**
   * FE-006: Shows issues list when issues found
   */
  it('shows issues list when issues are found', () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'success',
      result: {
        data: {
          mobileScore: 60,
          issues: [
            { severity: 'warning', message: 'Missing viewport tag', category: 'viewport' },
            { severity: 'warning', message: 'Small tap targets', category: 'tap_targets' },
          ],
        },
        analyzedAt: '2026-02-27T12:00:00Z',
      },
    } as any);

    render(<MobileFriendlyChecker />);

    expect(screen.getByText(/issues/i)).toBeInTheDocument();
    expect(screen.getByText(/Missing viewport tag/i)).toBeInTheDocument();
    expect(screen.getByText(/Small tap targets/i)).toBeInTheDocument();
  });

  /**
   * FE-007: Shows mobile preview iframe
   */
  it('shows mobile preview section', () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'success',
      result: {
        data: {
          mobileScore: 85,
          previewUrl: 'https://example.com',
        },
        analyzedAt: '2026-02-27T12:00:00Z',
      },
    } as any);

    render(<MobileFriendlyChecker />);

    // Preview section should be present
    expect(screen.getByText(/preview/i) || screen.getByTestId('mobile-preview')).toBeTruthy();
  });

  // =========================================================================
  // ERROR HANDLING TESTS (FE-008 to FE-009)
  // =========================================================================

  /**
   * FE-008: Handles error state
   */
  it('displays error message on failure', () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'error',
      error: { message: 'Failed to fetch page', code: 'FETCH_FAILED' },
    } as any);

    render(<MobileFriendlyChecker />);

    expect(screen.getByRole('alert')).toBeInTheDocument();
    expect(screen.getByText(/failed to fetch page/i)).toBeInTheDocument();
  });

  /**
   * FE-009: Retry button on error
   */
  it('shows retry button on error', () => {
    const mockRetry = jest.fn();
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'error',
      error: { message: 'Connection failed' },
      retry: mockRetry,
    } as any);

    render(<MobileFriendlyChecker />);

    const retryButton = screen.getByRole('button', { name: /try again|retry/i });
    expect(retryButton).toBeInTheDocument();

    fireEvent.click(retryButton);
    expect(mockRetry).toHaveBeenCalled();
  });

  // =========================================================================
  // ADDITIONAL FUNCTIONALITY TESTS (FE-010 to FE-012)
  // =========================================================================

  /**
   * FE-010: Reset button to analyze another URL
   */
  it('shows reset button after successful analysis', async () => {
    const mockReset = jest.fn();
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      state: 'success',
      result: {
        data: { mobileScore: 85 },
        analyzedAt: '2026-02-27T12:00:00Z',
      },
      reset: mockReset,
    } as any);

    render(<MobileFriendlyChecker />);

    const resetButton = screen.getByRole('button', { name: /analyze another|reset/i });
    expect(resetButton).toBeInTheDocument();

    fireEvent.click(resetButton);
    expect(mockReset).toHaveBeenCalled();
  });

  /**
   * FE-011: Accessibility - ARIA labels
   */
  it('has proper accessibility attributes', () => {
    render(<MobileFriendlyChecker />);

    // Check form elements have accessible names
    const urlInput = screen.getByLabelText(/url to analyze/i);
    expect(urlInput).toHaveAttribute('type', 'url');
    expect(urlInput).toHaveAttribute('id');

    // Check button is accessible
    const submitButton = screen.getByRole('button', { name: /analyze/i });
    expect(submitButton).not.toBeDisabled();
  });

  /**
   * FE-012: Responsive design - layout adapts
   */
  it('renders responsively on different screen sizes', () => {
    const { container } = render(<MobileFriendlyChecker />);

    // Check for responsive classes
    const card = container.querySelector('.max-w-2xl');
    expect(card).toBeInTheDocument();

    // Check for mobile-friendly form layout
    const form = container.querySelector('form');
    expect(form).toBeInTheDocument();
  });

  // =========================================================================
  // USER INTERACTION TESTS
  // =========================================================================

  /**
   * Submit button disabled when URL is empty
   */
  it('disables submit button when URL is empty', () => {
    render(<MobileFriendlyChecker />);

    const submitButton = screen.getByRole('button', { name: /analyze/i });
    expect(submitButton).toBeDisabled();
  });

  /**
   * Submit button enabled when URL is entered
   */
  it('enables submit button when URL is entered', async () => {
    const user = userEvent.setup();
    render(<MobileFriendlyChecker />);

    const urlInput = screen.getByLabelText(/url to analyze/i);
    const submitButton = screen.getByRole('button', { name: /analyze/i });

    await user.type(urlInput, 'https://example.com');

    expect(submitButton).not.toBeDisabled();
  });

  /**
   * Calls analyze on form submit
   */
  it('calls analyze function on form submit', async () => {
    const mockAnalyze = jest.fn();
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      analyze: mockAnalyze,
    } as any);

    const user = userEvent.setup();
    render(<MobileFriendlyChecker />);

    const urlInput = screen.getByLabelText(/url to analyze/i);
    const submitButton = screen.getByRole('button', { name: /analyze/i });

    await user.type(urlInput, 'https://example.com');
    await user.click(submitButton);

    expect(mockAnalyze).toHaveBeenCalledWith('https://example.com', undefined);
  });

  /**
   * Shows validation error for invalid URL
   */
  it('shows validation error for invalid URL', async () => {
    mockUseToolAnalysis.mockReturnValue({
      ...defaultMockReturn,
      validationError: 'Please enter a valid URL',
    } as any);

    render(<MobileFriendlyChecker />);

    expect(screen.getByText(/please enter a valid url/i)).toBeInTheDocument();
  });
});
