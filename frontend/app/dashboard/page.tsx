"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { RefreshCw, BarChart3, Clock, TrendingUp, Activity } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { useDashboard } from "@/lib/hooks/useDashboard";
import { clearAuthStorage } from "@/lib/api/auth";
import { Button } from "@/components/ui/button";
import {
  StatsCard,
  CostSummaryCard,
  RecentAnalysesTable,
  QuickInsightsCard,
  QuickActionsCard,
  WelcomeCard,
  DashboardSkeleton,
  AuthLoadingState,
} from "@/components/dashboard";

// ==================== Auth Error Detection ====================

/**
 * Detects if an error is an authentication error (401)
 * Handles multiple error format variations from different API clients
 */
function isAuthError(error: unknown): boolean {
  if (!error) return false;

  // Check for error.status property
  if (typeof error === "object" && error !== null) {
    const err = error as Record<string, unknown>;

    // Direct status property (number or string)
    if (err.status === 401 || err.status === "401") return true;

    // statusCode property
    if (err.statusCode === 401 || err.statusCode === "401") return true;

    // Nested response.status property
    if (
      typeof err.response === "object" &&
      err.response !== null &&
      (err.response as Record<string, unknown>).status === 401
    ) {
      return true;
    }

    // Check message for 401 or Unauthorized (only if status is not 500+)
    const message = typeof err.message === "string" ? err.message : "";
    const hasUnauthorizedInMessage =
      message.includes("401") || message.toLowerCase().includes("unauthorized");

    // Only treat as auth error if it's not a server error
    // (e.g., "Unauthorized access detected in logs" with status 500 is NOT an auth error)
    const status = err.status ?? err.statusCode;
    if (hasUnauthorizedInMessage && status !== 500 && status !== "500") {
      return true;
    }
  }

  // Check if error is an Error instance with 401 in message
  if (error instanceof Error) {
    const message = error.message.toLowerCase();
    if (message.includes("401") || message.includes("unauthorized")) {
      // Also check for explicit status property
      const errWithStatus = error as Error & { status?: number };
      if (errWithStatus.status === undefined || errWithStatus.status === 401) {
        return true;
      }
    }
  }

  return false;
}

// ==================== Dashboard Page ====================

export default function DashboardPage() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading: authLoading } = useAuth();
  const { data: dashboard, isLoading, error, refresh } = useDashboard();

  // Track if we've already processed an auth error to prevent re-renders
  const hasProcessedAuthError = React.useRef(false);

  // Redirect to login if not authenticated
  React.useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push("/login");
    }
  }, [authLoading, isAuthenticated, router]);

  // Handle row click for analysis details
  const handleAnalysisRowClick = (analysis: { id: number }) => {
    router.push(`/analyses/${analysis.id}`);
  };

  // Show auth loading state
  if (authLoading) {
    return (
      <div className="min-h-screen bg-slate-950" data-testid="auth-loading">
        <AuthLoadingState />
      </div>
    );
  }

  // Don't render if not authenticated (will redirect)
  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-slate-950" data-testid="auth-loading">
        <AuthLoadingState />
      </div>
    );
  }

  // Show loading skeleton while fetching data
  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <DashboardSkeleton />
        </div>
      </div>
    );
  }

  // Handle 401 auth errors - clear storage and redirect to login
  if (error && isAuthError(error) && !hasProcessedAuthError.current) {
    // Mark as processed to prevent re-renders from triggering multiple redirects
    hasProcessedAuthError.current = true;
    // Clear auth storage and redirect to login
    clearAuthStorage();
    router.push("/login");
    return null;
  }

  // Handle non-auth error state
  if (error) {
    return (
      <div className="min-h-screen bg-slate-950">
        <div className="container mx-auto px-4 py-10">
          <div className="text-center py-12">
            <p className="text-red-400 mb-4">Failed to load dashboard data</p>
            <Button onClick={refresh} variant="outline" className="border-slate-700 text-slate-200">
              <RefreshCw className="h-4 w-4 mr-2" />
              Retry
            </Button>
          </div>
        </div>
      </div>
    );
  }

  // Extract data with defaults
  const stats = dashboard?.stats ?? {
    totalAnalyses: 0,
    thisMonth: 0,
    thisWeek: 0,
    today: 0,
  };

  const costSummary = dashboard?.costSummary ?? null;
  const recentAnalyses = dashboard?.recentAnalyses ?? [];
  const quickInsights = dashboard?.quickInsights ?? null;

  // Prepare user data for welcome card
  const dashboardUser = user ? {
    id: user.id,
    name: user.name,
    email: user.email,
    avatar: user.avatar,
  } : null;

  return (
    <div className="min-h-screen bg-slate-950" data-testid="dashboard-container">
      <div className="container mx-auto px-4 py-10">
        {/* Header */}
        <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-semibold text-white">Dashboard</h1>
            <p className="text-sm text-slate-400">
              Overview of your SEO analysis activity
            </p>
          </div>
          <Button
            onClick={refresh}
            variant="outline"
            className="border-slate-700 text-slate-200"
          >
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>

        <div className="space-y-6">
          {/* Welcome Card */}
          <WelcomeCard
            user={dashboardUser}
            lastLoginAt={user?.lastLoginAt}
          />

          {/* Stats Grid */}
          <div
            className="grid gap-4 md:grid-cols-2 lg:grid-cols-4"
            data-testid="stats-grid"
          >
            <StatsCard
              label="Total Analyses"
              value={stats.totalAnalyses}
              icon={<BarChart3 className="h-5 w-5" />}
            />
            <StatsCard
              label="This Month"
              value={stats.thisMonth}
              icon={<TrendingUp className="h-5 w-5" />}
            />
            <StatsCard
              label="This Week"
              value={stats.thisWeek}
              icon={<Activity className="h-5 w-5" />}
            />
            <StatsCard
              label="Today"
              value={stats.today}
              icon={<Clock className="h-5 w-5" />}
            />
          </div>

          {/* Secondary Cards Grid */}
          <div className="grid gap-6 lg:grid-cols-3">
            <CostSummaryCard data={costSummary} />
            <QuickInsightsCard insights={quickInsights} />
            <QuickActionsCard />
          </div>

          {/* Recent Analyses */}
          <RecentAnalysesTable
            analyses={recentAnalyses}
            onRowClick={handleAnalysisRowClick}
          />

          {/* Last Updated */}
          {dashboard?.lastUpdated && (
            <p className="text-xs text-slate-500 text-right">
              Last updated: {new Date(dashboard.lastUpdated).toLocaleString()}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
