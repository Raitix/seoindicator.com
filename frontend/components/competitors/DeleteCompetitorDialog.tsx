"use client";

import * as React from "react";
import { Loader2 } from "lucide-react";

import type { Competitor } from "@/lib/types/competitors";
import { useDeleteCompetitor } from "@/lib/api/competitors";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteCompetitorDialogProps {
  competitor: Competitor | null;
  open: boolean;
  onClose: () => void;
}

export default function DeleteCompetitorDialog({
  competitor,
  open,
  onClose,
}: DeleteCompetitorDialogProps) {
  const [errorMessage, setErrorMessage] = React.useState<string | null>(null);
  const [isDeleting, setIsDeleting] = React.useState(false);
  const deleteCompetitor = useDeleteCompetitor();

  const onConfirm = async () => {
    if (!competitor) return;
    setErrorMessage(null);
    setIsDeleting(true);
    try {
      await deleteCompetitor(competitor.id);
      onClose();
    } catch (error: any) {
      setErrorMessage(error?.message ?? "Unable to delete competitor");
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete competitor</DialogTitle>
          <DialogDescription>
            This will remove {competitor?.domain} from your tracked competitors.
          </DialogDescription>
        </DialogHeader>
        {errorMessage && (
          <div className="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
            {errorMessage}
          </div>
        )}
        <DialogFooter>
          <Button variant="ghost" onClick={onClose} disabled={isDeleting}>
            Cancel
          </Button>
          <Button
            className="bg-rose-500 text-white hover:bg-rose-600"
            onClick={onConfirm}
            disabled={isDeleting}
          >
            {isDeleting ? (
              <span className="flex items-center gap-2">
                <Loader2 className="h-4 w-4 animate-spin" />
                Deleting...
              </span>
            ) : (
              "Delete"
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
