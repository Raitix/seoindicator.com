"use client";

import { CalendarDays, Filter, RotateCcw, SearchCheck } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { cn } from "@/lib/utils";
import type { ReviewFilterState, ReviewResponseFilter, ReviewSentiment, ReviewSortValue } from "@/lib/types/location-reviews";

export interface ReviewsFiltersProps {
  filters: ReviewFilterState;
  onChange: (next: ReviewFilterState) => void;
  onReset: () => void;
  isLoading?: boolean;
  className?: string;
}

const sortOptions: Array<{ value: ReviewSortValue; label: string }> = [
  { value: "-date", label: "Newest first" },
  { value: "date", label: "Oldest first" },
  { value: "-rating", label: "Highest rating" },
  { value: "rating", label: "Lowest rating" },
];

const sentimentOptions: Array<{ value: "all" | ReviewSentiment; label: string }> = [
  { value: "all", label: "All sentiments" },
  { value: "positive", label: "Positive" },
  { value: "neutral", label: "Neutral" },
  { value: "negative", label: "Negative" },
];

const responseOptions: Array<{ value: ReviewResponseFilter; label: string }> = [
  { value: "all", label: "All responses" },
  { value: "responded", label: "Has response" },
  { value: "unresponded", label: "Needs response" },
];

const quickPresets = [
  { label: "Negative reviews", apply: (filters: ReviewFilterState): ReviewFilterState => ({ ...filters, sentiment: "negative" }) },
  { label: "Needs response", apply: (filters: ReviewFilterState): ReviewFilterState => ({ ...filters, responseStatus: "unresponded" }) },
  {
    label: "Last 30 days",
    apply: (filters: ReviewFilterState): ReviewFilterState => {
      const endDate = new Date();
      const startDate = new Date();
      startDate.setDate(endDate.getDate() - 30);

      return {
        ...filters,
        startDate: startDate.toISOString().slice(0, 10),
        endDate: endDate.toISOString().slice(0, 10),
      };
    },
  },
];

export function ReviewsFilters({ filters, onChange, onReset, isLoading = false, className }: ReviewsFiltersProps) {
  const toggleRating = (rating: number) => {
    const exists = filters.ratings.includes(rating);
    const ratings = exists
      ? filters.ratings.filter((value) => value !== rating)
      : [...filters.ratings, rating].sort((left, right) => left - right);

    onChange({ ...filters, ratings });
  };

  const activeFilterCount =
    filters.ratings.length +
    (filters.sentiment !== "all" ? 1 : 0) +
    (filters.responseStatus !== "all" ? 1 : 0) +
    (filters.startDate ? 1 : 0) +
    (filters.endDate ? 1 : 0) +
    (filters.sort !== "-date" ? 1 : 0);

  return (
    <section className={cn("rounded-3xl border border-slate-800 bg-slate-950/70 p-5 shadow-lg shadow-slate-950/10", className)} aria-label="Review filters">
      <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <div className="flex items-center gap-2 text-sm font-medium text-white">
            <Filter className="h-4 w-4 text-violet-300" aria-hidden="true" />
            Filters & sort
          </div>
          <p className="mt-1 text-sm text-slate-400">
            Narrow the list to the reviews you need to reply to, audit, or export.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1 text-xs text-slate-400">
            {activeFilterCount} active filter{activeFilterCount === 1 ? "" : "s"}
          </span>
          <Button
            type="button"
            variant="outline"
            className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
            onClick={onReset}
            disabled={isLoading}
          >
            <RotateCcw className="h-4 w-4" aria-hidden="true" />
            Reset
          </Button>
        </div>
      </div>

      <div className="mt-5 grid gap-4 xl:grid-cols-[1.2fr_0.9fr_0.9fr_1fr]">
        <div className="space-y-3">
          <label className="text-xs font-medium uppercase tracking-wide text-slate-500">Rating</label>
          <div className="flex flex-wrap gap-2">
            {[5, 4, 3, 2, 1].map((rating) => {
              const active = filters.ratings.includes(rating);

              return (
                <button
                  key={rating}
                  type="button"
                  disabled={isLoading}
                  onClick={() => toggleRating(rating)}
                  className={cn(
                    "rounded-full border px-3 py-2 text-sm font-medium transition",
                    active
                      ? "border-amber-300 bg-amber-300 text-slate-950"
                      : "border-slate-700 bg-slate-900 text-slate-200 hover:border-slate-600 hover:bg-slate-800"
                  )}
                >
                  {rating}★
                </button>
              );
            })}
          </div>
        </div>

        <div className="space-y-3">
          <label className="text-xs font-medium uppercase tracking-wide text-slate-500">Sentiment</label>
          <Select value={filters.sentiment} onValueChange={(value) => onChange({ ...filters, sentiment: value as ReviewFilterState["sentiment"] })} disabled={isLoading}>
            <SelectTrigger>
              <SelectValue placeholder="All sentiments" />
            </SelectTrigger>
            <SelectContent>
              {sentimentOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-3">
          <label className="text-xs font-medium uppercase tracking-wide text-slate-500">Response status</label>
          <Select value={filters.responseStatus} onValueChange={(value) => onChange({ ...filters, responseStatus: value as ReviewResponseFilter })} disabled={isLoading}>
            <SelectTrigger>
              <SelectValue placeholder="All responses" />
            </SelectTrigger>
            <SelectContent>
              {responseOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-3">
          <label className="text-xs font-medium uppercase tracking-wide text-slate-500">Sort order</label>
          <Select value={filters.sort} onValueChange={(value) => onChange({ ...filters, sort: value as ReviewSortValue })} disabled={isLoading}>
            <SelectTrigger>
              <SelectValue placeholder="Newest first" />
            </SelectTrigger>
            <SelectContent>
              {sortOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        <label className="space-y-3">
          <span className="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
            <CalendarDays className="h-3.5 w-3.5" aria-hidden="true" />
            From date
          </span>
          <Input
            type="date"
            value={filters.startDate}
            disabled={isLoading}
            onChange={(event) => onChange({ ...filters, startDate: event.target.value })}
            className="border-slate-700 bg-slate-900 text-slate-100"
          />
        </label>

        <label className="space-y-3">
          <span className="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
            <CalendarDays className="h-3.5 w-3.5" aria-hidden="true" />
            To date
          </span>
          <Input
            type="date"
            value={filters.endDate}
            disabled={isLoading}
            onChange={(event) => onChange({ ...filters, endDate: event.target.value })}
            className="border-slate-700 bg-slate-900 text-slate-100"
          />
        </label>
      </div>

      <div className="mt-5 flex flex-wrap gap-2">
        {quickPresets.map((preset) => (
          <Button
            key={preset.label}
            type="button"
            variant="ghost"
            className="rounded-full border border-slate-800 bg-slate-900/80 text-slate-200 hover:bg-slate-800"
            onClick={() => onChange(preset.apply(filters))}
            disabled={isLoading}
          >
            <SearchCheck className="h-4 w-4" aria-hidden="true" />
            {preset.label}
          </Button>
        ))}
      </div>
    </section>
  );
}

export default ReviewsFilters;
