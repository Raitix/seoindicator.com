"use client";

import React, { useState, useMemo, Suspense } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { useContentDecayCandidates, useContentDecayCandidate, useUpdateCandidateStatus } from "@/lib/hooks/useContentDecay";
import type { DecayCandidate, ContentDecayCandidateStatus, ContentDecaySeverity } from "@/lib/types/content-decay";
import { SeverityBadge } from "@/components/content-decay";
import { LayoutDashboard, Columns, BarChart3, X, ChevronRight } from "lucide-react";

const COLUMNS: Array<{ id: ContentDecayCandidateStatus; title: string; color: string }> = [
  { id: "backlog", title: "Backlog", color: "bg-slate-600" },
  { id: "planned", title: "Planned", color: "bg-blue-600" },
  { id: "in_progress", title: "In Progress", color: "bg-yellow-600" },
  { id: "refreshed", title: "Refreshed", color: "bg-green-600" },
  { id: "recovered", title: "Recovered", color: "bg-emerald-600" },
];

// Loading fallback for Suspense
function BoardLoadingFallback() {
  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="recovery-board-page-loading">
      <div className="animate-pulse">
        <div className="h-8 w-48 bg-slate-700 rounded mb-2" />
        <div className="h-4 w-64 bg-slate-700 rounded" />
      </div>
      <div className="flex gap-4 overflow-x-auto pb-4">
        {COLUMNS.map((column) => (
          <div key={column.id} className="flex-shrink-0 w-72 md:w-80">
            <div className="animate-pulse space-y-3">
              <div className="h-6 w-24 bg-slate-700 rounded" />
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-24 bg-slate-700 rounded-lg" />
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function RecoveryBoardContent() {
  const params = useParams();
  const router = useRouter();
  const searchParams = useSearchParams();
  const siteId = params.siteId as string;

  const severityFilter = searchParams.get("severity") as ContentDecaySeverity | null;
  const pageTypeFilter = searchParams.get("pageType") as string | null;
  const [selectedCandidateId, setSelectedCandidateId] = useState<number | null>(null);
  const [isSheetOpen, setIsSheetOpen] = useState(false);

  const { candidates, isLoading } = useContentDecayCandidates(siteId, {
    severity: severityFilter || "all",
    pageType: pageTypeFilter || "all",
    status: "all",
  });

  const { candidate: selectedCandidate } = useContentDecayCandidate(selectedCandidateId);
  const { trigger: updateStatus } = useUpdateCandidateStatus(selectedCandidateId);

  const columnsData = useMemo(() => {
    const grouped: Record<string, DecayCandidate[]> = { backlog: [], planned: [], in_progress: [], refreshed: [], recovered: [] };
    candidates?.forEach((candidate) => {
      const status = candidate.status || "backlog";
      if (grouped[status]) grouped[status].push(candidate);
    });
    return grouped;
  }, [candidates]);

  const handleCardClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsSheetOpen(true);
  };

  const handleStatusChange = async (candidateId: number, newStatus: ContentDecayCandidateStatus) => {
    try { await updateStatus({ status: newStatus }); } catch (error) { console.error("Failed to update status:", error); }
  };

  const handleFilterChange = (key: string, value: string | null) => {
    const newParams = new URLSearchParams(searchParams.toString());
    if (value && value !== "all") newParams.set(key, value);
    else newParams.delete(key);
    router.push(`/sites/${siteId}/content-decay/board?${newParams.toString()}`);
  };

  const handleCloseSheet = () => { setIsSheetOpen(false); setSelectedCandidateId(null); };

  const tabs = [
    { id: "queue", label: "Queue", href: `/sites/${siteId}/content-decay`, icon: LayoutDashboard },
    { id: "board", label: "Board", href: `/sites/${siteId}/content-decay/board`, icon: Columns, active: true },
    { id: "recovery", label: "Recovery", href: `/sites/${siteId}/content-decay/recovery`, icon: BarChart3 },
  ];

  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="recovery-board-page">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Recovery Board</h1>
          <p className="text-slate-400 text-sm mt-1">Manage content refresh workflow</p>
        </div>
        <div className="flex items-center gap-2">
          <select value={severityFilter || "all"} onChange={(e) => handleFilterChange("severity", e.target.value)} className="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-sm" aria-label="Filter by severity">
            <option value="all">All Severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
          <select value={pageTypeFilter || "all"} onChange={(e) => handleFilterChange("pageType", e.target.value)} className="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-sm" aria-label="Filter by page type">
            <option value="all">All Page Types</option>
            <option value="blog">Blog</option>
            <option value="product">Product</option>
            <option value="landing">Landing Page</option>
            <option value="category">Category</option>
          </select>
        </div>
      </div>

      <nav className="flex gap-1 border-b border-slate-700 pb-2" aria-label="Content decay views">
        {tabs.map((tab) => (
          <Link key={tab.id} href={tab.href} className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ${tab.active ? "bg-slate-800 text-white" : "text-slate-400 hover:text-white hover:bg-slate-700"}`} aria-current={tab.active ? "page" : undefined}>
            <tab.icon className="w-4 h-4" />{tab.label}
          </Link>
        ))}
      </nav>

      <div className="flex gap-4 overflow-x-auto pb-4" role="region" aria-label="Kanban board">
        {COLUMNS.map((column) => (
          <div key={column.id} className="flex-shrink-0 w-72 md:w-80" data-testid={`column-${column.id}`}>
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <div className={`w-3 h-3 rounded-full ${column.color}`} />
                <h2 className="font-medium text-white">{column.title}</h2>
              </div>
              <span className="text-sm text-slate-400 bg-slate-800 px-2 py-0.5 rounded">{columnsData[column.id]?.length || 0}</span>
            </div>
            <div className="space-y-2 min-h-[200px] bg-slate-800/50 rounded-lg p-2">
              {isLoading ? (
                <div className="animate-pulse space-y-2">{[1, 2, 3].map((i) => (<div key={i} className="h-24 bg-slate-700 rounded-lg" />))}</div>
              ) : columnsData[column.id]?.length === 0 ? (
                <div className="text-center text-slate-500 text-sm py-8">No items</div>
              ) : (
                columnsData[column.id]?.map((candidate) => (
                  <CandidateCard key={candidate.id} candidate={candidate} onClick={() => handleCardClick(candidate)} onStatusChange={(newStatus) => handleStatusChange(candidate.id, newStatus)} />
                ))
              )}
            </div>
          </div>
        ))}
      </div>

      {isSheetOpen && selectedCandidate && (
        <div className="fixed inset-0 z-50 flex justify-end">
          <div className="absolute inset-0 bg-black/50" onClick={handleCloseSheet} />
          <div className="relative w-full max-w-md bg-slate-900 border-l border-slate-700 overflow-y-auto">
            <div className="sticky top-0 flex items-center justify-between p-4 border-b border-slate-700 bg-slate-900">
              <h3 className="font-medium text-white">Candidate Details</h3>
              <button onClick={handleCloseSheet} className="p-1 hover:bg-slate-800 rounded" aria-label="Close"><X className="w-5 h-5 text-slate-400" /></button>
            </div>
            <div className="p-4 space-y-4">
              <div><p className="text-sm text-slate-400">Page URL</p><a href={selectedCandidate.pageUrl} target="_blank" rel="noopener noreferrer" className="text-blue-400 hover:underline text-sm break-all">{selectedCandidate.pageUrl}</a></div>
              <div><p className="text-sm text-slate-400">Severity</p><SeverityBadge severity={selectedCandidate.severity} /></div>
              <div><p className="text-sm text-slate-400">Clicks Lost</p><p className="text-white font-medium">{selectedCandidate.clicksLost}</p></div>
              <div><p className="text-sm text-slate-400">Decay Score</p><p className="text-white font-medium">{selectedCandidate.decayScore}</p></div>
              <div className="pt-4 border-t border-slate-700">
                <p className="text-sm text-slate-400 mb-2">Change Status</p>
                <div className="flex flex-wrap gap-2">
                  {COLUMNS.filter((c) => c.id !== selectedCandidate.status).map((col) => (
                    <button key={col.id} onClick={() => handleStatusChange(selectedCandidate.id, col.id)} className="px-3 py-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg">{col.title}</button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function CandidateCard({ candidate, onClick, onStatusChange }: { candidate: DecayCandidate; onClick: () => void; onStatusChange: (status: ContentDecayCandidateStatus) => void }) {
  return (
    <div className="bg-slate-800 border border-slate-700 rounded-lg p-3 cursor-pointer hover:border-slate-500 transition-colors" onClick={onClick} role="button" tabIndex={0} onKeyDown={(e) => e.key === "Enter" && onClick()}>
      <div className="flex items-start justify-between gap-2 mb-2">
        <SeverityBadge severity={candidate.severity} />
        <span className="text-xs text-slate-400">{candidate.pageType}</span>
      </div>
      <p className="text-sm text-slate-200 line-clamp-2 mb-2">{candidate.pageTitle || candidate.pageUrl}</p>
      <div className="flex items-center gap-3 text-xs text-slate-400">
        <span>-{candidate.clicksLost} clicks</span>
        <span>Score: {candidate.decayScore}</span>
      </div>
      <button onClick={(e) => { e.stopPropagation(); onStatusChange(getNextStatus(candidate.status)); }} className="mt-2 flex items-center gap-1 text-xs text-blue-400 hover:text-blue-300">
        Next: {getNextStatusLabel(candidate.status)}<ChevronRight className="w-3 h-3" />
      </button>
    </div>
  );
}

function getNextStatus(current: ContentDecayCandidateStatus): ContentDecayCandidateStatus {
  const order: ContentDecayCandidateStatus[] = ["backlog", "planned", "in_progress", "refreshed", "recovered"];
  const idx = order.indexOf(current);
  return idx < order.length - 1 ? order[idx + 1] : current;
}

function getNextStatusLabel(current: ContentDecayCandidateStatus): string {
  const next = getNextStatus(current);
  return COLUMNS.find((c) => c.id === next)?.title || next;
}

// Wrap with Suspense for static export compatibility with useSearchParams
export default function RecoveryBoardPage() {
  return (
    <Suspense fallback={<BoardLoadingFallback />}>
      <RecoveryBoardContent />
    </Suspense>
  );
}
