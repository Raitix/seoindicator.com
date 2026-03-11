"use client";

import { Suspense, useState, useMemo } from "react";
import { useParams, useRouter } from "next/navigation";
import { Metadata } from "next";
import { ArrowLeft, Filter, AlertTriangle, TrendingDown, Clock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";
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
import { X, ExternalLink } from "lucide-react";
import {
  SeverityBadge,
  DecayReasonBadges,
  StatusWorkflowButtons,
} from "@/components/content-decay";
import {
  useContentDecayCandidates,
  useContentDecayCandidate,
  useUpdateCandidateStatus,
  useContentRefreshTasks,
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentDecaySeverity,
  ContentDecayPageType,
} from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Recovery Board | SEOIndicator",
  description: "Kanban-style board for managing content decay recovery workflow.",
};

const KANBAN_COLUMNS: { id: ContentDecayCandidateStatus; title: string; color: string }[] = [
  { id: "backlog", title: "Backlog", color: "bg-slate-500" },
  { id: "planned", title: "Planned", color: "bg-blue-500" },
  { id: "in_progress", title: "In Progress", color: "bg-yellow-500" },
  { id: "refreshed", title: "Refreshed", color: "bg-purple-500" },
  { id: "recovered", title: "Recovered", color: "bg-green-500" },
];

function formatNumber(num: number): string {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
  return num.toString();
}

