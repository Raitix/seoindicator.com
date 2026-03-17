"use client";

// Force dynamic rendering for static export compatibility
export const dynamic = 'force-dynamic';

// Required for static export with dynamic routes - returns empty array for client-side rendering
export function generateStaticParams() {
  return [];
}

import { Suspense } from "react";
import { useParams } from "next/navigation";
import KeywordList from "@/components/keywords/KeywordList";

function KeywordsPageContent({ siteId }: { siteId: string }) {
  return <KeywordList siteId={siteId} />;
}

export default function KeywordsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-6xl">
        <Suspense fallback={
          <div className="flex items-center justify-center py-20 text-slate-400">
            Loading keywords...
          </div>
        }>
          <KeywordsPageContent siteId={siteId} />
        </Suspense>
      </div>
    </div>
  );
}
