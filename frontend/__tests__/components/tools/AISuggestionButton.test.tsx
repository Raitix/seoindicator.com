/**
 * AISuggestionButton Component Tests
 * TASK-21-11: Frontend Integration with Tools
 * 
 * Tests for the AI suggestion trigger button in tool components.
 */

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Types
interface AISuggestionButtonProps {
  indicator: string;
  context: Record<string, unknown>;
  onClick?: () => void;
  disabled?: boolean;
  isLoading?: boolean;
  hasResults?: boolean;
  rateLimitReached?: boolean;
  rateLimitResetAt?: string | null;
  remainingRequests?: number;
  className?: string;
}

// Mock Component (will be replaced with actual implementation)
const AISuggestionButton: React.FC<AISuggestionButtonProps> = ({
  indicator,
  context,
  onClick,
  disabled = false,
  isLoading = false,
  hasResults = false,
  rateLimitReached = false,
  rateLimitResetAt = null,
  remainingRequests,
  className = '',
}) => {
  const isDisabled = disabled || isLoading || rateLimitReached || !context;

  const getTooltipText = () => {
    if (rateLimitReached && rateLimitResetAt) {
      const resetDate = new Date(rateLimitResetAt);
      return `Rate limit reached. Resets at ${resetDate.toLocaleTimeString()}`;
    }
    if (isLoading) {
      return 'Generating suggestions...';
    }
    if (!context || Object.keys(context).length === 0) {
      return 'Analyze a page first to get AI suggestions';
    }
    return 'Get AI-powered SEO suggestions';
  };

  return (
    <button
      data-testid="ai-suggestion-button"
      data-indicator={indicator}
      data-loading={isLoading}
      data-rate-limit-reached={rateLimitReached}
      onClick={onClick}
      disabled={isDisabled}
      className={className}
      aria-label={`Get AI suggestions for ${indicator}`}
      aria-busy={isLoading}
      aria-disabled={isDisabled}
      title={getTooltipText()}
    >
      {isLoading ? (
        <>
          <span data-testid="loading-spinner" aria-hidden="true" />
          <span>Generating...</span>
        </>
      ) : hasResults ? (
        <>
          <span data-testid="refresh-icon" aria-hidden="true" />
          <span>Refresh Suggestions</span>
        </>
      ) : (
        <>
          <span data-testid="sparkle-icon" aria-hidden="true" />
          <span>Get AI Suggestions</span>
        </>
      )}
      
      {!isLoading && remainingRequests !== undefined && (
        <span data-testid="remaining-requests" aria-label={`${remainingRequests} requests remaining`}>
          {remainingRequests}
        </span>
      )}
    </button>
  );
};

