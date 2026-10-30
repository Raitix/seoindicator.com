"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";
import { Loader2, BarChart3, Globe, ExternalLink } from "lucide-react";
import Link from "next/link";
import { BadgeStatsCard, formatNumber } from "./BadgeStatsCard";
import { cn } from "@/lib/utils";

// ============================================================
// Types
// ============================================================

export interface BadgeStats {
  totalImpressions: number;
  totalClicks: number;
  avgCTR: number;
  impressionsChange?: number;
  clicksChange?: number;
  ctrChange?: number;
}

export interface TimeSeriesPoint {
  date: string;
  impressions: number;
  clicks: number;
}

export interface TopReferrer {
  domain: string;
  impressions: number;
  clicks: number;
  ctr: number;
}

export interface TopAudit {
  auditId: string;
  url: string;
  hash: string;
  impressions: number;
  clicks: number;
  ctr: number;
  score: number;
}

export interface DateRange {
  start: Date;
  end: Date;
}

export type PresetRange = "7d" | "30d" | "90d" | "custom";

export interface BadgeAnalyticsDashboardProps {
  /** Site ID to filter analytics (optional) */
  siteId?: string;
  /** Badge statistics */
  stats?: BadgeStats;
  /** Time series data for impressions chart */
  impressionsData?: TimeSeriesPoint[];
  /** Time series data for clicks chart */
  clicksData?: TimeSeriesPoint[];
  /** Top referrer domains */
  topReferrers?: TopReferrer[];
  /** Top performing audits */
  topAudits?: TopAudit[];
  /** Selected date range preset */
  dateRange?: PresetRange;
  /** Custom date range when preset is "custom" */
  customDateRange?: DateRange;
  /** Callback when date range changes */
  onDateRangeChange?: (range: PresetRange, custom?: DateRange) => void;
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Custom class name */
  className?: string;
}

// ============================================================
// Helper Functions
// ============================================================

/**
 * Truncate URL for display
 */
function truncateUrl(url: string, maxLength: number = 30): string {
  if (url.length <= maxLength) return url;
  return url.substring(0, maxLength - 3) + "...";
}

/**
 * Format date for chart axis
 */
function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

/**
 * Custom tooltip for charts
 */
function CustomTooltip({ active, payload, label }: any) {
  if (active && payload && payload.length) {
    return (
      <div className="rounded-lg border border-slate-700 bg-slate-800 p-3 shadow-lg">
        <p className="text-xs text-slate-400">{label}</p>
        {payload.map((entry: any, index: number) => (
          <p key={index} className="text-sm font-medium text-white">
            {entry.name}: {formatNumber(entry.value)}
          </p>
        ))}
      </div>
    );
  }
  return null;
}

// ============================================================
// Sub-Components
// ============================================================

interface DateRangeSelectorProps {
  value: PresetRange;
  onChange: (range: PresetRange) => void;
  customDateRange?: DateRange;
}

function DateRangeSelector({ value, onChange, customDateRange }: DateRangeSelectorProps) {
  return (
    <div className="flex items-center gap-2" data-testid="date-range-selector">
      <select
        value={value}
        onChange={(e) => onChange(e.target.value as PresetRange)}
        className="rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        data-testid="date-range-select"
        aria-label="Select date range"
      >
        <option value="7d">Last 7 days</option>
        <option value="30d">Last 30 days</option>
        <option value="90d">Last 90 days</option>
        <option value="custom">Custom</option>
      </select>
      {value === "custom" && customDateRange && (
        <div className="flex items-center gap-2" data-testid="custom-date-range">
          <input
            type="date"
            value={customDateRange.start.toISOString().split("T")[0]}
            readOnly
            className="rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white"
            data-testid="start-date-input"
          />
          <span className="text-slate-400">to</span>
          <input
            type="date"
            value={customDateRange.end.toISOString().split("T")[0]}
            readOnly
            className="rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white"
            data-testid="end-date-input"
          />
        </div>
      )}
    </div>
  );
}

interface ChartCardProps {
  title: string;
  data: TimeSeriesPoint[];
  dataKey: "impressions" | "clicks";
  color: string;
}

