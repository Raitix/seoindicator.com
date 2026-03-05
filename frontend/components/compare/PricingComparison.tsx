/**
 * PricingComparison Component
 * Task: TASK-32-6 - Pricing Comparison Component
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays pricing tiers side by side for comparison with billing toggle.
 */

'use client';

import React, { useState, useMemo, useCallback } from 'react';
import type { PricingComparisonProps, PricingTier } from '@/lib/compare/types';

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Formats price for display with proper decimal handling.
 * - Free → "Free"
 * - Custom → "Custom"
 * - Number → "$X" or "$X.XX" (rounded to 2 decimals)
 */
function formatPrice(price: number | 'Free' | 'Custom'): string {
  if (price === 'Free') return 'Free';
  if (price === 'Custom') return 'Custom';
  
  // Round to 2 decimal places
  const rounded = Math.round(price * 100) / 100;
  
  // Show decimals only if needed
  if (rounded % 1 === 0) {
    return `$${rounded}`;
  }
  return `$${rounded.toFixed(2)}`;
}

/**
 * Calculates annual savings percentage.
 * Formula: ((monthly * 12 - annual * 12) / (monthly * 12)) * 100
 */
function calculateSavings(monthly: number, annual: number): number {
  if (monthly === 0 || annual === 0) return 0;
  const monthlyAnnual = monthly * 12;
  const annualTotal = annual * 12;
  return Math.round(((monthlyAnnual - annualTotal) / monthlyAnnual) * 100);
}

// ============================================================================
// Sub-Components
// ============================================================================

/**
 * Billing cycle toggle (Monthly/Annual)
 */
function BillingToggle({
  value,
  onChange,
}: {
  value: 'monthly' | 'annual';
  onChange: (v: 'monthly' | 'annual') => void;
}) {
  return (
    <div
      data-testid="pricing-toggle"
      role="group"
      aria-label="Billing period"
      className="flex justify-center mb-6"
    >
      <div className="inline-flex rounded-lg border border-slate-700 p-1">
        <button
          data-testid="monthly-toggle"
          onClick={() => onChange('monthly')}
          aria-pressed={value === 'monthly'}
          className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
            value === 'monthly'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-400 hover:text-white'
          }`}
        >
          Monthly
        </button>
        <button
          data-testid="annual-toggle"
          onClick={() => onChange('annual')}
          aria-pressed={value === 'annual'}
          className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
            value === 'annual'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-400 hover:text-white'
          }`}
        >
          Annual
        </button>
      </div>
    </div>
  );
}

/**
 * Price display with optional savings badge for annual billing.
 */
function PriceDisplay({
  tier,
  cycle,
  index,
  brand,
}: {
  tier: PricingTier;
  cycle: 'monthly' | 'annual';
  index: number;
  brand: 'seoindicator' | 'competitor';
}) {
  const price = cycle === 'annual' ? tier.annualPrice : tier.monthlyPrice;
  const isFree = tier.monthlyPrice === 'Free';
  const isCustom = tier.monthlyPrice === 'Custom';
  
  // Calculate savings for annual billing
  const showSavings =
    cycle === 'annual' &&
    typeof tier.monthlyPrice === 'number' &&
    typeof tier.annualPrice === 'number' &&
    !isFree &&
    !isCustom;
  
  const savings = showSavings
    ? calculateSavings(tier.monthlyPrice as number, tier.annualPrice as number)
    : 0;
  
  const testIdPrefix = brand === 'seoindicator' ? '' : 'competitor-';
  const priceTestId = brand === 'seoindicator' 
    ? `tier-price-${index}` 
    : `competitor-tier-price-${index}`;
  const savingsTestId = brand === 'seoindicator'
    ? `savings-badge-${index}`
    : `competitor-savings-badge-${index}`;
  
  return (
    <div data-testid={priceTestId} className="text-2xl font-bold text-white mb-1">
      {formatPrice(price)}
      {typeof price === 'number' && (
        <span className="text-sm font-normal text-slate-400">/mo</span>
      )}
      {showSavings && (
        <span
          data-testid={savingsTestId}
          className="ml-2 text-xs font-medium text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-full"
        >
          Save {savings}%
        </span>
      )}
    </div>
  );
}

/**
 * Feature list with check icons.
 */
function FeatureList({ features }: { features: string[] }) {
  return (
    <ul className="space-y-1 text-sm text-slate-300">
      {features.map((feature, idx) => (
        <li key={feature} data-testid={`feature-${idx}`}>
          <span data-testid="check-icon" className="text-emerald-400 mr-2">✓</span>
          {feature}
        </li>
      ))}
    </ul>
  );
}

