/**
 * Token Expiry Handler for SEOIndicator
 *
 * Handles graceful token expiry throughout the application.
 * When any API call returns 401, clears the invalid session and redirects
 * to login with a user-friendly message.
 *
 * Task: TASK-37-4
 * Spec: SPEC-037
 * Feature: FEAT-037
 */

import { AUTH_STORAGE_KEYS, AuthError, AuthErrorCode, shouldClearToken } from "@/lib/types/auth";

// ==================== Constants ====================

/**
 * Session expired message for URL param
 */
export const SESSION_EXPIRED_MESSAGE = "session_expired";

/**
 * User-friendly messages for session expiry
 */
export const SESSION_EXPIRED_MESSAGES = {
  title: "Session Expired",
  description: "Your session has expired. Please log in again to continue.",
  short: "Your session has expired. Please log in again.",
} as const;

/**
 * Public routes that should not trigger redirect
 */
const PUBLIC_ROUTES = [
  "/",
  "/login",
  "/register",
  "/forgot-password",
  "/reset-password",
  "/verify-email",
  "/tools",
  "/seo-guides",
] as const;

/**
 * Debounce timeout for preventing multiple redirects (in ms)
 */
const DEBOUNCE_TIMEOUT = 100;

// ==================== State ====================

/**
 * Track if we're currently handling a token expiry
 * to prevent multiple redirects
 */
let isHandlingExpiry = false;
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

// ==================== Utility Functions ====================

/**
 * Check if running in browser environment
 */
function isBrowser(): boolean {
  return typeof window !== "undefined";
}

/**
 * Check if current path is a public route
 */
export function isPublicRoute(path: string): boolean {
  return PUBLIC_ROUTES.some(
    (route) => path === route || path.startsWith(route + "/")
  );
}

/**
 * Sanitize a path to prevent XSS attacks
 * Removes any HTML tags and ensures the path starts with /
 */
export function sanitizePath(path: string): string {
  // Remove any HTML tags
  const sanitized = path.replace(/<[^>]*>/g, "");

  // Ensure it starts with / and doesn't start with // (protocol-relative URL)
  if (!sanitized.startsWith("/") || sanitized.startsWith("//")) {
    return "/";
  }

  return sanitized;
}

/**
 * Check if a redirect path is safe (not an open redirect)
 */
export function isSafeRedirect(path: string): boolean {
  // Only allow relative paths starting with /
  // Reject protocol-relative URLs (//) and absolute URLs (http://, https://)
  return path.startsWith("/") && !path.startsWith("//");
}

// ==================== Storage Functions ====================

/**
 * Clear all auth-related storage
 */
export function clearAuthStorageHandler(): void {
  if (!isBrowser()) return;

  try {
    localStorage.removeItem(AUTH_STORAGE_KEYS.USER);
    localStorage.removeItem(AUTH_STORAGE_KEYS.TOKEN);
  } catch {
    // Ignore storage errors (quota exceeded, private browsing, etc.)
  }
}

/**
 * Store redirect target for post-login navigation
 */
