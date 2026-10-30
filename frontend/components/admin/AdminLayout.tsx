"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { AdminNav } from "./AdminNav";

// ==================== Types ====================

export interface Breadcrumb {
  label: string;
  href?: string;
}

export interface AdminLayoutProps {
  children: React.ReactNode;
  title?: string;
  breadcrumbs?: Breadcrumb[];
  loading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  className?: string;
}

// ==================== Loading Skeleton ====================

function LoadingSkeleton() {
  return (
    <div
      data-testid="loading-skeleton"
      role="status"
      aria-live="polite"
      className="animate-pulse space-y-4 p-6"
    >
      <div className="h-8 w-1/4 rounded bg-slate-700" />
      <div className="h-4 w-1/2 rounded bg-slate-700" />
      <div className="h-32 rounded bg-slate-700" />
      <div className="h-4 w-3/4 rounded bg-slate-700" />
    </div>
  );
}

// ==================== Error Display ====================

interface ErrorDisplayProps {
  message: string;
  onRetry?: () => void;
}

function ErrorDisplay({ message, onRetry }: ErrorDisplayProps) {
  return (
    <div
      data-testid="error-message"
      role="alert"
      className="flex flex-col items-center justify-center rounded-lg bg-red-900/20 p-8 text-center"
    >
      <div className="mb-4 text-5xl">⚠️</div>
      <h3 className="mb-2 text-lg font-semibold text-red-400">Error</h3>
      <p className="mb-4 text-slate-400">{message}</p>
      {onRetry && (
        <button
          data-testid="retry-button"
          onClick={onRetry}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          Retry
        </button>
      )}
    </div>
  );
}

// ==================== Admin Layout Component ====================

export function AdminLayout({
  children,
  title,
  breadcrumbs,
  loading = false,
  error = null,
  onRetry,
  className,
}: AdminLayoutProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = React.useState(false);

  const handleToggleSidebar = React.useCallback(() => {
    setSidebarCollapsed((prev) => !prev);
  }, []);

  return (
    <div
      data-testid="admin-layout"
      className={cn("flex min-h-screen bg-slate-950", className)}
    >
      {/* Sidebar */}
      <aside
        data-testid="admin-sidebar"
        className={cn(
          "flex flex-col border-r border-slate-800 bg-slate-900 transition-all duration-300",
          sidebarCollapsed ? "w-16" : "w-64"
        )}
      >
        {/* Sidebar Header */}
        <div
          data-testid="sidebar-header"
          className="border-b border-slate-700 p-4"
        >
          {!sidebarCollapsed && (
            <h1 className="text-xl font-bold text-white">Admin Dashboard</h1>
          )}
        </div>

        {/* Navigation */}
        <div className="flex-1 overflow-y-auto p-4">
          <AdminNav
            collapsed={sidebarCollapsed}
            onToggle={handleToggleSidebar}
          />
        </div>
      </aside>

      {/* Main Content */}
      <main
        data-testid="admin-main"
        role="main"
        className="flex-1 overflow-y-auto p-6"
      >
        {/* Header */}
        <header data-testid="admin-header" className="mb-6">
          {/* Breadcrumbs */}
          {breadcrumbs && breadcrumbs.length > 0 && (
            <nav
              data-testid="breadcrumbs"
              aria-label="Breadcrumb"
              className="mb-2 flex items-center gap-2 text-sm text-slate-400"
            >
              {breadcrumbs.map((crumb, index) => (
                <React.Fragment key={index}>
                  {crumb.href ? (
                    <a
                      href={crumb.href}
                      className="hover:text-white hover:underline"
                    >
                      {crumb.label}
                    </a>
                  ) : (
                    <span className="text-white">{crumb.label}</span>
                  )}
                  {index < breadcrumbs.length - 1 && (
                    <span className="text-slate-600">/</span>
                  )}
                </React.Fragment>
              ))}
            </nav>
          )}

          {/* Page Title */}
          {title && (
            <h1
              data-testid="page-title"
              className="text-2xl font-bold text-white"
            >
              {title}
            </h1>
          )}
        </header>

        {/* Content Area */}
        {loading ? (
          <LoadingSkeleton />
        ) : error ? (
          <ErrorDisplay message={error} onRetry={onRetry} />
        ) : (
          <div data-testid="admin-content">{children}</div>
        )}
      </main>
    </div>
  );
}

// ==================== Exports ====================

export default AdminLayout;
