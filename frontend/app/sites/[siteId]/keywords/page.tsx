"use client";

import { useParams } from "next/navigation";
import KeywordList from "@/components/keywords/KeywordList";

export default function KeywordsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-6xl">
        <KeywordList siteId={siteId} />
      </div>
    </div>
  );
}
