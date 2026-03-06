/**
 * TokenDistributionChart Component
 * Task: TASK-27-16 - Usage Charts and Tables
 *
 * Pie/donut chart component displaying token usage distribution by model.
 * Uses Recharts for rendering with responsive and accessible design.
 */

"use client";

import React, { useState, useMemo, useCallback } from "react";
import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
  Legend,
} from "recharts";
import { RefreshCw, Download, PieChart as PieChartIcon } from "lucide-react";
import { cn } from "@/lib/utils";

// ============================================================================
// Types
// ============================================================================

export interface TokenDistributionData {
  model: string;
  tokens: number;
  percentage: number;
}

export interface TokenDistributionChartProps {
  /** Array of token distribution data */
  data: TokenDistributionData[];
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Chart type: pie or donut */
  chartType?: "pie" | "donut";
  /** Show legend */
  showLegend?: boolean;
  /** Retry callback */
  onRetry?: () => void;
  /** Additional CSS classes */
  className?: string;
  /** Accessible label */
  "aria-label"?: string;
}

// ============================================================================
// Constants
// ============================================================================

const COLORS = [
  "#3B82F6", // blue
  "#10B981", // emerald
  "#F59E0B", // amber
  "#EF4444", // red
  "#8B5CF6", // violet
  "#EC4899", // pink
  "#06B6D4", // cyan
  "#F97316", // orange
];

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Format tokens with K/M suffix
 */
export function formatTokens(value: number): string {
  if (value >= 1000000) {
    const millions = value / 1000000;
    return millions % 1 === 0 ? millions.toFixed(0) + "M" : millions.toFixed(1) + "M";
  }
  if (value >= 1000) {
    const thousands = value / 1000;
    return thousands % 1 === 0 ? thousands.toFixed(0) + "K" : thousands.toFixed(0) + "K";
  }
  return value.toString();
}

/**
 * Format tokens with commas
 */
function formatWithCommas(value: number): string {
  return value.toLocaleString();
}

/**
 * Calculate total tokens
 */
export function calculateTotal(data: TokenDistributionData[]): number {
  return data.reduce((sum, item) => sum + item.tokens, 0);
}

/**
 * Get color for model by index
 */
export function getModelColor(index: number): string {
  return COLORS[index % COLORS.length];
}

// ============================================================================
// Custom Components
// ============================================================================

interface CustomTooltipProps {
  active?: boolean;
  payload?: Array<{
    payload: TokenDistributionData;
    value: number;
  }>;
}

function CustomTooltip({ active, payload }: CustomTooltipProps) {
  if (!active || !payload || !payload[0]) return null;

  const data = payload[0].payload;

  return (
    <div className="rounded-lg border border-slate-700 bg-slate-900 p-3 shadow-lg">
      <p className="mb-1 text-sm font-medium text-white">{data.model}</p>
      <p className="text-sm text-slate-300">
        {formatWithCommas(data.tokens)} tokens
      </p>
      <p className="text-sm text-slate-400">{data.percentage.toFixed(1)}%</p>
    </div>
  );
}

interface CustomLegendProps {
  payload?: Array<{
    value: string;
    color: string;
    payload: { percentage: number; tokens: number };
  }>;
}

function CustomLegend({ payload }: CustomLegendProps) {
  if (!payload) return null;

  return (
    <div className="mt-4 flex flex-wrap justify-center gap-4">
      {payload.map((entry, index) => (
        <div key={index} className="flex items-center gap-2">
          <div
            className="h-3 w-3 rounded-full"
            style={{ backgroundColor: entry.color }}
          />
          <span className="text-sm text-slate-300">{entry.value}</span>
          <span className="text-sm text-slate-500">
            ({entry.payload.percentage.toFixed(1)}%)
          </span>
        </div>
      ))}
    </div>
  );
}

// ============================================================================
// Component
// ============================================================================

export function TokenDistributionChart({
  data,
  isLoading = false,
  error = null,
  chartType = "donut",
  showLegend = true,
  onRetry,
  className,
  "aria-label": ariaLabel = "Token distribution by model",
}: TokenDistributionChartProps) {
  const [isExporting, setIsExporting] = useState(false);

  // Calculate total tokens
  const totalTokens = useMemo(() => calculateTotal(data), [data]);

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
          <h3 className="text-lg font-semibold text-white">Token Distribution</h3>
        </div>
        <div className="flex h-64 items-center justify-center">
          <div className="h-48 w-48 animate-pulse rounded-full bg-slate-700" />
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
          <h3 className="text-lg font-semibold text-white">Token Distribution</h3>
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
          <h3 className="text-lg font-semibold text-white">Token Distribution</h3>
        </div>
        <div className="flex h-64 flex-col items-center justify-center gap-2 text-slate-400">
          <PieChartIcon className="h-8 w-8" />
          <p>No token data available</p>
        </div>
      </div>
    );
  }

  // Inner radius for donut chart
  const innerRadius = chartType === "donut" ? 60 : 0;

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
        <h3 className="text-lg font-semibold text-white">Token Distribution</h3>
        <button
          onClick={handleExport}
          disabled={isExporting}
          aria-label="Export token distribution"
          className="flex items-center gap-1 rounded-md bg-slate-700 px-3 py-1.5 text-sm text-slate-300 transition-colors hover:bg-slate-600 disabled:opacity-50"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      </div>

      {/* Chart */}
      <div className="relative h-64">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              cx="50%"
              cy="50%"
              innerRadius={innerRadius}
              outerRadius={80}
              dataKey="tokens"
              nameKey="model"
              paddingAngle={2}
            >
              {data.map((entry, index) => (
                <Cell
                  key={`cell-${index}`}
                  fill={getModelColor(index)}
                  stroke="#1E293B"
                  strokeWidth={2}
                />
              ))}
            </Pie>
            <Tooltip content={<CustomTooltip />} />
            {showLegend && <Legend content={<CustomLegend />} />}
          </PieChart>
        </ResponsiveContainer>

        {/* Center text for donut chart */}
        {chartType === "donut" && (
          <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
            <div className="text-2xl font-bold text-white">
              {formatTokens(totalTokens)}
            </div>
            <div className="text-sm text-slate-400">tokens</div>
          </div>
        )}
      </div>

      {/* Screen reader summary */}
      <div className="sr-only">
        {ariaLabel}. {data.length} models. Total tokens:{" "}
        {formatWithCommas(totalTokens)}. Top model: {data[0]?.model} with{" "}
        {data[0]?.percentage.toFixed(1)}%.
      </div>
    </div>
  );
}

export default TokenDistributionChart;
