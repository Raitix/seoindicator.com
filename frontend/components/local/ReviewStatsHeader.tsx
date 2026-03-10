"use client";

import { MessageSquareQuote, RefreshCw, Smile, Frown, Meh, Star } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import { formatNumber, formatRating, getSentimentTone } from "@/lib/location-dashboard";
import type { ReviewSentiment, ReviewStatsDetailed } from "@/lib/types/location-reviews";

export interface ReviewStatsHeaderProps {
  stats: ReviewStatsDetailed | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  onRatingClick?: (rating: number) => void;
  onSentimentClick?: (sentiment: ReviewSentiment) => void;
  className?: string;
}

const sentimentMeta: Record<ReviewSentiment, { label: string; icon: typeof Smile }> = {
  positive: { label: "Positive", icon: Smile },
  neutral: { label: "Neutral", icon: Meh },
  negative: { label: "Negative", icon: Frown },
};

function RatingStars({ rating }: { rating: number }) {
  const roundedRating = Math.max(0, Math.min(5, Math.round(rating)));

  return (
    <div className="flex items-center gap-1" role="img" aria-label={`${rating.toFixed(1)} out of 5 stars`}>
      {Array.from({ length: 5 }).map((_, index) => (
        <Star
          key={index}
          className={cn(
            "h-5 w-5",
            index < roundedRating ? "fill-amber-300 text-amber-300" : "text-slate-700"
          )}
          aria-hidden="true"
        />
      ))}
    </div>
  );
}

export function ReviewStatsHeader({
  stats,
  isLoading = false,
  error,
  onRetry,
  onRatingClick,
  onSentimentClick,
  className,
}: ReviewStatsHeaderProps) {
  if (isLoading) {
    return (
      <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100", className)}>
        <CardHeader>
          <Skeleton className="h-6 w-48 bg-slate-800" />
          <Skeleton className="h-4 w-72 bg-slate-800" />
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 lg:grid-cols-[260px_1fr]">
            <Skeleton className="h-44 w-full rounded-2xl bg-slate-800" />
            <Skeleton className="h-44 w-full rounded-2xl bg-slate-800" />
          </div>
          <Skeleton className="h-28 w-full rounded-2xl bg-slate-800" />
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card className={cn("border-red-500/20 bg-red-500/10 text-red-50", className)}>
        <CardHeader>
          <CardTitle>Unable to load review stats</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-red-100/90">{error.message}</p>
          {onRetry ? (
            <Button type="button" variant="outline" className="mt-4" onClick={onRetry}>
              <RefreshCw className="h-4 w-4" aria-hidden="true" />
              Retry
            </Button>
          ) : null}
        </CardContent>
      </Card>
    );
  }

  if (!stats) {
    return (
      <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100", className)}>
        <CardContent className="px-6 py-10 text-center text-sm text-slate-400">
          No review summary is available for this location yet.
        </CardContent>
      </Card>
    );
  }

  const distributionMax = Math.max(1, ...Object.values(stats.ratingDistribution));
  const totalSentiment =
    stats.sentimentBreakdown.positive + stats.sentimentBreakdown.neutral + stats.sentimentBreakdown.negative;

  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="border-b border-slate-800/80 pb-5">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <CardTitle className="flex items-center gap-2 text-white">
              <MessageSquareQuote className="h-5 w-5 text-violet-300" aria-hidden="true" />
              Review health overview
            </CardTitle>
            <p className="mt-2 text-sm text-slate-400">
              Track rating mix, sentiment shifts, and response coverage before diving into individual reviews.
            </p>
          </div>

          {onRetry ? (
            <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" onClick={onRetry}>
              <RefreshCw className="h-4 w-4" aria-hidden="true" />
              Refresh stats
            </Button>
          ) : null}
        </div>
      </CardHeader>

      <CardContent className="space-y-5 pt-6">
        <div className="grid gap-4 lg:grid-cols-[280px_1fr]">
          <div className="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
            <div className="text-xs uppercase tracking-[0.2em] text-slate-500">Average rating</div>
            <div className="mt-3 text-5xl font-semibold tracking-tight text-white">{formatRating(stats.averageRating)}</div>
            <div className="mt-3">
              <RatingStars rating={stats.averageRating} />
            </div>
            <div className="mt-4 text-sm text-slate-400">
              {formatNumber(stats.totalCount)} total reviews
            </div>
            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
              <div className="rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3">
                <div className="text-xs uppercase tracking-wide text-slate-500">Response rate</div>
                <div className="mt-1 text-xl font-semibold text-white">{stats.responseRate.toFixed(1)}%</div>
              </div>
              <div className="rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3">
                <div className="text-xs uppercase tracking-wide text-slate-500">Avg response time</div>
                <div className="mt-1 text-xl font-semibold text-white">
                  {stats.averageResponseTimeHours === null ? "—" : `${Math.round(stats.averageResponseTimeHours)}h`}
                </div>
              </div>
            </div>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <div className="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
              <div className="text-sm font-medium text-slate-200">Rating distribution</div>
              <div className="mt-4 space-y-3">
                {[5, 4, 3, 2, 1].map((rating) => {
                  const count = stats.ratingDistribution[rating] ?? 0;
                  const width = Math.round((count / distributionMax) * 100);

                  return (
                    <button
                      key={rating}
                      type="button"
                      onClick={() => onRatingClick?.(rating)}
                      className="grid w-full grid-cols-[48px_1fr_56px] items-center gap-3 rounded-2xl px-2 py-2 text-left transition hover:bg-slate-950/70"
                    >
                      <span className="text-sm font-medium text-slate-300">{rating}★</span>
                      <div className="h-3 overflow-hidden rounded-full bg-slate-800">
                        <div className="h-full rounded-full bg-amber-300 transition-all" style={{ width: `${width}%` }} />
                      </div>
                      <span className="text-right text-sm text-slate-400">{count}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            <div className="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
              <div className="text-sm font-medium text-slate-200">Sentiment mix</div>
              <div className="mt-4 space-y-3">
                {(Object.keys(sentimentMeta) as ReviewSentiment[]).map((sentiment) => {
                  const count = stats.sentimentBreakdown[sentiment] ?? 0;
                  const percentage = totalSentiment > 0 ? Math.round((count / totalSentiment) * 100) : 0;
                  const Icon = sentimentMeta[sentiment].icon;

                  return (
                    <button
                      key={sentiment}
                      type="button"
                      onClick={() => onSentimentClick?.(sentiment)}
                      className="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-left transition hover:border-slate-700"
                    >
                      <div className="flex items-center gap-3">
                        <div className={cn("rounded-full border p-2", getSentimentTone(sentiment))}>
                          <Icon className="h-4 w-4" aria-hidden="true" />
                        </div>
                        <div>
                          <div className="font-medium text-white">{sentimentMeta[sentiment].label}</div>
                          <div className="text-xs text-slate-500">{percentage}% of tracked reviews</div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-lg font-semibold text-white">{count}</div>
                        <div className="text-xs text-slate-500">reviews</div>
                      </div>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export default ReviewStatsHeader;
