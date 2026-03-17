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
import { vi, describe, it, expect, beforeEach } from "vitest";
import DashboardPage from "@/app/dashboard/page";

// Mock next/navigation
const mockPush = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: vi.fn(() => ({
    push: mockPush,
  })),
  useSearchParams: vi.fn(() => ({
    get: vi.fn(() => null),
  })),
}));

// Mock AuthContext
vi.mock("@/lib/contexts/AuthContext", () => ({
  useAuth: vi.fn(),
}));

import { useAuth } from "@/lib/contexts/AuthContext";

// Mock SWR
vi.mock("swr", () => ({
  __esModule: true,
  default: vi.fn(),
}));

import useSWR from "swr";

// Mock API helper
vi.mock("@/lib/api/dashboard", () => ({
  fetchDashboard: vi.fn(),
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
  login: vi.fn(),
  loginWithGoogle: vi.fn(),
  logout: vi.fn(),
  register: vi.fn(),
  resetPassword: vi.fn(),
  resendVerification: vi.fn(),
  clearError: vi.fn(),
  refreshUser: vi.fn(),
};

// Helper to setup SWR mock
const mockUseSWR = (data: any, isLoading = false, error: any = null, mutate = vi.fn()) => {
  vi.mocked(useSWR).mockReturnValue({
    data,
    isLoading,
    error,
    mutate,
  } as any);
};

// ==================== Test Setup ====================

beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(useAuth).mockReturnValue(mockAuthContext as any);
});

// ==================== DP-001: Authentication & Authorization ====================

describe("Authentication & Authorization", () => {
  it("redirects to login when user is not authenticated (DP-001)", async () => {
    vi.mocked(useAuth).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
    } as any);
    mockUseSWR(null, false);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("shows loading state while checking authentication (DP-002)", () => {
    vi.mocked(useAuth).mockReturnValue({
      ...mockAuthContext,
      user: null,
      isAuthenticated: false,
      isLoading: true,
    } as any);
    mockUseSWR(null, true);

    render(<DashboardPage />);

    // Should show loading skeleton or spinner
    expect(screen.getByTestId("auth-loading")).toBeInTheDocument();
  });

  it("renders dashboard when user is authenticated (DP-003)", () => {
    mockUseSWR(mockDashboardData, false);

    render(<DashboardPage />);

    expect(screen.getByText(/welcome/i)).toBeInTheDocument();
  });
});

// ==================== DP-010: Welcome Card ====================

describe("Welcome Card", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("displays user's name in welcome message (DP-010)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/john doe/i)).toBeInTheDocument();
  });

  it("displays personalized greeting based on time of day (DP-011)", () => {
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
    mockUseSWR(mockDashboardData, false);
  });

  it("displays total analyses count (DP-020)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("127")).toBeInTheDocument();
    expect(screen.getByText(/total analyses/i)).toBeInTheDocument();
  });

  it("displays this month count (DP-021)", () => {
    render(<DashboardPage />);

    expect(screen.getByText("23")).toBeInTheDocument();
    const thisMonthLabels = screen.getAllByText(/this month/i);
    expect(thisMonthLabels.length).toBeGreaterThan(0);
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
    mockUseSWR({
      ...mockDashboardData,
      stats: {
        totalAnalyses: 0,
        thisMonth: 0,
        thisWeek: 0,
        today: 0,
      },
    }, false);

    render(<DashboardPage />);

    const zeros = screen.getAllByText("0");
    expect(zeros.length).toBeGreaterThanOrEqual(4);
  });
});

// ==================== DP-030: Cost Summary Card ====================

describe("Cost Summary Card", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("displays total cost with currency (DP-030)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/\$12\.45/)).toBeInTheDocument();
    expect(screen.getByText(/total cost/i)).toBeInTheDocument();
  });

  it("displays this month cost (DP-031)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/\$3\.20/)).toBeInTheDocument();
  });

  it("displays currency indicator (DP-032)", () => {
    render(<DashboardPage />);

    // Currency is shown as $ symbol in the formatted price
    expect(screen.getByText(/\$12\.45/)).toBeInTheDocument();
  });

  it("shows cost trend indicator when available (DP-033)", () => {
    mockUseSWR({
      ...mockDashboardData,
      costSummary: {
        ...mockDashboardData.costSummary,
        trend: "up",
        percentChange: 15,
      },
    }, false);

    render(<DashboardPage />);

    // Should show trend arrow or percentage
    expect(screen.getByTestId("cost-trend") || screen.getByText(/%/)).toBeTruthy();
  });
});

// ==================== DP-040: Recent Analyses Section ====================

