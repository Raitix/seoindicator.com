/**
 * ProsConsCard Component
 * Task: TASK-32-7 - Pros and Cons Cards
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays pros and cons for a single tool with balanced, honest assessment.
 */

import React from 'react';
import { Check, X, Star } from 'lucide-react';
import type { ProsConsCardProps } from '@/lib/compare/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * Renders a star rating display (1-5 stars).
 */
function StarRating({ rating }: { rating: number }) {
  const clampedRating = Math.max(0, Math.min(5, rating));
  const fullStars = Math.floor(clampedRating);

  return (
    <div
      className="flex items-center gap-0.5"
      role="img"
      aria-label={`Rating: ${clampedRating.toFixed(1)} out of 5`}
    >
      {Array.from({ length: 5 }, (_, i) => (
        <Star
          key={i}
          data-testid="star-icon"
          data-filled={i < fullStars}
          className={cn(
            'w-4 h-4',
            i < fullStars
              ? 'fill-yellow-400 text-yellow-400'
              : 'fill-none text-slate-600'
          )}
          aria-hidden="true"
        />
      ))}
      <span className="ml-2 text-sm text-slate-400">
        {clampedRating.toFixed(1)} out of 5
      </span>
    </div>
  );
}

export function ProsConsCard({
  tool,
  pros = [],
  cons = [],
  overallRating,
  className,
}: ProsConsCardProps) {
  const clampedRating =
    overallRating !== undefined && overallRating !== null
      ? Math.max(0, Math.min(5, overallRating))
      : undefined;

  return (
    <Card
      className={cn(
        'bg-slate-900/50 border-slate-800 pros-cons-card',
        className
      )}
      role="region"
      aria-label={`Pros and cons for ${tool}`}
      data-testid="card"
    >
      <CardHeader data-testid="card-header">
        <CardTitle className="text-white flex items-center justify-between">
          <h3 className="text-lg font-semibold">{tool}</h3>
          {clampedRating !== undefined && (
            <StarRating rating={clampedRating} />
          )}
        </CardTitle>
      </CardHeader>
      <CardContent
        className="grid grid-cols-1 md:grid-cols-2 gap-4"
        data-testid="card-content"
      >
        {/* Pros Section */}
        <div aria-label="pros-section" className="space-y-2">
          <h4 className="text-sm font-semibold text-emerald-400 mb-2 flex items-center gap-2">
            <span aria-hidden="true">👍</span>
            Pros
          </h4>
          <ul
            className="space-y-1.5 text-sm text-slate-300"
            aria-label="pros list"
            data-testid="pros-list"
          >
            {pros.map((pro, index) => (
              <li key={index} className="flex items-start gap-2">
                <Check
                  data-testid="check-icon"
                  className="w-4 h-4 text-emerald-400 mt-0.5 shrink-0"
                  aria-hidden="true"
                />
                <span>{pro}</span>
              </li>
            ))}
          </ul>
        </div>

        {/* Cons Section */}
        <div aria-label="cons-section" className="space-y-2">
          <h4 className="text-sm font-semibold text-slate-400 mb-2 flex items-center gap-2">
            <span aria-hidden="true">👎</span>
            Cons
          </h4>
          <ul
            className="space-y-1.5 text-sm text-slate-300"
            aria-label="cons list"
            data-testid="cons-list"
          >
            {cons.map((con, index) => (
              <li key={index} className="flex items-start gap-2">
                <X
                  data-testid="x-icon"
                  className="w-4 h-4 text-slate-500 mt-0.5 shrink-0"
                  aria-hidden="true"
                />
                <span>{con}</span>
              </li>
            ))}
          </ul>
        </div>
      </CardContent>
    </Card>
  );
}

export default ProsConsCard;
