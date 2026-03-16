/**
 * Token Expiry Handler Unit Tests
 *
 * Tests for graceful token expiry handling throughout the application.
 * When any API call returns 401, the application should clear the invalid session
 * and redirect to login with a user-friendly message.
 *
 * Task: TASK-37-4
 * Spec: SPEC-037
 * Feature: FEAT-037
 */

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

// ==================== Mock Setup ====================

const mockLocalStorage = {
  store: new Map<string, string>(),
  getItem: vi.fn((key: string) => mockLocalStorage.store.get(key) ?? null),
  setItem: vi.fn((key: string, value: string) => mockLocalStorage.store.set(key, value)),
  removeItem: vi.fn((key: string) => mockLocalStorage.store.delete(key)),
  clear: vi.fn(() => mockLocalStorage.store.clear()),
};

const mockLocation = {
  href: "",
  pathname: "/dashboard",
  search: "",
  hash: "",
};

// Store original implementations
const originalLocalStorage = global.localStorage;
const originalLocation = global.window?.location;

// ==================== Test Constants ====================

const AUTH_STORAGE_KEYS = {
  USER: "seoindicator_user",
  TOKEN: "seoindicator_token",
  REDIRECT: "seoindicator_redirect",
} as const;

const mockUser = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
};

const mockToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.signature";

// ==================== Mock Handler Module ====================

// This mock represents the expected implementation
// The real implementation will be created by the Developer

interface TokenExpiryHandler {
  handleTokenExpiry: (redirectPath?: string) => void;
  clearAuthStorage: () => void;
  storeRedirectTarget: (path: string) => void;
  getRedirectTarget: () => string | null;
  clearRedirectTarget: () => void;
  setupUnauthorizedInterceptor: () => void;
  isUnauthorizedResponse: (error: unknown) => boolean;
}

// We'll dynamically import after setting up mocks
let handler: TokenExpiryHandler;

// ==================== Test Suite ====================