function ChartCard({ title, data, dataKey, color }: ChartCardProps) {
  const chartData = data.map((d) => ({
    date: formatDate(d.date),
    [dataKey]: d[dataKey],
  }));

  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader className="pb-2">
        <CardTitle className="text-lg text-white">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div style={{ width: "100%", height: 200 }} data-testid={`${dataKey}-chart`} data-points={data.length}>
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
              <XAxis
                dataKey="date"
                stroke="#64748b"
                fontSize={12}
                tickLine={false}
              />
              <YAxis stroke="#64748b" fontSize={12} tickLine={false} />
              <Tooltip content={<CustomTooltip />} />
              <Line
                type="monotone"
                dataKey={dataKey}
                stroke={color}
                strokeWidth={2}
                dot={false}
                name={dataKey === "impressions" ? "Impressions" : "Clicks"}
              />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </CardContent>
    </Card>
  );
}

interface ReferrersTableProps {
  referrers: TopReferrer[];
}

function ReferrersTable({ referrers }: ReferrersTableProps) {
  if (referrers.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-8 text-slate-400">
        <Globe className="mb-2 h-8 w-8" />
        <p data-testid="no-referrers-message">No referrer data available</p>
      </div>
    );
  }

  // Sort by impressions descending
  const sorted = [...referrers].sort((a, b) => b.impressions - a.impressions);

  return (
    <div className="overflow-x-auto">
      <table className="w-full" data-testid="top-referrers-table">
        <thead>
          <tr className="border-b border-slate-700">
            <th className="px-4 py-3 text-left text-xs font-medium uppercase text-slate-400">
              Domain
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              Impressions
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              Clicks
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              CTR
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {sorted.map((ref, idx) => (
            <tr
              key={ref.domain}
              className="hover:bg-slate-800/50"
              data-testid={`referrer-row-${idx}`}
            >
              <td
                className="px-4 py-3 text-sm text-white"
                data-testid={`referrer-domain-${idx}`}
              >
                {ref.domain}
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`referrer-impressions-${idx}`}
              >
                {formatNumber(ref.impressions)}
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`referrer-clicks-${idx}`}
              >
                {formatNumber(ref.clicks)}
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`referrer-ctr-${idx}`}
              >
                {ref.ctr.toFixed(1)}%
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

interface AuditsTableProps {
  audits: TopAudit[];
}

function AuditsTable({ audits }: AuditsTableProps) {
  if (audits.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-8 text-slate-400">
        <BarChart3 className="mb-2 h-8 w-8" />
        <p data-testid="no-audits-message">No audit data available</p>
      </div>
    );
  }

  // Sort by impressions descending
  const sorted = [...audits].sort((a, b) => b.impressions - a.impressions);

  return (
    <div className="overflow-x-auto">
      <table className="w-full" data-testid="top-audits-table">
        <thead>
          <tr className="border-b border-slate-700">
            <th className="px-4 py-3 text-left text-xs font-medium uppercase text-slate-400">
              URL
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              Score
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              Impressions
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              Clicks
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">
              CTR
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {sorted.map((audit, idx) => (
            <tr
              key={audit.auditId}
              className="hover:bg-slate-800/50"
              data-testid={`audit-row-${idx}`}
            >
              <td
                className="max-w-[200px] truncate px-4 py-3 text-sm"
                data-testid={`audit-url-${idx}`}
              >
                <Link
                  href={`/audit/${audit.hash}`}
                  className="flex items-center gap-1 text-blue-400 hover:text-blue-300"
                  data-testid={`audit-link-${idx}`}
                >
                  {truncateUrl(audit.url)}
                  <ExternalLink className="h-3 w-3" />
                </Link>
              </td>
              <td
                className="px-4 py-3 text-right text-sm"
                data-testid={`audit-score-${idx}`}
              >
                <span
                  className={cn(
                    "rounded-full px-2 py-0.5 text-xs font-medium",
                    audit.score >= 80
                      ? "bg-emerald-500/20 text-emerald-400"
                      : audit.score >= 50
                        ? "bg-amber-500/20 text-amber-400"
                        : "bg-rose-500/20 text-rose-400"
                  )}
                >
                  {audit.score}
                </span>
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`audit-impressions-${idx}`}
              >
                {formatNumber(audit.impressions)}
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`audit-clicks-${idx}`}
              >
                {formatNumber(audit.clicks)}
              </td>
              <td
                className="px-4 py-3 text-right text-sm text-slate-300"
                data-testid={`audit-ctr-${idx}`}
              >
                {audit.ctr.toFixed(1)}%
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ============================================================
// Main Component
// ============================================================

/**
 * BadgeAnalyticsDashboard - Dashboard showing badge performance analytics
 *
 * Displays overview cards, line charts, and tables for badge impressions,
 * clicks, and click-through rates across public audits.
 *
 * @example
 * ```tsx
 * <BadgeAnalyticsDashboard
 *   stats={{
 *     totalImpressions: 5600,
 *     totalClicks: 234,
 *     avgCTR: 4.2,
 *     impressionsChange: 12.5
 *   }}
 *   impressionsData={timeSeriesData}
 *   topReferrers={referrers}
 *   topAudits={audits}
 * />
 * ```
 */
export function BadgeAnalyticsDashboard({
  stats,
  impressionsData = [],
  clicksData = [],
  topReferrers = [],
  topAudits = [],
  dateRange = "30d",
  customDateRange,
  onDateRangeChange,
  isLoading = false,
  error = null,
  className,
}: BadgeAnalyticsDashboardProps) {
  // Error state
  if (error) {
    return (
      <div
        className={cn("rounded-lg border border-rose-500/50 bg-rose-500/10 p-6", className)}
        data-testid="dashboard-error"
        role="alert"
      >
        <p className="text-rose-400" data-testid="error-message">
          {error}
        </p>
      </div>
    );
  }

  // Loading state
  if (isLoading) {
    return (
      <div
        className={cn("flex items-center justify-center py-12", className)}
        data-testid="dashboard-loading"
      >
        <Loader2 className="h-8 w-8 animate-spin text-slate-400" data-testid="loader-icon" />
      </div>
    );
  }

  // Check if we have any data
  const hasData = stats && (stats.totalImpressions > 0 || stats.totalClicks > 0);

  // Empty state
  if (!hasData) {
    return (
      <div
        className={cn("flex flex-col items-center justify-center py-12", className)}
        data-testid="badge-analytics-dashboard"
        role="region"
        aria-label="Badge Analytics Dashboard"
      >
        <div data-testid="empty-state">
          <BarChart3 className="mx-auto mb-4 h-12 w-12 text-slate-500" data-testid="bar-chart-icon" />
          <p className="text-center text-slate-400" data-testid="empty-state-message">
            No analytics data yet. Embed badges to start tracking performance.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div
      className={cn("space-y-6", className)}
      data-testid="badge-analytics-dashboard"
      role="region"
      aria-label="Badge Analytics Dashboard"
    >
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" data-testid="dashboard-header">
        <h1 className="text-2xl font-bold text-white" data-testid="dashboard-title">
          Badge Analytics
        </h1>
        {onDateRangeChange && (
          <DateRangeSelector
            value={dateRange}
            onChange={(range) => onDateRangeChange(range, customDateRange)}
            customDateRange={customDateRange}
          />
        )}
      </div>

      {/* Overview Cards */}
      <div
        className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        data-testid="overview-cards"
      >
        <BadgeStatsCard
          title="Total Impressions"
          value={formatNumber(stats?.totalImpressions ?? 0)}
          icon="eye"
          change={stats?.impressionsChange}
        />
        <BadgeStatsCard
          title="Total Clicks"
          value={formatNumber(stats?.totalClicks ?? 0)}
          icon="click"
          change={stats?.clicksChange}
        />
        <BadgeStatsCard
          title="Avg CTR"
          value={`${(stats?.avgCTR ?? 0).toFixed(1)}%`}
          icon="ctr"
          change={stats?.ctrChange}
        />
      </div>

      {/* Charts Section */}
      <div
        className="grid grid-cols-1 gap-4 lg:grid-cols-2"
        data-testid="charts-section"
      >
        <div data-testid="impressions-chart-container">
          <ChartCard
            title="Impressions Over Time"
            data={impressionsData}
            dataKey="impressions"
            color="#3b82f6"
          />
        </div>
        <div data-testid="clicks-chart-container">
          <ChartCard
            title="Clicks Over Time"
            data={clicksData.length > 0 ? clicksData : impressionsData}
            dataKey="clicks"
            color="#10b981"
          />
        </div>
      </div>

      {/* Tables Section */}
      <div
        className="grid grid-cols-1 gap-4 lg:grid-cols-2"
        data-testid="tables-section"
      >
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-lg text-white" data-testid="top-referrers-title">
              Top Referrers
            </CardTitle>
          </CardHeader>
          <CardContent data-testid="top-referrers-container">
            <ReferrersTable referrers={topReferrers} />
          </CardContent>
        </Card>

        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-lg text-white" data-testid="top-audits-title">
              Top Performing Audits
            </CardTitle>
          </CardHeader>
          <CardContent data-testid="top-audits-container">
            <AuditsTable audits={topAudits} />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default BadgeAnalyticsDashboard;
