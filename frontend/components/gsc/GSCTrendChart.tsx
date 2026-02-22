'use client';

import React from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { GSCTrendData } from '@/lib/types/gsc';
import { formatDate, formatCTR, formatNumber } from '@/lib/api/gsc';
import { TrendingUp } from 'lucide-react';

interface GSCTrendChartProps {
  data: GSCTrendData[];
  isLoading?: boolean;
}

interface CustomTooltipProps {
  active?: boolean;
  payload?: Array<{
    name: string;
    value: number;
    color: string;
  }>;
  label?: string;
}

const CustomTooltip: React.FC<CustomTooltipProps> = ({ active, payload, label }) => {
  if (!active || !payload || !label) return null;

  return (
    <div className="bg-background border rounded-lg shadow-lg p-3">
      <p className="font-medium mb-2">{formatDate(label)}</p>
      {payload.map((entry, index) => (
        <p key={index} className="text-sm" style={{ color: entry.color }}>
          {entry.name === 'CTR' 
            ? `${entry.name}: ${formatCTR(entry.value)}`
            : entry.name === 'Avg Position'
            ? `${entry.name}: ${entry.value.toFixed(1)}`
            : `${entry.name}: ${formatNumber(entry.value)}`}
        </p>
      ))}
    </div>
  );
};

const GSCTrendChart: React.FC<GSCTrendChartProps> = ({ data, isLoading = false }) => {
  // Format dates for display
  const chartData = data.map((item) => ({
    ...item,
    displayDate: formatDate(item.date),
  }));

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <TrendingUp className="h-5 w-5" />
          Performance Trends
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center justify-center py-16">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : data.length === 0 ? (
          <div className="flex items-center justify-center py-16 text-muted-foreground">
            No trend data available for the selected date range.
          </div>
        ) : (
          <div className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart
                data={chartData}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis
                  dataKey="displayDate"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  angle={-45}
                  textAnchor="end"
                  height={60}
                />
                <YAxis
                  yAxisId="left"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                />
                <YAxis
                  yAxisId="right"
                  orientation="right"
                  tick={{ fontSize: 12 }}
                  className="text-muted-foreground"
                  domain={[1, 'dataMax']}
                  reversed
                />
                <Tooltip content={<CustomTooltip />} />
                <Legend />
                <Line
                  yAxisId="left"
                  type="monotone"
                  dataKey="clicks"
                  stroke="#2563eb"
                  strokeWidth={2}
                  dot={false}
                  name="Clicks"
                  activeDot={{ r: 4 }}
                />
                <Line
                  yAxisId="left"
                  type="monotone"
                  dataKey="impressions"
                  stroke="#7c3aed"
                  strokeWidth={2}
                  dot={false}
                  name="Impressions"
                  activeDot={{ r: 4 }}
                />
                <Line
                  yAxisId="right"
                  type="monotone"
                  dataKey="avg_position"
                  stroke="#059669"
                  strokeWidth={2}
                  dot={false}
                  name="Avg Position"
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

export default GSCTrendChart;
