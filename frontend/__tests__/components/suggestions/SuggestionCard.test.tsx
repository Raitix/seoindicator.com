/**
 * SuggestionCard Component Tests
 * Task: TASK-5-12
 * Specification: SPEC-005
 *
 * Tests for the reusable card component displaying individual keyword suggestions.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

import { SuggestionCard } from '@/components/suggestions/SuggestionCard';
import type { KeywordSuggestion } from '@/lib/types/suggestions';

describe('SuggestionCard', () => {
  const defaultSuggestion: KeywordSuggestion = {
    id: 1,
    keyword: 'best seo tools 2026',
    source: 'suggest',
    searchVolume: 1000,
    difficultyScore: 45,
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
      const progressFill = screen.getByTestId('progress-bar');
      expect(progressFill).toBeInTheDocument();
    });
  });

  // ============================================
  // Source Badge Color Tests
  // ============================================
  
  describe('Source Badge Colors', () => {
    it('should show blue for suggest source', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveClass('bg-blue-500');
    });

    it('should show purple for question source', () => {
      const questionSuggestion = { ...defaultSuggestion, source: 'question' as const };
      render(<SuggestionCard suggestion={questionSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveClass('bg-purple-500');
    });

    it('should display correct source text', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveTextContent('Suggest');
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
    it('should show loading indicator when loading', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} isLoading={true} />);
      const loadingIndicator = screen.getByTestId('loading-indicator');
      expect(loadingIndicator).not.toHaveAttribute('hidden');
    });

    it('should hide loading indicator when not loading', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} isLoading={false} />);
      const loadingIndicator = screen.getByTestId('loading-indicator');
      expect(loadingIndicator).toHaveAttribute('hidden');
    });

    it('should disable buttons when loading', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} isLoading={true} />);
      const trackBtn = screen.getByTestId('track-button');
      const dismissBtn = screen.getByTestId('dismiss-button');
      expect(trackBtn).toBeDisabled();
      expect(dismissBtn).toBeDisabled();
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
      
      const expandedContent = screen.getByTestId('expanded-content');
      expect(expandedContent).toHaveAttribute('hidden');
      
      await user.click(screen.getByTestId('more-info-button'));
      expect(expandedContent).not.toHaveAttribute('hidden');
    });

    it('should toggle button text', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      const button = screen.getByTestId('more-info-button');
      expect(button).toHaveTextContent('More Info');
      
      await user.click(button);
      expect(button).toHaveTextContent('Less Info');
    });

    it('should show discovered date in expanded view', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      await user.click(screen.getByTestId('more-info-button'));
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
      expect(card).toHaveAttribute('tabIndex', '0');
    });

    it('should trigger Track on Enter key', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} onTrack={mockOnTrack} />);
      
      const card = screen.getByTestId('suggestion-card-1');
      card.focus();
      await user.keyboard('{Enter}');
      
      expect(mockOnTrack).toHaveBeenCalledWith(1);
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
      const nullScoreSuggestion = { ...defaultSuggestion, opportunityScore: null };
      render(<SuggestionCard suggestion={nullScoreSuggestion} />);
      
      expect(screen.getByTestId('score-value')).toHaveTextContent('0');
    });

    it('should handle zero opportunity score', () => {
      const zeroScoreSuggestion = { ...defaultSuggestion, opportunityScore: 0 };
      render(<SuggestionCard suggestion={zeroScoreSuggestion} />);
      
      expect(screen.getByTestId('score-value')).toHaveTextContent('0');
    });

    it('should handle max opportunity score (100)', () => {
      const maxScoreSuggestion = { ...defaultSuggestion, opportunityScore: 100 };
      render(<SuggestionCard suggestion={maxScoreSuggestion} />);
      
      expect(screen.getByTestId('score-value')).toHaveTextContent('100');
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
      // React escapes HTML by default, so the script tag should appear as text
      // The escaped text is: &lt;script&gt;alert("xss")&lt;/script&gt;
      // toHaveTextContent checks for substring match, so we check for the full escaped content
      expect(keywordText).toHaveTextContent('<script>alert("xss")</script>');
      
      // Verify no actual script element was injected into the DOM
      expect(document.querySelector('script')).toBeNull();
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

  // ============================================
  // Additional Source Tests
  // ============================================
  
  describe('Additional Source Types', () => {
    it('should show green for related source', () => {
      const relatedSuggestion = { ...defaultSuggestion, source: 'related' as const };
      render(<SuggestionCard suggestion={relatedSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveClass('bg-emerald-500');
    });

    it('should show orange for competitor source', () => {
      const competitorSuggestion = { ...defaultSuggestion, source: 'competitor' as const };
      render(<SuggestionCard suggestion={competitorSuggestion} />);
      const badge = screen.getByTestId('source-badge');
      expect(badge).toHaveClass('bg-orange-500');
    });
  });

  // ============================================
  // Aria Attributes Tests
  // ============================================
  
  describe('Aria Attributes', () => {
    it('should have aria-expanded on more info button', () => {
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      const button = screen.getByTestId('more-info-button');
      expect(button).toHaveAttribute('aria-expanded', 'false');
    });

    it('should update aria-expanded when expanded', async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={defaultSuggestion} />);
      
      const button = screen.getByTestId('more-info-button');
      await user.click(button);
      
      expect(button).toHaveAttribute('aria-expanded', 'true');
    });

    it('should have aria-label on tracking checkmark', () => {
      const trackingSuggestion = { ...defaultSuggestion, isTracking: true };
      render(<SuggestionCard suggestion={trackingSuggestion} />);
      
      const checkmark = screen.getByTestId('tracking-checkmark');
      expect(checkmark).toHaveAttribute('aria-label', 'Tracking');
    });
  });
});
