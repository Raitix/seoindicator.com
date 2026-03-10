/**
 * Comparison Page Route
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Task: TASK-32-22 - Performance Optimization and Launch
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Dynamic route page using Next.js App Router with static site generation
 * via generateStaticParams(). Renders comparison content between SEO tools.
 *
 * Performance optimizations:
 * - Force static rendering for optimal build output
 * - Static params generation at build time
 * - Optimized metadata for SEO
 */

import { Metadata } from 'next';
import Link from 'next/link';
import dynamicImport from 'next/dynamic';
import { notFound } from 'next/navigation';
import { Suspense } from 'react';

import { getComparisonBySlug, COMPARISON_SLUGS } from '@/lib/compare/registry';
import { ComparisonData, ComparisonPageProps } from '@/lib/compare/types';
import { getComparisonOGMetadata, OG_IMAGE_WIDTH, OG_IMAGE_HEIGHT } from '@/lib/compare/og-images';

// Static components (imported directly for optimal bundle size)
import { ProsConsCard } from '@/components/compare/ProsConsCard';
import { UseCaseSection } from '@/components/compare/UseCaseSection';
import { ComparisonCTA } from '@/components/compare/ComparisonCTA';
import { ComparisonSchema } from '@/components/compare/ComparisonSchema';

// Skeleton components for loading states
import { Skeleton } from '@/components/ui/skeleton';

// ============================================================================
// Dynamic Imports for Heavy Components (Code Splitting)
// ============================================================================

/**
 * ComparisonTable - Lazy loaded for better initial bundle size
 * Heavy component with feature comparison table
 */
const ComparisonTable = dynamicImport(
  () => import('@/components/compare/ComparisonTable').then(mod => ({ default: mod.ComparisonTable })),
  {
    loading: () => <ComparisonTableSkeleton />,
    ssr: true, // Keep SSR for SEO and static generation
  }
);

/**
 * PricingComparison - Lazy loaded component
 * Contains pricing data for both tools
 */
const PricingComparison = dynamicImport(
  () => import('@/components/compare/PricingComparison').then(mod => ({ default: mod.PricingComparison })),
  {
    loading: () => <PricingComparisonSkeleton />,
    ssr: true,
  }
);

/**
 * FAQSection - Lazy loaded, below the fold
 * Contains FAQ accordion with structured data
 */
const FAQSection = dynamicImport(
  () => import('@/components/compare/FAQSection').then(mod => ({ default: mod.FAQSection })),
  {
    loading: () => <FAQSkeleton />,
    ssr: true,
  }
);

/**
 * RelatedComparisons - Lazy loaded, bottom of page
 * Links to related comparison pages
 */
const RelatedComparisons = dynamicImport(
  () => import('@/components/compare/RelatedComparisons').then(mod => ({ default: mod.RelatedComparisons })),
  {
    loading: () => <RelatedComparisonsSkeleton />,
    ssr: true,
  }
);

// ============================================================================
// Skeleton Components for Loading States
// ============================================================================

/**
 * Skeleton for comparison table section
 */
function ComparisonTableSkeleton() {
  return (
    <div className="w-full bg-slate-900/50 rounded-xl border border-slate-800 p-4 sm:p-6" data-testid="comparison-table-skeleton">
      <div className="grid grid-cols-3 gap-4 pb-3 border-b border-slate-700">
        <Skeleton className="h-4 w-20" />
        <Skeleton className="h-4 w-24 mx-auto" />
        <Skeleton className="h-4 w-24 mx-auto" />
      </div>
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className="grid grid-cols-3 gap-4 py-3 border-b border-slate-800/50">
          <Skeleton className="h-4 w-32" />
          <Skeleton className="h-5 w-5 mx-auto rounded-full" />
          <Skeleton className="h-5 w-5 mx-auto rounded-full" />
        </div>
      ))}
    </div>
  );
}

/**
 * Skeleton for pricing comparison section
 */
