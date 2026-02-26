"use client";

import * as React from "react";
import { Filter, Loader2, Sparkles } from "lucide-react";

import { useSERPFeatureOptions, FALLBACK_SERP_FEATURES, type SERPFeatureOption } from "@/lib/api/metadata";

// Icon mapping for SERP features
const iconMap: Record<string, React.ReactNode> = {
  trophy: <Sparkles className="h-4 w-4 text-amber-400" />,
  "map-pin": <span className="text-blue-400">📍</span>,
  image: <span className="text-green-400">🖼️</span>,
  "play-circle": <span className="text-red-400">▶️</span>,
  "help-circle": <span className="text-purple-400">❓</span>,
  info: <span className="text-cyan-400">ℹ️</span>,
  link: <span className="text-indigo-400">🔗</span>,
  star: <span className="text-yellow-400">⭐</span>,
  list: <span className="text-orange-400">📋</span>,
  "shopping-cart": <span className="text-pink-400">🛒</span>,
  filter: <Filter className="h-4 w-4 text-slate-400" />,
  circle: <span className="text-slate-400">○</span>,
};

export interface KeywordFilterState {
  serpFeature: string;
  showOnlyWithFeatures: boolean;
  status: "active" | "paused" | "all";
  search: string;
  sortBy: string;
  sortOrder: "asc" | "desc";
}

interface KeywordFilterBarProps {
  siteId: string;
  value: KeywordFilterState;
  onChange: (state: KeywordFilterState) => void;
  showStatusFilter?: boolean;
  showSortControls?: boolean;
}

/**
 * KeywordFilterBar provides SERP feature filtering for the keyword dashboard.
 * 
 * Features:
 * - SERP feature dropdown (fetches options from /api/v1/metadata/serp-features)
 * - "Show Only Keywords With Features" checkbox
 * - Combines with existing filters (status, search, sort)
 * 
 * TASK-7-18: Feature Filter UI
 */
export default function KeywordFilterBar({
  siteId,
  value,
  onChange,
  showStatusFilter = true,
  showSortControls = true,
}: KeywordFilterBarProps) {
  const { options, isLoading, error } = useSERPFeatureOptions();
  
  // Use fallback options if API fails or is loading
  const featureOptions: SERPFeatureOption[] = React.useMemo(() => {
    if (error || !options?.data?.length) {
      return FALLBACK_SERP_FEATURES;
    }
    // Add "All" option at the beginning
    return [
      { value: "all", label: "All Features", icon: "filter", description: "Show all keywords", ctrImpact: "" },
      ...options.data,
    ];
  }, [options, error]);

  const handleFeatureChange = (feature: string) => {
    onChange({
      ...value,
      serpFeature: feature,
    });
  };

  const handleShowOnlyWithFeaturesChange = (checked: boolean) => {
    onChange({
      ...value,
      showOnlyWithFeatures: checked,
    });
  };

  const handleStatusChange = (status: "active" | "paused" | "all") => {
    onChange({
      ...value,
      status,
    });
  };

  const handleSearchChange = (search: string) => {
    onChange({
      ...value,
      search,
    });
  };

  const handleSortByChange = (sortBy: string) => {
    onChange({
      ...value,
      sortBy,
    });
  };

  const handleSortOrderChange = (sortOrder: "asc" | "desc") => {
    onChange({
      ...value,
      sortOrder,
    });
  };

  const clearFilters = () => {
    onChange({
      serpFeature: "all",
      showOnlyWithFeatures: false,
      status: "all",
      search: "",
      sortBy: "keyword",
      sortOrder: "asc",
    });
  };

  const hasActiveFilters = 
    value.serpFeature !== "all" || 
    value.showOnlyWithFeatures || 
    value.status !== "all" ||
    value.search.trim() !== "";

  return (
    <div className="space-y-4">
      {/* Primary Filter Row */}
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
          {/* Search Input */}
          <input
            type="text"
            value={value.search}
            onChange={(e) => handleSearchChange(e.target.value)}
            placeholder="Search keywords"
            className="h-10 flex-1 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
          />

          {/* SERP Feature Filter Dropdown */}
          <div className="relative">
            <select
              value={value.serpFeature}
              onChange={(e) => handleFeatureChange(e.target.value)}
              className="h-10 min-w-[180px] rounded-md border border-slate-700 bg-slate-950/60 px-3 pr-8 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              aria-label="SERP Features"
              disabled={isLoading}
            >
              {isLoading ? (
                <option value="all">Loading features...</option>
              ) : (
                featureOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))
              )}
            </select>
            {isLoading && (
              <Loader2 className="absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-slate-400" />
            )}
          </div>

          {/* Status Filter */}
          {showStatusFilter && (
            <select
              value={value.status}
              onChange={(e) => handleStatusChange(e.target.value as "active" | "paused" | "all")}
              className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              aria-label="Status Filter"
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="paused">Paused</option>
            </select>
          )}

          {/* Sort Controls */}
          {showSortControls && (
            <>
              <select
                value={value.sortBy}
                onChange={(e) => handleSortByChange(e.target.value)}
                className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                aria-label="Sort By"
              >
                <option value="keyword">Sort: Keyword</option>
                <option value="position">Sort: Position</option>
                <option value="lastChecked">Sort: Last Checked</option>
              </select>
              <select
                value={value.sortOrder}
                onChange={(e) => handleSortOrderChange(e.target.value as "asc" | "desc")}
                className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                aria-label="Sort Order"
              >
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
              </select>
            </>
          )}
        </div>

        {/* Clear Filters Button */}
        {hasActiveFilters && (
          <button
            onClick={clearFilters}
            className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-4 text-sm text-slate-300 hover:bg-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500"
          >
            Clear Filters
          </button>
        )}
      </div>

      {/* Secondary Filter Row: SERP Feature Checkbox */}
      <div className="flex items-center gap-4">
        <label className="inline-flex cursor-pointer items-center gap-2">
          <input
            type="checkbox"
            checked={value.showOnlyWithFeatures}
            onChange={(e) => handleShowOnlyWithFeaturesChange(e.target.checked)}
            className="h-4 w-4 rounded border-slate-600 bg-slate-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900"
          />
          <span className="text-sm text-slate-300">
            Show Only Keywords With SERP Features
          </span>
        </label>
      </div>

      {/* Active Filter Tags */}
      {hasActiveFilters && (
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs text-slate-500">Active filters:</span>
          {value.serpFeature !== "all" && (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-300">
              {featureOptions.find((f) => f.value === value.serpFeature)?.label ?? value.serpFeature}
              <button
                onClick={() => handleFeatureChange("all")}
                className="hover:text-emerald-100"
              >
                ×
              </button>
            </span>
          )}
          {value.showOnlyWithFeatures && (
            <span className="inline-flex items-center gap-1 rounded-full bg-blue-500/15 px-2 py-0.5 text-xs font-medium text-blue-300">
              With Features
              <button
                onClick={() => handleShowOnlyWithFeaturesChange(false)}
                className="hover:text-blue-100"
              >
                ×
              </button>
            </span>
          )}
          {value.status !== "all" && (
            <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-300">
              {value.status}
              <button
                onClick={() => handleStatusChange("all")}
                className="hover:text-amber-100"
              >
                ×
              </button>
            </span>
          )}
        </div>
      )}
    </div>
  );
}

/**
 * Default filter state for initialization.
 */
export const defaultKeywordFilterState: KeywordFilterState = {
  serpFeature: "all",
  showOnlyWithFeatures: false,
  status: "all",
  search: "",
  sortBy: "keyword",
  sortOrder: "asc",
};
