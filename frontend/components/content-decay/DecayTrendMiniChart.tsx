"use client";

import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";
import { DecayTrendData } from "@/lib/types/content-decay";

/**
 * Props for the DecayTrendMiniChart component
 */
export interface DecayTrendMiniChartProps {
  /** Trend data with previous and current window metrics */
  data: DecayTrendData | null;
  /** Optional additional CSS classes */
  className?: string;
  /** Width of the chart in pixels */
  width?: number;
  /** Height of the chart in pixels */
  height?: number;
  /** Whether to show clicks trend */
  showClicks?: boolean;
  /** Whether to show impressions trend */
  showImpressions?: boolean;
}

/**
 * Number formatter for tooltip values
 */
function formatNumber(value: number): string {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`;
  }
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`;
  }
  return value.toString();
}

/**
 * Calculate trend direction
 */
function getTrendDirection(
  previous: number[],
  current: number[]
): "down" | "up" | "neutral" {
  if (!previous.length || !current.length) return "neutral";
  
  const prevSum = previous.reduce((a, b) => a + b, 0);
  const currSum = current.reduce((a, b) => a + b, 0);
  
  const change = (currSum - prevSum) / Math.max(prevSum, 1);
  
  if (change < -0.05) return "down";
  if (change > 0.05) return "up";
  return "neutral";
}

/**
 * DecayTrendMiniChart displays a sparkline chart showing previous vs current
 * window trends for clicks and impressions.
 *
 * @example
 * ```tsx
 * const trendData = {
 *   previousWindow: {
 *     clicks: [120, 115, 110, 105],
 *     impressions: [1200, 1180, 1160, 1140]
 *   },
 *   currentWindow: {
 *     clicks: [60, 55, 50, 45],
 *     impressions: [900, 870, 840, 810]
 *   }
 * };
 *
 * <DecayTrendMiniChart data={trendData} />
 * ```
 */
export function DecayTrendMiniChart({
  data,
  className,
  width = 120,
  height = 40,
  showClicks = true,
  showImpressions = true,
}: DecayTrendMiniChartProps) {
  // Handle empty/null data
  if (!data || !data.previousWindow || !data.currentWindow) {
    return (
      <div
        className={cn(
          "flex items-center justify-center bg-muted/30 rounded",
          className
        )}
        style={{ width, height }}
        aria-label="No trend data available"
      >
        <span className="text-muted-foreground text-xs">—</span>
      </div>
    );
  }

  const { previousWindow, currentWindow } = data;

  // Get clicks and impressions data
  const clicksTrend = getTrendDirection(
    previousWindow.clicks,
    currentWindow.clicks
  );
  const impressionsTrend = getTrendDirection(
    previousWindow.impressions,
    currentWindow.impressions
  );

  // Determine overall trend color
  const overallTrend = clicksTrend === "down" || impressionsTrend === "down" 
    ? "down" 
    : clicksTrend === "up" || impressionsTrend === "up" 
    ? "up" 
    : "neutral";

  const trendColor = overallTrend === "down" 
    ? "#ef4444" 
    : overallTrend === "up" 
    ? "#22c55e" 
    : "#6b7280";

  // Prepare data for sparkline
  const allClicks = [...(previousWindow.clicks || []), ...(currentWindow.clicks || [])];
  const allImpressions = [...(previousWindow.impressions || []), ...(currentWindow.impressions || [])];
  
  const maxValue = Math.max(...allClicks, ...allImpressions, 1);
  const padding = 2;
  const chartWidth = width - padding * 2;
  const chartHeight = height - padding * 2;

  // Generate path for clicks
  const generatePath = (values: number[], color: string, offset: number = 0) => {
    if (!values.length) return null;
    
    const points = values.map((v, i) => {
      const x = padding + (i / Math.max(values.length - 1, 1)) * chartWidth;
      const y = padding + chartHeight - (v / maxValue) * chartHeight;
      return `${x},${y}`;
    });

    return (
      <polyline
        points={points.join(" ")}
        fill="none"
        stroke={color}
        strokeWidth={1.5}
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    );
  };

  // Calculate summary stats for tooltip
  const prevClicksSum = previousWindow.clicks?.reduce((a, b) => a + b, 0) || 0;
  const currClicksSum = currentWindow.clicks?.reduce((a, b) => a + b, 0) || 0;
  const prevImprSum = previousWindow.impressions?.reduce((a, b) => a + b, 0) || 0;
  const currImprSum = currentWindow.impressions?.reduce((a, b) => a + b, 0) || 0;

  const clicksChange = prevClicksSum > 0 
    ? ((currClicksSum - prevClicksSum) / prevClicksSum * 100).toFixed(1) 
    : "0";
  const imprChange = prevImprSum > 0 
    ? ((currImprSum - prevImprSum) / prevImprSum * 100).toFixed(1) 
    : "0";

  const chartContent = (
    <svg
      width={width}
      height={height}
      viewBox={`0 0 ${width} ${height}`}
      className={cn("overflow-visible", className)}
      role="img"
      aria-label={`Trend chart: Clicks ${clicksChange}%, Impressions ${imprChange}%`}
    >
      {/* Background separator line between previous and current window */}
      <line
        x1={width / 2}
        y1={padding}
        x2={width / 2}
        y2={height - padding}
        stroke="currentColor"
        strokeOpacity={0.2}
        strokeDasharray="2,2"
      />
      
      {/* Previous window area indicator */}
      <rect
        x={padding}
        y={padding}
        width={chartWidth / 2 - padding}
        height={chartHeight}
        fill="currentColor"
        fillOpacity={0.05}
      />
      
      {/* Clicks trend line (blue) */}
      {showClicks && generatePath(allClicks, "#3b82f6")}
      
      {/* Impressions trend line (gray) */}
      {showImpressions && generatePath(allImpressions, "#9ca3af")}
    </svg>
  );

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <div className="cursor-pointer">{chartContent}</div>
        </TooltipTrigger>
        <TooltipContent side="top" className="text-sm">
          <div className="space-y-2">
            <p className="font-medium text-xs text-muted-foreground">Trend Comparison</p>
            <div className="space-y-1">
              <div className="flex items-center justify-between gap-4">
                <span className="flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-blue-500" />
                  Clicks
                </span>
                <span className={cn(
                  "font-medium",
                  parseFloat(clicksChange) < 0 ? "text-red-500" : parseFloat(clicksChange) > 0 ? "text-green-500" : ""
                )}>
                  {clicksChange}%
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-gray-400" />
                  Impressions
                </span>
                <span className={cn(
                  "font-medium",
                  parseFloat(imprChange) < 0 ? "text-red-500" : parseFloat(imprChange) > 0 ? "text-green-500" : ""
                )}>
                  {imprChange}%
                </span>
              </div>
            </div>
            <div className="text-xs text-muted-foreground border-t pt-1 mt-1">
              <div>Previous: {formatNumber(prevClicksSum)} clicks, {formatNumber(prevImprSum)} impr</div>
              <div>Current: {formatNumber(currClicksSum)} clicks, {formatNumber(currImprSum)} impr</div>
            </div>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}

export default DecayTrendMiniChart;
