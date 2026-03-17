import { Suspense } from "react";

// Force dynamic rendering for static export compatibility
export const dynamic = 'force-dynamic';

// Required for static export with dynamic routes - returns empty array for client-side rendering
export function generateStaticParams() {
  return [];
}

import { LocationManagementPage } from "@/components/local/LocationManagementPage";

function LocationManagementFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading location management…
    </div>
  );
}

export default async function SiteLocationsPage({
  params,
}: {
  params: Promise<{ siteId: string }>;
}) {
  const { siteId } = await params;

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<LocationManagementFallback />}>
          <LocationManagementPage siteId={siteId} />
        </Suspense>
      </div>
    </main>
  );
}
