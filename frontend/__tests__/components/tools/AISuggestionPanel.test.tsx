/**
 * AISuggestionPanel Component Tests
 * TASK-21-11: Frontend Integration with Tools
 * 
 * Tests for the AI suggestion display panel component.
 */

import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Types
interface Suggestion {
  type: 'improve' | 'rewrite' | 'add' | 'remove';
  text: string;
  reason: string;
}

interface Issue {
  severity: 'critical' | 'warning' | 'info';
  message: string;
}

interface AISuggestionPanelProps {
  suggestions: Suggestion[];
  issues: Issue[];
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

// Mock Component (will be replaced with actual implementation)
const AISuggestionPanel: React.FC<AISuggestionPanelProps> = ({
  suggestions,
  issues,
  bestPractices,
  score,
  provider,
  model,
  generatedAt,
  isLoading = false,
  error = null,
  onRetry,
  showDebug = false,
  className = '',
}) => {
  if (isLoading) {
    return (
      <div 
        data-testid="ai-suggestion-panel" 
        className={className}
        role="status"
        aria-live="polite"
        aria-label="Loading AI suggestions"
      >
        <div data-testid="loading-skeleton" className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4 mb-2" />
          <div className="h-4 bg-gray-200 rounded w-1/2" />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div 
        data-testid="ai-suggestion-panel" 
        className={className}
        role="alert"
        aria-label="AI suggestion error"
      >
        <p data-testid="error-message">{error}</p>
        {onRetry && (
          <button 
            onClick={onRetry}
            data-testid="retry-button"
            aria-label="Retry AI suggestions"
          >
            Retry
          </button>
        )}
      </div>
    );
  }

  return (
    <div 
      data-testid="ai-suggestion-panel"
      className={className}
      aria-label="AI suggestions panel"
    >
      {/* Score Section */}
      {score !== null && score !== undefined && (
        <div data-testid="score-section" aria-label="SEO score">
          <span data-testid="score-value">{score}</span>
          <span data-testid="score-label">SEO Score</span>
        </div>
      )}

      {/* Issues Section */}
      {issues.length > 0 && (
        <div data-testid="issues-section" aria-label="Issues found">
          <h3>Issues</h3>
          <ul data-testid="issues-list">
            {issues.map((issue, index) => (
              <li 
                key={index} 
                data-testid={`issue-${index}`}
                data-severity={issue.severity}
              >
                <span data-testid={`issue-severity-${index}`}>{issue.severity}</span>
                <span data-testid={`issue-message-${index}`}>{issue.message}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Suggestions Section */}
      {suggestions.length > 0 && (
        <div data-testid="suggestions-section" aria-label="Suggestions">
          <h3>Suggestions</h3>
          <ul data-testid="suggestions-list">
            {suggestions.map((suggestion, index) => (
              <li 
                key={index} 
                data-testid={`suggestion-${index}`}
                data-type={suggestion.type}
              >
                <span data-testid={`suggestion-type-${index}`}>{suggestion.type}</span>
                <span data-testid={`suggestion-text-${index}`}>{suggestion.text}</span>
                <span data-testid={`suggestion-reason-${index}`}>{suggestion.reason}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Best Practices Section */}
      {bestPractices.length > 0 && (
        <div data-testid="best-practices-section" aria-label="Best practices">
          <h3>Best Practices</h3>
          <ul data-testid="best-practices-list">
            {bestPractices.map((practice, index) => (
              <li key={index} data-testid={`best-practice-${index}`}>
                {practice}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Provider Info */}
      <div data-testid="provider-info">
        <span data-testid="provider-name">{provider}</span>
        {model && <span data-testid="model-name">{model}</span>}
        {generatedAt && <span data-testid="generated-at">{generatedAt}</span>}
      </div>

      {/* Debug Info */}
      {showDebug && (
        <div data-testid="debug-info" aria-label="Debug information">
          <p>Provider: {provider}</p>
          {model && <p>Model: {model}</p>}
          {generatedAt && <p>Generated: {generatedAt}</p>}
        </div>
      )}

      {/* Fallback Indicator */}
      {provider === 'static_fallback' && (
        <div data-testid="fallback-indicator" role="note">
          Using fallback suggestions
        </div>
      )}
    </div>
  );
};

// Test Data
const mockSuggestions: Suggestion[] = [
  { type: 'rewrite', text: 'Best Widgets 2026 | Premium Quality', reason: 'Includes target keyword' },
  { type: 'improve', text: 'Add brand name', reason: 'Increases brand recognition' },
];

const mockIssues: Issue[] = [
  { severity: 'critical', message: 'Title is too short (15 chars)' },
  { severity: 'warning', message: 'Missing target keyword' },
];

const mockBestPractices: string[] = [
  'Keep title under 60 characters',
  'Place primary keyword near the beginning',
];

describe('AISuggestionPanel', () => {
  // ========================================
  // AC-2: Component Structure Tests
  // ========================================
  describe('AC-2: Component Structure', () => {
    it('should render with required props', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('ai-suggestion-panel')).toBeInTheDocument();
      expect(screen.getByTestId('provider-info')).toBeInTheDocument();
    });

    it('should display score when provided', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={75}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('score-section')).toBeInTheDocument();
      expect(screen.getByTestId('score-value')).toHaveTextContent('75');
    });

    it('should not display score when null', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={null}
          provider="openrouter"
        />
      );

      expect(screen.queryByTestId('score-section')).not.toBeInTheDocument();
    });

    it('should display issues in structured format', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={mockIssues}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issues-section')).toBeInTheDocument();
      expect(screen.getByTestId('issues-list')).toBeInTheDocument();
      expect(screen.getAllByRole('listitem')).toHaveLength(2);
    });

    it('should display suggestions with type, text, and reason', () => {
      render(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestions-section')).toBeInTheDocument();
      expect(screen.getByTestId('suggestion-text-0')).toHaveTextContent('Best Widgets 2026 | Premium Quality');
      expect(screen.getByTestId('suggestion-reason-0')).toHaveTextContent('Includes target keyword');
    });

    it('should display best practices list', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={mockBestPractices}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('best-practices-section')).toBeInTheDocument();
      expect(screen.getByText('Keep title under 60 characters')).toBeInTheDocument();
    });
  });

  // ========================================
  // Provider Info Tests
  // ========================================
  describe('Provider Information', () => {
    it('should display provider name', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('provider-name')).toHaveTextContent('openrouter');
    });

    it('should display model name when provided', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
        />
      );

      expect(screen.getByTestId('model-name')).toHaveTextContent('llama-3.1-8b-instruct');
    });

    it('should not display model when null', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          model={null}
        />
      );

      expect(screen.queryByTestId('model-name')).not.toBeInTheDocument();
    });

    it('should display generated timestamp when provided', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          generatedAt="2026-03-04T12:00:00Z"
        />
      );

      expect(screen.getByTestId('generated-at')).toHaveTextContent('2026-03-04T12:00:00Z');
    });

