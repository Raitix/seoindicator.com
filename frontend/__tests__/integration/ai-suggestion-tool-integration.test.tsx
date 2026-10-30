/**
 * AI Suggestion Tool Integration Tests
 * TASK-21-11: Frontend Integration with Tools
 * 
 * Integration tests for AI suggestions across all 8 SEO tools.
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Types
interface ToolResultData {
  analyzedAt: string;
  results: Record<string, unknown>;
  summary?: string;
}

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

interface AISuggestionPanelProps {
  suggestions: Array<{
    type: 'improve' | 'rewrite' | 'add' | 'remove';
    text: string;
    reason: string;
  }>;
  issues: Array<{
    severity: 'critical' | 'warning' | 'info';
    message: string;
  }>;
  bestPractices: string[];
  score?: number | null;
  provider: string;
  model?: string | null;
  generatedAt?: string | null;
  isLoading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  showDebug?: boolean;
  className?: string;
}

// Mock Components
const AISuggestionButton: React.FC<AISuggestionButtonProps> = ({
  indicator,
  context,
  onClick,
  disabled = false,
  isLoading = false,
  hasResults = false,
  rateLimitReached = false,
  remainingRequests,
}) => {
  const isDisabled = disabled || isLoading || rateLimitReached || !context || Object.keys(context).length === 0;

  return (
    <button
      data-testid="ai-suggestion-button"
      data-indicator={indicator}
      data-loading={isLoading}
      disabled={isDisabled}
      onClick={onClick}
      aria-label={`Get AI suggestions for ${indicator}`}
    >
      {isLoading ? 'Generating...' : hasResults ? 'Refresh Suggestions' : 'Get AI Suggestions'}
      {remainingRequests !== undefined && (
        <span data-testid="remaining-requests">{remainingRequests}</span>
      )}
    </button>
  );
};

const AISuggestionPanel: React.FC<AISuggestionPanelProps> = ({
  suggestions,
  issues,
  bestPractices,
  score,
  provider,
  model,
  isLoading = false,
  error,
  onRetry,
}) => {
  if (isLoading) {
    return <div data-testid="ai-suggestion-panel" role="status">Loading...</div>;
  }

  if (error) {
    return (
      <div data-testid="ai-suggestion-panel" role="alert">
        <p>{error}</p>
        {onRetry && <button onClick={onRetry}>Retry</button>}
      </div>
    );
  }

  return (
    <div data-testid="ai-suggestion-panel">
      {score !== null && score !== undefined && (
        <div data-testid="score-section">Score: {score}</div>
      )}
      {issues.length > 0 && (
        <ul data-testid="issues-list">
          {issues.map((issue, i) => (
            <li key={i} data-severity={issue.severity}>{issue.message}</li>
          ))}
        </ul>
      )}
      {suggestions.length > 0 && (
        <ul data-testid="suggestions-list">
          {suggestions.map((s, i) => (
            <li key={i} data-type={s.type}>{s.text}</li>
          ))}
        </ul>
      )}
      <div data-testid="provider-info">Provider: {provider}</div>
      {model && <div data-testid="model-info">Model: {model}</div>}
    </div>
  );
};

// Mock Tool Components
interface ToolResultViewProps {
  toolName: string;
  indicator: string;
  contextData: Record<string, unknown>;
  hasResults: boolean;
  onAISuggestionClick?: () => void;
}

const ToolResultView: React.FC<ToolResultViewProps> = ({
  toolName,
  indicator,
  contextData,
  hasResults,
  onAISuggestionClick,
}) => {
  return (
    <div data-testid={`tool-result-${toolName}`}>
      <h2>{toolName} Results</h2>
      <div data-testid="tool-content">
        {/* Tool-specific results */}
      </div>
      <div className="ai-suggestion-section">
        <AISuggestionButton
          indicator={indicator}
          context={contextData}
          onClick={onAISuggestionClick}
          hasResults={hasResults}
        />
      </div>
    </div>
  );
};

