"use client";

import * as React from "react";
import { useRouter, usePathname } from "next/navigation";

import type {
  User,
  AuthContextType,
  RegisterData,
  AuthError as AuthErrorType,
} from "@/lib/types/auth";
import { AuthError, AuthErrorCode, shouldClearToken } from "@/lib/types/auth";
import {
  fetchUser,
  login as apiLogin,
  register as apiRegister,
  logout as apiLogout,
  initiateGoogleOAuth,
  handleGoogleCallback,
  forgotPassword,
  resetPassword as apiResetPassword,
  resendVerificationEmail,
  storeUserData,
  getStoredUserData,
  getStoredAuthToken,
  storeRedirectUrl,
  getStoredRedirectUrl,
  clearStoredRedirectUrl,
  clearAuthStorage,
  broadcastAuthChange,
} from "@/lib/api/auth";
import {
  setupUnauthorizedInterceptor,
  handleApiError,
  hasSessionExpiredMessage,
  clearSessionExpiredMessage,
} from "@/lib/api/token-expiry-handler";

// ==================== Context Creation ====================

const AuthContext = React.createContext<AuthContextType | null>(null);

// ==================== Provider ====================

interface AuthProviderProps {
  children: React.ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const router = useRouter();
  const pathname = usePathname();

  // State
  const [user, setUser] = React.useState<User | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<AuthErrorType | null>(null);
  const [isInitialized, setIsInitialized] = React.useState(false);

  // Track if login/register is in progress to prevent duplicate requests
  const loginInProgress = React.useRef(false);

  // ==================== Public Routes ====================

  const publicRoutes = React.useMemo(
    () => [
      "/",
      "/login",
      "/register",
      "/forgot-password",
      "/reset-password",
      "/verify-email",
      "/tools",
      "/seo-guides",
    ],
    []
  );

  const isPublicRoute = React.useCallback(
    (path: string) => {
      return publicRoutes.some(
        (route) => path === route || path.startsWith(route + "/")
      );
    },
    [publicRoutes]
  );

  // ==================== Initialize User on Mount ====================

  React.useEffect(() => {
    if (isInitialized) return;

    // Set up 401 interceptor for automatic token expiry handling
    setupUnauthorizedInterceptor();

    const initializeAuth = async () => {
      const storedUser = getStoredUserData();
      const storedToken = getStoredAuthToken();

      try {
        // If there's no stored token, skip API call and set user to null
        if (!storedToken) {
          setUser(null);
          clearAuthStorage();
          return;
        }

        // Fetch fresh user data from API to verify token validity
        // This is the key fix: we MUST verify token with API before setting user
        const freshUser = await fetchUser();

        if (freshUser) {
          // API confirmed token is valid - set user and store data
          setUser(freshUser);
          storeUserData(freshUser);
        } else {
          // API returned null (no valid session) - clear storage
          setUser(null);
          clearAuthStorage();
        }
      } catch (err) {
        console.error("Auth initialization error:", err);

        // Check if this is an auth error (invalid/expired token)
        // that should clear stored credentials
        if (shouldClearToken(err)) {
          // Token is invalid/expired - clear everything and show login
          setUser(null);
          clearAuthStorage();
        } else {
          // Network error, server error, or other non-auth error
          // Preserve the stored user for offline resilience
          // but don't set isAuthenticated if we don't have a stored user
          if (storedUser) {
            setUser(storedUser);
          } else {
            setUser(null);
            clearAuthStorage();
          }
        }
      } finally {
        setIsLoading(false);
        setIsInitialized(true);
      }
    };

    initializeAuth();
  }, [isInitialized]);

  // ==================== Handle Redirects ====================