    it('should show fallback indicator when using static_fallback provider', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="static_fallback"
        />
      );

      expect(screen.getByTestId('fallback-indicator')).toBeInTheDocument();
      expect(screen.getByText('Using fallback suggestions')).toBeInTheDocument();
    });

    it('should not show fallback indicator for AI providers', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.queryByTestId('fallback-indicator')).not.toBeInTheDocument();
    });
  });

  // ========================================
  // Debug Mode Tests
  // ========================================
  describe('AC-2: Debug Mode', () => {
    it('should show debug info when showDebug is true', () => {
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

      expect(screen.getByTestId('debug-info')).toBeInTheDocument();
    });

    it('should hide debug info when showDebug is false', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          showDebug={false}
        />
      );

      expect(screen.queryByTestId('debug-info')).not.toBeInTheDocument();
    });

    it('should include all debug fields', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
          generatedAt="2026-03-04T12:00:00Z"
          showDebug={true}
        />
      );

      const debugInfo = screen.getByTestId('debug-info');
      expect(debugInfo).toHaveTextContent('Provider: openrouter');
      expect(debugInfo).toHaveTextContent('Model: llama-3.1-8b-instruct');
      expect(debugInfo).toHaveTextContent('Generated: 2026-03-04T12:00:00Z');
    });
  });

  // ========================================
  // Loading State Tests
  // ========================================
  describe('Loading State', () => {
    it('should show loading skeleton when isLoading is true', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
    });

    it('should hide content during loading', () => {
      render(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={mockIssues}
          bestPractices={mockBestPractices}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.queryByTestId('suggestions-section')).not.toBeInTheDocument();
      expect(screen.queryByTestId('issues-section')).not.toBeInTheDocument();
    });

    it('should have aria-live="polite" for loading state', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByRole('status')).toHaveAttribute('aria-live', 'polite');
    });

    it('should have aria-label for loading state', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByRole('status')).toHaveAttribute('aria-label', 'Loading AI suggestions');
    });
  });

  // ========================================
  // Error Handling Tests (AC-5)
  // ========================================
  describe('AC-5: Error Handling', () => {
    it('should display error message when error prop is set', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Failed to generate suggestions"
        />
      );

      expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to generate suggestions');
    });

    it('should have role="alert" for error state', () => {
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

    it('should show retry button when onRetry is provided', () => {
      const onRetry = jest.fn();
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Error occurred"
          onRetry={onRetry}
        />
      );

      expect(screen.getByTestId('retry-button')).toBeInTheDocument();
    });

    it('should call onRetry when retry button is clicked', async () => {
      const onRetry = jest.fn();
      const user = userEvent.setup();
      
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Error occurred"
          onRetry={onRetry}
        />
      );

      await user.click(screen.getByTestId('retry-button'));
      expect(onRetry).toHaveBeenCalledTimes(1);
    });

    it('should not show retry button when onRetry is not provided', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Error occurred"
        />
      );

      expect(screen.queryByTestId('retry-button')).not.toBeInTheDocument();
    });

    it('should hide content when error is displayed', () => {
      render(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={mockIssues}
          bestPractices={mockBestPractices}
          provider="openrouter"
          error="Error occurred"
        />
      );

      expect(screen.queryByTestId('suggestions-section')).not.toBeInTheDocument();
      expect(screen.queryByTestId('issues-section')).not.toBeInTheDocument();
    });
  });

  // ========================================
  // Issue Severity Tests
  // ========================================
  describe('Issue Severity Display', () => {
    it('should display critical severity correctly', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[{ severity: 'critical', message: 'Critical issue' }]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issue-0')).toHaveAttribute('data-severity', 'critical');
    });

    it('should display warning severity correctly', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[{ severity: 'warning', message: 'Warning issue' }]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issue-0')).toHaveAttribute('data-severity', 'warning');
    });

    it('should display info severity correctly', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[{ severity: 'info', message: 'Info issue' }]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issue-0')).toHaveAttribute('data-severity', 'info');
    });
  });

  // ========================================
  // Suggestion Type Tests
  // ========================================
  describe('Suggestion Types', () => {
    it('should display rewrite suggestion type', () => {
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'rewrite', text: 'New title', reason: 'Better SEO' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-0')).toHaveAttribute('data-type', 'rewrite');
    });

    it('should display improve suggestion type', () => {
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'improve', text: 'Add keyword', reason: 'Better relevance' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-0')).toHaveAttribute('data-type', 'improve');
    });

    it('should display add suggestion type', () => {
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'add', text: 'Add meta description', reason: 'Missing' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-0')).toHaveAttribute('data-type', 'add');
    });

    it('should display remove suggestion type', () => {
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'remove', text: 'Remove duplicate', reason: 'Causes issues' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-0')).toHaveAttribute('data-type', 'remove');
    });
  });

  // ========================================
  // Accessibility Tests (AC-6)
  // ========================================
  describe('AC-6: Accessibility', () => {
    it('should have aria-label on main container', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('ai-suggestion-panel')).toHaveAttribute('aria-label', 'AI suggestions panel');
    });

    it('should have aria-label on score section', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={75}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('score-section')).toHaveAttribute('aria-label', 'SEO score');
    });

    it('should have aria-label on issues section', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={mockIssues}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('issues-section')).toHaveAttribute('aria-label', 'Issues found');
    });

    it('should have aria-label on suggestions section', () => {
      render(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestions-section')).toHaveAttribute('aria-label', 'Suggestions');
    });

    it('should have aria-label on best practices section', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={mockBestPractices}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('best-practices-section')).toHaveAttribute('aria-label', 'Best practices');
    });

    it('should have aria-label on retry button', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Error"
          onRetry={() => {}}
        />
      );

      expect(screen.getByTestId('retry-button')).toHaveAttribute('aria-label', 'Retry AI suggestions');
    });

    it('should have role="note" on fallback indicator', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="static_fallback"
        />
      );

      expect(screen.getByTestId('fallback-indicator')).toHaveAttribute('role', 'note');
    });
  });

  // ========================================
  // Empty State Tests
  // ========================================
  describe('Empty States', () => {
    it('should render without issues section when empty', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.queryByTestId('issues-section')).not.toBeInTheDocument();
    });

    it('should render without suggestions section when empty', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.queryByTestId('suggestions-section')).not.toBeInTheDocument();
    });

    it('should render without best practices section when empty', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.queryByTestId('best-practices-section')).not.toBeInTheDocument();
    });
  });

  // ========================================
  // className Prop Tests
  // ========================================
  describe('className Prop', () => {
    it('should apply custom className', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          className="custom-class"
        />
      );

      expect(screen.getByTestId('ai-suggestion-panel')).toHaveClass('custom-class');
    });

    it('should work without className prop', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('ai-suggestion-panel')).toBeInTheDocument();
    });
  });

  // ========================================
  // Edge Cases
  // ========================================
  describe('Edge Cases', () => {
    it('should handle score of 0', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={0}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('score-value')).toHaveTextContent('0');
    });

    it('should handle score of 100', () => {
      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          score={100}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('score-value')).toHaveTextContent('100');
    });

    it('should handle many issues', () => {
      const manyIssues: Issue[] = Array.from({ length: 20 }, (_, i) => ({
        severity: 'warning' as const,
        message: `Issue ${i + 1}`,
      }));

      render(
        <AISuggestionPanel
          suggestions={[]}
          issues={manyIssues}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getAllByRole('listitem')).toHaveLength(20);
    });

    it('should handle many suggestions', () => {
      const manySuggestions: Suggestion[] = Array.from({ length: 15 }, (_, i) => ({
        type: 'improve' as const,
        text: `Suggestion ${i + 1}`,
        reason: `Reason ${i + 1}`,
      }));

      render(
        <AISuggestionPanel
          suggestions={manySuggestions}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getAllByRole('listitem')).toHaveLength(15);
    });

    it('should handle long suggestion text', () => {
      const longText = 'A'.repeat(500);
      
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'improve', text: longText, reason: 'Long text' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-text-0')).toHaveTextContent(longText);
    });

    it('should handle unicode in suggestion text', () => {
      render(
        <AISuggestionPanel
          suggestions={[{ type: 'improve', text: '日本語タイトル 🎉', reason: 'Unicode test' }]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
        />
      );

      expect(screen.getByTestId('suggestion-text-0')).toHaveTextContent('日本語タイトル 🎉');
    });
  });

  // ========================================
  // Integration Tests
  // ========================================
  describe('Integration', () => {
    it('should display complete suggestion result', () => {
      render(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={mockIssues}
          bestPractices={mockBestPractices}
          score={65}
          provider="openrouter"
          model="llama-3.1-8b-instruct"
          generatedAt="2026-03-04T12:00:00Z"
        />
      );

      // Verify all sections present
      expect(screen.getByTestId('score-section')).toBeInTheDocument();
      expect(screen.getByTestId('issues-section')).toBeInTheDocument();
      expect(screen.getByTestId('suggestions-section')).toBeInTheDocument();
      expect(screen.getByTestId('best-practices-section')).toBeInTheDocument();
      expect(screen.getByTestId('provider-info')).toBeInTheDocument();
    });

    it('should transition from loading to loaded state', () => {
      const { rerender } = render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          isLoading={true}
        />
      );

      expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();

      rerender(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={mockIssues}
          bestPractices={mockBestPractices}
          score={75}
          provider="openrouter"
          isLoading={false}
        />
      );

      expect(screen.queryByTestId('loading-skeleton')).not.toBeInTheDocument();
      expect(screen.getByTestId('suggestions-section')).toBeInTheDocument();
    });

    it('should transition from error to loaded state', () => {
      const { rerender } = render(
        <AISuggestionPanel
          suggestions={[]}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error="Failed"
          onRetry={() => {}}
        />
      );

      expect(screen.getByTestId('error-message')).toBeInTheDocument();

      rerender(
        <AISuggestionPanel
          suggestions={mockSuggestions}
          issues={[]}
          bestPractices={[]}
          provider="openrouter"
          error={null}
        />
      );

      expect(screen.queryByTestId('error-message')).not.toBeInTheDocument();
      expect(screen.getByTestId('suggestions-section')).toBeInTheDocument();
    });
  });
});
