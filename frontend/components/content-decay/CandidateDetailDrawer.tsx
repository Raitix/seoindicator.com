/**
 * CandidateDetailDrawer Component
 * Detailed view for content decay candidates with tabs for signals, tasks, notes, and AI brief
 * 
 * @see TASK-36-12 for implementation requirements
 */

"use client";

import * as React from "react";
import {
  X,
  ExternalLink,
  AlertTriangle,
  TrendingDown,
  Clock,
  FileText,
  BarChart3,
  CheckCircle2,
  AlertCircle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetClose,
} from "@/components/ui/sheet";
import { SeverityBadge } from "./SeverityBadge";
import { DecayReasonBadges } from "./DecayReasonBadges";
import { DecayTrendMiniChart } from "./DecayTrendMiniChart";
import { RefreshTaskList } from "./RefreshTaskList";
import { RecoveryComparisonCard } from "./RecoveryComparisonCard";
import { StatusWorkflowButtons } from "./StatusWorkflowButtons";
import type {
  DecayCandidate,
  ContentRefreshTask,
  RecoveryComparison,
  AiRefreshBrief,
  ContentDecayCandidateStatus,
  ContentRefreshTaskCreateInput,
} from "@/lib/types/content-decay";
import { DECAY_REASON_CONFIG, SEVERITY_CONFIG, PAGE_TYPE_CONFIG } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface CandidateDetailDrawerProps {
  /** Candidate data to display */
  candidate: DecayCandidate | null;
  /** Tasks for this candidate */
  tasks?: ContentRefreshTask[];
  /** Recovery comparison data */
  comparison?: RecoveryComparison | null;
  /** AI brief data */
  aiBrief?: AiRefreshBrief | null;
  /** Is the drawer open */
  isOpen: boolean;
  /** Callback when drawer closes */
  onClose: () => void;
  /** Callback when status changes */
  onStatusChange?: (status: ContentDecayCandidateStatus) => void;
  /** Callback when a task is created */
  onCreateTask?: (task: ContentRefreshTaskCreateInput) => void;
  /** Callback when a task is updated */
  onUpdateTask?: (taskId: number, updates: Partial<ContentRefreshTask>) => void;
  /** Callback when a task is deleted */
  onDeleteTask?: (taskId: number) => void;
  /** Callback to request AI brief generation */
  onRequestAiBrief?: () => void;
  /** Callback to regenerate AI brief */
  onRegenerateAiBrief?: () => void;
  /** Show loading state */
  isLoading?: boolean;
  /** Error message to display */
  error?: string | null;
  /** Available users for task assignment */
  users?: Array<{ id: number; name: string }>;
}

function formatDate(dateStr: string): string {
  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return dateStr;
  }
}

function formatNumber(num: number): string {
  return num.toLocaleString("en-US");
}

