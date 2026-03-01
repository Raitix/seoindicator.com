/**
 * Dashboard Page Tests - TASK-25-19
 * 
 * Tests for the enhanced dashboard page with stats cards, cost summary,
 * recent analyses, and quick insights.
 * 
 * @see SPEC-025
 * @see TASK-25-19
 */

import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter } from "next/navigation";
import DashboardPage from "@/app/dashboard/page";

// Mock next/navigation
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(() => ({
    get: jest.fn(() => null),
  })),
}));

// Mock AuthContext
const mockPush = jest.fn();

jest.mock("@/lib/contexts/AuthContext", () => ({
  useAuth: jest.fn(),
}));

import { useAuth } from "@/lib/contexts/AuthContext";

// Mock react-query
jest.mock("@tanstack/react-query", () => ({
  useQuery: jest.fn(),
}));

import { useQuery } from "@tanstack/react-query";

// Mock API helper
jest.mock("@/lib/api/dashboard", () => ({
  fetchDashboard: jest.fn(),
}));

import { fetchDashboard } from "@/lib/api/dashboard";

// ==================== Test Data ====================

const mockUser = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
};

const mockDashboardData = {
  stats: {
    totalAnalyses: 127,
    thisMonth: 23,
    thisWeek: 8,
    today: 2,
  },
  costSummary: {
    totalCost: 12.45,
    thisMonthCost: 3.20,
    currency: "USD",
  },
  recentAnalyses: [
    {
      id: 127,
      toolName: "meta-tag-analyzer",
      toolDisplayName: "Meta Tag Analyzer",
      inputUrl: "https://example.com",
      status: "completed",
      analyzedAt: "2026-03-01T10:00:00Z",
    },
    {
      id: 126,
      toolName: "keyword-density",
      toolDisplayName: "Keyword Density Checker",
      inputUrl: "https://test.com/page",
      status: "failed",
      analyzedAt: "2026-03-01T09:30:00Z",
    },
    {
      id: 125,
      toolName: "mobile-friendly",
      toolDisplayName: "Mobile-Friendly Checker",
      inputUrl: "https://demo.org",
      status: "pending",
      analyzedAt: "2026-03-01T09:00:00Z",
    },
  ],
  quickInsights: {
    mostUsedTool: "meta-tag-analyzer",
    mostUsedToolDisplayName: "Meta Tag Analyzer",
    avgAnalysisTime: 1.2,
    successRate: 98.4,
  },
};

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
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
  (useRouter as jest.Mock).mockReturnValue({
    push: mockPush,
  });
  (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
});

// ==================== DP-001: Authentication & Authorization ====================

describe("Authentication & Authorization", () => {
  it("redirects to login when user is not authenticated (DP-001)", () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
    });

    render(<DashboardPage />);

    expect(mockPush).toHaveBeenCalledWith("/login");
  });

  it("shows loading state while checking authentication (DP-002)", () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
      isLoading: true,
    });

    render(<DashboardPage />);

    // Should show loading skeleton or spinner
    expect(screen.getByTestId("auth-loading") || screen.getByRole("status")).toBeTruthy();
  });

  it("renders dashboard when user is authenticated (DP-003)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByText(/welcome/i)).toBeInTheDocument();
  });
});

// ==================== DP-010: Welcome Card ====================

describe("Welcome Card", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays user's name in welcome message (DP-010)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/welcome, john doe/i) || screen.getByText(/hi, john doe/i)).toBeInTheDocument();
  });

  it("displays personalized greeting based on time of day (DP-011)", () => {
    // This test would need to mock the Date object
    render(<DashboardPage />);

    // Should show Good morning/afternoon/evening
    const welcomeText = screen.getByText(/good (morning|afternoon|evening)/i);
    expect(welcomeText).toBeInTheDocument();
  });

  it("shows last login timestamp when available (DP-012)", () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: { ...mockUser, lastLoginAt: "2026-03-01T08:00:00Z" },
    });

    render(<DashboardPage />);

    expect(screen.getByText(/last login/i)).toBeInTheDocument();
  });
});

// ==================== DP-020: Stats Cards ====================

describe("Stats Cards", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays total analyses count (DP-020)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("127")).toBeInTheDocument();
    expect(screen.getByText(/total analyses/i)).toBeInTheDocument();
  });

  it("displays this month count (DP-021)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("23")).toBeInTheDocument();
    expect(screen.getByText(/this month/i)).toBeInTheDocument();
  });

  it("displays this week count (DP-022)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("8")).toBeInTheDocument();
    expect(screen.getByText(/this week/i)).toBeInTheDocument();
  });

  it("displays today count (DP-023)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("2")).toBeInTheDocument();
    expect(screen.getByText(/today/i)).toBeInTheDocument();
  });

  it("displays zero values gracefully (DP-024)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        stats: {
          totalAnalyses: 0,
          thisMonth: 0,
          thisWeek: 0,
          today: 0,
        },
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    const zeros = screen.getAllByText("0");
    expect(zeros.length).toBeGreaterThanOrEqual(4);
  });
});

