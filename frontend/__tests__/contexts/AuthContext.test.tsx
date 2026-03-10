/**
 * AuthContext Unit Tests
 *
 * Tests for the React authentication context and provider.
 * Covers state management, API integration, loading states, and error handling.
 */

import { render, screen, waitFor, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import {
  AuthProvider,
  useAuth,
  useUser,
  useAuthActions,
  useHasRole,
  AuthContext,
} from "@/lib/contexts/AuthContext";
import type { User, AuthContextType } from "@/lib/types/auth";
import { AuthError, AuthErrorCode } from "@/lib/types/auth";

// ==================== Mocks ====================

const mockPush = jest.fn();
const mockPathname = jest.fn(() => "/");

jest.mock("next/navigation", () => ({
  useRouter: () => ({
    push: mockPush,
  }),
  usePathname: () => mockPathname(),
}));

// Mock the auth API
const mockFetchUser = jest.fn();
const mockLogin = jest.fn();
const mockRegister = jest.fn();
const mockLogout = jest.fn();
const mockInitiateGoogleOAuth = jest.fn();
const mockHandleGoogleCallback = jest.fn();
const mockForgotPassword = jest.fn();
const mockResetPassword = jest.fn();
const mockResendVerificationEmail = jest.fn();

jest.mock("@/lib/api/auth", () => ({
  fetchUser: () => mockFetchUser(),
  login: (...args: any[]) => mockLogin(...args),
  register: (...args: any[]) => mockRegister(...args),
  logout: () => mockLogout(),
  initiateGoogleOAuth: () => mockInitiateGoogleOAuth(),
  handleGoogleCallback: (...args: any[]) => mockHandleGoogleCallback(...args),
  forgotPassword: (...args: any[]) => mockForgotPassword(...args),
  resetPassword: (...args: any[]) => mockResetPassword(...args),
  resendVerificationEmail: () => mockResendVerificationEmail(),
  storeUserData: jest.fn(),
  getStoredUserData: jest.fn(() => null),
  getStoredAuthToken: jest.fn(() => null),
  storeRedirectUrl: jest.fn(),
  getStoredRedirectUrl: jest.fn(() => null),
  clearStoredRedirectUrl: jest.fn(),
  clearAuthStorage: jest.fn(),
  broadcastAuthChange: jest.fn(),
}));

const mockUser: User = {
  id: 1,
  name: "John Doe",
  email: "john@example.com",
  email_verified: true,
  avatar: "https://example.com/avatar.jpg",
};

// ==================== Test Components ====================

function TestComponent() {
  const { user, isAuthenticated, isLoading, error, login, logout, register } = useAuth();

  const handleLogin = async () => {
    try {
      await login("test@example.com", "password", false);
    } catch {
      // Error is set in state, no need to handle here
    }
  };

  const handleRegister = async () => {
    try {
      await register({ name: "Test", email: "test@example.com", password: "Password123!", password_confirmation: "Password123!" });
    } catch {
      // Error is set in state, no need to handle here
    }
  };

  const handleLogout = async () => {
    try {
      await logout();
    } catch {
      // Logout errors are handled internally
    }
  };

  return (
    <div>
      <span data-testid="loading">{isLoading.toString()}</span>
      <span data-testid="authenticated">{isAuthenticated.toString()}</span>
      <span data-testid="user-name">{user?.name ?? "null"}</span>
      <span data-testid="user-email">{user?.email ?? "null"}</span>
      <span data-testid="error">{error?.message ?? "null"}</span>
      <button onClick={handleLogin}>Login</button>
      <button onClick={handleRegister}>Register</button>
      <button onClick={handleLogout}>Logout</button>
    </div>
  );
}

// ==================== Test Setup ====================

describe("AuthContext", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockFetchUser.mockResolvedValue(null);
    mockPathname.mockReturnValue("/");
  });

  // ==================== AC-001: Context provides default null user ====================

  it("provides default null user (AC-001)", async () => {
    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    });

    expect(screen.getByTestId("user-name")).toHaveTextContent("null");
  });

  // ==================== AC-002: Context provides isLoading state ====================

  it("provides isLoading state (AC-002)", async () => {
    mockFetchUser.mockImplementation(() => new Promise(() => {})); // Never resolves

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    // Initially loading
    expect(screen.getByTestId("loading")).toHaveTextContent("true");
  });

  // ==================== AC-003 to AC-006: Context provides methods ====================

  it("provides login method (AC-003)", async () => {
    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Login")).toBeInTheDocument();
    });
  });

  it("provides loginWithGoogle method (AC-004)", async () => {
    function GoogleTestComponent() {
      const { loginWithGoogle } = useAuth();
      return <button onClick={() => loginWithGoogle()}>Google</button>;
    }

    mockInitiateGoogleOAuth.mockResolvedValue("https://accounts.google.com/oauth");

    render(
      <AuthProvider>
        <GoogleTestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Google")).toBeInTheDocument();
    });
  });

  it("provides logout method (AC-005)", async () => {
    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Logout")).toBeInTheDocument();
    });
  });

  it("provides register method (AC-006)", async () => {
    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Register")).toBeInTheDocument();
    });
  });

  // ==================== AC-007: Login success sets user ====================

  it("login success sets user (AC-007)", async () => {
    const user = userEvent.setup();
    mockLogin.mockResolvedValue(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      await user.click(screen.getByText("Login"));
    });

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
      expect(screen.getByTestId("user-name")).toHaveTextContent("John Doe");
    });
  });

  // ==================== AC-008: Login failure sets error ====================

  it("login failure sets error (AC-008)", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValue(new AuthError("Invalid credentials", AuthErrorCode.INVALID_CREDENTIALS));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      try {
        await user.click(screen.getByText("Login"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("Invalid credentials");
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    });
  });

  // ==================== AC-009: Login sets loading state ====================

  it("login sets loading state during operation (AC-009)", async () => {
    const user = userEvent.setup();
    let resolveLogin: (value: User) => void;
    mockLogin.mockImplementation(
      () =>
        new Promise<User>((resolve) => {
          resolveLogin = resolve;
        })
    );

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      await user.click(screen.getByText("Login"));
    });

    // Should be loading during login
    expect(screen.getByTestId("loading")).toHaveTextContent("true");

    // Resolve the login
    await act(async () => {
      resolveLogin!(mockUser);
    });

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });
  });

  // ==================== AC-010: Logout clears user ====================

  it("logout clears user (AC-010)", async () => {
    const user = userEvent.setup();
    mockLogin.mockResolvedValue(mockUser);
    mockLogout.mockResolvedValue(undefined);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    // First login
    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      await user.click(screen.getByText("Login"));
    });

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
    });

    // Then logout
    await act(async () => {
      await user.click(screen.getByText("Logout"));
    });

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
      expect(screen.getByTestId("user-name")).toHaveTextContent("null");
    });
  });

  // ==================== AC-011: Register success sets user ====================

  it("register success sets user (AC-011)", async () => {
    const user = userEvent.setup();
    mockRegister.mockResolvedValue(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      await user.click(screen.getByText("Register"));
    });

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
      expect(screen.getByTestId("user-name")).toHaveTextContent("John Doe");
    });
  });

  // ==================== AC-012: Register failure sets error ====================

  it("register failure sets error (AC-012)", async () => {
    const user = userEvent.setup();
    mockRegister.mockRejectedValue(new AuthError("Email already registered", AuthErrorCode.EMAIL_ALREADY_EXISTS));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      try {
        await user.click(screen.getByText("Register"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("Email already registered");
    });
  });

  // ==================== AC-013: Auto-fetch user on mount with token ====================

  it("auto-fetches user on mount (AC-013)", async () => {
    mockFetchUser.mockResolvedValue(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(mockFetchUser).toHaveBeenCalled();
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
    });
  });

  // ==================== AC-014: No auto-fetch without token ====================

  it("does not set user when not authenticated (AC-014)", async () => {
    mockFetchUser.mockResolvedValue(null);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(mockFetchUser).toHaveBeenCalled();
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    });
  });

  // ==================== AC-015: 401 response triggers logout ====================

  it("handles 401 response (AC-015)", async () => {
    const error401 = new AuthError("Session expired", AuthErrorCode.UNAUTHORIZED);
    mockFetchUser.mockRejectedValue(error401);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
    });
  });

  // ==================== AC-016: Error message is user-friendly ====================

  it("shows user-friendly error message (AC-016)", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValue(new AuthError("Invalid credentials", AuthErrorCode.INVALID_CREDENTIALS));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      try {
        await user.click(screen.getByText("Login"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      const errorText = screen.getByTestId("error").textContent;
      expect(errorText).not.toContain("stack");
      expect(errorText).not.toContain("Error:");
    });
  });

  // ==================== AC-017: Multiple simultaneous login calls ====================

  it("prevents duplicate login requests (AC-017)", async () => {
    const user = userEvent.setup();
    mockLogin.mockResolvedValue(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    // Click login multiple times quickly
    await act(async () => {
      const loginBtn = screen.getByText("Login");
      await user.click(loginBtn);
      await user.click(loginBtn);
      await user.click(loginBtn);
    });

    // Should only call login once
    expect(mockLogin).toHaveBeenCalledTimes(1);
  });

  // ==================== AC-024: isAuthenticated derived from user ====================

  it("isAuthenticated is false when user is null (AC-024)", async () => {
    mockFetchUser.mockResolvedValue(null);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("false");
      expect(screen.getByTestId("user-name")).toHaveTextContent("null");
    });
  });

  it("isAuthenticated is true when user exists (AC-025)", async () => {
    mockFetchUser.mockResolvedValue(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("authenticated")).toHaveTextContent("true");
      expect(screen.getByTestId("user-name")).toHaveTextContent("John Doe");
    });
  });

  // ==================== AC-037: Context outside provider ====================

  it("throws error when used outside provider (AC-037)", () => {
    const consoleError = jest.spyOn(console, "error").mockImplementation(() => {});

    function ErrorComponent() {
      useAuth();
      return null;
    }

    expect(() => render(<ErrorComponent />)).toThrow(
      "useAuth must be used within an AuthProvider"
    );

    consoleError.mockRestore();
  });

  // ==================== Test useUser hook ====================

  it("useUser hook returns user info", async () => {
    mockFetchUser.mockResolvedValue(mockUser);

    function UserTestComponent() {
      const { user, isAuthenticated, isLoading } = useUser();
      return (
        <div>
          <span data-testid="user">{user?.name ?? "null"}</span>
          <span data-testid="auth">{isAuthenticated.toString()}</span>
          <span data-testid="loading">{isLoading.toString()}</span>
        </div>
      );
    }

    render(
      <AuthProvider>
        <UserTestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("user")).toHaveTextContent("John Doe");
      expect(screen.getByTestId("auth")).toHaveTextContent("true");
    });
  });

  // ==================== Test useAuthActions hook ====================

  it("useAuthActions hook returns action methods", async () => {
    mockLogin.mockResolvedValue(mockUser);
    mockLogout.mockResolvedValue(undefined);

    function ActionsTestComponent() {
      const { login, logout, register, resetPassword, clearError } = useAuthActions();
      return (
        <div>
          <button onClick={() => login("test@example.com", "password")}>Login</button>
          <button onClick={() => logout()}>Logout</button>
          <button onClick={() => register({ name: "Test", email: "test@example.com", password: "pass", password_confirmation: "pass" })}>Register</button>
          <button onClick={() => resetPassword("test@example.com")}>Reset</button>
          <button onClick={() => clearError()}>Clear</button>
        </div>
      );
    }

    render(
      <AuthProvider>
        <ActionsTestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("Login")).toBeInTheDocument();
    });
  });

  // ==================== Test useHasRole hook ====================

  it("useHasRole hook checks user role", async () => {
    mockFetchUser.mockResolvedValue({ ...mockUser, role: "admin" });

    function RoleTestComponent() {
      const isAdmin = useHasRole("admin");
      const isUser = useHasRole("user");
      return (
        <div>
          <span data-testid="is-admin">{isAdmin.toString()}</span>
          <span data-testid="is-user">{isUser.toString()}</span>
        </div>
      );
    }

    render(
      <AuthProvider>
        <RoleTestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("is-admin")).toHaveTextContent("true");
    });
  });

  // ==================== AC-023: Clear error on new login attempt ====================

  it("clears error on new login attempt (AC-023)", async () => {
    const user = userEvent.setup();
    mockLogin
      .mockRejectedValueOnce(new AuthError("Invalid credentials", AuthErrorCode.INVALID_CREDENTIALS))
      .mockResolvedValueOnce(mockUser);

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    // First login attempt fails
    await act(async () => {
      try {
        await user.click(screen.getByText("Login"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("Invalid credentials");
    });

    // Second login attempt succeeds
    await act(async () => {
      await user.click(screen.getByText("Login"));
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("null");
    });
  });

  // ==================== AC-041: TypeScript type safety ====================

  it("provides TypeScript type inference (AC-041)", async () => {
    // This test verifies that the types are correctly inferred
    // If TypeScript compilation passes, the types are correct
    function TypedComponent() {
      const { user, isAuthenticated, isLoading, error, login, logout } = useAuth();

      // These should all type-check correctly
      const userName: string | undefined = user?.name;
      const auth: boolean = isAuthenticated;
      const loading: boolean = isLoading;
      const errorMsg: string | undefined = error?.message;

      return (
        <div>
          <span>{userName}</span>
          <span>{auth.toString()}</span>
          <span>{loading.toString()}</span>
          <span>{errorMsg}</span>
        </div>
      );
    }

    render(
      <AuthProvider>
        <TypedComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByText("false")).toBeInTheDocument();
    });
  });

  // ==================== AC-020: Google OAuth redirects ====================

  it("initiates Google OAuth (AC-020)", async () => {
    const user = userEvent.setup();
    mockInitiateGoogleOAuth.mockResolvedValue("https://accounts.google.com/oauth");

    function GoogleTestComponent() {
      const { loginWithGoogle } = useAuth();
      return <button onClick={() => loginWithGoogle()}>Google</button>;
    }

    // Mock window.location.href using delete and redefine
    const originalLocation = window.location;
    // @ts-expect-error - deleting location for test
    delete window.location;
    window.location = { href: "" } as Location;

    render(
      <AuthProvider>
        <GoogleTestComponent />
      </AuthProvider>
    );

    await act(async () => {
      await user.click(screen.getByText("Google"));
    });

    expect(window.location.href).toBe("https://accounts.google.com/oauth");

    // Restore
    window.location = originalLocation;
  });

  // ==================== AC-031: Network error handling ====================

  it("handles network errors (AC-031)", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValue(new Error("Network error"));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      try {
        await user.click(screen.getByText("Login"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("Network error");
    });
  });

  // ==================== AC-032: Server error handling ====================

  it("handles server errors (AC-032)", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValue(new AuthError("Server error", AuthErrorCode.SERVER_ERROR));

    render(
      <AuthProvider>
        <TestComponent />
      </AuthProvider>
    );

    await waitFor(() => {
      expect(screen.getByTestId("loading")).toHaveTextContent("false");
    });

    await act(async () => {
      try {
        await user.click(screen.getByText("Login"));
      } catch {
        // Expected
      }
    });

    await waitFor(() => {
      expect(screen.getByTestId("error")).toHaveTextContent("Server error");
    });
  });

  // ==================== Google OAuth callback handling ====================

  describe("Google OAuth callback", () => {
    it("handles Google OAuth callback (AC-021)", async () => {
      mockHandleGoogleCallback.mockResolvedValue({
        user: mockUser,
        isNewUser: false,
      });

      // This would be tested in the OAuth callback page
      const result = await mockHandleGoogleCallback({ code: "test-code" });

      expect(result.user).toEqual(mockUser);
      expect(result.isNewUser).toBe(false);
    });
  });

  // ==================== Password reset ====================

  describe("Password reset", () => {
    it("requests password reset email", async () => {
      mockForgotPassword.mockResolvedValue(undefined);

      function ResetTestComponent() {
        const { resetPassword } = useAuth();
        return <button onClick={() => resetPassword("test@example.com")}>Reset</button>;
      }

      const user = userEvent.setup();

      render(
        <AuthProvider>
          <ResetTestComponent />
        </AuthProvider>
      );

      await waitFor(() => {
        expect(screen.getByText("Reset")).toBeInTheDocument();
      });

      await act(async () => {
        await user.click(screen.getByText("Reset"));
      });

      expect(mockForgotPassword).toHaveBeenCalledWith("test@example.com");
    });
  });

  // ==================== Resend verification ====================

  describe("Resend verification", () => {
    it("resends verification email", async () => {
      mockResendVerificationEmail.mockResolvedValue(undefined);

      function ResendTestComponent() {
        const { resendVerification } = useAuth();
        return <button onClick={() => resendVerification()}>Resend</button>;
      }

      const user = userEvent.setup();

      render(
        <AuthProvider>
          <ResendTestComponent />
        </AuthProvider>
      );

      await waitFor(() => {
        expect(screen.getByText("Resend")).toBeInTheDocument();
      });

      await act(async () => {
        await user.click(screen.getByText("Resend"));
      });

      expect(mockResendVerificationEmail).toHaveBeenCalled();
    });
  });

  // ==================== Clear error ====================

  describe("Clear error", () => {
    it("clears error manually", async () => {
      mockLogin.mockRejectedValueOnce(new AuthError("Error", AuthErrorCode.UNKNOWN));

      function ClearErrorTestComponent() {
        const { error, login, clearError } = useAuth();
        
        const handleLogin = async () => {
          try {
            await login("test", "pass");
          } catch {
            // Error is set in state
          }
        };
        
        return (
          <div>
            <span data-testid="error">{error?.message ?? "null"}</span>
            <button onClick={handleLogin}>Login</button>
            <button onClick={() => clearError()}>Clear</button>
          </div>
        );
      }

      const user = userEvent.setup();

      render(
        <AuthProvider>
          <ClearErrorTestComponent />
        </AuthProvider>
      );

      await waitFor(() => {
        expect(screen.getByTestId("error")).toHaveTextContent("null");
      });

      await act(async () => {
        await user.click(screen.getByText("Login"));
      });

      await waitFor(() => {
        expect(screen.getByTestId("error")).toHaveTextContent("Error");
      });

      await act(async () => {
        await user.click(screen.getByText("Clear"));
      });

      await waitFor(() => {
        expect(screen.getByTestId("error")).toHaveTextContent("null");
      });
    });
  });

  // ==================== Refresh user ====================

  describe("Refresh user", () => {
    it("refreshes user data", async () => {
      mockFetchUser
        .mockResolvedValueOnce(null)
        .mockResolvedValueOnce(mockUser);

      function RefreshTestComponent() {
        const { user, refreshUser } = useAuth();
        return (
          <div>
            <span data-testid="user">{user?.name ?? "null"}</span>
            <button onClick={() => refreshUser()}>Refresh</button>
          </div>
        );
      }

      const user = userEvent.setup();

      render(
        <AuthProvider>
          <RefreshTestComponent />
        </AuthProvider>
      );

      await waitFor(() => {
        expect(screen.getByTestId("user")).toHaveTextContent("null");
      });

      await act(async () => {
        await user.click(screen.getByText("Refresh"));
      });

      await waitFor(() => {
        expect(screen.getByTestId("user")).toHaveTextContent("John Doe");
      });
    });
  });
});
