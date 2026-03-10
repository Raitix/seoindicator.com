"use client";

import * as React from "react";
import Link from "next/link";
import { CheckCircle2, Globe, MapPin, Phone, RefreshCw, Unplug } from "lucide-react";
import { toast } from "sonner";

import AlertsWidget from "@/components/local/AlertsWidget";
import CompetitorsPreview from "@/components/local/CompetitorsPreview";
import InsightsSection from "@/components/local/InsightsSection";
import QuickStatsCards from "@/components/local/QuickStatsCards";
import RankingsPreviewSection from "@/components/local/RankingsPreviewSection";
import ReviewsPreviewSection from "@/components/local/ReviewsPreviewSection";
import SectionErrorBoundary from "@/components/local/SectionErrorBoundary";
import SyncStatus from "@/components/local/SyncStatus";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  buildLocationDashboardLinks,
  buildQuickStatsSnapshot,
  formatDateTime,
} from "@/lib/location-dashboard";
import {
  useDashboardLocation,
  useLocationAlerts,
  useLocationCompetitors,
  useLocationInsights,
  useLocationRankings,
  useLocationReviews,
  useLocationReviewStats,
  useLocationSync,
} from "@/lib/hooks/useLocationDashboard";

export interface LocationDashboardPageProps {
  locationId: string;
  siteId?: string | null;
}

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/20">
        <Skeleton className="h-5 w-40 bg-slate-800" />
        <Skeleton className="mt-4 h-10 w-72 bg-slate-800" />
        <Skeleton className="mt-3 h-5 w-96 bg-slate-800" />
      </div>
      <QuickStatsCards
        isLoading
        stats={{
          views: null,
          searches: null,
          actions: null,
          rating: null,
          reviewCount: null,
          keywordsInPack: null,
        }}
      />
      <div className="grid gap-6 xl:grid-cols-2">
        <Skeleton className="h-[420px] w-full bg-slate-800" />
        <Skeleton className="h-[420px] w-full bg-slate-800" />
      </div>
      <div className="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <Skeleton className="h-[420px] w-full bg-slate-800" />
        <div className="space-y-6">
          <Skeleton className="h-[220px] w-full bg-slate-800" />
          <Skeleton className="h-[220px] w-full bg-slate-800" />
          <Skeleton className="h-[220px] w-full bg-slate-800" />
        </div>
      </div>
    </div>
  );
}

