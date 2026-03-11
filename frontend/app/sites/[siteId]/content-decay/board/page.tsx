/**
 * Content Decay Recovery Board Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Kanban-style board for managing content decay candidates workflow
 */

'use client';

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { Search, Filter, MoreVertical, ExternalLink, GripVertical } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { CandidateDetailDrawer } from '@/components/content-decay';
import type {
  DecayCandidate,
  ContentDecayCandidateStatus,
  ContentDecaySeverity,
  ContentDecayPageType,
} from '@/lib/types/content-decay';
import {
  useContentDecayCandidates,
  useUpdateCandidateStatus,
  useContentDecayCandidate,
  useContentRefreshTasks,
  useCreateRefreshTask,
} from '@/lib/hooks/useContentDecay';

// ============================================================
// Constants
// ============================================================

const COLUMNS: { id: ContentDecayCandidateStatus; title: string }[] = [
  { id: 'backlog', title: 'Backlog' },
  { id: 'planned', title: 'Planned' },
  { id: 'in_progress', title: 'In Progress' },
  { id: 'refreshed', title: 'Refreshed' },
  { id: 'recovered', title: 'Recovered' },
];

const SEVERITY_COLORS: Record<ContentDecaySeverity, string> = {
  critical: 'bg-red-100 text-red-800 border-red-200',
  high: 'bg-orange-100 text-orange-800 border-orange-200',
  medium: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  low: 'bg-gray-100 text-gray-800 border-gray-200',
};

// ============================================================
// Kanban Card Component
// ============================================================

interface KanbanCardProps {
  candidate: DecayCandidate;
  onDragStart: (e: React.DragEvent, candidate: DecayCandidate) => void;
  onClick: () => void;
}

