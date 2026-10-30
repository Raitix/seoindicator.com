"use client";

import * as React from "react";
import Link from "next/link";
import { ArrowLeft, CheckCircle2, MapPin, RefreshCw, Unplug } from "lucide-react";
import { toast } from "sonner";

import { ReviewResponseModal } from "@/components/local/ReviewResponseModal";
import { ReviewsFilters } from "@/components/local/ReviewsFilters";
import { ReviewsList } from "@/components/local/ReviewsList";
import { ReviewStatsHeader } from "@/components/local/ReviewStatsHeader";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { buildLocationDashboardLinks, formatDateTime } from "@/lib/location-dashboard";
import { useDashboardLocation } from "@/lib/hooks/useLocationDashboard";
import {
  useLocationReviewPage,
  useLocationReviewStatsDetailed,
  useSubmitLocationReviewResponse,
  useSyncLocationReviews,
} from "@/lib/hooks/useLocationReviewsPage";
import {
  defaultReviewFilters,
  REVIEW_PAGE_SIZE,
  type LocationReview,
} from "@/lib/types/location-reviews";

export interface LocationReviewsPageProps {
  locationId: string;
  siteId?: string | null;
}

function PageSkeleton() {
  return (
    <div className="space-y-6">
      <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6">
        <Skeleton className="h-4 w-40 bg-slate-800" />
        <Skeleton className="mt-4 h-10 w-72 bg-slate-800" />
        <Skeleton className="mt-3 h-5 w-96 bg-slate-800" />
      </div>
      <Skeleton className="h-[320px] w-full rounded-3xl bg-slate-800" />
      <Skeleton className="h-[220px] w-full rounded-3xl bg-slate-800" />
      <Skeleton className="h-[520px] w-full rounded-3xl bg-slate-800" />
    </div>
  );
}

