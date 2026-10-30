import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { AuthProvider, useAuth } from "@/lib/contexts/AuthContext";
import type { User } from "@/lib/types/auth";

const mockPush = vi.fn();
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
  useRouter: () => ({ push: mockPush }),
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

  it("keeps the stored user authenticated on protected routes when bearer fallback state exists and auth/user cannot hydrate the session", async () => {
    seedStoredAuth();

    render(
      <AuthProvider>
        <AuthStateProbe />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
    expect(screen.getByTestId("user-name")).toHaveTextContent("Raitis");
    expect(localStorage.getItem("seoindicator_user")).not.toBeNull();
    expect(localStorage.getItem("seoindicator_token")).toBe("spa-fallback-token");
    expect(mockPush).not.toHaveBeenCalledWith("/login");
  });

  it("refreshUser keeps the current user when bearer fallback state exists but auth/user still returns null", async () => {
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

    expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
    expect(screen.getByTestId("user-name")).toHaveTextContent("Raitis");
    expect(localStorage.getItem("seoindicator_user")).not.toBeNull();
    expect(localStorage.getItem("seoindicator_token")).toBe("spa-fallback-token");
    expect(mockPush).not.toHaveBeenCalledWith("/login");
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
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });
});
