"use client";

import * as React from "react";
import { ExternalLink, MessageSquareReply, MoreHorizontal, Smile, Meh, Frown, Star } from "lucide-react";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { cn } from "@/lib/utils";
import { formatDateTime, formatRelativeTime, getSentimentTone } from "@/lib/location-dashboard";
import type { LocationReview, ReviewSentiment } from "@/lib/types/location-reviews";

export interface ReviewCardProps {
  review: LocationReview;
  onRespond?: (review: LocationReview) => void;
  isSelected?: boolean;
  onSelect?: (selected: boolean) => void;
  isPending?: boolean;
  className?: string;
}

const sentimentMeta: Record<ReviewSentiment, { label: string; icon: typeof Smile }> = {
  positive: { label: "Positive", icon: Smile },
  neutral: { label: "Neutral", icon: Meh },
  negative: { label: "Negative", icon: Frown },
};

function RatingStars({ rating }: { rating: number }) {
  const fullStars = Math.max(0, Math.min(5, Math.round(rating)));

  return (
    <div className="flex items-center gap-1" role="img" aria-label={`${rating} out of 5 stars`}>
      {Array.from({ length: 5 }).map((_, index) => (
        <Star
          key={index}
          className={cn(
            "h-4 w-4",
            index < fullStars ? "fill-amber-300 text-amber-300" : "text-slate-700"
          )}
          aria-hidden="true"
        />
      ))}
    </div>
  );
}

const MAX_PREVIEW_LENGTH = 260;

export function ReviewCard({
  review,
  onRespond,
  isSelected = false,
  onSelect,
  isPending = false,
  className,
}: ReviewCardProps) {
  const [expanded, setExpanded] = React.useState(false);
  const showToggle = review.text.length > MAX_PREVIEW_LENGTH;
  const previewText = showToggle && !expanded ? `${review.text.slice(0, MAX_PREVIEW_LENGTH)}…` : review.text;
  const SentimentIcon = sentimentMeta[review.sentiment].icon;

  return (
    <article className={cn("rounded-3xl border border-slate-800 bg-slate-950/70 p-5 shadow-lg shadow-slate-950/10", className)} aria-label={`Review by ${review.author}`}>
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="flex min-w-0 gap-4">
          {onSelect ? (
            <div className="pt-2">
              <Checkbox checked={isSelected} onCheckedChange={(checked) => onSelect(Boolean(checked))} aria-label={`Select review by ${review.author}`} />
            </div>
          ) : null}

          <Avatar size="lg" className="border border-slate-800 bg-slate-900">
            {review.avatarUrl ? <AvatarImage src={review.avatarUrl} alt={`${review.author} avatar`} /> : null}
            <AvatarFallback className="bg-slate-800 text-slate-100">{review.author.slice(0, 1).toUpperCase()}</AvatarFallback>
          </Avatar>

          <div className="min-w-0 flex-1 space-y-3">
            <div className="flex flex-wrap items-start gap-3">
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <h3 className="truncate text-lg font-semibold text-white">{review.author}</h3>
                  {review.isLocalGuide ? (
                    <span className="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-slate-300">
                      Local Guide{review.reviewCount ? ` · ${review.reviewCount} reviews` : ""}
                    </span>
                  ) : null}
                </div>
                <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-400">
                  <RatingStars rating={review.rating} />
                  <span>{formatDateTime(review.reviewedAt)}</span>
                  <span>{formatRelativeTime(review.reviewedAt)}</span>
                </div>
              </div>

              <div className={cn("inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium", getSentimentTone(review.sentiment))}>
                <SentimentIcon className="h-3.5 w-3.5" aria-hidden="true" />
                {sentimentMeta[review.sentiment].label}
              </div>
            </div>

            <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-sm leading-6 text-slate-200">
              {previewText || "No review text provided."}
              {showToggle ? (
                <button
                  type="button"
                  onClick={() => setExpanded((current) => !current)}
                  className="ml-2 inline-flex items-center gap-1 font-medium text-violet-300 transition hover:text-violet-200"
                >
                  <MoreHorizontal className="h-3.5 w-3.5" aria-hidden="true" />
                  {expanded ? "Show less" : "Read more"}
                </button>
              ) : null}
            </div>

            {review.response ? (
              <div className="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-50">
                <div className="flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-emerald-200/80">
                  <MessageSquareReply className="h-3.5 w-3.5" aria-hidden="true" />
                  Owner response
                  <span className="normal-case tracking-normal text-emerald-100/80">
                    {review.response.respondedAt ? `· ${formatDateTime(review.response.respondedAt)}` : ""}
                  </span>
                </div>
                <p className="mt-2 leading-6 text-emerald-50/95">{review.response.text}</p>
              </div>
            ) : null}
          </div>
        </div>

        <div className="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:flex-col lg:items-stretch">
          <Button
            type="button"
            onClick={() => onRespond?.(review)}
            disabled={!onRespond || review.hasResponse || isPending}
            className="min-w-[150px]"
          >
            <MessageSquareReply className="h-4 w-4" aria-hidden="true" />
            {isPending ? "Sending…" : review.hasResponse ? "Responded" : "Respond"}
          </Button>

          <Button
            type="button"
            variant="outline"
            asChild
            className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
          >
            <a href={review.googleUrl ?? "https://www.google.com/maps"} target="_blank" rel="noopener noreferrer">
              <ExternalLink className="h-4 w-4" aria-hidden="true" />
              View on Google
            </a>
          </Button>
        </div>
      </div>
    </article>
  );
}

export default ReviewCard;