export function storeRedirectTarget(path: string): void {
  if (!isBrowser()) return;

  // Sanitize and validate path
  const sanitizedPath = sanitizePath(path);

  // Only store safe redirect paths
  if (!isSafeRedirect(sanitizedPath)) {
    return;
  }

  // Don't store if already on login page
  if (isPublicRoute(sanitizedPath)) {
    return;
  }

  try {
    localStorage.setItem(AUTH_STORAGE_KEYS.REDIRECT, sanitizedPath);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Get stored redirect target
 */
export function getRedirectTarget(): string | null {
  if (!isBrowser()) return null;

  try {
    return localStorage.getItem(AUTH_STORAGE_KEYS.REDIRECT);
  } catch {
    return null;
  }
}

/**
 * Clear stored redirect target
 */
export function clearRedirectTarget(): void {
  if (!isBrowser()) return;

  try {
    localStorage.removeItem(AUTH_STORAGE_KEYS.REDIRECT);
  } catch {
    // Ignore storage errors
  }
}

// ==================== Type Guard ====================

/**
 * Check if an error is a 401 unauthorized response
 */
export function isUnauthorizedResponse(error: unknown): boolean {
  if (!error || typeof error !== "object") {
    return false;
  }

  // Check for fetch response error format
  const err = error as Record<string, unknown>;

  // Check for response.status format (fetch API)
  if ("response" in err && err.response !== null && typeof err.response === "object") {
    const response = err.response as Record<string, unknown>;
    if ("status" in response && response.status === 401) {
      return true;
    }
  }

  // Check for AuthError with UNAUTHORIZED code
  if (error instanceof AuthError && error.code === AuthErrorCode.UNAUTHORIZED) {
    return true;
  }

  // Check for status property directly (some HTTP clients)
  if ("status" in err && err.status === 401) {
    return true;
  }

  return false;
}

/**
 * Check if an error indicates the token has expired
 * and should trigger logout/redirect
 */
export function shouldHandleTokenExpiry(error: unknown): boolean {
  // Use shouldClearToken from auth types for consistency
  if (shouldClearToken(error)) {
    return true;
  }

  // Also check for 401 response
  return isUnauthorizedResponse(error);
}

// ==================== Core Handler ====================

/**
 * Handle token expiry by clearing storage and redirecting to login
 *
 * @param redirectPath - Optional path to redirect to after login (defaults to current path)
 */
export function handleTokenExpiry(redirectPath?: string): void {
  if (!isBrowser()) return;

  // Prevent multiple rapid calls from triggering multiple redirects
  if (isHandlingExpiry) {
    return;
  }

  // Debounce to handle multiple 401s in quick succession
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  debounceTimer = setTimeout(() => {
    isHandlingExpiry = true;

    try {
      const currentPath = redirectPath ?? window.location.pathname;
      const fullPath =
        currentPath +
        window.location.search +
        window.location.hash;

      // Don't redirect if already on a public route
      if (isPublicRoute(currentPath)) {
        // Still clear storage for consistency
        clearAuthStorageHandler();
        isHandlingExpiry = false;
        return;
      }

      // Store redirect target for post-login navigation
      storeRedirectTarget(fullPath);

      // Clear auth storage BEFORE redirect
      // This ensures token is not exposed in browser history
      clearAuthStorageHandler();

      // Redirect to login with session expired message
      // Using window.location.href for full page navigation
      window.location.href = `/login?message=${SESSION_EXPIRED_MESSAGE}`;
    } finally {
      // Reset handling flag after navigation
      // Note: This may not execute if navigation succeeds immediately
      setTimeout(() => {
        isHandlingExpiry = false;
      }, DEBOUNCE_TIMEOUT);
    }
  }, DEBOUNCE_TIMEOUT);
}

// ==================== Fetch Interceptor ====================

/**
 * Create a wrapper around fetch that handles 401 responses
 */
export function createUnauthorizedInterceptor(): typeof fetch {
  const originalFetch = window.fetch;

  return async function interceptedFetch(
    input: RequestInfo | URL,
    init?: RequestInit
  ): Promise<Response> {
    const response = await originalFetch(input, init);

    // Check for 401 response
    if (response.status === 401) {
      // Don't handle 401 for auth endpoints (they handle their own errors)
      const url = typeof input === "string" ? input : input instanceof URL ? input.href : input.url;
      const isAuthEndpoint =
        url.includes("/auth/login") ||
        url.includes("/auth/register") ||
        url.includes("/auth/forgot-password") ||
        url.includes("/auth/reset-password") ||
        url.includes("/auth/user") || // fetchUser handles its own 401
        url.includes("/auth/google");

      if (!isAuthEndpoint) {
        // Clone response before reading body
        const clonedResponse = response.clone();

        // Handle token expiry
        handleTokenExpiry();
      }
    }

    return response;
  };
}

/**
 * Set up the fetch interceptor for 401 responses
 * This should be called once during app initialization
 */
export function setupUnauthorizedInterceptor(): void {
  if (!isBrowser()) return;

  // Only set up once
  if ((window as any).__tokenExpiryInterceptorInstalled) {
    return;
  }

  // Store original fetch
  const originalFetch = window.fetch;

  // Replace with interceptor
  window.fetch = createUnauthorizedInterceptor();

  // Mark as installed
  (window as any).__tokenExpiryInterceptorInstalled = true;

  // Provide cleanup function for testing
  (window as any).__restoreOriginalFetch = () => {
    window.fetch = originalFetch;
    delete (window as any).__tokenExpiryInterceptorInstalled;
  };
}

/**
 * Remove the fetch interceptor (for testing or cleanup)
 */
export function teardownUnauthorizedInterceptor(): void {
  if (!isBrowser()) return;

  if ((window as any).__restoreOriginalFetch) {
    (window as any).__restoreOriginalFetch();
  }
}

// ==================== Error Handler Integration ====================

/**
 * Handle API error responses, checking for 401
 * This can be used in catch blocks or error handlers
 */
export function handleApiError(error: unknown): void {
  if (shouldHandleTokenExpiry(error)) {
    handleTokenExpiry();
  }
}

// ==================== Message Utilities ====================

/**
 * Get user-friendly session expired message
 */
export function getSessionExpiredMessage(): string {
  return SESSION_EXPIRED_MESSAGES.short;
}

/**
 * Get full session expired message object for UI
 */
export function getSessionExpiredMessageObject(): {
  title: string;
  description: string;
} {
  return {
    title: SESSION_EXPIRED_MESSAGES.title,
    description: SESSION_EXPIRED_MESSAGES.description,
  };
}

/**
 * Check if URL has session expired message param
 */
export function hasSessionExpiredMessage(): boolean {
  if (!isBrowser()) return false;

  const params = new URLSearchParams(window.location.search);
  return params.get("message") === SESSION_EXPIRED_MESSAGE;
}

/**
 * Remove session expired message from URL
 */
export function clearSessionExpiredMessage(): void {
  if (!isBrowser()) return;

  const url = new URL(window.location.href);
  url.searchParams.delete("message");

  // Use replaceState to avoid adding to history
  window.history.replaceState({}, "", url.toString());
}