function PricingComparisonSkeleton() {
  return (
    <div className="grid gap-6 md:grid-cols-2" data-testid="pricing-comparison-skeleton">
      {Array.from({ length: 2 }).map((_, i) => (
        <div key={i} className="bg-slate-900/50 rounded-xl border border-slate-800 p-6">
          <Skeleton className="h-6 w-32 mb-4" />
          <div className="space-y-3">
            {Array.from({ length: 3 }).map((_, j) => (
              <div key={j} className="flex justify-between">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-4 w-16" />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

/**
 * Skeleton for FAQ section
 */
function FAQSkeleton() {
  return (
    <div className="space-y-4" data-testid="faq-skeleton">
      {Array.from({ length: 3 }).map((_, i) => (
        <div key={i} className="bg-slate-900/50 rounded-xl border border-slate-800 p-4">
          <Skeleton className="h-5 w-3/4 mb-2" />
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-5/6 mt-1" />
        </div>
      ))}
    </div>
  );
}

/**
 * Skeleton for related comparisons section
 */
function RelatedComparisonsSkeleton() {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-testid="related-comparisons-skeleton">
      {Array.from({ length: 3 }).map((_, i) => (
        <div key={i} className="bg-slate-900/50 rounded-xl border border-slate-800 p-4">
          <Skeleton className="h-5 w-32 mb-2" />
          <Skeleton className="h-4 w-full" />
        </div>
      ))}
    </div>
  );
}

// ============================================================================
// Static Generation Configuration
// ============================================================================

/**
 * Force static rendering for optimal performance.
 * Ensures pages are pre-rendered at build time with no dynamic rendering.
 */
export const dynamic = 'force-static';

/**
 * Generate static params for all comparison pages.
 * Called at build time to pre-render all comparison pages.
 */
export function generateStaticParams(): Array<{ slug: string }> {
  return COMPARISON_SLUGS.map((slug) => ({ slug }));
}

// ============================================================================
// Metadata Generation
// ============================================================================

/**
 * Generate metadata for SEO optimization.
 * Creates title, description, and OpenGraph data from comparison data.
 */
export async function generateMetadata({
  params,
}: ComparisonPageProps): Promise<Metadata> {
  // In Next.js 16, params must be awaited
  const { slug } = await params;
  const comparison = getComparisonBySlug(slug);

  if (!comparison) {
    return {
      title: 'Comparison Not Found',
    };
  }

  const url = `https://seoindicator.com/compare/${comparison.slug}/`;
  
  // Get OG image metadata (uses pre-generated static image)
  const ogImage = getComparisonOGMetadata(comparison.slug, 'https://seoindicator.com');

  return {
    title: comparison.title,
    description: comparison.metaDescription,
    robots: {
      index: true,
      follow: true,
    },
    alternates: {
      canonical: url,
    },
    openGraph: {
      title: comparison.title,
      description: comparison.metaDescription,
      url,
      siteName: 'SEOIndicator',
      type: 'article',
      images: [
        {
          url: ogImage.url,
          width: ogImage.width,
          height: ogImage.height,
          alt: ogImage.alt,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title: comparison.title,
      description: comparison.metaDescription,
      images: [ogImage.url],
    },
  };
}

// ============================================================================
// Page Component
// ============================================================================

export default async function ComparisonPage({ params }: ComparisonPageProps) {
  // In Next.js 16, params must be awaited
  const { slug } = await params;
  const comparison = getComparisonBySlug(slug);

  if (!comparison) {
    notFound();
  }

  // Get competitor names for display
  const seoindicatorName = 'SEOIndicator';
  const competitorId = comparison.competitors.find((id) => id !== 'seoindicator') || 'competitor';
  const competitorName = competitorId.charAt(0).toUpperCase() + competitorId.slice(1);

  return (
    <main
      className="min-h-screen bg-slate-950 text-white"
      role="main"
      aria-label="Comparison page"
    >
      {/* Breadcrumb Navigation */}
      <nav
        aria-label="Breadcrumb"
        className="border-b border-slate-800 bg-slate-900/50"
      >
        <div className="mx-auto max-w-5xl px-4 py-3">
          <ol className="flex items-center gap-2 text-sm text-slate-400">
            <li>
              <Link
                href="/"
                className="hover:text-emerald-400 transition-colors"
              >
                Home
              </Link>
            </li>
            <li className="text-slate-600" aria-hidden="true">
              /
            </li>
            <li>
              <Link
                href="/compare"
                className="hover:text-emerald-400 transition-colors"
              >
                Compare
              </Link>
            </li>
            <li className="text-slate-600" aria-hidden="true">
              /
            </li>
            <li className="text-white" aria-current="page">
              {comparison.title.replace(': Which SEO Tool Is Better?', '')}
            </li>
          </ol>
        </div>
      </nav>

      {/* Content */}
      <article className="mx-auto max-w-5xl px-4 py-10">
        {/* Hero Section */}
        <header className="mb-12 text-center">
          <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
            {comparison.title}
          </h1>
          <p
            className="text-lg text-slate-400 max-w-3xl mx-auto"
            data-testid="summary"
          >
            {comparison.summary}
          </p>
        </header>

        {/* Feature Comparison */}
        <section
          aria-labelledby="features-heading"
          className="mb-16"
        >
          <h2
            id="features-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Feature Comparison
          </h2>
          <div data-testid="comparison-table-wrapper">
            <ComparisonTable features={comparison.featureComparison} />
          </div>
        </section>

        {/* Pricing Comparison */}
        <section
          aria-labelledby="pricing-heading"
          className="mb-16"
        >
          <h2
            id="pricing-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Pricing Comparison
          </h2>
          <div data-testid="pricing-comparison-wrapper">
            <PricingComparison
              seoindicator={comparison.pricingComparison.seoindicator}
              competitor={comparison.pricingComparison.competitor}
            />
          </div>
        </section>

        {/* Pros & Cons */}
        <section
          aria-labelledby="pros-cons-heading"
          className="mb-16"
        >
          <h2
            id="pros-cons-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Pros & Cons
          </h2>
          <div
            className="grid gap-6 md:grid-cols-2"
            data-testid="pros-cons-wrapper"
          >
            <ProsConsCard
              tool={seoindicatorName}
              pros={comparison.prosCons.seoindicator.pros}
              cons={comparison.prosCons.seoindicator.cons}
            />
            <ProsConsCard
              tool={competitorName}
              pros={comparison.prosCons.competitor.pros}
              cons={comparison.prosCons.competitor.cons}
            />
          </div>
        </section>

        {/* Use Cases */}
        <section
          aria-labelledby="use-cases-heading"
          className="mb-16"
        >
          <h2
            id="use-cases-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Best For
          </h2>
          <div data-testid="use-cases-wrapper">
            <UseCaseSection
              seoindicator={comparison.useCases.seoindicator}
              competitor={comparison.useCases.competitor}
            />
          </div>
        </section>

        {/* Verdict */}
        <section
          aria-labelledby="verdict-heading"
          className="mb-16 bg-gradient-to-r from-emerald-900/30 to-slate-800/50 rounded-xl p-6 border border-emerald-500/20"
        >
          <h2
            id="verdict-heading"
            className="text-2xl font-bold mb-4 text-emerald-400"
          >
            Our Verdict
          </h2>
          <p
            className="text-lg text-slate-300"
            data-testid="verdict"
          >
            {comparison.verdict}
          </p>
        </section>

        {/* FAQ Section */}
        <section
          aria-labelledby="faq-heading"
          className="mb-16"
        >
          <h2
            id="faq-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Frequently Asked Questions
          </h2>
          <div data-testid="faq-wrapper">
            <FAQSection faqs={comparison.faqs} />
          </div>
        </section>

        {/* CTA Section */}
        <section className="mb-16 text-center">
          <div data-testid="cta-wrapper">
            <ComparisonCTA variant="primary" />
          </div>
        </section>

        {/* Related Comparisons */}
        <section
          aria-labelledby="related-heading"
          className="mb-16"
        >
          <h2
            id="related-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            Related Comparisons
          </h2>
          <div data-testid="related-wrapper">
            <RelatedComparisons
              slugs={comparison.relatedComparisons}
              currentSlug={comparison.slug}
            />
          </div>
        </section>
      </article>

      {/* Structured Data */}
      <ComparisonSchema
        comparison={comparison}
        faqs={comparison.faqs}
      />
    </main>
  );
}
