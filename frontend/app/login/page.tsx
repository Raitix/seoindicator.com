"use client";

import * as React from "react";
import { Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Eye, EyeOff, Loader2 } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { loginSchema, type LoginFormData } from "@/lib/validators/auth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  getStoredRedirectUrl,
  clearStoredRedirectUrl,
} from "@/lib/api/auth";

// ==================== Redirect Utilities ====================

const REDIRECT_STORAGE_KEY = "seoindicator_redirect";

/**
 * Validates a redirect path to prevent open redirect attacks.
 * Only allows relative paths starting with "/".
 * Rejects external URLs, javascript:, data:, and protocol-relative URLs.
 */
function isValidRedirect(redirect: string | null | undefined): boolean {
  if (!redirect) return false;
  if (typeof redirect !== "string") return false;
  
  const trimmed = redirect.trim();
  if (trimmed === "") return false;

  // Must start with /
  if (!trimmed.startsWith("/")) return false;

  // Reject protocol-relative URLs (//evil.com)
  if (trimmed.startsWith("//")) return false;

  // Reject absolute URLs (https://, http://)
  if (/^https?:\/\//i.test(trimmed)) return false;

  // Reject javascript: URLs
  if (/^javascript:/i.test(trimmed)) return false;

  // Reject data: URLs
  if (/^data:/i.test(trimmed)) return false;

  // Reject path traversal attempts
  if (trimmed.includes("../") || trimmed.includes("..\\")) return false;

  return true;
}

/**
 * Sanitizes a redirect path to prevent XSS attacks.
 * Removes any HTML tags or script content.
 */
function sanitizeRedirect(redirect: string): string {
  // Remove any HTML tags
  return redirect.replace(/<[^>]*>/g, "");
}

/**
 * Gets the redirect destination with priority:
 * 1. URL param (?redirect=/path)
 * 2. localStorage (seoindicator_redirect)
 * 3. Default to /dashboard
 */
function getRedirectDestination(
  urlParam: string | null,
  localStorageValue: string | null
): string {
  // URL param takes priority
  if (urlParam && isValidRedirect(urlParam)) {
    return sanitizeRedirect(urlParam);
  }

  // Fall back to localStorage
  if (localStorageValue && isValidRedirect(localStorageValue)) {
    return sanitizeRedirect(localStorageValue);
  }

  // Default to dashboard
  return "/dashboard";
}

/**
 * Clears the stored redirect URL from localStorage
 */
function clearRedirectStorage(): void {
  if (typeof window === "undefined") return;
  
  try {
    localStorage.removeItem(REDIRECT_STORAGE_KEY);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Loading skeleton for the login form
 */
function LoginLoadingSkeleton() {
  return (
    <Card className="w-full max-w-md">
      <CardContent className="flex items-center justify-center py-12">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          <p className="text-sm text-muted-foreground">Loading...</p>
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Inner login component that uses useSearchParams
 * Must be wrapped in Suspense for static export
 */
function LoginContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, loginWithGoogle, isLoading, error, clearError, isAuthenticated, user } = useAuth();

  const [showPassword, setShowPassword] = React.useState(false);
  const [submitError, setSubmitError] = React.useState<string | null>(null);

  // Get redirect target from URL param and localStorage
  const redirectFromUrl = searchParams.get("redirect");
  const [redirectFromStorage, setRedirectFromStorage] = React.useState<string | null>(null);

  // Read localStorage redirect on mount
  React.useEffect(() => {
    const stored = getStoredRedirectUrl();
    setRedirectFromStorage(stored);
  }, []);

  // Calculate final redirect destination
  const redirectDestination = React.useMemo(() => {
    return getRedirectDestination(redirectFromUrl, redirectFromStorage);
  }, [redirectFromUrl, redirectFromStorage]);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
      remember: false,
    },
  });

  // Redirect if already authenticated with a valid user
  // IMPORTANT: Check BOTH isAuthenticated AND user to prevent redirect loops
  // when token is invalid but isAuthenticated was briefly true
  React.useEffect(() => {
    // Don't redirect while auth is still loading (prevents flash redirect)
    if (isLoading) {
      return;
    }

    // Only redirect if we have BOTH a valid authentication state AND user data
    // This prevents redirect loops when token is invalid
    if (isAuthenticated && user) {
      // Clear stored redirect after use
      clearStoredRedirectUrl();
      clearRedirectStorage();
      
      // Redirect to the stored destination
      router.push(redirectDestination);
    }
  }, [isAuthenticated, user, isLoading, router, redirectDestination]);

  // Clear errors on mount
  React.useEffect(() => {
    clearError();
  }, [clearError]);

  const onSubmit = async (data: LoginFormData) => {
    setSubmitError(null);

    try {
      await login(data.email, data.password, data.remember);
      // Redirect is handled by AuthContext
    } catch (err) {
      const message = err instanceof Error ? err.message : "Login failed";
      setSubmitError(message);
    }
  };

  const handleGoogleLogin = async () => {
    setSubmitError(null);

    try {
      await loginWithGoogle();
    } catch (err) {
      const message = err instanceof Error ? err.message : "Google login failed";
      setSubmitError(message);
    }
  };

  const isLoadingState = isLoading || isSubmitting;

  // Show loading state while checking token validity on mount
  // This prevents flash of login form for already-authenticated users
  // and prevents redirect loops for users with invalid tokens
  const isCheckingAuth = isLoading && !isSubmitting;

  return isCheckingAuth ? (
    <Card className="w-full max-w-md">
      <CardContent className="flex items-center justify-center py-12">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
          <p className="text-sm text-muted-foreground">Checking authentication...</p>
        </div>
      </CardContent>
    </Card>
  ) : (
    <Card className="w-full max-w-md">
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold text-center">Sign In</CardTitle>
        <CardDescription className="text-center">
          Enter your credentials to access your account
        </CardDescription>
      </CardHeader>

      <form onSubmit={handleSubmit(onSubmit)}>
      <CardContent className="space-y-4">
        {/* Error Message */}
        {(submitError || error) && (
          <div
            role="alert"
            className="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-md text-sm"
          >
            {submitError || error?.message}
          </div>
        )}

        {/* Email Field */}
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <Input
            id="email"
            type="email"
            placeholder="name@example.com"
            autoComplete="email"
            aria-describedby={errors.email ? "email-error" : undefined}
            aria-invalid={!!errors.email}
            {...register("email")}
          />
          {errors.email && (
            <p id="email-error" className="text-sm text-destructive" role="alert">
              {errors.email.message}
            </p>
          )}
        </div>

        {/* Password Field */}
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <div className="relative">
            <Input
              id="password"
              type={showPassword ? "text" : "password"}
              placeholder="Enter your password"
              autoComplete="current-password"
              maxLength={256}
              aria-describedby={errors.password ? "password-error" : undefined}
              aria-invalid={!!errors.password}
              {...register("password")}
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              aria-label={showPassword ? "Hide password" : "Show password"}
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
          {errors.password && (
            <p id="password-error" className="text-sm text-destructive" role="alert">
              {errors.password.message}
            </p>
          )}
        </div>

        {/* Remember Me & Forgot Password */}
        <div className="flex items-center justify-between">
          <label className="flex items-center space-x-2 cursor-pointer">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-input"
              {...register("remember")}
            />
            <span className="text-sm text-muted-foreground">Remember me</span>
          </label>
          <Button
            type="button"
            variant="link"
            className="px-0 text-sm"
            onClick={() => router.push("/forgot-password")}
          >
            Forgot password?
          </Button>
        </div>

        {/* Submit Button */}
        <Button type="submit" className="w-full" disabled={isLoadingState}>
          {isLoadingState ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Signing in...
            </>
          ) : (
            "Sign In"
          )}
        </Button>

        {/* Divider */}
        <div className="relative">
          <div className="absolute inset-0 flex items-center">
            <span className="w-full border-t" />
          </div>
          <div className="relative flex justify-center text-xs uppercase">
            <span className="bg-card px-2 text-muted-foreground">Or continue with</span>
          </div>
        </div>

        {/* Google Sign In */}
        <Button
          type="button"
          variant="outline"
          className="w-full"
          onClick={handleGoogleLogin}
          disabled={isLoadingState}
        >
          <svg className="h-5 w-5 mr-2" viewBox="0 0 24 24">
            <path
              fill="currentColor"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="currentColor"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="currentColor"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
              fill="currentColor"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
          </svg>
          Sign in with Google
        </Button>
      </CardContent>
    </form>

    <CardFooter className="flex justify-center">
      <p className="text-sm text-muted-foreground">
        Don&apos;t have an account?{" "}
        <Button
          type="button"
          variant="link"
          className="px-0"
          onClick={() => router.push("/register")}
        >
          Sign up
        </Button>
      </p>
    </CardFooter>
  </Card>
  );
}

/**
 * Login page component with Suspense boundary for useSearchParams
 */
export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
      <Suspense fallback={<LoginLoadingSkeleton />}>
        <LoginContent />
      </Suspense>
    </div>
  );
}