  React.useEffect(() => {
    if (isLoading || !isInitialized) return;

    const isAuthenticated = user !== null;

    // Redirect authenticated users away from auth pages
    if (isAuthenticated) {
      if (pathname === "/login" || pathname === "/register") {
        // Check for stored redirect URL
        const redirectUrl = getStoredRedirectUrl();
        if (redirectUrl) {
          clearStoredRedirectUrl();
          router.push(redirectUrl);
        } else {
          router.push("/dashboard");
        }
      }
    }

    // Redirect unauthenticated users to login for protected routes
    if (!isAuthenticated && !isPublicRoute(pathname)) {
      storeRedirectUrl(pathname);
      router.push("/login");
    }
  }, [user, isLoading, isInitialized, pathname, router, isPublicRoute]);

  // ==================== Listen for Multi-Tab Sync ====================

  React.useEffect(() => {
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === "seoindicator_auth_event" && e.newValue) {
        try {
          const event = JSON.parse(e.newValue);
          if (event.type === "logout") {
            setUser(null);
            clearAuthStorage();
          } else if (event.type === "login") {
            // Fetch user data from API to verify token
            fetchUser()
              .then((freshUser) => {
                if (freshUser) {
                  setUser(freshUser);
                  storeUserData(freshUser);
                } else {
                  // API returned null - clear storage
                  setUser(null);
                  clearAuthStorage();
                }
              })
              .catch((err) => {
                // On auth error, clear storage; on network error, try fallback
                if (shouldClearToken(err)) {
                  setUser(null);
                  clearAuthStorage();
                } else {
                  // Network error - try to use stored user as fallback
                  const storedUser = getStoredUserData();
                  if (storedUser) {
                    setUser(storedUser);
                  }
                }
              });
          }
        } catch {
          // Ignore parsing errors
        }
      }
    };

