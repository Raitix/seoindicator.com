"use client";

import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Edit, RefreshCw } from "lucide-react";

import { useKeyword, useKeywordHistory, triggerRankCheck } from "@/lib/api/keywords";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RankingHistoryChart from "@/components/charts/RankingHistoryChart";

export default function KeywordPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;
  const keywordId = Number(params.keywordId);

  const { keyword, isLoading, error, mutate } = useKeyword(keywordId);
  const { history, isLoading: historyLoading } = useKeywordHistory(keywordId);

  const handleRefresh = async () => {
    try {
      await triggerRankCheck(keywordId);
      // Refresh keyword data after a short delay
      setTimeout(() => mutate(), 2000);
    } catch (err) {
      console.error("Failed to trigger rank check:", err);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
        <div className="mx-auto w-full max-w-4xl">
          <div className="animate-pulse space-y-4">
            <div className="h-8 w-48 rounded bg-slate-800" />
            <div className="h-4 w-64 rounded bg-slate-800" />
            <div className="h-64 rounded-xl bg-slate-800" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !keyword) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
        <div className="mx-auto w-full max-w-4xl">
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="p-6 text-center">
              <p className="text-lg text-white">Keyword not found</p>
              <p className="mt-2 text-sm text-slate-400">
                The keyword you're looking for doesn't exist or you don't have access to it.
              </p>
              <Button
                className="mt-4"
                onClick={() => router.push(`/sites/${siteId}/keywords`)}
              >
                Back to keywords
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  const getPositionBadge = () => {
    if (keyword.currentPosition === null) {
      return <span className="rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-300">Not ranked</span>;
    }
    if (keyword.currentPosition <= 3) {
      return <span className="rounded-full bg-emerald-500/20 px-3 py-1 text-sm text-emerald-300">#{keyword.currentPosition}</span>;
    }
    if (keyword.currentPosition <= 10) {
      return <span className="rounded-full bg-sky-500/20 px-3 py-1 text-sm text-sky-300">#{keyword.currentPosition}</span>;
    }
    return <span className="rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-300">#{keyword.currentPosition}</span>;
  };

  const getChangeIndicator = () => {
    if (keyword.positionChange === null || keyword.positionChange === 0) return null;
    const improved = keyword.positionChange < 0;
    return (
      <span className={`ml-2 text-sm ${improved ? "text-emerald-400" : "text-rose-400"}`}>
        {improved ? "↑" : "↓"} {Math.abs(keyword.positionChange)}
      </span>
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-4xl space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              className="border border-slate-700 text-slate-300"
              onClick={() => router.push(`/sites/${siteId}/keywords`)}
            >
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Button>
            <div>
              <h1 className="text-2xl font-semibold text-white">{keyword.keyword}</h1>
              <p className="text-sm text-slate-400">
                Keyword details and ranking history
              </p>
            </div>
          </div>
          <div className="flex gap-2">
            <Button
              variant="outline"
              className="border-slate-700 text-slate-300"
              onClick={handleRefresh}
            >
              <RefreshCw className="mr-2 h-4 w-4" />
              Check now
            </Button>
            <Button asChild className="bg-emerald-500 text-white hover:bg-emerald-600">
              <Link href={`/sites/${siteId}/keywords/${keywordId}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
          </div>
        </div>

        {/* Position Card */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-white">Current Position</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-4">
              {getPositionBadge()}
              {getChangeIndicator()}
            </div>
            {keyword.targetUrl && (
              <div className="mt-4">
                <p className="text-xs text-slate-500">Target URL</p>
                <a
                  href={keyword.targetUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-emerald-400 hover:underline"
                >
                  {keyword.targetUrl}
                </a>
              </div>
            )}
            {keyword.lastCheckedAt && (
              <div className="mt-4">
                <p className="text-xs text-slate-500">Last checked</p>
                <p className="text-sm text-slate-300">
                  {new Date(keyword.lastCheckedAt).toLocaleString()}
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Ranking History Chart */}
        {historyLoading ? (
          <div className="h-64 animate-pulse rounded-xl bg-slate-800" />
        ) : history && history.length > 0 ? (
          <RankingHistoryChart keywordName={keyword.keyword} data={history} />
        ) : (
          <Card className="border-slate-800 bg-slate-900/60">
            <CardContent className="py-8 text-center">
              <p className="text-slate-400">
                No ranking history available yet. Check back after the first rank check.
              </p>
            </CardContent>
          </Card>
        )}

        {/* Targeting Info */}
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-white">Targeting</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-3">
              <div>
                <p className="text-xs text-slate-500">Search Engine</p>
                <p className="text-sm text-slate-300 capitalize">{keyword.searchEngine ?? "Google"}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Location</p>
                <p className="text-sm text-slate-300 uppercase">{keyword.location ?? "US"}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Device</p>
                <p className="text-sm text-slate-300 capitalize">{keyword.device ?? "Desktop"}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
