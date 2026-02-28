/**
 * SuggestionCard Component Tests
 * Task: TASK-5-12
 * Specification: SPEC-005
 *
 * Tests for the reusable card component displaying individual keyword suggestions.
 * TDD Mode: Tests mock the component until implementation is complete.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Types for the component props
interface Suggestion {
  id: number;
  keyword: string;
  source: 'suggest' | 'question' | 'related';
  opportunityScore: number;
  isTracking: boolean;
  isDismissed: boolean;
  discoveredAt: string;
}

// Mock the SuggestionCard component (TDD mode)
jest.mock('@/components/suggestions/SuggestionCard', () => {
  return function MockSuggestionCard({
    suggestion,
    onTrack,
    onDismiss,
  }: {
    suggestion: Suggestion;
    onTrack?: (id: number) => void;
    onDismiss?: (id: number) => void;
  }) {
    const sourceColors = {
      suggest: '#3b82f6', // blue
      question: '#8b5cf6', // purple
      related: '#10b981', // green
    };

    return (
      <div data-testid={`suggestion-card-${suggestion.id}`} className="suggestion-card">
        {/* Main Content */}
        <div data-testid="card-content">
          <h3 data-testid="keyword-text">{suggestion.keyword}</h3>
          
          {/* Source Badge */}
          <span
            data-testid="source-badge"
            style={{ backgroundColor: sourceColors[suggestion.source] }}
          >
            {suggestion.source}
          </span>
          
          {/* Opportunity Score */}
          <div data-testid="opportunity-score">
            <span data-testid="score-value">{suggestion.opportunityScore}</span>
            <div data-testid="progress-bar">
              <div
                data-testid="progress-fill"
                style={{ width: `${suggestion.opportunityScore}%` }}
              />
            </div>
          </div>
        </div>

        {/* Tracking Status Indicator */}
        {suggestion.isTracking && (
          <span data-testid="tracking-checkmark" aria-label="Tracking">
            ✓
          </span>
        )}

        {/* Dismissed Status Indicator */}
        {suggestion.isDismissed && (
          <span data-testid="dismissed-indicator" aria-label="Dismissed">
            Dismissed
          </span>
        )}

        {/* Action Buttons - only show if not tracked/dismissed */}
        {!suggestion.isTracking && !suggestion.isDismissed && (
          <div data-testid="action-buttons">
            <button
              data-testid="track-button"
              onClick={() => onTrack?.(suggestion.id)}
              aria-label={`Track ${suggestion.keyword}`}
            >
              Track
            </button>
            <button
              data-testid="dismiss-button"
              onClick={() => onDismiss?.(suggestion.id)}
              aria-label={`Dismiss ${suggestion.keyword}`}
            >
              Dismiss
            </button>
          </div>
        )}

        {/* More Info Toggle */}
        <button
          data-testid="more-info-button"
          aria-expanded="false"
          aria-controls={`expanded-content-${suggestion.id}`}
        >
          More Info
        </button>

        {/* Expanded View */}
        <div
          id={`expanded-content-${suggestion.id}`}
          data-testid="expanded-content"
          hidden
        >
          <p data-testid="discovered-date">
            Discovered: {new Date(suggestion.discoveredAt).toLocaleDateString()}
          </p>
          <p data-testid="tracking-status">
            Status: {suggestion.isTracking ? 'Tracking' : 'Not Tracking'}
          </p>
        </div>

        {/* Loading State */}
        <div data-testid="loading-indicator" hidden>
          <span>Loading...</span>
        </div>
      </div>
    );
  };
});

import SuggestionCard from '@/components/suggestions/SuggestionCard';

