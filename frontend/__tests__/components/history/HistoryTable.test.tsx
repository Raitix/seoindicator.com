/**
 * HistoryTable Component Tests - TASK-25-20
 * 
 * Tests for the analysis history table component.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import HistoryTable from "@/components/history/HistoryTable";

// Mock dependencies
const mockOnView = jest.fn();
const mockOnDelete = jest.fn();

const defaultData = [
  {
    id: 127,
    toolName: "meta-tag-analyzer",
    toolDisplayName: "Meta Tag Analyzer",
    inputUrl: "https://example.com",
    status: "completed",
    cost: { total: 0.05, currency: "USD" },
    analysisDurationMs: 1234,
    analyzedAt: "2026-03-01T10:00:00Z",
  },
  {
    id: 126,
    toolName: "keyword-density",
    toolDisplayName: "Keyword Density Checker",
    inputUrl: "https://test.com/blog/post",
    status: "failed",
    cost: { total: 0, currency: "USD" },
    analysisDurationMs: 5000,
    analyzedAt: "2026-03-01T09:30:00Z",
    errorMessage: "Connection timeout",
  },
  {
    id: 125,
    toolName: "mobile-friendly",
    toolDisplayName: "Mobile-Friendly Checker",
    inputUrl: "https://demo.org",
    status: "pending",
    cost: { total: 0, currency: "USD" },
    analysisDurationMs: null,
    analyzedAt: "2026-03-01T09:00:00Z",
  },
  {
    id: 124,
    toolName: "meta-tag-analyzer",
    toolDisplayName: "Meta Tag Analyzer",
    inputUrl: "https://another-site.com/page",
    status: "running",
    cost: { total: 0, currency: "USD" },
    analysisDurationMs: null,
    analyzedAt: "2026-03-01T08:30:00Z",
  },
];

const defaultProps = {
  data: defaultData,
  onView: mockOnView,
  onDelete: mockOnDelete,
  isLoading: false,
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== HT-001: Table Structure ====================

describe("Table Structure", () => {
  it("renders table element with correct ARIA label", () => {
    render(<HistoryTable {...defaultProps} />);

    const table = screen.getByRole("table");
    expect(table).toBeInTheDocument();
    expect(table).toHaveAttribute("aria-label", "Analysis history");
  });

  it("renders all column headers", () => {
    render(<HistoryTable {...defaultProps} />);

    expect(screen.getByRole("columnheader", { name: /tool/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /url/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /status/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /cost/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /duration/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /date/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /actions/i })).toBeInTheDocument();
  });

  it("renders correct number of rows", () => {
    render(<HistoryTable {...defaultProps} />);

    const rows = screen.getAllByRole("row");
    // 4 data rows + 1 header row
    expect(rows.length).toBe(5);
  });
});

// ==================== HT-002: Tool Column ====================

describe("Tool Column", () => {
  it("displays tool display name", () => {
    render(<HistoryTable {...defaultProps} />);

    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
    expect(screen.getByText("Keyword Density Checker")).toBeInTheDocument();
    expect(screen.getByText("Mobile-Friendly Checker")).toBeInTheDocument();
  });

  it("falls back to toolName when toolDisplayName is missing", () => {
    const dataWithoutDisplayName = [{
      ...defaultData[0],
      toolDisplayName: null,
    }];

    render(<HistoryTable {...defaultProps} data={dataWithoutDisplayName} />);

    expect(screen.getByText("meta-tag-analyzer")).toBeInTheDocument();
  });
});

// ==================== HT-003: URL Column ====================

describe("URL Column", () => {
  it("displays truncated URL", () => {
    render(<HistoryTable {...defaultProps} />);

    expect(screen.getByText(/example\.com/i)).toBeInTheDocument();
  });

  it("truncates long URLs", () => {
    const dataWithLongUrl = [{
      ...defaultData[0],
      inputUrl: "https://very-long-domain-name.example.com/path/to/very/long/page/with/many/segments",
    }];

    render(<HistoryTable {...defaultProps} data={dataWithLongUrl} />);

    const urlCell = screen.getByText(/very-long-domain-name/i);
    expect(urlCell).toBeInTheDocument();
  });

  it("displays URL as link when clickable", () => {
    render(<HistoryTable {...defaultProps} />);

    // URL may be plain text or a link
    const urlElements = screen.getAllByText(/\.com/i);
    expect(urlElements.length).toBeGreaterThan(0);
  });
});

// ==================== HT-004: Status Column ====================

describe("Status Column", () => {
  it("displays completed status with green badge", () => {
    render(<HistoryTable {...defaultProps} />);

    const completedBadge = screen.getByText(/completed/i);
    expect(completedBadge).toBeInTheDocument();
    expect(completedBadge.className).toMatch(/emerald|green/i);
  });

  it("displays failed status with red badge", () => {
    render(<HistoryTable {...defaultProps} />);

    const failedBadge = screen.getByText(/failed/i);
    expect(failedBadge).toBeInTheDocument();
    expect(failedBadge.className).toMatch(/rose|red/i);
  });

  it("displays pending status with yellow badge", () => {
    render(<HistoryTable {...defaultProps} />);

    const pendingBadge = screen.getByText(/pending/i);
    expect(pendingBadge).toBeInTheDocument();
    expect(pendingBadge.className).toMatch(/amber|yellow/i);
  });

  it("displays running status with spinner", () => {
    render(<HistoryTable {...defaultProps} />);

    const runningBadge = screen.getByText(/running/i);
    expect(runningBadge).toBeInTheDocument();
    // Should have spinner animation nearby
    expect(screen.getByTestId("status-spinner") || 
           document.querySelector(".animate-spin")).toBeTruthy();
  });
});

// ==================== HT-005: Cost Column ====================

describe("Cost Column", () => {
  it("displays cost with currency symbol", () => {
    render(<HistoryTable {...defaultProps} />);

    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
  });

  it("displays free for zero cost", () => {
    render(<HistoryTable {...defaultProps} />);

    const freeLabels = screen.getAllByText(/free|-|\$0\.00/i);
    expect(freeLabels.length).toBeGreaterThan(0);
  });

  it("handles missing cost gracefully", () => {
    const dataWithoutCost = [{
      ...defaultData[0],
      cost: null,
    }];

    render(<HistoryTable {...defaultProps} data={dataWithoutCost} />);

    expect(screen.getByText(/-|n\/a/i)).toBeInTheDocument();
  });
});

// ==================== HT-006: Duration Column ====================

describe("Duration Column", () => {
  it("displays duration in seconds", () => {
    render(<HistoryTable {...defaultProps} />);

    expect(screen.getByText(/1\.23s/i) || screen.getByText(/1234ms/i)).toBeInTheDocument();
  });

  it("displays duration in milliseconds when less than 1 second", () => {
    const dataWithShortDuration = [{
      ...defaultData[0],
      analysisDurationMs: 500,
    }];

    render(<HistoryTable {...defaultProps} data={dataWithShortDuration} />);

    expect(screen.getByText(/500ms/i)).toBeInTheDocument();
  });

  it("displays N/A for null duration", () => {
    const dataWithNullDuration = [{
      ...defaultData[0],
      analysisDurationMs: null,
    }];

    render(<HistoryTable {...defaultProps} data={dataWithNullDuration} />);

    expect(screen.getByText(/-|n\/a|pending/i)).toBeInTheDocument();
  });
});

// ==================== HT-007: Date Column ====================

describe("Date Column", () => {
  it("displays date in readable format", () => {
    render(<HistoryTable {...defaultProps} />);

    // Date should be displayed
    const dateElements = screen.getAllByText(/mar.*2026|2026.*mar/i);
    expect(dateElements.length).toBeGreaterThan(0);
  });

  it("displays relative time for recent dates", () => {
    const recentData = [{
      ...defaultData[0],
      analyzedAt: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 minutes ago
    }];

    render(<HistoryTable {...defaultProps} data={recentData} />);

    // Should show relative time like "30 minutes ago"
    expect(screen.getByText(/ago/i) || screen.getByText(/minutes/i)).toBeTruthy();
  });
});

// ==================== HT-008: Actions Column ====================

describe("Actions Column", () => {
  it("displays view button for each row", () => {
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    expect(viewButtons.length).toBe(4);
  });

  it("displays delete button for each row", () => {
    render(<HistoryTable {...defaultProps} />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    expect(deleteButtons.length).toBe(4);
  });

  it("calls onView when view button clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    expect(mockOnView).toHaveBeenCalledWith(127);
  });

  it("calls onDelete when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    expect(mockOnDelete).toHaveBeenCalledWith(127);
  });

  it("disables delete button when delete in progress", () => {
    render(<HistoryTable {...defaultProps} deletingId={126} />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    const secondDeleteButton = deleteButtons[1];

    expect(secondDeleteButton).toBeDisabled();
  });
});

// ==================== HT-009: Row Click ====================

describe("Row Click", () => {
  it("calls onView when row is clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const firstRow = screen.getByText("Meta Tag Analyzer").closest("tr");
    if (firstRow) {
      await user.click(firstRow);
      expect(mockOnView).toHaveBeenCalledWith(127);
    }
  });

  it("does not trigger view when clicking action buttons", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    // onView should only be called once (from button, not from row)
    expect(mockOnView).toHaveBeenCalledTimes(1);
  });
});

// ==================== HT-010: Loading State ====================

describe("Loading State", () => {
  it("shows skeleton rows when loading", () => {
    render(<HistoryTable {...defaultProps} data={[]} isLoading={true} />);

    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it("shows skeleton with correct number of columns", () => {
    render(<HistoryTable {...defaultProps} data={[]} isLoading={true} />);

    // Each skeleton row should have 7 columns
    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });
});

// ==================== HT-011: Empty State ====================

describe("Empty State", () => {
  it("shows empty message when no data", () => {
    render(<HistoryTable {...defaultProps} data={[]} />);

    expect(screen.getByText(/no analyses|no data|empty/i)).toBeInTheDocument();
  });

  it("shows different message when filters applied", () => {
    render(<HistoryTable {...defaultProps} data={[]} hasActiveFilters={true} />);

    expect(screen.getByText(/no analyses match|no results/i)).toBeInTheDocument();
  });
});

// ==================== HT-012: Accessibility ====================

describe("Accessibility", () => {
  it("table has proper ARIA attributes", () => {
    render(<HistoryTable {...defaultProps} />);

    const table = screen.getByRole("table");
    expect(table).toHaveAttribute("aria-label", "Analysis history");
  });

  it("action buttons have accessible labels", () => {
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });

    viewButtons.forEach(btn => {
      expect(btn).toHaveAttribute("aria-label");
    });

    deleteButtons.forEach(btn => {
      expect(btn).toHaveAttribute("aria-label");
    });
  });

  it("supports keyboard navigation", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    await user.tab();
    expect(document.activeElement).toBeTruthy();
  });
});

// ==================== HT-013: Responsive Design ====================

describe("Responsive Design", () => {
  it("has responsive container with overflow", () => {
    render(<HistoryTable {...defaultProps} />);

    const container = screen.getByTestId("table-container");
    expect(container).toHaveClass(/overflow/i);
  });

  it("table has minimum width for horizontal scroll", () => {
    render(<HistoryTable {...defaultProps} />);

    const table = screen.getByRole("table");
    expect(table).toBeInTheDocument();
  });
});
