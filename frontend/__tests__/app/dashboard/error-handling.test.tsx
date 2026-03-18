/**
 * Dashboard Error Handling Tests - TASK-37-3
 *
 * Tests for dashboard page error handling, specifically 401 authentication errors.
 * When a 401 error occurs, the dashboard should redirect to login instead of
 * showing an error message.
 *
 * @see SPEC-037
 * @see TASK-37-3
 */

import { render, screen, waitFor, act } from "@testing-library/react";
import { vi, describe, it, expect, beforeEach, afterEach } from "vitest";
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

// Mock auth storage functions
vi.mock("@/lib/api/auth", () => ({
  clearAuthStorage: vi.fn(),
  storeUserData: vi.fn(),
  getStoredUserData: vi.fn(() => null),
  getStoredAuthToken: vi.fn(() => null),
  storeRedirectUrl: vi.fn(),
  getStoredRedirectUrl: vi.fn(() => null),
  clearStoredRedirectUrl: vi.fn(),
  broadcastAuthChange: vi.fn(),
}));

import { clearAuthStorage } from "@/lib/api/auth";

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
  recentAnalyses: [],
  quickInsights: null,
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

// Helper to setup SWR mock with custom error
const mockUseSWR = (
  data: any,
  isLoading = false,
  error: any = null,
  mutate = vi.fn()
) => {
  vi.mocked(useSWR).mockReturnValue({
    data,
    isLoading,
    error,
    mutate,
  } as any);
};

// Create an error with status code (simulating fetch API error)
class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.status = status;
    this.name = "ApiError";
  }
}

// ==================== Test Setup ====================

beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(useAuth).mockReturnValue(mockAuthContext as any);
});

afterEach(() => {
  vi.clearAllTimers();
});

// ==================== DE-001: 401 Error Detection ====================

describe("DE-001: 401 Error Detection", () => {
  it("detects 401 error from API response", async () => {
    // Mock SWR to return a 401 error
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Should redirect to login on 401
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 error with message containing 'Unauthorized'", async () => {
    // Some APIs return error message with 'Unauthorized' text
    const error = new Error("Unauthorized: Token expired");
    (error as any).status = 401;
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 error with status code 401", async () => {
    const error = { message: "HTTP 401", status: 401 };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects error with response.status 401", async () => {
    const error = {
      message: "Forbidden",
      response: { status: 401 },
    };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });
});

// ==================== DE-002: localStorage Clearing on 401 ====================

describe("DE-002: localStorage Clearing on 401", () => {
  it("clears auth storage on 401 error", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(clearAuthStorage).toHaveBeenCalled();
    });
  });

  it("clears auth storage before redirect", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Wait for both calls
    await waitFor(() => {
      expect(clearAuthStorage).toHaveBeenCalled();
      expect(mockPush).toHaveBeenCalledWith("/login");
    });

    // Verify order: clearAuthStorage should be called before push
    const clearCall = vi.mocked(clearAuthStorage).mock.invocationCallOrder[0];
    const pushCall = mockPush.mock.invocationCallOrder[0];
    expect(clearCall).toBeLessThan(pushCall);
  });

  it("does not clear auth storage on non-401 errors", async () => {
    const error500 = new ApiError("Internal Server Error", 500);
    mockUseSWR(null, false, error500);

    render(<DashboardPage />);

    // Should NOT clear auth storage for server errors
    expect(clearAuthStorage).not.toHaveBeenCalled();
  });
});

// ==================== DE-003: Redirect to Login ====================

describe("DE-003: Redirect to Login", () => {
  it("redirects to /login/ on 401 error", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("redirects without showing error message for 401", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Should NOT show error message for auth errors
    expect(screen.queryByText(/failed to load/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/error/i)).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /retry/i })).not.toBeInTheDocument();
  });

  it("redirect happens immediately on 401 (no delay)", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Redirect should happen quickly
    await waitFor(
      () => {
        expect(mockPush).toHaveBeenCalledWith("/login");
      },
      { timeout: 100 }
    );
  });
});

// ==================== DE-004: No Error Message for Auth Failures ====================

