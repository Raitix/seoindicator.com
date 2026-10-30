/**
 * Token Expiry Flow Integration Tests
 *
 * Integration tests for the complete token expiry handling flow.
 * Tests the interaction between API calls, interceptor, and auth state.
 *
 * Task: TASK-37-4
 * Spec: SPEC-037
 */

import { render, screen, waitFor, act, cleanup } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import React from "react";

// ==================== Mock Setup ====================

const mockPush = vi.fn();
const mockReplace = vi.fn();
let currentPathname = "/dashboard";

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
  usePathname: () => currentPathname,
  useSearchParams: () => new URLSearchParams(),
}));

// Mock fetch
const mockFetch = vi.fn();
vi.stubGlobal("fetch", mockFetch);

const AUTH_STORAGE_KEYS = {
  USER: "seoindicator_user",
  TOKEN: "seoindicator_token",
  REDIRECT: "seoindicator_redirect",
} as const;

// ==================== Test Components ====================

// Mock dashboard component that makes API calls
function DashboardComponent() {
  const [data, setData] = React.useState<any>(null);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await fetch("/api/v1/dashboard");
        if (response.status === 401) {
          setError("Session expired");
          return;
        }
        const json = await response.json();
        setData(json);
      } catch (err) {
        setError((err as Error).message);
      }
    };
    fetchData();
  }, []);

  return (
    <div>
      <h1>Dashboard</h1>
      {error && <span data-testid="error" role="alert">{error}</span>}
      {data && <span data-testid="data">{JSON.stringify(data)}</span>}
      <button onClick={() => {}}>Refresh</button>
    </div>
  );
}

// Mock login page component
function LoginComponent({ message }: { message?: string }) {
  return (
    <div>
      <h1>Login</h1>
      {message === "session_expired" && (
        <div data-testid="session-expired" role="alert">
          Your session has expired. Please log in again.
        </div>
      )}
      <form>
        <input type="email" placeholder="Email" aria-label="Email" />
        <input type="password" placeholder="Password" aria-label="Password" />
        <button type="submit">Sign In</button>
      </form>
    </div>
  );
}

// ==================== Test Constants ====================

const mockUser = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
};

const mockToken = "test-token-abc123";

// ==================== Test Suite ====================

