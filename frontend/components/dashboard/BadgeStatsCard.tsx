"use client";

import * as React from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Eye, MousePointerClick, TrendingUp, TrendingDown } from "lucide-react";
import { cn } from "@/lib/utils";

export interface BadgeStatsCardProps {
  /** Card title (e.g., "Total Impressions") */
  title: string;
  /** Value to display */
  value: string;
  /** Icon type: eye for impressions, click for clicks, ctr for CTR */
  icon: "eye" | "click" | "ctr";
  /** Percentage change from previous period (optional) */
  change?: number;
  /** Loading state */
  isLoading?: boolean;
  /** Custom class name */
  className?: string;
}

/**
 * Format number for display (e.g., 5600 -> "5.6K", 2500000 -> "2.5M")
 */
export function formatNumber(num: number): string {
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M`;
  }
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K`;
  }
  return num.toString();
}

/**
 * Get icon component based on type
 */
function getIcon(type: "eye" | "click" | "ctr") {
  switch (type) {
    case "eye":
      return <Eye className="h-5 w-5" />;
    case "click":
      return <MousePointerClick className="h-5 w-5" />;
    case "ctr":
      return <TrendingUp className="h-5 w-5" />;
  }
}

function Skeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="space-y-2">
            <div className="h-4 w-24 animate-pulse rounded bg-slate-800" />
            <div className="h-8 w-16 animate-pulse rounded bg-slate-800" />
          </div>
          <div className="h-10 w-10 animate-pulse rounded-lg bg-slate-800" />
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * BadgeStatsCard - Individual stat card for badge analytics dashboard
 *
 * Displays a metric with icon, value, and optional change indicator.
 *
 * @example
 * ```tsx
 * <BadgeStatsCard
 *   title="Total Impressions"
 *   value="5.6K"
 *   icon="eye"
 *   change={12.5}
 * />
 * ```
 */
export function BadgeStatsCard({
  title,
  value,
  icon,
  change,
  isLoading = false,
  className,
}: BadgeStatsCardProps) {
  if (isLoading) {
    return <Skeleton />;
  }

  const isPositive = change !== undefined && change >= 0;
  const TrendIcon = isPositive ? TrendingUp : TrendingDown;

  return (
    <Card
      className={cn("border-slate-800 bg-slate-900/60", className)}
      data-testid="stats-card"
      data-title={title}
    >
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="space-y-1">
            <div className="flex items-center gap-2" data-testid="stats-card-header">
              <span className="sr-only">{icon} icon</span>
              <p className="text-sm text-slate-400" data-testid="stats-card-title">
                {title}
              </p>
            </div>
            <p className="text-3xl font-bold text-white" data-testid="stats-card-value">
              {value}
            </p>
            {change !== undefined && (
              <div
                className={cn(
                  "flex items-center gap-1 text-xs",
                  isPositive ? "text-emerald-400" : "text-rose-400"
                )}
                data-testid="stats-card-change"
                data-positive={isPositive}
              >
                <TrendIcon className="h-3 w-3" />
                <span>
                  {isPositive ? "+" : ""}
                  {change}%
                </span>
              </div>
            )}
          </div>
          <div
            className={cn(
              "flex h-12 w-12 items-center justify-center rounded-lg",
              icon === "eye" && "bg-blue-500/20 text-blue-400",
              icon === "click" && "bg-emerald-500/20 text-emerald-400",
              icon === "ctr" && "bg-purple-500/20 text-purple-400"
            )}
          >
            {getIcon(icon)}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export function BadgeStatsCardSkeleton() {
  return <Skeleton />;
}