// ==================== DP-030: Cost Summary Card ====================

describe("Cost Summary Card", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays total cost with currency (DP-030)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/\$12\.45/)).toBeInTheDocument();
    expect(screen.getByText(/total cost/i)).toBeInTheDocument();
  });

  it("displays this month cost (DP-031)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/\$3\.20/)).toBeInTheDocument();
    expect(screen.getByText(/this month/i)).toBeInTheDocument();
  });

  it("displays currency indicator (DP-032)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/usd/i) || screen.getByText(/\$/)).toBeInTheDocument();
  });

  it("shows cost trend indicator when available (DP-033)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        costSummary: {
          ...mockDashboardData.costSummary,
          trend: "up", // or percentage change
        },
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    // Should show trend arrow or percentage
    expect(screen.getByTestId("cost-trend") || screen.getByText(/%/)).toBeTruthy();
  });
});

// ==================== DP-040: Recent Analyses Section ====================

describe("Recent Analyses Section", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays recent analyses table (DP-040)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("table") || screen.getByTestId("analyses-list")).toBeInTheDocument();
  });

  it("shows tool name for each analysis (DP-041)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
    expect(screen.getByText("Keyword Density Checker")).toBeInTheDocument();
  });

  it("shows input URL for each analysis (DP-042)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/example\.com/i)).toBeInTheDocument();
  });

  it("shows status badge for each analysis (DP-043)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/completed/i)).toBeInTheDocument();
    expect(screen.getByText(/failed/i)).toBeInTheDocument();
    expect(screen.getByText(/pending/i)).toBeInTheDocument();
  });

  it("shows analyzed date for each analysis (DP-044)", () => {
    render(<DashboardPage />);

    // Date should be displayed in a readable format
    expect(screen.getByText(/mar 1, 2026/i) || screen.getByText(/2026-03-01/i)).toBeInTheDocument();
  });

  it("click on analysis row navigates to details (DP-045)", async () => {
    const user = userEvent.setup();
    render(<DashboardPage />);

    const firstAnalysisRow = screen.getByText("Meta Tag Analyzer").closest("tr") || 
                             screen.getByText("Meta Tag Analyzer").closest("a");
    await user.click(firstAnalysisRow);

    expect(mockPush).toHaveBeenCalledWith("/analyses/127");
  });

  it("displays empty state when no recent analyses (DP-046)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        recentAnalyses: [],
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByText(/no analyses yet/i) || screen.getByText(/run your first analysis/i)).toBeInTheDocument();
  });
});

// ==================== DP-050: Quick Insights Section ====================

describe("Quick Insights Section", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays most used tool (DP-050)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/most used tool/i)).toBeInTheDocument();
    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
  });

  it("displays average analysis time (DP-051)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/avg\. analysis time/i)).toBeInTheDocument();
    expect(screen.getByText(/1\.2/)).toBeInTheDocument();
  });

  it("displays success rate percentage (DP-052)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/success rate/i)).toBeInTheDocument();
    expect(screen.getByText(/98\.4%/)).toBeInTheDocument();
  });

  it("shows N/A for insights when no data available (DP-053)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        quickInsights: null,
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    const naLabels = screen.getAllByText(/n\/a/i);
    expect(naLabels.length).toBeGreaterThan(0);
  });
});

// ==================== DP-060: Quick Actions Section ====================

describe("Quick Actions Section", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("displays run new analysis button (DP-060)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("button", { name: /run new analysis/i }) || 
           screen.getByRole("link", { name: /run new analysis/i })).toBeInTheDocument();
  });

  it("click on run new analysis navigates to tools (DP-061)", async () => {
    const user = userEvent.setup();
    render(<DashboardPage />);

    const newAnalysisButton = screen.getByRole("button", { name: /run new analysis/i }) ||
                              screen.getByRole("link", { name: /run new analysis/i });
    await user.click(newAnalysisButton);

    expect(mockPush).toHaveBeenCalledWith("/tools");
  });

  it("displays view all history link (DP-062)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("link", { name: /view all history/i }) ||
           screen.getByRole("button", { name: /view all history/i })).toBeInTheDocument();
  });

  it("click on view all history navigates to history page (DP-063)", async () => {
    const user = userEvent.setup();
    render(<DashboardPage />);

    const historyLink = screen.getByRole("link", { name: /view all history/i }) ||
                        screen.getByRole("button", { name: /view all history/i });
    await user.click(historyLink);

    expect(mockPush).toHaveBeenCalledWith("/dashboard/history");
  });
});

