"use client";

import { Eye, MessageSquare, MousePointerClick, Search, Star, Target } from "lucide-react";

import { TrendIndicator } from "@/components/admin/TrendIndicator";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatCompactNumber, formatRating } from "@/lib/location-dashboard";
import type { QuickStatsSnapshot } from "@/lib/types/location-dashboard";

export interface QuickStatsCardsProps {
  stats: QuickStatsSnapshot;
  isLoading?: boolean;
  className?: string;
}

interface StatConfig {
  key: keyof QuickStatsSnapshot;
  label: string;
  helper: string;
  icon: React.ComponentType<{ className?: string }>;
  accent: string;
  format: (value: number | null | undefined) => string;
  trendKey?: keyof QuickStatsSnapshot;
}

const statConfigs: StatConfig[] = [
  {
    key: "views",
    label: "Views",
    helper: "GBP profile visibility",
    icon: Eye,
    accent: "text-sky-300",
    format: formatCompactNumber,
    trendKey: "viewTrend",
  },
  {
    key: "searches",
    label: "Searches",
    helper: "Discovery demand",
    icon: Search,
    accent: "text-cyan-300",
    format: formatCompactNumber,
    trendKey: "searchTrend",
  },
  {
    key: "actions",
    label: "Actions",
    helper: "Calls, clicks, and directions",
    icon: MousePointerClick,
    accent: "text-emerald-300",
    format: formatCompactNumber,
    trendKey: "actionsTrend",
  },
  {
    key: "rating",
    label: "Rating",
    helper: "Average review score",
    icon: Star,
    accent: "text-amber-300",
    format: (value) => {
      const rating = formatRating(value);
      return rating === "—" ? rating : `${rating} ★`;
    },
  },
  {
    key: "reviewCount",
    label: "Reviews",
    helper: "Total public reviews",
    icon: MessageSquare,
    accent: "text-violet-300",
    format: formatCompactNumber,
  },
  {
    key: "keywordsInPack",
    label: "In Pack",
    helper: "Keywords inside local pack",
    icon: Target,
    accent: "text-fuchsia-300",
    format: formatCompactNumber,
  },
];

export function QuickStatsCards({ stats, isLoading = false, className }: QuickStatsCardsProps) {
  return (
    <div className={cn("grid gap-4 sm:grid-cols-2 xl:grid-cols-3", className)}>
      {statConfigs.map((item) => {
        const Icon = item.icon;
        const trendValue = item.trendKey ? stats[item.trendKey] : null;

        return (
          <Card key={item.key} className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10">
            <CardContent className="px-5 py-4">
              {isLoading ? (
                <div className="space-y-3">
                  <Skeleton className="h-4 w-24 bg-slate-800" />
                  <Skeleton className="h-8 w-28 bg-slate-800" />
                  <Skeleton className="h-4 w-32 bg-slate-800" />
                </div>
              ) : (
                <div className="space-y-3">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <div className="text-sm text-slate-400">{item.label}</div>
                      <div className="mt-1 text-2xl font-semibold text-white">{item.format(stats[item.key] as number | null | undefined)}</div>
                    </div>
                    <div className="rounded-2xl border border-slate-800 bg-slate-900/80 p-3">
                      <Icon className={cn("h-5 w-5", item.accent)} aria-hidden="true" />
                    </div>
                  </div>
                  <div className="flex items-center justify-between gap-3 text-xs text-slate-500">
                    <span>{item.helper}</span>
                    {typeof trendValue === "number" ? <TrendIndicator value={trendValue} /> : null}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}

export default QuickStatsCards;
