/**
 * Authentication types for SEOIndicator
 */

// ==================== User Types ====================

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  avatar?: string;
  role?: "user" | "admin";
  created_at?: string;
  updated_at?: string;
  lastLoginAt?: string;
}

// ==================== Auth Data Types ====================

export interface LoginData {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface ResetPasswordData {
  email: string;
  token?: string;
  password?: string;
  password_confirmation?: string;
}

export interface GoogleOAuthCallbackData {
  code: string;
  state?: string;
}

// ==================== API Response Types ====================

export interface AuthResponse {
  success: boolean;
  message?: string;
  data?: {
    user: User;
    token?: string;
    is_new_user?: boolean;
  };
}

export interface AuthErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

// ==================== Context Types ====================

export interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
  login: (email: string, password: string, remember?: boolean) => Promise<void>;
  loginWithGoogle: () => Promise<void>;
  logout: () => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  resetPassword: (email: string, token?: string, password?: string) => Promise<void>;
  resendVerification: () => Promise<void>;
  clearError: () => void;
  refreshUser: () => Promise<void>;
}

// ==================== Error Types ====================

export enum AuthErrorCode {
  INVALID_CREDENTIALS = "INVALID_CREDENTIALS",
  EMAIL_NOT_VERIFIED = "EMAIL_NOT_VERIFIED",
  EMAIL_ALREADY_EXISTS = "EMAIL_ALREADY_EXISTS",
  PASSWORD_MISMATCH = "PASSWORD_MISMATCH",
  WEAK_PASSWORD = "WEAK_PASSWORD",
  INVALID_EMAIL = "INVALID_EMAIL",
  INVALID_TOKEN = "INVALID_TOKEN",
  NETWORK_ERROR = "NETWORK_ERROR",
  SERVER_ERROR = "SERVER_ERROR",
  UNAUTHORIZED = "UNAUTHORIZED",
  TOKEN_EXPIRED = "TOKEN_EXPIRED",
  OAUTH_FAILED = "OAUTH_FAILED",
  RATE_LIMITED = "RATE_LIMITED",
  UNKNOWN = "UNKNOWN",
}

export class AuthError extends Error {
  code: AuthErrorCode;
  field?: string;
  details?: Record<string, string[]>;

  constructor(
    message: string,
    code: AuthErrorCode = AuthErrorCode.UNKNOWN,
    field?: string,
    details?: Record<string, string[]>
  ) {
    super(message);
    this.name = "AuthError";
    this.code = code;
    this.field = field;
    this.details = details;
  }

  static fromApiResponse(response: AuthErrorResponse): AuthError {
    const message = response.message || "An authentication error occurred";

    // Map common error messages to error codes
    let code = AuthErrorCode.UNKNOWN;
    if (message.toLowerCase().includes("invalid credentials")) {
      code = AuthErrorCode.INVALID_CREDENTIALS;
    } else if (message.toLowerCase().includes("email already")) {
      code = AuthErrorCode.EMAIL_ALREADY_EXISTS;
    } else if (message.toLowerCase().includes("not verified")) {
      code = AuthErrorCode.EMAIL_NOT_VERIFIED;
    } else if (message.toLowerCase().includes("rate limit")) {
      code = AuthErrorCode.RATE_LIMITED;
    }

    return new AuthError(message, code, undefined, response.errors);
  }
}

/**
 * Check if an error is an authentication error that should clear stored credentials.
 * These errors indicate the token is invalid/expired and should not be preserved.
 */
export function isAuthError(error: unknown): error is AuthError {
  return error instanceof AuthError;
}

/**
 * Check if an error indicates an invalid/expired token that should be cleared.
 * Returns true for UNAUTHORIZED and TOKEN_EXPIRED errors.
 * Returns false for network errors, server errors, and other non-auth errors.
 */
export function shouldClearToken(error: unknown): boolean {
  if (!isAuthError(error)) {
    // Non-AuthError errors (network errors, etc.) should NOT clear token
    return false;
  }

  // Only clear token for these specific auth errors
  const clearableCodes: AuthErrorCode[] = [
    AuthErrorCode.UNAUTHORIZED,
    AuthErrorCode.TOKEN_EXPIRED,
    AuthErrorCode.INVALID_TOKEN,
  ];

  return clearableCodes.includes(error.code);
}

// ==================== Token Types ====================

export interface TokenInfo {
  token: string;
  expiresAt?: number;
  type: "cookie" | "localStorage";
}

// ==================== Storage Keys ====================

export const AUTH_STORAGE_KEYS = {
  USER: "seoindicator_user",
  TOKEN: "seoindicator_token",
  REDIRECT: "seoindicator_redirect",
} as const;
