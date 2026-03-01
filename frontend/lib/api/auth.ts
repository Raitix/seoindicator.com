/**
 * Authentication API functions for SEOIndicator
 *
 * Uses Laravel Sanctum for SPA authentication with httpOnly cookies.
 * Falls back to localStorage for token storage when cookies are not available.
 */

import type {
  User,
  LoginData,
  RegisterData,
  ResetPasswordData,
  GoogleOAuthCallbackData,
  AuthResponse,
  AuthErrorResponse,
  AuthError,
} from "@/lib/types/auth";
import { AuthError as AuthErrorClass, AuthErrorCode, AUTH_STORAGE_KEYS } from "@/lib/types/auth";

const API_BASE = "/api/v1";

// ==================== Utility Functions ====================

/**
 * Sanitize user input to prevent XSS
 */
function sanitizeInput(input: string): string {
  return input
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#x27;")
    .replace(/\//g, "&#x2F;");
}

/**
 * Sanitize user object for display
 */
function sanitizeUser(user: any): User {
  return {
    id: user.id,
    name: sanitizeInput(user.name ?? ""),
    email: sanitizeInput(user.email ?? ""),
    email_verified: Boolean(user.email_verified ?? user.emailVerified ?? false),
    avatar: user.avatar ? sanitizeInput(user.avatar) : undefined,
    role: user.role,
    created_at: user.created_at ?? user.createdAt,
    updated_at: user.updated_at ?? user.updatedAt,
  };
}

/**
 * Handle API error responses
 */
async function handleErrorResponse(response: Response): Promise<never> {
  let errorBody: AuthErrorResponse | null = null;

  try {
    errorBody = await response.json();
  } catch {
    // Ignore JSON parsing errors
  }

  const message = errorBody?.message ?? `Request failed with status ${response.status}`;
  const error = AuthErrorClass.fromApiResponse(errorBody ?? { success: false, message });

  // Handle specific status codes
  if (response.status === 401) {
    throw new AuthErrorClass("Session expired. Please log in again.", AuthErrorCode.UNAUTHORIZED);
  }

  if (response.status === 429) {
    throw new AuthErrorClass(
      "Too many attempts. Please wait before trying again.",
      AuthErrorCode.RATE_LIMITED
    );
  }

  if (response.status >= 500) {
    throw new AuthErrorClass("Server error. Please try again later.", AuthErrorCode.SERVER_ERROR);
  }

  throw error;
}

/**
 * Make authenticated fetch request
 */
async function authFetch<T>(
  url: string,
  options: RequestInit = {}
): Promise<T> {
  const response = await fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...options.headers,
    },
    credentials: "include", // Include cookies for Sanctum
  });

  if (!response.ok) {
    await handleErrorResponse(response);
  }

  const data = await response.json();
  return data;
}

// ==================== CSRF Token ====================

/**
 * Get CSRF cookie from Sanctum
 */
export async function getCsrfToken(): Promise<void> {
  await fetch("/sanctum/csrf-cookie", {
    credentials: "include",
  });
}

// ==================== User Management ====================

/**
 * Get the current authenticated user
 */
export async function fetchUser(): Promise<User | null> {
  try {
    const response = await authFetch<AuthResponse>(`${API_BASE}/auth/user`);
    return response.data?.user ? sanitizeUser(response.data.user) : null;
  } catch (error) {
    // If 401, user is not authenticated
    if ((error as AuthError).code === AuthErrorCode.UNAUTHORIZED) {
      return null;
    }
    throw error;
  }
}

/**
 * Check if user is authenticated
 */
export async function checkAuth(): Promise<boolean> {
  try {
    const user = await fetchUser();
    return user !== null;
  } catch {
    return false;
  }
}

// ==================== Authentication ====================

/**
 * Login with email and password
 */
export async function login(
  email: string,
  password: string,
  remember: boolean = false
): Promise<User> {
  // Get CSRF token first
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(`${API_BASE}/auth/login`, {
    method: "POST",
    body: JSON.stringify({
      email: sanitizeInput(email),
      password, // Don't sanitize password
      remember,
    }),
  });

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Login failed",
      AuthErrorCode.INVALID_CREDENTIALS
    );
  }

  return sanitizeUser(response.data.user);
}

/**
 * Register a new user
 */
export async function register(data: RegisterData): Promise<User> {
  // Get CSRF token first
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(`${API_BASE}/auth/register`, {
    method: "POST",
    body: JSON.stringify({
      name: sanitizeInput(data.name),
      email: sanitizeInput(data.email),
      password: data.password,
      password_confirmation: data.password_confirmation,
    }),
  });

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Registration failed",
      AuthErrorCode.UNKNOWN
    );
  }

  return sanitizeUser(response.data.user);
}

