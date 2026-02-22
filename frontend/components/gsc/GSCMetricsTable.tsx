'use client';

import React, { useState, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { GSCMetric, SortConfig, SortDirection } from '@/lib/types/gsc';
import { formatCTR, formatNumber, formatPosition } from '@/lib/api/gsc';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';

interface GSCMetricsTableProps {
  data: GSCMetric[];
  isLoading?: boolean;
}

type MetricKey = keyof GSCMetric;

const columnConfig: { key: MetricKey; label: string; format: (val: number | string) => string }[] = [
  { key: 'query', label: 'Query', format: (val) => String(val) },
  { key: 'clicks', label: 'Clicks', format: (val) => formatNumber(val as number) },
  { key: 'impressions', label: 'Impressions', format: (val) => formatNumber(val as number) },
  { key: 'ctr', label: 'CTR', format: (val) => formatCTR(val as number) },
  { key: 'avg_position', label: 'Avg Position', format: (val) => formatPosition(val as number) },
];

const GSCMetricsTable: React.FC<GSCMetricsTableProps> = ({ data, isLoading = false }) => {
  const [sortConfig, setSortConfig] = useState<SortConfig>({
    key: 'clicks',
    direction: 'desc',
  });

  const handleSort = (key: MetricKey) => {
    setSortConfig((prev) => ({
      key,
      direction: prev.key === key && prev.direction === 'desc' ? 'asc' : 'desc',
    }));
  };

  const sortedData = useMemo(() => {
    if (!data.length) return [];

    return [...data].sort((a, b) => {
      const aValue = a[sortConfig.key];
      const bValue = b[sortConfig.key];

      let comparison = 0;

      if (typeof aValue === 'string' && typeof bValue === 'string') {
        comparison = aValue.localeCompare(bValue);
      } else if (typeof aValue === 'number' && typeof bValue === 'number') {
        comparison = aValue - bValue;
      }

      return sortConfig.direction === 'asc' ? comparison : -comparison;
    });
  }, [data, sortConfig]);

  const getSortIcon = (key: MetricKey) => {
    if (sortConfig.key !== key) {
      return <ArrowUpDown className="ml-2 h-4 w-4 opacity-50" />;
    }
    return sortConfig.direction === 'asc' ? (
      <ArrowUp className="ml-2 h-4 w-4" />
    ) : (
      <ArrowDown className="ml-2 h-4 w-4" />
    );
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">Query Performance</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  {columnConfig.map((col) => (
                    <th
                      key={col.key}
                      className="px-4 py-3 text-left text-sm font-medium cursor-pointer hover:bg-muted/50 select-none"
                      onClick={() => handleSort(col.key)}
                    >
                      <div className="flex items-center">
                        {col.label}
                        {getSortIcon(col.key)}
                      </div>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {sortedData.length === 0 ? (
                  <tr>
                    <td colSpan={columnConfig.length} className="px-4 py-8 text-center text-muted-foreground">
                      No data available for the selected date range.
                    </td>
                  </tr>
                ) : (
                  sortedData.map((row, index) => (
                    <tr key={`${row.query}-${index}`} className="border-b hover:bg-muted/30 transition-colors">
                      {columnConfig.map((col) => (
                        <td key={col.key} className="px-4 py-3 text-sm">
                          {col.key === 'query' ? (
                            <span className="font-medium">{col.format(row[col.key])}</span>
                          ) : (
                            col.format(row[col.key])
                          )}
                        </td>
                      ))}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
        {!isLoading && sortedData.length > 0 && (
          <div className="mt-4 text-sm text-muted-foreground">
            Showing {sortedData.length} queries
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default GSCMetricsTable;
