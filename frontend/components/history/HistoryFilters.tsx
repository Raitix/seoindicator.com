"use client";

import * as React from "react";
import { Search, X, ChevronDown, Calendar } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";

import type {
  HistoryFiltersProps,
  AnalysisFilters,
  ToolOption,
} from "@/lib/types/analysis";
import { STATUS_OPTIONS } from "@/lib/types/analysis";

// ==================== History Filters Component ====================

export function HistoryFilters({
  filters,
  onFiltersChange,
  tools = [],
  onClear,
}: HistoryFiltersProps) {
  const [datePopoverOpen, setDatePopoverOpen] = React.useState(false);
  
  // Default tools if not provided
  const toolOptions: ToolOption[] = tools.length > 0 ? tools : [
    { value: 'all', label: 'All Tools' },
    { value: 'meta-tag-analyzer', label: 'Meta Tag Analyzer' },
    { value: 'keyword-density', label: 'Keyword Density Checker' },
    { value: 'mobile-friendly', label: 'Mobile-Friendly Checker' },
    { value: 'social-preview', label: 'Social Media Preview' },
  ];

  // Check if any filters are active
  const hasActiveFilters = filters.tool || filters.status || filters.from || filters.to;

  // Handle tool change
  const handleToolChange = (value: string) => {
    onFiltersChange({ ...filters, tool: value === 'all' ? undefined : value || undefined });
  };

  // Handle status change
  const handleStatusChange = (value: string) => {
    onFiltersChange({ ...filters, status: value === 'all' ? undefined : (value || undefined) as AnalysisFilters['status'] });
  };

  // Handle date from change
  const handleDateFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    onFiltersChange({ ...filters, from: e.target.value || undefined });
  };

  // Handle date to change
  const handleDateToChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    onFiltersChange({ ...filters, to: e.target.value || undefined });
  };

  // Handle clear filters
  const handleClearFilters = () => {
    onFiltersChange({});
    onClear?.();
  };

  return (
    <div className="flex flex-wrap items-center gap-3" data-testid="history-filters">
      {/* Tool Filter */}
      <div className="w-48">
        <Select
          value={filters.tool || 'all'}
          onValueChange={handleToolChange}
        >
          <SelectTrigger
            className="bg-slate-900 border-slate-700 text-slate-200"
            data-testid="tool-filter"
          >
            <SelectValue placeholder="All Tools" />
          </SelectTrigger>
          <SelectContent className="bg-slate-900 border-slate-700">
            {toolOptions.map((tool) => (
              <SelectItem
                key={tool.value}
                value={tool.value}
                className="text-slate-200 focus:bg-slate-800"
              >
                {tool.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Status Filter */}
      <div className="w-40">
        <Select
          value={filters.status || 'all'}
          onValueChange={handleStatusChange}
        >
          <SelectTrigger
            className="bg-slate-900 border-slate-700 text-slate-200"
            data-testid="status-filter"
          >
            <SelectValue placeholder="All Statuses" />
          </SelectTrigger>
          <SelectContent className="bg-slate-900 border-slate-700">
            {STATUS_OPTIONS.map((status) => (
              <SelectItem
                key={status.value}
                value={status.value}
                className="text-slate-200 focus:bg-slate-800"
              >
                {status.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Date Range Filter */}
      <Popover open={datePopoverOpen} onOpenChange={setDatePopoverOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              "bg-slate-900 border-slate-700 text-slate-200 min-w-[180px]",
              (filters.from || filters.to) && "border-blue-500"
            )}
            data-testid="date-range-filter"
          >
            <Calendar className="h-4 w-4 mr-2" />
            {filters.from || filters.to ? (
              <span>
                {filters.from || '...'} - {filters.to || '...'}
              </span>
            ) : (
              "Date Range"
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent
          className="w-80 bg-slate-900 border-slate-700"
          align="start"
        >
          <div className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium text-slate-200">From</label>
              <Input
                type="date"
                value={filters.from || ''}
                onChange={handleDateFromChange}
                className="bg-slate-800 border-slate-700 text-slate-200"
                data-testid="date-from-input"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium text-slate-200">To</label>
              <Input
                type="date"
                value={filters.to || ''}
                onChange={handleDateToChange}
                className="bg-slate-800 border-slate-700 text-slate-200"
                data-testid="date-to-input"
              />
            </div>
          </div>
        </PopoverContent>
      </Popover>

      {/* Clear Filters Button */}
      {hasActiveFilters && (
        <Button
          variant="ghost"
          size="sm"
          onClick={handleClearFilters}
          className="text-slate-400 hover:text-slate-200"
          data-testid="clear-filters-button"
        >
          <X className="h-4 w-4 mr-1" />
          Clear Filters
        </Button>
      )}
    </div>
  );
}

export default HistoryFilters;
