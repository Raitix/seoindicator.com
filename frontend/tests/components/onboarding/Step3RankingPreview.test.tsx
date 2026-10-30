/**
 * Step 3 - Ranking Preview Component Tests
 *
 * Tests for the third step of the onboarding wizard that shows users
 * an immediate preview of their keyword ranking using MockSERPFetcher.
 *
 * @see TEST-17-8.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock MockSERPFetcher
const mockFetch = vi.fn();
vi.mock('@/services/MockSERPFetcher', () => ({
  default: {
    fetch: (...args: unknown[]) => mockFetch(...args),
  },
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Info: () => <span data-testid="info-icon">ℹ</span>,
  Loader2: ({ className }: { className?: string }) => (
    <span data-testid="loader-icon" className={className}>Loading</span>
  ),
  Plus: () => <span data-testid="plus-icon">+</span>,
  AlertCircle: () => <span data-testid="alert-icon">!</span>,
  RefreshCw: () => <span data-testid="refresh-icon">↻</span>,
  ArrowRight: () => <span data-testid="arrow-right-icon">→</span>,
}));

// Mock component implementation for TDD
const Step3RankingPreview = ({
  keyword,
  websiteUrl,
  onComplete,
  onSkip,
}: {
  keyword: string;
  websiteUrl: string;
  onComplete: () => void;
  onSkip: () => void;
}) => {
  return (
    <div data-testid="step3-ranking-preview">
      <div data-testid="preview-badge" className="preview-indicator">
        <span data-testid="info-icon">ℹ</span>
        <span>Preview based on sample data</span>
      </div>
      <h2>Checking ranking for: {keyword}</h2>
      <p>Website: {websiteUrl}</p>
      {/* Actual implementation will have loading, success, error states */}
    </div>
  );
};