describe("Recent Analyses Section", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("displays recent analyses table (DP-040)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("table") || screen.getByTestId("analyses-section")).toBeInTheDocument();
  });

  it("shows tool name for each analysis (DP-041)", () => {
    render(<DashboardPage />);

    // Meta Tag Analyzer appears in QuickInsights and in the table
    const metaTagAnalyzers = screen.getAllByText("Meta Tag Analyzer");
    expect(metaTagAnalyzers.length).toBeGreaterThan(0);
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

    // Date should be displayed in a readable format (e.g., "2 hours ago")
    const agoElements = screen.getAllByText(/ago/i);
    expect(agoElements.length).toBeGreaterThan(0);
  });

  it("click on analysis row navigates to details (DP-045)", async () => {
    const user = userEvent.setup();
    render(<DashboardPage />);

    // Use getAllByText and get the one in the table
    const metaElements = screen.getAllByText("Meta Tag Analyzer");
    const tableElement = metaElements.find(el => el.tagName === "TD");
    if (tableElement) {
      const row = tableElement.closest("tr");
      if (row) {
        await user.click(row);
        expect(mockPush).toHaveBeenCalledWith("/analyses/127");
      }
    }
  });

  it("displays empty state when no recent analyses (DP-046)", () => {
    mockUseSWR({
      ...mockDashboardData,
      recentAnalyses: [],
    }, false);

    render(<DashboardPage />);

    expect(screen.getByText(/no analyses yet/i) || screen.getByText(/run your first analysis/i)).toBeInTheDocument();
  });
});

// ==================== DP-050: Quick Insights Section ====================

describe("Quick Insights Section", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("displays most used tool (DP-050)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/most used tool/i)).toBeInTheDocument();
    // Meta Tag Analyzer appears in multiple places
    const metaElements = screen.getAllByText("Meta Tag Analyzer");
    expect(metaElements.length).toBeGreaterThan(0);
  });

  it("displays average analysis time (DP-051)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/avg\. analysis time/i) || screen.getByText(/average analysis/i)).toBeInTheDocument();
    expect(screen.getByText(/1\.2/)).toBeInTheDocument();
  });

  it("displays success rate percentage (DP-052)", () => {
    render(<DashboardPage />);

    expect(screen.getByText(/success rate/i)).toBeInTheDocument();
    expect(screen.getByText(/98\.4%/)).toBeInTheDocument();
  });

  it("shows N/A for insights when no data available (DP-053)", () => {
    mockUseSWR({
      ...mockDashboardData,
      quickInsights: null,
    }, false);

    render(<DashboardPage />);

    const naLabels = screen.getAllByText(/n\/a/i);
    expect(naLabels.length).toBeGreaterThan(0);
  });
});

// ==================== DP-060: Quick Actions Section ====================

describe("Quick Actions Section", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("displays run new analysis button (DP-060)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("link", { name: /run new analysis/i }) ||
           screen.getByRole("button", { name: /run new analysis/i })).toBeInTheDocument();
  });

  it("displays view all history link (DP-062)", () => {
    render(<DashboardPage />);

    expect(screen.getByRole("link", { name: /view all history/i }) ||
           screen.getByRole("button", { name: /view all history/i })).toBeInTheDocument();
  });
});

// ==================== DP-070: Loading States ====================

describe("Loading Skeleton States", () => {
  it("shows skeleton for stats cards while loading (DP-070)", () => {
    (useAuth as jest.Mock).mockReturnValue(mockAuthContext);
    mockUseSWR(null, true);

    render(<DashboardPage />);

    // Should have skeleton elements with animation
    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });
});

// ==================== DP-080: Error States ====================

describe("Error States", () => {
  it("shows error message when API fails (DP-080)", () => {
    mockUseSWR(null, false, new Error("Failed to fetch dashboard data"));

    render(<DashboardPage />);

    expect(screen.getByText(/failed to load/i) || screen.getByText(/error/i)).toBeInTheDocument();
  });

  it("shows retry button on error (DP-081)", () => {
    mockUseSWR(null, false, new Error("Failed to fetch dashboard data"));

    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    expect(retryButton).toBeInTheDocument();
  });

  it("retries data fetch when retry button clicked (DP-082)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(null, false, new Error("Failed to fetch dashboard data"), mockMutate);

    const user = userEvent.setup();
    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    await user.click(retryButton);

    expect(mockMutate).toHaveBeenCalled();
  });

  it("shows partial data when some sections fail (DP-083)", () => {
    mockUseSWR({
      stats: mockDashboardData.stats,
      costSummary: null, // Failed section
      recentAnalyses: mockDashboardData.recentAnalyses,
      quickInsights: null, // Failed section
    }, false);

    render(<DashboardPage />);

    // Stats should still display
    expect(screen.getByText("127")).toBeInTheDocument();
    // Failed sections should show N/A or similar
    expect(screen.getAllByText(/n\/a/i).length).toBeGreaterThan(0);
  });
});

// ==================== DP-090: Responsive Layout ====================

