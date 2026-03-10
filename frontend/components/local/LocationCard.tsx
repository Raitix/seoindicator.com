"use client";

import Link from "next/link";
import { ExternalLink, Globe, MapPin, MessageSquare, Phone, Star, TrendingUp, CheckCircle2, Unplug, Pencil, Trash2 } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import type { BusinessLocation } from "@/lib/types/locations";

export interface LocationCardProps {
  siteId: string | number;
  location: BusinessLocation;
  onEdit?: (location: BusinessLocation) => void;
  onDelete?: (location: BusinessLocation) => void;
  onViewDetails?: (location: BusinessLocation) => void;
  isDeleting?: boolean;
  className?: string;
}

const formatRating = (rating: number | null) => {
  if (rating === null || Number.isNaN(rating)) {
    return null;
  }

  return Number.isInteger(rating) ? String(rating) : rating.toFixed(1);
};

const statTone = (connected: boolean) =>
  connected
    ? "border-emerald-500/20 bg-emerald-500/10 text-emerald-100"
    : "border-slate-700 bg-slate-900/70 text-slate-300";

export function LocationCard({
  siteId,
  location,
  onEdit,
  onDelete,
  onViewDetails,
  isDeleting = false,
  className,
}: LocationCardProps) {
  const detailsHref = `/sites/${siteId}/local?locationId=${location.id}`;
  const rating = formatRating(location.rating);
  const reviewCount = location.reviewCount;

  return (
    <Card
      data-testid={`location-card-${location.id}`}
      aria-label={`Location: ${location.name}`}
      className={cn(
        "border-slate-800 bg-slate-950/70 text-slate-100 shadow-lg shadow-slate-950/20",
        className
      )}
    >
      <CardHeader className="gap-4 border-b border-slate-800/80 pb-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex items-start gap-3">
            <div className="rounded-xl border border-slate-800 bg-slate-900/80 p-2.5 text-slate-300">
              <MapPin className="h-5 w-5" aria-hidden="true" />
            </div>
            <div className="space-y-2">
              <div className="space-y-1">
                <CardTitle
                  data-testid={`location-name-${location.id}`}
                  className="text-xl font-semibold text-white"
                >
                  {location.name}
                </CardTitle>
                <p
                  data-testid={`location-address-${location.id}`}
                  className="max-w-2xl text-sm leading-6 text-slate-400"
                >
                  {location.address}
                </p>
              </div>

              <div className="flex flex-wrap items-center gap-2">
                {location.category ? (
                  <Badge variant="outline" className="border-slate-700 text-slate-300">
                    {location.category}
                  </Badge>
                ) : null}

                <Badge
                  data-testid={`connection-badge-${location.id}`}
                  aria-label={location.isGbpConnected ? "GBP Connected" : "GBP Not Connected"}
                  className={cn(
                    "gap-1.5 border font-medium",
                    location.isGbpConnected
                      ? "border-emerald-500/30 bg-emerald-500/15 text-emerald-200"
                      : "border-slate-700 bg-slate-900/80 text-slate-300"
                  )}
                >
                  {location.isGbpConnected ? (
                    <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                  ) : (
                    <Unplug className="h-3.5 w-3.5" aria-hidden="true" />
                  )}
                  {location.isGbpConnected ? "Connected" : "Not connected"}
                </Badge>
              </div>
            </div>
          </div>

          <div className="grid min-w-full grid-cols-1 gap-2 sm:min-w-[280px] sm:grid-cols-3">
            <div
              data-testid={`rating-stat-${location.id}`}
              className={cn(
                "rounded-xl border px-3 py-2.5 text-sm",
                statTone(Boolean(rating))
              )}
            >
              <div className="mb-1 flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                <Star className="h-3.5 w-3.5" aria-hidden="true" /> Rating
              </div>
              <div className="font-medium text-white">
                {rating ? `${rating}${reviewCount !== null ? ` · ${reviewCount} reviews` : ""}` : "No rating data"}
              </div>
            </div>

            <div
              data-testid={`pack-position-${location.id}`}
              className={cn(
                "rounded-xl border px-3 py-2.5 text-sm",
                location.packPosition !== null
                  ? "border-sky-500/20 bg-sky-500/10 text-sky-100"
                  : "border-slate-700 bg-slate-900/70 text-slate-300"
              )}
            >
              <div className="mb-1 flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                <TrendingUp className="h-3.5 w-3.5" aria-hidden="true" /> Local pack
              </div>
              <div className="font-medium text-white">
                {location.packPosition !== null ? `Position #${location.packPosition}` : "Not available"}
              </div>
            </div>

            <div
              data-testid={`review-count-${location.id}`}
              className={cn(
                "rounded-xl border px-3 py-2.5 text-sm",
                reviewCount !== null
                  ? "border-violet-500/20 bg-violet-500/10 text-violet-100"
                  : "border-slate-700 bg-slate-900/70 text-slate-300"
              )}
            >
              <div className="mb-1 flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                <MessageSquare className="h-3.5 w-3.5" aria-hidden="true" /> Reviews
              </div>
              <div className="font-medium text-white">{reviewCount ?? "No review data"}</div>
            </div>
          </div>
        </div>
      </CardHeader>

      <CardContent className="space-y-4 pt-6">
        <div data-testid={`quick-stats-${location.id}`} className="flex flex-wrap gap-3 text-sm text-slate-300">
          {location.phone ? (
            <div data-testid={`phone-stat-${location.id}`} className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5">
              <Phone className="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
              <span>{location.phone}</span>
            </div>
          ) : null}

          {location.website ? (
            <a
              data-testid={`website-link-${location.id}`}
              href={location.website}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-sky-300 transition hover:border-sky-500/30 hover:text-sky-200"
            >
              <Globe className="h-3.5 w-3.5" aria-hidden="true" />
              <span>Website</span>
              <ExternalLink className="h-3.5 w-3.5" aria-hidden="true" />
            </a>
          ) : null}

          {location.latitude !== null && location.longitude !== null ? (
            <div className="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-slate-300">
              <MapPin className="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
              <span>
                {location.latitude.toFixed(5)}, {location.longitude.toFixed(5)}
              </span>
            </div>
          ) : null}
        </div>
      </CardContent>

      <CardFooter className="flex flex-col gap-2 border-t border-slate-800/80 pt-5 sm:flex-row sm:justify-between">
        <Button
          data-testid={`view-details-btn-${location.id}`}
          aria-label={`View details for ${location.name}`}
          variant="outline"
          className="w-full justify-center border-slate-700 bg-slate-900/80 text-slate-100 hover:bg-slate-800 sm:w-auto"
          asChild={!onViewDetails}
          onClick={onViewDetails ? () => onViewDetails(location) : undefined}
        >
          {onViewDetails ? (
            <span>View details</span>
          ) : (
            <Link href={detailsHref}>View details</Link>
          )}
        </Button>

        <div data-testid={`actions-${location.id}`} className="flex w-full flex-wrap justify-end gap-2 sm:w-auto">
          <Button
            type="button"
            data-testid={`edit-btn-${location.id}`}
            aria-label={`Edit ${location.name}`}
            variant="ghost"
            className="text-slate-300 hover:bg-slate-800 hover:text-white"
            onClick={() => onEdit?.(location)}
          >
            <Pencil className="h-4 w-4" aria-hidden="true" />
            Edit
          </Button>

          <Button
            type="button"
            data-testid={`delete-btn-${location.id}`}
            aria-label={`Delete ${location.name}`}
            variant="destructive"
            disabled={isDeleting}
            className="bg-red-600/90 text-white hover:bg-red-600"
            onClick={() => onDelete?.(location)}
          >
            <Trash2 className="h-4 w-4" aria-hidden="true" />
            {isDeleting ? "Deleting..." : "Delete"}
          </Button>
        </div>
      </CardFooter>
    </Card>
  );
}