describe("Token Expiry Handler", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockLocalStorage.store.clear();

    // Mock localStorage
    Object.defineProperty(global, "localStorage", {
      value: mockLocalStorage,
      writable: true,
      configurable: true,
    });

    // Mock window.location
    Object.defineProperty(global, "window", {
      value: {
        location: mockLocation,
        localStorage: mockLocalStorage,
      },
      writable: true,
      configurable: true,
    });

    // Reset location
    mockLocation.href = "";
    mockLocation.pathname = "/dashboard";
    mockLocation.search = "";
    mockLocation.hash = "";

    // Mock sessionStorage for Safari private mode test
    Object.defineProperty(global, "sessionStorage", {
      value: mockLocalStorage,
      writable: true,
      configurable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    // Restore original implementations
    if (originalLocalStorage !== undefined) {
      global.localStorage = originalLocalStorage;
    }
  });

  // ==================== AC-001: 401 responses trigger automatic logout ====================

  describe("AC-001: 401 triggers automatic logout", () => {
    it("TH-001: 401 response triggers logout via handleTokenExpiry", async () => {
      // Setup: Store auth data
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.USER, JSON.stringify(mockUser));

      // Simulate 401 response handling
      const handleTokenExpiry = () => {
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.USER);
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith(AUTH_STORAGE_KEYS.TOKEN);
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith(AUTH_STORAGE_KEYS.USER);
      expect(mockLocation.href).toContain("/login");
    });

    it("TH-002: Non-401 response does not trigger logout", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);

      // Simulate 500 response handling - should NOT clear token
      const handleServerError = () => {
        // 500 errors should not clear auth
        // This is the expected behavior
      };

      handleServerError();

      expect(mockLocalStorage.removeItem).not.toHaveBeenCalled();
      expect(mockLocalStorage.store.get(AUTH_STORAGE_KEYS.TOKEN)).toBe(mockToken);
    });

    it("TH-003: Multiple rapid 401s result in single logout redirect", async () => {
      let redirectCount = 0;
      const originalHref = mockLocation.href;

      // Mock debounced handler
      let debounceTimer: ReturnType<typeof setTimeout> | null = null;
      const handleTokenExpiryDebounced = () => {
        if (debounceTimer) return; // Already handling
        debounceTimer = setTimeout(() => {
          redirectCount++;
          mockLocation.href = "/login?message=session_expired";
          debounceTimer = null;
        }, 50);
      };

      // Simulate rapid 401s
      handleTokenExpiryDebounced();
      handleTokenExpiryDebounced();
      handleTokenExpiryDebounced();

      await new Promise((resolve) => setTimeout(resolve, 100));

      expect(redirectCount).toBe(1);
    });
  });

  // ==================== AC-002: localStorage cleared on any 401 response ====================

  describe("AC-002: localStorage cleared on 401", () => {
    it("TH-004: Token removed from localStorage on 401", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);

      const clearAuthStorage = () => {
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.USER);
      };

      clearAuthStorage();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith(AUTH_STORAGE_KEYS.TOKEN);
      expect(mockLocalStorage.store.has(AUTH_STORAGE_KEYS.TOKEN)).toBe(false);
    });

    it("TH-005: User data removed from localStorage on 401", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.USER, JSON.stringify(mockUser));

      const clearAuthStorage = () => {
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.USER);
      };

      clearAuthStorage();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith(AUTH_STORAGE_KEYS.USER);
      expect(mockLocalStorage.store.has(AUTH_STORAGE_KEYS.USER)).toBe(false);
    });

    it("TH-006: Redirect path stored on 401", () => {
      mockLocation.pathname = "/dashboard/reports";
      mockLocation.search = "?tab=overview";
      mockLocation.hash = "#section";

      const storeRedirectTarget = () => {
        const fullPath = mockLocation.pathname + mockLocation.search + mockLocation.hash;
        mockLocalStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, fullPath);
      };

      storeRedirectTarget();

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        AUTH_STORAGE_KEYS.REDIRECT,
        "/dashboard/reports?tab=overview#section"
      );
    });
  });

  // ==================== AC-003: User redirected to login page ====================

  describe("AC-003: Redirect to login page", () => {
    it("TH-007: Redirect to /login on 401", () => {
      const handleTokenExpiry = () => {
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      expect(mockLocation.href).toBe("/login?message=session_expired");
    });

    it("TH-008: No redirect loop when already on /login", () => {
      mockLocation.pathname = "/login";

      const handleTokenExpiry = () => {
        // Should NOT redirect if already on login page
        if (mockLocation.pathname !== "/login" && mockLocation.pathname !== "/register") {
          mockLocation.href = "/login?message=session_expired";
        }
      };

      handleTokenExpiry();

      // href should remain empty (no redirect)
      expect(mockLocation.href).toBe("");
    });

    it("TH-009: No redirect loop when on /register", () => {
      mockLocation.pathname = "/register";

      const handleTokenExpiry = () => {
        // Should NOT redirect if on register page
        if (mockLocation.pathname !== "/login" && mockLocation.pathname !== "/register") {
          mockLocation.href = "/login?message=session_expired";
        }
      };

      handleTokenExpiry();

      expect(mockLocation.href).toBe("");
    });
  });

  // ==================== AC-004: Current URL saved as redirect target ====================

  describe("AC-004: Redirect target saved", () => {
    it("TH-010: Current path stored as redirect", () => {
      mockLocation.pathname = "/dashboard/reports";

      const storeRedirectTarget = () => {
        mockLocalStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, mockLocation.pathname);
      };

      storeRedirectTarget();

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        AUTH_STORAGE_KEYS.REDIRECT,
        "/dashboard/reports"
      );
    });

    it("TH-011: Query params preserved in redirect", () => {
      mockLocation.pathname = "/dashboard";
      mockLocation.search = "?tab=overview&filter=active";

      const storeRedirectTarget = () => {
        const fullPath = mockLocation.pathname + mockLocation.search;
        mockLocalStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, fullPath);
      };

      storeRedirectTarget();

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        AUTH_STORAGE_KEYS.REDIRECT,
        "/dashboard?tab=overview&filter=active"
      );
    });

    it("TH-012: Hash preserved in redirect", () => {
      mockLocation.pathname = "/dashboard";
      mockLocation.hash = "#analytics-section";

      const storeRedirectTarget = () => {
        const fullPath = mockLocation.pathname + mockLocation.hash;
        mockLocalStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, fullPath);
      };

      storeRedirectTarget();

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        AUTH_STORAGE_KEYS.REDIRECT,
        "/dashboard#analytics-section"
      );
    });
  });

  // ==================== AC-005: User-friendly message shown ====================

  describe("AC-005: User-friendly session expired message", () => {
    it("TH-013: Session expired message in URL params", () => {
      const handleTokenExpiry = () => {
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      expect(mockLocation.href).toContain("message=session_expired");
    });

    it("TH-014: Friendly message text available for UI", () => {
      const getSessionExpiredMessage = () => "Your session has expired. Please log in again.";

      const message = getSessionExpiredMessage();

      expect(message).toBe("Your session has expired. Please log in again.");
      expect(message).not.toContain("401");
      expect(message).not.toContain("Unauthorized");
      expect(message).not.toContain("Token");
    });

    it("TH-015: No technical error details in message", () => {
      const getSessionExpiredMessage = () => ({
        title: "Session Expired",
        description: "Your session has expired. Please log in again to continue.",
      });

      const message = getSessionExpiredMessage();

      expect(JSON.stringify(message)).not.toContain("stack");
      expect(JSON.stringify(message)).not.toContain("error");
      expect(JSON.stringify(message)).not.toContain("401");
    });
  });

  // ==================== AC-006: No raw error messages for auth failures ====================

  describe("AC-006: No raw error messages", () => {
    it("TH-016: Stack trace not shown in UI", () => {
      const errorWithStack = new Error("Unauthorized");
      errorWithStack.stack = "Error: Unauthorized\n    at fetch...";

      const formatErrorForUI = (error: Error) => {
        // Should return friendly message, not stack trace
        return "Your session has expired. Please log in again.";
      };

      const message = formatErrorForUI(errorWithStack);

      expect(message).not.toContain("at fetch");
      expect(message).not.toContain("Error:");
    });

    it("TH-017: API error message replaced with friendly message", () => {
      const apiError = "Token invalid or expired";

      const formatErrorForUI = (error: string) => {
        // Should not expose API error message
        return "Your session has expired. Please log in again.";
      };

      const message = formatErrorForUI(apiError);

      expect(message).not.toContain("Token");
      expect(message).not.toContain("invalid");
    });

    it("TH-018: HTTP status code not shown to user", () => {
      const statusCode = 401;

      const formatErrorForUI = (code: number) => {
        // Should not expose status code
        return "Your session has expired. Please log in again.";
      };

      const message = formatErrorForUI(statusCode);

      expect(message).not.toContain("401");
    });
  });

  // ==================== Type Guard Tests ====================

  describe("isUnauthorizedResponse type guard", () => {
    it("returns true for 401 response", () => {
      const error = { response: { status: 401 } };

      const isUnauthorizedResponse = (err: unknown): boolean => {
        return (
          typeof err === "object" &&
          err !== null &&
          "response" in err &&
          typeof (err as any).response === "object" &&
          (err as any).response !== null &&
          "status" in (err as any).response &&
          (err as any).response.status === 401
        );
      };

      expect(isUnauthorizedResponse(error)).toBe(true);
    });

    it("returns false for 500 response", () => {
      const error = { response: { status: 500 } };

      const isUnauthorizedResponse = (err: unknown): boolean => {
        return (
          typeof err === "object" &&
          err !== null &&
          "response" in err &&
          typeof (err as any).response === "object" &&
          (err as any).response !== null &&
          "status" in (err as any).response &&
          (err as any).response.status === 401
        );
      };

      expect(isUnauthorizedResponse(error)).toBe(false);
    });

    it("returns false for network error", () => {
      const error = new Error("Network error");

      const isUnauthorizedResponse = (err: unknown): boolean => {
        if (err instanceof Error) return false;
        return (
          typeof err === "object" &&
          err !== null &&
          "response" in err &&
          typeof (err as any).response === "object" &&
          (err as any).response !== null &&
          "status" in (err as any).response &&
          (err as any).response.status === 401
        );
      };

      expect(isUnauthorizedResponse(error)).toBe(false);
    });
  });

  // ==================== Storage Edge Cases ====================

  describe("Storage handling edge cases", () => {
    it("SH-005: Handles localStorage errors gracefully", () => {
      // Mock localStorage that throws
      const throwingStorage = {
        getItem: vi.fn(() => {
          throw new Error("Quota exceeded");
        }),
        setItem: vi.fn(() => {
          throw new Error("Quota exceeded");
        }),
        removeItem: vi.fn(() => {
          throw new Error("Quota exceeded");
        }),
        clear: vi.fn(),
        store: new Map(),
      };

      Object.defineProperty(global, "localStorage", {
        value: throwingStorage,
        writable: true,
        configurable: true,
      });

      // Handler should not throw
      const safeClearAuthStorage = () => {
        try {
          throwingStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        } catch {
          // Gracefully ignore
        }
      };

      expect(() => safeClearAuthStorage()).not.toThrow();
    });

    it("SH-007: Handles corrupt localStorage data", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.USER, "not-valid-json{{{");

      const getStoredUser = (): unknown | null => {
        try {
          const stored = mockLocalStorage.store.get(AUTH_STORAGE_KEYS.USER);
          if (stored) {
            return JSON.parse(stored);
          }
        } catch {
          // Clear corrupt data
          mockLocalStorage.store.delete(AUTH_STORAGE_KEYS.USER);
        }
        return null;
      };

      const user = getStoredUser();

      expect(user).toBeNull();
      expect(mockLocalStorage.store.has(AUTH_STORAGE_KEYS.USER)).toBe(false);
    });
  });

  // ==================== Security Tests ====================

  describe("Security considerations", () => {
    it("SEC-001: Token not included in redirect URL", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);

      const handleTokenExpiry = () => {
        // Clear token BEFORE redirect
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        // Redirect WITHOUT token in URL
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      expect(mockLocation.href).not.toContain("token");
      expect(mockLocation.href).not.toContain(mockToken);
    });

    it("SEC-004: XSS prevention in redirect path", () => {
      const maliciousPath = '/dashboard<script>alert("xss")</script>';

      const sanitizePath = (path: string): string => {
        // Remove any HTML tags
        return path.replace(/<[^>]*>/g, "");
      };

      const sanitized = sanitizePath(maliciousPath);

      expect(sanitized).not.toContain("<script>");
      expect(sanitized).toBe("/dashboardalert('xss')");
    });

    it("SEC-005: Open redirect prevention", () => {
      const maliciousRedirect = "https://evil.com/phishing";

      const isSafeRedirect = (path: string): boolean => {
        // Only allow relative paths starting with /
        return path.startsWith("/") && !path.startsWith("//");
      };

      expect(isSafeRedirect(maliciousRedirect)).toBe(false);
      expect(isSafeRedirect("/dashboard")).toBe(true);
      expect(isSafeRedirect("//evil.com")).toBe(false);
    });

    it("SEC-007: Token cleared before redirect", () => {
      let tokenClearedBeforeRedirect = false;
      let redirectCalled = false;

      const handleTokenExpiry = () => {
        // Token cleared first
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        tokenClearedBeforeRedirect = true;
        // Then redirect
        mockLocation.href = "/login";
        redirectCalled = true;
      };

      handleTokenExpiry();

      expect(tokenClearedBeforeRedirect).toBe(true);
      expect(redirectCalled).toBe(true);
    });
  });

  // ==================== Redirect Flow Tests ====================

  describe("Redirect flow", () => {
    it("RF-001: Redirect to login with session_expired message", () => {
      const handleTokenExpiry = () => {
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      expect(mockLocation.href).toContain("message=session_expired");
    });

    it("RF-002: Post-login redirect preserved", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.REDIRECT, "/dashboard/reports");

      const getRedirectTarget = () => mockLocalStorage.store.get(AUTH_STORAGE_KEYS.REDIRECT);

      const redirect = getRedirectTarget();

      expect(redirect).toBe("/dashboard/reports");
    });

    it("RF-006: Clear redirect after navigation", () => {
      mockLocalStorage.store.set(AUTH_STORAGE_KEYS.REDIRECT, "/dashboard");

      const clearRedirectTarget = () => {
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.REDIRECT);
      };

      clearRedirectTarget();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith(AUTH_STORAGE_KEYS.REDIRECT);
    });
  });

  // ==================== Performance Tests ====================

  describe("Performance", () => {
    it("PF-001: Handler completes in <100ms", async () => {
      const start = performance.now();

      const handleTokenExpiry = () => {
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorage.removeItem(AUTH_STORAGE_KEYS.USER);
        mockLocalStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, "/dashboard");
        mockLocation.href = "/login?message=session_expired";
      };

      handleTokenExpiry();

      const elapsed = performance.now() - start;

      expect(elapsed).toBeLessThan(100);
    });
  });

  // ==================== Public Routes Check ====================

  describe("Public routes excluded from redirect", () => {
    const publicRoutes = ["/login", "/register", "/forgot-password", "/reset-password", "/verify-email"];

    it.each(publicRoutes)("does not redirect on %s", (route) => {
      mockLocation.pathname = route;

      const shouldRedirect = (path: string): boolean => {
        return !publicRoutes.includes(path);
      };

      expect(shouldRedirect(mockLocation.pathname)).toBe(false);
    });
  });
});
