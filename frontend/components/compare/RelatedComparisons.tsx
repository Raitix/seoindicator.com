"use client";

import Link from "next/link";
import { getCompetitorById } from "@/lib/compare/registry";

/**
 * RelatedComparisons Component
 * Task: TASK-32-16 - Internal Linking Implementation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays links to related comparison pages at the bottom of comparison pages.
 * Supports filtering out the current page and limiting the number of items.
 */

export interface RelatedComparisonsProps {
  /** Array of comparison slugs to display */
  slugs: string[];
  /** Optional current slug to exclude (prevents self-referencing) */
  currentSlug?: string;
  /** Optional max items to display */
  maxItems?: number;
  /** Optional className for styling */
  className?: string;
}

/**
 * Format a comparison slug into a human-readable title.
 * e.g., "seoindicator-vs-ahrefs" -> "SEOIndicator vs Ahrefs"
 */
function formatComparisonTitle(slug: string): string {
  const parts = slug.split("-vs-");
  if (parts.length !== 2) {
    // Fallback for malformed slugs
    return slug;
  }

  const toolA = getCompetitorById(parts[0]);
  const toolB = getCompetitorById(parts[1]);

  // Use registry names if available, otherwise capitalize
  const nameA = toolA?.name || capitalizeFirst(parts[0]);
  const nameB = toolB?.name || capitalizeFirst(parts[1]);

  return `${nameA} vs ${nameB}`;
}

/**
 * Capitalize the first letter of a string.
 */
function capitalizeFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export function RelatedComparisons({
  slugs,
  currentSlug,
  maxItems,
  className,
}: RelatedComparisonsProps) {
  // Filter out current slug if provided
  const filteredSlugs = currentSlug
    ? slugs.filter((slug) => slug !== currentSlug)
    : slugs;

  // Apply max items limit
  const displaySlugs = maxItems ? filteredSlugs.slice(0, maxItems) : filteredSlugs;

  // Handle empty state
  if (displaySlugs.length === 0) {
    return (
      <section
        className={className}
        data-testid="related-comparisons"
        aria-label="Related comparisons"
      >
        <h2 className="text-2xl font-bold text-white mb-4">Related Comparisons</h2>
        <p
          className="text-slate-400"
          data-testid="empty-message"
        >
          No related comparisons available.
        </p>
      </section>
    );
  }

  return (
    <section
      className={className}
      data-testid="related-comparisons"
      aria-label="Related comparisons"
    >
      <h2 className="text-2xl font-bold text-white mb-6">Related Comparisons</h2>
      <div
        className="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
        data-testid="comparisons-grid"
      >
        {displaySlugs.map((slug) => {
          const title = formatComparisonTitle(slug);
          const parts = slug.split("-vs-");
          const toolA = parts[0] || "";
          const toolB = parts[1] || "";

          return (
            <Link
              key={slug}
              href={`/compare/${slug}`}
              className="group block p-4 bg-surface-2 border border-slate-700 rounded-lg hover:border-brand-primary/50 transition-colors"
              data-testid={`comparison-link-${slug}`}
              aria-label={`Compare ${getCompetitorById(toolA)?.name || capitalizeFirst(toolA)} with ${getCompetitorById(toolB)?.name || capitalizeFirst(toolB)}`}
            >
              <span className="text-white group-hover:text-brand-primary transition-colors comparison-title">
                {title}
              </span>
              <span className="block text-sm text-slate-400 mt-1">
                View detailed comparison →
              </span>
            </Link>
          );
        })}
      </div>
    </section>
  );
}

export default RelatedComparisons;
