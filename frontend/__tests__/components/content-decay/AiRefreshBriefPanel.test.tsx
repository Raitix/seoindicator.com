/**
 * Tests for AiRefreshBriefPanel Component
 * Task: TASK-36-12
 * Reference: TESTS/TEST-36-12.md
 */

import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi, beforeEach } from "vitest";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  Sparkles: () => <span data-testid="icon-sparkles">Sparkles</span>,
  AlertTriangle: () => <span data-testid="icon-alert-triangle">AlertTriangle</span>,
  ChevronDown: () => <span data-testid="icon-chevron-down">ChevronDown</span>,
  ChevronUp: () => <span data-testid="icon-chevron-up">ChevronUp</span>,
  Copy: () => <span data-testid="icon-copy">Copy</span>,
  RefreshCw: () => <span data-testid="icon-refresh">RefreshCw</span>,
  Loader2: () => <span data-testid="icon-loader">Loader2</span>,
  FileText: () => <span data-testid="icon-file-text">FileText</span>,
  Zap: () => <span data-testid="icon-zap">Zap</span>,
}));

// Mock clipboard API
const mockClipboardWrite = vi.fn();
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWrite,
  },
});

// Import types
import type { AiRefreshBrief, AiRefreshSuggestion } from "@/lib/types/content-decay";

