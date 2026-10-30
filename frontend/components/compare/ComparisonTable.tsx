/**
 * ComparisonTable Component
 * Task: TASK-32-5 - Comparison Table Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a feature comparison table between SEOIndicator and a competitor.
 * Supports responsive design, winner highlighting, and tooltips.
 */

import React, { memo } from 'react';
import { Check, X, HelpCircle } from 'lucide-react';
import type { ComparisonTableProps, FeatureCategory, Feature } from '@/lib/compare/types';

/**
 * Renders a feature value cell with check/X icon or text.
 */
const FeatureCell = memo(function FeatureCell({
  value,
  isWinner = false,
  columnType,
}: {
  value: boolean | string;
  isWinner?: boolean;
  columnType: 'seoindicator' | 'competitor';
}) {
  const className = `text-center ${isWinner ? 'winner-highlight bg-emerald-500/10 rounded px-2 py-1' : ''}`;
  const testId = columnType === 'seoindicator' ? 'seoindicator-value' : 'competitor-value';

  if (value === true) {
    return (
      <div role="cell" data-testid={testId} className={className}>
        <span className="inline-flex items-center justify-center text-emerald-400">
          <Check className="h-5 w-5" aria-label="Yes" data-testid="check-icon" />
        </span>
      </div>
    );
  }

  if (value === false) {
    return (
      <div role="cell" data-testid={testId} className={className}>
        <span className="inline-flex items-center justify-center text-red-400">
          <X className="h-5 w-5" aria-label="No" data-testid="x-icon" />
        </span>
      </div>
    );
  }

  // String value (e.g., "Partial", "Yes", "N/A", "50/day", "Unlimited")
  const isPositive = value.toLowerCase() === 'yes' || value.toLowerCase() === 'full';
  const isPartial = value.toLowerCase() === 'partial';

  return (
    <div role="cell" data-testid={testId} className={className}>
      <span
        className={`${
          isPositive
            ? 'text-emerald-400'
            : isPartial
            ? 'text-amber-400'
            : 'text-slate-300'
        }`}
      >
        {value}
      </span>
    </div>
  );
});

/**
 * Tooltip wrapper for feature names.
 */
const FeatureTooltip = memo(function FeatureTooltip({
  content,
  featureId,
  children,
}: {
  content: string;
  featureId: string;
  children: React.ReactNode;
}) {
  return (
    <span className="inline-flex items-center gap-1 cursor-help">
      <span title={content} data-testid={`tooltip-trigger-${featureId}`}>
        {children}
      </span>
      <HelpCircle className="h-4 w-4 text-slate-500" data-testid={`help-icon`} />
    </span>
  );
});

/**
 * Individual feature row with name and values for both tools.
 */
const FeatureRow = memo(function FeatureRow({
  feature,
  highlightWinner,
  showTooltips,
}: {
  feature: Feature;
  highlightWinner: boolean;
  showTooltips: boolean;
}) {
  const featureId = feature.name.toLowerCase().replace(/\s+/g, '-');
  
  // Determine winner highlighting (only for boolean values)
  const seoindicatorWins = highlightWinner && feature.seoindicator === true && feature.competitor === false;
  const competitorWins = highlightWinner && feature.competitor === true && feature.seoindicator === false;

  return (
    <div
      role="row"
      data-testid={`feature-row-${featureId}`}
      className="grid grid-cols-3 gap-4 py-3 border-b border-slate-800/50 hover:bg-slate-900/30 transition-colors"
    >
      {/* Feature Name */}
      <div role="cell" data-testid="feature-name" className="text-slate-300 flex items-center gap-2">
        {showTooltips && feature.tooltip ? (
          <FeatureTooltip content={feature.tooltip} featureId={featureId}>
            {feature.name}
          </FeatureTooltip>
        ) : (
          <span>{feature.name}</span>
        )}
      </div>

      {/* SEOIndicator Value */}
      <FeatureCell
        value={feature.seoindicator}
        isWinner={seoindicatorWins}
        columnType="seoindicator"
      />

      {/* Competitor Value */}
      <FeatureCell
        value={feature.competitor}
        isWinner={competitorWins}
        columnType="competitor"
      />
    </div>
  );
});

/**
 * Feature category row with collapsible features.
 */
const FeatureCategoryRow = memo(function FeatureCategoryRow({
  category,
  highlightWinner,
  showTooltips,
}: {
  category: FeatureCategory;
  highlightWinner: boolean;
  showTooltips: boolean;
}) {
  const categoryId = category.name.toLowerCase().replace(/\s+/g, '-');

  return (
    <div
      role="rowgroup"
      data-testid={`category-${categoryId}`}
      className="mb-4 last:mb-0"
    >
      {/* Category Header */}
      <div
        role="row"
        data-testid={`category-header-${categoryId}`}
        className="grid grid-cols-3 gap-4 py-2 bg-slate-800/50 rounded-t-lg"
      >
        <div role="cell" className="col-span-3 font-semibold text-white px-4 py-2">
          {category.name}
        </div>
      </div>

      {/* Feature Rows */}
      {category.features.map((feature, idx) => (
        <FeatureRow
          key={`${category.name}-${feature.name}-${idx}`}
          feature={feature}
          highlightWinner={highlightWinner}
          showTooltips={showTooltips}
        />
      ))}
    </div>
  );
});

/**
 * Main ComparisonTable component.
 * Displays a responsive feature comparison table between SEOIndicator and a competitor.
 */
export function ComparisonTable({
  features,
  highlightWinner = true,
  showTooltips = true,
}: ComparisonTableProps) {
  // Handle empty or undefined features
  if (!features || features.length === 0) {
    return (
      <div
        data-testid="comparison-table-empty"
        role="table"
        aria-label="Feature comparison table"
        className="text-slate-400 text-center py-8 bg-slate-900/50 rounded-xl border border-slate-800"
      >
        <p>No features to compare</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
      <div
        data-testid="comparison-table"
        role="table"
        aria-label="Feature comparison table"
        className="w-full bg-slate-900/50 rounded-xl border border-slate-800 p-4 sm:p-6 min-w-[600px]"
      >
        {/* Header Row */}
        <div role="rowgroup">
          <div
            role="row"
            data-testid="header-row"
            className="grid grid-cols-3 gap-4 pb-3 border-b border-slate-700 text-sm font-semibold"
          >
            <div role="columnheader" className="text-slate-400">
              Feature
            </div>
            <div role="columnheader" className="text-center text-emerald-400">
              SEOIndicator
            </div>
            <div role="columnheader" className="text-center text-blue-400">
              Competitor
            </div>
          </div>
        </div>

        {/* Feature Categories */}
        <div role="rowgroup">
          {features.map((category) => (
            <FeatureCategoryRow
              key={category.name}
              category={category}
              highlightWinner={highlightWinner}
              showTooltips={showTooltips}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

export default memo(ComparisonTable);
