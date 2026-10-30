/**
 * EmptyState Component - TASK-28-11
 *
 * Reusable empty state component with helpful messaging and CTAs.
 * Used when there's no data to display.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";
import { Inbox, Plus, Search, FileText, Users, BarChart3 } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";

/**
 * EmptyState Props
 */
export interface EmptyStateProps {
  /** Empty state type for default styling */
  type?: "keywords" | "competitors" | "reports" | "analyses" | "projects" | "custom";
  /** Custom title */
  title?: string;
  /** Custom description */
  description?: string;
  /** Custom icon component */
  icon?: React.ReactNode;
  /** CTA button text */
  ctaText?: string;
  /** CTA button click handler */
  onCtaClick?: () => void;
  /** Custom CTA component */
  cta?: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
  /** Show illustration */
  showIllustration?: boolean;
}

/**
 * Default configurations by type
 */
const typeConfigs: Record<string, { title: string; description: string; icon: React.ReactNode }> = {
  keywords: {
    title: "No keywords found",
    description: "Add your first keyword to start tracking rankings",
    icon: <Search className="h-12 w-12" />,
  },
  competitors: {
    title: "No competitors added",
    description: "Add competitors to compare your SEO performance",
    icon: <Users className="h-12 w-12" />,
  },
  reports: {
    title: "No reports generated",
    description: "Generate your first SEO report to get insights",
    icon: <FileText className="h-12 w-12" />,
  },
  analyses: {
    title: "No analyses yet",
    description: "Run your first SEO analysis to get started",
    icon: <BarChart3 className="h-12 w-12" />,
  },
  projects: {
    title: "No projects found",
    description: "Create your first project to organize your SEO work",
    icon: <Inbox className="h-12 w-12" />,
  },
  custom: {
    title: "No data available",
    description: "Get started by adding your first item",
    icon: <Inbox className="h-12 w-12" />,
  },
};

/**
 * EmptyState Component
 *
 * Displays a helpful message when there's no data to show,
 * with an optional call-to-action button.
 *
 * @example
 * ```tsx
 * // Default type
 * <EmptyState type="keywords" onCtaClick={handleAddKeyword} />
 *
 * // Custom content
 * <EmptyState
 *   title="No results found"
 *   description="Try adjusting your search criteria"
 *   icon={<Search className="h-12 w-12" />}
 * />
 * ```
 */
export function EmptyState({
  type = "custom",
  title,
  description,
  icon,
  ctaText,
  onCtaClick,
  cta,
  className,
  showIllustration = true,
}: EmptyStateProps) {
  const config = typeConfigs[type] || typeConfigs.custom;

  const displayTitle = title || config.title;
  const displayDescription = description || config.description;
  const displayIcon = icon || config.icon;
  const displayCtaText = ctaText || (type === "keywords" ? "Add Keyword" : "Get Started");

  return (
    <div
      data-testid="empty-state"
      role="status"
      aria-live="polite"
      className={cn(
        "flex flex-col items-center justify-center p-8 text-center",
        className
      )}
    >
      {/* Icon */}
      {showIllustration && (
        <div className="text-slate-600 mb-4" aria-hidden="true">
          {displayIcon}
        </div>
      )}

      {/* Title */}
      <h3 className="text-lg font-semibold text-white mb-2">
        {displayTitle}
      </h3>

      {/* Description */}
      <p className="text-sm text-slate-400 mb-6 max-w-sm">
        {displayDescription}
      </p>

      {/* CTA */}
      {cta ? (
        cta
      ) : onCtaClick ? (
        <Button
          data-testid="cta-button"
          onClick={onCtaClick}
          className="bg-brand-primary hover:bg-brand-primary/90 text-white"
        >
          <Plus className="h-4 w-4 mr-2" aria-hidden="true" />
          {displayCtaText}
        </Button>
      ) : null}
    </div>
  );
}

/**
 * EmptyStateSkeleton for loading state
 */
export function EmptyStateSkeleton({ className }: { className?: string }) {
  return (
    <div
      data-testid="empty-state-skeleton"
      className={cn(
        "flex flex-col items-center justify-center p-8 animate-pulse",
        className
      )}
    >
      <div className="h-12 w-12 bg-slate-700 rounded-full mb-4" />
      <div className="h-6 w-40 bg-slate-700 rounded mb-2" />
      <div className="h-4 w-56 bg-slate-700 rounded mb-6" />
      <div className="h-10 w-32 bg-slate-700 rounded" />
    </div>
  );
}
