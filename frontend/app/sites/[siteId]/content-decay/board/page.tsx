"use client";

import React, { useState, useMemo } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  Kanban,
  LayoutDashboard,
  TrendingUp,
  AlertTriangle,
  FileText,
  Globe,
  ChevronRight,
} from "lucide-react";

import { SeverityBadge } from "@/components/content-decay/SeverityBadge";
import { DecayReasonBadges } from "@/components/content-decay/DecayReasonBadges";
import { CandidateDetailDrawer } from "@/components/content-decay/CandidateDetailDrawer";
import {
  useContentDecayCandidates,
  useUpdateCandidateStatus,
} from "@/lib/hooks/useContentDecay";
import type { DecayCandidate, ContentDecayCandidateStatus } from "@/lib/types/content-decay";

/**
 * Content Decay Recovery Board Page
 * 
 * Kanban-style board for managing decay candidates through the recovery workflow.
 * Columns: Backlog, Planned, In Progress, Refreshed, Recovered.
 * 
 * @see TASK-36-13
 * @see SPEC-036
 */

// Kanban column configuration
const COLUMNS: Array<{
  id: ContentDecayCandidateStatus;
  title: string;
  color: string;
}> = [
  { id: "backlog", title: "Backlog", color: "bg-slate-600" },
  { id: "planned", title: "Planned", color: "bg-blue-600" },
  { id: "in_progress", title: "In Progress", color: "bg-yellow-600" },
  { id: "refreshed", title: "Refreshed", color: "bg-purple-600" },
  { id: "recovered", title: "Recovered", color: "bg-green-600" },
];

// Filter state type
interface BoardFilters {
  severity: string;
  pageType: string;
}