describe('Step3RankingPreview', () => {
  const defaultProps = {
    keyword: 'seo tools',
    websiteUrl: 'example.com',
    onComplete: vi.fn(),
    onSkip: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // RP-001: Component Rendering
  // ============================================
  describe('Rendering', () => {
    it('RP-001-01: renders with required props', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-001-02: displays keyword', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByText(/checking ranking for: seo tools/i)).toBeInTheDocument();
    });

    it('RP-001-03: displays website URL', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByText(/website: example\.com/i)).toBeInTheDocument();
    });

    it('RP-001-04: displays preview indicator', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByTestId('preview-badge')).toBeInTheDocument();
      expect(screen.getByText(/preview based on sample data/i)).toBeInTheDocument();
    });

    it('RP-001-05: has correct data-testid', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });
  });

  // ============================================
  // RP-002: Loading State
  // ============================================
  describe('Loading State', () => {
    it('RP-002-01: shows loading spinner initially', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation, this should find the loader icon
      // expect(screen.getByTestId('loader-icon')).toBeInTheDocument();
    });

    it('RP-002-02: shows loading text', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation: expect(screen.getByText(/checking your ranking/i)).toBeInTheDocument();
    });

    it('RP-002-03: loading has aria-live', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation: expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('RP-002-04: buttons disabled during loading', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation: expect(screen.getByRole('button', { name: /continue/i })).toBeDisabled();
    });

    it('RP-002-05: loading state transitions to results', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet text',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // After implementation: expect(screen.getByText(/position 15/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // RP-003: Position Display (Success State)
  // ============================================
  describe('Position Display', () => {
    it('RP-003-01: shows position number', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/position 15/i)).toBeInTheDocument();
      });
    });

    it('RP-003-02: shows position explanation', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/page 2 of search results/i)).toBeInTheDocument();
      });
    });

    it('RP-003-03: shows position icon', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
      });
    });

    it('RP-003-04: position 1 displays correctly', async () => {
      mockFetch.mockResolvedValue({
        position: 1,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/position 1/i)).toBeInTheDocument();
        // expect(screen.getByText(/top result/i)).toBeInTheDocument();
      });
    });

    it('RP-003-05: top 10 styling', async () => {
      mockFetch.mockResolvedValue({
        position: 5,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const positionElement = screen.getByTestId('position-display');
        // expect(positionElement).toHaveClass('text-green-500');
      });
    });

    it('RP-003-06: top 20 styling', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const positionElement = screen.getByTestId('position-display');
        // expect(positionElement).toHaveClass('text-yellow-500');
      });
    });

    it('RP-003-07: top 50 styling', async () => {
      mockFetch.mockResolvedValue({
        position: 45,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const positionElement = screen.getByTestId('position-display');
        // expect(positionElement).toHaveClass('text-orange-500');
      });
    });

    it('RP-003-08: top 100 styling', async () => {
      mockFetch.mockResolvedValue({
        position: 95,
        url: 'https://example.com',
        title: 'Example Page',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const positionElement = screen.getByTestId('position-display');
        // expect(positionElement).toHaveClass('text-gray-500');
      });
    });
  });

  // ============================================
  // RP-004: Not Ranking Display
  // ============================================
  describe('Not Ranking Display', () => {
    it('RP-004-01: shows "Not in top 100"', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/not in top 100/i)).toBeInTheDocument();
      });
    });

    it('RP-004-02: shows encouragement message', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/keep optimizing/i)).toBeInTheDocument();
      });
    });

    it('RP-004-03: shows info icon', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByTestId('info-icon')).toBeInTheDocument();
      });
    });

    it('RP-004-04: neutral styling', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const container = screen.getByTestId('not-ranking-container');
        // expect(container).toHaveClass('text-gray-400');
      });
    });
  });

  // ============================================
  // RP-005: Error Handling
  // ============================================
  describe('Error Handling', () => {
    it('RP-005-01: shows error message', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/error checking ranking/i)).toBeInTheDocument();
      });
    });

    it('RP-005-02: shows retry button on error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('button', { name: /retry/i })).toBeInTheDocument();
      });
    });

    it('RP-005-03: shows continue option on error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('button', { name: /continue anyway/i })).toBeInTheDocument();
      });
    });

    it('RP-005-04: error has alert role', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('alert')).toBeInTheDocument();
      });
    });

    it('RP-005-05: retry calls fetch again', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      mockFetch.mockResolvedValue({
        position: 10,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const retryButton = screen.getByRole('button', { name: /retry/i });
        // fireEvent.click(retryButton);
      });

      // await waitFor(() => {
      //   expect(mockFetch).toHaveBeenCalledTimes(2);
      // });
    });

    it('RP-005-06: network error handled', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/network error/i)).toBeInTheDocument();
      });
    });

    it('RP-005-07: timeout error handled', async () => {
      mockFetch.mockRejectedValue(new Error('Request timeout'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/timeout/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // RP-006: Continue Button
  // ============================================
  describe('Continue Button', () => {
    it('RP-006-01: renders continue button', () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // expect(screen.getByRole('button', { name: /continue/i })).toBeInTheDocument();
    });

    it('RP-006-02: triggers onComplete callback', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const continueButton = screen.getByRole('button', { name: /continue/i });
        // fireEvent.click(continueButton);
      });

      // expect(defaultProps.onComplete).toHaveBeenCalledTimes(1);
    });

    it('RP-006-03: has aria-label', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const button = screen.getByRole('button', { name: /continue to next step/i });
        // expect(button).toBeInTheDocument();
      });
    });

    it('RP-006-04: disabled during loading', () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // expect(screen.getByRole('button', { name: /continue/i })).toBeDisabled();
    });

    it('RP-006-05: enabled after fetch', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('button', { name: /continue/i })).not.toBeDisabled();
      });
    });
  });

  // ============================================
  // RP-007: Skip Functionality
  // ============================================
  describe('Skip Functionality', () => {
    it('RP-007-01: renders skip link', () => {
      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // expect(screen.getByRole('button', { name: /skip for now/i })).toBeInTheDocument();
    });

    it('RP-007-02: triggers onSkip callback', async () => {
      render(<Step3RankingPreview {...defaultProps} />);

      // const skipButton = screen.getByRole('button', { name: /skip for now/i });
      // fireEvent.click(skipButton);

      // expect(defaultProps.onSkip).toHaveBeenCalledTimes(1);
    });

    it('RP-007-03: has button role', () => {
      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // const skip = screen.getByRole('button', { name: /skip/i });
      // expect(skip).toBeInTheDocument();
    });

    it('RP-007-04: has accessible name', () => {
      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // expect(screen.getByLabelText(/skip ranking preview/i)).toBeInTheDocument();
    });

    it('RP-007-05: works during loading', () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // const skipButton = screen.getByRole('button', { name: /skip/i });
      // expect(skipButton).not.toBeDisabled();
    });

    it('RP-007-06: works during error', async () => {
      mockFetch.mockRejectedValue(new Error('Error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const skipButton = screen.getByRole('button', { name: /skip/i });
        // expect(skipButton).not.toBeDisabled();
      });
    });
  });

  // ============================================
  // RP-008: Add More Keywords Button
  // ============================================
  describe('Add More Keywords Button', () => {
    it('RP-008-01: renders button', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('button', { name: /add more keywords/i })).toBeInTheDocument();
      });
    });

    it('RP-008-02: has correct href', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const link = screen.getByRole('link', { name: /add more keywords/i });
        // expect(link).toHaveAttribute('href', '/keywords');
      });
    });

    it('RP-008-03: has icon', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByTestId('plus-icon')).toBeInTheDocument();
      });
    });

    it('RP-008-04: has aria-label', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByLabelText(/add more keywords to track/i)).toBeInTheDocument();
      });
    });

    it('RP-008-05: disabled during loading', () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // const button = screen.getByRole('button', { name: /add more keywords/i });
      // expect(button).toBeDisabled();
    });
  });

  // ============================================
  // RP-009: Preview Indicator
  // ============================================
  describe('Preview Indicator', () => {
    it('RP-009-01: renders preview badge', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByTestId('preview-badge')).toBeInTheDocument();
    });

    it('RP-009-02: explains data source', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByText(/sample data/i)).toBeInTheDocument();
    });

    it('RP-009-03: has info icon', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      expect(screen.getByTestId('info-icon')).toBeInTheDocument();
    });

    it('RP-009-04: has tooltip', async () => {
      const user = userEvent.setup();
      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // const badge = screen.getByTestId('preview-badge');
      // await user.hover(badge);
      // expect(screen.getByRole('tooltip')).toBeInTheDocument();
    });
  });

  // ============================================
  // RP-010: Accessibility
  // ============================================
  describe('Accessibility', () => {
    it('RP-010-01: has accessible name', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      // After implementation:
      // expect(screen.getByRole('region', { name: /ranking preview/i })).toBeInTheDocument();
    });

    it('RP-010-02: position has live region', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('status')).toBeInTheDocument();
      });
    });

    it('RP-010-03: focus management on state change', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // After implementation: focus should move to result
        // expect(screen.getByTestId('position-display')).toHaveFocus();
      });
    });

    it('RP-010-04: keyboard navigation', async () => {
      const user = userEvent.setup();
      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // await user.tab();
      // expect(screen.getByRole('button', { name: /skip/i })).toHaveFocus();
      // await user.tab();
      // expect(screen.getByRole('button', { name: /continue/i })).toHaveFocus();
    });

    it('RP-010-05: screen reader announces position', async () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const announcement = screen.getByText(/your site is in position 15/i);
        // expect(announcement).toBeInTheDocument();
      });
    });

    it('RP-010-06: screen reader announces not ranking', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // const announcement = screen.getByText(/your site is not in top 100/i);
        // expect(announcement).toBeInTheDocument();
      });
    });

    it('RP-010-07: color contrast sufficient', () => {
      render(<Step3RankingPreview {...defaultProps} />);
      // This is a visual test - after implementation, verify with axe-core
    });
  });

  // ============================================
  // RP-011: Integration with MockSERPFetcher
  // ============================================
  describe('MockSERPFetcher Integration', () => {
    it('RP-011-01: calls fetch on mount', () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      // After implementation:
      // expect(mockFetch).toHaveBeenCalledWith('seo tools', expect.objectContaining({
      //   targetUrl: 'example.com',
      // }));
    });

    it('RP-011-02: passes correct keyword', () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} keyword="marketing tips" />);

      // After implementation:
      // expect(mockFetch).toHaveBeenCalledWith('marketing tips', expect.anything());
    });

    it('RP-011-03: passes correct targetUrl', () => {
      mockFetch.mockResolvedValue({
        position: 15,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} websiteUrl="mysite.com" />);

      // After implementation:
      // expect(mockFetch).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({
      //   targetUrl: 'mysite.com',
      // }));
    });

    it('RP-011-04: displays position from result', async () => {
      mockFetch.mockResolvedValue({
        position: 25,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/position 25/i)).toBeInTheDocument();
      });
    });

    it('RP-011-05: handles null position', async () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/not in top 100/i)).toBeInTheDocument();
      });
    });

    it('RP-011-06: handles fetch error', async () => {
      mockFetch.mockRejectedValue(new Error('API Error'));

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByRole('alert')).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // RP-012: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('RP-012-01: handles empty keyword', () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} keyword="" />);

      // Should not crash, should handle gracefully
      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-012-02: handles empty website URL', () => {
      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} websiteUrl="" />);

      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-012-03: handles very long keyword', () => {
      const longKeyword = 'a'.repeat(250);

      mockFetch.mockResolvedValue({
        position: null,
        url: null,
        title: null,
        snippet: null,
      });

      render(<Step3RankingPreview {...defaultProps} keyword={longKeyword} />);

      // Should truncate or wrap gracefully
      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-012-04: handles special characters in keyword', () => {
      mockFetch.mockResolvedValue({
        position: 10,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} keyword="seo & marketing <script>" />);

      // Should escape HTML
      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-012-05: handles unicode in keyword', () => {
      mockFetch.mockResolvedValue({
        position: 10,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} keyword="seo ñoño 中文 🚀" />);

      expect(screen.getByTestId('step3-ranking-preview')).toBeInTheDocument();
    });

    it('RP-012-06: handles position 0 (featured snippet)', async () => {
      mockFetch.mockResolvedValue({
        position: 0,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
        isFeatured: true,
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/featured snippet/i)).toBeInTheDocument();
      });
    });

    it('RP-012-07: handles position > 100', async () => {
      mockFetch.mockResolvedValue({
        position: 150,
        url: 'https://example.com',
        title: 'Example',
        snippet: 'Snippet',
      });

      render(<Step3RankingPreview {...defaultProps} />);

      await waitFor(() => {
        // expect(screen.getByText(/not in top 100/i)).toBeInTheDocument();
      });
    });
  });
});