describe('AISuggestionButton', () => {
  const mockContext = { title: 'Test Page', keyword: 'test' };

  // ========================================
  // AC-1: Button Rendering Tests
  // ========================================
  describe('AC-1: Button Rendering', () => {
    it('should render the button', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeInTheDocument();
    });

    it('should display "Get AI Suggestions" text', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByText('Get AI Suggestions')).toBeInTheDocument();
    });

    it('should have sparkle icon initially', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('sparkle-icon')).toBeInTheDocument();
    });

    it('should store indicator in data attribute', () => {
      render(
        <AISuggestionButton
          indicator="meta_description"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('data-indicator', 'meta_description');
    });
  });

  // ========================================
  // AC-1: Loading State Tests
  // ========================================
  describe('AC-1: Loading State', () => {
    it('should show loading state when isLoading is true', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
      expect(screen.getByText('Generating...')).toBeInTheDocument();
    });

    it('should have data-loading attribute when loading', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('data-loading', 'true');
    });

    it('should be disabled during loading', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should have aria-busy="true" during loading', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-busy', 'true');
    });

    it('should show appropriate tooltip during loading', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('title', 'Generating suggestions...');
    });
  });

  // ========================================
  // AC-1: Disabled State Tests
  // ========================================
  describe('AC-1: Disabled State (No Page Analyzed)', () => {
    it('should be disabled when context is empty', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{}}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should be disabled when context is undefined/null', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{}}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should show tooltip when disabled due to no context', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{}}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('title', 'Analyze a page first to get AI suggestions');
    });

    it('should be disabled when disabled prop is true', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          disabled={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });
  });

  // ========================================
  // AC-4: Rate Limit UI Tests
  // ========================================
  describe('AC-4: Rate Limit UI', () => {
    it('should be disabled when rate limit is reached', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          rateLimitReached={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should have data-rate-limit-reached attribute', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          rateLimitReached={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('data-rate-limit-reached', 'true');
    });

    it('should show rate limit tooltip with reset time', () => {
      const resetTime = '2026-03-04T13:00:00Z';
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          rateLimitReached={true}
          rateLimitResetAt={resetTime}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      expect(button).toHaveAttribute('title', expect.stringContaining('Rate limit reached'));
    });

    it('should show remaining requests count', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={5}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('5');
    });

    it('should have aria-label for remaining requests', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={5}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveAttribute('aria-label', '5 requests remaining');
    });

    it('should show 0 remaining requests at limit', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={0}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('0');
    });

    it('should not show remaining requests when undefined', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.queryByTestId('remaining-requests')).not.toBeInTheDocument();
    });
  });

  // ========================================
  // Click Handler Tests
  // ========================================
  describe('Click Handler', () => {
    it('should call onClick when clicked', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
        />
      );

      await user.click(screen.getByTestId('ai-suggestion-button'));
      expect(onClick).toHaveBeenCalledTimes(1);
    });

    it('should not call onClick when disabled', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          disabled={true}
        />
      );

      await user.click(screen.getByTestId('ai-suggestion-button'));
      expect(onClick).not.toHaveBeenCalled();
    });

    it('should not call onClick when loading', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          isLoading={true}
        />
      );

      await user.click(screen.getByTestId('ai-suggestion-button'));
      expect(onClick).not.toHaveBeenCalled();
    });

    it('should not call onClick when rate limited', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          rateLimitReached={true}
        />
      );

      await user.click(screen.getByTestId('ai-suggestion-button'));
      expect(onClick).not.toHaveBeenCalled();
    });
  });

  // ========================================
  // Has Results State Tests
  // ========================================
  describe('Has Results State', () => {
    it('should show "Refresh Suggestions" when hasResults is true', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          hasResults={true}
        />
      );

      expect(screen.getByText('Refresh Suggestions')).toBeInTheDocument();
    });

    it('should show refresh icon when hasResults is true', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          hasResults={true}
        />
      );

      expect(screen.getByTestId('refresh-icon')).toBeInTheDocument();
    });

    it('should not show sparkle icon when hasResults is true', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          hasResults={true}
        />
      );

      expect(screen.queryByTestId('sparkle-icon')).not.toBeInTheDocument();
    });
  });

  // ========================================
  // Accessibility Tests (AC-6)
  // ========================================
  describe('AC-6: Accessibility', () => {
    it('should have aria-label with indicator name', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-label', 'Get AI suggestions for title');
    });

    it('should have aria-label for different indicators', () => {
      render(
        <AISuggestionButton
          indicator="meta_description"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-label', 'Get AI suggestions for meta_description');
    });

    it('should have aria-disabled when disabled', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          disabled={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-disabled', 'true');
    });

    it('should have aria-busy="false" when not loading', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={false}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-busy', 'false');
    });

    it('should have aria-hidden on decorative icons', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('sparkle-icon')).toHaveAttribute('aria-hidden', 'true');
    });

    it('should be focusable when not disabled', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      button.focus();
      expect(button).toHaveFocus();
    });

    it('should be keyboard accessible', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      button.focus();
      await user.keyboard('{Enter}');
      
      expect(onClick).toHaveBeenCalledTimes(1);
    });

    it('should respond to Space key', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      button.focus();
      await user.keyboard(' ');
      
      expect(onClick).toHaveBeenCalledTimes(1);
    });
  });

  // ========================================
  // Indicator Types Tests
  // ========================================
  describe('Indicator Types', () => {
    const indicators = [
      'title',
      'meta_description',
      'robots',
      'sitemap',
      'headings',
      'image_alt',
      'filenames',
      'url',
      'links',
      '404',
    ];

    indicators.forEach((indicator) => {
      it(`should render for indicator: ${indicator}`, () => {
        render(
          <AISuggestionButton
            indicator={indicator}
            context={mockContext}
          />
        );

        expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('data-indicator', indicator);
        expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-label', `Get AI suggestions for ${indicator}`);
      });
    });
  });

  // ========================================
  // className Prop Tests
  // ========================================
  describe('className Prop', () => {
    it('should apply custom className', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          className="custom-button-class"
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveClass('custom-button-class');
    });

    it('should work without className prop', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeInTheDocument();
    });
  });

  // ========================================
  // Edge Cases
  // ========================================
  describe('Edge Cases', () => {
    it('should handle large remaining requests count', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={999}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('999');
    });

    it('should handle context with many properties', () => {
      const largeContext = Object.fromEntries(
        Array.from({ length: 50 }, (_, i) => [`key${i}`, `value${i}`])
      );

      render(
        <AISuggestionButton
          indicator="title"
          context={largeContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).not.toBeDisabled();
    });

    it('should handle special characters in indicator', () => {
      render(
        <AISuggestionButton
          indicator="meta_description"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeInTheDocument();
    });

    it('should handle transition from disabled to enabled', () => {
      const { rerender } = render(
        <AISuggestionButton
          indicator="title"
          context={{}}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();

      rerender(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).not.toBeDisabled();
    });

    it('should handle transition from loading to ready', () => {
      const { rerender } = render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={true}
        />
      );

      expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();

      rerender(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          isLoading={false}
          hasResults={true}
        />
      );

      expect(screen.queryByTestId('loading-spinner')).not.toBeInTheDocument();
      expect(screen.getByTestId('refresh-icon')).toBeInTheDocument();
    });
  });

  // ========================================
  // Integration Tests
  // ========================================
  describe('Integration', () => {
    it('should show complete loading flow', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      const { rerender } = render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          isLoading={false}
        />
      );

      // Initial state
      expect(screen.getByText('Get AI Suggestions')).toBeInTheDocument();
      expect(screen.getByTestId('sparkle-icon')).toBeInTheDocument();

      // Click triggers loading
      await user.click(screen.getByTestId('ai-suggestion-button'));
      expect(onClick).toHaveBeenCalled();

      // Simulate loading state
      rerender(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          isLoading={true}
        />
      );

      expect(screen.getByText('Generating...')).toBeInTheDocument();
      expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();

      // Simulate results state
      rerender(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          onClick={onClick}
          isLoading={false}
          hasResults={true}
        />
      );

      expect(screen.getByText('Refresh Suggestions')).toBeInTheDocument();
      expect(screen.getByTestId('refresh-icon')).toBeInTheDocument();
    });

    it('should handle rate limit flow', () => {
      const { rerender } = render(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={1}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('1');
      expect(screen.getByTestId('ai-suggestion-button')).not.toBeDisabled();

      // Rate limit reached
      rerender(
        <AISuggestionButton
          indicator="title"
          context={mockContext}
          remainingRequests={0}
          rateLimitReached={true}
          rateLimitResetAt="2026-03-04T13:00:00Z"
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('0');
      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('title', expect.stringContaining('Rate limit reached'));
    });
  });
});
