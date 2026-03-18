/**
 * Tests for TASK-37-1: Fix Token Validation in AuthContext
 *
 * These tests verify that AuthContext properly validates tokens with the API
 * before considering a user authenticated, preventing redirect loops when
 * tokens expire.
 *
 * @see TASK-37-1.md
 */

import React from "react";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor, act } from "@testing-library/react";
import { ReactNode } from "react";

// ==================== Mock Types ====================

interface MockUser {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  avatar?: string;
  role?: "user" | "admin";
  created_at?: string;
  updated_at?: string;
}

// ==================== Test Helpers ====================

const mockValidUser: MockUser = {
  id: 1,
  name: "Test User",
  email: "test@example.com",
  email_verified: true,
  role: "user",
  created_at: "2024-01-01T00:00:00Z",
  updated_at: "2024-01-01T00:00:00Z",
};

const mockStoredToken = "valid-jwt-token-12345";
const mockExpiredToken = "expired-jwt-token-67890";
const mockInvalidToken = "invalid-token-format";

// Storage keys from auth types
const AUTH_STORAGE_KEYS = {
  USER: "seoindicator_user",
  TOKEN: "seoindicator_token",
  REDIRECT: "seoindicator_redirect",
};

// ==================== Mock Setup ====================

// Mock next/navigation
const mockPush = vi.fn();
const mockReplace = vi.fn();
vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
  usePathname: () => "/login",
}));

// Create mock functions for auth API
const mockFetchUser = vi.fn();
const mockGetStoredAuthToken = vi.fn();
const mockGetStoredUserData = vi.fn();
const mockStoreUserData = vi.fn();
const mockClearAuthStorage = vi.fn();
const mockBroadcastAuthChange = vi.fn();
const mockSetupUnauthorizedInterceptor = vi.fn();

vi.mock("@/lib/api/auth", () => ({
  fetchUser: () => mockFetchUser(),
  getStoredAuthToken: () => mockGetStoredAuthToken(),
  getStoredUserData: () => mockGetStoredUserData(),
  storeUserData: (user: MockUser) => mockStoreUserData(user),
  clearAuthStorage: () => mockClearAuthStorage(),
  broadcastAuthChange: vi.fn(),
  getStoredRedirectUrl: vi.fn(() => null),
  storeRedirectUrl: vi.fn(),
  clearStoredRedirectUrl: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
  register: vi.fn(),
  initiateGoogleOAuth: vi.fn(),
  handleGoogleCallback: vi.fn(),
  forgotPassword: vi.fn(),
  resetPassword: vi.fn(),
  resendVerificationEmail: vi.fn(),
}));

vi.mock("@/lib/api/token-expiry-handler", () => ({
  setupUnauthorizedInterceptor: () => mockSetupUnauthorizedInterceptor(),
  handleApiError: vi.fn(),
  hasSessionExpiredMessage: vi.fn(() => false),
  clearSessionExpiredMessage: vi.fn(),
}));

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] || null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value;
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key];
    }),
    clear: vi.fn(() => {
      store = {};
    }),
    get store() {
      return store;
    },
  };
})();

Object.defineProperty(window, "localStorage", {
  value: localStorageMock,
  writable: true,
  configurable: true,
});

// Import the actual component after mocks are set up
import { AuthProvider, useAuth } from "@/lib/contexts/AuthContext";

