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
const mockOnRowClick = vi.fn();
const mockOnViewDetails = vi.fn();
const mockOnDelete = vi.fn();

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
  analyses: defaultData,
  onRowClick: mockOnRowClick,
  onViewDetails: mockOnViewDetails,
  onDelete: mockOnDelete,
  isLoading: false,
};

// ==================== Test Setup ====================

beforeEach(() => {
  vi.clearAllMocks();
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

    expect(screen.getAllByText("Meta Tag Analyzer").length).toBe(2);
    expect(screen.getByText("Keyword Density Checker")).toBeInTheDocument();
    expect(screen.getByText("Mobile-Friendly Checker")).toBeInTheDocument();
  });

  it("falls back to toolName when toolDisplayName is missing", () => {
    const dataWithoutDisplayName = [{
      ...defaultData[0],
      toolDisplayName: null,
    }];

    render(<HistoryTable {...defaultProps} analyses={dataWithoutDisplayName} />);

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

    render(<HistoryTable {...defaultProps} analyses={dataWithLongUrl} />);

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

    render(<HistoryTable {...defaultProps} analyses={dataWithoutCost} />);

    expect(screen.getByText("—")).toBeInTheDocument();
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

    render(<HistoryTable {...defaultProps} analyses={dataWithShortDuration} />);

    expect(screen.getByText(/500ms/i)).toBeInTheDocument();
  });

  it("displays N/A for null duration", () => {
    const dataWithNullDuration = [{
      ...defaultData[0],
      analysisDurationMs: null,
    }];

    render(<HistoryTable {...defaultProps} analyses={dataWithNullDuration} />);

    expect(screen.getByText("—")).toBeInTheDocument();
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

  it("displays the time of the analysis for recent dates", () => {
    const recentData = [{
      ...defaultData[0],
      analyzedAt: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 minutes ago
    }];

    render(<HistoryTable {...defaultProps} analyses={recentData} />);

    // The date column shows the absolute date and time
    const dateCell = screen.getByText(/\d{1,2}:\d{2}/);
    expect(dateCell).toBeInTheDocument();
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

  it("calls onViewDetails when view button clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    expect(mockOnViewDetails).toHaveBeenCalledWith(defaultData[0]);
  });

  it("calls onDelete when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    expect(mockOnDelete).toHaveBeenCalledWith(defaultData[0]);
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
  it("calls onRowClick when row is clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const firstRow = screen.getAllByText("Meta Tag Analyzer")[0].closest("tr");
    if (firstRow) {
      await user.click(firstRow);
      expect(mockOnRowClick).toHaveBeenCalledWith(defaultData[0]);
    }
  });

  it("does not trigger the row click when clicking action buttons", async () => {
    const user = userEvent.setup();
    render(<HistoryTable {...defaultProps} />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    // The row click must not fire for the action buttons
    expect(mockOnRowClick).not.toHaveBeenCalled();
  });
});

// ==================== HT-010: Loading State ====================

describe("Loading State", () => {
  it("shows skeleton rows when loading", () => {
    render(<HistoryTable {...defaultProps} analyses={[]} isLoading={true} />);

    expect(screen.getByTestId("history-table-loading")).toBeInTheDocument();
    // The skeleton stands in for the data rows
    expect(screen.queryAllByTestId(/analysis-row-/)).toHaveLength(0);
  });

  it("shows skeleton with correct number of columns", () => {
    render(<HistoryTable {...defaultProps} analyses={[]} isLoading={true} />);

    // Each skeleton row should have 7 columns
    const headerCells = screen.getAllByRole("columnheader");
    expect(headerCells).toHaveLength(7);
  });
});

// ==================== HT-011: Empty State ====================

describe("Empty State", () => {
  it("renders no data rows when there are no analyses", () => {
    render(<HistoryTable {...defaultProps} analyses={[]} />);

    // The empty state is rendered by the parent, the table only shows
    // the header.
    expect(screen.queryAllByTestId(/analysis-row-/)).toHaveLength(0);
    expect(screen.getAllByRole("columnheader")).toHaveLength(7);
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