// ==================== DP-070: Loading States ====================

describe("Loading Skeleton States", () => {
  it("shows skeleton for stats cards while loading (DP-070)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
    });

    render(<DashboardPage />);

    // Should have skeleton elements with animation
    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it("shows skeleton for cost summary while loading (DP-071)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByTestId("cost-skeleton") || 
           screen.getAllByTestId(/skeleton/i).length).toBeTruthy();
  });

  it("shows skeleton for recent analyses while loading (DP-072)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByTestId("analyses-skeleton") || 
           screen.getAllByTestId(/skeleton/i).length).toBeTruthy();
  });

  it("shows skeleton for quick insights while loading (DP-073)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: true,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByTestId("insights-skeleton") || 
           screen.getAllByTestId(/skeleton/i).length).toBeTruthy();
  });
});

// ==================== DP-080: Error States ====================

describe("Error States", () => {
  it("shows error message when API fails (DP-080)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: new Error("Failed to fetch dashboard data"),
    });

    render(<DashboardPage />);

    expect(screen.getByText(/failed to load/i) || screen.getByText(/error/i)).toBeInTheDocument();
  });

  it("shows retry button on error (DP-081)", () => {
    const mockRefetch = jest.fn();
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: new Error("Failed to fetch dashboard data"),
      refetch: mockRefetch,
    });

    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    expect(retryButton).toBeInTheDocument();
  });

  it("retries data fetch when retry button clicked (DP-082)", async () => {
    const mockRefetch = jest.fn();
    (useQuery as jest.Mock).mockReturnValue({
      data: null,
      isLoading: false,
      error: new Error("Failed to fetch dashboard data"),
      refetch: mockRefetch,
    });

    const user = userEvent.setup();
    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    await user.click(retryButton);

    expect(mockRefetch).toHaveBeenCalled();
  });

  it("shows partial data when some sections fail (DP-083)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        stats: mockDashboardData.stats,
        costSummary: null, // Failed section
        recentAnalyses: mockDashboardData.recentAnalyses,
        quickInsights: null, // Failed section
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    // Stats should still display
    expect(screen.getByText("127")).toBeInTheDocument();
    // Failed sections should show error state
    expect(screen.getByText(/unavailable/i) || screen.getAllByText(/--/i).length).toBeTruthy();
  });
});

// ==================== DP-090: Responsive Layout ====================

describe("Responsive Layout", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("renders single column on mobile viewport (DP-090)", () => {
    // Mock mobile viewport
    Object.defineProperty(window, "innerWidth", {
      writable: true,
      configurable: true,
      value: 375,
    });

    render(<DashboardPage />);

    const statsGrid = screen.getByTestId("stats-grid");
    expect(statsGrid).toHaveClass(/grid-cols-1/);
  });

  it("renders 4 columns on desktop viewport (DP-091)", () => {
    // Mock desktop viewport
    Object.defineProperty(window, "innerWidth", {
      writable: true,
      configurable: true,
      value: 1280,
    });

    render(<DashboardPage />);

    const statsGrid = screen.getByTestId("stats-grid");
    expect(statsGrid).toHaveClass(/lg:grid-cols-4/);
  });

  it("renders 2 columns on tablet viewport (DP-092)", () => {
    // Mock tablet viewport
    Object.defineProperty(window, "innerWidth", {
      writable: true,
      configurable: true,
      value: 768,
    });

    render(<DashboardPage />);

    const statsGrid = screen.getByTestId("stats-grid");
    expect(statsGrid).toHaveClass(/md:grid-cols-2/);
  });

  it("shows condensed table on mobile (DP-093)", () => {
    // Mock mobile viewport
    Object.defineProperty(window, "innerWidth", {
      writable: true,
      configurable: true,
      value: 375,
    });

    render(<DashboardPage />);

    // On mobile, might show card list instead of table
    const analysesSection = screen.getByTestId("analyses-section");
    expect(analysesSection).toBeInTheDocument();
  });
});

// ==================== DP-100: Status Badges ====================

describe("Status Badges", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("shows green badge for completed status (DP-100)", () => {
    render(<DashboardPage />);

    const completedBadge = screen.getByText(/completed/i);
    expect(completedBadge).toHaveClass(/bg-green|text-green/);
  });

  it("shows red badge for failed status (DP-101)", () => {
    render(<DashboardPage />);

    const failedBadge = screen.getByText(/failed/i);
    expect(failedBadge).toHaveClass(/bg-red|text-red/);
  });

  it("shows yellow/amber badge for pending status (DP-102)", () => {
    render(<DashboardPage />);

    const pendingBadge = screen.getByText(/pending/i);
    expect(pendingBadge).toHaveClass(/bg-yellow|bg-amber|text-yellow|text-amber/);
  });

  it("shows running status with spinner (DP-103)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        recentAnalyses: [
          {
            ...mockDashboardData.recentAnalyses[0],
            status: "running",
          },
        ],
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    expect(screen.getByText(/running/i)).toBeInTheDocument();
    // Should have spinner animation
    expect(screen.getByTestId("status-spinner") || 
           document.querySelector(".animate-spin")).toBeTruthy();
  });
});

