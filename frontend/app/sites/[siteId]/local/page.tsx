"use client";

import { useParams } from "next/navigation";

import { LocationManagementPage } from "@/components/local/LocationManagementPage";

export default function SiteLocationsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-8 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <LocationManagementPage siteId={siteId} />
      </div>
    </main>
  );
}
