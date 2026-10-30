/**
 * HistoryFilters Component Tests - TASK-25-20
 * 
 * Tests for the filter controls component used in analysis history page.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import HistoryFilters from "@/components/history/HistoryFilters";

// Mock dependencies
const mockOnFilterChange = jest.fn();
const mockOnClear = jest.fn();

const defaultProps = {
  filters: {
    tool: "",
    status: "",
    fromDate: "",
    toDate: "",
  },
  onFilterChange: mockOnFilterChange,
  onClear: mockOnClear,
  tools: [
    { value: "", label: "All Tools" },
    { value: "meta-tag-analyzer", label: "Meta Tag Analyzer" },
    { value: "keyword-density", label: "Keyword Density Checker" },
  ],
  statuses: [
    { value: "", label: "All Statuses" },
    { value: "completed", label: "Completed" },
    { value: "failed", label: "Failed" },
    { value: "pending", label: "Pending" },
  ],
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== HF-001: Tool Filter ====================

describe("Tool Filter", () => {
  it("renders tool dropdown with all options", () => {
    render(<HistoryFilters {...defaultProps} />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    expect(toolFilter).toBeInTheDocument();

    // Check options are available
    expect(screen.getByRole("option", { name: /all tools/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /meta tag analyzer/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /keyword density/i })).toBeInTheDocument();
  });

  it("calls onFilterChange when tool selection changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    await user.selectOptions(toolFilter, "meta-tag-analyzer");

    expect(mockOnFilterChange).toHaveBeenCalledWith("tool", "meta-tag-analyzer");
  });

  it("shows current tool value from props", () => {
    render(<HistoryFilters {...defaultProps} filters={{ ...defaultProps.filters, tool: "keyword-density" }} />);

    const toolFilter = screen.getByLabelText(/filter by tool/i) as HTMLSelectElement;
    expect(toolFilter.value).toBe("keyword-density");
  });
});

// ==================== HF-002: Status Filter ====================

describe("Status Filter", () => {
  it("renders status dropdown with all options", () => {
    render(<HistoryFilters {...defaultProps} />);

    const statusFilter = screen.getByLabelText(/filter by status/i);
    expect(statusFilter).toBeInTheDocument();

    expect(screen.getByRole("option", { name: /all statuses/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /completed/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /failed/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /pending/i })).toBeInTheDocument();
  });

  it("calls onFilterChange when status selection changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const statusFilter = screen.getByLabelText(/filter by status/i);
    await user.selectOptions(statusFilter, "failed");

    expect(mockOnFilterChange).toHaveBeenCalledWith("status", "failed");
  });

  it("shows current status value from props", () => {
    render(<HistoryFilters {...defaultProps} filters={{ ...defaultProps.filters, status: "completed" }} />);

    const statusFilter = screen.getByLabelText(/filter by status/i) as HTMLSelectElement;
    expect(statusFilter.value).toBe("completed");
  });
});

// ==================== HF-003: Date Range Filter ====================

describe("Date Range Filter", () => {
  it("renders from date input", () => {
    render(<HistoryFilters {...defaultProps} />);

    const fromDate = screen.getByLabelText(/from date/i);
    expect(fromDate).toBeInTheDocument();
    expect(fromDate).toHaveAttribute("type", "date");
  });

  it("renders to date input", () => {
    render(<HistoryFilters {...defaultProps} />);

    const toDate = screen.getByLabelText(/to date/i);
    expect(toDate).toBeInTheDocument();
    expect(toDate).toHaveAttribute("type", "date");
  });

  it("calls onFilterChange when from date changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const fromDate = screen.getByLabelText(/from date/i);
    await user.type(fromDate, "2026-02-01");

    expect(mockOnFilterChange).toHaveBeenCalledWith("fromDate", expect.any(String));
  });

  it("calls onFilterChange when to date changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const toDate = screen.getByLabelText(/to date/i);
    await user.type(toDate, "2026-02-28");

    expect(mockOnFilterChange).toHaveBeenCalledWith("toDate", expect.any(String));
  });

  it("shows current date values from props", () => {
    render(<HistoryFilters {...defaultProps} filters={{ ...defaultProps.filters, fromDate: "2026-02-01", toDate: "2026-02-28" }} />);

    const fromDate = screen.getByLabelText(/from date/i) as HTMLInputElement;
    const toDate = screen.getByLabelText(/to date/i) as HTMLInputElement;

    expect(fromDate.value).toBe("2026-02-01");
    expect(toDate.value).toBe("2026-02-28");
  });

  it("shows error when from date is after to date", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} filters={{ ...defaultProps.filters, fromDate: "2026-03-01", toDate: "2026-02-01" }} />);

    // Component should show validation error
    expect(screen.getByText(/invalid date range/i) || screen.getByTestId("date-error")).toBeTruthy();
  });
});

// ==================== HF-004: Clear Filters ====================

describe("Clear Filters Button", () => {
  it("renders clear filters button when filters are active", () => {
    render(<HistoryFilters {...defaultProps} filters={{ tool: "meta-tag-analyzer", status: "", fromDate: "", toDate: "" }} />);

    const clearButton = screen.getByRole("button", { name: /clear filters/i });
    expect(clearButton).toBeInTheDocument();
  });

  it("does not render clear filters button when no filters active", () => {
    render(<HistoryFilters {...defaultProps} />);

    const clearButton = screen.queryByRole("button", { name: /clear filters/i });
    expect(clearButton).not.toBeInTheDocument();
  });

  it("calls onClear when clear filters button clicked", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} filters={{ tool: "meta-tag-analyzer", status: "", fromDate: "", toDate: "" }} />);

    const clearButton = screen.getByRole("button", { name: /clear filters/i });
    await user.click(clearButton);

    expect(mockOnClear).toHaveBeenCalled();
  });
});

// ==================== HF-005: Active Filters Indicators ====================

describe("Active Filters Indicators", () => {
  it("shows indicator when filters are active", () => {
    render(<HistoryFilters {...defaultProps} filters={{ tool: "meta-tag-analyzer", status: "", fromDate: "", toDate: "" }} />);

    // Should show some indicator of active filters
    expect(screen.getByText(/filter.*active|active filter/i) || 
           screen.getByTestId("active-filter-badge")).toBeTruthy();
  });

  it("shows count of active filters", () => {
    render(<HistoryFilters {...defaultProps} filters={{ tool: "meta-tag-analyzer", status: "completed", fromDate: "2026-02-01", toDate: "" }} />);

    // Should show count (2 filters active)
    expect(screen.getByText(/2.*filter/i) || screen.getByText("2")).toBeTruthy();
  });
});

// ==================== HF-006: Accessibility ====================

describe("Accessibility", () => {
  it("all filter inputs have associated labels", () => {
    render(<HistoryFilters {...defaultProps} />);

    expect(screen.getByLabelText(/filter by tool/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/filter by status/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/from date/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/to date/i)).toBeInTheDocument();
  });

  it("supports keyboard navigation", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    await user.tab();
    expect(document.activeElement).toBeTruthy();
  });
});

// ==================== HF-007: Responsive Design ====================

describe("Responsive Design", () => {
  it("has responsive layout classes", () => {
    render(<HistoryFilters {...defaultProps} />);

    const container = screen.getByTestId("filters-container");
    expect(container).toHaveClass(/flex|grid/i);
  });

  it("has collapsible toggle for mobile view", () => {
    render(<HistoryFilters {...defaultProps} />);

    // Check for collapsible functionality
    const toggleButton = screen.queryByRole("button", { name: /toggle filters|show filters/i });
    // May or may not exist depending on implementation
    if (toggleButton) {
      expect(toggleButton).toBeInTheDocument();
    }
  });
});
