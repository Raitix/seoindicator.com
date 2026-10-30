'use client';

import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRange } from '@/lib/types/gsc';
import { Calendar } from 'lucide-react';

interface GSCDateFilterProps {
  dateRange: DateRange;
  onDateRangeChange: (range: DateRange) => void;
  isLoading?: boolean;
}

// Preset date ranges
const presets = [
  { label: 'Last 7 days', days: 7 },
  { label: 'Last 14 days', days: 14 },
  { label: 'Last 30 days', days: 30 },
  { label: 'Last 90 days', days: 90 },
];

const GSCDateFilter: React.FC<GSCDateFilterProps> = ({
  dateRange,
  onDateRangeChange,
  isLoading = false,
}) => {
  const handlePresetClick = (days: number) => {
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - days);

    onDateRangeChange({
      from: from.toISOString().split('T')[0],
      to: to.toISOString().split('T')[0],
    });
  };

  const handleCustomDateChange = (field: 'from' | 'to', value: string) => {
    onDateRangeChange({
      ...dateRange,
      [field]: value,
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <Calendar className="h-5 w-5" />
          Date Range
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {/* Preset buttons */}
          <div className="flex flex-wrap gap-2">
            {presets.map((preset) => (
              <button
                key={preset.days}
                onClick={() => handlePresetClick(preset.days)}
                disabled={isLoading}
                className="px-3 py-1.5 text-sm rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {preset.label}
              </button>
            ))}
          </div>

          {/* Custom date inputs */}
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <label htmlFor="from-date" className="text-sm font-medium">
                From:
              </label>
              <input
                id="from-date"
                type="date"
                value={dateRange.from}
                onChange={(e) => handleCustomDateChange('from', e.target.value)}
                disabled={isLoading}
                className="px-3 py-1.5 text-sm rounded-md border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </div>
            <div className="flex items-center gap-2">
              <label htmlFor="to-date" className="text-sm font-medium">
                To:
              </label>
              <input
                id="to-date"
                type="date"
                value={dateRange.to}
                onChange={(e) => handleCustomDateChange('to', e.target.value)}
                disabled={isLoading}
                className="px-3 py-1.5 text-sm rounded-md border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default GSCDateFilter;
