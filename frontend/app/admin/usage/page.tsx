"use client";

import { useState, useMemo } from "react";
import { AdminLayout, AdminGuard } from "@/components/admin";
import {
  UsageChart,
  TokenDistributionChart,
  TopEndpointsTable,
  DateRangeSelector,
} from "@/components/admin";
import type { PresetRange } from "@/components/admin";
import type { UsageDataPoint, TokenDistributionData, EndpointData } from "@/components/admin";

// ============================================================================
// Mock Data (will be replaced with API data)
// ============================================================================

const mockUsageData: UsageDataPoint[] = [
  { date: "2026-03-01", requests: 120, tokens: 5000 },
  { date: "2026-03-02", requests: 150, tokens: 6200 },
  { date: "2026-03-03", requests: 180, tokens: 7500 },
  { date: "2026-03-04", requests: 95, tokens: 3800 },
  { date: "2026-03-05", requests: 200, tokens: 8100 },
  { date: "2026-03-06", requests: 175, tokens: 7000 },
  { date: "2026-03-07", requests: 210, tokens: 8400 },
];

const mockTokenDistribution: TokenDistributionData[] = [
  { model: "gpt-4", tokens: 45000, percentage: 45 },
  { model: "gpt-3.5-turbo", tokens: 35000, percentage: 35 },
  { model: "claude-3-opus", tokens: 15000, percentage: 15 },
  { model: "other", tokens: 5000, percentage: 5 },
];

const mockTopEndpoints: EndpointData[] = [
  { endpoint: "/api/v1/tools/meta-tag-analyzer", requests: 1250, percentage: 25, avgResponseTime: 150, lastUsed: "2026-03-07T10:30:00Z" },
  { endpoint: "/api/v1/tools/keyword-density", requests: 980, percentage: 19.6, avgResponseTime: 120, lastUsed: "2026-03-07T11:15:00Z" },
  { endpoint: "/api/v1/tools/pwa-analyzer", requests: 750, percentage: 15, avgResponseTime: 200, lastUsed: "2026-03-07T09:45:00Z" },
  { endpoint: "/api/v1/analysis/start", requests: 500, percentage: 10, avgResponseTime: 80, lastUsed: "2026-03-07T12:00:00Z" },
  { endpoint: "/api/v1/suggestions/generate", requests: 450, percentage: 9, avgResponseTime: 350, lastUsed: "2026-03-07T11:30:00Z" },
];

// ============================================================================
// Page Component
// ============================================================================

/**
 * Admin Usage Page
 * Usage statistics and analytics
 */
export default function AdminUsagePage() {
  const [dateRange, setDateRange] = useState<PresetRange>("7d");
  const [customRange, setCustomRange] = useState<{ start: Date; end: Date }>();

  // Handle date range change
  const handleDateRangeChange = (
    range: PresetRange,
    custom?: { start: Date; end: Date }
  ) => {
    setDateRange(range);
    setCustomRange(custom);
  };

  // Calculate totals for metric cards
  const totalRequests = useMemo(
    () => mockUsageData.reduce((sum, d) => sum + d.requests, 0),
    [mockUsageData]
  );
  const totalTokens = useMemo(
    () => mockUsageData.reduce((sum, d) => sum + (d.tokens || 0), 0),
    [mockUsageData]
  );

  return (
    <AdminGuard>
      <AdminLayout
        title="Usage Statistics"
        breadcrumbs={[
          { label: "Admin", href: "/admin" },
          { label: "Usage" },
        ]}
      >
        <div data-testid="page-usage" className="space-y-6">
          {/* Header with Date Range Selector */}
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h1 className="text-2xl font-bold text-white">Usage Statistics</h1>
            <DateRangeSelector
              value={dateRange}
              customRange={customRange}
              onChange={handleDateRangeChange}
            />
          </div>

          {/* Summary Cards */}
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-lg border border-slate-700 bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Total Requests</div>
              <div className="mt-1 text-2xl font-bold text-white">
                {totalRequests.toLocaleString()}
              </div>
            </div>
            <div className="rounded-lg border border-slate-700 bg-slate-800 p-4">
              <div className="text-sm text-slate-400">Total Tokens</div>
              <div className="mt-1 text-2xl font-bold text-white">
                {totalTokens.toLocaleString()}
              </div>
            </div>
            <div className="rounded-lg border border-slate-700 bg-slate-800 p-4">
              <div className="text-sm text-slate-400">API Endpoints</div>
              <div className="mt-1 text-2xl font-bold text-white">
                {mockTopEndpoints.length}
              </div>
            </div>
            <div className="rounded-lg border border-slate-700 bg-slate-800 p-4">
              <div className="text-sm text-slate-400">AI Models</div>
              <div className="mt-1 text-2xl font-bold text-white">
                {mockTokenDistribution.length}
              </div>
            </div>
          </div>

          {/* Usage Charts */}
          <div className="grid gap-6 lg:grid-cols-2">
            <UsageChart
              data={mockUsageData}
              days={dateRange === "custom" ? 30 : (parseInt(dateRange) as 7 | 14 | 30 | 90)}
              showTokens={true}
            />
            <TokenDistributionChart
              data={mockTokenDistribution}
              chartType="donut"
            />
          </div>

          {/* Top Endpoints Table */}
          <TopEndpointsTable data={mockTopEndpoints} maxRows={10} />
        </div>
      </AdminLayout>
    </AdminGuard>
  );
}
