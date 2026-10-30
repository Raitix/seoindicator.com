"use client";

import * as React from "react";
import { Loader2, MessageSquareReply, Sparkles } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { formatDateTime } from "@/lib/location-dashboard";
import { cn } from "@/lib/utils";
import type { LocationReview } from "@/lib/types/location-reviews";

export interface ReviewResponseModalProps {
  isOpen: boolean;
  review: LocationReview | null;
  isSubmitting?: boolean;
  onClose: () => void;
  onSubmit: (response: string) => Promise<void>;
  maxChars?: number;
}

const templates = [
  "Thank you for your feedback. We appreciate you taking the time to share your experience.",
  "Thanks for the review — we’re glad to hear the visit went well and hope to welcome you back soon.",
  "We appreciate the candid feedback and will use it to improve the experience for future customers.",
];

export function ReviewResponseModal({
  isOpen,
  review,
  isSubmitting = false,
  onClose,
  onSubmit,
  maxChars = 4096,
}: ReviewResponseModalProps) {
  const [value, setValue] = React.useState("");
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!isOpen) {
      setValue("");
      setError(null);
    }
  }, [isOpen]);

  const remaining = maxChars - value.length;
  const canSubmit = value.trim().length > 0 && remaining >= 0 && !isSubmitting;

  const counterTone =
    remaining < 96 ? "text-red-300" : remaining < 320 ? "text-amber-300" : "text-slate-500";

  const handleSubmit = async () => {
    const trimmed = value.trim();

    if (!trimmed.length) {
      setError("Please write a response before submitting.");
      return;
    }

    try {
      setError(null);
      await onSubmit(trimmed);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Failed to post review response.");
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => (!open ? onClose() : undefined)}>
      <DialogContent className="max-h-[90vh] overflow-y-auto border-slate-800 bg-slate-950 text-slate-100 sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <MessageSquareReply className="h-5 w-5 text-violet-300" aria-hidden="true" />
            Respond to review
          </DialogTitle>
          <DialogDescription>
            Keep it concise, specific, and professional. Responses are sent to Google Business Profile.
          </DialogDescription>
        </DialogHeader>

        {review ? (
          <div className="space-y-4">
            <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
              <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                <span className="font-medium text-white">{review.author}</span>
                <span>·</span>
                <span>{review.rating}★</span>
                <span>·</span>
                <span>{formatDateTime(review.reviewedAt)}</span>
              </div>
              <p className="mt-3 text-sm leading-6 text-slate-200">{review.text || "No review text provided."}</p>
            </div>

            <div className="space-y-3">
              <div className="flex flex-wrap gap-2">
                {templates.map((template) => (
                  <Button
                    key={template}
                    type="button"
                    variant="ghost"
                    className="rounded-full border border-slate-800 bg-slate-900/70 text-slate-200 hover:bg-slate-800"
                    onClick={() => setValue(template)}
                    disabled={isSubmitting}
                  >
                    <Sparkles className="h-4 w-4" aria-hidden="true" />
                    Use template
                  </Button>
                ))}
              </div>

              <Textarea
                value={value}
                onChange={(event) => setValue(event.target.value)}
                placeholder="Thank the reviewer, address their experience, and offer a next step when helpful."
                maxLength={maxChars}
                className="min-h-[180px] border-slate-700 bg-slate-900 text-slate-100"
                aria-label="Your response"
                disabled={isSubmitting}
              />

              <div className="flex items-center justify-between gap-3 text-sm">
                <div className={cn("transition", counterTone)}>{value.length}/{maxChars}</div>
                {error ? <div className="text-red-300">{error}</div> : <div className="text-slate-500">Google allows up to 4096 characters.</div>}
              </div>
            </div>
          </div>
        ) : null}

        <DialogFooter>
          <Button type="button" variant="outline" className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" onClick={onClose} disabled={isSubmitting}>
            Cancel
          </Button>
          <Button type="button" onClick={() => void handleSubmit()} disabled={!canSubmit}>
            {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" /> : <MessageSquareReply className="h-4 w-4" aria-hidden="true" />}
            {isSubmitting ? "Posting…" : "Post response"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default ReviewResponseModal;
