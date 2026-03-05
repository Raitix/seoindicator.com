/**
 * PricingComparison Component
 * Task: TASK-32-6 - Pricing Comparison Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays pricing tiers side by side for comparison.
 */

'use client';

import React, { useState } from 'react';
import type { PricingComparisonProps, PricingTier } from '@/lib/compare/types';

/**
 * Formats price for display.
 */
function formatPrice(price: number | 'Free' | 'Custom'): string {
  if (price === 'Free') return 'Free';
  if (price === 'Custom') return 'Custom';
  return `$${price}`;
}

/**
 * Renders a single pricing tier card.
 */
function PricingTierCard({
  tier,
  isHighlighted,
  brand,
}: {
  tier: PricingTier;
  isHighlighted?: boolean;
  brand: 'seoindicator' | 'competitor';
}) {
  const brandColor = brand === 'seoindicator' ? 'emerald' : 'blue';

  return (
    <div
      className={`p-4 rounded-lg border ${
        isHighlighted
          ? `border-${brandColor}-500 bg-${brandColor}-900/20`
          : 'border-slate-700 bg-slate-800/30'
      }`}
      data-testid={`tier-${brand}-${tier.name.toLowerCase().replace(/\s+/g, '-')}`}
    >
      {isHighlighted && (
        <span
          className={`inline-block px-2 py-1 text-xs font-medium rounded-full bg-${brandColor}-500/20 text-${brandColor}-400 mb-2`}
        >
          Most Popular
        </span>
      )}
      <h4 className="font-semibold text-white mb-2">{tier.name}</h4>
      <div className="text-2xl font-bold text-white mb-1">
        {formatPrice(tier.monthlyPrice)}
        {typeof tier.monthlyPrice === 'number' && (
          <span className="text-sm font-normal text-slate-400">/mo</span>
        )}
      </div>
      {typeof tier.annualPrice === 'number' && tier.annualPrice > 0 && (
        <div className="text-sm text-slate-400 mb-3">
          {formatPrice(tier.annualPrice)}/year
        </div>
      )}
      <ul className="space-y-1 text-sm text-slate-300">
        {tier.features.map((feature) => (
          <li key={feature} className="flex items-center gap-2">
            <span className="text-emerald-400">✓</span>
            {feature}
          </li>
        ))}
      </ul>
    </div>
  );
}

export function PricingComparison({
  seoindicator,
  competitor,
  showFreeTier = true,
}: PricingComparisonProps) {
  const [showAnnual, setShowAnnual] = useState(false);

  // Filter out free tier if not wanted
  const seoindicatorTiers = showFreeTier
    ? seoindicator
    : seoindicator.filter((t) => t.monthlyPrice !== 'Free');
  const competitorTiers = showFreeTier
    ? competitor
    : competitor.filter((t) => t.monthlyPrice !== 'Free');

  if (!seoindicatorTiers?.length && !competitorTiers?.length) {
    return (
      <div
        className="text-slate-400 text-center py-8"
        data-testid="empty-pricing"
      >
        No pricing data available.
      </div>
    );
  }

  return (
    <div data-testid="pricing-comparison">
      {/* Toggle */}
      <div className="flex justify-center mb-6">
        <div className="inline-flex rounded-lg border border-slate-700 p-1">
          <button
            onClick={() => setShowAnnual(false)}
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              !showAnnual
                ? 'bg-emerald-600 text-white'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Monthly
          </button>
          <button
            onClick={() => setShowAnnual(true)}
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              showAnnual
                ? 'bg-emerald-600 text-white'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Annual
          </button>
        </div>
      </div>

      {/* Pricing Grid */}
      <div className="grid gap-8 md:grid-cols-2">
        {/* SEOIndicator Pricing */}
        <div>
          <h3 className="text-xl font-semibold text-emerald-400 mb-4 text-center">
            SEOIndicator
          </h3>
          <div
            className="space-y-4"
            data-testid="seoindicator-pricing"
            data-tiers={seoindicatorTiers.length}
          >
            {seoindicatorTiers.map((tier) => (
              <PricingTierCard
                key={tier.name}
                tier={tier}
                isHighlighted={tier.highlighted}
                brand="seoindicator"
              />
            ))}
          </div>
        </div>

        {/* Competitor Pricing */}
        <div>
          <h3 className="text-xl font-semibold text-blue-400 mb-4 text-center">
            Competitor
          </h3>
          <div
            className="space-y-4"
            data-testid="competitor-pricing"
            data-tiers={competitorTiers.length}
          >
            {competitorTiers.map((tier) => (
              <PricingTierCard
                key={tier.name}
                tier={tier}
                isHighlighted={tier.highlighted}
                brand="competitor"
              />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export default PricingComparison;
