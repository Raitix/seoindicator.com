"use client";

import React, { useMemo, useState, useCallback } from "react";
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import {
  LineChart as LineChartIcon,
  Users,
  Calendar,
  TrendingUp,
  TrendingDown,
} from "lucide-react";
import { useComparisonHistory, parseDateRangeOption, type DateRangeOption } from "@/lib/api/comparison-history";
import type { Keyword } from "@/lib/types/keywords";
import type { Competitor } from "@/lib/types/competitors";

// Color palette for competitors
const COMPETITOR_COLORS = [
  "#ef4444", // red
  "#22c55e", // green
  "#f59e0b", // amber
  "#8b5cf6", // violet
  "#ec4899", // pink
];

const USER_COLOR = "#2563eb"; // blue

interface ChartDataPoint {
  date: string;
  displayDate: string;
  userPosition: number | null;
  [key: string]: string | number | null;
}

interface HistoricalComparisonChartProps {
  /** List of keywords to select from */
  keywords: Keyword[];
  /** List of competitors to compare */
  competitors: Competitor[];
  /** Initial keyword ID to display */
  initialKeywordId?: number;
  /** Initial date range */
  initialDateRange?: DateRangeOption;
  /** Optional className for styling */
  className?: string;
}

/**
 * Formats a date string for display
 */
const formatDate = (value: string): string => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("en-US", {
    month: "short",
    day: "numeric",
  }).format(date);
};

/**
 * Formats a date string for tooltip header
 */
const formatFullDate = (value: string): string => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(date);
};

/**
 * Custom tooltip component for the chart
 */
