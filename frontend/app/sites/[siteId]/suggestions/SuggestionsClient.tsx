"use client";

import * as React from "react";
import { Suspense } from "react";
import Link from "next/link";
import { useRouter, useSearchParams, useParams, usePathname } from "next/navigation";
import { ArrowDown, ArrowUp, Check, Loader2, Minus, RefreshCcw, X } from "lucide-react";

import type { KeywordSuggestion, SuggestionSource } from "@/lib/types/suggestions";
import {
  useSuggestions,
  trackSuggestion,
  bulkTrackSuggestions,
  dismissSuggestion,
  defaultSuggestionMeta,
} from "@/lib/api/suggestions";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

const PER_PAGE = 20;

// Source display configuration
const SOURCE_LABELS: Record<SuggestionSource, string> = {
  suggest: "Google Suggest",
  question: "Question Keywords",
  related: "Related Searches",
  competitor: "Competitor Keywords",
};

const SOURCE_COLORS: Record<SuggestionSource, string> = {
  suggest: "bg-blue-500/15 text-blue-200",
  question: "bg-purple-500/15 text-purple-200",
  related: "bg-amber-500/15 text-amber-200",
  competitor: "bg-rose-500/15 text-rose-200",
};

// Status badge component
const getStatusBadge = (suggestion: KeywordSuggestion) => {
  if (suggestion.isTracking) {
    return (
      <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-200">
        <Check className="h-3 w-3" />
        Tracking
      </span>
    );
  }
  if (suggestion.isDismissed) {
    return (
      <span className="inline-flex items-center gap-1 rounded-full bg-slate-500/15 px-2 py-0.5 text-xs font-semibold text-slate-400">
        <X className="h-3 w-3" />
        Dismissed
      </span>
    );
  }
  return (
    <span className="rounded-full bg-slate-700/50 px-2 py-0.5 text-xs font-semibold text-slate-300">
      New
    </span>
  );
};

// Opportunity score badge
const getOpportunityBadge = (score: number | null) => {
  if (score === null) {
    return <span className="text-slate-500">—</span>;
  }

  let colorClass = "text-slate-300";
  if (score >= 80) {
    colorClass = "text-emerald-300";
  } else if (score >= 60) {
    colorClass = "text-amber-300";
  } else if (score >= 40) {
    colorClass = "text-orange-300";
  }

  return (
    <span className={`font-semibold ${colorClass}`}>
      {score}
    </span>
  );
};

