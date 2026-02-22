import type { ReactNode } from "react";
import { flexRender } from "@tanstack/react-table";
import type { Column, Table } from "@tanstack/react-table";

import type { ComparisonRow } from "./comparison-data";

interface ComparisonTableProps {
  table: Table<ComparisonRow>;
  visibleColumns: Column<ComparisonRow, unknown>[];
}

export default function ComparisonTable({ table, visibleColumns }: ComparisonTableProps) {
  return (
    <div className="overflow-x-auto rounded-lg border border-slate-800">
      <table className="min-w-full text-left text-sm">
        <thead className="bg-slate-950 text-slate-400">
          <tr>
            {visibleColumns.map((column) => (
              <th
                key={column.id}
                className="px-4 py-3 font-semibold"
                data-testid={`column-${column.id}`}
              >
                {column.columnDef.header as string}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {table.getRowModel().rows.length === 0 ? (
            <tr>
              <td colSpan={visibleColumns.length} className="px-4 py-6 text-center text-slate-400">
                No keywords to compare.
              </td>
            </tr>
          ) : (
            table.getRowModel().rows.map((row) => (
              <tr key={row.id} className="hover:bg-slate-950/60">
                {row.getVisibleCells().map((cell) => {
                  const hidden = (cell.column.columnDef.meta as { hidden?: boolean })?.hidden;
                  if (hidden) return null;
                  const rendered = cell.column.columnDef.cell
                    ? flexRender(cell.column.columnDef.cell, cell.getContext())
                    : cell.getValue();

                  return (
                    <td key={cell.id} className="px-4 py-3 text-slate-200">
                      {rendered as ReactNode}
                    </td>
                  );
                })}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}