const CustomTooltip: React.FC<{
  active?: boolean;
  payload?: readonly {
    dataKey: string;
    value: number | null;
    color: string;
  }[];
  label?: string | number;
  competitorNames: Record<string, string>;
  showUser: boolean;
}> = ({ active, payload, label, competitorNames, showUser }) => {
  if (!active || !payload || !label) return null;

  const dateLabel = formatFullDate(String(label));

  return (
    <div className="bg-background border rounded-lg shadow-lg p-3 min-w-[180px]">
      <p className="font-medium text-sm mb-2 text-muted-foreground">{dateLabel}</p>
      <div className="space-y-1.5">
        {payload.map((entry) => {
          const isUser = entry.dataKey === "userPosition";
          if (isUser && !showUser) return null;

          const displayName = isUser
            ? "You"
            : competitorNames[entry.dataKey] || entry.dataKey;
          const position = entry.value;

          return (
            <div key={entry.dataKey} className="flex items-center justify-between gap-4">
              <div className="flex items-center gap-2">
                <div
                  className="w-3 h-3 rounded-full"
                  style={{ backgroundColor: entry.color }}
                />
                <span className="text-sm">{displayName}</span>
              </div>
              <span className="text-sm font-medium">
                {position !== null && position !== undefined
                  ? `#${position}`
                  : "N/A"}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

/**
 * Custom legend component with toggle functionality
 */
const CustomLegend: React.FC<{
  payload?: readonly {
    dataKey: string;
    color: string;
    value: string;
  }[];
  competitorNames: Record<string, string>;
  visibleSeries: Record<string, boolean>;
  onToggle: (key: string) => void;
}> = ({ payload, competitorNames, visibleSeries, onToggle }) => {
  if (!payload) return null;

  return (
    <div className="flex flex-wrap items-center justify-center gap-4 pt-4">
      {payload.map((entry) => {
        const isUser = entry.dataKey === "userPosition";
        const displayName = isUser
          ? "You"
          : competitorNames[entry.dataKey] || entry.dataKey;
        const isVisible = visibleSeries[entry.dataKey] !== false;

        return (
          <button
            key={entry.dataKey}
            type="button"
            onClick={() => onToggle(entry.dataKey)}
            className={`flex items-center gap-2 px-2 py-1 rounded transition-opacity ${
              isVisible ? "opacity-100" : "opacity-40"
            }`}
          >
            <div
              className="w-3 h-3 rounded-full"
              style={{ backgroundColor: entry.color }}
            />
            <span className="text-sm">{displayName}</span>
          </button>
        );
      })}
    </div>
  );
};

/**
 * Date range selector component
 */
const DateRangeSelector: React.FC<{
  value: DateRangeOption;
  onChange: (value: DateRangeOption) => void;
  customStartDate?: Date;
  customEndDate?: Date;
  onCustomChange?: (start: Date, end: Date) => void;
}> = ({ value, onChange }) => {
  const options: { label: string; value: DateRangeOption }[] = [
    { label: "7 days", value: "7d" },
    { label: "30 days", value: "30d" },
    { label: "90 days", value: "90d" },
  ];

  return (
    <div className="flex items-center gap-1 rounded-full border p-1">
      {options.map((option) => (
        <Button
          key={option.value}
          type="button"
          variant={value === option.value ? "default" : "ghost"}
          size="sm"
          onClick={() => onChange(option.value)}
        >
          {option.label}
        </Button>
      ))}
    </div>
  );
};

/**
 * Keyword selector dropdown
 */
const KeywordSelector: React.FC<{
  keywords: Keyword[];
  value: number | null;
  onChange: (id: number) => void;
  disabled?: boolean;
}> = ({ keywords, value, onChange, disabled }) => {
  return (
    <Select
      value={value ? String(value) : ""}
      onValueChange={(v) => onChange(Number(v))}
      disabled={disabled}
    >
      <SelectTrigger className="w-[200px]">
        <SelectValue placeholder="Select keyword" />
      </SelectTrigger>
      <SelectContent>
        {keywords.map((kw) => (
          <SelectItem key={kw.id} value={String(kw.id)}>
            {kw.keyword}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};

/**
 * Competitor filter checkboxes
 */
const CompetitorFilter: React.FC<{
  competitors: Competitor[];
  visibleCompetitors: Record<number, boolean>;
  onChange: (id: number, visible: boolean) => void;
}> = ({ competitors, visibleCompetitors, onChange }) => {
  if (competitors.length === 0) return null;

  return (
    <div className="flex flex-wrap items-center gap-4">
      <span className="text-sm text-muted-foreground flex items-center gap-1">
        <Users className="h-4 w-4" />
        Competitors:
      </span>
      {competitors.map((competitor, index) => (
        <div key={competitor.id} className="flex items-center gap-2">
          <Checkbox
            id={`competitor-${competitor.id}`}
            checked={visibleCompetitors[competitor.id] !== false}
            onCheckedChange={(checked) => onChange(competitor.id, checked === true)}
          />
          <Label
            htmlFor={`competitor-${competitor.id}`}
            className="text-sm flex items-center gap-1.5 cursor-pointer"
          >
            <div
              className="w-2.5 h-2.5 rounded-full"
              style={{ backgroundColor: COMPETITOR_COLORS[index % COMPETITOR_COLORS.length] }}
            />
            {competitor.displayName || competitor.domain}
          </Label>
        </div>
      ))}
    </div>
  );
};

/**
 * Main Historical Comparison Chart component
 */
const HistoricalComparisonChart: React.FC<HistoricalComparisonChartProps> = ({
  keywords,
  competitors,
  initialKeywordId,
  initialDateRange = "30d",
  className,
}) => {
  const [selectedKeywordId, setSelectedKeywordId] = useState<number | null>(
    initialKeywordId ?? (keywords[0]?.id ?? null)
  );
  const [dateRange, setDateRange] = useState<DateRangeOption>(initialDateRange);
  const [visibleSeries, setVisibleSeries] = useState<Record<string, boolean>>({
    userPosition: true,
  });
  const [visibleCompetitors, setVisibleCompetitors] = useState<Record<number, boolean>>(() => {
    const initial: Record<number, boolean> = {};
    competitors.forEach((c) => {
      initial[c.id] = true;
    });
    return initial;
  });

  const days = parseDateRangeOption(dateRange);
  const { data, isLoading, error } = useComparisonHistory({
    keywordId: selectedKeywordId,
    days,
  });

  // Build competitor ID to name mapping
  const competitorNames = useMemo<Record<string, string>>(() => {
    const map: Record<string, string> = {};
    competitors.forEach((c) => {
      map[`competitor_${c.id}`] = c.displayName || c.domain;
    });
    return map;
  }, [competitors]);

  // Transform API data into chart-friendly format
  const chartData = useMemo<ChartDataPoint[]>(() => {
    if (!data?.history) return [];

    const { user, competitors: competitorHistory } = data.history;

    // Create a map of dates to positions
    const dateMap = new Map<string, ChartDataPoint>();

    // Add user positions
    user.forEach((point) => {
      const dateKey = point.checkedAt.split("T")[0];
      const existing = dateMap.get(dateKey) || {
        date: dateKey,
        displayDate: formatDate(point.checkedAt),
        userPosition: null,
      };
      existing.userPosition = point.position;
      dateMap.set(dateKey, existing);
    });

    // Add competitor positions
    competitorHistory.forEach((point) => {
      const dateKey = point.checkedAt.split("T")[0];
      const existing = dateMap.get(dateKey) || {
        date: dateKey,
        displayDate: formatDate(point.checkedAt),
        userPosition: null,
      };
      existing[`competitor_${point.competitorId}`] = point.position;
      dateMap.set(dateKey, existing);
    });

    // Sort by date
    return Array.from(dateMap.values()).sort(
      (a, b) => new Date(a.date).getTime() - new Date(b.date).getTime()
    );
  }, [data]);

  // Calculate position change for trend indicator
  const positionChange = useMemo(() => {
    if (chartData.length < 2) return null;
    const first = chartData[0].userPosition;
    const last = chartData[chartData.length - 1].userPosition;
    if (first === null || last === null) return null;
    return first - last; // Positive = improved (lower position number = better)
  }, [chartData]);

  // Handle legend toggle
  const handleToggleSeries = useCallback((key: string) => {
    setVisibleSeries((prev) => ({
      ...prev,
      [key]: prev[key] !== false ? false : true,
    }));
  }, []);

  // Handle competitor visibility toggle
  const handleToggleCompetitor = useCallback((id: number, visible: boolean) => {
    setVisibleCompetitors((prev) => ({
      ...prev,
      [id]: visible,
    }));
    // Also update series visibility
    setVisibleSeries((prev) => ({
      ...prev,
      [`competitor_${id}`]: visible,
    }));
  }, []);

  // Get selected keyword name
  const selectedKeyword = keywords.find((k) => k.id === selectedKeywordId);

  // Build line definitions for competitors
  const competitorLines = useMemo(() => {
    return competitors
      .filter((c) => visibleCompetitors[c.id] !== false)
      .map((competitor, index) => {
        const dataKey = `competitor_${competitor.id}`;
        const isVisible = visibleSeries[dataKey] !== false;
        const color = COMPETITOR_COLORS[index % COMPETITOR_COLORS.length];

        return (
          <Line
            key={competitor.id}
            type="monotone"
            dataKey={dataKey}
            stroke={color}
            strokeWidth={2}
            dot={false}
            connectNulls={false}
            activeDot={{ r: 4 }}
            hide={!isVisible}
            name={competitor.displayName || competitor.domain}
          />
        );
      });
  }, [competitors, visibleCompetitors, visibleSeries]);

  return (
    <Card className={className}>
      <CardHeader className="flex flex-col gap-4">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <CardTitle className="text-lg flex items-center gap-2">
              <LineChartIcon className="h-5 w-5" />
              Historical Comparison
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Track ranking positions over time compared to competitors.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <KeywordSelector
              keywords={keywords}
              value={selectedKeywordId}
              onChange={setSelectedKeywordId}
            />
            <DateRangeSelector value={dateRange} onChange={setDateRange} />
          </div>
        </div>
        {competitors.length > 0 && (
          <CompetitorFilter
            competitors={competitors}
            visibleCompetitors={visibleCompetitors}
            onChange={handleToggleCompetitor}
          />
        )}
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center justify-center py-16">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
          </div>
        ) : error ? (
          <div className="flex flex-col items-center justify-center py-16 text-muted-foreground">
            <p>Failed to load chart data.</p>
            <Button
              variant="outline"
              size="sm"
              className="mt-2"
              onClick={() => setSelectedKeywordId(selectedKeywordId)}
            >
              Retry
            </Button>
          </div>
        ) : chartData.length === 0 ? (
          <div className="flex items-center justify-center py-16 text-muted-foreground">
            <p>
              {selectedKeyword
                ? `No historical data available for "${selectedKeyword.keyword}".`
                : "Select a keyword to view historical comparison."}
            </p>
          </div>
        ) : (
          <>
            {selectedKeyword && positionChange !== null && (
              <div className="flex items-center gap-2 mb-4">
                <span className="text-sm text-muted-foreground">Your position trend:</span>
                {positionChange > 0 ? (
                  <span className="flex items-center gap-1 text-emerald-500 text-sm font-medium">
                    <TrendingUp className="h-4 w-4" />
                    Improved by {positionChange} positions
                  </span>
                ) : positionChange < 0 ? (
                  <span className="flex items-center gap-1 text-rose-500 text-sm font-medium">
                    <TrendingDown className="h-4 w-4" />
                    Dropped by {Math.abs(positionChange)} positions
                  </span>
                ) : (
                  <span className="text-sm text-muted-foreground">No change</span>
                )}
              </div>
            )}
            <div className="h-80">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart
                  data={chartData}
                  margin={{ top: 10, right: 24, left: 8, bottom: 24 }}
                >
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis
                    dataKey="date"
                    tick={{ fontSize: 12 }}
                    className="text-muted-foreground"
                    tickFormatter={formatDate}
                    angle={-35}
                    textAnchor="end"
                    height={50}
                  />
                  <YAxis
                    tick={{ fontSize: 12 }}
                    className="text-muted-foreground"
                    domain={[1, "auto"]}
                    reversed
                    tickFormatter={(value) => `#${value}`}
                    label={{
                      value: "Position",
                      angle: -90,
                      position: "insideLeft",
                      style: { textAnchor: "middle", fontSize: 12 },
                    }}
                  />
                  <Tooltip
                    content={(props) => (
                      <CustomTooltip
                        {...props}
                        competitorNames={competitorNames}
                        showUser={visibleSeries.userPosition !== false}
                      />
                    )}
                  />
                  <Legend
                    content={(props) => (
                      <CustomLegend
                        {...props}
                        competitorNames={competitorNames}
                        visibleSeries={visibleSeries}
                        onToggle={handleToggleSeries}
                      />
                    )}
                  />
                  <Line
                    type="monotone"
                    dataKey="userPosition"
                    stroke={USER_COLOR}
                    strokeWidth={2}
                    dot={false}
                    connectNulls={false}
                    activeDot={{ r: 4 }}
                    hide={visibleSeries.userPosition === false}
                    name="You"
                  />
                  {competitorLines}
                </LineChart>
              </ResponsiveContainer>
            </div>
          </>
        )}
      </CardContent>
    </Card>
  );
};

export default HistoricalComparisonChart;