// Source badge component
const getSourceBadge = (source: SuggestionSource) => {
  const colorClass = SOURCE_COLORS[source] || "bg-slate-500/15 text-slate-300";
  const label = SOURCE_LABELS[source] || source;

  return (
    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${colorClass}`}>
      {label}
    </span>
  );
};

// Filter state type
interface FilterState {
  source: SuggestionSource | "all";
  status: "all" | "tracking" | "dismissed" | "new";
  sort: "opportunity_score" | "keyword" | "source";
  order: "asc" | "desc";
}

const defaultFilterState: FilterState = {
  source: "all",
  status: "new",
  sort: "opportunity_score",
  order: "desc",
};

/**
 * SuggestionsPageContent - Main suggestions list content component
 * Displays keyword suggestions with filtering, sorting, pagination, and bulk actions.
 * This component uses useSearchParams and must be wrapped in Suspense.
 */
function SuggestionsPageContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const params = useParams();
  const pathname = usePathname();
  const siteId = params.siteId as string;

  // Initialize filter state from URL params or defaults
  const [filterState, setFilterState] = React.useState<FilterState>(() => {
    const source = (searchParams.get("source") || "all") as SuggestionSource | "all";
    const status = (searchParams.get("status") || "new") as FilterState["status"];
    const sort = (searchParams.get("sort") || "opportunity_score") as FilterState["sort"];
    const order = (searchParams.get("order") || "desc") as "asc" | "desc";

    return {
      ...defaultFilterState,
      source,
      status,
      sort,
      order,
    };
  });

  const [page, setPage] = React.useState(1);
  const [selected, setSelected] = React.useState<Set<number>>(new Set());
  const [bulkBusy, setBulkBusy] = React.useState(false);
  const [actionLoading, setActionLoading] = React.useState<number | null>(null);

  // Build query params for API call
  const queryParams = React.useMemo(
    () => ({
      source: filterState.source !== "all" ? filterState.source : undefined,
      includeTracking: filterState.status === "tracking" || filterState.status === "all",
      includeDismissed: filterState.status === "dismissed" || filterState.status === "all",
      page,
      perPage: PER_PAGE,
      sort: filterState.sort,
      order: filterState.order,
    }),
    [filterState, page]
  );

  const { suggestions, meta, isLoading, error, mutate } = useSuggestions(siteId, queryParams);

  // Client-side filter for status (API may not filter this)
  const filteredSuggestions = React.useMemo(() => {
    let list = [...suggestions];

    if (filterState.status === "new") {
      list = list.filter((s) => !s.isTracking && !s.isDismissed);
    } else if (filterState.status === "tracking") {
      list = list.filter((s) => s.isTracking);
    } else if (filterState.status === "dismissed") {
      list = list.filter((s) => s.isDismissed);
    }

    return list;
  }, [suggestions, filterState.status]);

  const metaInfo = meta ?? defaultSuggestionMeta;
  const totalPages = Math.max(1, Math.ceil(metaInfo.total / metaInfo.perPage));

  // Update URL params when filter changes
  React.useEffect(() => {
    const urlParams = new URLSearchParams();
    if (filterState.source !== "all") {
      urlParams.set("source", filterState.source);
    }
    if (filterState.status !== "new") {
      urlParams.set("status", filterState.status);
    }
    if (filterState.sort !== "opportunity_score") {
      urlParams.set("sort", filterState.sort);
    }
    if (filterState.order !== "desc") {
      urlParams.set("order", filterState.order);
    }

    const queryString = urlParams.toString();
    const newUrl = queryString ? `${pathname}?${queryString}` : pathname;
    router.replace(newUrl, { scroll: false });
  }, [filterState, router, pathname]);

  // Reset page when filters change
  React.useEffect(() => {
    setPage(1);
    setSelected(new Set());
  }, [filterState.source, filterState.status]);

  // Selection handlers
  const toggleSelectAll = () => {
    if (selected.size === filteredSuggestions.length) {
      setSelected(new Set());
      return;
    }
    setSelected(new Set(filteredSuggestions.map((item) => item.id)));
  };

  const toggleSelection = (id: number) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  // Bulk action handlers
  const handleBulkTrack = async () => {
    if (selected.size === 0) return;
    setBulkBusy(true);
    const ids = Array.from(selected.values());
    try {
      await bulkTrackSuggestions(Number(siteId), ids);
      setSelected(new Set());
      await mutate();
    } catch (err) {
      console.error(err);
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkDismiss = async () => {
    if (selected.size === 0) return;
    setBulkBusy(true);
    const ids = Array.from(selected.values());
    try {
      await Promise.all(ids.map((id) => dismissSuggestion(Number(siteId), id)));
      setSelected(new Set());
      await mutate();
    } catch (err) {
      console.error(err);
    } finally {
      setBulkBusy(false);
    }
  };

  // Individual action handlers
  const handleTrack = async (suggestionId: number) => {
    setActionLoading(suggestionId);
    try {
      await trackSuggestion(Number(siteId), suggestionId);
      await mutate();
    } catch (err) {
      console.error(err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleDismiss = async (suggestionId: number) => {
    setActionLoading(suggestionId);
    try {
      await dismissSuggestion(Number(siteId), suggestionId);
      await mutate();
    } catch (err) {
      console.error(err);
    } finally {
      setActionLoading(null);
    }
  };

  // Sort handler
  const handleSort = (column: FilterState["sort"]) => {
    setFilterState((prev) => ({
      ...prev,
      sort: column,
      order: prev.sort === column && prev.order === "desc" ? "asc" : "desc",
    }));
  };

  // Sort icon component
  const SortIcon = ({ column }: { column: FilterState["sort"] }) => {
    if (filterState.sort !== column) {
      return <Minus className="h-3 w-3 text-slate-600" />;
    }
    return filterState.order === "asc" ? (
      <ArrowUp className="h-3 w-3 text-emerald-400" />
    ) : (
      <ArrowDown className="h-3 w-3 text-emerald-400" />
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-6xl space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-white">Keyword Suggestions</h1>
            <p className="text-slate-400">
              Discover new keyword opportunities to expand your SEO strategy.
            </p>
          </div>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div className="rounded-full border border-slate-700 bg-slate-900/60 px-3 py-1 text-sm text-slate-300">
              {metaInfo.total} total suggestions
            </div>
            <Button asChild className="bg-emerald-500 text-white hover:bg-emerald-600">
              <Link href={`/sites/${siteId}/suggestions/generate`}>Generate Suggestions</Link>
            </Button>
          </div>
        </div>

        <Card className="border-slate-800 bg-slate-900/50">
          <CardContent className="space-y-4 p-6">
            {/* Filter Bar */}
            <div className="flex flex-wrap items-center gap-3">
              {/* Source Filter */}
              <select
                data-testid="source-filter"
                aria-label="Filter by source"
                value={filterState.source}
                onChange={(e) =>
                  setFilterState((prev) => ({
                    ...prev,
                    source: e.target.value as FilterState["source"],
                  }))
                }
                className="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              >
                <option value="all">All Sources</option>
                <option value="suggest">Google Suggest</option>
                <option value="question">Question Keywords</option>
                <option value="related">Related Searches</option>
                <option value="competitor">Competitor Keywords</option>
              </select>

              {/* Status Filter */}
              <select
                data-testid="status-filter"
                aria-label="Filter by status"
                value={filterState.status}
                onChange={(e) =>
                  setFilterState((prev) => ({
                    ...prev,
                    status: e.target.value as FilterState["status"],
                  }))
                }
                className="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              >
                <option value="all">All Status</option>
                <option value="new">New</option>
                <option value="tracking">Tracking</option>
                <option value="dismissed">Dismissed</option>
              </select>
            </div>

            {/* Bulk Actions Row */}
            <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
              <span>{selected.size} selected</span>
              <Button
                variant="ghost"
                className="border border-emerald-700 text-emerald-200"
                disabled={bulkBusy || selected.size === 0}
                onClick={handleBulkTrack}
              >
                Track Selected
              </Button>
              <Button
                variant="ghost"
                className="border border-slate-700 text-slate-200"
                disabled={bulkBusy || selected.size === 0}
                onClick={handleBulkDismiss}
              >
                Dismiss Selected
              </Button>
            </div>

            {/* Loading State */}
            {isLoading && (
              <div
                data-testid="loading-overlay"
                className="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/40 px-4 py-6 text-slate-400"
              >
                <Loader2 className="h-4 w-4 animate-spin" />
                Loading suggestions...
              </div>
            )}

            {/* Error State */}
            {!isLoading && error && (
              <div className="rounded-lg border border-rose-500/40 bg-rose-500/10 p-4 text-rose-200">
                <div className="flex items-center gap-2 font-semibold">Failed to load suggestions</div>
                <p className="mt-2 text-sm text-rose-200/80">
                  {error.message ?? "Please try again."}
                </p>
                <Button
                  variant="outline"
                  className="mt-3 border-rose-400 text-rose-100"
                  onClick={() => mutate()}
                >
                  <RefreshCcw className="mr-2 h-4 w-4" />
                  Retry
                </Button>
              </div>
            )}

            {/* Empty State */}
            {!isLoading && !error && filteredSuggestions.length === 0 && (
              <div
                data-testid="empty-state"
                className="rounded-lg border border-slate-800 bg-slate-950/40 p-6 text-center text-slate-300"
              >
                <p className="text-lg font-semibold text-white">No suggestions found</p>
                <p className="text-sm text-slate-400">
                  {filterState.status !== "new"
                    ? "Try selecting a different status filter or clear your filters."
                    : "Generate some keywords to get started!"}
                </p>
              </div>
            )}

            {/* Table */}
            {!isLoading && !error && filteredSuggestions.length > 0 && (
              <div
                data-testid="suggestions-table"
                role="grid"
                className="overflow-hidden rounded-lg border border-slate-800"
              >
                <div className="max-w-full overflow-x-auto">
                  <table className="min-w-full text-sm text-slate-200">
                    <thead className="bg-slate-900/80 text-xs uppercase text-slate-400">
                      <tr>
                        <th className="px-4 py-3">
                          <input
                            type="checkbox"
                            data-testid="select-all"
                            aria-label="Select all"
                            checked={
                              selected.size === filteredSuggestions.length &&
                              filteredSuggestions.length > 0
                            }
                            onChange={toggleSelectAll}
                          />
                        </th>
                        <th
                          className="cursor-pointer px-4 py-3 text-left hover:text-emerald-400"
                          onClick={() => handleSort("keyword")}
                        >
                          <div className="flex items-center gap-1">
                            Keyword
                            <SortIcon column="keyword" />
                          </div>
                        </th>
                        <th
                          className="cursor-pointer px-4 py-3 text-left hover:text-emerald-400"
                          onClick={() => handleSort("source")}
                        >
                          <div className="flex items-center gap-1">
                            Source
                            <SortIcon column="source" />
                          </div>
                        </th>
                        <th
                          className="cursor-pointer px-4 py-3 text-left hover:text-emerald-400"
                          onClick={() => handleSort("opportunity_score")}
                        >
                          <div className="flex items-center gap-1">
                            Opportunity Score
                            <SortIcon column="opportunity_score" />
                          </div>
                        </th>
                        <th className="px-4 py-3 text-left">Status</th>
                        <th className="px-4 py-3 text-left">Actions</th>
                      </tr>
                    </thead>
                    <tbody data-testid="suggestions-body">
                      {filteredSuggestions.map((suggestion) => (
                        <tr
                          key={suggestion.id}
                          data-testid={`suggestion-row-${suggestion.id}`}
                          className="border-t border-slate-800 hover:bg-slate-900/40"
                        >
                          <td className="px-4 py-3">
                            <input
                              type="checkbox"
                              data-testid={`select-${suggestion.id}`}
                              aria-label={`Select suggestion ${suggestion.id}`}
                              checked={selected.has(suggestion.id)}
                              onChange={() => toggleSelection(suggestion.id)}
                            />
                          </td>
                          <td className="px-4 py-3 text-white">{suggestion.keyword}</td>
                          <td className="px-4 py-3">
                            <span data-testid="source-badge">
                              {getSourceBadge(suggestion.source)}
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            {getOpportunityBadge(suggestion.opportunityScore)}
                          </td>
                          <td className="px-4 py-3">
                            <span data-testid="status-badge">
                              {getStatusBadge(suggestion)}
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            {!suggestion.isTracking && (
                              <div className="flex items-center gap-2">
                                <Button
                                  data-testid={`track-btn-${suggestion.id}`}
                                  aria-label="Track keyword"
                                  variant="ghost"
                                  size="sm"
                                  className="border border-emerald-700 text-emerald-200"
                                  disabled={actionLoading === suggestion.id}
                                  onClick={() => handleTrack(suggestion.id)}
                                >
                                  {actionLoading === suggestion.id ? (
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                  ) : (
                                    "Track"
                                  )}
                                </Button>
                                {!suggestion.isDismissed && (
                                  <Button
                                    data-testid={`dismiss-btn-${suggestion.id}`}
                                    aria-label="Dismiss suggestion"
                                    variant="ghost"
                                    size="sm"
                                    className="border border-slate-700 text-slate-400"
                                    disabled={actionLoading === suggestion.id}
                                    onClick={() => handleDismiss(suggestion.id)}
                                  >
                                    Dismiss
                                  </Button>
                                )}
                              </div>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Pagination */}
            {!isLoading && !error && filteredSuggestions.length > 0 && (
              <div
                data-testid="pagination"
                className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400"
              >
                <span data-testid="page-info">
                  Page {metaInfo.page} of {totalPages}
                </span>
                <div className="flex items-center gap-2">
                  <Button
                    data-testid="prev-page"
                    aria-label="Previous page"
                    variant="ghost"
                    className="border border-slate-700 text-slate-200"
                    disabled={page <= 1}
                    onClick={() => setPage((prev) => Math.max(1, prev - 1))}
                  >
                    Previous
                  </Button>
                  <Button
                    data-testid="next-page"
                    aria-label="Next page"
                    variant="ghost"
                    className="border border-slate-700 text-slate-200"
                    disabled={page >= totalPages}
                    onClick={() => setPage((prev) => Math.min(totalPages, prev + 1))}
                  >
                    Next
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

interface SuggestionsClientProps {
  siteId: string;
}

/**
 * SuggestionsClient - Main suggestions list page component
 * Wraps SuggestionsPageContent in Suspense for useSearchParams compatibility.
 */
export default function SuggestionsClient({ siteId }: SuggestionsClientProps) {
  // Note: siteId is passed but SuggestionsPageContent gets it from useParams
  // This is intentional to maintain the existing hook-based logic
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
        <div className="mx-auto w-full max-w-6xl flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
        </div>
      </div>
    }>
      <SuggestionsPageContent />
    </Suspense>
  );
}
