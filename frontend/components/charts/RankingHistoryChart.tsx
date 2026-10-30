'use client';

import React, { useMemo, useRef, useState } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  ReferenceLine,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, LineChart as LineChartIcon } from 'lucide-react';

export interface RankingHistoryPoint {
  date: string; // ISO string
  position: number | null; // null or >100 means not in top 100
}

type DateRangeOption = '7d' | '30d' | '90d' | 'all';

interface RankingHistoryChartProps {
  keywordName: string;
  data: RankingHistoryPoint[];
  isLoading?: boolean;
  initialRange?: DateRangeOption;
  onRangeChange?: (range: DateRangeOption) => void;
}

interface TooltipPayload {
  value?: number | null;
  payload?: {
    date: string;
    rawPosition: number | null;
  };
}

const rangeOptions: { label: string; value: DateRangeOption }[] = [
  { label: '7d', value: '7d' },
  { label: '30d', value: '30d' },
  { label: '90d', value: '90d' },
  { label: 'All', value: 'all' },
];

const formatDate = (value: string) => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
  }).format(date);
};

const getDateRange = (data: RankingHistoryPoint[], range: DateRangeOption) => {
  if (range === 'all') return data;
  if (data.length === 0) return data;

  const sorted = [...data].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
  const endDate = new Date(sorted[sorted.length - 1].date);
  const days = range === '7d' ? 7 : range === '30d' ? 30 : 90;
  const startDate = new Date(endDate);
  startDate.setDate(endDate.getDate() - days + 1);

  return sorted.filter((point) => {
    const date = new Date(point.date);
    return date >= startDate && date <= endDate;
  });
};

const RankingHistoryChart: React.FC<RankingHistoryChartProps> = ({
  keywordName,
  data,
  isLoading = false,
  initialRange = '30d',
  onRangeChange,
}) => {
  const [range, setRange] = useState<DateRangeOption>(initialRange);
  const [isExporting, setIsExporting] = useState(false);
  const chartRef = useRef<HTMLDivElement | null>(null);

  const filteredData = useMemo(() => getDateRange(data, range), [data, range]);

  const chartData = useMemo(
    () =>
      filteredData.map((point) => {
        const normalizedPosition = point.position && point.position <= 100 ? point.position : null;
        return {
          date: point.date,
          displayDate: formatDate(point.date),
          position: normalizedPosition,
          rawPosition: point.position,
        };
      }),
    [filteredData]
  );

  const handleRangeChange = (nextRange: DateRangeOption) => {
    setRange(nextRange);
    onRangeChange?.(nextRange);
  };

  const handleExport = async () => {
    if (!chartRef.current) return;
    const svg = chartRef.current.querySelector('svg');
    if (!svg) return;

    setIsExporting(true);
    try {
      const serializer = new XMLSerializer();
      const svgString = serializer.serializeToString(svg);
      const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
      const url = URL.createObjectURL(svgBlob);
      const image = new Image();

      const canvas = document.createElement('canvas');
      const rect = svg.getBoundingClientRect();
      const scale = window.devicePixelRatio || 1;
      canvas.width = rect.width * scale;
      canvas.height = rect.height * scale;
      const ctx = canvas.getContext('2d');
      if (!ctx) return;
      ctx.scale(scale, scale);

      await new Promise<void>((resolve, reject) => {
        image.onload = () => resolve();
        image.onerror = () => reject(new Error('Failed to load chart image'));
        image.src = url;
      });

      ctx.drawImage(image, 0, 0, rect.width, rect.height);
      URL.revokeObjectURL(url);

      const pngUrl = canvas.toDataURL('image/png');
      const link = document.createElement('a');
      link.href = pngUrl;
      link.download = `${keywordName.replace(/\s+/g, '-').toLowerCase()}-ranking-history.png`;
      link.click();
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <CardTitle className="text-lg flex items-center gap-2">
            <LineChartIcon className="h-5 w-5" />
            Ranking History — {keywordName}
          </CardTitle>
          <p className="text-sm text-muted-foreground">
            Track position changes over time and identify visibility drops.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex items-center gap-1 rounded-full border p-1">
            {rangeOptions.map((option) => (
              <Button
                key={option.value}
                type="button"
                variant={range === option.value ? 'default' : 'ghost'}
                size="sm"
                onClick={() => handleRangeChange(option.value)}
              >
                {option.label}
              </Button>
            ))}
          </div>
          <Button type="button" variant="outline" size="sm" onClick={handleExport} disabled={isExporting}>
            <Download className="h-4 w-4 mr-2" />
            {isExporting ? 'Exporting...' : 'Export PNG'}
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center justify-center py-16">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : chartData.length === 0 ? (
          <div className="flex items-center justify-center py-16 text-muted-foreground">
            No ranking history available for this keyword.
          </div>
        ) : (
          <div ref={chartRef} className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chartData} margin={{ top: 10, right: 24, left: 8, bottom: 24 }}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis
                  dataKey="displayDate"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  angle={-35}
                  textAnchor="end"
                  height={50}
                />
                <YAxis
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  domain={[1, 100]}
                  reversed
                  tickFormatter={(value) => `${value}`}
                />
                <ReferenceLine y={100} stroke="#94a3b8" strokeDasharray="4 4" />
                <Tooltip
                  content={({ active, payload }) => {
                    if (!active || !payload || payload.length === 0) return null;
                    const datum = payload[0] as TooltipPayload;
                    const dateLabel = datum?.payload?.date ? formatDate(datum.payload.date) : '';
                    const rawPosition = datum?.payload?.rawPosition ?? null;
                    return (
                      <div className="bg-background border rounded-lg shadow-lg p-3">
                        <p className="font-medium mb-2">{dateLabel}</p>
                        <p className="text-sm text-foreground">
                          Position:{' '}
                          {rawPosition && rawPosition <= 100 ? rawPosition : 'Not in top 100'}
                        </p>
                      </div>
                    );
                  }}
                />
                <Line
                  type="monotone"
                  dataKey="position"
                  stroke="#2563eb"
                  strokeWidth={2}
                  dot={false}
                  connectNulls={false}
                  activeDot={{ r: 4 }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default RankingHistoryChart;
