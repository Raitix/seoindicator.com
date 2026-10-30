"use client";

import * as React from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Zap, Clock, TrendingUp, BarChart3 } from "lucide-react";
import type { QuickInsights } from "@/lib/types/dashboard";
import { cn } from "@/lib/utils";

export interface QuickInsightsProps {
  insights: QuickInsights | null;
  loading?: boolean;
  error?: Error | null;
}

function Skeleton() {
  return (
    <Card className="border-slate-800 bg-slate-900/60" data-testid="insights-skeleton">
      <CardHeader>
        <div className="h-5 w-32 animate-pulse rounded bg-slate-800" />
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-10 w-full animate-pulse rounded bg-slate-800" />
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

interface InsightItemProps {
  label: string;
  value: string | number;
  icon: React.ReactNode;
  suffix?: string;
}

function InsightItem({ label, value, icon, suffix }: InsightItemProps) {
  return (
    <div className="flex items-center gap-3 p-3 rounded-lg bg-slate-950/60">
      <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
        {icon}
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-xs text-slate-500">{label}</p>
        <p className="text-sm font-medium text-white truncate">
          {value}
          {suffix && <span className="text-slate-400 ml-1">{suffix}</span>}
        </p>
      </div>
    </div>
  );
}

export function QuickInsightsCard({ insights, loading = false, error }: QuickInsightsProps) {
  if (loading) {
    return <Skeleton />;
  }

  if (error || !insights) {
    return (
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-white">
            <Zap className="h-5 w-5 text-amber-400" />
            Quick Insights
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="flex items-center gap-3 p-3 rounded-lg bg-slate-950/60">
                <p className="text-sm text-slate-500">N/A</p>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-white">
          <Zap className="h-5 w-5 text-amber-400" />
          Quick Insights
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <InsightItem
          label="Most Used Tool"
          value={insights.mostUsedToolDisplayName}
          icon={<BarChart3 className="h-4 w-4" />}
        />
        <InsightItem
          label="Avg. Analysis Time"
          value={insights.avgAnalysisTime}
          suffix="s"
          icon={<Clock className="h-4 w-4" />}
        />
        <InsightItem
          label="Success Rate"
          value={`${insights.successRate.toFixed(1)}%`}
          icon={<TrendingUp className="h-4 w-4" />}
        />
        {insights.totalKeywords !== undefined && (
          <InsightItem
            label="Total Keywords"
            value={insights.totalKeywords}
            icon={<BarChart3 className="h-4 w-4" />}
          />
        )}
        {insights.avgPosition !== undefined && (
          <InsightItem
            label="Avg. Position"
            value={insights.avgPosition.toFixed(1)}
            icon={<TrendingUp className="h-4 w-4" />}
          />
        )}
      </CardContent>
    </Card>
  );
}

export function QuickInsightsCardSkeleton() {
  return <Skeleton />;
}