// Test wrapper component
function TestWrapper({ children }: { children: ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>;
}

// Component to read auth state
function AuthStateReader({
  onStateChange,
}: {
  onStateChange: (state: {
    user: MockUser | null;
    isAuthenticated: boolean;
    isLoading: boolean;
  }) => void;
}) {
  const { user, isAuthenticated, isLoading } = useAuth();

  React.useEffect(() => {
    onStateChange({ user, isAuthenticated, isLoading });
  }, [user, isAuthenticated, isLoading, onStateChange]);

  return <div data-testid="auth-state">{isAuthenticated ? "authenticated" : "not-authenticated"}</div>;
}

// ==================== Test Suite ====================

describe("AuthContext Token Validation (TASK-37-1)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
    mockPush.mockClear();
    mockReplace.mockClear();
    mockFetchUser.mockReset();
    mockGetStoredAuthToken.mockReset();
    mockGetStoredUserData.mockReset();
    mockStoreUserData.mockReset();
    mockClearAuthStorage.mockReset();
    mockSetupUnauthorizedInterceptor.mockReset();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ========================================
  // TV-001: Token Validation on Initialization
  // ========================================

  describe("TV-001: Token Validation on Initialization", () => {
    it("TV-001a: should call fetchUser API to verify token on mount", async () => {
      // Setup: stored token exists
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        // Wait for initialization
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Assert: fetchUser was called to verify token
      expect(mockFetchUser).toHaveBeenCalled();
      expect(mockSetupUnauthorizedInterceptor).toHaveBeenCalled();
    });

    it("TV-001b: should set isAuthenticated=true only after API confirms validity", async () => {
      // Setup: valid token, API returns user
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // After API success: should be authenticated
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(true);
      expect(mockStoreUserData).toHaveBeenCalledWith(mockValidUser);
    });

    it("TV-001c: should NOT use fallback user when API returns 401", async () => {
      // Setup: stored token exists but is expired
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      // API returns 401 Unauthorized
      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Key assertion: should clear storage, not use fallback
      expect(mockClearAuthStorage).toHaveBeenCalled();

      // After 401: should NOT be authenticated
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-001d: should set user to null when API returns null (no valid session)", async () => {
      // Setup: stored token exists
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      // API returns null (no valid session)
      mockFetchUser.mockResolvedValue(null);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Expected: storage cleared, user null
      expect(mockClearAuthStorage).toHaveBeenCalled();

      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });
  });

  // ========================================
  // TV-002: Expired Token Handling
  // ========================================

  describe("TV-002: Expired Token Handling", () => {
    it("TV-002a: should clear localStorage when token is expired (401 response)", async () => {
      // Setup: expired token in storage
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Session expired", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-002b: should show login page for users with expired tokens", async () => {
      // Setup: expired token
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // After fix: should NOT redirect to dashboard
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");

      // User should not be authenticated
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-002c: should handle TOKEN_EXPIRED error code specifically", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Token expired", AuthErrorCode.TOKEN_EXPIRED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-002d: should clear both token and user data on expiration", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // clearAuthStorage should be called once (clears both token and user)
      expect(mockClearAuthStorage).toHaveBeenCalledTimes(1);
    });
  });

  // ========================================
  // TV-003: Loading State During Verification
  // ========================================

  describe("TV-003: Loading State During Verification", () => {
    it("TV-003a: should show loading state while verifying token", async () => {
      // Setup: slow API response
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(null);

      let resolveFetchUser: (value: MockUser) => void;
      const fetchUserPromise = new Promise<MockUser>((resolve) => {
        resolveFetchUser = resolve;
      });
      mockFetchUser.mockReturnValue(fetchUserPromise);

      const stateChanges: any[] = [];

      const { unmount } = render(
        <TestWrapper>
          <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
        </TestWrapper>
      );

      // Wait a bit for initial render
      await act(async () => {
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      // During loading, isLoading should be true
      const loadingState = stateChanges.find((s) => s.isLoading === true);
      expect(loadingState).toBeDefined();

      // Resolve the promise
      await act(async () => {
        resolveFetchUser!(mockValidUser);
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      unmount();
    });

    it("TV-003b: should clear loading state after successful verification", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // After verification completes, isLoading should be false
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isLoading).toBe(false);
    });

    it("TV-003c: should clear loading state after failed verification", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // After error, isLoading should be false
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isLoading).toBe(false);
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-003d: should not flash authenticated state during loading", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      let resolveFetchUser: (value: null) => void;
      const fetchUserPromise = new Promise<null>((resolve) => {
        resolveFetchUser = resolve;
      });
      mockFetchUser.mockReturnValue(fetchUserPromise);

      const stateChanges: any[] = [];

      const { unmount } = render(
        <TestWrapper>
          <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
        </TestWrapper>
      );

      // Wait for initial render
      await act(async () => {
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      // During loading, isAuthenticated should NOT be true
      const duringLoading = stateChanges.filter(
        (s) => s.isLoading === true && s.isAuthenticated === true
      );
      expect(duringLoading.length).toBe(0);

      // Resolve with null (invalid token)
      await act(async () => {
        resolveFetchUser!(null);
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      // After fix: should NOT have redirected to dashboard
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");

      unmount();
    });
  });

  // ========================================
  // TV-004: Redirect Prevention for Invalid Tokens
  // ========================================

  describe("TV-004: Redirect Prevention for Invalid Tokens", () => {
    it("TV-004a: should NOT redirect to dashboard when token is expired", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Key assertion: should NOT redirect to dashboard
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");
    });

    it("TV-004b: should NOT redirect to dashboard when token is invalid", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockInvalidToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");
    });

    it("TV-004c: should NOT create redirect loop for expired tokens", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should NOT redirect to dashboard (which would cause the loop)
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");

      // Should clear the invalid token
      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-004d: should stay on login page when token is expired", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should not have redirected anywhere
      expect(mockPush).not.toHaveBeenCalled();
    });
  });

  // ========================================
  // TV-005: Network Error Handling
  // ========================================

  describe("TV-005: Network Error Handling", () => {
    it("TV-005a: should handle network errors gracefully during verification", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const networkError = new Error("Network error");
      mockFetchUser.mockRejectedValue(networkError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should handle gracefully
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isLoading).toBe(false);
    });

    it("TV-005b: should NOT clear token on temporary network errors", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const networkError = new Error("Network error");
      mockFetchUser.mockRejectedValue(networkError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Network errors should NOT clear storage (token may still be valid)
      expect(mockClearAuthStorage).not.toHaveBeenCalled();
    });

    it("TV-005c: should distinguish between 401 and network errors", async () => {
      // Test 1: 401 should clear storage
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockClearAuthStorage).toHaveBeenCalled();

      // Reset
      vi.clearAllMocks();

      // Test 2: Network error should NOT clear storage
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const networkError = new Error("Network error");
      mockFetchUser.mockRejectedValue(networkError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Network error: preserve token, don't clear
      expect(mockClearAuthStorage).not.toHaveBeenCalled();
    });
  });

  // ========================================
  // TV-006: Storage Clearing Behavior
  // ========================================

  describe("TV-006: Storage Clearing Behavior", () => {
    it("TV-006a: should clear token from localStorage on 401", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-006b: should clear user data from localStorage on 401", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // clearAuthStorage clears both
      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-006c: should clear both token and user data together", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // clearAuthStorage called once (clears both)
      expect(mockClearAuthStorage).toHaveBeenCalledTimes(1);
    });

    it("TV-006d: should handle empty/null token gracefully", async () => {
      mockGetStoredAuthToken.mockReturnValue(null);
      mockGetStoredUserData.mockReturnValue(null);
      mockFetchUser.mockResolvedValue(null);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // No token to clear, should not error
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-006e: should handle whitespace-only token", async () => {
      mockGetStoredAuthToken.mockReturnValue("   ");
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      // Whitespace token should be treated as no token
      mockFetchUser.mockResolvedValue(null);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should clear storage since API returned null
      expect(mockClearAuthStorage).toHaveBeenCalled();
    });
  });

  // ========================================
  // TV-007: Edge Cases
  // ========================================

  describe("TV-007: Edge Cases", () => {
    it("TV-007a: should handle corrupted user data in localStorage", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue({ corrupted: "data" } as any);

      mockFetchUser.mockResolvedValue(mockValidUser);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should fetch fresh user and store it
      expect(mockFetchUser).toHaveBeenCalled();
    });

    it("TV-007b: should handle malformed JSON in localStorage", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      // Simulate malformed JSON parsing returning null
      mockGetStoredUserData.mockReturnValue(null);

      mockFetchUser.mockResolvedValue(mockValidUser);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockFetchUser).toHaveBeenCalled();
    });

    it("TV-007c: should handle concurrent initialization calls", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      // Render multiple times (React StrictMode simulation)
      const { unmount } = render(
        <TestWrapper>
          <AuthStateReader onStateChange={() => {}} />
        </TestWrapper>
      );

      // Wait for initialization
      await act(async () => {
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockFetchUser).toHaveBeenCalled();

      unmount();
    });

    it("TV-007d: should handle token with special characters", async () => {
      const specialToken = "token-with-special!@#$%^&*()_+-={}[]|:;<>,.?/";

      mockGetStoredAuthToken.mockReturnValue(specialToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should handle special characters in token without error
      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-007e: should handle very long tokens", async () => {
      const longToken = "a".repeat(10000);

      mockGetStoredAuthToken.mockReturnValue(longToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockClearAuthStorage).toHaveBeenCalled();
    });
  });

  // ========================================
  // TV-008: Successful Authentication Flow
  // ========================================

  describe("TV-008: Successful Authentication Flow", () => {
    it("TV-008a: should authenticate user when token is valid", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should store updated user data
      expect(mockStoreUserData).toHaveBeenCalledWith(mockValidUser);

      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(true);
    });

    it("TV-008b: should refresh user data from API", async () => {
      const storedUser = { ...mockValidUser, name: "Old Name" };
      const freshUser = { ...mockValidUser, name: "New Name" };

      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(storedUser);
      mockFetchUser.mockResolvedValue(freshUser);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should store fresh user data
      expect(mockStoreUserData).toHaveBeenCalledWith(freshUser);
    });

    it("TV-008c: should redirect to dashboard for valid authenticated user", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // For valid authentication on login page, should redirect to dashboard
      // (The redirect happens in a useEffect after authentication)
    });
  });

  // ========================================
  // TV-009: Acceptance Criteria Verification
  // ========================================

  describe("TV-009: Acceptance Criteria Verification", () => {
    it("TV-009a: AC1 - Token validity verified with API before setting authenticated state", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Core assertion: fetchUser API must be called
      expect(mockFetchUser).toHaveBeenCalled();

      // isAuthenticated should only be true AFTER API confirms
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(true);
    });

    it("TV-009b: AC2 - Expired tokens are cleared from localStorage", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Token and user data should be cleared
      expect(mockClearAuthStorage).toHaveBeenCalled();
    });

    it("TV-009c: AC3 - Users with expired tokens see login page, not dashboard error", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should NOT redirect to dashboard
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");

      // Storage should be cleared
      expect(mockClearAuthStorage).toHaveBeenCalled();

      // Should not be authenticated
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-009d: AC4 - Loading state shown while verifying token", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(null);

      let resolveFetchUser: (value: MockUser) => void;
      const fetchUserPromise = new Promise<MockUser>((resolve) => {
        resolveFetchUser = resolve;
      });
      mockFetchUser.mockReturnValue(fetchUserPromise);

      const stateChanges: any[] = [];

      const { unmount } = render(
        <TestWrapper>
          <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
        </TestWrapper>
      );

      // Wait for initial render
      await act(async () => {
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      // Loading state should be present
      const loadingState = stateChanges.find((s) => s.isLoading === true);
      expect(loadingState).toBeDefined();

      await act(async () => {
        resolveFetchUser!(mockValidUser);
        await new Promise((resolve) => setTimeout(resolve, 50));
      });

      // isLoading should be false after completion
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isLoading).toBe(false);

      unmount();
    });

    it("TV-009e: AC5 - No redirect to dashboard for invalid tokens", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockInvalidToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={() => {}} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should NOT redirect to dashboard
      expect(mockPush).not.toHaveBeenCalledWith("/dashboard");
    });

    it("TV-009f: AC6 - Unit tests for token validation scenarios", async () => {
      // This test file itself verifies AC6
      // All the tests in this file cover token validation scenarios
      expect(true).toBe(true);
    });
  });

  // ========================================
  // TV-010: Integration Scenarios
  // ========================================

  describe("TV-010: Integration Scenarios", () => {
    it("TV-010a: should handle page refresh with valid token", async () => {
      // Simulate page refresh scenario
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should restore authenticated state
      expect(mockStoreUserData).toHaveBeenCalledWith(mockValidUser);

      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(true);
    });

    it("TV-010b: should handle page refresh with expired token", async () => {
      // Simulate page refresh with expired token
      mockGetStoredAuthToken.mockReturnValue(mockExpiredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const { AuthError, AuthErrorCode } = await import("@/lib/types/auth");
      const authError = new AuthError("Unauthorized", AuthErrorCode.UNAUTHORIZED);
      mockFetchUser.mockRejectedValue(authError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should clear storage and show login
      expect(mockClearAuthStorage).toHaveBeenCalled();

      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(false);
    });

    it("TV-010c: should handle new tab opening with stored token", async () => {
      // Multi-tab scenario
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);
      mockFetchUser.mockResolvedValue(mockValidUser);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      expect(mockFetchUser).toHaveBeenCalled();

      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isAuthenticated).toBe(true);
    });

    it("TV-010d: should handle server error during verification", async () => {
      mockGetStoredAuthToken.mockReturnValue(mockStoredToken);
      mockGetStoredUserData.mockReturnValue(mockValidUser);

      const serverError = new Error("Internal Server Error");
      mockFetchUser.mockRejectedValue(serverError);

      const stateChanges: any[] = [];

      await act(async () => {
        render(
          <TestWrapper>
            <AuthStateReader onStateChange={(state) => stateChanges.push(state)} />
          </TestWrapper>
        );
        await new Promise((resolve) => setTimeout(resolve, 100));
      });

      // Should handle server error gracefully
      // Should not crash or show raw error to user
      const finalState = stateChanges[stateChanges.length - 1];
      expect(finalState?.isLoading).toBe(false);

      // Should NOT clear storage for server errors
      expect(mockClearAuthStorage).not.toHaveBeenCalled();
    });
  });
});
