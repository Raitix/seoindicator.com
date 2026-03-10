"use client";

import { Download, Inbox, RefreshCw } from "lucide-react";

import { ReviewCard } from "@/components/local/ReviewCard";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Skeleton } from "@/components/ui/skeleton";
import { formatNumber } from "@/lib/location-dashboard";
import { cn } from "@/lib/utils";
import type { LocationReview, ReviewListMeta } from "@/lib/types/location-reviews";

export interface ReviewsListProps {
  reviews: LocationReview[];
  meta: ReviewListMeta;
  isLoading?: boolean;
  error?: Error | null;
  selectedIds?: string[];
  pendingReviewId?: string | null;
  onSelect?: (reviewId: string, selected: boolean) => void;
  onSelectAll?: (selected: boolean) => void;
  onExportSelected?: () => void;
  onPageChange: (page: number) => void;
  onRespond: (review: LocationReview) => void;
  onRetry?: () => void;
  className?: string;
}

function ReviewsListSkeleton() {
  return (
    <div className="space-y-4">
      {Array.from({ length: 3 }).map((_, index) => (
        <Skeleton key={index} className="h-52 w-full rounded-3xl bg-slate-800" />
      ))}
    </div>
  );
}

export function ReviewsList({
  reviews,
  meta,
  isLoading = false,
  error,
  selectedIds = [],
  pendingReviewId = null,
  onSelect,
  onSelectAll,
  onExportSelected,
  onPageChange,
  onRespond,
  onRetry,
  className,
}: ReviewsListProps) {
  if (error) {
    return (
      <Card className={cn("border-red-500/20 bg-red-500/10 text-red-50", className)}>
        <CardContent className="px-6 py-8">
          <div className="text-lg font-semibold">Unable to load reviews</div>
          <p className="mt-2 text-sm text-red-100/90">{error.message}</p>
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

  if (isLoading) {
    return <ReviewsListSkeleton />;
  }

  if (!reviews.length) {
    return (
      <Card className={cn("border-slate-800 bg-slate-950/70 text-slate-100", className)}>
        <CardContent className="px-6 py-14 text-center">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900 text-slate-300">
            <Inbox className="h-6 w-6" aria-hidden="true" />
          </div>
          <h3 className="mt-4 text-xl font-semibold text-white">No reviews match the current filters</h3>
          <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-400">
            Try broadening the filters or refresh reviews after the next Google Business Profile sync.
          </p>
        </CardContent>
      </Card>
    );
  }

  const allSelected = reviews.length > 0 && reviews.every((review) => selectedIds.includes(review.id));
  const from = meta.total === 0 ? 0 : (meta.currentPage - 1) * meta.perPage + 1;
  const to = Math.min(meta.currentPage * meta.perPage, meta.total);

  return (
    <div className={cn("space-y-4", className)}>
      <div className="flex flex-col gap-3 rounded-3xl border border-slate-800 bg-slate-950/70 p-4 shadow-lg shadow-slate-950/10 md:flex-row md:items-center md:justify-between">
        <div className="flex flex-wrap items-center gap-3">
          {onSelectAll ? (
            <label className="inline-flex items-center gap-2 text-sm text-slate-300">
              <Checkbox checked={allSelected} onCheckedChange={(checked) => onSelectAll(Boolean(checked))} aria-label="Select all reviews on this page" />
              Select page
            </label>
          ) : null}
          <span className="text-sm text-slate-400">
            Showing <span className="font-medium text-slate-200">{formatNumber(from)}</span>–<span className="font-medium text-slate-200">{formatNumber(to)}</span> of <span className="font-medium text-slate-200">{formatNumber(meta.total)}</span>
          </span>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {onExportSelected ? (
            <Button
              type="button"
              variant="outline"
              className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
              onClick={onExportSelected}
              disabled={selectedIds.length === 0}
            >
              <Download className="h-4 w-4" aria-hidden="true" />
              Export selected ({selectedIds.length})
            </Button>
          ) : null}
        </div>
      </div>

      <div className="space-y-4">
        {reviews.map((review) => (
          <ReviewCard
            key={review.id}
            review={review}
            onRespond={onRespond}
            onSelect={onSelect ? (selected) => onSelect(review.id, selected) : undefined}
            isSelected={selectedIds.includes(review.id)}
            isPending={pendingReviewId === review.id}
          />
        ))}
      </div>

      <div className="flex flex-col items-center justify-between gap-3 rounded-3xl border border-slate-800 bg-slate-950/70 p-4 sm:flex-row">
        <div className="text-sm text-slate-400">
          Page <span className="font-medium text-slate-200">{meta.currentPage}</span> of <span className="font-medium text-slate-200">{meta.lastPage}</span>
        </div>
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="outline"
            className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
            disabled={meta.currentPage <= 1}
            onClick={() => onPageChange(Math.max(1, meta.currentPage - 1))}
          >
            Previous
          </Button>
          <Button
            type="button"
            variant="outline"
            className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
            disabled={meta.currentPage >= meta.lastPage}
            onClick={() => onPageChange(Math.min(meta.lastPage, meta.currentPage + 1))}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}

export default ReviewsList;
