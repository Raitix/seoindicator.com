"use client";

import Link from "next/link";
import { ChevronRight, MessageSquare, Star } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import {
  formatDateTime,
  formatNumber,
  formatRating,
  getDominantSentiment,
  getSentimentTone,
} from "@/lib/location-dashboard";
import type { ReviewPreview, ReviewStats } from "@/lib/types/location-dashboard";

export interface ReviewsPreviewSectionProps {
  locationId: string;
  stats: ReviewStats | null;
  reviews: ReviewPreview[] | null;
  isLoading?: boolean;
  error?: Error | null;
  onRetry?: () => void;
  linkHref: string;
  className?: string;
}

function StarRating({ rating }: { rating: number }) {
  const fullStars = Math.max(0, Math.min(5, Math.round(rating)));

  return (
    <div className="flex items-center gap-1" role="img" aria-label={`${rating.toFixed(1)} out of 5 stars`}>
      {Array.from({ length: 5 }).map((_, index) => (
        <Star
          key={index}
          className={cn(
            "h-4 w-4",
            index < fullStars ? "fill-amber-300 text-amber-300" : "text-slate-700"
          )}
          aria-hidden="true"
        />
      ))}
    </div>
  );
}

const sentimentLabels: Record<"positive" | "neutral" | "negative", string> = {
  positive: "Positive",
  neutral: "Neutral",
  negative: "Negative",
};

export function ReviewsPreviewSection({
  stats,
  reviews,
  isLoading = false,
  error,
  onRetry,
  linkHref,
  className,
}: ReviewsPreviewSectionProps) {
  const dominantSentiment = getDominantSentiment(stats?.sentiment);
  const reviewItems = reviews?.slice(0, 3) ?? [];

  return (
    <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/10", className)}>
      <CardHeader className="flex flex-row items-start justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
          <CardTitle className="flex items-center gap-2 text-white">
            <MessageSquare className="h-5 w-5 text-violet-300" aria-hidden="true" />
            Reviews
          </CardTitle>
          <p className="mt-2 text-sm text-slate-400">Recent customer feedback, rating distribution, and sentiment summary.</p>
        </div>

        <Button asChild variant="ghost" className="text-violet-300 hover:bg-slate-900 hover:text-violet-200">
          <Link href={linkHref}>
            View all reviews
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
        ) : stats ? (
          <>
            <div className="grid gap-4 xl:grid-cols-[auto_1fr] xl:items-start">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div className="text-4xl font-semibold text-white">{formatRating(stats.average_rating)}</div>
                <div className="mt-2">
                  <StarRating rating={stats.average_rating} />
                </div>
                <div className="mt-3 text-sm text-slate-400">{formatNumber(stats.total_count)} reviews</div>
                <div className={cn("mt-4 inline-flex rounded-full border px-3 py-1.5 text-xs font-medium", getSentimentTone(dominantSentiment))}>
                  {sentimentLabels[dominantSentiment]} sentiment
                </div>
              </div>

              <div className="space-y-2 rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                {[5, 4, 3, 2, 1].map((rating) => {
                  const count = stats.rating_distribution[rating] ?? 0;
                  const width = stats.total_count > 0 ? Math.max(8, Math.round((count / stats.total_count) * 100)) : 0;

                  return (
                    <div key={rating} className="grid grid-cols-[40px_1fr_48px] items-center gap-3 text-sm text-slate-300">
                      <span>{rating}★</span>
                      <div className="h-2 overflow-hidden rounded-full bg-slate-800">
                        <div className="h-full rounded-full bg-amber-300" style={{ width: `${width}%` }} />
                      </div>
                      <span className="text-right text-slate-500">{count}</span>
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="space-y-3">
              <div className="text-sm font-medium text-slate-300">Latest reviews</div>
              {reviewItems.length ? (
                reviewItems.map((review) => (
                  <div key={review.id} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <div className="font-medium text-white">{review.author}</div>
                        <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                          <StarRating rating={review.rating} />
                          <span>{formatDateTime(review.date)}</span>
                        </div>
                      </div>
                      <div className={cn("rounded-full border px-2.5 py-1 text-[11px] font-medium", getSentimentTone(review.sentiment))}>
                        {sentimentLabels[review.sentiment]}
                      </div>
                    </div>
                    <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-300">{review.text || "No review text provided."}</p>
                  </div>
                ))
              ) : (
                <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-6 text-sm text-slate-400">
                  No recent reviews available yet.
                </div>
              )}
            </div>
          </>
        ) : (
          <div className="rounded-2xl border border-dashed border-slate-800 bg-slate-900/60 px-4 py-8 text-center text-sm text-slate-400">
            No review summary is available for this location yet.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default ReviewsPreviewSection;
