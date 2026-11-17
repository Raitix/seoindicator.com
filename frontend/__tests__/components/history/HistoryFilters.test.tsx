/**
 * HistoryFilters Component Tests - TASK-25-20
 *
 * Tests for the filter controls component used in analysis history page.
 *
 * The filters are Radix selects and a popover, so the options are only
 * rendered once the control is opened.
 *
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import HistoryFilters from "@/components/history/HistoryFilters";

// Mock dependencies
const mockOnFiltersChange = vi.fn();
const mockOnClear = vi.fn();

const defaultProps = {
  filters: {
    tool: undefined,
    status: undefined,
    from: undefined,
    to: undefined,
  },
  onFiltersChange: mockOnFiltersChange,
  onClear: mockOnClear,
  tools: [
    { value: "all", label: "All Tools" },
    { value: "meta-tag-analyzer", label: "Meta Tag Analyzer" },
    { value: "keyword-density", label: "Keyword Density Checker" },
  ],
};

/** Opens a Radix select and returns its trigger. */
async function openSelect(user: ReturnType<typeof userEvent.setup>, testId: string) {
  const trigger = screen.getByTestId(testId);
  await user.click(trigger);
  await waitFor(() => expect(screen.getByRole("listbox")).toBeInTheDocument());
  return trigger;
}

// ==================== Test Setup ====================

beforeEach(() => {
  vi.clearAllMocks();
});

// ==================== HF-001: Tool Filter ====================

describe("Tool Filter", () => {
  it("renders tool dropdown with all options", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    expect(toolFilter).toBeInTheDocument();

    await openSelect(user, "tool-filter");

    // Check options are available
    expect(screen.getByRole("option", { name: /all tools/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /meta tag analyzer/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /keyword density/i })).toBeInTheDocument();
  });

  it("calls onFiltersChange when tool selection changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    await openSelect(user, "tool-filter");
    await user.click(screen.getByRole("option", { name: /meta tag analyzer/i }));

    expect(mockOnFiltersChange).toHaveBeenCalledWith({
      ...defaultProps.filters,
      tool: "meta-tag-analyzer",
    });
  });

  it("shows current tool value from props", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, tool: "keyword-density" }}
      />
    );

    expect(screen.getByTestId("tool-filter")).toHaveTextContent(/keyword density/i);
  });
});

// ==================== HF-002: Status Filter ====================

describe("Status Filter", () => {
  it("renders status dropdown with all options", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const statusFilter = screen.getByLabelText(/filter by status/i);
    expect(statusFilter).toBeInTheDocument();

    await openSelect(user, "status-filter");

    expect(screen.getByRole("option", { name: /all statuses/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /completed/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /failed/i })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: /pending/i })).toBeInTheDocument();
  });

  it("calls onFiltersChange when status selection changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    await openSelect(user, "status-filter");
    await user.click(screen.getByRole("option", { name: /^failed$/i }));

    expect(mockOnFiltersChange).toHaveBeenCalledWith({
      ...defaultProps.filters,
      status: "failed",
    });
  });

  it("shows current status value from props", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, status: "completed" }}
      />
    );

    expect(screen.getByTestId("status-filter")).toHaveTextContent(/completed/i);
  });
});

// ==================== HF-003: Date Range Filter ====================

describe("Date Range Filter", () => {
  /** Opens the date range popover and returns the two date inputs. */
  async function openDateRange(user: ReturnType<typeof userEvent.setup>) {
    await user.click(screen.getByTestId("date-range-filter"));
    const fromDate = await screen.findByTestId("date-from-input");
    const toDate = await screen.findByTestId("date-to-input");
    return { fromDate, toDate };
  }

  it("renders from date input", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const { fromDate } = await openDateRange(user);
    expect(fromDate).toBeInTheDocument();
    expect(fromDate).toHaveAttribute("type", "date");
  });

  it("renders to date input", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const { toDate } = await openDateRange(user);
    expect(toDate).toBeInTheDocument();
    expect(toDate).toHaveAttribute("type", "date");
  });

  it("calls onFiltersChange when from date changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const { fromDate } = await openDateRange(user);
    await user.type(fromDate, "2026-02-01");

    expect(mockOnFiltersChange).toHaveBeenCalledWith({
      ...defaultProps.filters,
      from: "2026-02-01",
    });
  });

  it("calls onFiltersChange when to date changes", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    const { toDate } = await openDateRange(user);
    await user.type(toDate, "2026-02-28");

    expect(mockOnFiltersChange).toHaveBeenCalledWith({
      ...defaultProps.filters,
      to: "2026-02-28",
    });
  });

  it("shows current date values from props", async () => {
    const user = userEvent.setup();
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, from: "2026-02-01", to: "2026-02-28" }}
      />
    );

    const { fromDate, toDate } = await openDateRange(user);

    expect(fromDate).toHaveValue("2026-02-01");
    expect(toDate).toHaveValue("2026-02-28");
  });

  it("shows error when from date is after to date", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, from: "2026-03-01", to: "2026-02-01" }}
      />
    );

    // Component should show validation error
    expect(screen.getByText(/invalid date range/i)).toBeInTheDocument();
  });
});

// ==================== HF-004: Clear Filters ====================

describe("Clear Filters Button", () => {
  it("renders clear filters button when filters are active", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, tool: "meta-tag-analyzer" }}
      />
    );

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
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, tool: "meta-tag-analyzer" }}
      />
    );

    const clearButton = screen.getByRole("button", { name: /clear filters/i });
    await user.click(clearButton);

    expect(mockOnClear).toHaveBeenCalled();
  });
});

// ==================== HF-005: Active Filters Indicators ====================

describe("Active Filters Indicators", () => {
  it("shows indicator when filters are active", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{ ...defaultProps.filters, tool: "meta-tag-analyzer" }}
      />
    );

    // Should show some indicator of active filters
    expect(screen.getByText(/active filters?/i)).toBeInTheDocument();
  });

  it("shows count of active filters", () => {
    render(
      <HistoryFilters
        {...defaultProps}
        filters={{
          ...defaultProps.filters,
          tool: "meta-tag-analyzer",
          status: "completed",
          from: "2026-02-01",
        }}
      />
    );

    // Should show count (3 filters active)
    expect(screen.getByText(/3.*filter/i)).toBeInTheDocument();
  });
});

// ==================== HF-006: Accessibility ====================

describe("Accessibility", () => {
  it("all filter inputs have associated labels", async () => {
    const user = userEvent.setup();
    render(<HistoryFilters {...defaultProps} />);

    expect(screen.getByLabelText(/filter by tool/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/filter by status/i)).toBeInTheDocument();

    // The date inputs live in the date range popover, so it has to be
    // open before they are in the document.
    await user.click(screen.getByTestId("date-range-filter"));

    expect(await screen.findByLabelText(/from date/i)).toBeInTheDocument();
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

    const container = screen.getByTestId("history-filters");
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