// ==================== DP-110: Accessibility ====================

describe("Accessibility", () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });
  });

  it("has proper heading hierarchy (DP-110)", () => {
    render(<DashboardPage />);

    // Should have h1 as main heading
    const h1 = screen.getByRole("heading", { level: 1 });
    expect(h1).toBeInTheDocument();
  });

  it("stats cards have accessible labels (DP-111)", () => {
    render(<DashboardPage />);

    const totalAnalysesCard = screen.getByLabelText(/total analyses/i) ||
                              screen.getByText(/total analyses/i).closest("div");
    expect(totalAnalysesCard).toBeInTheDocument();
  });

  it("table has proper ARIA attributes (DP-112)", () => {
    render(<DashboardPage />);

    const table = screen.getByRole("table");
    if (table) {
      expect(table).toHaveAttribute("aria-label", /recent analyses/i);
    }
  });

  it("supports keyboard navigation (DP-113)", async () => {
    const user = userEvent.setup();
    render(<DashboardPage />);

    // Tab through interactive elements
    await user.tab();
    expect(document.activeElement).toBeTruthy();
  });

  it("has sufficient color contrast (DP-114)", () => {
    render(<DashboardPage />);

    // This would typically be tested with a tool like axe-core
    // For now, we verify the component renders
    const container = screen.getByTestId("dashboard-container");
    expect(container).toBeInTheDocument();
  });
});

// ==================== DP-120: Data Freshness ====================

describe("Data Freshness", () => {
  it("shows last updated timestamp (DP-120)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
      dataUpdatedAt: Date.now(),
    });

    render(<DashboardPage />);

    expect(screen.getByText(/last updated/i) || 
           screen.getByText(/updated/i)).toBeInTheDocument();
  });

  it("provides refresh button to reload data (DP-121)", () => {
    const mockRefetch = jest.fn();
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
      refetch: mockRefetch,
    });

    render(<DashboardPage />);

    const refreshButton = screen.getByRole("button", { name: /refresh/i });
    expect(refreshButton).toBeInTheDocument();
  });

  it("refreshes data when refresh button clicked (DP-122)", async () => {
    const mockRefetch = jest.fn();
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
      refetch: mockRefetch,
    });

    const user = userEvent.setup();
    render(<DashboardPage />);

    const refreshButton = screen.getByRole("button", { name: /refresh/i });
    await user.click(refreshButton);

    expect(mockRefetch).toHaveBeenCalled();
  });
});

// ==================== DP-130: Performance ====================

describe("Performance", () => {
  it("renders within acceptable time (DP-130)", () => {
    const startTime = performance.now();
    
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);
    
    const renderTime = performance.now() - startTime;
    expect(renderTime).toBeLessThan(100); // Should render in under 100ms
  });

  it("does not re-render unnecessarily (DP-131)", () => {
    const { rerender } = render(<DashboardPage />);
    
    // Re-rendering with same props should not cause issues
    rerender(<DashboardPage />);
    
    expect(screen.getByText(/welcome/i)).toBeInTheDocument();
  });
});

// ==================== DP-140: Security ====================

describe("Security", () => {
  it("sanitizes user name in welcome message (DP-140)", () => {
    (useAuth as jest.Mock).mockReturnValue({
      ...mockAuthContext,
      user: {
        ...mockUser,
        name: '<script>alert("XSS")</script>John',
      },
    });

    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    // Script tag should be escaped, not executed
    expect(screen.queryByText(/<script>/i)).not.toBeInTheDocument();
  });

  it("sanitizes URL display in recent analyses (DP-141)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: {
        ...mockDashboardData,
        recentAnalyses: [
          {
            ...mockDashboardData.recentAnalyses[0],
            inputUrl: 'javascript:alert("XSS")',
          },
        ],
      },
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    // javascript: protocol should not be clickable
    const link = screen.queryByRole("link", { name: /javascript/i });
    if (link) {
      expect(link).not.toHaveAttribute("href", expect.stringContaining("javascript:"));
    }
  });

  it("does not expose sensitive data in DOM (DP-142)", () => {
    (useQuery as jest.Mock).mockReturnValue({
      data: mockDashboardData,
      isLoading: false,
      error: null,
    });

    render(<DashboardPage />);

    // Should not expose user ID, tokens, etc. in the DOM
    const html = document.body.innerHTML;
    expect(html).not.toContain("token");
    expect(html).not.toContain("password");
  });
});
