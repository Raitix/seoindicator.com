/**
 * StatusWorkflowButtons Component
 * Status transition buttons for content decay candidates
 * 
 * @see TASK-36-12 for implementation requirements
 */

"use client";

import * as React from "react";
import {
  ArrowRight,
  RotateCcw,
  XCircle,
  CheckCircle,
  PlayCircle,
  Loader2,
  Clock,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import type { ContentDecayCandidateStatus } from "@/lib/types/content-decay";
import { VALID_STATUS_TRANSITIONS } from "@/lib/types/content-decay";
import { cn } from "@/lib/utils";

export interface StatusWorkflowButtonsProps {
  /** Current status of the candidate */
  status: ContentDecayCandidateStatus;
  /** Callback when status changes */
  onStatusChange: (status: ContentDecayCandidateStatus) => void;
  /** Show loading state */
  isLoading?: boolean;
  /** Disable all buttons */
  disabled?: boolean;
  /** Status history for display */
  history?: Array<{
    status: string;
    changedAt: string;
    changedBy: string;
  }>;
  /** Additional CSS classes */
  className?: string;
}

const STATUS_CONFIG: Record<ContentDecayCandidateStatus, { label: string; color: string }> = {
  backlog: { label: "Backlog", color: "bg-gray-100 text-gray-800 border-gray-200" },
  planned: { label: "Planned", color: "bg-blue-100 text-blue-800 border-blue-200" },
  in_progress: { label: "In Progress", color: "bg-yellow-100 text-yellow-800 border-yellow-200" },
  refreshed: { label: "Refreshed", color: "bg-purple-100 text-purple-800 border-purple-200" },
  recovered: { label: "Recovered", color: "bg-green-100 text-green-800 border-green-200" },
  ignored: { label: "Ignored", color: "bg-red-100 text-red-800 border-red-200" },
};

const TRANSITION_CONFIG: Record<string, { next: ContentDecayCandidateStatus; label: string }> = {
  backlog: { next: "planned", label: "Plan Refresh" },
  planned: { next: "in_progress", label: "Start Work" },
  in_progress: { next: "refreshed", label: "Mark Refreshed" },
  refreshed: { next: "recovered", label: "Confirm Recovery" },
};

export function StatusWorkflowButtons({
  status,
  onStatusChange,
  isLoading = false,
  disabled = false,
  history,
  className,
}: StatusWorkflowButtonsProps) {
  const [showConfirmDialog, setShowConfirmDialog] = React.useState(false);
  const [pendingStatus, setPendingStatus] = React.useState<ContentDecayCandidateStatus | null>(null);

  const canIgnore = ["backlog", "planned"].includes(status);
  const isIgnored = status === "ignored";
  const isRecovered = status === "recovered";
  const hasTransition = TRANSITION_CONFIG[status] !== undefined;

  const handlePrimaryAction = () => {
    if (TRANSITION_CONFIG[status]) {
      onStatusChange(TRANSITION_CONFIG[status].next);
    }
  };

  const handleIgnore = () => {
    setShowConfirmDialog(true);
    setPendingStatus("ignored");
  };

  const handleRestore = () => {
    onStatusChange("backlog");
  };

  const confirmAction = () => {
    if (pendingStatus) {
      onStatusChange(pendingStatus);
    }
    setShowConfirmDialog(false);
    setPendingStatus(null);
  };

  const cancelAction = () => {
    setShowConfirmDialog(false);
    setPendingStatus(null);
  };

  const currentConfig = STATUS_CONFIG[status] || STATUS_CONFIG.backlog;

  return (
    <div
      data-testid="status-workflow-buttons"
      className={cn("flex flex-wrap items-center gap-2", className)}
      role="group"
      aria-label="Status workflow actions"
    >
      {/* Current Status Badge */}
      <Badge
        data-testid="current-status-badge"
        data-status={status}
        variant="outline"
        className={cn("font-medium", currentConfig.color)}
      >
        {currentConfig.label}
      </Badge>

      {/* Confirmation Dialog */}
      <Dialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
        <DialogContent data-testid="confirm-dialog">
          <DialogHeader>
            <DialogTitle>Confirm Action</DialogTitle>
            <DialogDescription data-testid="confirm-message">
              Are you sure you want to ignore this candidate?
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              data-testid="cancel-btn"
              variant="outline"
              onClick={cancelAction}
            >
              Cancel
            </Button>
            <Button
              data-testid="confirm-btn"
              variant="destructive"
              onClick={confirmAction}
            >
              Confirm
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Primary Action Button */}
      {!isIgnored && !isRecovered && hasTransition && (
        <Button
          data-testid="primary-action-btn"
          onClick={handlePrimaryAction}
          disabled={isLoading || disabled}
          className="gap-1.5"
          aria-label={TRANSITION_CONFIG[status].label}
        >
          {isLoading ? (
            <Loader2 className="h-4 w-4 animate-spin" data-testid="icon-loader" />
          ) : (
            <ArrowRight className="h-4 w-4" data-testid="icon-arrow-right" />
          )}
          {TRANSITION_CONFIG[status].label}
        </Button>
      )}

      {/* Ignore Button */}
      {canIgnore && (
        <Button
          data-testid="ignore-btn"
          variant="outline"
          onClick={handleIgnore}
          disabled={isLoading || disabled}
          className="gap-1.5 text-red-600 hover:text-red-700 hover:bg-red-50"
          aria-label="Ignore candidate"
        >
          <XCircle className="h-4 w-4" data-testid="icon-x-circle" />
          Ignore
        </Button>
      )}

      {/* Restore Button */}
      {isIgnored && (
        <Button
          data-testid="restore-btn"
          variant="outline"
          onClick={handleRestore}
          disabled={isLoading || disabled}
          className="gap-1.5"
          aria-label="Restore to backlog"
        >
          <RotateCcw className="h-4 w-4" data-testid="icon-rotate-ccw" />
          Restore
        </Button>
      )}

      {/* Recovered Badge */}
      {isRecovered && (
        <Badge
          data-testid="recovered-badge"
          className="bg-green-100 text-green-800 gap-1.5"
        >
          <CheckCircle className="h-4 w-4" data-testid="icon-check-circle" />
          Recovered
        </Badge>
      )}

      {/* Status History */}
      {history && history.length > 0 && (
        <div
          data-testid="status-history"
          className="mt-2 w-full border-t pt-2"
          aria-label="Status history"
        >
          <div className="text-xs text-muted-foreground mb-1">History</div>
          <div className="space-y-1">
            {history.map((item, i) => (
              <div
                key={i}
                data-testid={`history-item-${i}`}
                className="flex items-center gap-2 text-xs text-muted-foreground"
              >
                <Clock className="h-3 w-3" data-testid="icon-clock" />
                <span className="font-medium">
                  {STATUS_CONFIG[item.status as ContentDecayCandidateStatus]?.label || item.status}
                </span>
                <span>by {item.changedBy}</span>
                <span className="text-muted-foreground/60">
                  {new Date(item.changedAt).toLocaleDateString()}
                </span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
