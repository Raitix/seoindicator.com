"use client";

import * as React from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  Columns,
  LayoutDashboard,
  BarChart3,
  AlertTriangle,
  GripVertical,
  ExternalLink,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetClose } from "@/components/ui/sheet";
import { X } from "lucide-react";
import { toast } from "sonner";
import {
  SeverityBadge,
  StatusWorkflowButtons,
} from "@/components/content-decay";
import {
  useContentDecayCandidates,
  useUpdateCandidateStatus,
} from "@/lib/hooks/useContentDecay";
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentDecaySeverity,
  ContentDecayPageType,
} from "@/lib/types/content-decay";
import { SEVERITY_CONFIG, PAGE_TYPE_CONFIG } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

// Kanban columns
const KANBAN_COLUMNS: { id: ContentDecayCandidateStatus; title: string; color: string }[] = [
  { id: "backlog", title: "Backlog", color: "bg-slate-500" },
  { id: "planned", title: "Planned", color: "bg-blue-500" },
  { id: "in_progress", title: "In Progress", color: "bg-yellow-500" },
  { id: "refreshed", title: "Refreshed", color: "bg-purple-500" },
  { id: "recovered", title: "Recovered", color: "bg-green-500" },
];

const SEVERITY_OPTIONS: { value: ContentDecaySeverity | "all"; label: string }[] = [
  { value: "all", label: "All Severities" },
  { value: "critical", label: "Critical" },
  { value: "high", label: "High" },
  { value: "medium", label: "Medium" },
  { value: "low", label: "Low" },
];

const PAGE_TYPE_OPTIONS: { value: ContentDecayPageType | "all"; label: string }[] = [
  { value: "all", label: "All Types" },
  { value: "home", label: "Homepage" },
  { value: "blog", label: "Blog" },
  { value: "landing", label: "Landing Page" },
  { value: "product", label: "Product" },
  { value: "tool", label: "Tool" },
  { value: "compare", label: "Comparison" },
  { value: "docs", label: "Documentation" },
  { value: "other", label: "Other" },
];

function formatNumber(num: number): string {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
  return num.toString();
}

interface KanbanCardProps {
  candidate: DecayCandidate;
  onClick: () => void;
  onDragStart: (e: React.DragEvent, candidate: DecayCandidate) => void;
}

