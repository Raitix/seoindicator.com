"use client";

import * as React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ArrowDown, ArrowUp, Loader2, Minus, RefreshCcw } from "lucide-react";

import type { Keyword } from "@/lib/types/keywords";
import {
  bulkDeleteKeywords,
  bulkUpdateKeywordStatus,
  defaultKeywordMeta,
  type KeywordSortBy,
  type KeywordSortOrder,
  useKeywords,
} from "@/lib/api/keywords";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

const PER_PAGE = 20;

const sortOptions: { label: string; value: KeywordSortBy }[] = [
  { label: "Keyword", value: "keyword" },
  { label: "Position", value: "position" },
  { label: "Change", value: "change" },
  { label: "Last checked", value: "lastChecked" },
];

const statusOptions = [
  { label: "All", value: "all" },
  { label: "Active", value: "active" },
  { label: "Paused", value: "paused" },
];

const formatDate = (value: string | null) => {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";
  return date.toLocaleString();
};

const getChangeBadge = (keyword: Keyword) => {
  if (keyword.positionChange === null) {
    if (keyword.currentPosition !== null && keyword.previousPosition === null) {
      return (
        <span className="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-200">
          New
        </span>
      );
    }
    return <span className="text-slate-500">—</span>;
  }

  if (keyword.positionChange === 0) {
    return (
      <span className="inline-flex items-center gap-1 text-xs font-semibold text-slate-400">
        <Minus className="h-3.5 w-3.5" />
        0
      </span>
    );
  }

  const improved = keyword.positionChange < 0;
  const Icon = improved ? ArrowUp : ArrowDown;
  return (
    <span
      className={`inline-flex items-center gap-1 text-xs font-semibold ${
        improved ? "text-emerald-300" : "text-rose-300"
      }`}
    >
      <Icon className="h-3.5 w-3.5" />
      {keyword.positionChange}
    </span>
  );
};

const getPositionLabel = (position: number | null) => {
  if (position === null) {
    return "Not ranked";
  }
  if (position > 100) {
    return "Not in top 100";
  }
  return position.toString();
};

