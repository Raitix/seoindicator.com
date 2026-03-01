"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { RefreshCw, BarChart3, Clock, TrendingUp, Activity } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { useDashboard } from "@/lib/hooks/useDashboard";
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

// ==================== Dashboard Page ====================

export default function DashboardPage() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading: authLoading } = useAuth();
  const { data: dashboard, isLoading, error, refresh } = useDashboard();

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

  // Handle error state
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
    lastLoginAt: user.lastLoginAt,
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
