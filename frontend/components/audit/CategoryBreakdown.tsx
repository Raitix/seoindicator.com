"use client";

import * as React from "react";
import {
  ChevronDown,
  ChevronRight,
  AlertCircle,
  AlertTriangle,
  CheckCircle,
  Wrench,
  FileText,
  Edit,
  Smartphone,
  LucideIcon,
} from "lucide-react";

import { cn } from "@/lib/utils";
import { Progress } from "@/components/ui/progress";

/**
 * CategoryScore interface representing a single SEO category's score data
 */
export interface CategoryScore {
  name: string;
  score: number;
  issueCount: number;
  criticalCount: number;
  warningCount: number;
}

/**
 * CategoryBreakdown component props
 */
export interface CategoryBreakdownProps {
  categories: CategoryScore[];
  expanded?: boolean;
  className?: string;
}

/**
 * Get the color class based on score range
 * 80-100: Green, 60-79: Blue, 40-59: Yellow, 20-39: Orange, 0-19: Red
 */
function getScoreColorClass(score: number): string {
  if (score >= 80) return "bg-green-500";
  if (score >= 60) return "bg-blue-500";
  if (score >= 40) return "bg-yellow-500";
  if (score >= 20) return "bg-orange-500";
  return "bg-red-500";
}

/**
 * Get the text color class based on score range
 */
function getScoreTextColorClass(score: number): string {
  if (score >= 80) return "text-green-500";
  if (score >= 60) return "text-blue-500";
  if (score >= 40) return "text-yellow-500";
  if (score >= 20) return "text-orange-500";
  return "text-red-500";
}

/**
 * Get category-specific icon
 */
function getCategoryIcon(name: string): LucideIcon {
  const normalizedName = name.toLowerCase();
  if (normalizedName.includes("technical")) return Wrench;
  if (normalizedName.includes("on-page")) return FileText;
  if (normalizedName.includes("content")) return Edit;
  if (normalizedName.includes("mobile") || normalizedName.includes("performance")) return Smartphone;
  return FileText;
}

/**
 * Single category item component
 */
interface CategoryItemProps {
  category: CategoryScore;
  defaultExpanded: boolean;
}

function CategoryItem({ category, defaultExpanded }: CategoryItemProps) {
  const [isOpen, setIsOpen] = React.useState(defaultExpanded);
  const Icon = getCategoryIcon(category.name);
  const colorClass = getScoreColorClass(category.score);
  const textColorClass = getScoreTextColorClass(category.score);

  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      setIsOpen(!isOpen);
    }
  };

  const hasIssues = category.issueCount > 0 || category.criticalCount > 0 || category.warningCount > 0;

  return (
    <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4" data-testid={`category-${category.name.toLowerCase().replace(/\s+/g, "-")}`}>
      {/* Header row with icon, name, and score */}
      <div className="flex items-center gap-3">
        <Icon className="h-5 w-5 text-slate-400" aria-hidden="true" />
        <div className="flex-1">
          <div className="flex items-center justify-between">
            <h3 className="font-medium text-white">{category.name}</h3>
            <span className={cn("text-lg font-semibold", textColorClass)} aria-label={`Score: ${category.score} out of 100`}>
              {category.score}/100
            </span>
          </div>
        </div>
      </div>

      {/* Progress bar */}
      <div className="mt-3">
        <Progress
          value={category.score}
          className="h-2"
          aria-label={`${category.name} score progress`}
          aria-valuenow={category.score}
          aria-valuemin={0}
          aria-valuemax={100}
          role="progressbar"
        />
        {/* Custom indicator with color */}
        <div
          className={cn("mt-1 h-2 rounded-full transition-all", colorClass)}
          style={{ width: `${Math.min(100, Math.max(0, category.score))}%` }}
          aria-hidden="true"
        />
      </div>

      {/* Expandable issue count */}
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={handleKeyDown}
        className="mt-3 flex w-full items-center gap-2 text-left text-sm text-slate-400 hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900 rounded"
        aria-expanded={isOpen}
        aria-controls={`category-content-${category.name.toLowerCase().replace(/\s+/g, "-")}`}
        data-testid={`expand-trigger-${category.name.toLowerCase().replace(/\s+/g, "-")}`}
      >
        {isOpen ? (
          <ChevronDown className="h-4 w-4" aria-hidden="true" />
        ) : (
          <ChevronRight className="h-4 w-4" aria-hidden="true" />
        )}
        <span>{category.issueCount} issue{category.issueCount !== 1 ? "s" : ""}</span>
      </button>

      {/* Expandable content */}
      <div
        id={`category-content-${category.name.toLowerCase().replace(/\s+/g, "-")}`}
        data-open={isOpen}
        data-testid={`category-content-${category.name.toLowerCase().replace(/\s+/g, "-")}`}
        className={cn(
          "overflow-hidden transition-all",
          isOpen ? "max-h-40 opacity-100" : "max-h-0 opacity-0"
        )}
      >
        <div className="mt-3 space-y-2 border-t border-slate-800 pt-3">
          {/* Critical issues */}
          {category.criticalCount > 0 && (
            <div className="flex items-center gap-2 text-sm" data-testid="critical-indicator">
              <AlertCircle className="h-4 w-4 text-red-500" aria-hidden="true" />
              <span className="text-red-400">{category.criticalCount} critical</span>
            </div>
          )}

          {/* Warning issues */}
          {category.warningCount > 0 && (
            <div className="flex items-center gap-2 text-sm" data-testid="warning-indicator">
              <AlertTriangle className="h-4 w-4 text-yellow-500" aria-hidden="true" />
              <span className="text-yellow-400">{category.warningCount} warning{category.warningCount !== 1 ? "s" : ""}</span>
            </div>
          )}

          {/* No issues indicator */}
          {!hasIssues && (
            <div className="flex items-center gap-2 text-sm" data-testid="no-issues-indicator">
              <CheckCircle className="h-4 w-4 text-green-500" aria-hidden="true" />
              <span className="text-green-400">No issues</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

/**
 * CategoryBreakdown component displays score progress bars for each SEO category
 * with expandable issue counts and severity indicators.
 */
export function CategoryBreakdown({ categories, expanded = false, className }: CategoryBreakdownProps) {
  return (
    <div
      className={cn("space-y-4", className)}
      data-testid="category-breakdown"
      role="region"
      aria-label="SEO Category Breakdown"
    >
      {categories.map((category) => (
        <CategoryItem
          key={category.name}
          category={category}
          defaultExpanded={expanded}
        />
      ))}

      {categories.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-center text-slate-400">
          No categories to display
        </div>
      )}
    </div>
  );
}

export default CategoryBreakdown;