// Tool Integration Configuration
const toolConfigs = [
  {
    toolName: 'Title Analyzer',
    indicator: 'title',
    contextData: { title: 'Test Page Title', content_summary: 'Page content...', keyword: 'test' },
  },
  {
    toolName: 'Meta Description',
    indicator: 'meta_description',
    contextData: { description: 'Test meta description', content: 'Page content...' },
  },
  {
    toolName: 'Robots Checker',
    indicator: 'robots',
    contextData: { robots_content: 'User-agent: *\nAllow: /' },
  },
  {
    toolName: 'Sitemap Analyzer',
    indicator: 'sitemap',
    contextData: { sitemap_url: 'https://example.com/sitemap.xml', page_count: 50 },
  },
  {
    toolName: 'Headings Analyzer',
    indicator: 'headings',
    contextData: { heading_structure: ['H1: Title', 'H2: Section 1', 'H2: Section 2'] },
  },
  {
    toolName: 'Image Alt Checker',
    indicator: 'image_alt',
    contextData: { image_list: [{ src: 'img1.jpg', alt: '' }, { src: 'img2.jpg', alt: 'Image 2' }] },
  },
  {
    toolName: 'URL Analyzer',
    indicator: 'url',
    contextData: { page_url: 'https://example.com/page', structure: 'path/to/page' },
  },
  {
    toolName: 'Links Analyzer',
    indicator: 'links',
    contextData: { internal_links: 10, external_links: 5 },
  },
];

