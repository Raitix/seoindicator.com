import { Suspense } from "react";

import { LocationRankingsPage } from "@/components/local/LocationRankingsPage";

function SiteLocationRankingsFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading rankings workspace…
    </div>
  );
}

export default async function SiteLocationRankingsRoute({
  params,
}: {
  params: Promise<{ siteId?: string; locationId?: string }>;
}) {
  const resolvedParams = await params;
  const siteId = resolvedParams.siteId ?? "preview";
  const locationId = resolvedParams.locationId ?? "preview";

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<SiteLocationRankingsFallback />}>
          <LocationRankingsPage locationId={locationId} siteId={siteId} />
        </Suspense>
      </div>
    </main>
  );
}