const escapeCsvValue = (value: unknown) => {
  const stringValue = `${value ?? ""}`.replace(/"/g, '""');
  return `"${stringValue}"`;
};

export function LocationReviewsPage({ locationId, siteId }: LocationReviewsPageProps) {
  const { location, error: locationError, isLoading: locationLoading, mutate: mutateLocation } = useDashboardLocation(locationId);
  const { stats, error: statsError, isLoading: statsLoading, mutate: mutateStats } = useLocationReviewStatsDetailed(locationId);
  const [filters, setFilters] = React.useState(defaultReviewFilters);
  const [page, setPage] = React.useState(1);
  const { reviews, meta, error: reviewsError, isLoading: reviewsLoading, mutate: mutateReviews } = useLocationReviewPage(
    locationId,
    filters,
    page,
    REVIEW_PAGE_SIZE
  );
  const { submitResponse } = useSubmitLocationReviewResponse(locationId);
  const { triggerSync } = useSyncLocationReviews(locationId);
  const [selectedReviewIds, setSelectedReviewIds] = React.useState<string[]>([]);
  const [activeReview, setActiveReview] = React.useState<LocationReview | null>(null);
  const [pendingReviewId, setPendingReviewId] = React.useState<string | null>(null);
  const [isSyncing, setIsSyncing] = React.useState(false);

  const resolvedSiteId = siteId ?? (location?.siteId !== null && location?.siteId !== undefined ? String(location.siteId) : undefined);
  const links = buildLocationDashboardLinks(locationId, resolvedSiteId);

  React.useEffect(() => {
    setPage(1);
  }, [filters]);

  const selectionScopeKey = `${meta.currentPage}:${reviews.map((review) => review.id).join("|")}`;

  React.useEffect(() => {
    const availableReviewIds = new Set(reviews.map((review) => review.id));

    setSelectedReviewIds((current) => {
      if (!current.length) {
        return current;
      }

      const next = current.filter((reviewId) => availableReviewIds.has(reviewId));
      return next.length === current.length ? current : next;
    });
  }, [selectionScopeKey, reviews]);

  const retryAll = React.useCallback(async () => {
    await Promise.all([mutateLocation(), mutateStats(), mutateReviews()]);
  }, [mutateLocation, mutateReviews, mutateStats]);

  const handleFilterChange = React.useCallback((nextFilters: typeof defaultReviewFilters) => {
    setFilters(nextFilters);
  }, []);

  const handleResetFilters = React.useCallback(() => {
    setFilters(defaultReviewFilters);
  }, []);

  const handleToggleReviewSelection = React.useCallback((reviewId: string, selected: boolean) => {
    setSelectedReviewIds((current) => {
      if (selected) {
        return current.includes(reviewId) ? current : [...current, reviewId];
      }

      return current.filter((id) => id !== reviewId);
    });
  }, []);

  const handleSelectAll = React.useCallback((selected: boolean) => {
    setSelectedReviewIds(selected ? reviews.map((review) => review.id) : []);
  }, [reviews]);

  const handleExportSelected = React.useCallback(() => {
    const selectedReviews = reviews.filter((review) => selectedReviewIds.includes(review.id));

    if (!selectedReviews.length) {
      toast.error("Select at least one review to export");
      return;
    }

    const rows = [
      ["Author", "Rating", "Sentiment", "Reviewed At", "Has Response", "Review Text", "Owner Response"],
      ...selectedReviews.map((review) => [
        review.author,
        review.rating,
        review.sentiment,
        review.reviewedAt,
        review.hasResponse ? "Yes" : "No",
        review.text,
        review.response?.text ?? "",
      ]),
    ];

    const csv = rows.map((row) => row.map(escapeCsvValue).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = `location-${locationId}-reviews.csv`;
    anchor.click();
    URL.revokeObjectURL(url);
    toast.success("Selected reviews exported");
  }, [locationId, reviews, selectedReviewIds]);

  const handleSyncReviews = React.useCallback(async () => {
    setIsSyncing(true);

    try {
      await triggerSync();
      await Promise.all([mutateLocation(), mutateStats(), mutateReviews()]);
      toast.success("Review sync started");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to sync reviews");
    } finally {
      setIsSyncing(false);
    }
  }, [mutateLocation, mutateReviews, mutateStats, triggerSync]);

  const handleSubmitResponse = React.useCallback(async (responseText: string) => {
    if (!activeReview) {
      return;
    }

    const responseReviewId = activeReview.numericId ?? activeReview.id;
    setPendingReviewId(activeReview.id);

    try {
      await submitResponse(responseReviewId, responseText);
      await Promise.all([mutateStats(), mutateReviews(), mutateLocation()]);
      toast.success("Review response posted");
      setActiveReview(null);
    } finally {
      setPendingReviewId(null);
    }
  }, [activeReview, mutateLocation, mutateReviews, mutateStats, submitResponse]);

  if (locationLoading && !location) {
    return <PageSkeleton />;
  }

  if (locationError && !location) {
    return (
      <Card className="border-red-500/20 bg-red-500/10 text-red-50">
        <CardContent className="space-y-4 px-6 py-8">
          <div className="text-2xl font-semibold">Unable to load the reviews workspace</div>
          <p className="text-sm text-red-100/90">{locationError.message}</p>
          <Button type="button" variant="outline" onClick={() => void mutateLocation()}>
            Try again
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (!location) {
    return <PageSkeleton />;
  }

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 p-6 shadow-2xl shadow-slate-950/20">
        <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
          <div className="space-y-4">
            <nav className="flex flex-wrap items-center gap-2 text-sm text-slate-400" aria-label="Breadcrumb">
              {resolvedSiteId ? (
                <>
                  <Link href="/sites" className="transition hover:text-slate-200">Sites</Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}`} className="transition hover:text-slate-200">Site #{resolvedSiteId}</Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}/local`} className="transition hover:text-slate-200">Local locations</Link>
                </>
              ) : (
                <Link href="/locations" className="transition hover:text-slate-200">Locations</Link>
              )}
              <span>/</span>
              <Link href={links.dashboard} className="transition hover:text-slate-200">{location.name}</Link>
              <span>/</span>
              <span aria-current="page" className="text-slate-200">Reviews</span>
            </nav>

            <div className="space-y-3">
              <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Reviews management</h1>
                <Badge className={location.isGbpConnected ? "border-emerald-500/30 bg-emerald-500/15 text-emerald-200" : "border-slate-700 bg-slate-900 text-slate-300"}>
                  {location.isGbpConnected ? <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" /> : <Unplug className="h-3.5 w-3.5" aria-hidden="true" />}
                  {location.isGbpConnected ? "GBP connected" : "GBP not connected"}
                </Badge>
              </div>
              <p className="max-w-3xl text-sm leading-6 text-slate-400">
                Review incoming customer feedback, respond to unanswered reviews, and monitor how sentiment shifts across this location.
              </p>
              <div className="flex flex-wrap items-center gap-3 text-sm text-slate-400">
                <span className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                  <MapPin className="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {location.name}
                </span>
                <span className="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                  Last synced: <span className="ml-1 text-slate-200">{formatDateTime(location.lastSyncAt)}</span>
                </span>
              </div>
            </div>
          </div>

          <div className="flex w-full flex-col gap-3 sm:w-auto sm:min-w-[220px]">
            <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" asChild>
              <Link href={links.dashboard}>
                <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                Back to dashboard
              </Link>
            </Button>
            <Button type="button" onClick={() => void handleSyncReviews()} disabled={!location.isGbpConnected || isSyncing}>
              <RefreshCw className={`h-4 w-4 ${isSyncing ? "animate-spin" : ""}`} aria-hidden="true" />
              {isSyncing ? "Syncing…" : "Sync reviews"}
            </Button>
          </div>
        </div>
      </section>

      {!location.isGbpConnected ? (
        <Card className="border-amber-500/20 bg-amber-500/10 text-amber-50">
          <CardContent className="px-6 py-5 text-sm text-amber-100/90">
            This location is not connected to Google Business Profile yet. Connect it first to fetch reviews and post owner responses.
          </CardContent>
        </Card>
      ) : null}

      <ReviewStatsHeader
        stats={stats ?? null}
        isLoading={statsLoading}
        error={statsError ?? null}
        onRetry={() => void mutateStats()}
        onRatingClick={(rating) => setFilters((current) => ({ ...current, ratings: current.ratings.includes(rating) ? [] : [rating] }))}
        onSentimentClick={(sentiment) => setFilters((current) => ({ ...current, sentiment }))}
      />

      <ReviewsFilters
        filters={filters}
        onChange={handleFilterChange}
        onReset={handleResetFilters}
        isLoading={reviewsLoading}
      />

      <ReviewsList
        reviews={reviews}
        meta={meta}
        isLoading={reviewsLoading}
        error={reviewsError ?? null}
        selectedIds={selectedReviewIds}
        pendingReviewId={pendingReviewId}
        onSelect={handleToggleReviewSelection}
        onSelectAll={handleSelectAll}
        onExportSelected={handleExportSelected}
        onPageChange={setPage}
        onRespond={(review) => setActiveReview(review)}
        onRetry={() => void retryAll()}
      />

      <ReviewResponseModal
        isOpen={Boolean(activeReview)}
        review={activeReview}
        isSubmitting={pendingReviewId !== null}
        onClose={() => setActiveReview(null)}
        onSubmit={handleSubmitResponse}
      />
    </div>
  );
}

export default LocationReviewsPage;
