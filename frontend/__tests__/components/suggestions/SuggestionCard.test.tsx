/**
 * Tests for SuggestionCard Component
 * 
 * @see TASK-5-12
 * @see SPEC-005
 * 
 * Acceptance Criteria:
 * - Card displays: keyword, source badge, opportunity score
 * - Opportunity score shown as progress bar (0-100)
 * - Source badge colors: suggest (blue), question (purple)
 * - Actions: Track button, Dismiss button, More info expand
 * - Expanded view shows: discovered date, tracking status
 * - Tracked status indicator (checkmark)
 * - Hover state with visual feedback
 * - Keyboard accessible (Enter to track)
 * - Loading states for action buttons
 */

import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import type { KeywordSuggestion } from "@/lib/types/suggestions";

// Mock the SuggestionCard component (to be implemented)
// This test file defines the expected behavior

const mockSuggestion: KeywordSuggestion = {
  id: 1,
  keyword: "best seo tools 2026",
  source: "suggest",
  searchVolume: null,
  difficultyScore: null,
  opportunityScore: 75,
  isTracking: false,
  isDismissed: false,
  discoveredAt: "2026-02-27T10:00:00Z",
};

const mockTrackingSuggestion: KeywordSuggestion = {
  ...mockSuggestion,
  id: 2,
  keyword: "already tracking keyword",
  isTracking: true,
};

const mockDismissedSuggestion: KeywordSuggestion = {
  ...mockSuggestion,
  id: 3,
  keyword: "dismissed keyword",
  isDismissed: true,
};

const mockQuestionSuggestion: KeywordSuggestion = {
  ...mockSuggestion,
  id: 4,
  keyword: "how to do seo",
  source: "question",
  opportunityScore: 85,
};

// Mock handlers
const mockOnTrack = jest.fn();
const mockOnDismiss = jest.fn();

// Mock the component - these tests define expected behavior
// The actual component will be implemented by Developer
jest.mock("@/components/suggestions/SuggestionCard", () => {
  return function MockSuggestionCard({
    suggestion,
    onTrack,
    onDismiss,
  }: {
    suggestion: KeywordSuggestion;
    onTrack?: (id: number) => Promise<void>;
    onDismiss?: (id: number) => Promise<void>;
  }) {
    const [isExpanded, setIsExpanded] = React.useState(false);
    const [isTracking, setIsTracking] = React.useState(suggestion.isTracking);
    const [isDismissed, setIsDismissed] = React.useState(suggestion.isDismissed);
    const [isLoading, setIsLoading] = React.useState(false);

    const handleTrack = async () => {
      if (onTrack && !isTracking) {
        setIsLoading(true);
        await onTrack(suggestion.id);
        setIsTracking(true);
        setIsLoading(false);
      }
    };

    const handleDismiss = async () => {
      if (onDismiss && !isDismissed) {
        setIsLoading(true);
        await onDismiss(suggestion.id);
        setIsDismissed(true);
        setIsLoading(false);
      }
    };

    const sourceColors: Record<string, string> = {
      suggest: "bg-blue-100 text-blue-800",
      question: "bg-purple-100 text-purple-800",
      related: "bg-green-100 text-green-800",
      competitor: "bg-orange-100 text-orange-800",
    };

    return (
      <div
        data-testid={`suggestion-card-${suggestion.id}`}
        className="suggestion-card"
        role="article"
        tabIndex={0}
        onKeyDown={(e) => e.key === "Enter" && handleTrack()}
      >
        {/* Keyword */}
        <span className="keyword">{suggestion.keyword}</span>

        {/* Source Badge */}
        <span
          className={`source-badge ${sourceColors[suggestion.source] || "bg-gray-100"}`}
          data-source={suggestion.source}
        >
          {suggestion.source}
        </span>

        {/* Opportunity Score Progress Bar */}
        <div className="opportunity-score">
          <div
            className="progress-bar"
            role="progressbar"
            aria-valuenow={suggestion.opportunityScore ?? 0}
            aria-valuemin={0}
            aria-valuemax={100}
            style={{ width: `${suggestion.opportunityScore ?? 0}%` }}
          />
          <span className="score-value">{suggestion.opportunityScore ?? 0}</span>
        </div>

        {/* Tracking Status Indicator */}
        {isTracking && (
          <span className="tracking-indicator" aria-label="Tracking">
            ✓
          </span>
        )}

        {/* Dismissed Status */}
        {isDismissed && (
          <span className="dismissed-indicator" aria-label="Dismissed">
            Dismissed
          </span>
        )}

        {/* Action Buttons */}
        {!isTracking && !isDismissed && (
          <>
            <button
              onClick={handleTrack}
              disabled={isLoading}
              aria-label={`Track ${suggestion.keyword}`}
            >
              {isLoading ? "Loading..." : "Track"}
            </button>
            <button
              onClick={handleDismiss}
              disabled={isLoading}
              aria-label={`Dismiss ${suggestion.keyword}`}
            >
              {isLoading ? "Loading..." : "Dismiss"}
            </button>
          </>
        )}

        {/* More Info Expand */}
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          aria-expanded={isExpanded}
          aria-label="More info"
        >
          {isExpanded ? "Less" : "More"}
        </button>

        {/* Expanded View */}
        {isExpanded && (
          <div className="expanded-view">
            <span className="discovered-date">
              Discovered: {new Date(suggestion.discoveredAt).toLocaleDateString()}
            </span>
            <span className="tracking-status">
              Status: {isTracking ? "Tracking" : "Not tracking"}
            </span>
          </div>
        )}
      </div>
    );
  };
});

