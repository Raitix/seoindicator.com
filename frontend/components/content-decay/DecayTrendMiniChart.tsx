/**
 * DecayTrendMiniChart Component
 * Sparkline showing previous vs current window for clicks and impressions
 * 
 * @see TASK-36-11 for implementation requirements
 */

import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import type { DecayTrendData } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface DecayTrendMiniChartProps {
  /** Trend data with previous and current window values */
  data: DecayTrendData | null | undefined;
  /** Chart width in pixels */
  width?: number;
  /** Chart height in pixels */
  height?: number;
  /** Show clicks trend line */
  showClicks?: boolean;
  /** Show impressions trend line */
  showImpressions?: boolean;
  /** Additional CSS classes */
  className?: string;
}

const DEFAULT_WIDTH = 120;
const DEFAULT_HEIGHT = 40;
const PADDING = 2;

function calculateTrend(
  previous: number[],
  current: number[]
): { percent: number; direction: "up" | "down" | "stable" } {
  if (previous.length === 0 || current.length === 0) {
    return { percent: 0, direction: "stable" };
  }

  const prevSum = previous.reduce((a, b) => a + b, 0);
  const currSum = current.reduce((a, b) => a + b, 0);

  if (prevSum === 0) {
    return { percent: currSum > 0 ? 100 : 0, direction: currSum > 0 ? "up" : "stable" };
  }

  const percent = ((currSum - prevSum) / prevSum) * 100;
  const direction = percent > 1 ? "up" : percent < -1 ? "down" : "stable";

  return { percent, direction };
}

function pointsToPolyline(
  values: number[],
  width: number,
  height: number,
  padding: number
): string {
  if (values.length === 0) return "";

  const min = Math.min(...values);
  const max = Math.max(...values);
  const range = max - min || 1;

  const innerWidth = width - padding * 2;
  const innerHeight = height - padding * 2;

  return values
    .map((v, i) => {
      const x = padding + (i / (values.length - 1 || 1)) * innerWidth;
      const y = padding + innerHeight - ((v - min) / range) * innerHeight;
      return `${x},${y}`;
    })
    .join(" ");
}

function formatNumber(num: number): string {
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
}

export function DecayTrendMiniChart({
  data,
  width = DEFAULT_WIDTH,
  height = DEFAULT_HEIGHT,
  showClicks = true,
  showImpressions = true,
  className,
}: DecayTrendMiniChartProps) {
  // Handle null/undefined/missing data
  // Empty arrays are still valid - render SVG with no polylines
  if (
    !data ||
    !data.previousWindow ||
    !data.currentWindow
  ) {
    return (
      <span className="text-muted-foreground text-sm" aria-label="No trend data available">
        —
      </span>
    );
  }

  // Check if there's any data to render
  const hasAnyData = 
    (data.previousWindow.clicks?.length ?? 0) > 0 ||
    (data.previousWindow.impressions?.length ?? 0) > 0 ||
    (data.currentWindow.clicks?.length ?? 0) > 0 ||
    (data.currentWindow.impressions?.length ?? 0) > 0;

  const clicksTrend = calculateTrend(
    data.previousWindow.clicks || [],
    data.currentWindow.clicks || []
  );
  
  const impressionsTrend = calculateTrend(
    data.previousWindow.impressions || [],
    data.currentWindow.impressions || []
  );

  const prevClicksSum = (data.previousWindow.clicks || []).reduce((a, b) => a + b, 0);
  const currClicksSum = (data.currentWindow.clicks || []).reduce((a, b) => a + b, 0);
  const prevImpressionsSum = (data.previousWindow.impressions || []).reduce((a, b) => a + b, 0);
  const currImpressionsSum = (data.currentWindow.impressions || []).reduce((a, b) => a + b, 0);

  const ariaLabel = `Trend chart: Clicks ${clicksTrend.percent > 0 ? "+" : ""}${clicksTrend.percent.toFixed(1)}% (${formatNumber(prevClicksSum)} → ${formatNumber(currClicksSum)}), Impressions ${impressionsTrend.percent > 0 ? "+" : ""}${impressionsTrend.percent.toFixed(1)}% (${formatNumber(prevImpressionsSum)} → ${formatNumber(currImpressionsSum)})`;

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <div className={cn("cursor-pointer", className)}>
            <svg
              width={width}
              height={height}
              viewBox={`0 0 ${width} ${height}`}
              role="img"
              aria-label={ariaLabel}
              className="overflow-visible"
            >
              {/* Previous window - gray, dashed */}
              {showImpressions && data.previousWindow.impressions && (
                <polyline
                  points={pointsToPolyline(
                    data.previousWindow.impressions,
                    width,
                    height,
                    PADDING
                  )}
                  fill="none"
                  stroke="#9ca3af"
                  strokeWidth={1.5}
                  strokeDasharray="3,3"
                  opacity={0.5}
                />
              )}
              {showClicks && data.previousWindow.clicks && (
                <polyline
                  points={pointsToPolyline(
                    data.previousWindow.clicks,
                    width,
                    height,
                    PADDING
                  )}
                  fill="none"
                  stroke="#9ca3af"
                  strokeWidth={1.5}
                  opacity={0.5}
                />
              )}

              {/* Current window - colored by trend */}
              {showImpressions && data.currentWindow.impressions && (
                <polyline
                  points={pointsToPolyline(
                    data.currentWindow.impressions,
                    width,
                    height,
                    PADDING
                  )}
                  fill="none"
                  stroke="#3b82f6"
                  strokeWidth={1.5}
                  strokeDasharray="3,3"
                />
              )}
              {showClicks && data.currentWindow.clicks && (
                <polyline
                  points={pointsToPolyline(
                    data.currentWindow.clicks,
                    width,
                    height,
                    PADDING
                  )}
                  fill="none"
                  stroke={
                    clicksTrend.direction === "down"
                      ? "#ef4444"
                      : clicksTrend.direction === "up"
                      ? "#22c55e"
                      : "#6b7280"
                  }
                  strokeWidth={1.5}
                />
              )}
            </svg>
          </div>
        </TooltipTrigger>
        <TooltipContent side="top" className="min-w-48">
          <div className="space-y-2 text-sm">
            <div className="font-medium">Trend Comparison</div>
            <div className="grid grid-cols-2 gap-x-4 gap-y-1">
              <span className="text-muted-foreground">Clicks:</span>
              <span className={cn(
                "font-medium",
                clicksTrend.direction === "down" && "text-red-500",
                clicksTrend.direction === "up" && "text-green-500"
              )}>
                {formatNumber(prevClicksSum)} → {formatNumber(currClicksSum)}
                {" "}
                <span className="text-xs">
                  ({clicksTrend.percent > 0 ? "+" : ""}{clicksTrend.percent.toFixed(1)}%)
                </span>
              </span>
              <span className="text-muted-foreground">Impressions:</span>
              <span className={cn(
                "font-medium",
                impressionsTrend.direction === "down" && "text-red-500",
                impressionsTrend.direction === "up" && "text-green-500"
              )}>
                {formatNumber(prevImpressionsSum)} → {formatNumber(currImpressionsSum)}
                {" "}
                <span className="text-xs">
                  ({impressionsTrend.percent > 0 ? "+" : ""}{impressionsTrend.percent.toFixed(1)}%)
                </span>
              </span>
            </div>
            <div className="flex gap-3 text-xs text-muted-foreground">
              <span className="flex items-center gap-1">
                <span className="h-0.5 w-3 bg-gray-400" />
                Previous
              </span>
              <span className="flex items-center gap-1">
                <span className="h-0.5 w-3 bg-blue-500" />
                Current
              </span>
            </div>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
