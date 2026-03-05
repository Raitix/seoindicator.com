/**
 * UseCaseSection Component
 * Task: TASK-32-8 - Use Case Section
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays use cases for both tools side by side.
 */

import React from 'react';
import type { UseCaseSectionProps, UseCase } from '@/lib/compare/types';

/**
 * Maps audience type to display label.
 */
const audienceLabels: Record<string, string> = {
  solo: 'Solo Users',
  'small-team': 'Small Teams',
  enterprise: 'Enterprise',
  agency: 'Agencies',
};

/**
 * Maps budget type to display label and color.
 */
const budgetConfig: Record<string, { label: string; color: string }> = {
  free: { label: 'Free', color: 'text-green-400' },
  budget: { label: 'Budget', color: 'text-emerald-400' },
  'mid-range': { label: 'Mid-Range', color: 'text-blue-400' },
  premium: { label: 'Premium', color: 'text-purple-400' },
};

/**
 * Renders a single use case card.
 */
function UseCaseCard({
  useCase,
  brand,
}: {
  useCase: UseCase;
  brand: 'seoindicator' | 'competitor';
}) {
  const brandColor = brand === 'seoindicator' ? 'emerald' : 'blue';
  const audienceLabel = audienceLabels[useCase.audience] || useCase.audience;
  const budget = budgetConfig[useCase.budget] || {
    label: useCase.budget,
    color: 'text-slate-400',
  };

  return (
    <div
      className={`p-4 rounded-lg border border-slate-700 bg-slate-800/30 hover:border-${brandColor}-500/50 transition-colors`}
      data-testid={`use-case-${useCase.title.toLowerCase().replace(/\s+/g, '-')}`}
    >
      <h4 className="font-semibold text-white mb-2">{useCase.title}</h4>
      <p className="text-sm text-slate-300 mb-3">{useCase.description}</p>
      <div className="flex gap-2 text-xs">
        <span
          className={`px-2 py-1 rounded-full bg-${brandColor}-500/10 text-${brandColor}-400`}
        >
          {audienceLabel}
        </span>
        <span className={`px-2 py-1 rounded-full bg-slate-700 ${budget.color}`}>
          {budget.label}
        </span>
      </div>
    </div>
  );
}

export function UseCaseSection({
  seoindicator,
  competitor,
}: UseCaseSectionProps) {
  if (!seoindicator?.length && !competitor?.length) {
    return (
      <div
        className="text-slate-400 text-center py-8"
        data-testid="empty-use-cases"
      >
        No use case data available.
      </div>
    );
  }

  return (
    <div data-testid="use-cases-section">
      <div className="grid gap-8 md:grid-cols-2">
        {/* SEOIndicator Use Cases */}
        <div>
          <h3 className="text-lg font-semibold text-emerald-400 mb-4">
            Best for SEOIndicator users
          </h3>
          <div
            className="space-y-3"
            data-testid="seoindicator-use-cases"
          >
            {seoindicator?.map((useCase) => (
              <UseCaseCard
                key={useCase.title}
                useCase={useCase}
                brand="seoindicator"
              />
            ))}
          </div>
        </div>

        {/* Competitor Use Cases */}
        <div>
          <h3 className="text-lg font-semibold text-blue-400 mb-4">
            Best for Competitor users
          </h3>
          <div
            className="space-y-3"
            data-testid="competitor-use-cases"
          >
            {competitor?.map((useCase) => (
              <UseCaseCard
                key={useCase.title}
                useCase={useCase}
                brand="competitor"
              />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export default UseCaseSection;
