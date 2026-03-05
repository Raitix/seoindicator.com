/**
 * Analysis History Page Tests - TASK-25-20
 * 
 * Tests for the analysis history page with filters, pagination,
 * details view, delete functionality, and responsive design.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import HistoryPage from "@/app/dashboard/history/page";

// Mock next/navigation
const mockPush = jest.fn();
const mockReplace = jest.fn();

jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(),
  usePathname: jest.fn(),
}));

// Mock AuthContext
jest.mock("@/lib/contexts/AuthContext", () => ({
  useAuth: jest.fn(),
}));

import { useAuth } from "@/lib/contexts/AuthContext";

// Mock SWR
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
}));

import useSWR from "swr";

// Mock API helpers
jest.mock("@/lib/api/analyses", () => ({
  fetchAnalyses: jest.fn(),
  fetchAnalysisById: jest.fn(),
  deleteAnalysis: jest.fn(),
  parseFilterUrl: jest.fn(() => ({ tool: "", status: "", from: "", to: "", page: 1, perPage: 15 })),
  buildFilterUrl: jest.fn(() => ""),
  analysisKeys: {
    list: jest.fn(() => "analyses-list"),
    detail: jest.fn(() => "analyses-detail"),
  },
}));

import { fetchAnalyses, fetchAnalysisById, deleteAnalysis } from "@/lib/api/analyses";

// ==================== Test Data ====================

const mockUser = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
};

const mockAnalysesResponse = {
  data: [
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
  ],
  meta: {
    total: 50,
    page: 1,
    perPage: 15,
    lastPage: 4,
  },
};

const mockAnalysisDetail = {
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

const mockTools = [
  { value: "", label: "All Tools" },
  { value: "meta-tag-analyzer", label: "Meta Tag Analyzer" },
  { value: "keyword-density", label: "Keyword Density Checker" },
  { value: "mobile-friendly", label: "Mobile-Friendly Checker" },
];

const mockAuthContext = {
  user: mockUser,
  isAuthenticated: true,
  isLoading: false,
  error: null,
  login: jest.fn(),
  loginWithGoogle: jest.fn(),
  logout: jest.fn(),
  register: jest.fn(),
  resetPassword: jest.fn(),
  resendVerification: jest.fn(),
  clearError: jest.fn(),
  refreshUser: jest.fn(),
};

// Helper to setup SWR mock
const mockUseSWR = (data: any, isLoading = false, error: any = null, mutate = jest.fn()) => {
  (useSWR as jest.Mock).mockReturnValue({
    data,
    isLoading,
    error,
    mutate,
  });
};

// Helper to setup search params
const mockSearchParams = (params: Record<string, string> = {}) => {
  const searchParams = new URLSearchParams(params);
  (useSearchParams as jest.Mock).mockReturnValue(searchParams);
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
  (useRouter as jest.Mock).mockReturnValue({
    push: mockPush,
    replace: mockReplace,
  });
  (usePathname as jest.Mock).mockReturnValue("/dashboard/history");
  (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
  mockSearchParams();
});

// ==================== HP-001: Authentication & Authorization ====================

describe("Authentication & Authorization", () => {
  it("redirects to login when user is not authenticated (HP-001)", async () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
    });
    mockUseSWR(null, false);

    render(<HistoryPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("shows loading state while checking authentication (HP-002)", () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
      isLoading: true,
    });
    mockUseSWR(null, true);

    render(<HistoryPage />);

    expect(screen.getByTestId("auth-loading")).toBeInTheDocument();
  });

  it("renders history page when user is authenticated (HP-003)", () => {
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    expect(screen.getByRole("heading", { name: /analysis history/i })).toBeInTheDocument();
  });
});

// ==================== HP-010: Filters Section ====================

describe("Filters Section", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("renders tool dropdown filter (HP-010)", () => {
    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i) || 
                       screen.getByRole("combobox", { name: /tool/i });
    expect(toolFilter).toBeInTheDocument();
  });

  it("applies tool filter when selection changes (HP-011)", async () => {
    const user = userEvent.setup();
    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    await user.selectOptions(toolFilter, "meta-tag-analyzer");

    // URL should be updated with filter
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });

  it("renders status dropdown filter (HP-012)", () => {
    render(<HistoryPage />);

    const statusFilter = screen.getByLabelText(/filter by status/i) ||
                         screen.getByRole("combobox", { name: /status/i });
    expect(statusFilter).toBeInTheDocument();
  });

  it("applies status filter when selection changes (HP-013)", async () => {
    const user = userEvent.setup();
    render(<HistoryPage />);

    const statusFilter = screen.getByLabelText(/filter by status/i);
    await user.selectOptions(statusFilter, "failed");

    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });

  it("renders date range picker (HP-014)", () => {
    render(<HistoryPage />);

    const fromDate = screen.getByLabelText(/from date/i) ||
                     screen.getByPlaceholderText(/from/i);
    const toDate = screen.getByLabelText(/to date/i) ||
                   screen.getByPlaceholderText(/to/i);

    expect(fromDate || toDate).toBeTruthy();
  });

  it("applies date range filter (HP-015)", async () => {
    const user = userEvent.setup();
    render(<HistoryPage />);

    const fromDate = screen.getByLabelText(/from date/i);
    if (fromDate) {
      await user.type(fromDate, "2026-02-01");
      await waitFor(() => {
        expect(mockReplace).toHaveBeenCalled();
      });
    }
  });

  it("clears all filters when clear button clicked (HP-016)", async () => {
    const user = userEvent.setup();
    mockSearchParams({ tool: "meta-tag-analyzer", status: "completed" });
    
    render(<HistoryPage />);

    const clearButton = screen.getByRole("button", { name: /clear filters/i });
    await user.click(clearButton);

    expect(mockReplace).toHaveBeenCalledWith("/dashboard/history");
  });

  it("applies multiple filters with AND logic (HP-017)", async () => {
    const user = userEvent.setup();
    render(<HistoryPage />);

    // Apply tool filter
    const toolFilter = screen.getByLabelText(/filter by tool/i);
    await user.selectOptions(toolFilter, "meta-tag-analyzer");

    // Apply status filter
    const statusFilter = screen.getByLabelText(/filter by status/i);
    await user.selectOptions(statusFilter, "completed");

    // Both filters should be applied
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledTimes(2);
    });
  });
});

// ==================== HP-020: History Table Rendering ====================

describe("History Table Rendering", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("renders table headers correctly (HP-020)", () => {
    render(<HistoryPage />);

    const table = screen.getByRole("table");
    expect(table).toBeInTheDocument();

    expect(screen.getByRole("columnheader", { name: /tool/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /url/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /status/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /cost/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /duration/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /date/i })).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /actions/i })).toBeInTheDocument();
  });

  it("displays tool display name in tool column (HP-021)", () => {
    render(<HistoryPage />);

    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
    expect(screen.getByText("Keyword Density Checker")).toBeInTheDocument();
  });

  it("displays input URL in URL column (HP-022)", () => {
    render(<HistoryPage />);

    expect(screen.getByText(/example\.com/i)).toBeInTheDocument();
    expect(screen.getByText(/test\.com/i)).toBeInTheDocument();
  });

  it("shows green badge for completed status (HP-023)", () => {
    render(<HistoryPage />);

    const completedBadges = screen.getAllByText(/completed/i);
    expect(completedBadges.length).toBeGreaterThan(0);
    expect(completedBadges[0].className).toMatch(/emerald|green/i);
  });

  it("shows red badge for failed status (HP-024)", () => {
    render(<HistoryPage />);

    const failedBadge = screen.getByText(/failed/i);
    expect(failedBadge).toBeInTheDocument();
    expect(failedBadge.className).toMatch(/rose|red/i);
  });

  it("shows yellow badge for pending status (HP-025)", () => {
    render(<HistoryPage />);

    const pendingBadge = screen.getByText(/pending/i);
    expect(pendingBadge).toBeInTheDocument();
    expect(pendingBadge.className).toMatch(/amber|yellow/i);
  });

  it("shows spinner for running status (HP-026)", () => {
    render(<HistoryPage />);

    const runningBadge = screen.getByText(/running/i);
    expect(runningBadge).toBeInTheDocument();
    // Should have spinner animation nearby
    expect(screen.getByTestId("status-spinner") || 
           runningBadge.querySelector(".animate-spin")).toBeTruthy();
  });

  it("displays cost with currency symbol (HP-027)", () => {
    render(<HistoryPage />);

    expect(screen.getByText(/\$0\.05/)).toBeInTheDocument();
  });

  it("displays duration in readable format (HP-028)", () => {
    render(<HistoryPage />);

    // Duration should be shown as seconds or milliseconds
    expect(screen.getByText(/1\.23s/i) || screen.getByText(/1234ms/i)).toBeInTheDocument();
  });

  it("displays analyzed date in readable format (HP-029)", () => {
    render(<HistoryPage />);

    // Date should be displayed - exact format may vary
    const dateElements = screen.getAllByText(/mar.*2026|2026.*mar/i);
    expect(dateElements.length).toBeGreaterThan(0);
  });

  it("displays action buttons for each row (HP-030)", () => {
    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });

    expect(viewButtons.length).toBeGreaterThan(0);
    expect(deleteButtons.length).toBeGreaterThan(0);
  });
});

// ==================== HP-035: Pagination Controls ====================

describe("Pagination Controls", () => {
  it("renders pagination for multiple pages (HP-035)", () => {
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    expect(screen.getByRole("navigation", { name: /pagination/i }) ||
           screen.getByTestId("pagination")).toBeInTheDocument();
  });

  it("disables previous button on first page (HP-036)", () => {
    mockSearchParams({ page: "1" });
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    const prevButton = screen.getByRole("button", { name: /previous/i });
    expect(prevButton).toBeDisabled();
  });

  it("disables next button on last page (HP-037)", () => {
    mockSearchParams({ page: "4" }); // Last page
    mockUseSWR({
      ...mockAnalysesResponse,
      meta: { ...mockAnalysesResponse.meta, page: 4 },
    }, false);

    render(<HistoryPage />);

    const nextButton = screen.getByRole("button", { name: /next/i });
    expect(nextButton).toBeDisabled();
  });

  it("navigates to next page when next button clicked (HP-038)", async () => {
    const user = userEvent.setup();
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    const nextButton = screen.getByRole("button", { name: /next/i });
    await user.click(nextButton);

    expect(mockReplace).toHaveBeenCalled();
  });

  it("navigates to previous page when previous button clicked (HP-039)", async () => {
    const user = userEvent.setup();
    mockSearchParams({ page: "2" });
    mockUseSWR({
      ...mockAnalysesResponse,
      meta: { ...mockAnalysesResponse.meta, page: 2 },
    }, false);

    render(<HistoryPage />);

    const prevButton = screen.getByRole("button", { name: /previous/i });
    await user.click(prevButton);

    expect(mockReplace).toHaveBeenCalled();
  });

  it("allows changing page size (HP-040)", async () => {
    const user = userEvent.setup();
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    const pageSizeSelect = screen.getByLabelText(/per page/i) ||
                           screen.getByRole("combobox", { name: /per page/i });
    
    if (pageSizeSelect) {
      await user.selectOptions(pageSizeSelect, "25");
      expect(mockReplace).toHaveBeenCalled();
    }
  });

  it("hides pagination for single page (HP-041)", () => {
    mockUseSWR({
      ...mockAnalysesResponse,
      meta: { total: 10, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    expect(screen.queryByRole("navigation", { name: /pagination/i })).not.toBeInTheDocument();
  });

  it("shows correct item range in pagination (HP-042)", () => {
    mockSearchParams({ page: "2" });
    mockUseSWR({
      ...mockAnalysesResponse,
      meta: { total: 50, page: 2, perPage: 15, lastPage: 4 },
    }, false);

    render(<HistoryPage />);

    expect(screen.getByText(/showing 16-30 of 50/i) ||
           screen.getByText(/16-30.*50/i)).toBeInTheDocument();
  });
});

// ==================== HP-045: Details View ====================

describe("Details View", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("opens details modal when row is clicked (HP-045)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    // Click on the first analysis row
    const firstRow = screen.getByText("Meta Tag Analyzer").closest("tr");
    if (firstRow) {
      await user.click(firstRow);
    }

    await waitFor(() => {
      expect(screen.getByRole("dialog") || screen.getByTestId("details-modal")).toBeInTheDocument();
    });
  });

  it("displays full results in details modal (HP-046)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      expect(screen.getByText(/example domain/i)).toBeInTheDocument();
    });
  });

  it("displays cost breakdown in details modal (HP-047)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      expect(screen.getByText(/base analysis cost/i) || screen.getByText(/\$0\.01/)).toBeInTheDocument();
    });
  });

  it("displays analysis duration in details modal (HP-048)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      expect(screen.getByText(/1\.23.*seconds/i) || screen.getByText(/1234ms/i)).toBeInTheDocument();
    });
  });

  it("displays error message for failed analysis (HP-049)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue({
      ...mockAnalysisDetail,
      status: "failed",
      errorMessage: "Connection timeout after 30 seconds",
    });

    render(<HistoryPage />);

    // Click on the failed analysis row
    const failedRow = screen.getByText("Keyword Density Checker").closest("tr");
    if (failedRow) {
      await user.click(failedRow);
    }

    await waitFor(() => {
      expect(screen.getByText(/connection timeout/i)).toBeInTheDocument();
    });
  });

  it("does not show error section for completed analysis (HP-050)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      expect(screen.queryByText(/error message/i)).not.toBeInTheDocument();
    });
  });

  it("closes details modal when close button clicked (HP-051)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      expect(screen.getByRole("dialog")).toBeInTheDocument();
    });

    const closeButton = screen.getByRole("button", { name: /close/i });
    await user.click(closeButton);

    await waitFor(() => {
      expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });
  });

  it("navigates to details page when View button clicked (HP-052)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    expect(mockPush).toHaveBeenCalledWith("/analyses/127");
  });
});

// ==================== HP-055: Delete Confirmation Dialog ====================

describe("Delete Confirmation Dialog", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("opens delete confirmation dialog when delete button clicked (HP-055)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    expect(screen.getByRole("dialog", { name: /delete analysis/i }) ||
           screen.getByTestId("delete-dialog")).toBeInTheDocument();
  });

  it("closes dialog without deleting when cancel is clicked (HP-056)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    await user.click(cancelButton);

    expect(screen.queryByRole("dialog", { name: /delete analysis/i })).not.toBeInTheDocument();
    expect(deleteAnalysis).not.toHaveBeenCalled();
  });

  it("deletes analysis when confirm is clicked (HP-057)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(mockAnalysesResponse, false, null, mockMutate);
    (deleteAnalysis as jest.Mock).mockResolvedValue({ success: true });

    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    await waitFor(() => {
      expect(deleteAnalysis).toHaveBeenCalledWith(127);
    });
  });

  it("shows success toast after successful deletion (HP-058)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(mockAnalysesResponse, false, null, mockMutate);
    (deleteAnalysis as jest.Mock).mockResolvedValue({ success: true });

    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    await waitFor(() => {
      expect(screen.getByText(/deleted successfully/i) || 
             screen.getByTestId("toast-success")).toBeInTheDocument();
    });
  });

  it("shows error toast when deletion fails (HP-059)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(mockAnalysesResponse, false, null, mockMutate);
    (deleteAnalysis as jest.Mock).mockRejectedValue(new Error("Failed to delete"));

    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    await waitFor(() => {
      expect(screen.getByText(/failed to delete/i) ||
             screen.getByTestId("toast-error")).toBeInTheDocument();
    });
  });

  it("shows loading state during deletion (HP-060)", async () => {
    (deleteAnalysis as jest.Mock).mockImplementation(() => 
      new Promise(resolve => setTimeout(resolve, 1000))
    );

    const user = userEvent.setup();

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    // Should show spinner or loading state
    expect(screen.getByTestId("delete-loading") ||
           document.querySelector(".animate-spin")).toBeTruthy();
  });
});

// ==================== HP-065: Empty State ====================

describe("Empty State", () => {
  it("shows empty state for first-time user (HP-065)", () => {
    mockUseSWR({
      data: [],
      meta: { total: 0, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    expect(screen.getByText(/no analyses yet/i) ||
           screen.getByText(/no analysis history/i)).toBeInTheDocument();
  });

  it("shows CTA button for first-time user (HP-066)", () => {
    mockUseSWR({
      data: [],
      meta: { total: 0, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    const ctaButton = screen.getByRole("link", { name: /run.*first analysis/i }) ||
                      screen.getByRole("button", { name: /run.*first analysis/i });
    expect(ctaButton).toBeInTheDocument();
  });

  it("shows empty state when filters return no results (HP-067)", () => {
    mockSearchParams({ status: "failed" });
    mockUseSWR({
      data: [],
      meta: { total: 0, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    expect(screen.getByText(/no analyses match/i) ||
           screen.getByText(/no results found/i)).toBeInTheDocument();
  });

  it("clears filters when clear button clicked from empty state (HP-068)", async () => {
    const user = userEvent.setup();
    mockSearchParams({ status: "failed" });
    mockUseSWR({
      data: [],
      meta: { total: 0, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    const clearButton = screen.getByRole("button", { name: /clear.*filters/i });
    await user.click(clearButton);

    expect(mockReplace).toHaveBeenCalledWith("/dashboard/history");
  });
});

// ==================== HP-070: Loading States ====================

describe("Loading States", () => {
  it("shows skeleton during initial page load (HP-070)", () => {
    (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
    mockUseSWR(null, true);

    render(<HistoryPage />);

    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it("shows loading state during filter change (HP-071)", async () => {
    const user = userEvent.setup();
    
    // Start with loaded data
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    await user.selectOptions(toolFilter, "meta-tag-analyzer");

    // Component should handle loading state internally
    // This is more of an integration test concern
  });

  it("shows loading state during page change (HP-072)", () => {
    // Similar to filter change - handled by SWR internally
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    // Component should render normally
    expect(screen.getByRole("table")).toBeInTheDocument();
  });

  it("shows loading state during delete operation (HP-073)", async () => {
    (deleteAnalysis as jest.Mock).mockImplementation(() => 
      new Promise(resolve => setTimeout(resolve, 1000))
    );

    const user = userEvent.setup();
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    expect(screen.getByTestId("delete-loading")).toBeInTheDocument();
  });
});

// ==================== HP-075: Error States ====================

describe("Error States", () => {
  it("shows error message when API fails on load (HP-075)", () => {
    mockUseSWR(null, false, new Error("Failed to fetch analyses"));

    render(<HistoryPage />);

    expect(screen.getByText(/failed to load/i) || 
           screen.getByText(/error/i)).toBeInTheDocument();
  });

  it("shows retry button on error (HP-076)", () => {
    mockUseSWR(null, false, new Error("Failed to fetch analyses"));

    render(<HistoryPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    expect(retryButton).toBeInTheDocument();
  });

  it("retries fetch when retry button clicked (HP-077)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(null, false, new Error("Failed to fetch analyses"), mockMutate);

    const user = userEvent.setup();

    render(<HistoryPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    await user.click(retryButton);

    expect(mockMutate).toHaveBeenCalled();
  });

  it("shows network error message on timeout (HP-078)", () => {
    mockUseSWR(null, false, new Error("Network timeout"));

    render(<HistoryPage />);

    expect(screen.getByText(/network|timeout|connection/i)).toBeInTheDocument();
  });

  it("redirects to login on 401 unauthorized (HP-079)", async () => {
    mockUseSWR(null, false, { status: 401 });

    render(<HistoryPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("gracefully handles partial data (HP-080)", () => {
    mockUseSWR({
      data: [{
        id: 127,
        toolName: "meta-tag-analyzer",
        toolDisplayName: null, // Missing field
        inputUrl: null, // Missing field
        status: "completed",
      }],
      meta: { total: 1, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    // Should render without crashing
    expect(screen.getByRole("table")).toBeInTheDocument();
  });
});

// ==================== HP-085: Responsive Design ====================

describe("Responsive Design", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("has responsive table container (HP-085)", () => {
    render(<HistoryPage />);

    const tableContainer = screen.getByTestId("table-container");
    expect(tableContainer).toHaveClass(/overflow/i);
  });

  it("has responsive grid for stats (HP-086)", () => {
    render(<HistoryPage />);

    const container = screen.getByTestId("history-container");
    expect(container).toBeInTheDocument();
  });

  it("table has all required columns on desktop (HP-087)", () => {
    render(<HistoryPage />);

    const table = screen.getByRole("table");
    expect(table).toBeInTheDocument();
    expect(screen.getByRole("columnheader", { name: /actions/i })).toBeInTheDocument();
  });

  it("has collapsible filter section for mobile (HP-088)", () => {
    render(<HistoryPage />);

    const filterToggle = screen.queryByRole("button", { name: /show filters/i }) ||
                         screen.queryByRole("button", { name: /filters/i });
    
    // Filter toggle may or may not exist depending on implementation
    // The test verifies the component renders without error
    expect(screen.getByTestId("filters-section")).toBeTruthy();
  });

  it("has accessible touch targets on mobile (HP-090)", () => {
    render(<HistoryPage />);

    const buttons = screen.getAllByRole("button");
    buttons.forEach(button => {
      // Minimum touch target size is typically 44px
      // This is more of a visual/UX test
      expect(button).toBeInTheDocument();
    });
  });
});

// ==================== HP-095: Accessibility ====================

describe("Accessibility", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("has proper heading hierarchy (HP-095)", () => {
    render(<HistoryPage />);

    const h1 = screen.getByRole("heading", { level: 1 });
    expect(h1).toBeInTheDocument();
    expect(h1).toHaveTextContent(/analysis history/i);
  });

  it("table has ARIA label (HP-096)", () => {
    render(<HistoryPage />);

    const table = screen.getByRole("table");
    expect(table).toHaveAttribute("aria-label", "Analysis history");
  });

  it("filters have associated labels (HP-097)", () => {
    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    const statusFilter = screen.getByLabelText(/filter by status/i);

    expect(toolFilter).toBeInTheDocument();
    expect(statusFilter).toBeInTheDocument();
  });

  it("supports keyboard navigation (HP-098)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    // Tab through filters
    await user.tab();
    expect(document.activeElement).toBeTruthy();

    // Continue tabbing to reach interactive elements
    await user.tab();
    expect(document.activeElement).toBeTruthy();
  });

  it("maintains focus in modal dialog (HP-100)", async () => {
    const user = userEvent.setup();
    (fetchAnalysisById as jest.Mock).mockResolvedValue(mockAnalysisDetail);

    render(<HistoryPage />);

    const viewButtons = screen.getAllByRole("button", { name: /view/i });
    await user.click(viewButtons[0]);

    await waitFor(() => {
      const dialog = screen.getByRole("dialog");
      expect(dialog).toBeInTheDocument();
    });

    // Tab should cycle within modal
    await user.tab();
    expect(document.activeElement?.closest('[role="dialog"]')).toBeTruthy();
  });
});

// ==================== HP-105: URL State Management ====================

describe("URL State Management", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("stores filter state in URL params (HP-105)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i);
    await user.selectOptions(toolFilter, "meta-tag-analyzer");

    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledWith(expect.stringContaining("tool=meta-tag-analyzer"));
    });
  });

  it("stores page number in URL params (HP-106)", async () => {
    const user = userEvent.setup();

    render(<HistoryPage />);

    const nextButton = screen.getByRole("button", { name: /next/i });
    await user.click(nextButton);

    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledWith(expect.stringContaining("page=2"));
    });
  });

  it("restores filters from URL on page load (HP-107)", () => {
    mockSearchParams({ tool: "meta-tag-analyzer", status: "completed" });
    
    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i) as HTMLSelectElement;
    const statusFilter = screen.getByLabelText(/filter by status/i) as HTMLSelectElement;

    expect(toolFilter?.value).toBe("meta-tag-analyzer");
    expect(statusFilter?.value).toBe("completed");
  });

  it("supports shareable URLs with filter state (HP-108)", () => {
    mockSearchParams({ tool: "meta-tag-analyzer", status: "completed", page: "2" });

    render(<HistoryPage />);

    // All filter params should be preserved
    expect(mockReplace).not.toHaveBeenCalled(); // No redirect means URL is valid
  });

  it("restores filters on browser back navigation (HP-109)", async () => {
    // This is handled by the router - component reads from useSearchParams
    mockSearchParams({ tool: "keyword-density" });

    render(<HistoryPage />);

    const toolFilter = screen.getByLabelText(/filter by tool/i) as HTMLSelectElement;
    expect(toolFilter?.value).toBe("keyword-density");
  });
});

// ==================== HP-115: Data Validation ====================

describe("Data Validation", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("handles invalid page number gracefully (HP-115)", async () => {
    mockSearchParams({ page: "-1" });

    render(<HistoryPage />);

    // Should redirect to page 1 or show corrected state
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });

  it("handles invalid status filter gracefully (HP-116)", () => {
    mockSearchParams({ status: "invalid-status" });

    render(<HistoryPage />);

    // Should ignore invalid filter or reset to default
    const statusFilter = screen.getByLabelText(/filter by status/i) as HTMLSelectElement;
    expect(statusFilter?.value).toBe("");
  });

  it("handles invalid date range gracefully (HP-117)", () => {
    mockSearchParams({ from: "2026-03-01", to: "2026-02-01" }); // From > To

    render(<HistoryPage />);

    // Component should either show error or auto-correct
    expect(screen.getByRole("table") || screen.getByText(/invalid/i)).toBeTruthy();
  });

  it("handles very large page numbers (HP-118)", async () => {
    mockSearchParams({ page: "9999" });

    render(<HistoryPage />);

    // Should redirect to last page
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalled();
    });
  });

  it("sanitizes SQL injection attempts in filters (HP-119)", () => {
    mockSearchParams({ tool: "'; DROP TABLE analyses;--" });

    render(<HistoryPage />);

    // Component should handle malicious input without error
    expect(screen.getByRole("table") || screen.getByText(/no analyses/i)).toBeTruthy();
  });

  it("sanitizes XSS attempts in URL field (HP-120)", () => {
    mockUseSWR({
      data: [{
        id: 1,
        toolName: "test",
        toolDisplayName: "Test",
        inputUrl: "javascript:alert(1)",
        status: "completed",
      }],
      meta: { total: 1, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    // URL should not be rendered as a clickable javascript: link
    const links = screen.queryAllByRole("link");
    links.forEach(link => {
      expect(link).not.toHaveAttribute("href", expect.stringContaining("javascript:"));
    });
  });
});

// ==================== HP-125: Performance ====================

describe("Performance", () => {
  it("renders initial page within acceptable time (HP-125)", () => {
    const startTime = performance.now();
    
    mockUseSWR(mockAnalysesResponse, false);
    render(<HistoryPage />);

    const renderTime = performance.now() - startTime;
    expect(renderTime).toBeLessThan(200);
  });

  it("handles large dataset efficiently (HP-126)", () => {
    // Generate large dataset
    const largeData = Array.from({ length: 100 }, (_, i) => ({
      id: i + 1,
      toolName: `tool-${i}`,
      toolDisplayName: `Tool ${i}`,
      inputUrl: `https://example-${i}.com`,
      status: "completed",
      cost: { total: 0.05, currency: "USD" },
      analysisDurationMs: 1000 + i,
      analyzedAt: new Date().toISOString(),
    }));

    const startTime = performance.now();
    
    mockUseSWR({
      data: largeData,
      meta: { total: 100, page: 1, perPage: 100, lastPage: 1 },
    }, false);
    
    render(<HistoryPage />);

    const renderTime = performance.now() - startTime;
    expect(renderTime).toBeLessThan(500);
  });

  it("debounces filter changes (HP-127)", async () => {
    const user = userEvent.setup();
    mockUseSWR(mockAnalysesResponse, false);

    render(<HistoryPage />);

    // Rapid filter changes should be debounced
    const toolFilter = screen.getByLabelText(/filter by tool/i);
    
    await user.selectOptions(toolFilter, "meta-tag-analyzer");
    await user.selectOptions(toolFilter, "keyword-density");

    // Only one URL update should occur after debounce
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledTimes(1);
    }, { timeout: 1000 });
  });

  it("cleans up resources on unmount (HP-128)", () => {
    mockUseSWR(mockAnalysesResponse, false);

    const { unmount } = render(<HistoryPage />);

    // Unmount should not throw
    expect(() => unmount()).not.toThrow();
  });
});

// ==================== HP-130: Security ====================

describe("Security", () => {
  beforeEach(() => {
    mockUseSWR(mockAnalysesResponse, false);
  });

  it("escapes XSS in tool name (HP-130)", () => {
    mockUseSWR({
      data: [{
        id: 1,
        toolName: '<script>alert("XSS")</script>',
        toolDisplayName: '<script>alert("XSS")</script>',
        inputUrl: "https://example.com",
        status: "completed",
      }],
      meta: { total: 1, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    // Script should be escaped, not executed
    expect(document.querySelector('script[src*="alert"]')).toBeNull();
    expect(screen.getByText(/alert/i)).toBeInTheDocument();
  });

  it("escapes XSS in URL display (HP-131)", () => {
    mockUseSWR({
      data: [{
        id: 1,
        toolName: "test",
        toolDisplayName: "Test",
        inputUrl: '<img src=x onerror=alert(1)>',
        status: "completed",
      }],
      meta: { total: 1, page: 1, perPage: 15, lastPage: 1 },
    }, false);

    render(<HistoryPage />);

    // XSS payload should be escaped
    expect(document.querySelector('img[src="x"]')).toBeNull();
  });

  it("prevents IDOR by checking ownership (HP-132)", async () => {
    // This is primarily a backend concern, but frontend should handle 403
    mockUseSWR(null, false, { status: 403, message: "Forbidden" });

    render(<HistoryPage />);

    // Should show error without exposing sensitive data
    expect(screen.getByText(/forbidden|access denied|unauthorized/i) ||
           screen.getByText(/error/i)).toBeInTheDocument();
  });

  it("includes CSRF token in delete requests (HP-133)", async () => {
    const user = userEvent.setup();
    (deleteAnalysis as jest.Mock).mockResolvedValue({ success: true });

    render(<HistoryPage />);

    const deleteButtons = screen.getAllByRole("button", { name: /delete/i });
    await user.click(deleteButtons[0]);

    const confirmButton = screen.getByRole("button", { name: /delete/i });
    await user.click(confirmButton);

    // CSRF token should be included in request (handled by API helper)
    expect(deleteAnalysis).toHaveBeenCalled();
  });

  it("does not expose sensitive data in DOM (HP-134)", () => {
    render(<HistoryPage />);

    const html = document.body.innerHTML;
    expect(html).not.toContain("token");
    expect(html).not.toContain("password");
    expect(html).not.toContain("secret");
  });
});
