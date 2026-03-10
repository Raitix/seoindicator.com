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
import { buildApiUrl, buildSanctumUrl } from "@/lib/api/base-url";

// ==================== Utility Functions ====================

function isBrowser(): boolean {
  return typeof window !== "undefined";
}

function headersToObject(headers?: HeadersInit): Record<string, string> {
  if (!headers) {
    return {};
  }

  if (typeof Headers !== "undefined" && headers instanceof Headers) {
    return Object.fromEntries(headers.entries());
  }

  if (Array.isArray(headers)) {
    return Object.fromEntries(headers);
  }

  return { ...(headers as Record<string, string>) };
}

function buildRequestHeaders(
  headers?: HeadersInit,
  options: {
    includeJsonContentType?: boolean;
    includeAuthToken?: boolean;
  } = {}
): Record<string, string> {
  const { includeJsonContentType = true, includeAuthToken = true } = options;
  const nextHeaders = headersToObject(headers);

  if (!nextHeaders.Accept) {
    nextHeaders.Accept = "application/json";
  }

  if (includeJsonContentType && !nextHeaders["Content-Type"]) {
    nextHeaders["Content-Type"] = "application/json";
  }

  const token = includeAuthToken ? getStoredAuthToken() : null;
  if (token && !nextHeaders.Authorization) {
    nextHeaders.Authorization = `Bearer ${token}`;
  }

  return nextHeaders;
}

function resolveAuthToken(response: AuthResponse | Record<string, unknown> | null | undefined): string | null {
  if (!response || typeof response !== "object") {
    return null;
  }

  const candidate =
    ("data" in response && response.data && typeof response.data === "object" && "token" in response.data
      ? (response.data as { token?: unknown }).token
      : null) ??
    ("token" in response ? (response as { token?: unknown }).token : null);

  return typeof candidate === "string" && candidate.trim().length > 0 ? candidate : null;
}

/**
 * Sanitize user input to prevent XSS
 */
function sanitizeInput(input: string): string {
  return input
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
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
  options: RequestInit = {},
  config: {
    includeAuthToken?: boolean;
  } = {}
): Promise<T> {
  const { includeAuthToken = true } = config;
  const response = await fetch(url, {
    ...options,
    headers: buildRequestHeaders(options.headers, {
      includeJsonContentType: options.body !== undefined,
      includeAuthToken,
    }),
    credentials: "include",
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
  await fetch(buildSanctumUrl(), {
    credentials: "include",
  });
}

// ==================== User Management ====================

/**
 * Get the current authenticated user
 */
export async function fetchUser(): Promise<User | null> {
  try {
    const response = await authFetch<AuthResponse>(buildApiUrl("/auth/user"));
    return response.data?.user ? sanitizeUser(response.data.user) : null;
  } catch (error) {
    if ((error as AuthError).code === AuthErrorCode.UNAUTHORIZED) {
      clearStoredAuthToken();
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
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    buildApiUrl("/auth/login"),
    {
      method: "POST",
      body: JSON.stringify({
        email: sanitizeInput(email),
        password,
        remember,
      }),
    },
    {
      includeAuthToken: false,
    }
  );

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Login failed",
      AuthErrorCode.INVALID_CREDENTIALS
    );
  }

  const token = resolveAuthToken(response);
  if (token) {
    storeAuthToken(token);
  }

  return sanitizeUser(response.data.user);
}

/**
 * Register a new user
 */
export async function register(data: RegisterData): Promise<User> {
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    buildApiUrl("/auth/register"),
    {
      method: "POST",
      body: JSON.stringify({
        name: sanitizeInput(data.name),
        email: sanitizeInput(data.email),
        password: data.password,
        password_confirmation: data.password_confirmation,
      }),
    },
    {
      includeAuthToken: false,
    }
  );

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Registration failed",
      AuthErrorCode.UNKNOWN
    );
  }

  const token = resolveAuthToken(response);
  if (token) {
    storeAuthToken(token);
  } else {
    clearStoredAuthToken();
  }

  return sanitizeUser(response.data.user);
}

