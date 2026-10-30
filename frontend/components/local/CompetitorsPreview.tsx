"use client";

import { Star, Users } from "lucide-react";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatNumber, formatRating } from "@/lib/location-dashboard";
import type { CompetitorPreview as CompetitorPreviewItem } from "@/lib/types/location-dashboard";

export interface CompetitorsPreviewProps {
  competitors: CompetitorPreviewItem[] | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  className?: string;
}

export function CompetitorsPreview({
  competitors,
  isLoading = false,
  error,
  className,
}: CompetitorsPreviewProps) {
  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="border-b border-slate-800/80 pb-5">
        <CardTitle className="flex items-center gap-2 text-white">
          <Users className="h-5 w-5 text-fuchsia-300" aria-hidden="true" />
          Competitors
        </CardTitle>
        <p className="mt-2 text-sm text-slate-400">Top businesses currently showing alongside this location in the local pack.</p>
      </CardHeader>

      <CardContent className="space-y-3 pt-6">
        {error ? (
          <div className="rounded-2xl border border-red-500/20 bg-red-500/10 p-5 text-sm text-red-100/90" role="alert">
            {error.message}
          </div>
        ) : isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-20 w-full bg-slate-800" />
            <Skeleton className="h-20 w-full bg-slate-800" />
            <Skeleton className="h-20 w-full bg-slate-800" />
          </div>
        ) : competitors?.length ? (
          competitors.map((competitor) => (
            <div key={competitor.name} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="font-medium text-white">{competitor.name}</div>
                  <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-400">
                    <span className="inline-flex items-center gap-1.5">
                      <Star className="h-4 w-4 fill-amber-300 text-amber-300" aria-hidden="true" />
                      {formatRating(competitor.rating)}
                    </span>
                    <span>{formatNumber(competitor.review_count)} reviews</span>
                  </div>
                </div>
                <span className="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs font-medium text-slate-300">
                  #{competitor.position}
                </span>
              </div>
            </div>
          ))
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-8 text-center text-sm text-slate-400">
            No competitors found.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default CompetitorsPreview;
