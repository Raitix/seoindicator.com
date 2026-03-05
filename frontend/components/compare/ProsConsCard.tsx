/**
 * ProsConsCard Component
 * Task: TASK-32-7 - Pros and Cons Cards
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays pros and cons for a single tool.
 */

import React from 'react';
import type { ProsConsCardProps } from '@/lib/compare/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function ProsConsCard({
  tool,
  pros,
  cons,
  overallRating,
}: ProsConsCardProps) {
  return (
    <Card
      className="bg-slate-900/50 border-slate-800"
      data-testid={`pros-cons-card-${tool.toLowerCase()}`}
    >
      <CardHeader>
        <CardTitle className="text-white flex items-center justify-between">
          {tool}
          {overallRating && (
            <span className="text-lg font-bold text-emerald-400">
              {overallRating.toFixed(1)} / 5
            </span>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Pros */}
        <div>
          <h4 className="text-sm font-semibold text-emerald-400 mb-2 flex items-center gap-2">
            <span aria-hidden="true">👍</span>
            Pros
          </h4>
          <ul
            className="space-y-1 text-sm text-slate-300"
            data-testid="pros-list"
          >
            {pros.map((pro, index) => (
              <li key={index} className="flex items-start gap-2">
                <span className="text-emerald-400 mt-0.5">✓</span>
                {pro}
              </li>
            ))}
          </ul>
        </div>

        {/* Cons */}
        <div>
          <h4 className="text-sm font-semibold text-red-400 mb-2 flex items-center gap-2">
            <span aria-hidden="true">👎</span>
            Cons
          </h4>
          <ul
            className="space-y-1 text-sm text-slate-300"
            data-testid="cons-list"
          >
            {cons.map((con, index) => (
              <li key={index} className="flex items-start gap-2">
                <span className="text-red-400 mt-0.5">✗</span>
                {con}
              </li>
            ))}
          </ul>
        </div>
      </CardContent>
    </Card>
  );
}

export default ProsConsCard;