/**
 * Logout the current user
 */
export async function logout(): Promise<void> {
  try {
    await getCsrfToken();
    await authFetch<AuthResponse>(buildApiUrl("/auth/logout"), {
      method: "POST",
    });
  } catch {
    // Even if the API call fails, clear local state
  }

  clearAuthStorage();
}

/**
 * Initiate Google OAuth login
 */
export async function initiateGoogleOAuth(): Promise<string> {
  const response = await authFetch<{ redirect_url: string }>(
    buildApiUrl("/auth/google"),
    {
      method: "POST",
    },
    {
      includeAuthToken: false,
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
    buildApiUrl("/auth/google/callback"),
    {
      method: "POST",
      body: JSON.stringify({
        code: data.code,
        state: data.state,
      }),
    },
    {
      includeAuthToken: false,
    }
  );

  if (!response.success || !response.data?.user) {
    throw new AuthErrorClass(
      response.message ?? "Google authentication failed",
      AuthErrorCode.OAUTH_FAILED
    );
  }

  const token = resolveAuthToken(response);
  if (token) {
    storeAuthToken(token);
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
    buildApiUrl("/auth/forgot-password"),
    {
      method: "POST",
      body: JSON.stringify({
        email: sanitizeInput(email),
      }),
    },
    {
      includeAuthToken: false,
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
    buildApiUrl("/auth/reset-password"),
    {
      method: "POST",
      body: JSON.stringify({
        email: sanitizeInput(email),
        token,
        password,
        password_confirmation: passwordConfirmation,
      }),
    },
    {
      includeAuthToken: false,
    }
  );

  if (!response.success) {
    throw new AuthErrorClass(
      response.message ?? "Failed to reset password",
      AuthErrorCode.UNKNOWN
    );
  }

  const nextToken = resolveAuthToken(response);
  if (nextToken) {
    storeAuthToken(nextToken);
  }
}

// ==================== Email Verification ====================

/**
 * Resend email verification
 */
export async function resendVerificationEmail(): Promise<void> {
  await getCsrfToken();

  const response = await authFetch<AuthResponse>(
    buildApiUrl("/auth/resend-verification"),
    {
      method: "POST",
    }
  );

  if (!response.success) {
    throw new AuthErrorClass(
      response.message ?? "Failed to resend verification",
      AuthErrorCode.UNKNOWN
    );
  }
}

// ==================== Storage ====================

/**
 * Store user data locally for quick access
 */
export function storeUserData(user: User): void {
  if (!isBrowser()) return;

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
  if (!isBrowser()) return null;

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
 * Store auth token locally for bearer-token fallback
 */
export function storeAuthToken(token: string): void {
  if (!isBrowser()) return;

  try {
    localStorage.setItem(AUTH_STORAGE_KEYS.TOKEN, token);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get stored auth token
 */
export function getStoredAuthToken(): string | null {
  if (!isBrowser()) return null;

  try {
    return localStorage.getItem(AUTH_STORAGE_KEYS.TOKEN);
  } catch {
    return null;
  }
}

/**
 * Clear stored auth token
 */
export function clearStoredAuthToken(): void {
  if (!isBrowser()) return;

  try {
    localStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Store redirect URL for after login
 */
export function storeRedirectUrl(url: string): void {
  if (!isBrowser()) return;

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
  if (!isBrowser()) return null;

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
  if (!isBrowser()) return;

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
  if (!isBrowser()) return;

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
  if (!isBrowser()) return;

  try {
    localStorage.setItem(
      "seoindicator_auth_event",
      JSON.stringify({
        type,
        timestamp: Date.now(),
      })
    );
    localStorage.removeItem("seoindicator_auth_event");
  } catch {
    // Ignore storage errors
  }
}
