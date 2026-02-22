"use client";

import { useEffect, useMemo, useState } from "react";
import Papa from "papaparse";
import {
  ColumnDef,
  SortingState,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

const competitorsList = ["Searchly", "RankForge", "VistaSEO"] as const;

type CompetitorName = (typeof competitorsList)[number];

type ComparisonRow = {
  keyword: string;
  myPosition: number | null;
  competitors: Record<CompetitorName, number | null>;
  status: "winning" | "losing" | "gap" | "both";
  bestCompetitorPosition: number | null;
  diff: number | null;
};

const mockRows: ComparisonRow[] = [
  {
    keyword: "seo dashboard",
    myPosition: 3,
    competitors: { Searchly: 5, RankForge: 8, VistaSEO: 6 },
    status: "winning",
    bestCompetitorPosition: 5,
    diff: 2,
  },
  {
    keyword: "keyword gap tool",
    myPosition: 12,
    competitors: { Searchly: 6, RankForge: 9, VistaSEO: 11 },
    status: "losing",
    bestCompetitorPosition: 6,
    diff: -6,
  },
  {
    keyword: "competitor ranking tracker",
    myPosition: null,
    competitors: { Searchly: 7, RankForge: 10, VistaSEO: 9 },
    status: "gap",
    bestCompetitorPosition: 7,
    diff: null,
  },
  {
    keyword: "serp visibility score",
    myPosition: 9,
    competitors: { Searchly: 9, RankForge: 15, VistaSEO: 14 },
    status: "both",
    bestCompetitorPosition: 9,
    diff: 0,
  },
  {
    keyword: "seo audit insights",
    myPosition: 2,
    competitors: { Searchly: 4, RankForge: null, VistaSEO: 5 },
    status: "winning",
    bestCompetitorPosition: 4,
    diff: 2,
  },
  {
    keyword: "site health score",
    myPosition: 18,
    competitors: { Searchly: 13, RankForge: 12, VistaSEO: 16 },
    status: "losing",
    bestCompetitorPosition: 12,
    diff: -6,
  },
  {
    keyword: "technical seo report",
    myPosition: 6,
    competitors: { Searchly: null, RankForge: 7, VistaSEO: 8 },
    status: "both",
    bestCompetitorPosition: 7,
    diff: 1,
  },
  {
    keyword: "content opportunity finder",
    myPosition: null,
    competitors: { Searchly: 10, RankForge: 14, VistaSEO: 13 },
    status: "gap",
    bestCompetitorPosition: 10,
    diff: null,
  },
];

const statusMeta = {
  winning: { label: "I’m Winning", className: "bg-emerald-500/15 text-emerald-200 border-emerald-500/40" },
  losing: { label: "They’re Winning", className: "bg-rose-500/15 text-rose-200 border-rose-500/40" },
  gap: { label: "Keyword Gap", className: "bg-amber-500/15 text-amber-200 border-amber-500/40" },
  both: { label: "Both Rank", className: "bg-sky-500/15 text-sky-200 border-sky-500/40" },
};

const filterOptions = ["All", "I’m Winning", "They’re Winning", "Keyword Gaps", "Both Rank"] as const;
const sortOptions = ["Keyword", "My Position", "Competitor Position", "Diff"] as const;

type FilterOption = (typeof filterOptions)[number];
type SortOption = (typeof sortOptions)[number];

type ColumnMeta = {
  hidden?: boolean;
};

function formatPosition(position: number | null) {
  return position === null ? "—" : `#${position}`;
}

export default function ComparisonDashboard() {
  const [filterOption, setFilterOption] = useState<FilterOption>("All");
  const [sortOption, setSortOption] = useState<SortOption>("Keyword");
  const [search, setSearch] = useState("");
  const [visibleCompetitors, setVisibleCompetitors] = useState<CompetitorName[]>([...competitorsList]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => setIsLoading(false), 600);
    return () => clearTimeout(timer);
  }, []);

  const filteredRows = useMemo(() => {
    const term = search.trim().toLowerCase();
    return mockRows.filter((row) => {
      const matchesSearch = term.length === 0 || row.keyword.toLowerCase().includes(term);
      if (!matchesSearch) return false;

      switch (filterOption) {
        case "I’m Winning":
          return row.status === "winning";
        case "They’re Winning":
          return row.status === "losing";
        case "Keyword Gaps":
          return row.status === "gap";
        case "Both Rank":
          return row.status === "both";
        default:
          return true;
      }
    });
  }, [filterOption, search]);

  const summary = useMemo(() => {
    const total = filteredRows.length;
    const winning = filteredRows.filter((row) => row.status === "winning").length;
    const losing = filteredRows.filter((row) => row.status === "losing").length;
    const gap = filteredRows.filter((row) => row.status === "gap").length;
    return { total, winning, losing, gap };
  }, [filteredRows]);

  const columns = useMemo<ColumnDef<ComparisonRow, unknown>[]>(() => {
    const competitorColumns: ColumnDef<ComparisonRow, unknown>[] = competitorsList.map((competitor) => ({
      id: competitor,
      header: competitor,
      accessorFn: (row) => row.competitors[competitor],
      cell: ({ row }) => {
        const value = row.original.competitors[competitor];
        const diff = value && row.original.myPosition ? row.original.myPosition - value : null;
        const diffLabel = diff === null ? "" : diff > 0 ? `(+${diff})` : `(${diff})`;
        return (
          <div className="flex flex-col gap-1">
            <span className="text-slate-200">{formatPosition(value)}</span>
            <span className="text-xs text-slate-400">{diffLabel}</span>
          </div>
        );
      },
      meta: {
        hidden: !visibleCompetitors.includes(competitor),
      } satisfies ColumnMeta,
    }));

    return [
      {
        accessorKey: "keyword",
        header: "Keyword",
        cell: ({ row }) => <span className="font-medium text-slate-100">{row.original.keyword}</span>,
      },
      {
        accessorKey: "myPosition",
        header: "My Position",
        cell: ({ row }) => <span className="text-slate-200">{formatPosition(row.original.myPosition)}</span>,
      },
      {
        id: "bestCompetitor",
        accessorFn: (row) => row.bestCompetitorPosition ?? Number.POSITIVE_INFINITY,
        header: "Best Competitor",
        meta: { hidden: true } satisfies ColumnMeta,
      },
      {
        id: "diff",
        accessorFn: (row) => (row.diff === null ? Number.NEGATIVE_INFINITY : row.diff),
        header: "Diff",
        meta: { hidden: true } satisfies ColumnMeta,
      },
      ...competitorColumns,
      {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) => {
          const meta = statusMeta[row.original.status];
          return (
            <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${meta.className}`}>
              {meta.label}
            </span>
          );
        },
      },
    ];
  }, [visibleCompetitors]);

  const sorting = useMemo<SortingState>(() => {
    switch (sortOption) {
      case "My Position":
        return [{ id: "myPosition", desc: false }];
      case "Competitor Position":
        return [{ id: "bestCompetitor", desc: false }];
      case "Diff":
        return [{ id: "diff", desc: true }];
      default:
        return [{ id: "keyword", desc: false }];
    }
  }, [sortOption]);

  const table = useReactTable({
    data: filteredRows,
    columns,
    state: { sorting },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 6 } },
  });

  const visibleColumns = table.getAllColumns().filter((column) => !(column.columnDef.meta as ColumnMeta)?.hidden);

  function handleExport() {
    const rowsToExport = table.getRowModel().rows.map((row) => ({
      Keyword: row.original.keyword,
      "My Position": row.original.myPosition ?? "",
      Status: statusMeta[row.original.status].label,
      ...Object.fromEntries(
        competitorsList.map((competitor) => [competitor, row.original.competitors[competitor] ?? ""])
      ),
    }));

    const csv = Papa.unparse(rowsToExport);
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "comparison-dashboard.csv";
    link.click();
    URL.revokeObjectURL(url);
  }

  if (isLoading) {
    return (
      <div className="space-y-8">
        <div className="grid gap-4 md:grid-cols-4">
          {Array.from({ length: 4 }).map((_, index) => (
            <Card key={index} className="border-slate-800 bg-slate-900/60">
              <CardHeader>
                <div className="h-4 w-24 animate-pulse rounded bg-slate-800" />
                <div className="mt-3 h-8 w-16 animate-pulse rounded bg-slate-800" />
              </CardHeader>
            </Card>
          ))}
        </div>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <div className="h-5 w-40 animate-pulse rounded bg-slate-800" />
            <div className="mt-3 h-4 w-72 animate-pulse rounded bg-slate-800" />
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, index) => (
                <div key={index} className="h-10 w-full animate-pulse rounded bg-slate-800" />
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-4">
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Total Keywords</CardDescription>
            <CardTitle className="text-3xl text-white">{summary.total}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>I’m Winning</CardDescription>
            <CardTitle className="text-3xl text-emerald-300">{summary.winning}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>They’re Winning</CardDescription>
            <CardTitle className="text-3xl text-rose-300">{summary.losing}</CardTitle>
          </CardHeader>
        </Card>
        <Card className="border-slate-800 bg-slate-900/60">
          <CardHeader>
            <CardDescription>Keyword Gaps</CardDescription>
            <CardTitle className="text-3xl text-amber-300">{summary.gap}</CardTitle>
          </CardHeader>
        </Card>
      </div>

      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader className="gap-3">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <CardTitle className="text-white">Comparison Dashboard</CardTitle>
              <CardDescription>Compare rankings side-by-side and export the gaps.</CardDescription>
            </div>
            <Button onClick={handleExport} className="bg-emerald-500 text-white hover:bg-emerald-600">
              Export CSV
            </Button>
          </div>
          <div className="flex flex-wrap gap-3">
            <Input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search keyword..."
              className="max-w-xs border-slate-700 bg-slate-950 text-slate-200"
            />
            <select
              value={filterOption}
              onChange={(event) => setFilterOption(event.target.value as FilterOption)}
              className="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200"
            >
              {filterOptions.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </select>
            <select
              value={sortOption}
              onChange={(event) => setSortOption(event.target.value as SortOption)}
              className="rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200"
            >
              {sortOptions.map((option) => (
                <option key={option} value={option}>
                  Sort by {option}
                </option>
              ))}
            </select>
          </div>
          <div className="flex flex-wrap gap-2">
            {competitorsList.map((competitor) => (
              <label
                key={competitor}
                className="flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs text-slate-200"
              >
                <input
                  type="checkbox"
                  checked={visibleCompetitors.includes(competitor)}
                  onChange={(event) => {
                    setVisibleCompetitors((prev) =>
                      event.target.checked
                        ? [...prev, competitor]
                        : prev.filter((item) => item !== competitor)
                    );
                  }}
                  className="accent-emerald-500"
                />
                {competitor}
              </label>
            ))}
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto rounded-lg border border-slate-800">
            <table className="min-w-full text-left text-sm">
              <thead className="bg-slate-950 text-slate-400">
                <tr>
                  {visibleColumns.map((column) => (
                    <th key={column.id} className="px-4 py-3 font-semibold">
                      {column.columnDef.header as string}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {table.getRowModel().rows.length === 0 ? (
                  <tr>
                    <td colSpan={visibleColumns.length} className="px-4 py-6 text-center text-slate-400">
                      No matching keywords found.
                    </td>
                  </tr>
                ) : (
                  table.getRowModel().rows.map((row) => (
                    <tr key={row.id} className="hover:bg-slate-950/60">
                      {row.getVisibleCells().map((cell) => {
                        const hidden = (cell.column.columnDef.meta as ColumnMeta)?.hidden;
                        if (hidden) return null;
                        return (
                          <td key={cell.id} className="px-4 py-3 text-slate-200">
                            {cell.renderValue() as React.ReactNode}
                          </td>
                        );
                      })}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400">
            <div>
              Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
            </div>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                className="border-slate-700 text-slate-200"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                className="border-slate-700 text-slate-200"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
              >
                Next
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