export function LocationDashboardPage({ locationId, siteId }: LocationDashboardPageProps) {
  const { location, error: locationError, isLoading: locationLoading, mutate: mutateLocation } = useDashboardLocation(locationId);
  const { insights, error: insightsError, isLoading: insightsLoading, mutate: mutateInsights } = useLocationInsights(locationId);
  const { stats: reviewStats, error: reviewStatsError, isLoading: reviewStatsLoading, mutate: mutateReviewStats } = useLocationReviewStats(locationId);
  const { reviews, error: reviewsError, isLoading: reviewsLoading, mutate: mutateReviews } = useLocationReviews(locationId);
  const { rankings, error: rankingsError, isLoading: rankingsLoading, mutate: mutateRankings } = useLocationRankings(locationId);
  const { alerts, error: alertsError, isLoading: alertsLoading, mutate: mutateAlerts } = useLocationAlerts(locationId);
  const { competitors, error: competitorsError, isLoading: competitorsLoading, mutate: mutateCompetitors } = useLocationCompetitors(locationId);
  const { triggerSync } = useLocationSync(locationId);
  const [isRefreshing, setIsRefreshing] = React.useState(false);

  const resolvedSiteId = siteId ?? (location?.siteId !== null && location?.siteId !== undefined ? String(location.siteId) : undefined);
  const links = buildLocationDashboardLinks(locationId, resolvedSiteId);
  const reviewSectionError = reviewStatsError ?? reviewsError ?? null;
  const quickStats = React.useMemo(
    () => buildQuickStatsSnapshot(insights, reviewStats, rankings),
    [insights, reviewStats, rankings]
  );

  const retryAll = React.useCallback(async () => {
    await Promise.all([
      mutateLocation(),
      mutateInsights(),
      mutateReviewStats(),
      mutateReviews(),
      mutateRankings(),
      mutateAlerts(),
      mutateCompetitors(),
    ]);
  }, [mutateAlerts, mutateCompetitors, mutateInsights, mutateLocation, mutateRankings, mutateReviewStats, mutateReviews]);

  const handleRefresh = React.useCallback(async () => {
    setIsRefreshing(true);

    try {
      await triggerSync();
      await retryAll();
      toast.success("Location refresh started");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Failed to refresh location data");
      throw error;
    } finally {
      setIsRefreshing(false);
    }
  }, [retryAll, triggerSync]);

  const allSecondarySectionsFailed = Boolean(
    insightsError && reviewSectionError && rankingsError && alertsError && competitorsError
  );

  if (locationLoading && !location) {
    return <DashboardSkeleton />;
  }

  if (locationError && !location) {
    return (
      <Card className="border-red-500/20 bg-red-500/10 text-red-50 shadow-2xl shadow-red-950/10">
        <CardContent className="space-y-4 px-6 py-8">
          <div className="text-2xl font-semibold">Unable to load location dashboard</div>
          <p className="text-sm text-red-100/90">{locationError.message}</p>
          <Button type="button" variant="outline" onClick={() => void mutateLocation()}>
            Try again
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (!location) {
    return <DashboardSkeleton />;
  }

  return (
    <div className="space-y-6">
      <div className="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 p-6 shadow-2xl shadow-slate-950/20">
        <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
          <div className="space-y-4">
            <nav className="flex flex-wrap items-center gap-2 text-sm text-slate-400" aria-label="Breadcrumb">
              {resolvedSiteId ? (
                <>
                  <Link href="/sites" className="transition hover:text-slate-200">
                    Sites
                  </Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}`} className="transition hover:text-slate-200">
                    Site #{resolvedSiteId}
                  </Link>
                  <span>/</span>
                  <Link href={`/sites/${resolvedSiteId}/local`} className="transition hover:text-slate-200">
                    Local locations
                  </Link>
                </>
              ) : (
                <>
                  <Link href="/locations" className="transition hover:text-slate-200">
                    Locations
                  </Link>
                </>
              )}
              <span>/</span>
              <span aria-current="page" className="text-slate-200">
                {location.name}
              </span>
            </nav>

            <div className="space-y-3">
              <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-3xl font-semibold tracking-tight text-white sm:text-4xl">{location.name}</h1>
                <Badge
                  className={location.isGbpConnected ? "border-emerald-500/30 bg-emerald-500/15 text-emerald-200" : "border-slate-700 bg-slate-900 text-slate-300"}
                >
                  {location.isGbpConnected ? <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" /> : <Unplug className="h-3.5 w-3.5" aria-hidden="true" />}
                  {location.isGbpConnected ? "GBP connected" : "GBP not connected"}
                </Badge>
              </div>

              <div className="flex flex-wrap items-center gap-3 text-sm text-slate-400">
                <span className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                  <MapPin className="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {location.address || "Address not available"}
                </span>
                {location.phone ? (
                  <span className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
                    <Phone className="h-4 w-4 text-slate-500" aria-hidden="true" />
                    {location.phone}
                  </span>
                ) : null}
                {location.website ? (
                  <a
                    href={location.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-sky-300 transition hover:text-sky-200"
                  >
                    <Globe className="h-4 w-4" aria-hidden="true" />
                    Website
                  </a>
                ) : null}
              </div>
            </div>

            <div className="text-sm text-slate-500">Last updated: {formatDateTime(location.updatedAt)}</div>
          </div>

          <div className="flex w-full flex-col gap-3 sm:w-auto sm:min-w-[220px]">
            <Button type="button" onClick={() => void handleRefresh()} disabled={isRefreshing}>
              <RefreshCw className={`h-4 w-4 ${isRefreshing ? "animate-spin" : ""}`} aria-hidden="true" />
              {isRefreshing ? "Refreshing…" : "Refresh dashboard"}
            </Button>
            <div className="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 text-sm text-slate-400">
              Last sync: <span className="text-slate-200">{formatDateTime(location.lastSyncAt)}</span>
            </div>
          </div>
        </div>
      </div>

      {!location.isGbpConnected ? (
        <Card className="border-amber-500/20 bg-amber-500/10 text-amber-50">
          <CardContent className="px-6 py-5 text-sm text-amber-100/90">
            Google Business Profile is not connected yet. The dashboard is ready, but some sections may stay empty until the location is connected and synced.
          </CardContent>
        </Card>
      ) : null}

      {allSecondarySectionsFailed ? (
        <Card className="border-red-500/20 bg-red-500/10 text-red-50">
          <CardContent className="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-lg font-semibold">Dashboard data is temporarily unavailable</div>
              <div className="mt-1 text-sm text-red-100/90">All dashboard sections failed to load. Retry to fetch fresh data.</div>
            </div>
            <Button type="button" variant="outline" onClick={() => void retryAll()}>
              Try again
            </Button>
          </CardContent>
        </Card>
      ) : null}

      <QuickStatsCards
        stats={quickStats}
        isLoading={!insights && !reviewStats && !rankings && (insightsLoading || reviewStatsLoading || rankingsLoading)}
      />

      <div className="grid gap-6 xl:grid-cols-2">
        <SectionErrorBoundary title="GBP Insights" onRetry={() => void mutateInsights()}>
          <InsightsSection
            locationId={locationId}
            insights={insights ?? null}
            isLoading={insightsLoading}
            error={insightsError ?? null}
            onRetry={() => void mutateInsights()}
            linkHref={links.insights}
          />
        </SectionErrorBoundary>

        <SectionErrorBoundary title="Reviews" onRetry={() => void Promise.all([mutateReviewStats(), mutateReviews()])}>
          <ReviewsPreviewSection
            locationId={locationId}
            stats={reviewStats ?? null}
            reviews={reviews ?? null}
            isLoading={reviewStatsLoading || reviewsLoading}
            error={reviewSectionError}
            onRetry={() => void Promise.all([mutateReviewStats(), mutateReviews()])}
            linkHref={links.reviews}
          />
        </SectionErrorBoundary>
      </div>

      <div className="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <SectionErrorBoundary title="Local Rankings" onRetry={() => void mutateRankings()}>
          <RankingsPreviewSection
            locationId={locationId}
            rankings={rankings ?? null}
            isLoading={rankingsLoading}
            error={rankingsError ?? null}
            onRetry={() => void mutateRankings()}
            linkHref={links.rankings}
          />
        </SectionErrorBoundary>

        <div className="space-y-6">
          <SectionErrorBoundary title="Alerts" onRetry={() => void mutateAlerts()}>
            <AlertsWidget
              locationId={locationId}
              alerts={alerts ?? null}
              isLoading={alertsLoading}
              error={alertsError ?? null}
              onRetry={() => void mutateAlerts()}
              linkHref={links.alerts}
            />
          </SectionErrorBoundary>

          <SectionErrorBoundary title="Competitors" onRetry={() => void mutateCompetitors()}>
            <CompetitorsPreview
              competitors={competitors ?? null}
              isLoading={competitorsLoading}
              error={competitorsError ?? null}
              onRetry={() => void mutateCompetitors()}
            />
          </SectionErrorBoundary>

          <SyncStatus
            locationId={locationId}
            lastSyncAt={location.lastSyncAt}
            onRefresh={handleRefresh}
            isLoading={isRefreshing}
          />
        </div>
      </div>
    </div>
  );
}

export default LocationDashboardPage;