export default function ContentDecayBoardPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State
  const [selectedCandidate, setSelectedCandidate] = useState<DecayCandidate | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [filters, setFilters] = useState<BoardFilters>({
    severity: "all",
    pageType: "all",
  });

  // Hooks - fetch all candidates without status filter
  const { candidates, isLoading, mutate } = useContentDecayCandidates(siteId, {
    severity: filters.severity !== "all" ? filters.severity : undefined,
    pageType: filters.pageType !== "all" ? filters.pageType : undefined,
  });

  // Group candidates by status
  const candidatesByColumn = useMemo(() => {
    const grouped: Record<ContentDecayCandidateStatus, DecayCandidate[]> = {
      backlog: [],
      planned: [],
      in_progress: [],
      refreshed: [],
      recovered: [],
      ignored: [],
    };

    candidates.forEach((candidate) => {
      const status = candidate.status || "backlog";
      if (grouped[status]) {
        grouped[status].push(candidate);
      }
    });

    return grouped;
  }, [candidates]);

  // Calculate totals
  const totalCandidates = candidates.length;
  const recoveredCount = candidatesByColumn.recovered.length;
  const inProgressCount = candidatesByColumn.in_progress.length;

  // Get unique page types from candidates
  const pageTypes = useMemo(() => {
    const types = new Set(candidates.map((c) => c.pageType).filter(Boolean));
    return ["all", ...Array.from(types)];
  }, [candidates]);

  // Handlers
  const handleCandidateClick = (candidate: DecayCandidate) => {
    setSelectedCandidate(candidate);
    setIsDrawerOpen(true);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedCandidate(null);
  };

  const handleStatusChange = (candidateId: number, newStatus: ContentDecayCandidateStatus) => {
    mutate();
  };

  // Render card
  const renderCard = (candidate: DecayCandidate) => (
    <div
      key={candidate.id}
      onClick={() => handleCandidateClick(candidate)}
      className="bg-slate-800 border border-slate-700 rounded-lg p-4 cursor-pointer hover:border-slate-600 transition-colors"
      role="button"
      tabIndex={0}
      aria-label={`View details for ${candidate.pageTitle}`}
    >
      {/* Header */}
      <div className="flex items-start justify-between gap-2 mb-2">
        <h4 className="text-sm font-medium text-slate-100 line-clamp-2 flex-1">
          {candidate.pageTitle}
        </h4>
        <SeverityBadge severity={candidate.severity} />
      </div>

      {/* URL */}
      <p className="text-xs text-slate-500 truncate mb-3">{candidate.pageUrl}</p>

      {/* Metrics */}
      <div className="flex items-center gap-4 text-xs text-slate-400 mb-3">
        <div className="flex items-center gap-1">
          <span className="text-red-400">-{candidate.clicksLost}</span>
          <span>clicks</span>
        </div>
        <div className="flex items-center gap-1">
          <span className="text-red-400">-{candidate impressionsLost}</span>
          <span>impr.</span>
        </div>
      </div>

      {/* Decay Reasons */}
      <DecayReasonBadges reasons={candidate.decayReasons} />

      {/* Page Type */}
      {candidate.pageType && (
        <div className="mt-3 flex items-center gap-1 text-xs text-slate-500">
          <Globe className="w-3 h-3" />
          <span>{candidate.pageType}</span>
        </div>
      )}
    </div>
  );

  // Loading state
  if (isLoading) {
    return (
      <div className="container mx-auto px-4 py-6" data-testid="loading-skeleton">
        <div className="animate-pulse space-y-6">
          <div className="h-8 w-48 bg-slate-700 rounded" />
          <div className="flex gap-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="flex-1 h-96 bg-slate-700 rounded-lg" />
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-100">Recovery Board</h1>
          <p className="text-slate-400 mt-1">
            Manage content recovery workflow
          </p>
        </div>
      </div>

      {/* Navigation Tabs */}
      <nav className="flex gap-1 mb-6 border-b border-slate-700" aria-label="Content decay navigation">
        <Link
          href={`/sites/${siteId}/content-decay`}
          className="flex items-center gap-2 px-4 py-3 text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600 transition-colors"
        >
          <LayoutDashboard className="w-4 h-4" />
          Queue
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/board`}
          className="flex items-center gap-2 px-4 py-3 text-blue-400 border-b-2 border-blue-400 font-medium"
          aria-current="page"
        >
          <Kanban className="w-4 h-4" />
          Board
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/recovery`}
          className="flex items-center gap-2 px-4 py-3 text-slate-400 hover:text-slate-200 border-b-2 border-transparent hover:border-slate-600 transition-colors"
        >
          <TrendingUp className="w-4 h-4" />
          Recovery
        </Link>
      </nav>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-4 mb-6">
        <div className="flex items-center gap-2">
          <label htmlFor="severity-filter" className="text-sm text-slate-400">
            Severity:
          </label>
          <select
            id="severity-filter"
            value={filters.severity}
            onChange={(e) => setFilters({ ...filters, severity: e.target.value })}
            className="bg-slate-800 border border-slate-700 text-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Filter by severity"
          >
            <option value="all">All</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        <div className="flex items-center gap-2">
          <label htmlFor="page-type-filter" className="text-sm text-slate-400">
            Page Type:
          </label>
          <select
            id="page-type-filter"
            value={filters.pageType}
            onChange={(e) => setFilters({ ...filters, pageType: e.target.value })}
            className="bg-slate-800 border border-slate-700 text-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Filter by page type"
          >
            {pageTypes.map((type) => (
              <option key={type} value={type}>
                {type === "all" ? "All Types" : type}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Kanban Board */}
      <div className="flex gap-4 overflow-x-auto pb-4" role="region" aria-label="Kanban board">
        {COLUMNS.map((column) => {
          const columnCandidates = candidatesByColumn[column.id] || [];
          
          return (
            <div
              key={column.id}
              className="flex-shrink-0 w-72 bg-slate-900/50 rounded-lg"
              role="list"
              aria-label={`${column.title} column`}
            >
              {/* Column Header */}
              <div className="flex items-center justify-between px-4 py-3 border-b border-slate-700">
                <div className="flex items-center gap-2">
                  <div className={`w-2 h-2 rounded-full ${column.color}`} />
                  <h3 className="text-sm font-medium text-slate-200">{column.title}</h3>
                </div>
                <span className="text-xs text-slate-500 bg-slate-800 px-2 py-0.5 rounded-full">
                  {columnCandidates.length}
                </span>
              </div>

              {/* Cards */}
              <div className="p-2 space-y-2 max-h-[calc(100vh-300px)] overflow-y-auto">
                {columnCandidates.length === 0 ? (
                  <div className="text-center py-8 text-slate-500 text-sm">
                    No candidates
                  </div>
                ) : (
                  columnCandidates.map(renderCard)
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Quick Stats */}
      <div className="mt-6 flex items-center gap-6 text-sm text-slate-400">
        <div>
          <span className="text-slate-500">Total:</span>{" "}
          <span className="text-slate-200">{totalCandidates}</span>
        </div>
        <div>
          <span className="text-slate-500">In Progress:</span>{" "}
          <span className="text-yellow-400">{inProgressCount}</span>
        </div>
        <div>
          <span className="text-slate-500">Recovered:</span>{" "}
          <span className="text-green-400">{recoveredCount}</span>
        </div>
      </div>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        isOpen={isDrawerOpen}
        onClose={handleDrawerClose}
        onStatusChange={handleStatusChange}
      />
    </div>
  );
}
