/**
 * ComparisonCard Component
 * Task: TASK-32-4 - Implement Comparison Index Page
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a card linking to a comparison page with competitor info.
 */

import Link from 'next/link';
import type { ComparisonCardProps } from '@/lib/compare/types';

export function ComparisonCard({
  slug,
  title,
  description,
  competitorA,
  competitorB,
  className = '',
}: ComparisonCardProps & { className?: string }) {
  return (
    <Link
      href={`/compare/${slug}`}
      className={`group block p-6 bg-slate-900/50 border border-slate-800 rounded-xl hover:border-emerald-500/50 transition-all ${className}`}
      data-testid="comparison-card"
    >
      {/* Competitor logos/names */}
      <div className="flex items-center gap-4 mb-4">
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

        <span className="text-slate-400" aria-hidden="true">
          vs
        </span>

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
    </Link>
  );
}