function KanbanCard({
  candidate,
  onClick,
}: {
  candidate: DecayCandidate;
  onClick: () => void;
}) {
  return (
    <Card
      role="button"
      tabIndex={0}
      aria-label={`${candidate.pageTitle || candidate.pageUrl} - ${candidate.severity} severity`}
      className="cursor-pointer hover:border-primary/50 transition-colors"
      onClick={onClick}
      onKeyDown={(e) => e.key === "Enter" && onClick()}
      data-testid={`kanban-card-${candidate.id}`}
    >
      <CardContent className="p-3 space-y-2">
        {/* Title */}
        <div className="font-medium text-sm truncate" data-testid="card-title">
          {candidate.pageTitle || "Untitled"}
        </div>

        {/* URL */}
        <a
          href={candidate.pageUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="text-xs text-muted-foreground hover:text-primary truncate block"
          onClick={(e) => e.stopPropagation()}
          data-testid="card-url"
        >
          {candidate.pageUrl}
        </a>

        {/* Badges */}
        <div className="flex flex-wrap items-center gap-1">
          <SeverityBadge severity={candidate.severity} size="sm" />
          <DecayReasonBadges
            reasons={candidate.reasonCodes}
            primaryReason={candidate.primaryReasonCode}
            maxSupporting={1}
            size="sm"
          />
        </div>

        {/* Metrics */}
        <div className="flex items-center justify-between text-xs">
          <span className="text-red-500 flex items-center gap-1">
            <TrendingDown className="h-3 w-3" />
            {formatNumber(candidate.lostClickEstimate)}
          </span>
          <span className="text-muted-foreground flex items-center gap-1">
            <AlertTriangle className="h-3 w-3" />
            {candidate.decayScore.toFixed(1)}
          </span>
        </div>
      </CardContent>
    </Card>
  );
}

function KanbanColumn({
  column,
  candidates,
  onCardClick,
}: {
  column: typeof KANBAN_COLUMNS[0];
  candidates: DecayCandidate[];
  onCardClick: (candidate: DecayCandidate) => void;
}) {
  return (
    <div
      className="flex flex-col min-w-[280px] max-w-[320px]"
      data-testid={`kanban-column-${column.id}`}
      role="region"
      aria-label={`${column.title} column`}
    >
      {/* Column Header */}
      <div className="flex items-center justify-between mb-3 px-1">
        <div className="flex items-center gap-2">
          <div className={cn("w-3 h-3 rounded-full", column.color)} />
          <h3 className="font-medium text-white">{column.title}</h3>
        </div>
        <Badge variant="secondary" className="text-xs" data-testid="column-count">
          {candidates.length}
        </Badge>
      </div>

      {/* Cards Container */}
      <div
        className="flex-1 space-y-2 overflow-y-auto max-h-[calc(100vh-300px)] p-1"
        data-testid="column-cards"
      >
        {candidates.length === 0 ? (
          <div className="text-center text-muted-foreground text-sm py-8 border border-dashed rounded-lg">
            No items
          </div>
        ) : (
          candidates.map((candidate) => (
            <KanbanCard
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

function RecoveryBoardContent({ siteId }: { siteId: string }) {
  const router = useRouter();
  const [selectedCandidateId, setSelectedCandidateId] = useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [severityFilter, setSeverityFilter] = useState<ContentDecaySeverity | "all">("all");
  const [pageTypeFilter, setPageTypeFilter] = useState<ContentDecayPageType | "all">("all");

  // Data hooks
  const { candidates, isLoading } = useContentDecayCandidates(siteId, {
    status: "all",
    severity: severityFilter,
    pageType: pageTypeFilter,
    search: "",
    sort: "decay_score",
    order: "desc",
  });
  const { candidate: selectedCandidate, isLoading: isCandidateLoading } = useContentDecayCandidate(selectedCandidateId ?? 0);
  const { tasks } = useContentRefreshTasks(selectedCandidateId ?? 0);
  const { updateStatus, isUpdating } = useUpdateCandidateStatus(selectedCandidateId ?? 0);

  // Group candidates by status
  const candidatesByStatus = useMemo(() => {
    const grouped: Record<ContentDecayCandidateStatus, DecayCandidate[]> = {
      backlog: [],
      planned: [],
      in_progress: [],
      refreshed: [],
      recovered: [],
      ignored: [],
    };

    candidates.forEach((candidate) => {
      if (grouped[candidate.status]) {
        grouped[candidate.status].push(candidate);
      }
    });

    return grouped;
  }, [candidates]);

  // Handlers
  const handleCardClick = (candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  };

  const handleStatusChange = async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      handleDrawerClose();
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.push(`/sites/${siteId}/content-decay`)}
            data-testid="back-button"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Queue
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-white">Recovery Board</h1>
            <p className="text-sm text-slate-400">
              Kanban workflow for content recovery
            </p>
          </div>
        </div>

        {/* Filters */}
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-muted-foreground" />
          <Select
            value={severityFilter}
            onValueChange={(v) => setSeverityFilter(v as ContentDecaySeverity | "all")}
          >
            <SelectTrigger className="w-32" aria-label="Filter by severity">
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
            <SelectTrigger className="w-36" aria-label="Filter by page type">
              <SelectValue placeholder="Page Type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem value="blog">Blog</SelectItem>
              <SelectItem value="landing">Landing</SelectItem>
              <SelectItem value="product">Product</SelectItem>
              <SelectItem value="tool">Tool</SelectItem>
              <SelectItem value="compare">Comparison</SelectItem>
              <SelectItem value="docs">Documentation</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Kanban Board */}
      <div
        className="flex gap-4 overflow-x-auto pb-4"
        role="region"
        aria-label="Kanban board"
        data-testid="kanban-board"
      >
        {isLoading ? (
          // Loading skeletons
          KANBAN_COLUMNS.map((col) => (
            <div key={col.id} className="min-w-[280px] max-w-[320px]">
              <div className="flex items-center gap-2 mb-3 px-1">
                <Skeleton className="w-3 h-3 rounded-full" data-testid="skeleton" />
                <Skeleton className="h-5 w-24" data-testid="skeleton" />
              </div>
              <div className="space-y-2">
                {Array.from({ length: 3 }).map((_, i) => (
                  <Skeleton key={i} className="h-32 w-full" data-testid="skeleton" />
                ))}
              </div>
            </div>
          ))
        ) : (
          KANBAN_COLUMNS.map((column) => (
            <KanbanColumn
              key={column.id}
              column={column}
              candidates={candidatesByStatus[column.id]}
              onCardClick={handleCardClick}
            />
          ))
        )}
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-4 border-t border-slate-800 pt-4">
        {KANBAN_COLUMNS.map((col) => (
          <div key={col.id} className="text-center">
            <div className={cn("w-3 h-3 rounded-full mx-auto mb-1", col.color)} />
            <div className="text-lg font-bold text-white" data-testid={`stat-${col.id}`}>
              {candidatesByStatus[col.id].length}
            </div>
            <div className="text-xs text-muted-foreground">{col.title}</div>
          </div>
        ))}
      </div>

      {/* Detail Sheet */}
      <Sheet open={isDrawerOpen} onOpenChange={(open) => !open && handleDrawerClose()}>
        <SheetContent
          className="w-[400px] sm:max-w-[500px]"
          data-testid="detail-sheet"
        >
          <SheetHeader>
            <div className="flex items-start justify-between">
              <SheetTitle className="text-left truncate pr-4">
                {selectedCandidate?.pageTitle || selectedCandidate?.pageUrl || "Candidate Details"}
              </SheetTitle>
              <SheetClose asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8">
                  <X className="h-4 w-4" />
                </Button>
              </SheetClose>
            </div>
          </SheetHeader>

          {selectedCandidate && (
            <div className="space-y-4 mt-4">
              {/* Quick Info */}
              <div className="flex items-center gap-2">
                <SeverityBadge severity={selectedCandidate.severity} />
                <Badge variant="outline">{selectedCandidate.status.replace("_", " ")}</Badge>
              </div>

              {/* URL */}
              <a
                href={selectedCandidate.pageUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-sm text-muted-foreground hover:text-primary flex items-center gap-1"
              >
                {selectedCandidate.pageUrl}
                <ExternalLink className="h-3 w-3" />
              </a>

              {/* Metrics */}
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-muted-foreground">Lost Clicks:</span>
                  <div className="text-red-500 font-medium">
                    {formatNumber(selectedCandidate.lostClickEstimate)}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground">Recovery Upside:</span>
                  <div className="text-green-500 font-medium">
                    {formatNumber(selectedCandidate.recoveryUpsideEstimate)}
                  </div>
                </div>
              </div>

              {/* Status Actions */}
              <div className="border-t pt-4">
                <StatusWorkflowButtons
                  status={selectedCandidate.status}
                  onStatusChange={handleStatusChange}
                  isUpdating={isUpdating}
                />
              </div>
            </div>
          )}
        </SheetContent>
      </Sheet>
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <Skeleton className="h-10 w-48" data-testid="skeleton" />
        <div className="flex gap-2">
          <Skeleton className="h-10 w-32" data-testid="skeleton" />
          <Skeleton className="h-10 w-32" data-testid="skeleton" />
        </div>
      </div>
      <div className="flex gap-4 overflow-x-auto">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="min-w-[280px]">
            <Skeleton className="h-8 w-24 mb-3" data-testid="skeleton" />
            <div className="space-y-2">
              {Array.from({ length: 3 }).map((_, j) => (
                <Skeleton key={j} className="h-32 w-full" data-testid="skeleton" />
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function RecoveryBoardPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-full">
        <Suspense fallback={<LoadingSkeleton />}>
          <RecoveryBoardContent siteId={siteId} />
        </Suspense>
      </div>
    </div>
  );
}
