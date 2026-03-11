/**
 * Content Decay Recovery Board Page
 * Kanban-style board for managing content decay candidates through the recovery workflow
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 */

"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import {
  Settings,
  LayoutDashboard,
  KanbanSquare,
  BarChart3,
  AlertTriangle,
  Filter,
  GripVertical,
  ExternalLink,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetClose,
} from "@/components/ui/sheet";
import { SeverityBadge } from "@/components/content-decay/SeverityBadge";
import { DecayReasonBadges } from "@/components/content-decay/DecayReasonBadges";
import { StatusWorkflowButtons } from "@/components/content-decay/StatusWorkflowButtons";
import {
  useContentDecaySummary,
  useContentDecayCandidates,
  useContentDecayCandidate,
  useUpdateCandidateStatus,
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentDecaySeverity,
  ContentDecayPageType,
  SEVERITY_CONFIG,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

// ============================================================
// Column Configuration
// ============================================================

const COLUMNS: Array<{
  id: ContentDecayCandidateStatus;
  title: string;
  color: string;
}> = [
  { id: "backlog", title: "Backlog", color: "border-slate-500" },
  { id: "planned", title: "Planned", color: "border-blue-500" },
  { id: "in_progress", title: "In Progress", color: "border-yellow-500" },
  { id: "refreshed", title: "Refreshed", color: "border-purple-500" },
  { id: "recovered", title: "Recovered", color: "border-green-500" },
];

// ============================================================
// Loading Skeleton
// ============================================================

function PageSkeleton() {
  return (
    <div className="space-y-6 p-6" data-testid="page-skeleton">
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-10 w-64" />
      </div>
      <div className="grid grid-cols-5 gap-4">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="space-y-2">
            <Skeleton className="h-8 w-24" />
            <Skeleton className="h-32" />
            <Skeleton className="h-24" />
          </div>
        ))}
      </div>
    </div>
  );
}

// ============================================================
// Navigation Tabs
// ============================================================

interface NavTabsProps {
  siteId: string;
  activeTab: string;
}

function NavTabs({ siteId, activeTab }: NavTabsProps) {
  const router = useRouter();

  return (
    <Tabs value={activeTab} className="w-auto">
      <TabsList>
        <TabsTrigger
          value="queue"
          onClick={() => router.push(`/sites/${siteId}/content-decay`)}
          className="gap-1.5"
        >
          <LayoutDashboard className="h-4 w-4" />
          Queue
        </TabsTrigger>
        <TabsTrigger
          value="board"
          onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
          className="gap-1.5"
        >
          <KanbanSquare className="h-4 w-4" />
          Board
        </TabsTrigger>
        <TabsTrigger
          value="recovery"
          onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
          className="gap-1.5"
        >
          <BarChart3 className="h-4 w-4" />
          Recovery
        </TabsTrigger>
      </TabsList>
    </Tabs>
  );
}

// ============================================================
// Candidate Card
// ============================================================

interface CandidateCardProps {
  candidate: DecayCandidate;
  onClick: () => void;
}