export function CandidateDetailDrawer({
  candidate,
  tasks = [],
  comparison,
  aiBrief,
  isOpen,
  onClose,
  onStatusChange,
  onCreateTask,
  onUpdateTask,
  onDeleteTask,
  onRequestAiBrief,
  onRegenerateAiBrief,
  isLoading = false,
  error = null,
  users = [],
}: CandidateDetailDrawerProps) {
  const [activeTab, setActiveTab] = React.useState("overview");

  // Handle ESC key
  React.useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen) {
        onClose();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, onClose]);

  if (isLoading) {
    return (
      <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
        <SheetContent
          data-testid="drawer-loading"
          role="dialog"
          aria-modal="true"
          className="w-[500px] sm:max-w-[600px]"
        >
          <div className="space-y-4 pt-6">
            <Skeleton data-testid="skeleton-title" className="h-8 w-3/4" />
            <Skeleton data-testid="skeleton-content" className="h-32 w-full" />
            <Skeleton className="h-24 w-full" />
            <Skeleton className="h-24 w-full" />
          </div>
        </SheetContent>
      </Sheet>
    );
  }

  if (error) {
    return (
      <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
        <SheetContent
          data-testid="drawer-error"
          role="dialog"
          aria-modal="true"
          className="w-[500px] sm:max-w-[600px]"
        >
          <div className="flex flex-col items-center justify-center h-full gap-4">
            <AlertCircle className="h-12 w-12 text-red-500" />
            <p className="text-red-600">{error}</p>
            <Button onClick={onClose}>Close</Button>
          </div>
        </SheetContent>
      </Sheet>
    );
  }

  if (!candidate) return null;

  const primaryReason = candidate.reasonCodes.find((r) => r.isPrimary);
  const supportingReasons = candidate.reasonCodes.filter((r) => !r.isPrimary);
  const severityConfig = SEVERITY_CONFIG[candidate.severity];
  const pageTypeConfig = PAGE_TYPE_CONFIG[candidate.pageType];

  return (
    <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <SheetContent
        data-testid="candidate-detail-drawer"
        role="dialog"
        aria-modal="true"
        aria-label={`Details for ${candidate.pageTitle || candidate.pageUrl}`}
        className="w-[500px] sm:max-w-[600px] p-0 flex flex-col"
      >
        {/* Header */}
        <SheetHeader className="p-4 border-b space-y-3">
          <div className="flex items-start justify-between gap-2">
            <div className="flex-1 min-w-0">
              {/* Page URL Link */}
              <a
                href={candidate.pageUrl}
                target="_blank"
                rel="noopener noreferrer"
                data-testid="page-url-link"
                className="text-sm text-muted-foreground hover:text-primary flex items-center gap-1 truncate"
              >
                {candidate.pageUrl}
                <ExternalLink className="h-3 w-3 flex-shrink-0" data-testid="icon-external-link" />
              </a>

              {/* Page Title */}
              <SheetTitle data-testid="page-title" className="text-lg font-semibold mt-1 truncate">
                {candidate.pageTitle || "Untitled Page"}
              </SheetTitle>
            </div>

            <SheetClose asChild>
              <Button
                variant="ghost"
                size="icon"
                data-testid="close-button"
                aria-label="Close drawer"
              >
                <X className="h-4 w-4" data-testid="icon-x" />
              </Button>
            </SheetClose>
          </div>

          {/* Badges Row */}
          <div className="flex flex-wrap items-center gap-2">
            <Badge
              data-testid="page-type-badge"
              variant="outline"
              className="text-xs"
            >
              {pageTypeConfig.label}
            </Badge>
            <SeverityBadge
              severity={candidate.severity}
              data-testid="severity-badge"
              data-severity={candidate.severity}
            />
            <Badge
              data-testid="status-badge"
              data-status={candidate.status}
              variant="outline"
              className="text-xs"
            >
              {candidate.status.replace("_", " ")}
            </Badge>
          </div>

          {/* Decay Score & Confidence */}
          <div className="flex items-center gap-4 text-sm">
            <div className="flex items-center gap-1.5">
              <AlertTriangle className={cn("h-4 w-4", severityConfig.colorClass.split(" ")[0])} />
              <span className="font-medium">Decay Score:</span>
              <span data-testid="decay-score" className="font-bold">
                {candidate.decayScore}
              </span>
            </div>
            <div className="flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4 text-green-500" />
              <span className="text-muted-foreground">Confidence:</span>
              <span data-testid="detection-confidence">
                {Math.round(candidate.detectionConfidence * 100)}%
              </span>
            </div>
          </div>

          {/* Estimates */}
          <div className="flex items-center gap-4 text-sm">
            <div className="flex items-center gap-1.5 text-red-600">
              <TrendingDown className="h-4 w-4" />
              <span data-testid="lost-clicks">{formatNumber(candidate.lostClickEstimate)} Lost Clicks</span>
            </div>
            <div className="flex items-center gap-1.5 text-green-600">
              <BarChart3 className="h-4 w-4" />
              <span data-testid="recovery-upside">{formatNumber(candidate.recoveryUpsideEstimate)} Upside</span>
            </div>
          </div>
        </SheetHeader>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="flex-1 flex flex-col min-h-0">
          <div className="border-b px-4">
            <TabsList data-testid="tabs-container" className="h-10">
              <TabsTrigger
                data-testid="tab-overview"
                value="overview"
                aria-selected={activeTab === "overview"}
              >
                Overview
              </TabsTrigger>
              <TabsTrigger
                data-testid="tab-signals"
                value="signals"
                aria-selected={activeTab === "signals"}
              >
                Signals
              </TabsTrigger>
              <TabsTrigger
                data-testid="tab-tasks"
                value="tasks"
                aria-selected={activeTab === "tasks"}
              >
                Tasks ({tasks.length})
              </TabsTrigger>
              <TabsTrigger
                data-testid="tab-notes"
                value="notes"
                aria-selected={activeTab === "notes"}
              >
                Notes
              </TabsTrigger>
              <TabsTrigger
                data-testid="tab-ai-brief"
                value="ai-brief"
                aria-selected={activeTab === "ai-brief"}
              >
                AI Brief
              </TabsTrigger>
            </TabsList>
          </div>

          <ScrollArea className="flex-1">
            {/* Overview Tab */}
            <TabsContent data-testid="tab-content-overview" value="overview" className="p-4 space-y-4 m-0">
              {/* Reasons Section */}
              <div data-testid="reasons-section">
                <h3 className="text-sm font-medium mb-2">Detection Reasons</h3>
                {primaryReason && (
                  <div className="mb-2">
                    <span className="text-xs text-muted-foreground">Primary:</span>
                    <Badge
                      data-testid="primary-reason"
                      variant="outline"
                      className="ml-2"
                    >
                      {DECAY_REASON_CONFIG[primaryReason.type]?.label || primaryReason.type}
                    </Badge>
                  </div>
                )}
                {supportingReasons.length > 0 && (
                  <div data-testid="supporting-reasons" className="flex flex-wrap gap-1">
                    {supportingReasons.map((reason, i) => (
                      <Badge
                        key={i}
                        data-testid={`reason-${reason.type}`}
                        variant="secondary"
                        className="text-xs"
                      >
                        {DECAY_REASON_CONFIG[reason.type]?.label || reason.type}
                      </Badge>
                    ))}
                  </div>
                )}
              </div>

              <Separator />

              {/* Detection Dates */}
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-muted-foreground">First Detected:</span>
                  <div data-testid="first-detected" className="flex items-center gap-1 mt-1">
                    <Clock className="h-3 w-3" />
                    {formatDate(candidate.firstDetectedAt)}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground">Last Detected:</span>
                  <div data-testid="last-detected" className="flex items-center gap-1 mt-1">
                    <Clock className="h-3 w-3" />
                    {formatDate(candidate.lastDetectedAt)}
                  </div>
                </div>
              </div>

              {/* Signals Table */}
              {candidate.signals ? (
                <div className="mt-4">
                  <h3 className="text-sm font-medium mb-2">Signal Comparison</h3>
                  <table data-testid="signals-table" className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        <th className="text-left py-2 text-muted-foreground">Metric</th>
                        <th className="text-right py-2 text-muted-foreground">Previous</th>
                        <th className="text-right py-2 text-muted-foreground">Current</th>
                        <th className="text-right py-2 text-muted-foreground">Change</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr className="border-b">
                        <td className="py-2">Clicks</td>
                        <td data-testid="prev-clicks" className="text-right">{formatNumber(candidate.signals.previousClicks)}</td>
                        <td data-testid="curr-clicks" className="text-right">{formatNumber(candidate.signals.currentClicks)}</td>
                        <td data-testid="delta-clicks" className="text-right text-red-600">
                          {candidate.signals.currentClicks - candidate.signals.previousClicks}
                        </td>
                      </tr>
                      <tr className="border-b">
                        <td className="py-2">Impressions</td>
                        <td data-testid="prev-impressions" className="text-right">{formatNumber(candidate.signals.previousImpressions)}</td>
                        <td data-testid="curr-impressions" className="text-right">{formatNumber(candidate.signals.currentImpressions)}</td>
                        <td className="text-right text-red-600">
                          {candidate.signals.currentImpressions - candidate.signals.previousImpressions}
                        </td>
                      </tr>
                      <tr className="border-b">
                        <td className="py-2">CTR</td>
                        <td className="text-right">{(candidate.signals.previousCtr * 100).toFixed(2)}%</td>
                        <td className="text-right">{(candidate.signals.currentCtr * 100).toFixed(2)}%</td>
                        <td className="text-right text-red-600">
                          {((candidate.signals.currentCtr - candidate.signals.previousCtr) * 100).toFixed(2)}%
                        </td>
                      </tr>
                      {candidate.signals.previousAvgPosition !== null && candidate.signals.currentAvgPosition !== null && (
                        <tr className="border-b">
                          <td className="py-2">Avg Position</td>
                          <td className="text-right">{candidate.signals.previousAvgPosition.toFixed(1)}</td>
                          <td className="text-right">{candidate.signals.currentAvgPosition.toFixed(1)}</td>
                          <td className="text-right text-red-600">
                            {(candidate.signals.currentAvgPosition - candidate.signals.previousAvgPosition).toFixed(1)}
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div data-testid="no-signals" className="text-center text-muted-foreground py-4">
                  No signal data available
                </div>
              )}

              {/* Trend Chart */}
              {candidate.trend && (
                <div data-testid="trend-chart" aria-label="Trend visualization" className="mt-4">
                  <h3 className="text-sm font-medium mb-2">Trend</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div data-testid="clicks-trend">
                      <div className="text-xs text-muted-foreground mb-1">Clicks Trend</div>
                      <DecayTrendMiniChart
                        data={candidate.trend}
                        showClicks={true}
                        showImpressions={false}
                        height={60}
                      />
                    </div>
                    <div data-testid="impressions-trend">
                      <div className="text-xs text-muted-foreground mb-1">Impressions Trend</div>
                      <DecayTrendMiniChart
                        data={candidate.trend}
                        showClicks={false}
                        showImpressions={true}
                        height={60}
                      />
                    </div>
                  </div>
                </div>
              )}
            </TabsContent>

            {/* Signals Tab */}
            <TabsContent value="signals" className="p-4 m-0">
              {candidate.signals ? (
                <RecoveryComparisonCard comparison={comparison ?? null} />
              ) : (
                <div className="text-center text-muted-foreground py-8">
                  No signal data available
                </div>
              )}
            </TabsContent>

            {/* Tasks Tab */}
            <TabsContent value="tasks" className="p-4 m-0">
              <RefreshTaskList
                candidateId={candidate.id}
                tasks={tasks}
                onCreateTask={onCreateTask || (() => {})}
                onUpdateTask={onUpdateTask || (() => {})}
                onDeleteTask={onDeleteTask || (() => {})}
                users={users}
              />
            </TabsContent>

            {/* Notes Tab */}
            <TabsContent value="notes" className="p-4 m-0">
              <div className="text-center text-muted-foreground py-8">
                <FileText className="h-8 w-8 mx-auto mb-2 opacity-50" />
                <p>Notes feature coming soon</p>
              </div>
            </TabsContent>

            {/* AI Brief Tab */}
            <TabsContent value="ai-brief" className="p-4 m-0">
              {/* AI Brief Panel will be rendered here */}
              <div className="text-center text-muted-foreground py-8">
                <p>AI Brief panel needed</p>
              </div>
            </TabsContent>
          </ScrollArea>
        </Tabs>

        {/* Footer with Status Workflow */}
        <div className="border-t p-4">
          <div data-testid="status-workflow">
            <StatusWorkflowButtons
              status={candidate.status}
              onStatusChange={onStatusChange || (() => {})}
            />
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
}
