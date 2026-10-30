/**
 * RecoveryComparisonCard Component
 * Displays before/after metrics for content decay recovery
 * 
 * @see TASK-36-12 for implementation requirements
 */

"use client";

import * as React from "react";
import {
  TrendingUp,
  TrendingDown,
  Minus,
  MousePointerClick,
  Eye,
  BarChart2,
  Target,
  Sparkles,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { RecoveryComparison, RecoveryMetric } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface RecoveryComparisonCardProps {
  /** Recovery comparison data */
  comparison: RecoveryComparison | null;
  /** Show loading state */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
  /** Render in compact mode */
  compact?: boolean;
}

function formatNumber(num: number): string {
  if (Math.abs(num) >= 1000000) {
    return (num / 1000000).toFixed(1) + "M";
  }
  if (Math.abs(num) >= 1000) {
    return (num / 1000).toFixed(1) + "K";
  }
  return num.toFixed(num % 1 === 0 ? 0 : 1);
}

function formatPercent(num: number): string {
  return (num * 100).toFixed(1) + "%";
}

function getDeltaColor(delta: number, isPosition = false): string {
  // For position, negative delta (going down in position number) is good
  const isPositive = isPosition ? delta < 0 : delta > 0;
  if (delta === 0) return "text-gray-500";
  return isPositive ? "text-green-600" : "text-red-600";
}

interface MetricRowProps {
  label: string;
  metric: RecoveryMetric;
  icon: React.ReactNode;
  isPosition?: boolean;
  format?: "number" | "percent" | "position";
  compact?: boolean;
}

function MetricRow({
  label,
  metric,
  icon,
  isPosition = false,
  format = "number",
  compact = false,
}: MetricRowProps) {
  const deltaColor = getDeltaColor(metric.delta, isPosition);
  const showUpIcon = isPosition ? metric.delta < 0 : metric.delta > 0;
  const TrendIcon = showUpIcon ? TrendingUp : metric.delta < 0 ? TrendingDown : Minus;

  const formatValue = (val: number): string => {
    if (format === "percent") return formatPercent(val);
    return formatNumber(val);
  };

  const formatDelta = (val: number): string => {
    if (format === "percent") return formatPercent(val);
    return formatNumber(val);
  };

  if (compact) {
    return (
      <div
        data-testid={`metric-${label.toLowerCase().replace(/\s/g, "-")}`}
        className="flex items-center justify-between py-1.5"
      >
        <div className="flex items-center gap-2 text-sm">
          {icon}
          <span data-testid="metric-label">{label}</span>
        </div>
        <div className="flex items-center gap-2">
          <span data-testid="metric-after" className="font-medium">
            {formatValue(metric.after)}
          </span>
          <span
            data-testid="metric-delta"
            data-color={deltaColor.includes("green") ? "green" : deltaColor.includes("red") ? "red" : "gray"}
            className={cn("text-xs font-medium", deltaColor)}
          >
            {metric.delta > 0 ? "+" : ""}
            {formatDelta(metric.delta)}
            {isPosition && " positions"}
            <TrendIcon className="inline-block ml-1 h-3 w-3" />
          </span>
        </div>
      </div>
    );
  }

  return (
    <div
      data-testid={`metric-${label.toLowerCase().replace(/\s/g, "-")}`}
      className="grid grid-cols-4 items-center gap-2 py-2 text-sm"
    >
      <div className="flex items-center gap-2">
        {icon}
        <span data-testid="metric-label">{label}</span>
      </div>
      <span data-testid="metric-before" className="text-muted-foreground text-right">
        {formatValue(metric.before)}
      </span>
      <span data-testid="metric-after" className="font-medium text-right">
        {formatValue(metric.after)}
      </span>
      <span
        data-testid="metric-delta"
        data-color={deltaColor.includes("green") ? "green" : deltaColor.includes("red") ? "red" : "gray"}
        className={cn("text-right font-medium", deltaColor)}
      >
        {metric.delta > 0 ? "+" : ""}
        {formatDelta(metric.delta)}
        {isPosition && " positions"}
        <TrendIcon className="inline-block ml-1 h-3 w-3" />
      </span>
      {metric.deltaPercent !== null && !compact && (
        <span
          data-testid="metric-delta-percent"
          className="text-xs text-muted-foreground"
        >
          ({metric.deltaPercent > 0 ? "+" : ""}
          {metric.deltaPercent.toFixed(1)}%)
        </span>
      )}
    </div>
  );
}

export function RecoveryComparisonCard({
  comparison,
  isLoading = false,
  className,
  compact = false,
}: RecoveryComparisonCardProps) {
  if (isLoading) {
    return (
      <div data-testid="comparison-loading" className={cn("space-y-3", className)}>
        <Skeleton data-testid="skeleton-metric" className="h-8 w-full" />
        <Skeleton data-testid="skeleton-metric" className="h-8 w-full" />
        <Skeleton data-testid="skeleton-metric" className="h-8 w-full" />
      </div>
    );
  }

  if (!comparison) {
    return (
      <div
        data-testid="comparison-empty"
        className={cn("text-center text-muted-foreground py-8", className)}
      >
        No recovery data yet
      </div>
    );
  }

  return (
    <Card
      data-testid="recovery-comparison-card"
      className={className}
      role="region"
      aria-label="Recovery comparison"
    >
      <CardHeader className="pb-2">
        <CardTitle data-testid="card-title" className="text-lg">
          Recovery Comparison
        </CardTitle>
      </CardHeader>
      <CardContent>
        {!compact && (
          <div data-testid="headers" className="grid grid-cols-4 gap-2 text-xs text-muted-foreground pb-2 border-b">
            <span>Metric</span>
            <span className="text-right">Before</span>
            <span className="text-right">After</span>
            <span className="text-right">Change</span>
          </div>
        )}
        <div data-testid="metrics-container" className="divide-y">
          <MetricRow
            label="Clicks"
            metric={comparison.clicks}
            icon={<MousePointerClick className="h-4 w-4 text-muted-foreground" />}
            compact={compact}
          />
          <MetricRow
            label="Impressions"
            metric={comparison.impressions}
            icon={<Eye className="h-4 w-4 text-muted-foreground" />}
            compact={compact}
          />
          <MetricRow
            label="CTR"
            metric={comparison.ctr}
            icon={<BarChart2 className="h-4 w-4 text-muted-foreground" />}
            format="percent"
            compact={compact}
          />
          <MetricRow
            label="Position"
            metric={comparison.position}
            icon={<Target className="h-4 w-4 text-muted-foreground" />}
            isPosition
            format="position"
            compact={compact}
          />
          {comparison.aiVisibility && (
            <MetricRow
              label="AI Visibility"
              metric={comparison.aiVisibility}
              icon={<Sparkles className="h-4 w-4 text-purple-500" />}
              format="percent"
              compact={compact}
            />
          )}
        </div>
      </CardContent>
    </Card>
  );
}
