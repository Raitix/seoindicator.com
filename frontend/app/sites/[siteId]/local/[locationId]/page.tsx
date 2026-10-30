"use client";

import * as React from "react";
import { Suspense } from "react";
import { useParams } from "next/navigation";

import { LocationDashboardPage } from "@/components/local/LocationDashboardPage";
import { resolveSiteLocationDashboardIds } from "@/lib/location-dashboard-route";

function LocationDashboardFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading location dashboard…
    </div>
  );
}

export default function SiteLocationDashboardPage() {
  const params = useParams<{ siteId?: string | string[]; locationId?: string | string[] }>();
  const [resolvedParams, setResolvedParams] = React.useState<{ siteId: string; locationId: string } | null>(null);

  React.useEffect(() => {
    const pathname = typeof window !== "undefined" ? window.location.pathname : null;
    setResolvedParams(resolveSiteLocationDashboardIds(params.siteId, params.locationId, pathname));
  }, [params.locationId, params.siteId]);

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        {resolvedParams ? (
          <Suspense fallback={<LocationDashboardFallback />}>
            <LocationDashboardPage locationId={resolvedParams.locationId} siteId={resolvedParams.siteId} />
          </Suspense>
        ) : (
          <LocationDashboardFallback />
        )}
      </div>
    </main>
  );
}