export default function KeywordList({ siteId }: { siteId: string }) {
  const router = useRouter();
  const [statusFilter, setStatusFilter] = React.useState<
    "active" | "paused" | "all"
  >("all");
  const [search, setSearch] = React.useState("");
  const [sortBy, setSortBy] = React.useState<KeywordSortBy>("keyword");
  const [sortOrder, setSortOrder] = React.useState<KeywordSortOrder>("asc");
  const [page, setPage] = React.useState(1);
  const [selected, setSelected] = React.useState<Set<number>>(new Set());
  const [bulkBusy, setBulkBusy] = React.useState(false);

  const { keywords, meta, isLoading, error, mutate } = useKeywords(siteId, {
    status: statusFilter,
    page,
    perPage: PER_PAGE,
    sortBy,
    sortOrder,
  });

  const filteredKeywords = React.useMemo(() => {
    let list = [...keywords];

    if (search.trim()) {
      const query = search.trim().toLowerCase();
      list = list.filter((item) => item.keyword.toLowerCase().includes(query));
    }

    if (sortBy === "change") {
      list.sort((a, b) => {
        const aChange = a.positionChange ?? Number.POSITIVE_INFINITY;
        const bChange = b.positionChange ?? Number.POSITIVE_INFINITY;
        return sortOrder === "asc" ? aChange - bChange : bChange - aChange;
      });
    }

    return list;
  }, [keywords, search, sortBy, sortOrder]);

  const metaInfo = meta ?? defaultKeywordMeta;
  const totalPages = Math.max(1, Math.ceil(metaInfo.total / metaInfo.perPage));

  const toggleSelectAll = () => {
    if (selected.size === filteredKeywords.length) {
      setSelected(new Set());
      return;
    }
    setSelected(new Set(filteredKeywords.map((item) => item.id)));
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

  const handleBulkAction = async (action: "pause" | "resume" | "delete") => {
    if (selected.size === 0) return;
    setBulkBusy(true);
    const ids = Array.from(selected.values());
    try {
      if (action === "delete") {
        await bulkDeleteKeywords(ids);
      } else {
        await bulkUpdateKeywordStatus(ids, action === "pause" ? "paused" : "active");
      }
      setSelected(new Set());
      await mutate();
    } catch (err) {
      console.error(err);
    } finally {
      setBulkBusy(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 className="text-2xl font-semibold text-white">Keyword list</h2>
          <p className="text-slate-400">
            Track keyword positions, changes, and ranking health across your sites.
          </p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div className="rounded-full border border-slate-700 bg-slate-900/60 px-3 py-1 text-sm text-slate-300">
            {metaInfo.total} total keywords
          </div>
          <Button asChild className="bg-emerald-500 text-white hover:bg-emerald-600">
            <Link href={`/sites/${siteId}/keywords/new`}>Add Keyword</Link>
          </Button>
        </div>
      </div>

      <Card className="border-slate-800 bg-slate-900/50">
        <CardContent className="space-y-4 p-6">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search keywords"
                className="bg-slate-950/60 text-white placeholder:text-slate-500"
              />
              <select
                className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200"
                value={statusFilter}
                onChange={(event) => {
                  setStatusFilter(event.target.value as "active" | "paused" | "all");
                  setPage(1);
                }}
              >
                {statusOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <select
                className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200"
                value={sortBy}
                onChange={(event) => setSortBy(event.target.value as KeywordSortBy)}
              >
                {sortOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    Sort: {option.label}
                  </option>
                ))}
              </select>
              <select
                className="h-10 rounded-md border border-slate-700 bg-slate-950/60 px-3 text-sm text-slate-200"
                value={sortOrder}
                onChange={(event) => setSortOrder(event.target.value as KeywordSortOrder)}
              >
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
              </select>
            </div>
            <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
              <span>{selected.size} selected</span>
              <Button
                variant="ghost"
                className="border border-slate-700 text-slate-200"
                disabled={bulkBusy || selected.size === 0}
                onClick={() => handleBulkAction("pause")}
              >
                Pause
              </Button>
              <Button
                variant="ghost"
                className="border border-slate-700 text-slate-200"
                disabled={bulkBusy || selected.size === 0}
                onClick={() => handleBulkAction("resume")}
              >
                Resume
              </Button>
              <Button
                variant="ghost"
                className="border border-rose-500/40 text-rose-200"
                disabled={bulkBusy || selected.size === 0}
                onClick={() => handleBulkAction("delete")}
              >
                Delete
              </Button>
            </div>
          </div>

          {isLoading && (
            <div className="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/40 px-4 py-6 text-slate-400">
              <Loader2 className="h-4 w-4 animate-spin" />
              Loading keywords...
            </div>
          )}

          {!isLoading && error && (
            <div className="rounded-lg border border-rose-500/40 bg-rose-500/10 p-4 text-rose-200">
              <div className="flex items-center gap-2 font-semibold">
                Failed to load keywords
              </div>
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

          {!isLoading && !error && filteredKeywords.length === 0 && (
            <div className="rounded-lg border border-slate-800 bg-slate-950/40 p-6 text-center text-slate-300">
              <p className="text-lg font-semibold text-white">No keywords yet</p>
              <p className="text-sm text-slate-400">
                Add your first keyword to start tracking positions.
              </p>
            </div>
          )}

          {!isLoading && !error && filteredKeywords.length > 0 && (
            <div className="overflow-hidden rounded-lg border border-slate-800">
              <div className="max-w-full overflow-x-auto">
                <table className="min-w-full text-sm text-slate-200">
                  <thead className="bg-slate-900/80 text-xs uppercase text-slate-400">
                    <tr>
                      <th className="px-4 py-3">
                        <input
                          type="checkbox"
                          checked={selected.size === filteredKeywords.length}
                          onChange={toggleSelectAll}
                        />
                      </th>
                      <th className="px-4 py-3 text-left">Keyword</th>
                      <th className="px-4 py-3 text-left">Position</th>
                      <th className="px-4 py-3 text-left">Change</th>
                      <th className="px-4 py-3 text-left">Last checked</th>
                      <th className="px-4 py-3 text-left">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredKeywords.map((keyword) => (
                      <tr
                        key={keyword.id}
                        className="border-t border-slate-800 hover:bg-slate-900/40"
                        onClick={(event) => {
                          if ((event.target as HTMLElement).tagName === "INPUT") {
                            return;
                          }
                          router.push(`/sites/${siteId}/keywords/${keyword.id}`);
                        }}
                      >
                        <td className="px-4 py-3">
                          <input
                            type="checkbox"
                            checked={selected.has(keyword.id)}
                            onChange={() => toggleSelection(keyword.id)}
                          />
                        </td>
                        <td className="px-4 py-3 text-white">
                          <div className="flex flex-col">
                            <Link
                              href={`/sites/${siteId}/keywords/${keyword.id}`}
                              className="font-semibold text-white hover:text-emerald-300"
                            >
                              {keyword.keyword}
                            </Link>
                            {keyword.targetUrl && (
                              <span className="text-xs text-slate-500">
                                {keyword.targetUrl}
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <span className="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-semibold text-slate-200">
                            {getPositionLabel(keyword.currentPosition)}
                          </span>
                        </td>
                        <td className="px-4 py-3">{getChangeBadge(keyword)}</td>
                        <td className="px-4 py-3 text-slate-400">
                          {formatDate(keyword.lastCheckedAt)}
                        </td>
                        <td className="px-4 py-3">
                          <span
                            className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                              keyword.status === "active"
                                ? "bg-emerald-500/15 text-emerald-200"
                                : "bg-amber-500/15 text-amber-200"
                            }`}
                          >
                            {keyword.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {!isLoading && !error && filteredKeywords.length > 0 && (
            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400">
              <span>
                Page {metaInfo.page} of {totalPages}
              </span>
              <div className="flex items-center gap-2">
                <Button
                  variant="ghost"
                  className="border border-slate-700 text-slate-200"
                  disabled={page <= 1}
                  onClick={() => setPage((prev) => Math.max(1, prev - 1))}
                >
                  Previous
                </Button>
                <Button
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
  );
}
