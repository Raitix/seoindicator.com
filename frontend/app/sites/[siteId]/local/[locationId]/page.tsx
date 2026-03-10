"use client";

import * as React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { ExternalLink, Globe, MapPin, MessageSquare, Phone, Star, TrendingUp, ArrowLeft, CheckCircle2, Unplug } from "lucide-react";

import { GBPConnectButton, type ConnectedGBP } from "@/components/local/GBPConnectButton";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useLocation } from "@/lib/hooks/useLocations";

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-slate-800 bg-slate-900/70 p-4">
      <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
      <div className="mt-2 text-sm text-slate-100">{value}</div>
    </div>
  );
}

export default function LocationDetailsPage() {
  const params = useParams();
  const siteId = params.siteId as string;
  const locationId = params.locationId as string;
  const { location, isLoading, error, mutate } = useLocation(locationId);
  const [connectedGBP, setConnectedGBP] = React.useState<ConnectedGBP | null>(null);

  React.useEffect(() => {
    if (!location?.isGbpConnected) {
      setConnectedGBP(null);
      return;
    }

    setConnectedGBP({
      businessName: location.name,
      locationId: location.gbpLocationId ?? String(location.id),
    });
  }, [location?.gbpLocationId, location?.id, location?.isGbpConnected, location?.name]);

  const isGbpConnected = Boolean(connectedGBP ?? location?.isGbpConnected);

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <Button variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" asChild>
            <Link href={`/sites/${siteId}/local`}>
              <ArrowLeft className="h-4 w-4" aria-hidden="true" />
              Back to locations
            </Link>
          </Button>

          {!isLoading && !error ? (
            <Button variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" onClick={() => void mutate()}>
              Refresh
            </Button>
          ) : null}
        </div>

        {isLoading ? (
          <Card className="border-slate-800 bg-slate-950/70 text-slate-100">
            <CardHeader>
              <Skeleton className="h-7 w-48 bg-slate-800" />
              <Skeleton className="h-4 w-72 bg-slate-800" />
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              {Array.from({ length: 6 }).map((_, index) => (
                <Skeleton key={index} className="h-20 rounded-xl bg-slate-800" />
              ))}
            </CardContent>
          </Card>
        ) : error || !location ? (
          <Card className="border-red-500/20 bg-red-500/10 text-red-50">
            <CardHeader>
              <CardTitle>Unable to load location</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-red-100/90">{error?.message ?? "Location not found"}</p>
            </CardContent>
          </Card>
        ) : (
          <>
            <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-xl shadow-slate-950/10">
              <CardHeader className="gap-4 border-b border-slate-800/80 pb-5">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div className="space-y-3">
                    <div className="flex items-center gap-3">
                      <div className="rounded-xl border border-slate-800 bg-slate-900/80 p-2.5 text-slate-300">
                        <MapPin className="h-5 w-5" aria-hidden="true" />
                      </div>
                      <div>
                        <h1 className="text-3xl font-semibold text-white">{location.name}</h1>
                        <p className="mt-1 text-sm text-slate-400">{location.address}</p>
                      </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                      {location.category ? (
                        <Badge variant="outline" className="border-slate-700 text-slate-300">
                          {location.category}
                        </Badge>
                      ) : null}
                      <Badge
                        className={isGbpConnected ? "border-emerald-500/30 bg-emerald-500/15 text-emerald-200" : "border-slate-700 bg-slate-900 text-slate-300"}
                      >
                        {isGbpConnected ? <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" /> : <Unplug className="h-3.5 w-3.5" aria-hidden="true" />}
                        {isGbpConnected ? "GBP connected" : "GBP not connected"}
                      </Badge>
                    </div>
                  </div>

                  <div className="grid gap-3 sm:grid-cols-3">
                    <DetailRow label="Rating" value={location.rating !== null ? location.rating.toFixed(1) : "No rating data"} />
                    <DetailRow label="Reviews" value={location.reviewCount ?? "No review data"} />
                    <DetailRow label="Pack position" value={location.packPosition !== null ? `#${location.packPosition}` : "Not available"} />
                  </div>
                </div>
              </CardHeader>

              <CardContent className="grid gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                <DetailRow label="Phone" value={location.phone ? <span className="inline-flex items-center gap-2"><Phone className="h-4 w-4 text-slate-400" aria-hidden="true" />{location.phone}</span> : "Not provided"} />
                <DetailRow label="Website" value={location.website ? <a href={location.website} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 text-sky-300 hover:text-sky-200"><Globe className="h-4 w-4" aria-hidden="true" />Visit website<ExternalLink className="h-4 w-4" aria-hidden="true" /></a> : "Not provided"} />
                <DetailRow label="Coordinates" value={location.latitude !== null && location.longitude !== null ? `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}` : "Not available"} />
                <DetailRow label="Insights" value={location.insightsSummary ? `${location.insightsSummary.totalViews ?? 0} views · ${location.insightsSummary.totalQueries ?? 0} queries` : "No insights synced yet"} />
                <DetailRow label="Review summary" value={location.reviewStats ? `${location.reviewStats.totalReviews ?? 0} reviews · ${location.reviewStats.responseRate ?? 0}% response rate` : "No review summary available"} />
                <DetailRow label="Last updated" value={location.updatedAt ? new Date(location.updatedAt).toLocaleString() : "Unknown"} />
              </CardContent>
            </Card>

            <Card className="border-slate-800 bg-slate-950/70 text-slate-100 shadow-xl shadow-slate-950/10">
              <CardHeader>
                <CardTitle>Google Business Profile</CardTitle>
                <p className="text-sm text-slate-400">
                  Connect this business location to Google so reviews, insights, and local ranking syncs can be enabled.
                </p>
              </CardHeader>
              <CardContent>
                <GBPConnectButton
                  siteId={siteId}
                  locationId={locationId}
                  connectedGBP={connectedGBP}
                  onConnectionChange={(connected, data) => {
                    setConnectedGBP(
                      connected
                        ? data ?? {
                            businessName: location.name,
                            locationId: location.gbpLocationId ?? String(location.id),
                          }
                        : null
                    );
                    void mutate();
                  }}
                />
              </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-3">
              <Card className="border-slate-800 bg-slate-950/70 text-slate-100">
                <CardContent className="flex items-center gap-3 px-6 py-5">
                  <Star className="h-5 w-5 text-amber-300" aria-hidden="true" />
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Review score</div>
                    <div className="mt-1 text-lg font-semibold text-white">{location.rating !== null ? location.rating.toFixed(1) : "—"}</div>
                  </div>
                </CardContent>
              </Card>
              <Card className="border-slate-800 bg-slate-950/70 text-slate-100">
                <CardContent className="flex items-center gap-3 px-6 py-5">
                  <MessageSquare className="h-5 w-5 text-violet-300" aria-hidden="true" />
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Total reviews</div>
                    <div className="mt-1 text-lg font-semibold text-white">{location.reviewCount ?? "—"}</div>
                  </div>
                </CardContent>
              </Card>
              <Card className="border-slate-800 bg-slate-950/70 text-slate-100">
                <CardContent className="flex items-center gap-3 px-6 py-5">
                  <TrendingUp className="h-5 w-5 text-sky-300" aria-hidden="true" />
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Local pack</div>
                    <div className="mt-1 text-lg font-semibold text-white">{location.packPosition !== null ? `#${location.packPosition}` : "—"}</div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </>
        )}
      </div>
    </main>
  );
}