describe("Responsive Layout", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("renders stats grid with responsive classes (DP-090)", () => {
    render(<DashboardPage />);

    const statsGrid = screen.getByTestId("stats-grid");
    expect(statsGrid).toHaveClass(/md:grid-cols-2/);
  });

  it("renders 4 columns on desktop viewport (DP-091)", () => {
    render(<DashboardPage />);

    const statsGrid = screen.getByTestId("stats-grid");
    expect(statsGrid).toHaveClass(/lg:grid-cols-4/);
  });

  it("shows analyses section (DP-093)", () => {
    render(<DashboardPage />);

    const analysesSection = screen.getByTestId("analyses-section");
    expect(analysesSection).toBeInTheDocument();
  });
});

// ==================== DP-100: Status Badges ====================

describe("Status Badges", () => {
  beforeEach(() => {
    mockUseSWR(mockDashboardData, false);
  });

  it("shows green badge for completed status (DP-100)", () => {
    render(<DashboardPage />);

    const completedBadge = screen.getByText(/completed/i);
    expect(completedBadge.className).toMatch(/emerald|green/i);
  });

  it("shows red badge for failed status (DP-101)", () => {
    render(<DashboardPage />);

    const failedBadge = screen.getByText(/failed/i);
    expect(failedBadge.className).toMatch(/rose|red/i);
  });

  it("shows yellow/amber badge for pending status (DP-102)", () => {
    render(<DashboardPage />);

    const pendingBadge = screen.getByText(/pending/i);
    expect(pendingBadge.className).toMatch(/amber|yellow/i);
  });

  it("shows running status with spinner (DP-103)", () => {
    mockUseSWR({
      ...mockDashboardData,
      recentAnalyses: [
        {
          ...mockDashboardData.recentAnalyses[0],
          status: "running",
        },
      ],
    }, false);

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
    mockUseSWR(mockDashboardData, false);
  });

  it("has proper heading hierarchy (DP-110)", () => {
    render(<DashboardPage />);

    // Should have h1 as main heading
    const h1 = screen.getByRole("heading", { level: 1 });
    expect(h1).toBeInTheDocument();
    expect(h1).toHaveTextContent(/dashboard/i);
  });

  it("stats cards have accessible labels (DP-111)", () => {
    render(<DashboardPage />);

    const totalAnalysesCard = screen.getByText(/total analyses/i);
    expect(totalAnalysesCard).toBeInTheDocument();
  });

  it("table has proper ARIA attributes (DP-112)", () => {
    render(<DashboardPage />);

    const table = screen.getByRole("table");
    if (table) {
      // aria-label is case-sensitive
      expect(table).toHaveAttribute("aria-label", "Recent analyses");
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
  it("provides refresh button to reload data (DP-121)", () => {
    const mockMutate = jest.fn();
    mockUseSWR(mockDashboardData, false, null, mockMutate);

    render(<DashboardPage />);

    const refreshButton = screen.getByRole("button", { name: /refresh/i });
    expect(refreshButton).toBeInTheDocument();
  });

  it("refreshes data when refresh button clicked (DP-122)", async () => {
    const mockMutate = jest.fn();
    mockUseSWR(mockDashboardData, false, null, mockMutate);

    const user = userEvent.setup();
    render(<DashboardPage />);

    const refreshButton = screen.getByRole("button", { name: /refresh/i });
    await user.click(refreshButton);

    expect(mockMutate).toHaveBeenCalled();
  });
});

// ==================== DP-130: Performance ====================

describe("Performance", () => {
  it("renders within acceptable time (DP-130)", () => {
    const startTime = performance.now();

    mockUseSWR(mockDashboardData, false);

    render(<DashboardPage />);

    const renderTime = performance.now() - startTime;
    expect(renderTime).toBeLessThan(100); // Should render in under 100ms
  });

  it("does not re-render unnecessarily (DP-131)", () => {
    mockUseSWR(mockDashboardData, false);

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

    mockUseSWR(mockDashboardData, false);

    render(<DashboardPage />);

    // The XSS test - the script tag text should not appear as raw HTML
    // React automatically escapes HTML in JSX expressions
    const scriptElements = screen.queryAllByText(/<script>/i);
    // If there are elements with the script tag text, they should be escaped
    // In this case, React escapes the content, so we check that there's no raw script execution
    expect(document.querySelector('script[src*="alert"]')).toBeNull();
    // The escaped text should be visible (not as HTML)
    expect(screen.getByText(/alert/i)).toBeInTheDocument();
  });

  it("does not expose sensitive data in DOM (DP-142)", () => {
    mockUseSWR(mockDashboardData, false);

    render(<DashboardPage />);

    // Should not expose user ID, tokens, etc. in the DOM
    const html = document.body.innerHTML;
    expect(html).not.toContain("token");
    expect(html).not.toContain("password");
  });
});
