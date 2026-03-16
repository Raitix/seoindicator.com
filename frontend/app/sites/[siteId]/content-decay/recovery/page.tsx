"use client";

import React from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { useContentDecayRecovery, useExportContentDecay } from "@/lib/hooks/useContentDecay";
import { LayoutDashboard, Columns, BarChart3, Download, TrendingUp, Calendar, Target } from "lucide-react";

export default function RecoveryReportPage() {
  const params = useParams();
  const router = useRouter();
  const searchParams = useSearchParams();
  const siteId = params.siteId as string;
  const dateRange = searchParams.get("range") || "30d";

  const { recovery, isLoading } = useContentDecayRecovery(siteId, getDateRangeParams(dateRange));
  const { trigger: exportData, isMutating: isExporting } = useExportContentDecay(siteId);

  const handleDateRangeChange = (value: string) => {
    const newParams = new URLSearchParams(searchParams.toString());
    newParams.set("range", value);
    router.push(`/sites/${siteId}/content-decay/recovery?${newParams.toString()}`);
  };

  const handleExport = async () => {
    try { await exportData({ format: "pdf" }); } catch (error) { console.error("Failed to export:", error); }
  };

  const tabs = [
    { id: "queue", label: "Queue", href: `/sites/${siteId}/content-decay`, icon: LayoutDashboard },
    { id: "board", label: "Board", href: `/sites/${siteId}/content-decay/board`, icon: Columns },
    { id: "recovery", label: "Recovery", href: `/sites/${siteId}/content-decay/recovery`, icon: BarChart3, active: true },
  ];

  const summary = recovery?.summary || { totalRecovered: 0, totalClicksRecovered: 0, avgRecoveryRate: 0, avgDaysToRecovery: 0 };

  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="recovery-report-page">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Recovery Report</h1>
          <p className="text-slate-400 text-sm mt-1">Track content refresh outcomes and recovered traffic</p>
        </div>
        <div className="flex items-center gap-2">
          <select value={dateRange} onChange={(e) => handleDateRangeChange(e.target.value)} className="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-sm" aria-label="Date range" id="date-range-select">
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="90d">Last 90 days</option>
            <option value="all">All time</option>
          </select>
          <label htmlFor="date-range-select" className="sr-only">Date range</label>
          <button onClick={handleExport} disabled={isExporting} className="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 text-slate-200 text-sm font-medium rounded-lg transition-colors" aria-label="Export to PDF">
            <Download className="w-4 h-4" />Export PDF
          </button>
        </div>
      </div>

      <nav className="flex gap-1 border-b border-slate-700 pb-2" aria-label="Content decay views">
        {tabs.map((tab) => (
          <Link key={tab.id} href={tab.href} className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ${tab.active ? "bg-slate-800 text-white" : "text-slate-400 hover:text-white hover:bg-slate-700"}`} aria-current={tab.active ? "page" : undefined}>
            <tab.icon className="w-4 h-4" />{tab.label}
          </Link>
        ))}
      </nav>

      <section aria-label="Recovery summary">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <SummaryCard title="Total Recovered" value={summary.totalRecovered} icon={<TrendingUp className="w-5 h-5" />} isLoading={isLoading} />
          <SummaryCard title="Clicks Recovered" value={summary.totalClicksRecovered} icon={<Target className="w-5 h-5" />} isLoading={isLoading} />
          <SummaryCard title="Avg Recovery Rate" value={`${summary.avgRecoveryRate}%`} icon={<BarChart3 className="w-5 h-5" />} isLoading={isLoading} />
          <SummaryCard title="Avg Days to Recovery" value={summary.avgDaysToRecovery} icon={<Calendar className="w-5 h-5" />} isLoading={isLoading} />
        </div>
      </section>

      <section aria-label="Recovery timeline">
        <div className="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
          <h2 className="text-lg font-medium text-white mb-4">Recovery Timeline</h2>
          {isLoading ? <div className="h-48 bg-slate-700/50 animate-pulse rounded" /> : <TimelineChart data={recovery?.timeline || []} />}
        </div>
      </section>

      <section aria-label="Before and after comparison">
        <div className="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden">
          <div className="p-4 border-b border-slate-700"><h2 className="text-lg font-medium text-white">Before & After Comparison</h2></div>
          {isLoading ? (
            <div className="p-4 space-y-2">{[1, 2, 3].map((i) => (<div key={i} className="h-16 bg-slate-700/50 animate-pulse rounded" />))}</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-700">
                    <th className="text-left p-4 text-slate-400 font-medium">Page</th>
                    <th className="text-right p-4 text-slate-400 font-medium">Before Clicks</th>
                    <th className="text-right p-4 text-slate-400 font-medium">After Clicks</th>
                    <th className="text-right p-4 text-slate-400 font-medium">Recovery</th>
                  </tr>
                </thead>
                <tbody>
                  {recovery?.comparisons?.slice(0, 10).map((item) => (
                    <tr key={item.id} className="border-b border-slate-700/50 hover:bg-slate-700/30">
                      <td className="p-4"><div><p className="text-slate-200 font-medium line-clamp-1">{item.pageTitle}</p><p className="text-xs text-slate-500">{item.pageUrl}</p></div></td>
                      <td className="text-right p-4 text-slate-300">{item.before.clicks}</td>
                      <td className="text-right p-4 text-slate-300">{item.after.clicks}</td>
                      <td className="text-right p-4"><span className={item.after.clicks > item.before.clicks ? "text-green-400" : "text-red-400"}>{item.after.clicks > item.before.clicks ? "+" : ""}{item.after.clicks - item.before.clicks}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>

      <section aria-label="Recovery stories">
        <div className="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
          <h2 className="text-lg font-medium text-white mb-4">Recovery Stories</h2>
          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">{[1, 2].map((i) => (<div key={i} className="h-32 bg-slate-700/50 animate-pulse rounded" />))}</div>
          ) : recovery?.stories?.length === 0 ? (
            <p className="text-slate-500 text-center py-8">No recovery stories yet</p>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {recovery?.stories?.map((story) => (
                <div key={story.id} className="bg-slate-700/50 border border-slate-600 rounded-lg p-4">
                  <p className="text-slate-200 font-medium mb-2">{story.pageTitle}</p>
                  <p className="text-sm text-slate-400 mb-3">{story.summary}</p>
                  <div className="flex items-center gap-4 text-sm">
                    <span className="text-green-400">+{story.clicksRecovered} clicks</span>
                    <span className="text-slate-500">{story.daysToRecovery} days</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
}

function SummaryCard({ title, value, icon, isLoading }: { title: string; value: number | string; icon: React.ReactNode; isLoading: boolean }) {
  return (
    <div className="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
      <div className="flex items-center gap-3">
        <div className="p-2 bg-blue-600/20 rounded-lg text-blue-400">{icon}</div>
        <div>
          <p className="text-sm text-slate-400">{title}</p>
          {isLoading ? <div className="h-6 w-16 bg-slate-700 animate-pulse rounded mt-1" /> : <p className="text-xl font-bold text-white">{value}</p>}
        </div>
      </div>
    </div>
  );
}

function TimelineChart({ data }: { data: Array<{ date: string; recovered: number; clicks: number }> }) {
  if (!data || data.length === 0) return <p className="text-slate-500 text-center py-8">No data available</p>;
  const maxValue = Math.max(...data.map((d) => d.clicks), 1);
  return (
    <div className="flex items-end gap-2 h-48" role="img" aria-label="Recovery timeline chart">
      {data.map((item, index) => (
        <div key={index} className="flex-1 flex flex-col items-center">
          <div className="w-full bg-blue-500 rounded-t" style={{ height: `${(item.clicks / maxValue) * 100}%`, minHeight: item.clicks > 0 ? "4px" : "0" }} title={`${item.date}: ${item.clicks} clicks`} />
          {index % 7 === 0 && <span className="text-xs text-slate-500 mt-1">{new Date(item.date).toLocaleDateString("en-US", { month: "short", day: "numeric" })}</span>}
        </div>
      ))}
    </div>
  );
}

function getDateRangeParams(range: string): { from: string; to: string } {
  const now = new Date();
  const to = now.toISOString().split("T")[0];
  let from: string;
  switch (range) {
    case "7d": from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split("T")[0]; break;
    case "90d": from = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split("T")[0]; break;
    case "all": from = "2020-01-01"; break;
    case "30d":
    default: from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0];
  }
  return { from, to };
}
