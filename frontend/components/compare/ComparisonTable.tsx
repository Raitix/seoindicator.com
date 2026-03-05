/**
 * ComparisonTable Component
 * Task: TASK-32-5 - Comparison Table Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a feature comparison table between SEOIndicator and a competitor.
 */

import React from 'react';
import type { ComparisonTableProps, FeatureCategory } from '@/lib/compare/types';

/**
 * Renders a single feature value with appropriate styling.
 */
function FeatureValue({
  value,
  highlightWinner,
}: {
  value: boolean | string;
  highlightWinner?: boolean;
}) {
  if (value === true) {
    return (
      <span className="text-emerald-400 font-medium" aria-label="Yes">
        ✓
      </span>
    );
  }

  if (value === false) {
    return (
      <span className="text-red-400" aria-label="No">
        ✗
      </span>
    );
  }

  // String value (e.g., "Partial", "Yes", "N/A")
  const isPositive = value.toLowerCase() === 'yes' || value.toLowerCase() === 'full';
  const isPartial = value.toLowerCase() === 'partial';

  return (
    <span
      className={`${
        isPositive
          ? 'text-emerald-400'
          : isPartial
          ? 'text-amber-400'
          : 'text-slate-400'
      }`}
    >
      {value}
    </span>
  );
}

/**
 * Renders a feature category with its features.
 */
function FeatureCategoryRow({
  category,
  highlightWinner,
  showTooltips,
}: {
  category: FeatureCategory;
  highlightWinner?: boolean;
  showTooltips?: boolean;
}) {
  return (
    <div className="mb-6">
      <h3 className="text-lg font-semibold text-white mb-3 border-b border-slate-700 pb-2">
        {category.name}
      </h3>
      <div className="space-y-2">
        {category.features.map((feature) => (
          <div
            key={feature.name}
            className="grid grid-cols-3 gap-4 py-2 border-b border-slate-800/50"
          >
            <div className="flex items-center gap-2">
              <span className="text-slate-300">{feature.name}</span>
              {feature.tooltip && showTooltips && (
                <span
                  className="text-slate-500 text-xs cursor-help"
                  title={feature.tooltip}
                >
                  ⓘ
                </span>
              )}
            </div>
            <div className="text-center">
              <FeatureValue
                value={feature.seoindicator}
                highlightWinner={highlightWinner}
              />
            </div>
            <div className="text-center">
              <FeatureValue
                value={feature.competitor}
                highlightWinner={highlightWinner}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export function ComparisonTable({
  features,
  highlightWinner = true,
  showTooltips = true,
}: ComparisonTableProps) {
  if (!features || features.length === 0) {
    return (
      <div
        className="text-slate-400 text-center py-8"
        data-testid="empty-features"
      >
        No feature data available.
      </div>
    );
  }

  return (
    <div
      className="bg-slate-900/50 rounded-xl border border-slate-800 p-6"
      data-testid="comparison-table"
    >
      {/* Header */}
      <div className="grid grid-cols-3 gap-4 mb-4 text-sm font-semibold text-slate-400 border-b border-slate-700 pb-3">
        <div>Feature</div>
        <div className="text-center text-emerald-400">SEOIndicator</div>
        <div className="text-center text-blue-400">Competitor</div>
      </div>

      {/* Categories */}
      {features.map((category) => (
        <FeatureCategoryRow
          key={category.name}
          category={category}
          highlightWinner={highlightWinner}
          showTooltips={showTooltips}
        />
      ))}
    </div>
  );
}

export default ComparisonTable;
