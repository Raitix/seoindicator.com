/**
 * ComparisonCTA Component
 * Task: TASK-32-9 - Call to Action Section
 * Task: TASK-32-18 - A/B Test Infrastructure Integration
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a call-to-action button or banner with support for:
 * - Primary variant: Full-width section with heading and TWO buttons
 * - Secondary variant: Smaller inline CTA
 * - Sticky variant: Fixed at bottom on mobile, appears after 500px scroll
 * - A/B testing: Optional integration with useABTest hook
 */

'use client';

import React, { useState, useEffect, useCallback } from 'react';
import type { ComparisonCTAProps } from '@/lib/compare/types';
import { trackEvent } from '@/lib/analytics';
import { ArrowRight, ExternalLink } from 'lucide-react';
import { useABTest, trackConversion } from '@/hooks/useABTest';
import { getTestConfig } from '@/lib/ab-tests';

// Default CTA text for A/B tests
const DEFAULT_CTA_TEXT = 'Try SEOIndicator Free';

/**
 * ComparisonCTA Component
 *
 * @param variant - Visual variant: 'primary', 'secondary', or 'sticky'
 * @param text - Custom button text (default: "Try SEOIndicator Free")
 * @param href - Link destination (default: "/register")
 * @param abTestGroup - A/B test group identifier for analytics
 * @param abTestId - A/B test ID to automatically fetch variant and text
 */
export function ComparisonCTA({
  variant = 'primary',
  text = DEFAULT_CTA_TEXT,
  href = '/register',
  abTestGroup,
  abTestId,
}: ComparisonCTAProps & { abTestId?: string }) {
  const [showSticky, setShowSticky] = useState(false);

  // A/B test integration
  const abVariant = useABTest(abTestId || '');
  const abConfig = abTestId ? getTestConfig(abTestId, abVariant) : undefined;
  const finalAbGroup = abTestGroup || (abTestId ? abVariant : undefined);
  const finalText = abConfig?.text ? String(abConfig.text) : text;

  // Track CTA click with A/B test conversion
  const handleClick = useCallback(() => {
    // Track basic CTA click
    trackEvent('cta_click', {
      variant,
      text: finalText,
      href,
      abTestGroup: finalAbGroup,
      page: typeof window !== 'undefined' ? window.location.pathname : '',
    });

    // Track A/B test conversion if in test
    if (abTestId && finalAbGroup) {
      trackConversion(abTestId, finalAbGroup, 'cta_click');
    }
  }, [variant, finalText, href, finalAbGroup, abTestId]);

  // Sticky scroll handler - show after 500px scroll
  useEffect(() => {
    if (variant !== 'sticky') return;

    const handleScroll = () => {
      setShowSticky(window.scrollY > 500);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [variant]);

  // Sticky variant - only on mobile, appears after scroll
  if (variant === 'sticky') {
    if (!showSticky) return null;

    return (
      <div
        data-testid="cta-sticky"
        className="fixed bottom-0 left-0 right-0 bg-surface-2 border-t border-slate-700 p-4 md:hidden z-50"
        role="complementary"
        aria-label="Mobile call to action"
      >
        <a
          href={href}
          onClick={handleClick}
          data-testid="cta-link"
          data-variant={variant}
          data-ab-test-group={finalAbGroup}
          className="block w-full bg-brand-primary text-white text-center py-3 rounded-lg font-semibold hover:bg-brand-primary/90"
        >
          {finalText}
          <ArrowRight className="inline-block ml-2 w-4 h-4" aria-hidden="true" data-testid="arrow-icon" />
        </a>
      </div>
    );
  }

  // Secondary variant - inline CTA
  if (variant === 'secondary') {
    return (
      <a
        href={href}
        onClick={handleClick}
        data-testid="cta-link"
        data-variant={variant}
        data-ab-test-group={finalAbGroup}
        className="inline-flex items-center gap-2 px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
        role="link"
      >
        {finalText}
        <ExternalLink className="w-4 h-4" aria-hidden="true" data-testid="external-icon" />
      </a>
    );
  }

  // Primary variant - full section with TWO buttons
  return (
    <section
      data-testid="cta-primary"
      className="comparison-cta comparison-cta--primary bg-surface-1 rounded-xl p-8 text-center"
      aria-labelledby="cta-heading"
    >
      <div className="cta-content max-w-2xl mx-auto">
        <h3 id="cta-heading" className="text-2xl font-bold text-white mb-2">
          Ready to Try SEOIndicator?
        </h3>
        <p className="text-slate-300 mb-6">
          Start with our free tier - no credit card required
        </p>
        <div className="cta-buttons flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href={href}
            onClick={handleClick}
            data-testid="cta-primary-button"
            data-variant={variant}
            data-ab-test-group={finalAbGroup}
            className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary text-white rounded-lg font-semibold hover:bg-brand-primary/90"
          >
            {finalText}
            <ArrowRight className="w-4 h-4" aria-hidden="true" data-testid="arrow-icon" />
          </a>
          <a
            href="/features"
            data-testid="cta-secondary-button"
            className="inline-flex items-center justify-center px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
          >
            View Features
          </a>
        </div>
      </div>
    </section>
  );
}

/**
 * ComparisonCTAWithABTest - Pre-wrapped component with A/B test integration
 *
 * This component automatically uses the 'comparison-cta-text' A/B test
 * to determine the CTA button text.
 *
 * @param variant - Visual variant: 'primary', 'secondary', or 'sticky'
 * @param href - Link destination (default: "/register")
 *
 * @example
 * ```tsx
 * // Automatic A/B testing
 * <ComparisonCTAWithABTest variant="primary" />
 *
 * // Manual A/B test ID
 * <ComparisonCTAWithABTest variant="primary" testId="comparison-cta-text" />
 * ```
 */
export function ComparisonCTAWithABTest({
  variant = 'primary',
  href = '/register',
  testId = 'comparison-cta-text',
}: {
  variant?: 'primary' | 'secondary' | 'sticky';
  href?: string;
  testId?: string;
}) {
  const abVariant = useABTest(testId);
  const config = getTestConfig(testId, abVariant);
  const text = config?.text ? String(config.text) : DEFAULT_CTA_TEXT;

  return (
    <ComparisonCTA
      variant={variant}
      text={text}
      href={href}
      abTestGroup={abVariant}
      abTestId={testId}
    />
  );
}

export default ComparisonCTA;