function CandidateCard({ candidate, onClick }: CandidateCardProps) {
  return (
    <Card
      data-testid={`candidate-card-${candidate.id}`}
      className="cursor-pointer hover:border-primary/50 transition-colors bg-slate-900 border-slate-700"
      onClick={onClick}
    >
      <CardContent className="p-3 space-y-2">
        {/* Title */}
        <div className="flex items-start justify-between gap-2">
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-slate-200 truncate">
              {candidate.pageTitle || "Untitled"}
            </p>
            <a
              href={candidate.pageUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-xs text-slate-400 hover:text-slate-300 flex items-center gap-1 truncate"
              onClick={(e) => e.stopPropagation()}
            >
              {candidate.pageUrl.length > 30
                ? candidate.pageUrl.substring(0, 30) + "..."
                : candidate.pageUrl}
              <ExternalLink className="h-3 w-3 flex-shrink-0" />
            </a>
          </div>
          <SeverityBadge severity={candidate.severity} size="sm" />
        </div>

        {/* Metrics */}
        <div className="flex items-center justify-between text-xs">
          <span className="text-red-400">
            -{candidate.lostClickEstimate.toLocaleString()} clicks
          </span>
          <span className="text-green-400">
            +{candidate.recoveryUpsideEstimate.toLocaleString()} upside
          </span>
        </div>

        {/* Reasons */}
        <DecayReasonBadges
          reasons={candidate.reasonCodes}
          primaryReason={candidate.primaryReasonCode}
          maxSupporting={1}
          size="sm"
        />
      </CardContent>
    </Card>
  );
}

// ============================================================
// Kanban Column
// ============================================================

interface KanbanColumnProps {
  column: (typeof COLUMNS)[number];
  candidates: DecayCandidate[];
  onCardClick: (candidate: DecayCandidate) => void;
}

function KanbanColumn({ column, candidates, onCardClick }: KanbanColumnProps) {
  return (
    <div
      data-testid={`column-${column.id}`}
      className="flex flex-col min-w-[280px] max-w-[320px]"
    >
      {/* Column Header */}
      <div
        className={cn(
          "flex items-center justify-between p-3 rounded-t-lg border-t-2 bg-slate-800/50",
          column.color
        )}
      >
        <h3 className="text-sm font-medium text-slate-200">{column.title}</h3>
        <Badge variant="secondary" className="text-xs">
          {candidates.length}
        </Badge>
      </div>

      {/* Column Content */}
      <div
        className="flex-1 p-2 space-y-2 bg-slate-800/30 rounded-b-lg border border-t-0 border-slate-700 min-h-[400px]"
        role="list"
        aria-label={`${column.title} candidates`}
      >
        {candidates.length === 0 ? (
          <div className="text-center text-slate-500 text-sm py-8">
            No candidates
          </div>
        ) : (
          candidates.map((candidate) => (
            <CandidateCard
              key={candidate.id}
              candidate={candidate}
              onClick={() => onCardClick(candidate)}
            />
          ))
        )}
      </div>
    </div>
  );
}

// ============================================================
// Recovery Board Content
// ============================================================

function RecoveryBoardContent({ siteId }: { siteId: string }) {
  const router = useRouter();

  // Filter state
  const [severityFilter, setSeverityFilter] = React.useState<ContentDecaySeverity | "all">("all");
  const [pageTypeFilter, setPageTypeFilter] = React.useState<ContentDecayPageType | "all">("all");

  // Hooks
  const {
    summary,
    isLoading: isSummaryLoading,
  } = useContentDecaySummary(siteId);

  const {
    candidates,
    isLoading: isCandidatesLoading,
    mutate: mutateCandidates,
  } = useContentDecayCandidates(siteId, {
    status: "all",
    severity: severityFilter,
    pageType: pageTypeFilter,
  });

  // Selected candidate for detail sheet
  const [selectedCandidateId, setSelectedCandidateId] = React.useState<number | null>(null);

  const {
    candidate: selectedCandidate,
    isLoading: isCandidateLoading,
  } = useContentDecayCandidate(selectedCandidateId ?? 0);

  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);

  // Group candidates by status
  const candidatesByStatus = React.useMemo(() => {
    const grouped: Record<ContentDecayCandidateStatus, DecayCandidate[]> = {
      backlog: [],
      planned: [],
      in_progress: [],
      refreshed: [],
      recovered: [],
      ignored: [],
    };

    candidates.forEach((candidate) => {
      if (candidate.status in grouped) {
        grouped[candidate.status].push(candidate);
      }
    });

    return grouped;
  }, [candidates]);

  // Handlers
  const handleCardClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
  };

  const handleCloseSheet = () => {
    setSelectedCandidateId(null);
  };

  const handleStatusChange = async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      mutateCandidates();
    }
  };

  const isLoading = isSummaryLoading || isCandidatesLoading;

  // Stats at bottom
  const stats = React.useMemo(() => {
    return {
      total: candidates.length,
      critical: candidates.filter((c) => c.severity === "critical").length,
      high: candidates.filter((c) => c.severity === "high").length,
    };
  }, [candidates]);

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <div className="container mx-auto px-4 py-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white flex items-center gap-2">
              <KanbanSquare className="h-6 w-6 text-blue-500" />
              Recovery Board
            </h1>
            <p className="text-sm text-slate-400">
              Drag candidates through the recovery workflow
            </p>
          </div>

          <div className="flex items-center gap-3">
            <NavTabs siteId={siteId} activeTab="board" />
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.push(`/sites/${siteId}/content-decay/settings`)}
              className="border-slate-700 text-slate-300"
            >
              <Settings className="h-4 w-4" />
            </Button>
          </div>
        </div>

        {/* Filters */}
        <div className="flex items-center gap-3 mb-6">
          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-slate-400" />
            <span className="text-sm text-slate-400">Filters:</span>
          </div>

          <Select
            value={severityFilter}
            onValueChange={(v) => setSeverityFilter(v as ContentDecaySeverity | "all")}
          >
            <SelectTrigger className="w-32 bg-slate-800 border-slate-700 text-slate-200">
              <SelectValue placeholder="Severity" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Severities</SelectItem>
              <SelectItem value="critical">Critical</SelectItem>
              <SelectItem value="high">High</SelectItem>
              <SelectItem value="medium">Medium</SelectItem>
              <SelectItem value="low">Low</SelectItem>
            </SelectContent>
          </Select>

          <Select
            value={pageTypeFilter}
            onValueChange={(v) => setPageTypeFilter(v as ContentDecayPageType | "all")}
          >
            <SelectTrigger className="w-36 bg-slate-800 border-slate-700 text-slate-200">
              <SelectValue placeholder="Page Type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem value="blog">Blog</SelectItem>
              <SelectItem value="landing">Landing</SelectItem>
              <SelectItem value="product">Product</SelectItem>
              <SelectItem value="tool">Tool</SelectItem>
              <SelectItem value="docs">Docs</SelectItem>
              <SelectItem value="other">Other</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Kanban Board */}
        {isLoading ? (
          <PageSkeleton />
        ) : (
          <div
            className="flex gap-4 overflow-x-auto pb-4"
            role="region"
            aria-label="Kanban board"
          >
            {COLUMNS.map((column) => (
              <KanbanColumn
                key={column.id}
                column={column}
                candidates={candidatesByStatus[column.id] || []}
                onCardClick={handleCardClick}
              />
            ))}
          </div>
        )}

        {/* Quick Stats */}
        <div className="mt-6 p-4 rounded-lg bg-slate-800/50 border border-slate-700">
          <div className="flex items-center gap-6 text-sm">
            <div>
              <span className="text-slate-400">Total Candidates:</span>
              <span className="ml-2 font-medium text-slate-200">{stats.total}</span>
            </div>
            <div>
              <span className="text-slate-400">Critical:</span>
              <span className="ml-2 font-medium text-red-400">{stats.critical}</span>
            </div>
            <div>
              <span className="text-slate-400">High:</span>
              <span className="ml-2 font-medium text-orange-400">{stats.high}</span>
            </div>
          </div>
        </div>

        {/* Candidate Detail Sheet */}
        <Sheet
          open={selectedCandidateId !== null}
          onOpenChange={(open) => !open && handleCloseSheet()}
        >
          <SheetContent className="w-[500px] sm:max-w-[600px] bg-slate-900 border-slate-700">
            <SheetHeader>
              <SheetTitle className="text-slate-200">
                {selectedCandidate?.pageTitle || "Candidate Details"}
              </SheetTitle>
              <SheetClose asChild>
                <Button variant="ghost" size="icon" className="absolute right-4 top-4">
                  <span className="sr-only">Close</span>
                </Button>
              </SheetClose>
            </SheetHeader>

            {isCandidateLoading ? (
              <div className="space-y-4 pt-4">
                <Skeleton className="h-8 w-3/4" />
                <Skeleton className="h-24 w-full" />
                <Skeleton className="h-24 w-full" />
              </div>
            ) : selectedCandidate ? (
              <div className="space-y-4 pt-4">
                {/* URL */}
                <a
                  href={selectedCandidate.pageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-slate-400 hover:text-slate-300 flex items-center gap-1"
                >
                  {selectedCandidate.pageUrl}
                  <ExternalLink className="h-3 w-3" />
                </a>

                {/* Severity & Metrics */}
                <div className="flex items-center gap-4">
                  <SeverityBadge severity={selectedCandidate.severity} />
                  <span className="text-sm text-red-400">
                    -{selectedCandidate.lostClickEstimate.toLocaleString()} clicks
                  </span>
                  <span className="text-sm text-green-400">
                    +{selectedCandidate.recoveryUpsideEstimate.toLocaleString()} upside
                  </span>
                </div>

                {/* Reasons */}
                <div>
                  <h4 className="text-sm font-medium text-slate-300 mb-2">Reasons</h4>
                  <DecayReasonBadges
                    reasons={selectedCandidate.reasonCodes}
                    primaryReason={selectedCandidate.primaryReasonCode}
                  />
                </div>

                {/* Status Workflow */}
                <div className="pt-4 border-t border-slate-700">
                  <h4 className="text-sm font-medium text-slate-300 mb-2">Status</h4>
                  <StatusWorkflowButtons
                    status={selectedCandidate.status}
                    onStatusChange={handleStatusChange}
                    isLoading={isUpdating}
                  />
                </div>
              </div>
            ) : null}
          </SheetContent>
        </Sheet>
      </div>
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function RecoveryBoardPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  if (!siteId) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <p className="text-slate-400">Site not found</p>
      </div>
    );
  }

  return <RecoveryBoardContent siteId={siteId} />;
}
