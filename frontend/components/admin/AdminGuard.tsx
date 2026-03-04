"use client";

import * as React from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuth } from "@/lib/contexts/AuthContext";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

// ==================== Types ====================

export interface AdminGuardProps {
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

// ==================== Loading Component ====================

function AdminLoading() {
  return (
    <div
      data-testid="admin-loading"
      role="status"
      aria-live="polite"
      className="flex min-h-screen items-center justify-center bg-slate-900"
    >
      <div className="flex flex-col items-center gap-4">
        <div className="h-12 w-12 animate-spin rounded-full border-4 border-slate-700 border-t-blue-500" />
        <span className="text-slate-400">Checking admin access...</span>
      </div>
    </div>
  );
}

// ==================== Unauthorized Component ====================

export interface AdminUnauthorizedProps {
  redirectPath?: string;
}

export function AdminUnauthorized({ redirectPath }: AdminUnauthorizedProps) {
  const router = useRouter();
  const pathname = usePathname();

  const handleLogin = React.useCallback(() => {
    const redirect = redirectPath || pathname;
    router.push(`/login?redirect=${encodeURIComponent(redirect)}`);
  }, [router, pathname, redirectPath]);

  return (
    <div
      data-testid="admin-unauthorized"
      role="alert"
      className="flex min-h-screen items-center justify-center bg-slate-900"
    >
      <div className="max-w-md rounded-lg bg-slate-800 p-8 text-center shadow-xl">
        <div className="mb-4 text-5xl">🔐</div>
        <h2 className="mb-2 text-2xl font-bold text-white">
          Authentication Required
        </h2>
        <p className="mb-6 text-slate-400">
          You must be logged in to access the admin dashboard.
        </p>
        <Button onClick={handleLogin} data-testid="login-button">
          Log In
        </Button>
      </div>
    </div>
  );
}

// ==================== Forbidden Component ====================

export interface AdminForbiddenProps {
  redirectPath?: string;
}

export function AdminForbidden({ redirectPath }: AdminForbiddenProps) {
  const router = useRouter();

  const handleBack = React.useCallback(() => {
    router.push(redirectPath || "/dashboard");
  }, [router, redirectPath]);

  return (
    <div
      data-testid="admin-forbidden"
      role="alert"
      className="flex min-h-screen items-center justify-center bg-slate-900"
    >
      <div className="max-w-md rounded-lg bg-slate-800 p-8 text-center shadow-xl">
        <div className="mb-4 text-5xl">⛔</div>
        <h2 className="mb-2 text-2xl font-bold text-white">Access Denied</h2>
        <p className="mb-6 text-slate-400">
          You do not have permission to access the admin dashboard.
        </p>
        <Button onClick={handleBack} data-testid="back-button">
          Return to Dashboard
        </Button>
      </div>
    </div>
  );
}

// ==================== Admin Guard Component ====================

export function AdminGuard({ children, fallback }: AdminGuardProps) {
  const { user, isAuthenticated, isLoading } = useAuth();

  // Loading state
  if (isLoading) {
    return <AdminLoading />;
  }

  // Not authenticated
  if (!isAuthenticated || !user) {
    if (fallback) {
      return <>{fallback}</>;
    }
    return <AdminUnauthorized />;
  }

  // Not admin - check role field
  const isAdmin = user.role === "admin";
  if (!isAdmin) {
    if (fallback) {
      return <>{fallback}</>;
    }
    return <AdminForbidden />;
  }

  // Authorized
  return (
    <div data-testid="admin-authorized">
      {children}
    </div>
  );
}

// ==================== Exports ====================

export default AdminGuard;