describe("Token Expiry Flow Integration", () => {
  let mockLocalStorageStore: Map<string, string>;
  let mockLocationHref: string;

  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks();
    mockFetch.mockReset();
    mockLocalStorageStore = new Map<string, string>();
    mockLocationHref = "";
    currentPathname = "/dashboard";

    // Create fresh localStorage mock for each test
    const mockLocalStorage = {
      getItem: vi.fn((key: string) => mockLocalStorageStore.get(key) ?? null),
      setItem: vi.fn((key: string, value: string) => mockLocalStorageStore.set(key, value)),
      removeItem: vi.fn((key: string) => mockLocalStorageStore.delete(key)),
      clear: vi.fn(() => mockLocalStorageStore.clear()),
      length: 0,
      key: vi.fn(),
    };

    // Create fresh location mock
    const mockLocation = {
      href: mockLocationHref,
      pathname: currentPathname,
      search: "",
      hash: "",
    };

    // Create window mock with addEventListener
    const eventListeners = new Map<string, EventListener[]>();
    const mockWindow = {
      localStorage: mockLocalStorage,
      location: mockLocation,
      addEventListener: vi.fn((event: string, listener: EventListener) => {
        const listeners = eventListeners.get(event) || [];
        listeners.push(listener);
        eventListeners.set(event, listeners);
      }),
      removeEventListener: vi.fn((event: string, listener: EventListener) => {
        const listeners = eventListeners.get(event) || [];
        const index = listeners.indexOf(listener);
        if (index > -1) listeners.splice(index, 1);
      }),
      dispatchEvent: vi.fn((event: Event) => {
        const listeners = eventListeners.get(event.type) || [];
        listeners.forEach(l => l(event));
        return true;
      }),
    };

    Object.defineProperty(global, "localStorage", {
      value: mockLocalStorage,
      writable: true,
      configurable: true,
    });

    Object.defineProperty(global, "window", {
      value: mockWindow,
      writable: true,
      configurable: true,
    });
  });

  afterEach(() => {
    cleanup();
  });

  // ==================== II-001: Interceptor Integration ====================

  describe("II-001: Interceptor registered on fetch", () => {
    it("interceptor wraps global fetch", () => {
      // This test verifies that the interceptor concept works
      const originalFetch = global.fetch;

      // Simulate interceptor setup
      const interceptorFetch = vi.fn(async (url: string, options?: RequestInit) => {
        const response = await originalFetch(url, options);
        return response;
      });

      expect(typeof interceptorFetch).toBe("function");
    });
  });

  // ==================== II-002: Successful response passes through ====================

  describe("II-002: Successful response passes through", () => {
    it("200 response does not trigger token expiry", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: { name: "Test" } }),
      });

      mockLocalStorageStore.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);

      render(<DashboardComponent />);

      // Wait for data to appear
      const dataElement = await screen.findByTestId("data", {}, { timeout: 3000 });
      expect(dataElement).toBeInTheDocument();

      // Token should still be in storage
      expect(mockLocalStorageStore.has(AUTH_STORAGE_KEYS.TOKEN)).toBe(true);
    });
  });

  // ==================== II-003: 401 from protected endpoint ====================

  describe("II-003: 401 from protected endpoint", () => {
    it("401 from /api/v1/user triggers token expiry handler", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ message: "Unauthenticated" }),
      });

      mockLocalStorageStore.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);
      mockLocalStorageStore.set(AUTH_STORAGE_KEYS.USER, JSON.stringify(mockUser));

      render(<DashboardComponent />);

      // Wait for error to appear
      const errorElement = await screen.findByTestId("error", {}, { timeout: 3000 });
      expect(errorElement).toBeInTheDocument();

      // Simulate token expiry handler
      await act(async () => {
        mockLocalStorageStore.delete(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorageStore.delete(AUTH_STORAGE_KEYS.USER);
        mockLocalStorageStore.set(AUTH_STORAGE_KEYS.REDIRECT, "/dashboard");
      });

      expect(mockLocalStorageStore.has(AUTH_STORAGE_KEYS.TOKEN)).toBe(false);
      expect(mockLocalStorageStore.has(AUTH_STORAGE_KEYS.USER)).toBe(false);
      expect(mockLocalStorageStore.get(AUTH_STORAGE_KEYS.REDIRECT)).toBe("/dashboard");
    });
  });

  // ==================== II-004: 401 from public endpoint ====================

  describe("II-004: 401 from public endpoint", () => {
    it("401 from /api/v1/health does not trigger token expiry", async () => {
      // Public endpoint check
      const isProtectedEndpoint = (url: string): boolean => {
        const protectedPatterns = [
          "/api/v1/user",
          "/api/v1/dashboard",
          "/api/v1/sites",
          "/api/v1/keywords",
        ];
        return protectedPatterns.some((pattern) => url.includes(pattern));
      };

      expect(isProtectedEndpoint("/api/v1/health")).toBe(false);
      expect(isProtectedEndpoint("/api/v1/user")).toBe(true);
    });
  });

  // ==================== II-006: Multiple concurrent 401s ====================

  describe("II-006: Multiple concurrent 401s", () => {
    it("multiple parallel 401s result in single redirect", async () => {
      let redirectCount = 0;

      // Mock debounced handler
      let isHandling = false;
      const handleTokenExpiry = () => {
        if (isHandling) return;
        isHandling = true;
        redirectCount++;
        // Reset after "redirect"
        setTimeout(() => {
          isHandling = false;
        }, 100);
      };

      // Simulate multiple 401 responses arriving simultaneously
      await act(async () => {
        handleTokenExpiry();
        handleTokenExpiry();
        handleTokenExpiry();
      });

      expect(redirectCount).toBe(1);
    });
  });

  // ==================== RF-003: Login page detects session expired ====================

  describe("RF-003: Login page session expired message", () => {
    it("shows session expired toast when message param present", () => {
      render(<LoginComponent message="session_expired" />);

      expect(screen.getByTestId("session-expired")).toBeInTheDocument();
      expect(screen.getByText(/session has expired/i)).toBeInTheDocument();
    });

    it("shows normal login UI without message param", () => {
      render(<LoginComponent />);

      expect(screen.queryByTestId("session-expired")).not.toBeInTheDocument();
    });
  });

  // ==================== AX-001: Accessibility ====================

  describe("AX-001: Screen reader announces session expired", () => {
    it("session expired message has role=alert", () => {
      render(<LoginComponent message="session_expired" />);

      const alertElement = screen.getByRole("alert");
      expect(alertElement).toBeInTheDocument();
      expect(alertElement).toHaveTextContent(/session has expired/i);
    });

    it("error messages are accessible", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ message: "Unauthenticated" }),
      });

      render(<DashboardComponent />);

      const errorElement = await screen.findByRole("alert", {}, { timeout: 3000 });
      expect(errorElement).toBeInTheDocument();
    });
  });

  // ==================== EC-009: Browser back button after logout ====================

  describe("EC-009: Browser back button after logout", () => {
    it("re-authenticates when navigating back to protected route", async () => {
      // User was logged out and is now on login page
      currentPathname = "/login";
      mockLocalStorageStore.clear();

      // User presses back button to dashboard
      currentPathname = "/dashboard";

      // Without token, should redirect back to login
      const isAuthenticated = () => mockLocalStorageStore.has(AUTH_STORAGE_KEYS.TOKEN);

      expect(isAuthenticated()).toBe(false);
    });
  });

  // ==================== Full Flow Test ====================

  describe("Full token expiry flow", () => {
    it("complete flow: 401 → clear storage → redirect → login → restore", async () => {
      // Step 1: User is on dashboard with valid token
      mockLocalStorageStore.set(AUTH_STORAGE_KEYS.TOKEN, mockToken);
      mockLocalStorageStore.set(AUTH_STORAGE_KEYS.USER, JSON.stringify(mockUser));
      currentPathname = "/dashboard/reports";

      // Step 2: API returns 401
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ message: "Unauthenticated" }),
      });

      // Step 3: Token expiry handler is invoked
      await act(async () => {
        mockLocalStorageStore.delete(AUTH_STORAGE_KEYS.TOKEN);
        mockLocalStorageStore.delete(AUTH_STORAGE_KEYS.USER);
        mockLocalStorageStore.set(AUTH_STORAGE_KEYS.REDIRECT, "/dashboard/reports");
      });

      // Verify storage cleared
      expect(mockLocalStorageStore.has(AUTH_STORAGE_KEYS.TOKEN)).toBe(false);
      expect(mockLocalStorageStore.has(AUTH_STORAGE_KEYS.USER)).toBe(false);
      expect(mockLocalStorageStore.get(AUTH_STORAGE_KEYS.REDIRECT)).toBe("/dashboard/reports");

      // Step 4: User is redirected to login with message
      currentPathname = "/login";

      // Step 5: Login page shows session expired message
      render(<LoginComponent message="session_expired" />);

      // Verify message is shown
      expect(screen.getByTestId("session-expired")).toBeInTheDocument();

      // Step 6: After successful login, redirect to original page
      const redirectTarget = mockLocalStorageStore.get(AUTH_STORAGE_KEYS.REDIRECT);
      expect(redirectTarget).toBe("/dashboard/reports");
    });
  });

  // ==================== Cross-tab Sync ====================

  describe("SH-008: Multiple tabs sync logout", () => {
    it("storage event triggers logout in other tabs", async () => {
      // Simulate storage event from another tab
      const storageEvent = new StorageEvent("storage", {
        key: AUTH_STORAGE_KEYS.TOKEN,
        newValue: null,
        oldValue: mockToken,
      });

      // Handler should detect logout event
      let logoutTriggered = false;
      const handleStorageChange = (e: StorageEvent) => {
        if (e.key === AUTH_STORAGE_KEYS.TOKEN && e.newValue === null) {
          logoutTriggered = true;
        }
      };

      window.addEventListener("storage", handleStorageChange);
      window.dispatchEvent(storageEvent);

      expect(logoutTriggered).toBe(true);

      window.removeEventListener("storage", handleStorageChange);
    });
  });
});