function KanbanCard({ candidate, onDragStart, onClick }: KanbanCardProps) {
  return (
    <div
      draggable
      onDragStart={(e) => onDragStart(e, candidate)}
      onClick={onClick}
      data-testid={`card-${candidate.id}`}
      data-severity={candidate.severity}
      data-score={candidate.decayScore}
      className="bg-white rounded-lg shadow p-3 cursor-pointer hover:shadow-md transition-shadow border"
    >
      {/* Header */}
      <div className="flex justify-between items-start mb-2">
        <Badge
          data-testid={`card-severity-${candidate.id}`}
          className={SEVERITY_COLORS[candidate.severity]}
        >
          {candidate.severity}
        </Badge>
        <span
          data-testid={`card-score-${candidate.id}`}
          className="font-mono text-sm text-muted-foreground"
        >
          {candidate.decayScore}
        </span>
      </div>

      {/* Title */}
      <h4
        data-testid={`card-title-${candidate.id}`}
        className="font-medium text-sm mb-1 truncate"
      >
        {candidate.pageTitle || 'Untitled'}
      </h4>

      {/* URL */}
      <p
        data-testid={`card-url-${candidate.id}`}
        className="text-xs text-muted-foreground truncate mb-2"
      >
        {candidate.pageUrl.replace(/^https?:\/\/[^\/]+/, '')}
      </p>

      {/* Footer */}
      <div className="flex justify-between items-center text-xs text-muted-foreground">
        <span data-testid={`card-lost-${candidate.id}`}>
          {candidate.lostClickEstimate > 0
            ? `-${candidate.lostClickEstimate.toLocaleString()} clicks`
            : 'No loss'}
        </span>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="sm"
              className="h-6 w-6 p-0"
              data-testid={`card-menu-${candidate.id}`}
              onClick={(e) => e.stopPropagation()}
            >
              <MoreVertical className="h-3 w-3" data-testid="more-icon" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={onClick}>
              View Details
            </DropdownMenuItem>
            <DropdownMenuItem>
              <a
                href={candidate.pageUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center w-full"
                onClick={(e) => e.stopPropagation()}
              >
                <ExternalLink className="h-4 w-4 mr-2" />
                Open Page
              </a>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function RecoveryBoardPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // State
  const [selectedCandidateId, setSelectedCandidateId] = React.useState<number | null>(null);
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);
  const [severityFilter, setSeverityFilter] = React.useState<ContentDecaySeverity | 'all'>('all');
  const [typeFilter, setTypeFilter] = React.useState<ContentDecayPageType | 'all'>('all');
  const [searchQuery, setSearchQuery] = React.useState('');
  const [draggedCandidate, setDraggedCandidate] = React.useState<DecayCandidate | null>(null);

  // Data hooks
  const { candidates, isLoading, mutate } = useContentDecayCandidates(siteId);
  const { candidate: selectedCandidate } = useContentDecayCandidate(selectedCandidateId ?? 0);
  const { tasks } = useContentRefreshTasks(selectedCandidateId ?? 0);
  const { updateStatus } = useUpdateCandidateStatus(selectedCandidateId ?? 0);

  // Filter candidates
  const filteredCandidates = React.useMemo(() => {
    return candidates.filter((c) => {
      if (severityFilter !== 'all' && c.severity !== severityFilter) return false;
      if (typeFilter !== 'all' && c.pageType !== typeFilter) return false;
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        return (
          c.pageUrl.toLowerCase().includes(query) ||
          (c.pageTitle?.toLowerCase().includes(query) ?? false)
        );
      }
      return true;
    });
  }, [candidates, severityFilter, typeFilter, searchQuery]);

  // Group by status
  const candidatesByStatus = React.useMemo(() => {
    const groups: Record<ContentDecayCandidateStatus, DecayCandidate[]> = {
      backlog: [],
      planned: [],
      in_progress: [],
      refreshed: [],
      recovered: [],
      ignored: [],
    };
    for (const c of filteredCandidates) {
      groups[c.status].push(c);
    }
    return groups;
  }, [filteredCandidates]);

  // Handlers
  const handleDragStart = React.useCallback((e: React.DragEvent, candidate: DecayCandidate) => {
    setDraggedCandidate(candidate);
    e.dataTransfer.effectAllowed = 'move';
  }, []);

  const handleDragOver = React.useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }, []);

  const handleDrop = React.useCallback(async (e: React.DragEvent, newStatus: ContentDecayCandidateStatus) => {
    e.preventDefault();
    if (draggedCandidate && draggedCandidate.status !== newStatus) {
      // Update status
      const result = await updateStatus(newStatus);
      if (result.success) {
        toast.success(`Moved to ${newStatus.replace('_', ' ')}`);
        mutate();
      } else {
        toast.error('Failed to update status');
      }
    }
    setDraggedCandidate(null);
  }, [draggedCandidate, updateStatus, mutate]);

  const handleCardClick = React.useCallback((candidate: DecayCandidate) => {
    setSelectedCandidateId(candidate.id);
    setIsDrawerOpen(true);
  }, []);

  const handleCloseDrawer = React.useCallback(() => {
    setIsDrawerOpen(false);
    setSelectedCandidateId(null);
  }, []);

  const handleStatusChange = React.useCallback(async (status: ContentDecayCandidateStatus) => {
    const result = await updateStatus(status);
    if (result.success) {
      toast.success(`Status updated to ${status.replace('_', ' ')}`);
      mutate();
    }
  }, [updateStatus, mutate]);

  // Loading state
  if (isLoading) {
    return (
      <main className="container mx-auto px-4 py-8">
        <div className="flex gap-4 overflow-x-auto pb-4">
          {COLUMNS.map((col) => (
            <div key={col.id} className="flex-shrink-0 w-72">
              <Skeleton className="h-12 mb-4" />
              <div className="space-y-3">
                {[1, 2, 3].map((i) => (
                  <Skeleton key={i} className="h-32" />
                ))}
              </div>
            </div>
          ))}
        </div>
      </main>
    );
  }

  return (
    <main className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-4 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol className="flex items-center gap-2">
          <li>
            <Link href="/sites" className="hover:text-foreground">Sites</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}`} className="hover:text-foreground">Site {siteId}</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}/content-decay`} className="hover:text-foreground">
              Content Decay
            </Link>
          </li>
          <li>/</li>
          <li className="text-foreground font-medium">Board</li>
        </ol>
      </nav>

      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Recovery Board</h1>
        <p className="text-muted-foreground">
          Track and manage content refresh workflow
        </p>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-4 mb-6">
        <Select
          value={severityFilter}
          onValueChange={(v) => setSeverityFilter(v as ContentDecaySeverity | 'all')}
        >
          <SelectTrigger className="w-36" data-testid="severity-select">
            <SelectValue placeholder="All Severity" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Severity</SelectItem>
            <SelectItem value="critical">Critical</SelectItem>
            <SelectItem value="high">High</SelectItem>
            <SelectItem value="medium">Medium</SelectItem>
            <SelectItem value="low">Low</SelectItem>
          </SelectContent>
        </Select>

        <Select
          value={typeFilter}
          onValueChange={(v) => setTypeFilter(v as ContentDecayPageType | 'all')}
        >
          <SelectTrigger className="w-36" data-testid="type-select">
            <SelectValue placeholder="All Types" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Types</SelectItem>
            <SelectItem value="blog">Blog</SelectItem>
            <SelectItem value="product">Product</SelectItem>
            <SelectItem value="landing">Landing</SelectItem>
            <SelectItem value="docs">Docs</SelectItem>
          </SelectContent>
        </Select>

        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search candidates..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9"
            data-testid="search-input"
          />
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="flex gap-4 border-b mb-6">
        <Link
          href={`/sites/${siteId}/content-decay`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Queue
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/board`}
          className="px-4 py-2 border-b-2 border-primary font-medium"
        >
          Board
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/recovery`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Recovery
        </Link>
      </div>

      {/* Kanban Board */}
      <div
        data-testid="kanban-board"
        className="flex gap-4 overflow-x-auto pb-4"
      >
        {COLUMNS.map((column) => (
          <div
            key={column.id}
            data-testid={`column-${column.id}`}
            data-status={column.id}
            className="flex-shrink-0 w-72 bg-muted/50 rounded-lg p-4"
            onDragOver={handleDragOver}
            onDrop={(e) => handleDrop(e, column.id)}
          >
            {/* Column Header */}
            <div
              data-testid={`column-header-${column.id}`}
              className="flex justify-between items-center mb-4"
            >
              <h3
                data-testid={`column-title-${column.id}`}
                className="font-semibold"
              >
                {column.title}
              </h3>
              <Badge
                data-testid={`column-count-${column.id}`}
                variant="secondary"
              >
                {candidatesByStatus[column.id].length}
              </Badge>
            </div>

            {/* Column Cards */}
            <div
              data-testid={`column-cards-${column.id}`}
              data-drop-zone={column.id}
              className="space-y-3 min-h-[200px]"
            >
              {candidatesByStatus[column.id].map((candidate) => (
                <KanbanCard
                  key={candidate.id}
                  candidate={candidate}
                  onDragStart={handleDragStart}
                  onClick={() => handleCardClick(candidate)}
                />
              ))}

              {/* Empty State */}
              {candidatesByStatus[column.id].length === 0 && (
                <div
                  data-testid={`empty-column-${column.id}`}
                  className="text-center text-muted-foreground py-8 border-2 border-dashed rounded-lg"
                >
                  <p className="text-sm">No candidates</p>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Candidate Detail Drawer */}
      <CandidateDetailDrawer
        candidate={selectedCandidate}
        tasks={tasks}
        isOpen={isDrawerOpen}
        onClose={handleCloseDrawer}
        onStatusChange={handleStatusChange}
      />
    </main>
  );
}
