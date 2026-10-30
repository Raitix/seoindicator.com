"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

type KeywordRow = {
  keyword: string;
  position: number;
  change: number;
  url: string;
};

type AlertRow = {
  keyword: string;
  change: number;
  previousPosition: number;
  currentPosition: number;
};

type DashboardData = {
  totalKeywords: number;
  averagePosition: number;
  ranked: number;
  notRanked: number;
  changes: {
    improved: number;
    declined: number;
    unchanged: number;
    new: number;
  };
  topKeywords: KeywordRow[];
  alerts: AlertRow[];
};

const baseData: DashboardData = {
  totalKeywords: 128,
  averagePosition: 14.3,
  ranked: 96,
  notRanked: 32,
  changes: {
    improved: 34,
    declined: 18,
    unchanged: 41,
    new: 5,
  },
  topKeywords: [
    { keyword: "seo indicator", position: 2, change: 1, url: "/seo-indicator" },
    { keyword: "keyword tracking", position: 4, change: 0, url: "/keyword-tracking" },
    { keyword: "rank monitoring", position: 6, change: 2, url: "/rank-monitoring" },
    { keyword: "seo dashboard", position: 8, change: -1, url: "/seo-dashboard" },
    { keyword: "competitor analysis", position: 9, change: 1, url: "/competitor-analysis" },
  ],
  alerts: [
    {
      keyword: "content audit",
      change: -7,
      previousPosition: 9,
      currentPosition: 16,
    },
    {
      keyword: "backlink checker",
      change: -5,
      previousPosition: 11,
      currentPosition: 16,
    },
  ],
};

function shift(value: number, variance = 2) {
  const delta = Math.floor(Math.random() * (variance * 2 + 1)) - variance;
  return value + delta;
}

function refreshMockData(previous: DashboardData): DashboardData {
  return {
    ...previous,
    averagePosition: Math.max(1, Number((previous.averagePosition + (Math.random() - 0.5) * 0.6).toFixed(1))),
    changes: {
      improved: Math.max(0, shift(previous.changes.improved, 3)),
      declined: Math.max(0, shift(previous.changes.declined, 2)),
      unchanged: Math.max(0, shift(previous.changes.unchanged, 3)),
      new: Math.max(0, shift(previous.changes.new, 1)),
    },
  };
}

function formatChange(value: number) {
  if (value > 0) return `+${value}`;
  return `${value}`;
}

