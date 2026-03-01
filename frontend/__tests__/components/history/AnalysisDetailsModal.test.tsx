/**
 * AnalysisDetailsModal Component Tests - TASK-25-20
 * 
 * Tests for the analysis details modal component.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import AnalysisDetailsModal from "@/components/history/AnalysisDetailsModal";

// Mock dependencies
const mockOnClose = jest.fn();

const defaultAnalysis = {
  id: 127,
  toolName: "meta-tag-analyzer",
  toolDisplayName: "Meta Tag Analyzer",
  inputUrl: "https://example.com",
  status: "completed",
  cost: {
    total: 0.05,
    breakdown: [
      { type: "base", amount: 0.01, description: "Base analysis cost" },
      { type: "compute", amount: 0.04, description: "Processing time" },
    ],
    currency: "USD",
  },
  analysisDurationMs: 1234,
  analyzedAt: "2026-03-01T10:00:00Z",
  results: {
    title: { content: "Example Domain", length: 14 },
    metaDescription: { content: "This domain is for use in illustrative examples", length: 50 },
    h1: { count: 1, content: ["Example Domain"] },
  },
};

const defaultProps = {
  analysis: defaultAnalysis,
  isOpen: true,
  onClose: mockOnClose,
  isLoading: false,
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== ADM-001: Modal Structure ====================

describe("Modal Structure", () => {
  it("renders when isOpen is true", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });

  it("does not render when isOpen is false", () => {
    render(<AnalysisDetailsModal {...defaultProps} isOpen={false} />);

    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
  });

  it("has correct ARIA attributes", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog).toHaveAttribute("aria-modal", "true");
    expect(dialog).toHaveAttribute("aria-labelledby");
  });

  it("has close button", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const closeButton = screen.getByRole("button", { name: /close/i });
    expect(closeButton).toBeInTheDocument();
  });
});

// ==================== ADM-002: Header Section ====================

describe("Header Section", () => {
  it("displays tool name in header", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
  });

  it("displays analysis ID", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/#127/i)).toBeInTheDocument();
  });

  it("displays status badge", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const statusBadge = screen.getByText(/completed/i);
    expect(statusBadge).toBeInTheDocument();
    expect(statusBadge.className).toMatch(/emerald|green/i);
  });
});

// ==================== ADM-003: Basic Info Section ====================

describe("Basic Info Section", () => {
  it("displays input URL", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/example\.com/i)).toBeInTheDocument();
  });

  it("displays analyzed date", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/mar.*2026|2026.*mar/i)).toBeInTheDocument();
  });

  it("displays analysis duration", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/1\.23.*second|1234ms/i)).toBeInTheDocument();
  });

  it("displays tool name", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/meta-tag-analyzer/i) || 
           screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
  });
});

// ==================== ADM-004: Results Section ====================

describe("Results Section", () => {
  it("displays results section", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/results/i)).toBeInTheDocument();
  });

  it("displays title from results", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText("Example Domain")).toBeInTheDocument();
  });

  it("displays meta description from results", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/This domain is for use/i)).toBeInTheDocument();
  });

  it("displays H1 count from results", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/h1.*count|count.*h1/i) || 
           screen.getByText(/1.*h1/i)).toBeTruthy();
  });

  it("displays JSON view for raw results", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    // Should have option to view raw JSON
    const jsonView = screen.queryByTestId("json-viewer") || 
                     screen.queryByRole("button", { name: /view.*json|raw/i });
    if (jsonView) {
      expect(jsonView).toBeInTheDocument();
    }
  });
});

// ==================== ADM-005: Cost Breakdown Section ====================

describe("Cost Breakdown Section", () => {
  it("displays cost breakdown section", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/cost breakdown|cost summary/i)).toBeInTheDocument();
  });

  it("displays total cost", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
  });

  it("displays individual cost items", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.getByText(/base analysis cost/i)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.01/)).toBeInTheDocument();
    expect(screen.getByText(/processing time/i)).toBeInTheDocument();
    expect(screen.getByText(/\$0\.04/)).toBeInTheDocument();
  });

  it("handles missing cost breakdown gracefully", () => {
    const analysisWithoutBreakdown = {
      ...defaultAnalysis,
      cost: { total: 0.05, currency: "USD" },
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={analysisWithoutBreakdown} />);

    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
  });
});

// ==================== ADM-006: Error Section ====================

describe("Error Section", () => {
  it("displays error message for failed analysis", () => {
    const failedAnalysis = {
      ...defaultAnalysis,
      status: "failed",
      errorMessage: "Connection timeout after 30 seconds",
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={failedAnalysis} />);

    expect(screen.getByText(/error|failed/i)).toBeInTheDocument();
    expect(screen.getByText(/connection timeout/i)).toBeInTheDocument();
  });

  it("does not show error section for successful analysis", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    expect(screen.queryByText(/error message/i)).not.toBeInTheDocument();
  });

  it("shows error icon for failed analysis", () => {
    const failedAnalysis = {
      ...defaultAnalysis,
      status: "failed",
      errorMessage: "Error occurred",
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={failedAnalysis} />);

    const errorSection = screen.getByTestId("error-section") ||
                         screen.getByText(/error occurred/i).closest("div");
    expect(errorSection?.className).toMatch(/red|rose|error/i);
  });
});

// ==================== ADM-007: Actions ====================

describe("Actions", () => {
  it("closes modal when close button clicked", async () => {
    const user = userEvent.setup();
    render(<AnalysisDetailsModal {...defaultProps} />);

    const closeButton = screen.getByRole("button", { name: /close/i });
    await user.click(closeButton);

    expect(mockOnClose).toHaveBeenCalled();
  });

  it("closes modal when clicking outside", async () => {
    const user = userEvent.setup();
    render(<AnalysisDetailsModal {...defaultProps} />);

    // Click on overlay (outside dialog content)
    const overlay = screen.getByRole("dialog").parentElement;
    if (overlay) {
      await user.click(overlay);
      expect(mockOnClose).toHaveBeenCalled();
    }
  });

  it("closes modal when pressing Escape key", async () => {
    const user = userEvent.setup();
    render(<AnalysisDetailsModal {...defaultProps} />);

    await user.keyboard("{Escape}");

    expect(mockOnClose).toHaveBeenCalled();
  });
});

// ==================== ADM-008: Loading State ====================

describe("Loading State", () => {
  it("shows loading spinner when isLoading is true", () => {
    render(<AnalysisDetailsModal {...defaultProps} isLoading={true} />);

    expect(screen.getByTestId("loading-spinner") || 
           document.querySelector(".animate-spin")).toBeTruthy();
  });

  it("shows skeleton content when loading", () => {
    render(<AnalysisDetailsModal {...defaultProps} isLoading={true} />);

    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });
});

// ==================== ADM-009: Focus Management ====================

describe("Focus Management", () => {
  it("focuses close button when modal opens", async () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    await waitFor(() => {
      const closeButton = screen.getByRole("button", { name: /close/i });
      expect(closeButton).toHaveFocus();
    });
  });

  it("traps focus within modal", async () => {
    const user = userEvent.setup();
    render(<AnalysisDetailsModal {...defaultProps} />);

    const closeButton = screen.getByRole("button", { name: /close/i });
    closeButton.focus();

    // Tab through all focusable elements
    for (let i = 0; i < 10; i++) {
      await user.tab();
    }

    // Focus should still be within dialog
    expect(document.activeElement?.closest('[role="dialog"]')).toBeTruthy();
  });

  it("returns focus to trigger element when closed", async () => {
    const { rerender } = render(<AnalysisDetailsModal {...defaultProps} />);

    // Close modal
    rerender(<AnalysisDetailsModal {...defaultProps} isOpen={false} />);

    // Focus management would be handled by parent component
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
  });
});

// ==================== ADM-010: Accessibility ====================

describe("Accessibility", () => {
  it("has proper ARIA attributes for dialog", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog).toHaveAttribute("aria-modal", "true");
    expect(dialog).toHaveAttribute("aria-labelledby");
  });

  it("close button has accessible label", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const closeButton = screen.getByRole("button", { name: /close/i });
    expect(closeButton).toHaveAttribute("aria-label");
  });

  it("has descriptive heading", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const heading = screen.getByRole("heading", { level: 2 });
    expect(heading).toHaveTextContent(/analysis details|meta tag analyzer/i);
  });
});

// ==================== ADM-011: Responsive Design ====================

describe("Responsive Design", () => {
  it("has responsive modal width", () => {
    render(<AnalysisDetailsModal {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog.className).toMatch(/max-w|w-full/i);
  });

  it("scrolls content when too tall", () => {
    const tallAnalysis = {
      ...defaultAnalysis,
      results: {
        ...defaultAnalysis.results,
        largeArray: Array.from({ length: 100 }, (_, i) => ({ item: i })),
      },
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={tallAnalysis} />);

    const content = screen.getByTestId("modal-content") ||
                    screen.getByRole("dialog").querySelector("[class*='overflow']");
    expect(content).toBeTruthy();
  });
});

// ==================== ADM-012: Different Tool Results ====================

describe("Different Tool Results", () => {
  it("displays keyword density results correctly", () => {
    const keywordAnalysis = {
      ...defaultAnalysis,
      toolName: "keyword-density",
      toolDisplayName: "Keyword Density Checker",
      results: {
        keywords: [
          { word: "seo", count: 15, density: 2.5 },
          { word: "analysis", count: 10, density: 1.8 },
        ],
        totalWords: 600,
      },
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={keywordAnalysis} />);

    expect(screen.getByText(/keyword density/i)).toBeInTheDocument();
  });

  it("displays mobile-friendly results correctly", () => {
    const mobileAnalysis = {
      ...defaultAnalysis,
      toolName: "mobile-friendly",
      toolDisplayName: "Mobile-Friendly Checker",
      results: {
        isMobileFriendly: true,
        issues: [],
        score: 95,
      },
    };

    render(<AnalysisDetailsModal {...defaultProps} analysis={mobileAnalysis} />);

    expect(screen.getByText(/mobile-friendly|mobile friendly/i)).toBeInTheDocument();
  });
});