// Mock component - will be replaced with real implementation
const MockAiRefreshBriefPanel: React.FC<{
  brief: AiRefreshBrief | null;
  onRequestBrief?: () => void;
  onRegenerate?: () => void;
  isLoading?: boolean;
  isRegenerating?: boolean;
  error?: string | null;
  className?: string;
}> = ({ brief, onRequestBrief, onRegenerate, isLoading, isRegenerating, error, className }) => {
  const [expandedSuggestions, setExpandedSuggestions] = React.useState<Set<string>>(new Set());
  const [copiedId, setCopiedId] = React.useState<string | null>(null);

  const toggleSuggestion = (id: string) => {
    setExpandedSuggestions((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const handleCopy = async (id: string, text: string) => {
    await navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const getPriorityColor = (priority: string): string => {
    switch (priority) {
      case "high":
        return "red";
      case "medium":
        return "yellow";
      case "low":
        return "gray";
      default:
        return "gray";
    }
  };

  if (isLoading) {
    return (
      <div data-testid="brief-loading" className={className}>
        <div data-testid="skeleton-brief" />
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid="brief-error" className={className}>
        <span>{error}</span>
      </div>
    );
  }

  if (!brief) {
    return (
      <div data-testid="brief-empty" className={className}>
        <p>No AI brief generated yet</p>
        <button
          data-testid="request-brief-btn"
          onClick={onRequestBrief}
          aria-label="Request AI brief"
        >
          <span data-testid="icon-sparkles">Sparkles</span>
          Request AI Brief
        </button>
      </div>
    );
  }

  return (
    <div
      data-testid="ai-refresh-brief-panel"
      className={className}
      role="region"
      aria-label="AI refresh brief"
    >
      {/* Warning Label */}
      <div data-testid="warning-label" role="alert">
        <span data-testid="icon-alert-triangle">AlertTriangle</span>
        AI-generated, verify before use
      </div>

      {/* Generated Timestamp */}
      <span data-testid="generated-at">Generated: {brief.generatedAt}</span>

      {/* Suggestions */}
      <div data-testid="suggestions-container">
        {brief.suggestions.map((suggestion) => (
          <div key={suggestion.id} data-testid={`suggestion-${suggestion.id}`}>
            {/* Suggestion Header */}
            <div data-testid={`suggestion-header-${suggestion.id}`}>
              <span data-testid="icon-file-text">FileText</span>
              <span data-testid={`suggestion-title-${suggestion.id}`}>{suggestion.title}</span>
              <span
                data-testid={`suggestion-priority-${suggestion.id}`}
                data-priority={suggestion.priority}
                className={`priority-${getPriorityColor(suggestion.priority)}`}
              >
                {suggestion.priority}
              </span>
              <span data-testid={`suggestion-category-${suggestion.id}`}>{suggestion.category}</span>
            </div>

            {/* Description */}
            <p data-testid={`suggestion-description-${suggestion.id}`}>{suggestion.description}</p>

            {/* Expand/Collapse Button */}
            <button
              data-testid={`toggle-details-${suggestion.id}`}
              onClick={() => toggleSuggestion(suggestion.id)}
              aria-expanded={expandedSuggestions.has(suggestion.id)}
              aria-label={expandedSuggestions.has(suggestion.id) ? "Collapse details" : "Expand details"}
            >
              {expandedSuggestions.has(suggestion.id) ? (
                <span data-testid="icon-chevron-up">ChevronUp</span>
              ) : (
                <span data-testid="icon-chevron-down">ChevronDown</span>
              )}
            </button>

            {/* Details (if expanded) */}
            {expandedSuggestions.has(suggestion.id) && suggestion.details && (
              <ul data-testid={`suggestion-details-${suggestion.id}`}>
                {suggestion.details.map((detail, i) => (
                  <li key={i}>{detail}</li>
                ))}
              </ul>
            )}

            {/* Copy Button */}
            <button
              data-testid={`copy-btn-${suggestion.id}`}
              onClick={() =>
                handleCopy(suggestion.id, `${suggestion.title}\n\n${suggestion.description}`)
              }
              aria-label="Copy suggestion"
            >
              <span data-testid="icon-copy">Copy</span>
              {copiedId === suggestion.id && <span data-testid="copied-indicator">Copied!</span>}
            </button>
          </div>
        ))}
      </div>

      {/* Disclaimer */}
      <div data-testid="disclaimer">{brief.disclaimer}</div>

      {/* Regenerate Button */}
      <button
        data-testid="regenerate-btn"
        onClick={onRegenerate}
        disabled={isRegenerating}
        aria-label="Regenerate brief"
      >
        {isRegenerating ? (
          <>
            <span data-testid="icon-loader">Loader2</span>
            Regenerating...
          </>
        ) : (
          <>
            <span data-testid="icon-refresh">RefreshCw</span>
            Regenerate Brief
          </>
        )}
      </button>
    </div>
  );
};

// Test Data
const mockBrief: AiRefreshBrief = {
  id: "brief-1",
  candidateId: 1,
  generatedAt: "2026-03-10T10:00:00Z",
  suggestions: [
    {
      id: "sug-1",
      title: "Improve Title Tag",
      description: "The current title lacks emotional triggers. Consider adding power words.",
      priority: "high",
      category: "On-Page",
      details: [
        "Add numbers or statistics",
        "Include current year",
        "Use power words like 'Complete', 'Ultimate', 'Essential'",
      ],
    },
    {
      id: "sug-2",
      title: "Add FAQ Section",
      description: "Adding a FAQ section can improve AI visibility and capture featured snippets.",
      priority: "medium",
      category: "Content",
    },
    {
      id: "sug-3",
      title: "Update Statistics",
      description: "Some statistics in the article are outdated. Refresh with current data.",
      priority: "low",
      category: "Content",
      details: ["Replace 2023 data with 2024 data", "Add source citations"],
    },
  ],
  disclaimer: "AI-generated suggestions. Verify relevance before implementation.",
};

describe("AiRefreshBriefPanel", () => {
  const mockOnRequestBrief = vi.fn();
  const mockOnRegenerate = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    mockClipboardWrite.mockResolvedValue(undefined);
  });

  // ABP-01: Renders brief content
  it("renders all suggestions when brief is provided", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("ai-refresh-brief-panel")).toBeInTheDocument();
    expect(screen.getByTestId("suggestion-sug-1")).toBeInTheDocument();
    expect(screen.getByTestId("suggestion-sug-2")).toBeInTheDocument();
    expect(screen.getByTestId("suggestion-sug-3")).toBeInTheDocument();
  });

  // ABP-02: Shows warning label
  it("shows warning label 'AI-generated, verify before use'", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("warning-label")).toHaveTextContent(
      "AI-generated, verify before use"
    );
    expect(screen.getByTestId("icon-alert-triangle")).toBeInTheDocument();
  });

  // ABP-03: Shows suggestion cards
  it("shows a card for each suggestion", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("suggestion-sug-1")).toBeInTheDocument();
    expect(screen.getByTestId("suggestion-sug-2")).toBeInTheDocument();
    expect(screen.getByTestId("suggestion-sug-3")).toBeInTheDocument();
  });

  // ABP-04: Shows priority badges
  it("shows priority badge with correct color for high priority", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const priorityBadge = screen.getByTestId("suggestion-priority-sug-1");
    expect(priorityBadge).toHaveTextContent("high");
    expect(priorityBadge).toHaveClass("priority-red");
  });

  // ABP-05: Shows category badges
  it("shows category badge for each suggestion", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("suggestion-category-sug-1")).toHaveTextContent("On-Page");
    expect(screen.getByTestId("suggestion-category-sug-2")).toHaveTextContent("Content");
  });

  // ABP-06: Expandable suggestion
  it("expands suggestion details when clicked", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const toggleBtn = screen.getByTestId("toggle-details-sug-1");
    await user.click(toggleBtn);

    expect(screen.getByTestId("suggestion-details-sug-1")).toBeInTheDocument();
    expect(toggleBtn).toHaveAttribute("aria-expanded", "true");
  });

  // ABP-07: Collapse suggestion
  it("collapses suggestion when clicked again", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const toggleBtn = screen.getByTestId("toggle-details-sug-1");

    // Expand
    await user.click(toggleBtn);
    expect(screen.getByTestId("suggestion-details-sug-1")).toBeInTheDocument();

    // Collapse
    await user.click(toggleBtn);
    expect(screen.queryByTestId("suggestion-details-sug-1")).not.toBeInTheDocument();
  });

  // ABP-08: Copy to clipboard
  it("copies suggestion to clipboard", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    await user.click(screen.getByTestId("copy-btn-sug-1"));

    expect(mockClipboardWrite).toHaveBeenCalled();
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();
  });

  // ABP-09: Request regeneration
  it("calls onRegenerate when regenerate button clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockAiRefreshBriefPanel brief={mockBrief} onRegenerate={mockOnRegenerate} />
    );

    await user.click(screen.getByTestId("regenerate-btn"));
    expect(mockOnRegenerate).toHaveBeenCalledTimes(1);
  });

  // ABP-10: Regeneration loading
  it("shows loading state during regeneration", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} isRegenerating={true} />);

    expect(screen.getByTestId("icon-loader")).toBeInTheDocument();
    expect(screen.getByText("Regenerating...")).toBeInTheDocument();
    expect(screen.getByTestId("regenerate-btn")).toBeDisabled();
  });

  // ABP-11: Empty state
  it("shows empty state with request button when no brief", () => {
    render(<MockAiRefreshBriefPanel brief={null} onRequestBrief={mockOnRequestBrief} />);

    expect(screen.getByTestId("brief-empty")).toBeInTheDocument();
    expect(screen.getByText("No AI brief generated yet")).toBeInTheDocument();
    expect(screen.getByTestId("request-brief-btn")).toBeInTheDocument();
  });

  // ABP-12: Request new brief
  it("calls onRequestBrief when request button clicked", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={null} onRequestBrief={mockOnRequestBrief} />);

    await user.click(screen.getByTestId("request-brief-btn"));
    expect(mockOnRequestBrief).toHaveBeenCalledTimes(1);
  });

  // ABP-13: Loading state
  it("shows loading skeleton when loading", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} isLoading={true} />);

    expect(screen.getByTestId("brief-loading")).toBeInTheDocument();
    expect(screen.getByTestId("skeleton-brief")).toBeInTheDocument();
  });

  // ABP-14: Error state
  it("shows error message on error", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} error="AI service unavailable" />);

    expect(screen.getByTestId("brief-error")).toBeInTheDocument();
    expect(screen.getByText("AI service unavailable")).toBeInTheDocument();
  });

  // ABP-15: Disclaimer visible
  it("shows disclaimer text", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("disclaimer")).toHaveTextContent(
      "AI-generated suggestions. Verify relevance before implementation."
    );
  });

  // ABP-16: Suggestion with details
  it("shows details list when expanded", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    await user.click(screen.getByTestId("toggle-details-sug-1"));

    const details = screen.getByTestId("suggestion-details-sug-1");
    expect(details).toBeInTheDocument();
    expect(details.querySelectorAll("li")).toHaveLength(3);
  });

  // ABP-17: Generated timestamp
  it("shows generated timestamp", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("generated-at")).toHaveTextContent("2026-03-10T10:00:00Z");
  });

  // ABP-18: Accessible panel
  it("has correct accessibility attributes", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const panel = screen.getByTestId("ai-refresh-brief-panel");
    expect(panel).toHaveAttribute("role", "region");
    expect(panel).toHaveAttribute("aria-label", "AI refresh brief");
  });

  // ABP-19: Keyboard navigation
  it("interactive elements are focusable", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const toggleBtn = screen.getByTestId("toggle-details-sug-1");
    const copyBtn = screen.getByTestId("copy-btn-sug-1");
    const regenerateBtn = screen.getByTestId("regenerate-btn");

    expect(toggleBtn.tagName.toLowerCase()).toBe("button");
    expect(copyBtn.tagName.toLowerCase()).toBe("button");
    expect(regenerateBtn.tagName.toLowerCase()).toBe("button");
  });

  // ABP-20: Custom className
  it("applies custom className", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} className="custom-panel" />);

    expect(screen.getByTestId("ai-refresh-brief-panel")).toHaveClass("custom-panel");
  });

  // ABP-21: Warning label has alert role
  it("warning label has alert role", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("warning-label")).toHaveAttribute("role", "alert");
  });

  // ABP-22: Medium priority badge
  it("shows medium priority with yellow color", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const priorityBadge = screen.getByTestId("suggestion-priority-sug-2");
    expect(priorityBadge).toHaveTextContent("medium");
    expect(priorityBadge).toHaveClass("priority-yellow");
  });

  // ABP-23: Low priority badge
  it("shows low priority with gray color", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const priorityBadge = screen.getByTestId("suggestion-priority-sug-3");
    expect(priorityBadge).toHaveTextContent("low");
    expect(priorityBadge).toHaveClass("priority-gray");
  });

  // ABP-24: Toggle button has aria-expanded
  it("toggle button has aria-expanded attribute", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const toggleBtn = screen.getByTestId("toggle-details-sug-1");
    expect(toggleBtn).toHaveAttribute("aria-expanded", "false");
  });

  // ABP-25: Toggle button aria-label changes
  it("toggle button aria-label reflects state", async () => {
    const user = userEvent.setup();
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    const toggleBtn = screen.getByTestId("toggle-details-sug-1");
    expect(toggleBtn).toHaveAttribute("aria-label", "Expand details");

    await user.click(toggleBtn);
    expect(toggleBtn).toHaveAttribute("aria-label", "Collapse details");
  });

  // ABP-26: Suggestion without details
  it("handles suggestion without details gracefully", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    // sug-2 has no details
    const suggestion = screen.getByTestId("suggestion-sug-2");
    expect(suggestion).toBeInTheDocument();
    // Details won't exist but toggle button should still be there
    expect(screen.getByTestId("toggle-details-sug-2")).toBeInTheDocument();
  });

  // ABP-27: Regenerate button has accessible label
  it("regenerate button has accessible label", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("regenerate-btn")).toHaveAttribute("aria-label");
  });

  // ABP-28: Copy button has accessible label
  it("copy button has accessible label", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("copy-btn-sug-1")).toHaveAttribute("aria-label");
  });

  // ABP-29: Request button has icon and text
  it("request button shows sparkles icon", () => {
    render(<MockAiRefreshBriefPanel brief={null} onRequestBrief={mockOnRequestBrief} />);

    expect(screen.getByTestId("request-brief-btn")).toHaveTextContent("Request AI Brief");
    expect(screen.getByTestId("icon-sparkles")).toBeInTheDocument();
  });

  // ABP-30: Suggestion title displayed
  it("displays suggestion title", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("suggestion-title-sug-1")).toHaveTextContent("Improve Title Tag");
    expect(screen.getByTestId("suggestion-title-sug-2")).toHaveTextContent("Add FAQ Section");
  });

  // ABP-31: Suggestion description displayed
  it("displays suggestion description", () => {
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    expect(screen.getByTestId("suggestion-description-sug-1")).toHaveTextContent(
      "The current title lacks emotional triggers"
    );
  });

  // ABP-32: Copied indicator disappears after timeout
  it("copied indicator disappears after timeout", async () => {
    vi.useFakeTimers();
    const user = userEvent.setup({ delay: null });
    render(<MockAiRefreshBriefPanel brief={mockBrief} />);

    await user.click(screen.getByTestId("copy-btn-sug-1"));
    expect(screen.getByTestId("copied-indicator")).toBeInTheDocument();

    // Fast-forward 2 seconds
    vi.advanceTimersByTime(2000);

    await waitFor(() => {
      expect(screen.queryByTestId("copied-indicator")).not.toBeInTheDocument();
    });

    vi.useRealTimers();
  });
});
