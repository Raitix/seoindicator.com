"use client";

import * as React from "react";
import { Card, CardContent } from "@/components/ui/card";
import { TrendingUp, TrendingDown, Minus } from "lucide-react";
import { cn } from "@/lib/utils";

export interface StatsCardProps {
  label: string;
  value: number;
  icon?: React.ReactNode;
  trend?: "up" | "down" | "stable";
  trendValue?: number;
  loading?: boolean;
  className?: string;
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

export function StatsCard({
  label,
  value,
  icon,
  trend,
  trendValue,
  loading = false,
  className,
}: StatsCardProps) {
  if (loading) {
    return <Skeleton />;
  }

  const trendColors = {
    up: "text-emerald-400",
    down: "text-rose-400",
    stable: "text-slate-400",
  };

  const TrendIcon = trend === "up" ? TrendingUp : trend === "down" ? TrendingDown : Minus;

  return (
    <Card
      className={cn("border-slate-800 bg-slate-900/60", className)}
      aria-label={label}
    >
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="space-y-1">
            <p className="text-sm text-slate-400">{label}</p>
            <p className="text-3xl font-bold text-white">
              {value.toLocaleString()}
            </p>
            {trend !== undefined && trendValue !== undefined && (
              <div className={cn("flex items-center gap-1 text-xs", trendColors[trend])}>
                <TrendIcon className="h-3 w-3" />
                <span>{trendValue > 0 ? "+" : ""}{trendValue}%</span>
              </div>
            )}
          </div>
          {icon && (
            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
              {icon}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export function StatsCardSkeleton() {
  return <Skeleton />;
}
