/**
 * UsageChart Component
 * Task: TASK-27-16 - Usage Charts and Tables
 *
 * Line chart component displaying request trends over time.
 * Uses Recharts for rendering with responsive and accessible design.
 */

"use client";

import React, { useState, useMemo, useCallback } from "react";
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts";
import { RefreshCw, Download, TrendingUp } from "lucide-react";
import { cn } from "@/lib/utils";

// ============================================================================
// Types
// ============================================================================

export interface UsageDataPoint {
  date: string;
  requests: number;
  tokens?: number;
}

export interface UsageChartProps {
  /** Array of data points */
  data: UsageDataPoint[];
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Number of days displayed (affects formatting) */
  days?: 7 | 14 | 30 | 90;
  /** Show tokens line */
  showTokens?: boolean;
  /** Retry callback */
  onRetry?: () => void;
  /** Additional CSS classes */
  className?: string;
  /** Accessible label */
  "aria-label"?: string;
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Format date for X-axis labels
 */
export function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

/**
 * Format number with K/M suffix
 */
export function formatNumber(value: number): string {
  if (value >= 1000000) {
    const millions = value / 1000000;
    return millions % 1 === 0 ? millions.toFixed(0) + "M" : millions.toFixed(1) + "M";
  }
  if (value >= 1000) {
    const thousands = value / 1000;
    return thousands % 1 === 0 ? thousands.toFixed(0) + "K" : thousands.toFixed(1) + "K";
  }
  return value.toString();
}

/**
 * Format number with commas
 */
function formatWithCommas(value: number): string {
  return value.toLocaleString();
}

/**
 * Calculate Y-axis max value
 */
export function calculateYMax(
  data: UsageDataPoint[],
  key: "requests" | "tokens" = "requests"
): number {
  const values = data.map((d) => d[key] || 0);
  const max = Math.max(...values, 0);
  if (max === 0) return 10; // Default Y max when all values are 0
  // Round up to next nice number
  const magnitude = Math.pow(10, Math.floor(Math.log10(max)));
  return Math.ceil(max / magnitude) * magnitude;
}

// ============================================================================
// Custom Tooltip Component
// ============================================================================

interface CustomTooltipProps {
  active?: boolean;
  payload?: Array<{
    value: number;
    name: string;
    color: string;
  }>;
  label?: string;
  showTokens?: boolean;
}

function CustomTooltip({
  active,
  payload,
  label,
  showTokens,
}: CustomTooltipProps) {
  if (!active || !payload) return null;

  return (
    <div className="rounded-lg border border-slate-700 bg-slate-900 p-3 shadow-lg">
      <p className="mb-2 text-sm font-medium text-white">{label}</p>
      {payload.map((item, index) => (
        <p
          key={index}
          className="text-sm"
          style={{ color: item.color }}
        >
          {item.name === "requests"
            ? `${formatWithCommas(item.value)} requests`
            : `${formatWithCommas(item.value)} tokens`}
        </p>
      ))}
    </div>
  );
}

// ============================================================================
// Component
// ============================================================================

export function UsageChart({
  data,
  isLoading = false,
  error = null,
  days = 7,
  showTokens = false,
  onRetry,
  className,
  "aria-label": ariaLabel = "Usage trends chart",
}: UsageChartProps) {
  const [isExporting, setIsExporting] = useState(false);

  // Process data for chart
  const chartData = useMemo(() => {
    return data.map((point) => ({
      ...point,
      formattedDate: formatDate(point.date),
    }));
  }, [data]);

  // Calculate Y-axis domains
  const requestYMax = useMemo(
    () => calculateYMax(data, "requests"),
    [data]
  );
  const tokenYMax = useMemo(
    () => (showTokens ? calculateYMax(data, "tokens") : 0),
    [data, showTokens]
  );

  // Handle export
  const handleExport = useCallback(() => {
    setIsExporting(true);
    // Placeholder - future CSV export
    setTimeout(() => {
      setIsExporting(false);
    }, 500);
  }, []);

  // Loading state
  if (isLoading) {
    return (
      <div
        className={cn(
          "rounded-lg border border-slate-700 bg-slate-800 p-6",
          className
        )}
        aria-busy="true"
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Request Trends</h3>
        </div>
        <div className="flex h-64 items-center justify-center">
          <div className="h-full w-full animate-pulse rounded bg-slate-700" />
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        className={cn(
          "rounded-lg border border-red-700 bg-slate-800 p-6",
          className
        )}
        role="alert"
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Request Trends</h3>
        </div>
        <div className="flex h-64 flex-col items-center justify-center gap-4">
          <p className="text-red-400">{error}</p>
          {onRetry && (
            <button
              onClick={onRetry}
              className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
              <RefreshCw className="h-4 w-4" />
              Retry
            </button>
          )}
        </div>
      </div>
    );
  }

  // Empty state
  if (data.length === 0) {
    return (
      <div
        className={cn(
          "rounded-lg border border-slate-700 bg-slate-800 p-6",
          className
        )}
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-white">Request Trends</h3>
        </div>
        <div className="flex h-64 flex-col items-center justify-center gap-2 text-slate-400">
          <TrendingUp className="h-8 w-8" />
          <p>No data available</p>
        </div>
      </div>
    );
  }

  return (
    <div
      className={cn(
        "rounded-lg border border-slate-700 bg-slate-800 p-6",
        className
      )}
      role="figure"
      aria-label={ariaLabel}
    >
      {/* Header */}
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-lg font-semibold text-white">Request Trends</h3>
        <button
          onClick={handleExport}
          disabled={isExporting}
          aria-label="Export chart data"
          className="flex items-center gap-1 rounded-md bg-slate-700 px-3 py-1.5 text-sm text-slate-300 transition-colors hover:bg-slate-600 disabled:opacity-50"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      </div>

      {/* Chart */}
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart
            data={chartData}
            margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
            <XAxis
              dataKey="formattedDate"
              stroke="#9CA3AF"
              fontSize={12}
              tickLine={false}
            />
            <YAxis
              stroke="#9CA3AF"
              fontSize={12}
              tickLine={false}
              tickFormatter={formatNumber}
              domain={[0, requestYMax]}
            />
            <Tooltip content={<CustomTooltip showTokens={showTokens} />} />
            {showTokens && (
              <Legend
                wrapperStyle={{ paddingTop: "10px" }}
                formatter={(value) => (
                  <span className="text-slate-300">{value}</span>
                )}
              />
            )}
            <Line
              type="monotone"
              dataKey="requests"
              stroke="#3B82F6"
              strokeWidth={2}
              dot={{ fill: "#3B82F6", strokeWidth: 2, r: 4 }}
              activeDot={{ r: 6, strokeWidth: 0 }}
              name="requests"
            />
            {showTokens && (
              <Line
                type="monotone"
                dataKey="tokens"
                stroke="#10B981"
                strokeWidth={2}
                dot={{ fill: "#10B981", strokeWidth: 2, r: 4 }}
                activeDot={{ r: 6, strokeWidth: 0 }}
                name="tokens"
              />
            )}
          </LineChart>
        </ResponsiveContainer>
      </div>

      {/* Screen reader summary */}
      <div className="sr-only">
        {ariaLabel}. {data.length} data points from {data[0]?.date} to{" "}
        {data[data.length - 1]?.date}. Total requests:{" "}
        {formatWithCommas(data.reduce((sum, d) => sum + d.requests, 0))}.
      </div>
    </div>
  );
}

export default UsageChart;
