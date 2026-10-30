"use client";

import * as React from "react";
import {
  AdminLayout,
  AdminGuard,
  MetricGrid,
  HealthBadge,
  type MetricCardData,
} from "@/components/admin";
import {
  fetchAdminOverview,
  getMockOverviewData,
  adminKeys,
} from "@/lib/api/admin-stats";
import type { AdminOverviewData } from "@/lib/types/admin-stats";

// ==================== Types ====================

interface AdminOverviewState {
  data: AdminOverviewData | null;
  isLoading: boolean;
  error: string | null;
}

// ==================== Hook for Data Fetching ====================

function useAdminOverview(refreshInterval: number = 30000) {
  const [state, setState] = React.useState<AdminOverviewState>({
    data: null,
    isLoading: true,
    error: null,
  });

  const fetchData = React.useCallback(async () => {
    setState((prev) => ({ ...prev, isLoading: true, error: null }));

    try {
      const data = await fetchAdminOverview();
      setState({ data, isLoading: false, error: null });
    } catch (error) {
      // Fall back to mock data in development or on error
      console.warn("Failed to fetch admin overview, using mock data:", error);
      setState({
        data: getMockOverviewData(),
        isLoading: false,
        error: null,
      });
    }
  }, []);

  // Initial fetch
  React.useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Auto-refresh
  React.useEffect(() => {
    if (refreshInterval <= 0) return;

    const interval = setInterval(fetchData, refreshInterval);
    return () => clearInterval(interval);
  }, [fetchData, refreshInterval]);

  return {
    ...state,
    refetch: fetchData,
    lastUpdated: state.data?.lastUpdated
      ? new Date(state.data.lastUpdated)
      : null,
  };
}

// ==================== Helper: Build Metric Cards ====================

function buildMetricCards(data: AdminOverviewData | null): MetricCardData[] {
  if (!data) return [];

  return [
    {
      id: "total-users",
      title: "Total Users",
      value: data.users.total,
      trend: data.users.growth_rate,
      href: "/admin/users",
    },
    {
      id: "active-users",
      title: "Active Users Today",
      value: data.users.active_today,
      trend: null,
      href: "/admin/users?filter=active",
    },
    {
      id: "total-requests",
      title: "Total Requests (24h)",
      value: data.usage.requests_24h,
      trend: data.usage.trend,
      href: "/admin/usage",
    },
    {
      id: "revenue",
      title: "Revenue This Month",
      value: data.credits.revenue_month,
      trend: data.credits.trend,
      format: "currency",
      href: "/admin/credits",
    },
  ];
}

// ==================== Page Component ====================

export default function AdminOverviewPage() {
  const { data, isLoading, error, refetch, lastUpdated } = useAdminOverview(30000);

  const metricCards = buildMetricCards(data);
  const healthStatus = data?.health?.status || "unknown";

  return (
    <AdminGuard>
      <AdminLayout
        title="Dashboard Overview"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Overview" },
        ]}
      >
        <div data-testid="page-overview" className="space-y-6">
          {/* Page Header */}
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-white">Dashboard Overview</h1>
              <p className="text-slate-400">
                Monitor key metrics and system health
              </p>
            </div>
          </div>

          {/* Metric Cards Grid */}
          <MetricGrid
            cards={metricCards}
            isLoading={isLoading}
            error={error}
            onRetry={refetch}
            onRefresh={refetch}
            lastUpdated={lastUpdated}
            refreshInterval={30000}
          />

          {/* System Health Card */}
          <div className="rounded-lg bg-slate-800 p-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-white">System Health</h2>
                <p className="text-sm text-slate-400">
                  Current system status and availability
                </p>
              </div>
              <HealthBadge
                status={healthStatus}
                pulse={healthStatus === "degraded"}
                onClick={() => (window.location.href = "/admin/health")}
              />
            </div>

            {/* Health Details */}
            {data?.health?.checks && (
              <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                {Object.entries(data.health.checks).map(([name, status]) => (
                  <div
                    key={name}
                    className="flex items-center justify-between rounded-lg bg-slate-700/50 px-4 py-2"
                  >
                    <span className="text-sm text-slate-300 capitalize">
                      {name}
                    </span>
                    <HealthBadge
                      status={
                        typeof status === "string" ? status : status.status
                      }
                      size="sm"
                      showIcon={true}
                    />
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Quick Actions */}
          <div className="rounded-lg bg-slate-800 p-6">
            <h2 className="mb-4 text-lg font-semibold text-white">Quick Actions</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <a
                href="/admin/users"
                className="flex items-center gap-3 rounded-lg bg-slate-700/50 p-4 text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
              >
                <span className="text-2xl">👥</span>
                <span>Manage Users</span>
              </a>
              <a
                href="/admin/usage"
                className="flex items-center gap-3 rounded-lg bg-slate-700/50 p-4 text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
              >
                <span className="text-2xl">📊</span>
                <span>View Usage</span>
              </a>
              <a
                href="/admin/credits"
                className="flex items-center gap-3 rounded-lg bg-slate-700/50 p-4 text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
              >
                <span className="text-2xl">💳</span>
                <span>Credits & Billing</span>
              </a>
              <a
                href="/admin/health"
                className="flex items-center gap-3 rounded-lg bg-slate-700/50 p-4 text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
              >
                <span className="text-2xl">🏥</span>
                <span>System Health</span>
              </a>
            </div>
          </div>
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