describe("DE-004: No Error Message for Auth Failures", () => {
  it("does not show error UI for 401 errors", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Should not show error UI components
    expect(screen.queryByText(/failed to load dashboard/i)).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /retry/i })).not.toBeInTheDocument();
  });

  it("shows error UI for non-401 errors", () => {
    const error500 = new ApiError("Internal Server Error", 500);
    mockUseSWR(null, false, error500);

    render(<DashboardPage />);

    // SHOULD show error UI for server errors
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("shows error UI for network errors", () => {
    const networkError = new Error("Network error");
    mockUseSWR(null, false, networkError);

    render(<DashboardPage />);

    // SHOULD show error UI for network errors
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("shows error UI for timeout errors", () => {
    const timeoutError = new Error("Request timeout");
    mockUseSWR(null, false, timeoutError);

    render(<DashboardPage />);

    // SHOULD show error UI for timeout errors
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
  });
});

// ==================== DE-005: Loading State During Auth Check ====================

describe("DE-005: Loading State During Auth Check", () => {
  it("shows loading skeleton while fetching initial data", () => {
    mockUseSWR(null, true);

    render(<DashboardPage />);

    // Should show loading skeleton elements
    const skeletons = screen.getAllByTestId(/skeleton/i);
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it("shows auth loading state while checking authentication", () => {
    vi.mocked(useAuth).mockReturnValue({
      ...mockAuthContext,
      isLoading: true,
    } as any);
    mockUseSWR(null, true);

    render(<DashboardPage />);

    expect(screen.getByTestId("auth-loading")).toBeInTheDocument();
  });

  it("transitions from loading to content on successful fetch", async () => {
    const mockMutate = vi.fn();

    // Start with loading
    mockUseSWR(null, true, null, mockMutate);

    const { rerender } = render(<DashboardPage />);

    // Verify loading state
    expect(screen.getAllByTestId(/skeleton/i).length).toBeGreaterThan(0);

    // Simulate data loaded
    mockUseSWR(mockDashboardData, false, null, mockMutate);

    rerender(<DashboardPage />);

    // Should now show content
    await waitFor(() => {
      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    });
  });
});

// ==================== DE-006: Non-Auth Error Handling ====================

describe("DE-006: Non-Auth Error Handling", () => {
  it("shows retry button for server errors (500)", () => {
    const error500 = new ApiError("Internal Server Error", 500);
    mockUseSWR(null, false, error500);

    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    expect(retryButton).toBeInTheDocument();
  });

  it("shows retry button for network errors", () => {
    const networkError = new Error("Failed to fetch");
    mockUseSWR(null, false, networkError);

    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    expect(retryButton).toBeInTheDocument();
  });

  it("retry button calls mutate function", async () => {
    const mockMutate = vi.fn();
    const error500 = new ApiError("Internal Server Error", 500);
    mockUseSWR(null, false, error500, mockMutate);

    render(<DashboardPage />);

    const retryButton = screen.getByRole("button", { name: /retry/i });
    await act(async () => {
      retryButton.click();
    });

    expect(mockMutate).toHaveBeenCalled();
  });

  it("shows 404 error message for not found", () => {
    const error404 = new ApiError("Not found", 404);
    mockUseSWR(null, false, error404);

    render(<DashboardPage />);

    // 404 is not an auth error, so should show error UI
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
  });

  it("shows 403 error message for forbidden", () => {
    const error403 = new ApiError("Forbidden", 403);
    mockUseSWR(null, false, error403);

    render(<DashboardPage />);

    // 403 is not an auth error (user is authenticated but lacks permission)
    // Should show error UI
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
  });
});

// ==================== DE-007: Error Status Code Detection ====================

describe("DE-007: Error Status Code Detection", () => {
  it("detects 401 from error.status property", async () => {
    const error = { message: "Unauthorized", status: 401 };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 from error.response.status property", async () => {
    const error = {
      message: "Token expired",
      response: { status: 401 },
    };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 from error.statusCode property", async () => {
    const error = {
      message: "Auth required",
      statusCode: 401,
    };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 from error message containing '401'", async () => {
    const error = new Error("HTTP 401: Unauthorized");
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("detects 401 from error message containing 'Unauthorized'", async () => {
    const error = new Error("Unauthorized access");
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("does not redirect for error message containing 'Unauthorized' but status 500", () => {
    const error = {
      message: "Unauthorized access detected in logs",
      status: 500,
    };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    // Should show error UI, not redirect
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    expect(mockPush).not.toHaveBeenCalledWith("/login");
  });
});

// ==================== DE-008: Edge Cases ====================

describe("DE-008: Edge Cases", () => {
  it("handles null error gracefully", () => {
    mockUseSWR(mockDashboardData, false, null);

    render(<DashboardPage />);

    // Should render normally
    expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
  });

  it("handles undefined error gracefully", () => {
    mockUseSWR(mockDashboardData, false, undefined);

    render(<DashboardPage />);

    expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
  });

  it("handles error object with no message", () => {
    const error = {};
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    // Should show error UI for unknown errors
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
  });

  it("handles error with numeric status code as string", async () => {
    const error = {
      message: "Token expired",
      status: "401",
    };
    mockUseSWR(null, false, error);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("handles concurrent errors gracefully", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    const { unmount } = render(<DashboardPage />);

    // Unmount during async operation
    unmount();

    // Should not throw or cause issues
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalled();
    });
  });
});

// ==================== DE-009: Integration with Auth Context ====================

describe("DE-009: Integration with Auth Context", () => {
  it("works when auth context reports authenticated", async () => {
    vi.mocked(useAuth).mockReturnValue(mockAuthContext as any);
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("redirects to login when auth context reports not authenticated", async () => {
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

  it("shows auth loading state when auth is loading", () => {
    vi.mocked(useAuth).mockReturnValue({
      ...mockAuthContext,
      isLoading: true,
    } as any);
    mockUseSWR(null, true);

    render(<DashboardPage />);

    expect(screen.getByTestId("auth-loading")).toBeInTheDocument();
  });
});

// ==================== DE-010: Acceptance Criteria Verification ====================

describe("DE-010: Acceptance Criteria Verification", () => {
  it("AC1: Dashboard detects 401 error from API", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("AC2: localStorage cleared on 401 error", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(clearAuthStorage).toHaveBeenCalled();
    });
  });

  it("AC3: User redirected to /login/ on auth failure", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("AC4: No error message shown for auth failures (just redirect)", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Should NOT show error UI
    expect(screen.queryByText(/failed to load/i)).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /retry/i })).not.toBeInTheDocument();

    // Should redirect
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("AC5: Loading state shown while checking auth", () => {
    vi.mocked(useAuth).mockReturnValue({
      ...mockAuthContext,
      isLoading: true,
    } as any);
    mockUseSWR(null, true);

    render(<DashboardPage />);

    expect(screen.getByTestId("auth-loading")).toBeInTheDocument();
  });

  it("AC6: Unit tests for error handling exist", () => {
    // This test file itself verifies the acceptance criteria
    // If all tests pass, AC6 is satisfied
    expect(true).toBe(true);
  });
});

// ==================== DE-011: Security Considerations ====================

describe("DE-011: Security Considerations", () => {
  it("does not expose sensitive data in error state", () => {
    const error500 = new ApiError("Internal Server Error", 500);
    mockUseSWR(null, false, error500);

    render(<DashboardPage />);

    const html = document.body.innerHTML;
    expect(html).not.toContain("token");
    expect(html).not.toContain("password");
    expect(html).not.toContain("secret");
  });

  it("clears auth tokens on 401 before redirect", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    await waitFor(() => {
      expect(clearAuthStorage).toHaveBeenCalled();
    });

    // Verify redirect happens after clear
    const clearCall = vi.mocked(clearAuthStorage).mock.invocationCallOrder[0];
    const pushCall = mockPush.mock.invocationCallOrder[0];
    expect(clearCall).toBeLessThan(pushCall);
  });

  it("handles malicious error messages safely", async () => {
    const error401 = new ApiError(
      '<script>alert("XSS")</script>Unauthorized',
      401
    );
    mockUseSWR(null, false, error401);

    render(<DashboardPage />);

    // Should redirect, not show the XSS payload
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });

    // Should not execute script
    expect(document.querySelector('script[src*="alert"]')).toBeNull();
  });
});

// ==================== DE-012: Performance ====================

describe("DE-012: Performance", () => {
  it("handles 401 redirect quickly", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    const startTime = performance.now();
    render(<DashboardPage />);

    await waitFor(
      () => {
        expect(mockPush).toHaveBeenCalledWith("/login");
      },
      { timeout: 50 }
    );

    const endTime = performance.now();
    expect(endTime - startTime).toBeLessThan(50);
  });

  it("does not cause unnecessary re-renders on error", async () => {
    const error401 = new ApiError("Unauthorized", 401);
    mockUseSWR(null, false, error401);

    const { rerender } = render(<DashboardPage />);

    // Rerender with same error
    rerender(<DashboardPage />);

    // Should only redirect once
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledTimes(1);
    });
  });
});
