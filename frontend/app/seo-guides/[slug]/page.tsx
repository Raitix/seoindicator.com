/**
 * SEO Guide Dynamic Page
 * Task: TASK-20-4 - Set Up Routing with Static Params Generation
 * Updated: TASK-20-29 - Render Guide Content in Page Component
 * 
 * Dynamic route for individual SEO guide pages with static site generation.
 * Generates static HTML for all 17 guide slugs at build time.
 */

import { notFound } from 'next/navigation';
import { Metadata } from 'next';
import Link from 'next/link';
import {
  getGuideBySlug,
  getAllGuides,
  GUIDE_SLUGS,
  getRelatedGuides
} from '@/lib/guides/registry';
import type { SEOGuide } from '@/lib/guides/types';
import { GuideSection, GuideChecklist, GuideSchema } from '@/components/guides';
import { ToolEmbed } from '@/components/guides/ToolEmbed';

// ============================================================================
// Static Params Generation (SSG)
// ============================================================================

/**
 * Generate static params for all guide slugs
 * Called at build time to pre-render all guide pages
 */
export function generateStaticParams(): { slug: string }[] {
  return GUIDE_SLUGS.map((slug) => ({ slug }));
}

// ============================================================================
// Metadata Generation (SEO)
// ============================================================================

/**
 * Generate metadata for each guide page
 * Includes title, description, Open Graph, and Twitter cards
 */