function KanbanCard({ candidate, onClick, onDragStart }: KanbanCardProps) {
  const severityConfig = SEVERITY_CONFIG[candidate.severity];

  return (
    <div
      role="article"
      aria-label={candidate.pageTitle || candidate.pageUrl}
      draggable
      onDragStart={(e) => onDragStart(e, candidate)}
      onClick={onClick}
      className="bg-card border rounded-lg p-3 cursor-pointer hover:shadow-md transition-shadow group"
    >
      <div className="flex items-start justify-between gap-2 mb-2">
        <div className="flex-1 min-w-0">
          <div className="font-medium text-sm truncate">
            {candidate.pageTitle || "Untitled"}
          </div>
          <a
            href={candidate.pageUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-xs text-muted-foreground hover:text-primary truncate block"
            onClick={(e) => e.stopPropagation()}
          >
            {candidate.pageUrl}
          </a>
        </div>
        <GripVertical className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
      </div>

      <div className="flex items-center gap-2 mb-2">
        <SeverityBadge severity={candidate.severity} size="sm" />
        <Badge variant="outline" className="text-xs">
          {PAGE_TYPE_CONFIG[candidate.pageType]?.label ?? candidate.pageType}
        </Badge>
      </div>

      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <span className="text-red-600">-{formatNumber(candidate.lostClickEstimate)} clicks</span>
        <span className="font-mono">Score: {candidate.decayScore.toFixed(1)}</span>
      </div>
    </div>
  );
}

interface KanbanColumnProps {
  column: typeof KANBAN_COLUMNS[0];
  candidates: DecayCandidate[];
  onCardClick: (candidate: DecayCandidate) => void;
  onDragStart: (e: React.DragEvent, candidate: DecayCandidate) => void;
  onDragOver: (e: React.DragEvent) => void;
  onDrop: (e: React.DragEvent, status: ContentDecayCandidateStatus) => void;
  isOver: boolean;
}

function KanbanColumn({
  column,
  candidates,
  onCardClick,
  onDragStart,
  onDragOver,
  onDrop,
  isOver,
}: KanbanColumnProps) {
  return (
    <div
      role="region"
      aria-label={`${column.title} column with ${candidates.length} items`}
      className="flex-1 min-w-[280px] max-w-[350px]"
      onDragOver={onDragOver}
      onDrop={(e) => onDrop(e, column.id)}
    >
      {/* Column Header */}
      <div className="flex items-center gap-2 mb-3 px-1">
        <div className={cn("w-3 h-3 rounded-full", column.color)} />
        <h3 className="font-semibold text-sm">{column.title}</h3>
        <Badge variant="secondary" className="text-xs ml-auto">
          {candidates.length}
        </Badge>
      </div>

      {/* Column Content */}
      <div
        className={cn(
          "space-y-2 min-h-[400px] p-2 rounded-lg transition-colors",
          isOver ? "bg-muted/50 ring-2 ring-primary" : "bg-muted/30"
        )}
      >
        {candidates.map((candidate) => (
          <KanbanCard
            key={candidate.id}
            candidate={candidate}
            onClick={() => onCardClick(candidate)}
            onDragStart={onDragStart}
          />
        ))}
        {candidates.length === 0 && (
          <div className="text-center text-muted-foreground text-sm py-8">
            No items
          </div>
        )}
      </div>
    </div>
  );
}

export default function ContentDecayBoardPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  // State
  const [severityFilter, setSeverityFilter] = React.useState<ContentDecaySeverity | "all">("all");
  const [pageTypeFilter, setPageTypeFilter] = React.useState<ContentDecayPageType | "all">("all");
  const [selectedCandidate, setSelectedCandidate] = React.useState<DecayCandidate | null>(null);
  const [isSheetOpen, setIsSheetOpen] = React.useState(false);
  const [dragOverColumn, setDragOverColumn] = React.useState<ContentDecayCandidateStatus | null>(null);
  const [draggedCandidate, setDraggedCandidate] = React.useState<DecayCandidate | null>(null);

  // Data hooks
  const { candidates, isLoading } = useContentDecayCandidates(siteId);
  const { updateStatus } = useUpdateCandidateStatus(selectedCandidate?.id ?? 0);

  // Filter candidates
  const filteredCandidates = React.useMemo(() => {
    return candidates.filter((c) => {
      if (severityFilter !== "all" && c.severity !== severityFilter) return false;
      if (pageTypeFilter !== "all" && c.pageType !== pageTypeFilter) return false;
      // Only show board statuses (not ignored)
      return !["ignored"].includes(c.status);
    });
  }, [candidates, severityFilter, pageTypeFilter]);

  // Group by status
  const candidatesByStatus = React.useMemo(() => {
    const grouped: Record<ContentDecayCandidateStatus, DecayCandidate[]> = {
      backlog: [],
      planned: [],
      in_progress: [],
      refreshed: [],
      recovered: [],
      ignored: [],
    };
    filteredCandidates.forEach((c) => {
      if (grouped[c.status]) {
        grouped[c.status].push(c);
      }
    });
    return grouped;
  }, [filteredCandidates]);

  // Stats
  const stats = React.useMemo(() => ({
    total: filteredCandidates.length,
    critical: filteredCandidates.filter((c) => c.severity === "critical").length,
    high: filteredCandidates.filter((c) => c.severity === "high").length,
  }), [filteredCandidates]);

  // Handlers
  const handleCardClick = React.useCallback((candidate: DecayCandidate) => {
    setSelectedCandidate(candidate);
    setIsSheetOpen(true);
  }, []);

  const handleDragStart = React.useCallback((e: React.DragEvent, candidate: DecayCandidate) => {
    setDraggedCandidate(candidate);
    e.dataTransfer.effectAllowed = "move";
  }, []);

  const handleDragOver = React.useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  }, []);

  const handleDrop = React.useCallback(async (
    e: React.DragEvent,
    newStatus: ContentDecayCandidateStatus
  ) => {
    e.preventDefault();
    setDragOverColumn(null);

    if (draggedCandidate && draggedCandidate.status !== newStatus) {
      const { updateStatus } = await import("@/lib/hooks/useContentDecay").then((m) =>
        m.useUpdateCandidateStatus(draggedCandidate.id)
      );
      // Direct API call for drag-drop
      try {
        const response = await fetch(`/api/v1/content-decay/candidates/${draggedCandidate.id}`, {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ status: newStatus }),
        });
        if (response.ok) {
          toast.success(`Moved to ${newStatus.replace("_", " ")}`);
        } else {
          toast.error("Failed to update status");
        }
      } catch {
        toast.error("Failed to update status");
      }
    }
    setDraggedCandidate(null);
  }, [draggedCandidate]);

  const handleStatusChange = React.useCallback(async (status: ContentDecayCandidateStatus) => {
    if (!selectedCandidate) return;
    try {
      const response = await fetch(`/api/v1/content-decay/candidates/${selectedCandidate.id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status }),
      });
      if (response.ok) {
        toast.success(`Status updated to ${status.replace("_", " ")}`);
        setIsSheetOpen(false);
        setSelectedCandidate(null);
      } else {
        toast.error("Failed to update status");
      }
    } catch {
      toast.error("Failed to update status");
    }
  }, [selectedCandidate]);

  // Loading state
  if (isLoading) {
    return (
      <div className="container mx-auto py-6 space-y-6">
        <div className="flex items-center justify-between">
          <Skeleton className="h-8 w-48" />
        </div>
        <div className="flex gap-4 overflow-x-auto pb-4">
          {KANBAN_COLUMNS.map((col) => (
            <div key={col.id} className="flex-1 min-w-[280px]">
              <Skeleton className="h-8 w-32 mb-3" />
              <Skeleton className="h-[400px] w-full rounded-lg" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-6 space-y-6">
      {/* Header */}
      <header className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Recovery Board</h1>
          <p className="text-muted-foreground text-sm">
            Drag cards between columns to update status
          </p>
        </div>

        {/* Filters */}
        <div className="flex items-center gap-3">
          <Select
            value={severityFilter}
            onValueChange={(v) => setSeverityFilter(v as ContentDecaySeverity | "all")}
          >
            <SelectTrigger
              className="w-36"
              aria-label="Filter by severity"
            >
              <SelectValue placeholder="Severity" />
            </SelectTrigger>
            <SelectContent>
              {SEVERITY_OPTIONS.map((opt) => (
                <SelectItem key={opt.value} value={opt.value}>
                  {opt.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select
            value={pageTypeFilter}
            onValueChange={(v) => setPageTypeFilter(v as ContentDecayPageType | "all")}
          >
            <SelectTrigger
              className="w-36"
              aria-label="Filter by page type"
            >
              <SelectValue placeholder="Page Type" />
            </SelectTrigger>
            <SelectContent>
              {PAGE_TYPE_OPTIONS.map((opt) => (
                <SelectItem key={opt.value} value={opt.value}>
                  {opt.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </header>

      {/* Navigation Tabs */}
      <Tabs defaultValue="board" className="w-full">
        <TabsList>
          <TabsTrigger value="queue" asChild>
            <Link href={`/sites/${siteId}/content-decay`}>
              <LayoutDashboard className="h-4 w-4 mr-2" />
              Queue
            </Link>
          </TabsTrigger>
          <TabsTrigger value="board" asChild>
            <Link href={`/sites/${siteId}/content-decay/board`}>
              <Columns className="h-4 w-4 mr-2" />
              Board
            </Link>
          </TabsTrigger>
          <TabsTrigger value="recovery" asChild>
            <Link href={`/sites/${siteId}/content-decay/recovery`}>
              <BarChart3 className="h-4 w-4 mr-2" />
              Recovery
            </Link>
          </TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Quick Stats */}
      <div className="flex items-center gap-4 text-sm">
        <span className="text-muted-foreground">
          Total: <span className="font-medium text-foreground">{stats.total}</span>
        </span>
        {stats.critical > 0 && (
          <span className="text-red-600 flex items-center gap-1">
            <AlertTriangle className="h-4 w-4" />
            {stats.critical} Critical
          </span>
        )}
        {stats.high > 0 && (
          <span className="text-orange-600">
            {stats.high} High
          </span>
        )}
      </div>

      {/* Kanban Board */}
      <div className="flex gap-4 overflow-x-auto pb-4">
        {KANBAN_COLUMNS.map((column) => (
          <KanbanColumn
            key={column.id}
            column={column}
            candidates={candidatesByStatus[column.id]}
            onCardClick={handleCardClick}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDrop={handleDrop}
            isOver={dragOverColumn === column.id}
          />
        ))}
      </div>

      {/* Candidate Detail Sheet */}
      <Sheet open={isSheetOpen} onOpenChange={setIsSheetOpen}>
        <SheetContent className="w-[400px] sm:max-w-[500px]">
          <SheetHeader>
            <SheetTitle>
              {selectedCandidate?.pageTitle || "Candidate Details"}
            </SheetTitle>
            <SheetClose asChild>
              <Button variant="ghost" size="icon" className="absolute right-4 top-4">
                <X className="h-4 w-4" />
              </Button>
            </SheetClose>
          </SheetHeader>

          {selectedCandidate && (
            <div className="mt-6 space-y-4">
              <div className="flex items-center gap-2">
                <SeverityBadge severity={selectedCandidate.severity} />
                <Badge variant="outline">
                  {selectedCandidate.status.replace("_", " ")}
                </Badge>
              </div>

              <div className="text-sm">
                <span className="text-muted-foreground">URL:</span>
                <a
                  href={selectedCandidate.pageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary hover:underline ml-2 flex items-center gap-1"
                >
                  {selectedCandidate.pageUrl}
                  <ExternalLink className="h-3 w-3" />
                </a>
              </div>

              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-muted-foreground">Decay Score:</span>
                  <span className="font-medium ml-2">{selectedCandidate.decayScore.toFixed(1)}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Lost Clicks:</span>
                  <span className="font-medium text-red-600 ml-2">
                    -{formatNumber(selectedCandidate.lostClickEstimate)}
                  </span>
                </div>
              </div>

              <div className="pt-4 border-t">
                <h4 className="font-medium text-sm mb-3">Change Status</h4>
                <StatusWorkflowButtons
                  status={selectedCandidate.status}
                  onStatusChange={handleStatusChange}
                />
              </div>
            </div>
          )}
        </SheetContent>
      </Sheet>
    </div>
  );
}