/**
 * Individual pricing tier card.
 */
function PricingCard({
  tier,
  cycle,
  index,
  brand,
}: {
  tier: PricingTier;
  cycle: 'monthly' | 'annual';
  index: number;
  brand: 'seoindicator' | 'competitor';
}) {
  const isFree = tier.monthlyPrice === 'Free';
  const isHighlighted = tier.highlighted;
  
  const tierTestId = brand === 'seoindicator'
    ? `seoindicator-tier-${index}`
    : `competitor-tier-${index}`;
  const nameTestId = brand === 'seoindicator'
    ? `tier-name-${index}`
    : `competitor-tier-name-${index}`;
  const featuresTestId = brand === 'seoindicator'
    ? `tier-features-${index}`
    : `competitor-tier-features-${index}`;
  const freeBadgeTestId = `free-badge-${index}`;
  const popularBadgeTestId = brand === 'seoindicator'
    ? `popular-badge-${index}`
    : `competitor-popular-badge-${index}`;
  
  return (
    <div
      data-testid={tierTestId}
      className={`p-4 rounded-lg border transition-colors ${
        isHighlighted
          ? 'border-emerald-500 bg-emerald-900/20 highlighted'
          : 'border-slate-700 bg-slate-800/30'
      }`}
    >
      {/* Popular badge for highlighted tiers */}
      {isHighlighted && (
        <span
          data-testid={popularBadgeTestId}
          className="inline-block px-2 py-1 text-xs font-medium rounded-full bg-emerald-500/20 text-emerald-400 mb-2"
        >
          Popular
        </span>
      )}
      
      {/* Free tier badge */}
      {isFree && (
        <span
          data-testid={freeBadgeTestId}
          className="free-tier-badge inline-block px-2 py-1 text-xs font-medium rounded-full bg-emerald-500/30 text-emerald-300 mb-2"
        >
          Free Forever
        </span>
      )}
      
      {/* Tier name */}
      <h4 data-testid={nameTestId} className="font-semibold text-white mb-2">
        {tier.name}
      </h4>
      
      {/* Price display */}
      <PriceDisplay tier={tier} cycle={cycle} index={index} brand={brand} />
      
      {/* Features list */}
      <div data-testid={featuresTestId}>
        <FeatureList features={tier.features} />
      </div>
    </div>
  );
}

// ============================================================================
// Main Component
// ============================================================================

export function PricingComparison({
  seoindicator,
  competitor,
  showFreeTier = true,
}: PricingComparisonProps) {
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'annual'>('monthly');

  // Memoize filtered tiers
  const seoindicatorTiers = useMemo(() => {
    const tiers = seoindicator || [];
    return showFreeTier
      ? tiers
      : tiers.filter((t) => t.monthlyPrice !== 'Free');
  }, [seoindicator, showFreeTier]);

  const competitorTiers = useMemo(() => {
    const tiers = competitor || [];
    return showFreeTier
      ? tiers
      : tiers.filter((t) => t.monthlyPrice !== 'Free');
  }, [competitor, showFreeTier]);

  const handleCycleChange = useCallback((cycle: 'monthly' | 'annual') => {
    setBillingCycle(cycle);
  }, []);

  return (
    <div data-testid="pricing-comparison" aria-label="Pricing comparison">
      {/* Billing Toggle */}
      <BillingToggle value={billingCycle} onChange={handleCycleChange} />

      {/* Pricing Cards Grid */}
      <div
        data-testid="pricing-grid"
        className="grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        {/* SEOIndicator Pricing Column */}
        <div
          data-testid="seoindicator-pricing"
          aria-label="SEOIndicator pricing tiers"
        >
          <h3
            data-testid="seoindicator-header"
            className="text-xl font-semibold text-emerald-400 mb-4 text-center"
          >
            SEOIndicator
          </h3>
          <div className="space-y-4">
            {seoindicatorTiers.map((tier, idx) => (
              <PricingCard
                key={tier.name}
                tier={tier}
                cycle={billingCycle}
                index={idx}
                brand="seoindicator"
              />
            ))}
          </div>
        </div>

        {/* Competitor Pricing Column */}
        <div
          data-testid="competitor-pricing"
          aria-label="Competitor pricing tiers"
        >
          <h3
            data-testid="competitor-header"
            className="text-xl font-semibold text-blue-400 mb-4 text-center"
          >
            Competitor
          </h3>
          <div className="space-y-4">
            {competitorTiers.map((tier, idx) => (
              <PricingCard
                key={tier.name}
                tier={tier}
                cycle={billingCycle}
                index={idx}
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
