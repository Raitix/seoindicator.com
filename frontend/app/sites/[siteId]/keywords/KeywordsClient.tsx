"use client";

import { Suspense } from "react";
import KeywordList from "@/components/keywords/KeywordList";

interface KeywordsClientProps {
  siteId: string;
}

function KeywordsPageContent({ siteId }: KeywordsClientProps) {
  return <KeywordList siteId={siteId} />;
}

export default function KeywordsClient({ siteId }: KeywordsClientProps) {
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
