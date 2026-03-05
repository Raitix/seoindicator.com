/**
 * ComparisonCTA Component
 * Task: TASK-32-9 - Call to Action Section
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a call-to-action button or banner with support for:
 * - Primary variant: Full-width section with heading and TWO buttons
 * - Secondary variant: Smaller inline CTA
 * - Sticky variant: Fixed at bottom on mobile, appears after 500px scroll
 */

'use client';

import React, { useState, useEffect, useCallback } from 'react';
import type { ComparisonCTAProps } from '@/lib/compare/types';
import { trackEvent } from '@/lib/analytics';
import { ArrowRight, ExternalLink } from 'lucide-react';

/**
 * ComparisonCTA Component
 *
 * @param variant - Visual variant: 'primary', 'secondary', or 'sticky'
 * @param text - Custom button text (default: "Try SEOIndicator Free")
 * @param href - Link destination (default: "/register")
 * @param abTestGroup - A/B test group identifier for analytics
 */
export function ComparisonCTA({
  variant = 'primary',
  text = 'Try SEOIndicator Free',
  href = '/register',
  abTestGroup,
}: ComparisonCTAProps) {
  const [showSticky, setShowSticky] = useState(false);

  // Track CTA click
  const handleClick = useCallback(() => {
    trackEvent('cta_click', {
      variant,
      text,
      href,
      abTestGroup,
      page: typeof window !== 'undefined' ? window.location.pathname : '',
    });
  }, [variant, text, href, abTestGroup]);

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
          data-ab-test-group={abTestGroup}
          className="block w-full bg-brand-primary text-white text-center py-3 rounded-lg font-semibold hover:bg-brand-primary/90"
        >
          {text}
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
        data-ab-test-group={abTestGroup}
        className="inline-flex items-center gap-2 px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-lg"
        role="link"
      >
        {text}
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
            data-ab-test-group={abTestGroup}
            className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary text-white rounded-lg font-semibold hover:bg-brand-primary/90"
          >
            {text}
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

export default ComparisonCTA;
