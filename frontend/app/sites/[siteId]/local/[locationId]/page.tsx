"use client";

import { Suspense } from "react";
import { useParams } from "next/navigation";

import { LocationDashboardPage } from "@/components/local/LocationDashboardPage";

function LocationDashboardFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading location dashboard…
    </div>
  );
}

export default function SiteLocationDashboardPage() {
  const params = useParams<{ siteId?: string | string[]; locationId?: string | string[] }>();
  const rawSiteId = params.siteId;
  const rawLocationId = params.locationId;
  const siteId = Array.isArray(rawSiteId) ? rawSiteId[0] : rawSiteId ?? "preview";
  const locationId = Array.isArray(rawLocationId) ? rawLocationId[0] : rawLocationId ?? "preview";

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<LocationDashboardFallback />}>
          <LocationDashboardPage locationId={locationId} siteId={siteId} />
        </Suspense>
      </div>
    </main>
  );
}
