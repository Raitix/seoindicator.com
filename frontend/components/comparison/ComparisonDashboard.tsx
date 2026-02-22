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

import ComparisonControls from "@/components/comparison/ComparisonControls";
import ComparisonHeader from "@/components/comparison/ComparisonHeader";
import ComparisonLayout from "@/components/comparison/ComparisonLayout";
import ComparisonSearch from "@/components/comparison/ComparisonSearch";
import ComparisonSkeleton from "@/components/comparison/ComparisonSkeleton";
import ComparisonTable from "@/components/comparison/ComparisonTable";
import CompetitorVisibilityToggle from "@/components/comparison/CompetitorVisibilityToggle";
import DiffBadge from "@/components/comparison/DiffBadge";
import ExportButton from "@/components/comparison/ExportButton";
import FilterDropdown from "@/components/comparison/FilterDropdown";
import PaginationControls from "@/components/comparison/PaginationControls";
import SortControl from "@/components/comparison/SortControl";
import SummaryCard from "@/components/comparison/SummaryCard";

import {
  competitorsList,
  filterOptions,
  mockRows,
  sortOptions,
  statusMeta,
  type ComparisonRow,
  type FilterOption,
  type SortOption,
} from "@/components/comparison/comparison-data";

type ColumnMeta = {
  hidden?: boolean;
};

export default function ComparisonDashboard() {
  const [filterOption, setFilterOption] = useState<FilterOption>("All");
  const [sortOption, setSortOption] = useState<SortOption>("Keyword");
  const [search, setSearch] = useState("");
  const [visibleCompetitors, setVisibleCompetitors] = useState([...competitorsList]);
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

  const renderPosition = (position: number | null) => {
    if (position === null) {
      return (
        <span data-testid="position-null" className="text-slate-200">
          Not ranking
        </span>
      );
    }

    return <span className="text-slate-200">#{position}</span>;
  };

  const columns = useMemo<ColumnDef<ComparisonRow, unknown>[]>(() => {
    const competitorColumns: ColumnDef<ComparisonRow, unknown>[] = competitorsList.map((competitor) => ({
      id: competitor,
      header: competitor,
      accessorFn: (row) => row.competitors[competitor],
      cell: ({ row }) => {
        const value = row.original.competitors[competitor];
        const diff = value !== null && row.original.myPosition !== null ? row.original.myPosition - value : null;

        return (
          <div className="flex flex-col gap-1">
            {renderPosition(value)}
            <DiffBadge value={diff} />
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
        cell: ({ row }) => renderPosition(row.original.myPosition),
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
            <span
              className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${meta.className}`}
            >
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
    return <ComparisonSkeleton />;
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-4">
        <SummaryCard title="Total Keywords" value={summary.total} />
        <SummaryCard title="I’m Winning" value={summary.winning} valueClassName="text-emerald-300" />
        <SummaryCard title="They’re Winning" value={summary.losing} valueClassName="text-rose-300" />
        <SummaryCard title="Keyword Gaps" value={summary.gap} valueClassName="text-amber-300" />
      </div>

      <ComparisonLayout
        header={
          <div className="flex flex-wrap items-center justify-between gap-4">
            <ComparisonHeader
              title="Comparison Dashboard"
              description="Compare rankings side-by-side and export the gaps."
            />
            <ExportButton onExport={handleExport} />
          </div>
        }
        controls={
          <ComparisonControls
            search={<ComparisonSearch value={search} onChange={setSearch} />}
            filter={<FilterDropdown value={filterOption} options={filterOptions} onChange={setFilterOption} />}
            sort={<SortControl value={sortOption} options={sortOptions} onChange={setSortOption} />}
          />
        }
        toggles={
          <CompetitorVisibilityToggle
            competitors={competitorsList}
            visibleCompetitors={visibleCompetitors}
            onChange={setVisibleCompetitors}
          />
        }
        table={<ComparisonTable table={table} visibleColumns={visibleColumns} />}
        pagination={
          <PaginationControls
            pageIndex={table.getState().pagination.pageIndex}
            pageCount={table.getPageCount()}
            canPrevious={table.getCanPreviousPage()}
            canNext={table.getCanNextPage()}
            onPrevious={() => table.previousPage()}
            onNext={() => table.nextPage()}
          />
        }
      />
    </div>
  );
}
