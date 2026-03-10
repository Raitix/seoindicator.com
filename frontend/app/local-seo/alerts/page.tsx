import { Suspense } from "react";

import { LocalSeoAlertsPage } from "@/components/local/LocalSeoAlertsPage";

function LocalSeoAlertsFallback() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 text-sm text-slate-400 shadow-2xl shadow-slate-950/20">
      Loading local SEO alerts…
    </div>
  );
}

export default function LocalSeoAlertsRoute() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <Suspense fallback={<LocalSeoAlertsFallback />}>
          <LocalSeoAlertsPage />
        </Suspense>
      </div>
    </main>
  );
}