export function LocationCardSkeleton() {
  return (
    <Card className="border-slate-800 bg-slate-950/70 text-slate-100">
      <CardHeader className="gap-4 border-b border-slate-800/80 pb-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div className="flex items-start gap-3">
            <Skeleton className="h-11 w-11 rounded-xl bg-slate-800" />
            <div className="space-y-3">
              <Skeleton className="h-6 w-40 bg-slate-800" />
              <Skeleton className="h-4 w-72 bg-slate-800" />
              <div className="flex gap-2">
                <Skeleton className="h-6 w-20 rounded-full bg-slate-800" />
                <Skeleton className="h-6 w-24 rounded-full bg-slate-800" />
              </div>
            </div>
          </div>

          <div className="grid min-w-full grid-cols-1 gap-2 sm:min-w-[280px] sm:grid-cols-3">
            <Skeleton className="h-20 rounded-xl bg-slate-800" />
            <Skeleton className="h-20 rounded-xl bg-slate-800" />
            <Skeleton className="h-20 rounded-xl bg-slate-800" />
          </div>
        </div>
      </CardHeader>

      <CardContent className="space-y-4 pt-6">
        <div className="flex flex-wrap gap-3">
          <Skeleton className="h-9 w-40 rounded-full bg-slate-800" />
          <Skeleton className="h-9 w-28 rounded-full bg-slate-800" />
        </div>
      </CardContent>

      <CardFooter className="flex flex-wrap justify-between gap-2 border-t border-slate-800/80 pt-5">
        <Skeleton className="h-9 w-28 bg-slate-800" />
        <div className="flex gap-2">
          <Skeleton className="h-9 w-20 bg-slate-800" />
          <Skeleton className="h-9 w-24 bg-slate-800" />
        </div>
      </CardFooter>
    </Card>
  );
}

export default LocationCard;
