/**
 * DateRangeSelector Component
 * Task: TASK-27-16 - Usage Charts and Tables
 *
 * Date range selector component for filtering usage data.
 * Supports preset ranges (7d, 14d, 30d, 90d) and custom date selection.
 */

"use client";

import React, { useState, useCallback, useMemo } from "react";
import { Calendar, ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";

// ============================================================================
// Types
// ============================================================================

export type PresetRange = "7d" | "14d" | "30d" | "90d" | "custom";

export interface DateRangeSelectorProps {
  /** Currently selected range */
  value: PresetRange;
  /** Custom date range (only used when value is 'custom') */
  customRange?: { start: Date; end: Date };
  /** Callback when range changes */
  onChange: (range: PresetRange, custom?: { start: Date; end: Date }) => void;
  /** Disable the selector */
  disabled?: boolean;
  /** Minimum selectable date */
  minDate?: Date;
  /** Maximum selectable date */
  maxDate?: Date;
  /** Additional CSS classes */
  className?: string;
  /** Accessible label */
  "aria-label"?: string;
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Get human-readable label for preset range
 */
export function getPresetLabel(range: PresetRange): string {
  const labels: Record<PresetRange, string> = {
    "7d": "Last 7 days",
    "14d": "Last 14 days",
    "30d": "Last 30 days",
    "90d": "Last 90 days",
    custom: "Custom",
  };
  return labels[range];
}

/**
 * Calculate date range from preset
 */
export function calculateDateRange(
  range: PresetRange
): { start: Date; end: Date } | null {
  if (range === "custom") return null;

  const end = new Date();
  const start = new Date();

  const daysMap: Record<Exclude<PresetRange, "custom">, number> = {
    "7d": 7,
    "14d": 14,
    "30d": 30,
    "90d": 90,
  };

  start.setDate(start.getDate() - daysMap[range as Exclude<PresetRange, "custom">]);

  return { start, end };
}

/**
 * Format date for display
 */
export function formatDateDisplay(date: Date): string {
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

/**
 * Validate date range
 */
export function isValidDateRange(
  start: Date,
  end: Date,
  minDate?: Date,
  maxDate?: Date
): boolean {
  if (start > end) return false;
  if (minDate && start < minDate) return false;
  if (maxDate && end > maxDate) return false;
  return true;
}

/**
 * Format date for input
 */
function formatDateForInput(date: Date): string {
  return date.toISOString().split("T")[0];
}

// ============================================================================
// Component
// ============================================================================

export function DateRangeSelector({
  value,
  customRange,
  onChange,
  disabled = false,
  minDate,
  maxDate,
  className,
  "aria-label": ariaLabel = "Select date range",
}: DateRangeSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [tempStart, setTempStart] = useState<string>(
    customRange?.start ? formatDateForInput(customRange.start) : ""
  );
  const [tempEnd, setTempEnd] = useState<string>(
    customRange?.end ? formatDateForInput(customRange.end) : ""
  );

  // Get display label for current selection
  const displayLabel = useMemo(() => {
    if (value === "custom" && customRange) {
      return `${formatDateDisplay(customRange.start)} - ${formatDateDisplay(customRange.end)}`;
    }
    return getPresetLabel(value);
  }, [value, customRange]);

  // Handle preset selection
  const handlePresetSelect = useCallback(
    (preset: PresetRange) => {
      if (preset === "custom") {
        // Initialize custom range with last 30 days
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 30);
        setTempStart(formatDateForInput(start));
        setTempEnd(formatDateForInput(end));
        onChange("custom", { start, end });
      } else {
        const range = calculateDateRange(preset);
        if (range) {
          onChange(preset, range);
        }
        setIsOpen(false);
      }
    },
    [onChange]
  );

  // Handle custom date apply
  const handleApplyCustom = useCallback(() => {
    const start = new Date(tempStart);
    const end = new Date(tempEnd);

    if (isValidDateRange(start, end, minDate, maxDate)) {
      onChange("custom", { start, end });
      setIsOpen(false);
    }
  }, [tempStart, tempEnd, minDate, maxDate, onChange]);

  // Preset options
  const presets: PresetRange[] = ["7d", "14d", "30d", "90d"];

  return (
    <div className={cn("relative", className)}>
      {/* Trigger Button */}
      <button
        type="button"
        onClick={() => !disabled && setIsOpen(!isOpen)}
        disabled={disabled}
        aria-label={ariaLabel}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        className={cn(
          "flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium transition-colors",
          disabled
            ? "cursor-not-allowed opacity-50"
            : "hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        )}
      >
        <Calendar className="h-4 w-4 text-slate-400" />
        <span className="text-white">{displayLabel}</span>
        <ChevronDown
          className={cn(
            "h-4 w-4 text-slate-400 transition-transform",
            isOpen && "rotate-180"
          )}
        />
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div
          role="listbox"
          aria-label="Date range options"
          className="absolute left-0 top-full z-50 mt-2 w-72 rounded-lg border border-slate-700 bg-slate-800 p-2 shadow-lg"
        >
          {/* Preset Options */}
          <div className="mb-2 space-y-1">
            {presets.map((preset) => (
              <button
                key={preset}
                type="button"
                role="option"
                aria-selected={value === preset}
                onClick={() => handlePresetSelect(preset)}
                className={cn(
                  "w-full rounded-md px-3 py-2 text-left text-sm transition-colors",
                  value === preset
                    ? "bg-blue-600 text-white"
                    : "text-slate-300 hover:bg-slate-700"
                )}
              >
                {getPresetLabel(preset)}
              </button>
            ))}
          </div>

          {/* Divider */}
          <div className="my-2 border-t border-slate-700" />

          {/* Custom Range */}
          <div className="space-y-2">
            <button
              type="button"
              role="option"
              aria-selected={value === "custom"}
              onClick={() => handlePresetSelect("custom")}
              className={cn(
                "w-full rounded-md px-3 py-2 text-left text-sm transition-colors",
                value === "custom"
                  ? "bg-blue-600 text-white"
                  : "text-slate-300 hover:bg-slate-700"
              )}
            >
              Custom Range
            </button>

            {/* Custom Date Inputs */}
            {value === "custom" && (
              <div className="space-y-2 p-2">
                <div>
                  <label
                    htmlFor="start-date"
                    className="block text-xs text-slate-400"
                  >
                    Start Date
                  </label>
                  <input
                    id="start-date"
                    type="date"
                    value={tempStart}
                    onChange={(e) => setTempStart(e.target.value)}
                    min={minDate ? formatDateForInput(minDate) : undefined}
                    max={maxDate ? formatDateForInput(maxDate) : undefined}
                    className="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label
                    htmlFor="end-date"
                    className="block text-xs text-slate-400"
                  >
                    End Date
                  </label>
                  <input
                    id="end-date"
                    type="date"
                    value={tempEnd}
                    onChange={(e) => setTempEnd(e.target.value)}
                    min={minDate ? formatDateForInput(minDate) : undefined}
                    max={maxDate ? formatDateForInput(maxDate) : undefined}
                    className="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <button
                  type="button"
                  onClick={handleApplyCustom}
                  className="w-full rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                  Apply
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default DateRangeSelector;
