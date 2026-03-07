/**
 * Comparison Page Route
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Dynamic route page using Next.js App Router with static site generation
 * via generateStaticParams(). Renders comparison content between SEO tools.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { getComparisonBySlug, COMPARISON_SLUGS } from '@/lib/compare/registry';
import { ComparisonData, ComparisonPageProps } from '@/lib/compare/types';
import { getComparisonOGMetadata, OG_IMAGE_WIDTH, OG_IMAGE_HEIGHT } from '@/lib/compare/og-images';

// Components
import { ComparisonTable } from '@/components/compare/ComparisonTable';
import { PricingComparison } from '@/components/compare/PricingComparison';
import { ProsConsCard } from '@/components/compare/ProsConsCard';
import { UseCaseSection } from '@/components/compare/UseCaseSection';
import { ComparisonCTA } from '@/components/compare/ComparisonCTA';
import { ComparisonSchema } from '@/components/compare/ComparisonSchema';
import { FAQSection } from '@/components/compare/FAQSection';
import { RelatedComparisons } from '@/components/compare/RelatedComparisons';

// ============================================================================
// Static Generation
// ============================================================================

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