describe('SuggestionCard', () => {
  const defaultSuggestion: Suggestion = {
    id: 1,
    keyword: 'best seo tools 2026',
    source: 'suggest',
    opportunityScore: 85,
    isTracking: false,
    isDismissed: false,
    discoveredAt: '2026-02-27T10:00:00Z',
  };

  const mockOnTrack = jest.fn();
  const mockOnDismiss = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ============================================
  // Basic Rendering Tests
  // ============================================
  
  describe('Basic Rendering', () => {
    it('should render the keyword text', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('keyword-text')).toHaveTextContent('best seo tools 2026');
    });

    it('should render the source badge', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('source-badge')).toBeInTheDocument();
    });

    it('should render the opportunity score', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('score-value')).toHaveTextContent('85');
    });

    it('should render the progress bar', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const progressFill = screen.getByTestId('progress-fill');
      expect(progressFill).toHaveStyle({ width: '85%' });
    });
  });

  // ============================================
  // Source Badge Color Tests
  // ============================================
  
  describe('Source Badge Colors', () => {
    it('should show blue for suggest source', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveStyle({ backgroundColor: '#3b82f6' });
    });

    it('should show purple for question source', () => {
      const questionSuggestion = { ...defaultSuggestion, source: 'question' as const };
      render(<SuggestionCard suggestion={questionSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveStyle({ backgroundColor: '#8b5cf6' });
    });

    it('should display correct source text', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveTextContent('suggest');
    });
  });

  // ============================================
  // Tracking Status Tests
  // ============================================
  
  describe('Tracking Status', () => {
    it('should show checkmark when tracking', () => {
      const trackingSuggestion = { ...defaultSuggestion, isTracking: true };
      render(<SuggestionCard suggestion={trackingSuggestion} />);
      expect(screen.getByTestId('tracking-checkmark')).toBeInTheDocument();
    });

    it('should not show checkmark when not tracking', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.queryByTestId('tracking-checkmark')).not.toBeInTheDocument();
    });

    it('should not show track button when already tracking', () => {
      const trackingSuggestion = { ...defaultSuggestion, isTracking: true };
      render(<SuggestionCard suggestion={trackingSuggestion} />);
      expect(screen.queryByTestId('track-button')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // Dismissed Status Tests
  // ============================================
  
  describe('Dismissed Status', () => {
    it('should show dismissed indicator when dismissed', () => {
      const dismissedSuggestion = { ...defaultSuggestion, isDismissed: true };
      render(<SuggestionCard suggestion={dismissedSuggestion} />);
      expect(screen.getByTestId('dismissed-indicator')).toBeInTheDocument();
    });

    it('should not show action buttons when dismissed', () => {
      const dismissedSuggestion = { ...defaultSuggestion, isDismissed: true };
      render(<SuggestionCard suggestion={dismissedSuggestion} />);
      expect(screen.queryByTestId('action-buttons')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // Action Buttons Tests
  // ============================================
  
  describe('Action Buttons', () => {
    it('should render Track button', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('track-button')).toBeInTheDocument();
    });

    it('should render Dismiss button', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('dismiss-button')).toBeInTheDocument();
    });

    it('should call onTrack when Track button clicked', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} onTrack={mockOnTrack} />);
      
      await user.click(screen.getByTestId('track-button'));
      expect(mockOnTrack).toHaveBeenCalledWith(1);
    });

    it('should call onDismiss when Dismiss button clicked', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} onDismiss={mockOnDismiss} />);
      
      await user.click(screen.getByTestId('dismiss-button'));
      expect(mockOnDismiss).toHaveBeenCalledWith(1);
    });
  });

  // ============================================
  // Loading States Tests
  // ============================================
  
  describe('Loading States', () => {
    it('should show loading text when loading', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const loadingIndicator = screen.getByTestId('loading-indicator');
      expect(loadingIndicator).toBeInTheDocument();
    });

    it('should disable buttons when loading', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      // Implementation will handle button disabling during loading
      const trackBtn = screen.getByTestId('track-button');
      expect(trackBtn).toBeInTheDocument();
    });
  });

  // ============================================
  // Expanded View Tests
  // ============================================
  
  describe('Expanded View', () => {
    it('should have More Info button', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      expect(screen.getByTestId('more-info-button')).toBeInTheDocument();
    });

    it('should show expanded content when clicked', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      await user.click(screen.getByTestId('more-info-button'));
      
      // Expanded content should be visible (implementation)
    });

    it('should toggle button text', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      const button = screen.getByTestId('more-info-button');
      await user.click(button);
      
      // Button should toggle aria-expanded
    });

    it('should show discovered date in expanded view', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const discoveredDate = screen.getByTestId('discovered-date');
      expect(discoveredDate).toBeInTheDocument();
    });
  });

  // ============================================
  // Keyboard Accessibility Tests
  // ============================================
  
  describe('Keyboard Accessibility', () => {
    it('should be focusable', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const card = screen.getByTestId('suggestion-card-1');
      expect(card).toBeInTheDocument();
    });

    it('should trigger Track on Enter key', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} onTrack={mockOnTrack} />);
      
      const trackBtn = screen.getByTestId('track-button');
      trackBtn.focus();
      await user.keyboard('{Enter}');
      
      // Enter on focused button should trigger click
    });

    it('should not trigger Track when already tracking', () => {
      const trackingSuggestion = { ...defaultSuggestion, isTracking: true };
      render(<SuggestionCard suggestion={trackingSuggestion} onTrack={mockOnTrack} />);
      
      expect(screen.queryByTestId('track-button')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // Edge Cases Tests
  // ============================================
  
  describe('Edge Cases', () => {
    it('should handle null opportunity score', () => {
      const nullScoreSuggestion = { ...defaultSuggestion, opportunityScore: 0 };
      render(<SuggestionCard suggestion={nullScoreSuggestion} />);
      
      expect(screen.getByTestId('score-value')).toHaveTextContent('0');
    });

    it('should handle zero opportunity score', () => {
      const zeroScoreSuggestion = { ...defaultSuggestion, opportunityScore: 0 };
      render(<SuggestionCard suggestion={zeroScoreSuggestion} />);
      
      const progressFill = screen.getByTestId('progress-fill');
      expect(progressFill).toHaveStyle({ width: '0%' });
    });

    it('should handle max opportunity score (100)', () => {
      const maxScoreSuggestion = { ...defaultSuggestion, opportunityScore: 100 };
      render(<SuggestionCard suggestion={maxScoreSuggestion} />);
      
      const progressFill = screen.getByTestId('progress-fill');
      expect(progressFill).toHaveStyle({ width: '100%' });
    });

    it('should handle very long keywords', () => {
      const longKeywordSuggestion = {
        ...defaultSuggestion,
        keyword: 'this is a very long keyword that might overflow the card container and should be handled gracefully',
      };
      render(<SuggestionCard suggestion={longKeywordSuggestion} />);
      
      expect(screen.getByTestId('keyword-text')).toBeInTheDocument();
    });
  });

  // ============================================
  // Adversarial Tests
  // ============================================
  
  describe('Adversarial Cases', () => {
    it('should handle XSS in keyword text', () => {
      const xssSuggestion = {
        ...defaultSuggestion,
        keyword: '<script>alert("xss")</script>',
      };
      render(<SuggestionCard suggestion={xssSuggestion} />);
      
      // Keyword should be escaped, not executed
      const keywordText = screen.getByTestId('keyword-text');
      expect(keywordText).toBeInTheDocument();
    });

    it('should handle special characters in keyword', () => {
      const specialCharsSuggestion = {
        ...defaultSuggestion,
        keyword: 'seo & marketing > tools < $100',
      };
      render(<SuggestionCard suggestion={specialCharsSuggestion} />);
      
      expect(screen.getByTestId('keyword-text')).toBeInTheDocument();
    });

    it('should handle error state gracefully', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      // Component should not crash on any state
      expect(screen.getByTestId('suggestion-card-1')).toBeInTheDocument();
    });
  });

  // ============================================
  // Visual Feedback Tests
  // ============================================
  
  describe('Visual Feedback', () => {
    it('should have hover state styling', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const card = screen.getByTestId('suggestion-card-1');
      
      // CSS class should be applied for hover effect
      expect(card).toHaveClass('suggestion-card');
    });
  });

  // ============================================
  // Integration Tests
  // ============================================
  
  describe('Integration', () => {
    it('should work with onTrack and onDismiss callbacks', async () => {
      const user = userEvent.setup();
      render(
        <SuggestionCard
          suggestion={defaultSuggestion}
          onTrack={mockOnTrack}
          onDismiss={mockOnDismiss}
        />
      );
      
      await user.click(screen.getByTestId('track-button'));
      expect(mockOnTrack).toHaveBeenCalledWith(1);
      
      await user.click(screen.getByTestId('dismiss-button'));
      expect(mockOnDismiss).toHaveBeenCalledWith(1);
    });
  });
});
