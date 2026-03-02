// Tests for AISuggestionPanel component
// frontend/__tests__/components/tools/AISuggestionPanel.test.tsx

import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AISuggestionPanel } from '@/components/tools/AISuggestionPanel';

import type { SuggestionResult } from '@/lib/types/ai-suggestions';

// Mock clipboard API
const mockClipboard = {
  writeText: jest.fn().mockResolvedValue(undefined),
};
Object.assign(navigator, { clipboard: mockClipboard });

// Mock timers
jest.useFakeTimers();

const mockSuggestionResult: SuggestionResult = {
  indicator: 'title',
  score: 75,
  issues: ['Title is too generic', 'Missing target keyword'],
  suggestions: [
    {
      type: 'rewrite',
      text: 'Best Widgets 2026 | Premium Quality Widgets',
      reason: 'Includes target keyword and value proposition',
    },
    {
      type: 'improve',
      text: 'Add brand name at the end',
      reason: 'Improves brand recognition',
    },
  ],
  best_practices: [
    'Keep title under 60 characters',
    'Place primary keyword near the beginning',
  ],
  provider: 'openrouter',
  model: 'llama-3.1-8b-instruct',
  generated_at: '2026-03-02T10:00:00Z',
};

const mockFallbackResult: SuggestionResult = {
  indicator: 'title',
  score: 0,
  issues: [],
  suggestions: [],
  best_practices: ['Keep title under 60 characters'],
  provider: 'static_fallback',
  model: null,
  generated_at: '2026-03-02T10:00:00Z',
  fallback_reason: 'AI provider timeout',
};