export async function generateMetadata({
  params
}: {
  params: Promise<{ slug: string }>
}): Promise<Metadata> {
  const { slug } = await params;
  const guide = getGuideBySlug(slug);

  if (!guide) {
    return {
      title: 'Guide Not Found',
    };
  }

  const title = `${guide.title} | SEO Guides`;
  const description = guide.summary;
  const url = `https://seoindicator.com/seo-guides/${guide.slug}/`;
  const ogImageUrl = `https://seoindicator.com/guides/og/og-${guide.slug}.png`;

  return {
    title,
    description,
    alternates: {
      canonical: url,
    },
    openGraph: {
      title,
      description,
      type: 'article',
      url,
      siteName: 'SEOIndicator',
      images: [
        {
          url: ogImageUrl,
          width: 1200,
          height: 630,
          alt: `${guide.title} Guide`,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
      images: [ogImageUrl],
    },
  };
}

// ============================================================================
// Guide Page Component
// ============================================================================

interface GuidePageProps {
  params: Promise<{ slug: string }>;
}

/**
 * Get previous and next guides for navigation
 */
function getPrevNextGuides(currentSlug: string): {
  prev: SEOGuide | null;
  next: SEOGuide | null
} {
  const guides = getAllGuides();
  const currentIndex = guides.findIndex(g => g.slug === currentSlug);

  return {
    prev: currentIndex > 0 ? guides[currentIndex - 1] : null,
    next: currentIndex < guides.length - 1 ? guides[currentIndex + 1] : null,
  };
}

/**
 * Category badge colors
 */
const categoryColors: Record<string, string> = {
  'on-page': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  'technical': 'bg-purple-500/10 text-purple-400 border-purple-500/20',
  'content': 'bg-green-500/10 text-green-400 border-green-500/20',
};

/**
 * Individual SEO Guide Page
 * Renders guide content with breadcrumb, navigation, and related guides
 */
export default async function GuidePage({ params }: GuidePageProps) {
  const { slug } = await params;
  const guide = getGuideBySlug(slug);

  // 404 for invalid slugs
  if (!guide) {
    notFound();
  }

  const relatedGuides = getRelatedGuides(slug);
  const { prev, next } = getPrevNextGuides(slug);

  // Check if guide has content
  const hasSections = guide.sections && guide.sections.length > 0;
  const hasChecklist = guide.checklist && guide.checklist.length > 0;
  const hasContent = hasSections || hasChecklist;

  return (
    <main className="min-h-screen bg-slate-950 text-white">
      {/* Breadcrumb Navigation */}
      <nav aria-label="Breadcrumb" className="border-b border-slate-800 bg-slate-900/50">
        <div className="mx-auto max-w-4xl px-4 py-3">
          <ol className="flex items-center gap-2 text-sm text-slate-400">
            <li>
              <Link
                href="/"
                className="hover:text-emerald-400 transition-colors"
              >
                Home
              </Link>
            </li>
            <li className="text-slate-600">/</li>
            <li>
              <Link
                href="/seo-guides/"
                className="hover:text-emerald-400 transition-colors"
              >
                SEO Guides
              </Link>
            </li>
            <li className="text-slate-600">/</li>
            <li className="text-white" aria-current="page">
              {guide.title}
            </li>
          </ol>
        </div>
      </nav>

      <article className="mx-auto max-w-4xl px-4 py-8">
        {/* Hero Section */}
        <header className="mb-8">
          <div className="flex flex-wrap items-center gap-3 mb-4">
            {/* Category Badge */}
            <span
              className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium ${categoryColors[guide.category]}`}
            >
              {guide.category.charAt(0).toUpperCase() + guide.category.slice(1)} SEO
            </span>

            {/* Tool Badge */}
            {guide.toolSlug && (
              <span className="inline-flex items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                Has Tool
              </span>
            )}

            {/* Read Time */}
            <span className="flex items-center gap-1 text-sm text-slate-400">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {guide.readTime} min read
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl font-bold text-white mb-3 flex items-center gap-3">
            <span className="text-4xl">{guide.icon}</span>
            {guide.title}
          </h1>

          {/* Summary */}
          <p className="text-lg text-slate-300">
            {guide.summary}
          </p>
        </header>

        {/* Content Area */}
        {hasContent ? (
          <div className="mb-8">
            {/* Sections */}
            {hasSections && (
              <div data-testid="guide-sections" className="space-y-12">
                {guide.sections.map((section, index) => (
                  <GuideSection key={index} section={section} index={index} />
                ))}
              </div>
            )}

            {/* Tool Embed - Interactive tool for guides with toolSlug */}
            {guide.toolSlug && (
              <div data-testid="guide-tool-embed" className="my-12">
                <ToolEmbed
                  toolSlug={guide.toolSlug}
                  title={`Test Your ${guide.title.replace(' Guide', '').replace(' Optimization', '').replace(' Best Practices', '')}`}
                  showCta={true}
                  ctaText="Use Full Tool"
                />
              </div>
            )}

            {/* Checklist */}
            {hasChecklist && (
              <GuideChecklist items={guide.checklist} />
            )}
          </div>
        ) : (
          /* Placeholder for guides without content */
          <div className="prose prose-invert prose-slate max-w-none mb-8">
            <div className="rounded-lg border border-slate-800 bg-slate-900/50 p-8 text-center">
              <p className="text-slate-400">
                Guide content coming soon. Check back later for detailed information about {guide.title.toLowerCase()}.
              </p>
            </div>
          </div>
        )}

        {/* Compare with Alternatives Callout */}
        <aside
          data-testid="comparison-callout"
          role="complementary"
          className="my-12 rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-6"
        >
          <h4 className="text-lg font-semibold text-white mb-2">
            Compare with Alternatives
          </h4>
          <p className="text-slate-400 mb-4">
            See how SEOIndicator compares to other SEO tools and find the best fit for your needs.
          </p>
          <Link
            href="/compare"
            className="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 transition-colors font-medium"
            data-testid="callout-compare-link"
          >
            View comparisons
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </aside>

        {/* Related Guides */}
        {relatedGuides.length > 0 && (
          <section className="mt-12 border-t border-slate-800 pt-8">
            <h2 className="mb-4 text-xl font-semibold text-white">
              Related Guides
            </h2>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {relatedGuides.map((related) => (
                <Link
                  key={related.slug}
                  href={`/seo-guides/${related.slug}/`}
                  className="group rounded-lg border border-slate-800 bg-slate-900/50 p-4 transition-colors hover:border-emerald-500/50 hover:bg-slate-800/50"
                >
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-2xl">{related.icon}</span>
                    <h3 className="font-medium text-white group-hover:text-emerald-400">
                      {related.title}
                    </h3>
                  </div>
                  <p className="text-sm text-slate-400 line-clamp-2">
                    {related.summary}
                  </p>
                </Link>
              ))}
            </div>
          </section>
        )}

        {/* Navigation */}
        <nav className="mt-12 flex items-center justify-between border-t border-slate-800 pt-8" aria-label="Guide navigation">
          <div className="flex-1">
            {prev ? (
              <Link
                href={`/seo-guides/${prev.slug}/`}
                className="group inline-flex flex-col items-start gap-1 text-slate-400 hover:text-emerald-400 transition-colors"
              >
                <span className="flex items-center gap-1 text-xs">
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                  </svg>
                  Previous
                </span>
                <span className="font-medium text-white group-hover:text-emerald-400">
                  {prev.icon} {prev.title}
                </span>
              </Link>
            ) : (
              <div />
            )}
          </div>

          <Link
            href="/seo-guides/"
            className="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition-colors"
          >
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            All Guides
          </Link>

          <div className="flex-1 text-right">
            {next ? (
              <Link
                href={`/seo-guides/${next.slug}/`}
                className="group inline-flex flex-col items-end gap-1 text-slate-400 hover:text-emerald-400 transition-colors"
              >
                <span className="flex items-center gap-1 text-xs">
                  Next
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </span>
                <span className="font-medium text-white group-hover:text-emerald-400">
                  {next.icon} {next.title}
                </span>
              </Link>
            ) : (
              <div />
            )}
          </div>
        </nav>
      </article>

      {/* JSON-LD Structured Data - HowTo Schema for Rich Results */}
      <GuideSchema guide={guide} />
    </main>
  );
}