export default function DashboardSummary({ siteId = "1" }: { siteId?: string }) {
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [autoRefresh, setAutoRefresh] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
  const [data, setData] = useState<DashboardData>(baseData);

  const refreshData = useCallback(() => {
    setIsRefreshing(true);
    setTimeout(() => {
      setData((prev) => refreshMockData(prev));
      setIsRefreshing(false);
      setLastUpdated(new Date());
    }, 500);
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsLoading(false);
      setLastUpdated(new Date());
    }, 700);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (!autoRefresh) return undefined;
    const interval = setInterval(() => refreshData(), 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [autoRefresh, refreshData]);

  const changesMeta = useMemo(
    () => [
      { label: "Improved", value: data.changes.improved, className: "text-emerald-300" },
      { label: "Declined", value: data.changes.declined, className: "text-rose-300" },
      { label: "Unchanged", value: data.changes.unchanged, className: "text-slate-200" },
      { label: "New", value: data.changes.new, className: "text-sky-300" },
    ],
    [data.changes]
  );

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="grid gap-4 md:grid-cols-4">
          {Array.from({ length: 4 }).map((_, index) => (
            <Card key={index} className="border-slate-800 bg-slate-900/60">
              <CardHeader>
                <div className="h-4 w-24 animate-pulse rounded bg-slate-800" />
                <div className="mt-3 h-8 w-16 animate-pulse rounded bg-slate-800" />
              </CardHeader>
            </Card>
          ))}
        </div>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <div className="h-4 w-40 animate-pulse rounded bg-slate-800" />
            <div className="mt-3 h-6 w-72 animate-pulse rounded bg-slate-800" />
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, index) => (
                <div key={index} className="h-10 w-full animate-pulse rounded bg-slate-800" />
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold text-white">Dashboard Summary</h1>
          <p className="text-sm text-slate-400">
            Snapshot of ranking health and keyword momentum.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <label className="flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs text-slate-200">
            <input
              type="checkbox"
              checked={autoRefresh}
              onChange={(event) => setAutoRefresh(event.target.checked)}
              className="accent-emerald-500"
            />
            Auto-refresh (5m)
          </label>
          <Button
            variant="outline"
            className="border-slate-700 text-slate-200"
            onClick={refreshData}
            disabled={isRefreshing}
          >
            {isRefreshing ? "Refreshing..." : "Refresh all"}
          </Button>
          <Button asChild className="bg-emerald-500 text-white hover:bg-emerald-600">
            <Link href={`/sites/${siteId}/keywords/new`}>Add keyword</Link>
          </Button>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Total Keywords</CardDescription>
            <CardTitle className="text-3xl text-white">{data.totalKeywords}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Average Position</CardDescription>
            <CardTitle className="text-3xl text-emerald-300">{data.averagePosition}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Ranked</CardDescription>
            <CardTitle className="text-3xl text-sky-300">{data.ranked}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Not Ranked</CardDescription>
            <CardTitle className="text-3xl text-amber-300">{data.notRanked}</CardTitle>
          </CardHeader>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader className="gap-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <CardTitle className="text-white">Position Changes</CardTitle>
                <CardDescription>Movement across tracked keywords.</CardDescription>
              </div>
              {lastUpdated && (
                <span className="text-xs text-slate-400">
                  Updated {lastUpdated.toLocaleTimeString()}
                </span>
              )}
            </div>
            <div className="grid gap-3 sm:grid-cols-4">
              {changesMeta.map((item) => (
                <div
                  key={item.label}
                  className="rounded-lg border border-slate-800 bg-slate-950/60 p-4"
                >
                  <p className="text-xs uppercase text-slate-500">{item.label}</p>
                  <p className={`mt-2 text-2xl font-semibold ${item.className}`}>{item.value}</p>
                </div>
              ))}
            </div>
          </CardHeader>
        </Card>

        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardTitle className="text-white">Alerts</CardTitle>
            <CardDescription>Significant drops to review.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {data.alerts.map((alert) => (
              <div key={alert.keyword} className="rounded-lg border border-rose-500/40 bg-rose-500/10 p-3">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-medium text-rose-100">{alert.keyword}</p>
                  <span className="text-xs font-semibold text-rose-200">{formatChange(alert.change)}</span>
                </div>
                <p className="mt-1 text-xs text-rose-200/80">
                  {alert.previousPosition} → {alert.currentPosition}
                </p>
              </div>
            ))}
            {data.alerts.length === 0 && (
              <p className="text-sm text-slate-400">No critical drops detected.</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader className="gap-3">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle className="text-white">Top Keywords</CardTitle>
              <CardDescription>Best positions right now.</CardDescription>
            </div>
            <Button asChild variant="outline" className="border-slate-700 text-slate-200">
              <Link href={`/sites/${siteId}/keywords`}>View full keyword list</Link>
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-hidden rounded-lg border border-slate-800">
            <table className="min-w-full text-left text-sm">
              <thead className="bg-slate-950 text-slate-400">
                <tr>
                  <th className="px-4 py-3 font-semibold">Keyword</th>
                  <th className="px-4 py-3 font-semibold">Position</th>
                  <th className="px-4 py-3 font-semibold">Change</th>
                  <th className="px-4 py-3 font-semibold">URL</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {data.topKeywords.map((row) => (
                  <tr key={row.keyword} className="hover:bg-slate-950/60">
                    <td className="px-4 py-3 text-slate-100">{row.keyword}</td>
                    <td className="px-4 py-3 text-slate-200">#{row.position}</td>
                    <td className={`px-4 py-3 ${row.change >= 0 ? "text-emerald-300" : "text-rose-300"}`}>
                      {formatChange(row.change)}
                    </td>
                    <td className="px-4 py-3 text-slate-400">{row.url}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