/**
 * Logout the current user
 */
export async function logout(): Promise<void> {
  try {
    await getCsrfToken();
    await authFetch<AuthResponse>(`${API_BASE}/auth/logout`, {
      method: "POST",
    });
  } catch {
    // Even if the API call fails, clear local state
  }

  // Clear any stored data
  clearAuthStorage();
}

/**
 * Initiate Google OAuth login
 */
export async function initiateGoogleOAuth(): Promise<string> {
  const response = await authFetch<{ redirect_url: string }>(
    `${API_BASE}/auth/google`,
    {
      method: "POST",
    }
  );

  return response.redirect_url;
}

/**
 * Handle Google OAuth callback
 */
export async function handleGoogleCallback(data: GoogleOAuthCallbackData): Promise<{
  user: User;
  isNewUser: boolean;
}> {
  const response = await authFetch<AuthResponse>(
    `${API_BASE}/auth/google/callback`,
    {
      method: "POST",
      body: JSON.stringify({
        code: data.code,
        state: data.state,
      }),
    }
  );

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Google authentication failed",
      AuthErrorCode.OAUTH_FAILED
    );
  }

  return {
    user: sanitizeUser(response.data.user),
    isNewUser: response.data.is_new_user ?? false,
  };
}

// ==================== Password Management ====================

/**
 * Request password reset email
 */
export async function forgotPassword(email: string): Promise<void> {
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    `${API_BASE}/auth/forgot-password`,
    {
      method: "POST",
      body: JSON.stringify({
        email: sanitizeInput(email),
      }),
    }
  );

  if (!response.success) {
    throw new AuthErrorClass(
      response.message ?? "Failed to send reset email",
      AuthErrorCode.UNKNOWN
    );
  }
}

/**
 * Reset password with token
 */
export async function resetPassword(
  email: string,
  token: string,
  password: string,
  passwordConfirmation: string
): Promise<void> {
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    `${API_BASE}/auth/reset-password`,
    {
      method: "POST",
      body: JSON.stringify({
        email: sanitizeInput(email),
        token,
        password,
        password_confirmation: passwordConfirmation,
      }),
    }
  );

  if (!response.success) {
    throw new AuthErrorClass(
      response.message ?? "Failed to reset password",
      AuthErrorCode.UNKNOWN
    );
  }
}

// ==================== Email Verification ====================

/**
 * Resend email verification
 */
export async function resendVerificationEmail(): Promise<void> {
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    `${API_BASE}/auth/resend-verification`,
    {
      method: "POST",
    }
  );

  if (!response.success) {
    throw new AuthErrorClass(
      response.message ?? "Failed to resend verification email",
      AuthErrorCode.UNKNOWN
    );
  }
}

// ==================== Storage ====================

/**
 * Store user data locally for quick access
 */
export function storeUserData(user: User): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.setItem(AUTH_STORAGE_KEYS.USER, JSON.stringify(user));
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get stored user data
 */
export function getStoredUserData(): User | null {
  if (typeof window === "undefined") return null;

  try {
    const stored = localStorage.getItem(AUTH_STORAGE_KEYS.USER);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch {
    // Ignore storage errors
  }

  return null;
}

/**
 * Store redirect URL for after login
 */
export function storeRedirectUrl(url: string): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, url);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get stored redirect URL
 */
export function getStoredRedirectUrl(): string | null {
  if (typeof window === "undefined") return null;

  try {
    return localStorage.getItem(AUTH_STORAGE_KEYS.REDIRECT);
  } catch {
    return null;
  }
}

/**
 * Clear stored redirect URL
 */
export function clearStoredRedirectUrl(): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.removeItem(AUTH_STORAGE_KEYS.REDIRECT);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Clear all auth-related storage
 */
export function clearAuthStorage(): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.removeItem(AUTH_STORAGE_KEYS.USER);
    localStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
  } catch {
    // Ignore storage errors
  }
}

// ==================== Multi-Tab Sync ====================

/**
 * Broadcast auth state change to other tabs
 */
export function broadcastAuthChange(type: "login" | "logout"): void {
  if (typeof window === "undefined") return;

  try {
    localStorage.setItem(
      "seoindicator_auth_event",
      JSON.stringify({
        type,
        timestamp: Date.now(),
      })
    );
    // Remove immediately after setting to trigger storage event in other tabs
    localStorage.removeItem("seoindicator_auth_event");
  } catch {
    // Ignore storage errors
  }
}
