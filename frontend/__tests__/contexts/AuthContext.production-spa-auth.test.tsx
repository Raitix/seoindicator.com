import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { AuthProvider, useAuth } from "@/lib/contexts/AuthContext";
import type { User } from "@/lib/types/auth";

const mockPush = vi.fn();
const mockReplace = vi.fn();
const mockPathname = vi.fn(() => "/sites/42/local");
const mockFetchUser = vi.fn();
const mockLogin = vi.fn();
const mockRegister = vi.fn();
const mockLogout = vi.fn();
const mockInitiateGoogleOAuth = vi.fn();
const mockHandleGoogleCallback = vi.fn();
const mockForgotPassword = vi.fn();
const mockResetPassword = vi.fn();
const mockResendVerificationEmail = vi.fn();
const mockBroadcastAuthChange = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push: mockPush, replace: mockReplace }),
  usePathname: () => mockPathname(),
}));

vi.mock("@/lib/api/auth", () => ({
  fetchUser: (...args: unknown[]) => mockFetchUser(...args),
  login: (...args: unknown[]) => mockLogin(...args),
  register: (...args: unknown[]) => mockRegister(...args),
  logout: (...args: unknown[]) => mockLogout(...args),
  initiateGoogleOAuth: (...args: unknown[]) => mockInitiateGoogleOAuth(...args),
  handleGoogleCallback: (...args: unknown[]) => mockHandleGoogleCallback(...args),
  forgotPassword: (...args: unknown[]) => mockForgotPassword(...args),
  resetPassword: (...args: unknown[]) => mockResetPassword(...args),
  resendVerificationEmail: (...args: unknown[]) => mockResendVerificationEmail(...args),
  storeUserData: (user: User) => localStorage.setItem("seoindicator_user", JSON.stringify(user)),
  getStoredUserData: () => {
    const stored = localStorage.getItem("seoindicator_user");
    return stored ? JSON.parse(stored) : null;
  },
  getStoredAuthToken: () => localStorage.getItem("seoindicator_token"),
  storeRedirectUrl: (url: string) => localStorage.setItem("seoindicator_redirect", url),
  getStoredRedirectUrl: () => localStorage.getItem("seoindicator_redirect"),
  clearStoredRedirectUrl: () => localStorage.removeItem("seoindicator_redirect"),
  clearAuthStorage: () => {
    localStorage.removeItem("seoindicator_user");
    localStorage.removeItem("seoindicator_token");
  },
  broadcastAuthChange: (...args: unknown[]) => mockBroadcastAuthChange(...args),
}));

const mockUser: User = {
  id: 1,
  name: "Raitis",
  email: "raitis@example.com",
  email_verified: true,
  role: "user",
};

function seedStoredAuth(user: User = mockUser, token = "spa-fallback-token") {
  localStorage.setItem("seoindicator_user", JSON.stringify(user));
  localStorage.setItem("seoindicator_token", token);
}

function AuthStateProbe() {
  const { user, isAuthenticated, isLoading, refreshUser, logout } = useAuth();

  return (
    <div>
      <span data-testid="loading">{String(isLoading)}</span>
      <span data-testid="authenticated">{String(isAuthenticated)}</span>
      <span data-testid="user-name">{user?.name ?? "null"}</span>
      <button onClick={() => refreshUser()}>Refresh</button>
      <button onClick={() => logout()}>Logout</button>
    </div>
  );
}

describe("AuthContext production SPA auth persistence", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    mockPathname.mockReturnValue("/sites/42/local");
    mockFetchUser.mockResolvedValue(null);
    mockLogout.mockResolvedValue(undefined);
  });

  it("clears the stored session when the API cannot confirm it", async () => {
    // The auth contract verifies the stored token with the API before it
    // trusts the session (SPEC-037). The routing bug that made the API
    // unreachable is fixed, so a token the API will not confirm is stale
    // and must not keep the visitor signed in.
    seedStoredAuth();

    render(
      <AuthProvider>
        <AuthStateProbe />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    expect(screen.getByTestId("user-name")).toHaveTextContent("null");

    // The stale session is dropped rather than trusted.
    expect(localStorage.getItem("seoindicator_user")).toBeNull();
    expect(localStorage.getItem("seoindicator_token")).toBeNull();

    // The protected route redirects to the login page.
    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledWith("/login");
    });
  });

  it("refreshUser clears the session when the API still returns no user", async () => {
    const user = userEvent.setup();
    seedStoredAuth();
    mockFetchUser.mockResolvedValueOnce(mockUser).mockResolvedValueOnce(null);

    render(
      <AuthProvider>
        <AuthStateProbe />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
      expect(screen.getByTestId("user-name")).toHaveTextContent("Raitis");
    });

    await user.click(screen.getByText("Refresh"));

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    // The refresh could not confirm the session, so it is dropped.
    expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    expect(screen.getByTestId("user-name")).toHaveTextContent("null");
    expect(localStorage.getItem("seoindicator_user")).toBeNull();
    expect(localStorage.getItem("seoindicator_token")).toBeNull();
  });

  it("still redirects protected routes to login when no stored fallback auth state exists", async () => {
    render(
      <AuthProvider>
        <AuthStateProbe />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    expect(screen.getByTestId("authenticated")).toHaveTextContent("false");

    await waitFor(() => {
      expect(mockReplace).toHaveBeenCalledWith("/login");
    });
  });
});
