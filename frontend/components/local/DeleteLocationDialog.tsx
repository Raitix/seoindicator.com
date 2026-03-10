"use client";

import { AlertTriangle, Loader2, Trash2 } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import type { BusinessLocation } from "@/lib/types/locations";

export interface DeleteLocationDialogProps {
  open: boolean;
  location: BusinessLocation | null;
  isDeleting?: boolean;
  onClose: () => void;
  onConfirm: (location: BusinessLocation) => Promise<void> | void;
}

export function DeleteLocationDialog({
  open,
  location,
  isDeleting = false,
  onClose,
  onConfirm,
}: DeleteLocationDialogProps) {
  if (!location) {
    return null;
  }

  const label = location.name?.trim() || "this location";

  return (
    <Dialog open={open} onOpenChange={(nextOpen) => (!nextOpen && !isDeleting ? onClose() : undefined)}>
      <DialogContent
        role="alertdialog"
        aria-describedby="delete-location-description"
        className="border-red-500/20 bg-slate-950 text-slate-100 sm:max-w-md"
      >
        <DialogHeader>
          <div className="flex items-start gap-3">
            <div className="rounded-xl border border-red-500/30 bg-red-500/10 p-2 text-red-300">
              <AlertTriangle className="h-5 w-5" aria-hidden="true" />
            </div>
            <div className="space-y-2">
              <DialogTitle className="text-white">Delete location?</DialogTitle>
              <DialogDescription id="delete-location-description" className="text-slate-300">
                You are about to permanently remove <span className="font-semibold text-white">{label}</span>.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <div className="rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-50">
          <p className="font-medium">This action cannot be undone.</p>
          <ul className="mt-3 list-disc space-y-1 pl-5 text-red-100/90">
            <li>Google Business Profile connection details will be cleared.</li>
            <li>Review history and rating insights for this location may be lost.</li>
            <li>Local pack ranking snapshots and analytics history will no longer be accessible.</li>
          </ul>
        </div>

        <DialogFooter className="gap-2 border-t border-slate-800 pt-4">
          <Button
            type="button"
            variant="outline"
            className="border-slate-700 bg-transparent text-slate-100 hover:bg-slate-900"
            onClick={onClose}
            disabled={isDeleting}
          >
            Cancel
          </Button>
          <Button
            type="button"
            variant="destructive"
            disabled={isDeleting}
            className="bg-red-600/95 text-white hover:bg-red-600"
            onClick={() => onConfirm(location)}
          >
            {isDeleting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" /> : <Trash2 className="h-4 w-4" aria-hidden="true" />}
            {isDeleting ? "Deleting..." : "Delete location"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default DeleteLocationDialog;