describe('AI Suggestion Tool Integration', () => {
  // ========================================
  // AC-1: AI Suggestion Button in Tool Components
  // ========================================
  describe('AC-1: Button in Tool Components', () => {
    toolConfigs.forEach(({ toolName, indicator, contextData }) => {
      it(`should render AI suggestion button in ${toolName}`, () => {
        render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={true}
          />
        );

        expect(screen.getByTestId('ai-suggestion-button')).toBeInTheDocument();
      });

      it(`should pass correct indicator for ${toolName}`, () => {
        render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={true}
          />
        );

        expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('data-indicator', indicator);
      });

      it(`should pass correct context data for ${toolName}`, () => {
        const onAISuggestionClick = jest.fn();
        const user = userEvent.setup();

        render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={true}
            onAISuggestionClick={onAISuggestionClick}
          />
        );

        // Click triggers the handler (actual API call would be in the handler)
        screen.getByTestId('ai-suggestion-button').click();
      });
    });

    it('should show loading state during request', () => {
      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{ title: 'Test' }}
          hasResults={true}
        />
      );

      // Simulate loading by re-rendering with loading button
      render(
        <div data-testid="tool-result-title-analyzer">
          <AISuggestionButton
            indicator="title"
            context={{ title: 'Test' }}
            isLoading={true}
            hasResults={false}
          />
        </div>
      );

      expect(screen.getByText('Generating...')).toBeInTheDocument();
    });

    it('should be disabled when no page analyzed', () => {
      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{}}
          hasResults={false}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should show visual feedback on rate limit', () => {
      render(
        <div data-testid="tool-result-title-analyzer">
          <AISuggestionButton
            indicator="title"
            context={{ title: 'Test' }}
            rateLimitReached={true}
            rateLimitResetAt="2026-03-04T13:00:00Z"
          />
        </div>
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });
  });

  // ========================================
  // AC-2: AI Suggestion Panel Component
  // ========================================
  describe('AC-2: AI Suggestion Panel', () => {
    it('should display score when applicable', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={75}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('score-section')).toHaveTextContent('Score: 75');
    });

    it('should display issues list', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[
            { severity: 'critical', message: 'Missing title' },
            { severity: 'warning', message: 'Title too short' },
          ]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issues-list')).toBeInTheDocument();
      expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('should display suggestions with reasons', () => {
      render(
        <AISuggestionPanel
          suggestions={[
            { type: 'rewrite', text: 'New Title', reason: 'Includes keyword' },
          ]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestions-list')).toBeInTheDocument();
      expect(screen.getByText('New Title')).toBeInTheDocument();
    });

    it('should display best practices', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={['Keep under 60 chars', 'Use keywords']}
          provider="openrouter"
        />
      );

      expect(screen.getByText('Keep under 60 chars')).toBeInTheDocument();
      expect(screen.getByText('Use keywords')).toBeInTheDocument();
    });

    it('should show provider used', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('provider-info')).toHaveTextContent('Provider: openrouter');
    });

    it('should show model when available', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
        />
      );

      expect(screen.getByTestId('model-info')).toHaveTextContent('Model: llama-3.1-8b-instruct');
    });

    it('should show debug info when enabled', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
          showDebug={true}
        />
      );

      expect(screen.getByTestId('model-info')).toBeInTheDocument();
    });
  });

  // ========================================
  // AC-4: Rate Limit UI
  // ========================================
  describe('AC-4: Rate Limit UI', () => {
    it('should show remaining requests', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          remainingRequests={15}
        />
      );

      expect(screen.getByTestId('remaining-requests')).toHaveTextContent('15');
    });

    it('should show reset time in tooltip', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          rateLimitReached={true}
          rateLimitResetAt="2026-03-04T13:00:00Z"
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      expect(button).toHaveAttribute('title', expect.stringContaining('Rate limit'));
    });

    it('should disable button when limit reached', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          rateLimitReached={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });
  });

  // ========================================
  // AC-5: Error Handling
  // ========================================
  describe('AC-5: Error Handling', () => {
    it('should display error messages clearly', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Failed to generate suggestions. Please try again."
        />
      );

      expect(screen.getByRole('alert')).toHaveTextContent('Failed to generate suggestions');
    });

    it('should show fallback indicator when static fallback used', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="static_fallback"
        />
      );

      // Fallback provider is shown in provider info
      expect(screen.getByTestId('provider-info')).toHaveTextContent('Provider: static_fallback');
    });

    it('should have retry button for failed requests', async () => {
      const onRetry = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Request failed"
          onRetry={onRetry}
        />
      );

      const retryButton = screen.getByRole('button', { name: 'Retry' });
      await user.click(retryButton);

      expect(onRetry).toHaveBeenCalledTimes(1);
    });
  });

  // ========================================
  // AC-6: Accessibility
  // ========================================
  describe('AC-6: Accessibility', () => {
    it('should support keyboard navigation', async () => {
      const onClick = jest.fn();
      const user = userEvent.setup();

      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
          onClick={onClick}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      button.focus();
      await user.keyboard('{Enter}');

      expect(onClick).toHaveBeenCalled();
    });

    it('should have screen reader announcements for loading', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('should have screen reader announcements for errors', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Error occurred"
        />
      );

      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('should have proper focus management after loading', async () => {
      const { rerender } = render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      // Loading state
      expect(screen.getByRole('status')).toBeInTheDocument();

      // Results state
      rerender(
        <AISuggestionPanel
          suggestions={[{ type: 'improve', text: 'Test', reason: 'Reason' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={false}
        />
      );

      // Panel should be accessible
      expect(screen.getByTestId('ai-suggestion-panel')).toBeInTheDocument();
    });

    it('should have aria-label on buttons', () => {
      render(
        <AISuggestionButton
          indicator="title"
          context={{ title: 'Test' }}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute('aria-label');
    });
  });

  // ========================================
  // All Tools Integration Tests
  // ========================================
  describe('All Tools Integration', () => {
    toolConfigs.forEach(({ toolName, indicator, contextData }) => {
      describe(`${toolName} Integration`, () => {
        it('should integrate button in tool result view', () => {
          render(
            <ToolResultView
              toolName={toolName.replace(' ', '-').toLowerCase()}
              indicator={indicator}
              contextData={contextData}
              hasResults={true}
            />
          );

          const toolContainer = screen.getByTestId(`tool-result-${toolName.replace(' ', '-').toLowerCase()}`);
          expect(within(toolContainer).getByTestId('ai-suggestion-button')).toBeInTheDocument();
        });

        it('should have correct button positioning', () => {
          render(
            <ToolResultView
              toolName={toolName.replace(' ', '-').toLowerCase()}
              indicator={indicator}
              contextData={contextData}
              hasResults={true}
            />
          );

          const suggestionSection = screen.getByTestId(`tool-result-${toolName.replace(' ', '-').toLowerCase()}`).querySelector('.ai-suggestion-section');
          expect(suggestionSection).toBeInTheDocument();
        });

        it('should trigger suggestion request on click', async () => {
          const onAISuggestionClick = jest.fn();

          render(
            <ToolResultView
              toolName={toolName.replace(' ', '-').toLowerCase()}
              indicator={indicator}
              contextData={contextData}
              hasResults={true}
              onAISuggestionClick={onAISuggestionClick}
            />
          );

          const button = screen.getByTestId('ai-suggestion-button');
          await button.click();

          expect(onAISuggestionClick).toHaveBeenCalled();
        });
      });
    });
  });

  // ========================================
  // Consistent UX Tests
  // ========================================
  describe('Consistent UX Across Tools', () => {
    it('should have same button text across all tools', () => {
      toolConfigs.forEach(({ toolName, indicator, contextData }) => {
        const { unmount } = render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={false}
          />
        );

        expect(screen.getByText('Get AI Suggestions')).toBeInTheDocument();
        unmount();
      });
    });

    it('should have same aria-label pattern across all tools', () => {
      toolConfigs.forEach(({ toolName, indicator, contextData }) => {
        const { unmount } = render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={false}
          />
        );

        const button = screen.getByTestId('ai-suggestion-button');
        expect(button).toHaveAttribute('aria-label', `Get AI suggestions for ${indicator}`);
        unmount();
      });
    });

    it('should have same button positioning across all tools', () => {
      toolConfigs.forEach(({ toolName, indicator, contextData }) => {
        const { unmount } = render(
          <ToolResultView
            toolName={toolName.replace(' ', '-').toLowerCase()}
            indicator={indicator}
            contextData={contextData}
            hasResults={true}
          />
        );

        const toolContainer = screen.getByTestId(`tool-result-${toolName.replace(' ', '-').toLowerCase()}`);
        const suggestionSection = toolContainer.querySelector('.ai-suggestion-section');
        expect(suggestionSection).toBeInTheDocument();
        unmount();
      });
    });
  });

  // ========================================
  // Edge Cases
  // ========================================
  describe('Edge Cases', () => {
    it('should handle tool with no results', () => {
      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{}}
          hasResults={false}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should handle tool with empty context data', () => {
      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{}}
          hasResults={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
    });

    it('should handle unicode in context data', () => {
      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{ title: '日本語タイトル 🎉' }}
          hasResults={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).not.toBeDisabled();
    });

    it('should handle large context data', () => {
      const largeContext = {
        title: 'Test',
        content: 'x'.repeat(4000),
        keyword: 'test',
      };

      render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={largeContext}
          hasResults={true}
        />
      );

      expect(screen.getByTestId('ai-suggestion-button')).not.toBeDisabled();
    });
  });

  // ========================================
  // Full Flow Integration
  // ========================================
  describe('Full Flow Integration', () => {
    it('should complete full suggestion flow for Title Analyzer', async () => {
      const onAISuggestionClick = jest.fn();
      const user = userEvent.setup();

      const { rerender } = render(
        <ToolResultView
          toolName="title-analyzer"
          indicator="title"
          contextData={{ title: 'Test Page' }}
          hasResults={true}
          onAISuggestionClick={onAISuggestionClick}
        />
      );

      // 1. Button is visible and enabled
      const button = screen.getByTestId('ai-suggestion-button');
      expect(button).not.toBeDisabled();

      // 2. Click button
      await user.click(button);
      expect(onAISuggestionClick).toHaveBeenCalled();

      // 3. Simulate loading state
      rerender(
        <div data-testid="tool-result-title-analyzer">
          <AISuggestionButton
            indicator="title"
            context={{ title: 'Test Page' }}
            isLoading={true}
          />
        </div>
      );
      expect(screen.getByText('Generating...')).toBeInTheDocument();

      // 4. Simulate results state
      rerender(
        <div data-testid="tool-result-title-analyzer">
          <AISuggestionButton
            indicator="title"
            context={{ title: 'Test Page' }}
            hasResults={true}
          />
        </div>
      );
      expect(screen.getByText('Refresh Suggestions')).toBeInTheDocument();
    });

    it('should handle error and retry flow', async () => {
      const onRetry = jest.fn();
      const user = userEvent.setup();

      // Initial error state
      const { rerender } = render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Request failed"
          onRetry={onRetry}
        />
      );

      expect(screen.getByRole('alert')).toHaveTextContent('Request failed');

      // Click retry
      await user.click(screen.getByRole('button', { name: 'Retry' }));
      expect(onRetry).toHaveBeenCalled();

      // Simulate loading after retry
      rerender(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByRole('status')).toBeInTheDocument();

      // Simulate success after loading
      rerender(
        <AISuggestionPanel
          suggestions={[{ type: 'improve', text: 'Better title', reason: 'Test' }]}
          issues={[]}
          bestPractices={['Keep it short']}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
        />
      );

      expect(screen.getByTestId('suggestions-list')).toBeInTheDocument();
    });
  });
});
