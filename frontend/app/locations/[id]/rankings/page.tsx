"use client";

import * as React from "react";
import { Suspense } from "react";
import { useParams } from "next/navigation";

import { LocationRankingsPage } from "@/components/local/LocationRankingsPage";
import { resolveStandaloneLocationDashboardId } from "@/lib/location-dashboard-route";

function LocationRankingsFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading rankings workspace...
    </div>
  );
}

export default function StandaloneLocationRankingsPage() {
  const params = useParams<{ id?: string | string[] }>();
  const [locationId, setLocationId] = React.useState<string | null>(null);

  React.useEffect(() => {
    const pathname = typeof window !== "undefined" ? window.location.pathname : null;
    setLocationId(resolveStandaloneLocationDashboardId(params.id, pathname));
  }, [params.id]);

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        {locationId ? (
          <Suspense fallback={<LocationRankingsFallback />}>
            <LocationRankingsPage locationId={locationId} />
          </Suspense>
        ) : (
          <LocationRankingsFallback />
        )}
      </div>
    </main>
  );
}
