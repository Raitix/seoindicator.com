"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { DollarSign, TrendingUp, TrendingDown } from "lucide-react";
import type { CostSummary } from "@/lib/types/dashboard";
import { cn } from "@/lib/utils";

export interface CostCardProps {
  data: CostSummary | null;
  loading?: boolean;
  error?: Error | null;
}

function Skeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60" data-testid="cost-skeleton">
      <CardHeader>
        <div className="h-5 w-32 animate-pulse rounded bg-slate-800" />
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="h-10 w-24 animate-pulse rounded bg-slate-800" />
        <div className="h-6 w-20 animate-pulse rounded bg-slate-800" />
      </CardContent>
    </Card>
  );
}

export function CostSummaryCard({ data, loading = false, error }: CostCardProps) {
  if (loading) {
    return <Skeleton />;
  }

  if (error) {
    return (
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="text-white">Cost Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-400">Unable to load cost data</p>
        </CardContent>
      </Card>
    );
  }

  if (!data) {
    return (
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="text-white">Cost Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-400">N/A</p>
        </CardContent>
      </Card>
    );
  }

  const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: currency,
    }).format(amount);
  };

  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-white">
          <DollarSign className="h-5 w-5 text-emerald-400" />
          Cost Summary
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div>
          <p className="text-sm text-slate-400">Total Cost</p>
          <p className="text-3xl font-bold text-white">
            {formatCurrency(data.totalCost, data.currency)}
          </p>
        </div>
        <div className="flex items-center gap-4">
          <div>
            <p className="text-xs text-slate-500">This Month</p>
            <p className="text-lg font-semibold text-emerald-300">
              {formatCurrency(data.thisMonthCost, data.currency)}
            </p>
          </div>
          {data.trend && (
            <div
              className={cn(
                "flex items-center gap-1 text-xs",
                data.trend === "up" ? "text-rose-400" : "text-emerald-400"
              )}
              data-testid="cost-trend"
            >
              {data.trend === "up" ? (
                <TrendingUp className="h-3 w-3" />
              ) : (
                <TrendingDown className="h-3 w-3" />
              )}
              <span>{data.percentChange ? `${data.percentChange}%` : ""}</span>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export function CostSummaryCardSkeleton() {
  return <Skeleton />;
}
