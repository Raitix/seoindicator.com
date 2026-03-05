/**
 * ComparisonCard Component
 * Task: TASK-32-4 - Implement Comparison Index Page
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a card linking to a comparison page with competitor logos/names,
 * title, and description.
 */

import React from 'react';
import type { ComparisonCardProps } from '@/lib/compare/types';

export interface ComparisonCardComponentProps extends ComparisonCardProps {
  /** Optional additional CSS classes */
  className?: string;
}

/**
 * ComparisonCard Component
 *
 * Renders a card that links to a comparison page. Displays:
 * - Two competitor logos (or initials if no logo)
 * - Comparison title
 * - Description text
 *
 * @param props - Component props
 * @returns React component
 */
export function ComparisonCard({
  slug,
  title,
  description,
  competitorA,
  competitorB,
  className = '',
}: ComparisonCardComponentProps) {
  return (
    <a
      href={`/compare/${slug}`}
      className={`group block p-6 bg-slate-900/50 border border-slate-800 rounded-xl hover:border-emerald-500/50 transition-all ${className}`}
      data-testid="comparison-card"
    >
      {/* Competitor logos/names */}
      <div className="flex items-center gap-4 mb-4">
        {/* Competitor A */}
        <div className="flex items-center gap-2">
          {competitorA.logo ? (
            <img
              src={competitorA.logo}
              alt={`${competitorA.name} logo`}
              className="w-8 h-8 object-contain"
              data-testid="logo-a"
            />
          ) : (
            <div
              className="w-8 h-8 bg-slate-700 rounded flex items-center justify-center"
              data-testid="logo-placeholder-a"
            >
              <span className="text-xs font-bold text-slate-300">
                {competitorA.name.charAt(0)}
              </span>
            </div>
          )}
          <span className="font-medium text-white" data-testid="name-a">
            {competitorA.name}
          </span>
        </div>

        {/* VS separator */}
        <span className="text-slate-400" aria-hidden="true">
          vs
        </span>

        {/* Competitor B */}
        <div className="flex items-center gap-2">
          {competitorB.logo ? (
            <img
              src={competitorB.logo}
              alt={`${competitorB.name} logo`}
              className="w-8 h-8 object-contain"
              data-testid="logo-b"
            />
          ) : (
            <div
              className="w-8 h-8 bg-slate-700 rounded flex items-center justify-center"
              data-testid="logo-placeholder-b"
            >
              <span className="text-xs font-bold text-slate-300">
                {competitorB.name.charAt(0)}
              </span>
            </div>
          )}
          <span className="font-medium text-white" data-testid="name-b">
            {competitorB.name}
          </span>
        </div>
      </div>

      {/* Title */}
      <h3
        className="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors"
        data-testid="card-title"
      >
        {title}
      </h3>

      {/* Description */}
      <p className="text-sm text-slate-400 mt-2" data-testid="card-description">
        {description}
      </p>
    </a>
  );
}

export default ComparisonCard;
