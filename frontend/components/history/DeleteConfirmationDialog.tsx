"use client";

import * as React from "react";
import { AlertTriangle, Loader2 } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

import type { DeleteConfirmationDialogProps } from "@/lib/types/analysis";

// ==================== Delete Confirmation Dialog ====================

export function DeleteConfirmationDialog({
  analysis,
  isOpen,
  onClose,
  onConfirm,
  isDeleting = false,
}: DeleteConfirmationDialogProps) {
  // Handle escape key
  React.useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen && !isDeleting) {
        onClose();
      }
    };

    document.addEventListener("keydown", handleEscape);
    return () => document.removeEventListener("keydown", handleEscape);
  }, [isOpen, isDeleting, onClose]);

  if (!analysis) return null;

  return (
    <Dialog open={isOpen} onOpenChange={isDeleting ? undefined : onClose}>
      <DialogContent
        className="max-w-md bg-slate-950 border-slate-800 text-slate-200"
        data-testid="delete-confirmation-dialog"
      >
        <DialogHeader>
          <div className="flex items-start gap-4">
            <div className="flex-shrink-0">
              <div className="h-10 w-10 rounded-full bg-red-500/10 flex items-center justify-center">
                <AlertTriangle className="h-5 w-5 text-red-500" />
              </div>
            </div>
            <div>
              <DialogTitle className="text-lg text-white">
                Delete Analysis
              </DialogTitle>
              <DialogDescription className="text-slate-400 mt-2">
                Are you sure you want to delete this analysis? This action cannot be undone.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        {/* Analysis Info */}
        <div className="bg-slate-900 rounded-lg p-4 mt-4">
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-slate-400">Tool:</span>
              <span className="text-slate-200">{analysis.toolDisplayName}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-400">URL:</span>
              <span className="text-slate-200 truncate max-w-[200px]" title={analysis.inputUrl}>
                {analysis.inputUrl}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-400">Status:</span>
              <span className="text-slate-200 capitalize">{analysis.status}</span>
            </div>
          </div>
        </div>

        {/* Warning Message */}
        <p className="text-sm text-slate-400 mt-4">
          The analysis results and all associated data will be permanently removed.
        </p>

        <DialogFooter className="mt-6 flex gap-3">
          <Button
            variant="outline"
            onClick={onClose}
            disabled={isDeleting}
            className="flex-1 border-slate-700 text-slate-200"
            data-testid="cancel-delete-button"
          >
            Cancel
          </Button>
          <Button
            variant="destructive"
            onClick={onConfirm}
            disabled={isDeleting}
            className="flex-1 bg-red-600 hover:bg-red-700 text-white"
            data-testid={isDeleting ? "delete-loading" : "confirm-delete-button"}
          >
            {isDeleting ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Deleting...
              </>
            ) : (
              "Delete"
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default DeleteConfirmationDialog;