    window.addEventListener("storage", handleStorageChange);
    return () => window.removeEventListener("storage", handleStorageChange);
  }, []);

  // ==================== Clear Error on Navigation ====================

  React.useEffect(() => {
    setError(null);
  }, [pathname]);

  // ==================== Handle Session Expired Message ====================

  React.useEffect(() => {
    // Show session expired message if present in URL
    if (pathname === "/login" && hasSessionExpiredMessage()) {
      setError({
        message: "Your session has expired. Please log in again.",
        code: "SESSION_EXPIRED" as any,
      } as any);
      // Clear the message from URL
      clearSessionExpiredMessage();
    }
  }, [pathname]);

  // ==================== Login ====================

  const login = React.useCallback(
    async (email: string, password: string, remember: boolean = false) => {
      // Prevent duplicate login requests
      if (loginInProgress.current) {
        return;
      }

      loginInProgress.current = true;
      setError(null);
      setIsLoading(true);

      try {
        const loggedInUser = await apiLogin(email, password, remember);
        setUser(loggedInUser);
        storeUserData(loggedInUser);
        broadcastAuthChange("login");

        // Redirect will be handled by useEffect
      } catch (err) {
        const authError =
          err instanceof AuthError
            ? err
            : new AuthError(
                (err as Error)?.message ?? "Login failed",
                AuthErrorCode.UNKNOWN
              );
        setError(authError);
        throw authError;
      } finally {
        setIsLoading(false);
        loginInProgress.current = false;
      }
    },
    []
  );

  // ==================== Register ====================

  const register = React.useCallback(async (data: RegisterData) => {
    // Prevent duplicate register requests
    if (loginInProgress.current) {
      return;
    }

    loginInProgress.current = true;
    setError(null);
    setIsLoading(true);

    try {
      const newUser = await apiRegister(data);
      setUser(newUser);
      storeUserData(newUser);
      broadcastAuthChange("login");

      // User may need to verify email before being fully authenticated
      // The API response will indicate this
    } catch (err) {
      const authError =
        err instanceof AuthError
          ? err
          : new AuthError(
              (err as Error)?.message ?? "Registration failed",
              AuthErrorCode.UNKNOWN
            );
      setError(authError);
      throw authError;
    } finally {
      setIsLoading(false);
      loginInProgress.current = false;
    }
  }, []);

  // ==================== Logout ====================

  const logout = React.useCallback(async () => {
    setError(null);
    setIsLoading(true);

    try {
      await apiLogout();
    } catch {
      // Continue with local logout even if API fails
    } finally {
      setUser(null);
      clearAuthStorage();
      broadcastAuthChange("logout");
      setIsLoading(false);
      router.push("/login");
    }
  }, [router]);

  // ==================== Google OAuth ====================

  const loginWithGoogle = React.useCallback(async () => {
    setError(null);
    setIsLoading(true);

    try {
      const redirectUrl = await initiateGoogleOAuth();
      // Store current path for redirect after OAuth
      storeRedirectUrl(pathname);
      // Redirect to Google OAuth
      window.location.href = redirectUrl;
    } catch (err) {
      const authError =
        err instanceof AuthError
          ? err
          : new AuthError(
              (err as Error)?.message ?? "Google login failed",
              AuthErrorCode.OAUTH_FAILED
            );
      setError(authError);
      setIsLoading(false);
      throw authError;
    }
  }, [pathname]);

  // ==================== Password Reset ====================

  const resetPassword = React.useCallback(
    async (email: string, token?: string, password?: string) => {
      setError(null);
      setIsLoading(true);

      try {
        if (token && password) {
          // Reset password with token
          await apiResetPassword(email, token, password, password);
          router.push("/login");
        } else {
          // Request reset email
          await forgotPassword(email);
        }
      } catch (err) {
        const authError =
          err instanceof AuthError
            ? err
            : new AuthError(
                (err as Error)?.message ?? "Password reset failed",
                AuthErrorCode.UNKNOWN
              );
        setError(authError);
        throw authError;
      } finally {
        setIsLoading(false);
      }
    },
    [router]
  );

  // ==================== Resend Verification ====================

  const resendVerification = React.useCallback(async () => {
    setError(null);
    setIsLoading(true);

    try {
      await resendVerificationEmail();
    } catch (err) {
      const authError =
        err instanceof AuthError
          ? err
          : new AuthError(
              (err as Error)?.message ?? "Failed to resend verification",
              AuthErrorCode.UNKNOWN
            );
      setError(authError);
      throw authError;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // ==================== Clear Error ====================

  const clearError = React.useCallback(() => {
    setError(null);
  }, []);

  // ==================== Refresh User ====================

  const refreshUser = React.useCallback(async () => {
    setIsLoading(true);

    try {
      const freshUser = await fetchUser();
      if (freshUser) {
        setUser(freshUser);
        storeUserData(freshUser);
      } else {
        // API returned null - clear storage
        setUser(null);
        clearAuthStorage();
      }
    } catch (err) {
      console.error("Failed to refresh user:", err);
      
      // On auth error, clear storage; on network error, preserve user
      if (shouldClearToken(err)) {
        setUser(null);
        clearAuthStorage();
      }
      // For network errors, keep the current user
    } finally {
      setIsLoading(false);
    }
  }, []);

  // ==================== Context Value ====================

  const value: AuthContextType = {
    user,
    isAuthenticated: user !== null,
    isLoading,
    error,
    login,
    loginWithGoogle,
    logout,
    register,
    resetPassword,
    resendVerification,
    clearError,
    refreshUser,
  };

  return (
    <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
  );
}

// ==================== Hooks ====================

/**
 * Hook to access auth context
 * @throws Error if used outside of AuthProvider
 */
export function useAuth(): AuthContextType {
  const context = React.useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }

  return context;
}

/**
 * Hook for components that only need user info
 */
export function useUser() {
  const { user, isAuthenticated, isLoading } = useAuth();
  return { user, isAuthenticated, isLoading };
}

/**
 * Hook for protected page components
 * Returns user and auth actions
 */
export function useAuthActions() {
  const { login, logout, register, resetPassword, resendVerification, refreshUser, clearError } =
    useAuth();

  return {
    login,
    logout,
    register,
    resetPassword,
    resendVerification,
    refreshUser,
    clearError,
  };
}

/**
 * Hook to check if user has specific role
 */
export function useHasRole(role: "user" | "admin"): boolean {
  const { user } = useAuth();
  return user?.role === role || user?.role === "admin";
}

// Export context for advanced use cases
export { AuthContext };
