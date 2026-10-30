"use client";

import Link from "next/link";
import { ArrowDownRight, ArrowUpRight, ChevronRight, Target } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatPosition } from "@/lib/location-dashboard";
import type { LocalRankings } from "@/lib/types/location-dashboard";

export interface RankingsPreviewSectionProps {
  locationId: string;
  rankings: LocalRankings | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  linkHref: string;
  className?: string;
}

function ChangePill({ change }: { change: number }) {
  if (change === 0) {
    return <span className="rounded-full border border-slate-700 px-2 py-1 text-[11px] text-slate-400">No change</span>;
  }

  const positive = change > 0;

  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[11px] font-medium",
        positive
          ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-100"
          : "border-red-500/30 bg-red-500/10 text-red-100"
      )}
    >
      {positive ? <ArrowUpRight className="h-3 w-3" aria-hidden="true" /> : <ArrowDownRight className="h-3 w-3" aria-hidden="true" />}
      {positive ? "+" : ""}
      {change}
    </span>
  );
}

export function RankingsPreviewSection({
  rankings,
  isLoading = false,
  error,
  onRetry,
  linkHref,
  className,
}: RankingsPreviewSectionProps) {
  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="flex flex-row items-start justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
          <CardTitle className="flex items-center gap-2 text-white">
            <Target className="h-5 w-5 text-sky-300" aria-hidden="true" />
            Local Rankings
          </CardTitle>
          <p className="mt-2 text-sm text-slate-400">Top keywords, average local-pack position, and drops that need attention.</p>
        </div>

        <Button asChild variant="ghost" className="text-sky-300 hover:bg-slate-900 hover:text-sky-200">
          <Link href={linkHref}>
            View rankings
            <ChevronRight className="h-4 w-4" aria-hidden="true" />
          </Link>
        </Button>
      </CardHeader>

      <CardContent className="space-y-5 pt-6">
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
          <div className="space-y-4">
            <Skeleton className="h-20 w-full bg-slate-800" />
            <Skeleton className="h-24 w-full bg-slate-800" />
            <Skeleton className="h-24 w-full bg-slate-800" />
          </div>
        ) : rankings ? (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div className="text-sm text-slate-400">Keywords in local pack</div>
                <div className="mt-2 text-3xl font-semibold text-white">{rankings.keywords_in_pack}</div>
                <p className="mt-2 text-sm text-slate-500">Tracked keywords currently appearing in the local pack.</p>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div className="text-sm text-slate-400">Average position</div>
                <div className="mt-2 text-3xl font-semibold text-white">{formatPosition(rankings.average_position)}</div>
                <p className="mt-2 text-sm text-slate-500">Lower numbers mean stronger local visibility.</p>
              </div>
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div className="text-sm font-medium text-slate-300">Top performing keywords</div>
                <div className="mt-4 space-y-3">
                  {rankings.top_keywords.length ? (
                    rankings.top_keywords.map((keyword) => (
                      <div key={keyword.keyword} className="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-3">
                        <div>
                          <div className="font-medium text-white">{keyword.keyword}</div>
                          <div className="mt-1 text-xs text-slate-500">Position #{keyword.position}</div>
                        </div>
                        <ChangePill change={keyword.change} />
                      </div>
                    ))
                  ) : (
                    <div className="rounded-xl border border-dashed border-slate-800 px-4 py-6 text-sm text-slate-400">
                      No keyword rankings available yet.
                    </div>
                  )}
                </div>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm font-medium text-slate-300">Needs attention</div>
                  <span className="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[11px] font-medium text-amber-100">
                    {rankings.dropped_keywords.length} dropped
                  </span>
                </div>
                <div className="mt-4 space-y-3">
                  {rankings.dropped_keywords.length ? (
                    rankings.dropped_keywords.map((keyword) => (
                      <div key={keyword.keyword} className="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-3">
                        <div className="font-medium text-white">{keyword.keyword}</div>
                        <div className="mt-2 text-xs text-slate-400">
                          Previous #{keyword.previous_position}
                          {keyword.current_position > 0 ? ` → Current #${keyword.current_position}` : " → Currently out of pack"}
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="rounded-xl border border-dashed border-slate-800 px-4 py-6 text-sm text-slate-400">
                      No dropped keywords right now.
                    </div>
                  )}
                </div>
              </div>
            </div>
          </>
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-8 text-center text-sm text-slate-400">
            No ranking summary is available for this location yet.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default RankingsPreviewSection;
