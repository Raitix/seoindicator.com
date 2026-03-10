"use client";

import Link from "next/link";
import { BarChart3, ChevronRight, MousePointerClick, Search, Eye } from "lucide-react";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatNumber } from "@/lib/location-dashboard";
import type { GBPInsights } from "@/lib/types/location-dashboard";
import LocalSparkline from "@/components/local/LocalSparkline";

export interface InsightsSectionProps {
  locationId: string;
  insights: GBPInsights | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  linkHref: string;
  className?: string;
}

const metricCards = [
  {
    key: "views",
    label: "Views",
    icon: Eye,
    accent: "text-sky-300",
  },
  {
    key: "searches",
    label: "Searches",
    icon: Search,
    accent: "text-cyan-300",
  },
  {
    key: "actions",
    label: "Actions",
    icon: MousePointerClick,
    accent: "text-emerald-300",
  },
] as const;

const formatBreakdownLabel = (value: string) =>
  value
    .replace(/_/g, " ")
    .replace(/\b\w/g, (letter) => letter.toUpperCase());

export function InsightsSection({
  insights,
  isLoading = false,
  error,
  onRetry,
  linkHref,
  className,
}: InsightsSectionProps) {
  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="flex flex-row items-start justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
          <CardTitle className="flex items-center gap-2 text-white">
            <BarChart3 className="h-5 w-5 text-sky-300" aria-hidden="true" />
            GBP Insights
          </CardTitle>
          <p className="mt-2 text-sm text-slate-400">
            Views, searches, and customer actions for the latest {insights?.period ?? "7d"} window.
          </p>
        </div>

        <Button asChild variant="ghost" className="text-sky-300 hover:bg-slate-900 hover:text-sky-200">
          <Link href={linkHref}>
            View detailed insights
            <ChevronRight className="h-4 w-4" aria-hidden="true" />
          </Link>
        </Button>
      </CardHeader>

      <CardContent className="pt-6">
        {error ? (
          <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-5 text-red-50" role="alert">
            <p className="text-sm text-red-100/90">{error.message}</p>
            {onRetry ? (
              <Button type="button" variant="outline" className="mt-4" onClick={onRetry}>
                Try again
              </Button>
            ) : null}
          </div>
        ) : isLoading ? (
          <div className="grid gap-4 lg:grid-cols-3">
            {metricCards.map((metric) => (
              <div key={metric.key} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                <Skeleton className="h-5 w-20 bg-slate-800" />
                <Skeleton className="mt-3 h-8 w-24 bg-slate-800" />
                <Skeleton className="mt-4 h-16 w-full bg-slate-800" />
              </div>
            ))}
          </div>
        ) : insights ? (
          <div className="grid gap-4 lg:grid-cols-3">
            {metricCards.map((metric) => {
              const series = insights[metric.key];
              const Icon = metric.icon;
              const breakdownEntries = Object.entries(series.breakdown).slice(0, 3);

              return (
                <div key={metric.key} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="text-sm text-slate-400">{metric.label}</div>
                      <div className="mt-2 text-3xl font-semibold text-white">{formatNumber(series.total)}</div>
                    </div>
                    <div className="rounded-2xl border border-slate-800 bg-slate-950 p-2.5">
                      <Icon className={cn("h-5 w-5", metric.accent)} aria-hidden="true" />
                    </div>
                  </div>

                  <div className="mt-4">
                    <LocalSparkline label={metric.label} data={series.trend} />
                  </div>

                  <div className="mt-4 grid gap-2 text-xs text-slate-400">
                    {breakdownEntries.length ? (
                      breakdownEntries.map(([label, value]) => (
                        <div key={label} className="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                          <span>{formatBreakdownLabel(label)}</span>
                          <span className="font-medium text-slate-200">{formatNumber(value)}</span>
                        </div>
                      ))
                    ) : (
                      <div className="rounded-xl border border-dashed border-slate-800 px-3 py-3 text-slate-500">
                        No breakdown data available.
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-8 text-center text-sm text-slate-400">
            No GBP insights are available yet for this location.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default InsightsSection;
