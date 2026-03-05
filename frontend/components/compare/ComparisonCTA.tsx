/**
 * ComparisonCTA Component
 * Task: TASK-32-9 - Call to Action Section
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays a call-to-action button or banner.
 */

'use client';

import React from 'react';
import Link from 'next/link';
import type { ComparisonCTAProps } from '@/lib/compare/types';
import { Button } from '@/components/ui/button';

export function ComparisonCTA({
  variant = 'primary',
  text = 'Get Started Free',
  href = '/register',
  abTestGroup,
}: ComparisonCTAProps) {
  // Primary variant - full width centered button
  if (variant === 'primary') {
    return (
      <div
        className="py-8"
        data-testid="comparison-cta"
        data-variant="primary"
        data-ab-test-group={abTestGroup}
      >
        <Button
          asChild
          size="lg"
          className="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-6 text-lg font-semibold rounded-lg shadow-lg shadow-emerald-500/20"
        >
          <Link href={href}>
            {text}
          </Link>
        </Button>
        <p className="text-sm text-slate-400 mt-3">
          No credit card required • Free tier available
        </p>
      </div>
    );
  }

  // Secondary variant - outlined button
  if (variant === 'secondary') {
    return (
      <div
        className="py-6"
        data-testid="comparison-cta"
        data-variant="secondary"
        data-ab-test-group={abTestGroup}
      >
        <Button
          asChild
          variant="outline"
          size="lg"
          className="border-emerald-500 text-emerald-400 hover:bg-emerald-500/10 px-6"
        >
          <Link href={href}>
            {text}
          </Link>
        </Button>
      </div>
    );
  }

  // Sticky variant - fixed at bottom of viewport
  if (variant === 'sticky') {
    return (
      <div
        className="fixed bottom-0 left-0 right-0 bg-slate-900/95 border-t border-slate-800 py-3 px-4 backdrop-blur-sm z-50"
        data-testid="comparison-cta"
        data-variant="sticky"
        data-ab-test-group={abTestGroup}
      >
        <div className="max-w-5xl mx-auto flex items-center justify-between gap-4">
          <div className="hidden sm:block">
            <span className="text-white font-medium">
              Ready to improve your SEO?
            </span>
          </div>
          <Button
            asChild
            className="bg-emerald-600 hover:bg-emerald-700 text-white"
          >
            <Link href={href}>
              {text}
            </Link>
          </Button>
        </div>
      </div>
    );
  }

  return null;
}

export default ComparisonCTA;