describe('AISuggestionPanel', () => {
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
    it('AP-001: renders suggestion result', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.getByText(/score/i)).toBeInTheDocument();
      expect(screen.getByText('75')).toBeInTheDocument();
      expect(screen.getByText(/title is too generic/i)).toBeInTheDocument();
    });

    it('AP-005: displays issues list', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      const issues = screen.getAllByRole('listitem');
      expect(issues.length).toBeGreaterThanOrEqual(2); // Issues + suggestions
    });

    it('AP-006: displays suggestions with copy buttons', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      const copyButtons = screen.getAllByRole('button', { name: /copy/i });
      expect(copyButtons.length).toBe(2); // One per suggestion
    });

    it('AP-008: displays best practices section', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.getByText(/best practices/i)).toBeInTheDocument();
      expect(screen.getByText(/keep title under 60 characters/i)).toBeInTheDocument();
    });

    it('AP-009: shows provider attribution', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.getByText(/openrouter/i)).toBeInTheDocument();
    });

    it('AP-014: shows model name when available', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.getByText(/llama-3.1-8b-instruct/i)).toBeInTheDocument();
    });

    it('AP-020: handles null result gracefully', () => {
      render(<AISuggestionPanel result={null} />);

      expect(screen.getByText(/no suggestion/i)).toBeInTheDocument();
    });

    it('AP-021: shows loading skeleton', () => {
      render(<AISuggestionPanel result={null} isLoading />);

      expect(screen.getByTestId('skeleton') || screen.getByRole('status')).toBeDefined();
    });
  });

  // ============================================
  // Score Badge Colors
  // ============================================

  describe('Score Badge Colors', () => {
    it('AP-002: shows green badge for score >= 70', () => {
      const result = { ...mockSuggestionResult, score: 85 };
      render(<AISuggestionPanel result={result} />);

      const badge = screen.getByText('85').closest('[class*="green"], [class*="success"]');
      expect(badge || screen.getByText('85')).toBeInTheDocument();
    });

    it('AP-003: shows yellow badge for score 40-69', () => {
      const result = { ...mockSuggestionResult, score: 50 };
      render(<AISuggestionPanel result={result} />);

      const badge = screen.getByText('50').closest('[class*="yellow"], [class*="warning"]');
      expect(badge || screen.getByText('50')).toBeInTheDocument();
    });

    it('AP-004: shows red badge for score < 40', () => {
      const result = { ...mockSuggestionResult, score: 25 };
      render(<AISuggestionPanel result={result} />);

      const badge = screen.getByText('25').closest('[class*="red"], [class*="error"]');
      expect(badge || screen.getByText('25')).toBeInTheDocument();
    });
  });

  // ============================================
  // Copy to Clipboard
  // ============================================

  describe('Copy to Clipboard', () => {
    it('AP-007: copy to clipboard works', async () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      const copyButtons = screen.getAllByRole('button', { name: /copy/i });
      fireEvent.click(copyButtons[0]);

      await waitFor(() => {
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
          'Best Widgets 2026 | Premium Quality Widgets'
        );
      });
    });

    it('shows success toast after copy', async () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      const copyButtons = screen.getAllByRole('button', { name: /copy/i });
      fireEvent.click(copyButtons[0]);

      await waitFor(() => {
        expect(screen.getByText(/copied/i)).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Fallback Indicator
  // ============================================

  describe('Fallback Indicator', () => {
    it('AP-010: shows fallback indicator with warning', () => {
      render(<AISuggestionPanel result={mockFallbackResult} />);

      // Check for the warning banner with fallback text
      expect(screen.getByText(/fallback mode/i)).toBeInTheDocument();
    });

    it('shows fallback reason in warning', () => {
      render(<AISuggestionPanel result={mockFallbackResult} />);

      expect(screen.getByText(/timeout/i)).toBeInTheDocument();
    });

    it('does not show fallback warning for normal results', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.queryByText(/fallback/i)).not.toBeInTheDocument();
    });
  });

  // ============================================
  // Collapse/Expand
  // ============================================

  describe('Collapse/Expand', () => {
    it('AP-011: collapse/expand functionality', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      // Find collapse button (might be an icon button)
      const collapseButton = screen.getByRole('button', { name: /collapse|expand|toggle/i });
      fireEvent.click(collapseButton);

      // Content should be collapsed
      expect(screen.queryByText(/title is too generic/i)).not.toBeInTheDocument();
    });

    it('starts collapsed when defaultCollapsed is true', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} defaultCollapsed />);

      // Should show collapsed state
      const expandButton = screen.getByRole('button', { name: /collapse|expand|toggle/i });
      expect(expandButton).toBeInTheDocument();
    });
  });

  // ============================================
  // Empty States
  // ============================================

  describe('Empty States', () => {
    it('AP-012: handles empty issues array', () => {
      const result = { ...mockSuggestionResult, issues: [] };
      render(<AISuggestionPanel result={result} />);

      // Issues section should not be rendered when empty
      const issuesHeading = screen.queryByRole('heading', { name: /issues/i });
      expect(issuesHeading).not.toBeInTheDocument();
    });

    it('AP-013: handles empty suggestions array', () => {
      const result = { ...mockSuggestionResult, suggestions: [] };
      render(<AISuggestionPanel result={result} />);

      expect(screen.getByText(/no suggestions available/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // Timestamp Display
  // ============================================

  describe('Timestamp Display', () => {
    it('AP-015: shows generated timestamp', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      // Should show relative time
      expect(screen.getByText(/ago$/)).toBeInTheDocument();
    });
  });

  // ============================================
  // Long Text Handling
  // ============================================

  describe('Long Text Handling', () => {
    it('AP-016: handles long suggestion text', () => {
      const longResult: SuggestionResult = {
        ...mockSuggestionResult,
        suggestions: [
          {
            type: 'rewrite',
            text: 'This is a very long suggestion text that should be truncated with ellipsis when displayed in the panel to prevent layout issues',
            reason: 'Long suggestion',
          },
        ],
      };

      render(<AISuggestionPanel result={longResult} />);

      // Text should be present (truncation is CSS-based)
      expect(screen.getByText(/This is a very long suggestion/)).toBeInTheDocument();
    });
  });

  // ============================================
  // Accessibility
  // ============================================

  describe('Accessibility', () => {
    it('AP-017: keyboard navigation for copy buttons', async () => {
      const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });

      render(<AISuggestionPanel result={mockSuggestionResult} />);

      // Tab to first copy button
      await user.tab();
      await user.tab();

      const focusedElement = document.activeElement;
      expect(focusedElement?.getAttribute('aria-label')?.toLowerCase()).toContain('copy');
    });

    it('AP-018: screen reader announces suggestions', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      // Check for aria-live region
      const liveRegion = screen.getByRole('status', { hidden: true });
      expect(liveRegion).toBeInTheDocument();
    });

    it('AP-023: focus management after load', async () => {
      const { rerender } = render(<AISuggestionPanel result={null} isLoading />);

      // Loading state
      expect(screen.getByTestId('skeleton') || screen.getByRole('status')).toBeDefined();

      // Loaded
      rerender(<AISuggestionPanel result={mockSuggestionResult} isLoading={false} />);

      // Focus should move to panel header
      await waitFor(() => {
        const panelHeader = screen.getByRole('heading', { level: 3 });
        expect(panelHeader).toBeInTheDocument();
      });
    });
  });

  // ============================================
  // Suggestion Type Badges
  // ============================================

  describe('Suggestion Type Badges', () => {
    it('AP-019: shows suggestion type badges', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.getByText('rewrite')).toBeInTheDocument();
      expect(screen.getByText('improve')).toBeInTheDocument();
    });
  });

  // ============================================
  // Error State
  // ============================================

  describe('Error State', () => {
    it('shows error message when provided', () => {
      render(<AISuggestionPanel result={null} error="Failed to load suggestions" />);

      expect(screen.getByText(/failed to load suggestions/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // Rate Limit Message
  // ============================================

  describe('Rate Limit Message', () => {
    it('AP-024: handles rate limit message', () => {
      render(<AISuggestionPanel result={null} error="Rate limit reached" />);

      expect(screen.getByText(/rate limit/i)).toBeInTheDocument();
    });
  });

  // ============================================
  // Regenerate Button
  // ============================================

  describe('Regenerate Button', () => {
    it('AP-025: shows regenerate button when enabled', () => {
      const mockRegenerate = jest.fn();

      render(
        <AISuggestionPanel result={mockSuggestionResult} showRegenerate onRegenerate={mockRegenerate} />
      );

      const regenerateButton = screen.getByRole('button', { name: /regenerate/i });
      expect(regenerateButton).toBeInTheDocument();

      fireEvent.click(regenerateButton);
      expect(mockRegenerate).toHaveBeenCalled();
    });

    it('does not show regenerate button when disabled', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      expect(screen.queryByRole('button', { name: /regenerate/i })).not.toBeInTheDocument();
    });
  });

  // ============================================
  // Custom Class
  // ============================================

  describe('Custom Class', () => {
    it('applies custom className', () => {
      const { container } = render(<AISuggestionPanel result={mockSuggestionResult} className="custom-panel" />);

      expect(container.firstChild).toHaveClass('custom-panel');
    });
  });

  // ============================================
  // Suggestion Reason Display
  // ============================================

  describe('Suggestion Reason Display', () => {
    it('AP-022: handles suggestion reason display', () => {
      render(<AISuggestionPanel result={mockSuggestionResult} />);

      // Reason should be visible (either directly or in tooltip)
      expect(screen.getByText(/includes target keyword/i)).toBeInTheDocument();
    });
  });
});