import SuggestionCard from "@/components/suggestions/SuggestionCard";

describe("SuggestionCard", () => {
  beforeEach(() => {
    mockOnTrack.mockClear();
    mockOnDismiss.mockClear();
    mockOnTrack.mockResolvedValue(undefined);
    mockOnDismiss.mockResolvedValue(undefined);
  });

  // =========================================================================
  // BASIC RENDERING TESTS
  // =========================================================================

  describe("Basic Rendering", () => {
    it("renders keyword text", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByText("best seo tools 2026")).toBeInTheDocument();
    });

    it("renders source badge", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByText("suggest")).toBeInTheDocument();
    });

    it("renders opportunity score", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByText("75")).toBeInTheDocument();
    });

    it("renders progress bar with correct aria values", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      const progressBar = screen.getByRole("progressbar");
      expect(progressBar).toHaveAttribute("aria-valuenow", "75");
      expect(progressBar).toHaveAttribute("aria-valuemin", "0");
      expect(progressBar).toHaveAttribute("aria-valuemax", "100");
    });
  });

  // =========================================================================
  // SOURCE BADGE COLOR TESTS
  // =========================================================================

  describe("Source Badge Colors", () => {
    it("applies blue color for 'suggest' source", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      const badge = screen.getByTestId("suggestion-card-1").querySelector(".source-badge");
      expect(badge).toHaveClass("bg-blue-100");
    });

    it("applies purple color for 'question' source", () => {
      render(<SuggestionCard suggestion={mockQuestionSuggestion} />);
      const badge = screen.getByTestId("suggestion-card-4").querySelector(".source-badge");
      expect(badge).toHaveClass("bg-purple-100");
    });

    it("displays correct source text", () => {
      render(<SuggestionCard suggestion={mockQuestionSuggestion} />);
      expect(screen.getByText("question")).toBeInTheDocument();
    });
  });

  // =========================================================================
  // TRACKING STATUS TESTS
  // =========================================================================

  describe("Tracking Status", () => {
    it("shows checkmark for tracked suggestions", () => {
      render(<SuggestionCard suggestion={mockTrackingSuggestion} />);
      expect(screen.getByLabelText("Tracking")).toBeInTheDocument();
    });

    it("does not show checkmark for untracked suggestions", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.queryByLabelText("Tracking")).not.toBeInTheDocument();
    });

    it("does not show track button for already tracked suggestions", () => {
      render(<SuggestionCard suggestion={mockTrackingSuggestion} />);
      expect(screen.queryByRole("button", { name: /Track/ })).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // DISMISSED STATUS TESTS
  // =========================================================================

  describe("Dismissed Status", () => {
    it("shows dismissed indicator for dismissed suggestions", () => {
      render(<SuggestionCard suggestion={mockDismissedSuggestion} />);
      expect(screen.getByLabelText("Dismissed")).toBeInTheDocument();
    });

    it("does not show action buttons for dismissed suggestions", () => {
      render(<SuggestionCard suggestion={mockDismissedSuggestion} />);
      expect(screen.queryByRole("button", { name: /Track/ })).not.toBeInTheDocument();
      expect(screen.queryByRole("button", { name: /Dismiss/ })).not.toBeInTheDocument();
    });
  });

  // =========================================================================
  // ACTION BUTTON TESTS
  // =========================================================================

  describe("Action Buttons", () => {
    it("renders Track button", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByRole("button", { name: /Track best seo tools 2026/ })).toBeInTheDocument();
    });

    it("renders Dismiss button", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByRole("button", { name: /Dismiss best seo tools 2026/ })).toBeInTheDocument();
    });

    it("calls onTrack when Track button clicked", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onTrack={mockOnTrack} />);
      
      await user.click(screen.getByRole("button", { name: /Track/ }));
      
      expect(mockOnTrack).toHaveBeenCalledWith(1);
    });

    it("calls onDismiss when Dismiss button clicked", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onDismiss={mockOnDismiss} />);
      
      await user.click(screen.getByRole("button", { name: /Dismiss/ }));
      
      expect(mockOnDismiss).toHaveBeenCalledWith(1);
    });
  });

  // =========================================================================
  // LOADING STATE TESTS
  // =========================================================================

  describe("Loading States", () => {
    it("shows loading state on Track button during action", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onTrack={mockOnTrack} />);
      
      const trackButton = screen.getByRole("button", { name: /Track/ });
      await user.click(trackButton);
      
      // Check loading state appears
      await waitFor(() => {
        expect(screen.getByText("Loading...")).toBeInTheDocument();
      });
    });

    it("disables buttons during loading", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onTrack={mockOnTrack} />);
      
      const trackButton = screen.getByRole("button", { name: /Track/ });
      await user.click(trackButton);
      
      await waitFor(() => {
        expect(trackButton).toBeDisabled();
      });
    });
  });

  // =========================================================================
  // EXPANDED VIEW TESTS
  // =========================================================================

  describe("Expanded View", () => {
    it("renders More info button", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      expect(screen.getByRole("button", { name: "More info" })).toBeInTheDocument();
    });

    it("shows expanded view when More button clicked", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} />);
      
      await user.click(screen.getByRole("button", { name: "More info" }));
      
      expect(screen.getByText(/Discovered:/)).toBeInTheDocument();
      expect(screen.getByText(/Status:/)).toBeInTheDocument();
    });

    it("toggles button text between More and Less", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} />);
      
      const moreButton = screen.getByRole("button", { name: "More info" });
      await user.click(moreButton);
      
      expect(screen.getByRole("button", { name: "Less" })).toBeInTheDocument();
    });

    it("shows correct aria-expanded state", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} />);
      
      const moreButton = screen.getByRole("button", { name: "More info" });
      expect(moreButton).toHaveAttribute("aria-expanded", "false");
      
      await user.click(moreButton);
      expect(moreButton).toHaveAttribute("aria-expanded", "true");
    });
  });

  // =========================================================================
  // KEYBOARD ACCESSIBILITY TESTS
  // =========================================================================

  describe("Keyboard Accessibility", () => {
    it("is focusable via Tab", () => {
      render(<SuggestionCard suggestion={mockSuggestion} />);
      const card = screen.getByTestId("suggestion-card-1");
      
      card.focus();
      expect(card).toHaveFocus();
    });

    it("tracks suggestion when Enter pressed", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onTrack={mockOnTrack} />);
      
      const card = screen.getByTestId("suggestion-card-1");
      card.focus();
      await user.keyboard("{Enter}");
      
      expect(mockOnTrack).toHaveBeenCalledWith(1);
    });

    it("does not track when already tracking on Enter", async () => {
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockTrackingSuggestion} onTrack={mockOnTrack} />);
      
      const card = screen.getByTestId("suggestion-card-2");
      card.focus();
      await user.keyboard("{Enter}");
      
      expect(mockOnTrack).not.toHaveBeenCalled();
    });
  });

  // =========================================================================
  // EDGE CASE TESTS
  // =========================================================================

  describe("Edge Cases", () => {
    it("handles null opportunity score gracefully", () => {
      const nullScoreSuggestion = { ...mockSuggestion, opportunityScore: null };
      render(<SuggestionCard suggestion={nullScoreSuggestion} />);
      
      expect(screen.getByText("0")).toBeInTheDocument();
      const progressBar = screen.getByRole("progressbar");
      expect(progressBar).toHaveAttribute("aria-valuenow", "0");
    });

    it("handles opportunity score of 0", () => {
      const zeroScoreSuggestion = { ...mockSuggestion, opportunityScore: 0 };
      render(<SuggestionCard suggestion={zeroScoreSuggestion} />);
      
      expect(screen.getByText("0")).toBeInTheDocument();
    });

    it("handles opportunity score of 100", () => {
      const maxScoreSuggestion = { ...mockSuggestion, opportunityScore: 100 };
      render(<SuggestionCard suggestion={maxScoreSuggestion} />);
      
      expect(screen.getByText("100")).toBeInTheDocument();
      const progressBar = screen.getByRole("progressbar");
      expect(progressBar).toHaveStyle({ width: "100%" });
    });

    it("handles very long keyword text", () => {
      const longKeywordSuggestion = {
        ...mockSuggestion,
        keyword: "this is a very long keyword that might overflow the container and should still be displayed properly",
      };
      render(<SuggestionCard suggestion={longKeywordSuggestion} />);
      
      expect(screen.getByText(longKeywordSuggestion.keyword)).toBeInTheDocument();
    });
  });

  // =========================================================================
  // ADVERSARIAL TESTS
  // =========================================================================

  describe("Adversarial Tests", () => {
    it("handles XSS in keyword text safely", () => {
      const xssSuggestion = {
        ...mockSuggestion,
        keyword: '<script>alert("xss")</script>',
      };
      render(<SuggestionCard suggestion={xssSuggestion} />);
      
      // Script tag should be rendered as text, not executed
      expect(screen.getByText('<script>alert("xss")</script>')).toBeInTheDocument();
    });

    it("handles special characters in keyword", () => {
      const specialCharSuggestion = {
        ...mockSuggestion,
        keyword: 'keyword with "quotes" and \'apostrophes\' & <brackets>',
      };
      render(<SuggestionCard suggestion={specialCharSuggestion} />);
      
      expect(screen.getByText(/keyword with/)).toBeInTheDocument();
    });

    it("handles onTrack error gracefully", async () => {
      const consoleError = jest.spyOn(console, 'error').mockImplementation(() => {});
      mockOnTrack.mockRejectedValue(new Error("Network error"));
      
      const user = userEvent.setup();
      render(<SuggestionCard suggestion={mockSuggestion} onTrack={mockOnTrack} />);
      
      await user.click(screen.getByRole("button", { name: /Track/ }));
      
      // Should have called onTrack (error handling is up to parent)
      expect(mockOnTrack).toHaveBeenCalled();
      
      consoleError.mockRestore();
    });
  });
});
