"use client";

import * as React from "react";
import { useForm } from "react-hook-form";
import { Loader2 } from "lucide-react";

import type { Competitor } from "@/lib/types/competitors";
import { useUpdateCompetitor } from "@/lib/api/competitors";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

interface EditCompetitorModalProps {
  competitor: Competitor | null;
  open: boolean;
  onClose: () => void;
}

interface EditCompetitorFormValues {
  displayName: string;
  notes: string;
}

export default function EditCompetitorModal({
  competitor,
  open,
  onClose,
}: EditCompetitorModalProps) {
  const [errorMessage, setErrorMessage] = React.useState<string | null>(null);
  const updateCompetitor = useUpdateCompetitor();

  const {
    register,
    handleSubmit,
    formState: { isSubmitting },
    reset,
  } = useForm<EditCompetitorFormValues>({
    mode: "onChange",
  });

  React.useEffect(() => {
    if (competitor) {
      reset({
        displayName: competitor.displayName ?? "",
        notes: competitor.notes ?? "",
      });
    }
  }, [competitor, reset]);

  const onSubmit = async (values: EditCompetitorFormValues) => {
    if (!competitor) return;
    setErrorMessage(null);
    try {
      await updateCompetitor(competitor.id, {
        domain: competitor.domain,
        displayName: values.displayName,
        notes: values.notes,
      });
      onClose();
    } catch (error: any) {
      setErrorMessage(error?.message ?? "Unable to update competitor");
    }
  };

  return (
    <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit competitor</DialogTitle>
          <DialogDescription>
            Update display name or notes for this competitor.
          </DialogDescription>
        </DialogHeader>
        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
          <div className="space-y-2">
            <Label htmlFor="edit-domain">Domain</Label>
            <Input
              id="edit-domain"
              value={competitor?.domain ?? ""}
              disabled
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="edit-displayName">Display name</Label>
            <Input
              id="edit-displayName"
              placeholder="Competitor Name"
              {...register("displayName")}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="edit-notes">Notes</Label>
            <Textarea
              id="edit-notes"
              placeholder="Optional notes"
              {...register("notes")}
            />
          </div>
          {errorMessage && (
            <div className="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
              {errorMessage}
            </div>
          )}
          <DialogFooter>
            <Button
              type="submit"
              className="bg-emerald-500 text-white hover:bg-emerald-600"
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <span className="flex items-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Saving...
                </span>
              ) : (
                "Save changes"
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
