/**
 * Compare Index Page
 * Route: /compare
 *
 * Lists all available comparison pages for programmatic SEO.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import { COMPARISON_SLUGS, getCompetitorById } from '@/lib/compare/registry';

export const metadata: Metadata = {
  title: 'SEO Tool Comparisons | SEOIndicator',
  description:
    'Compare SEOIndicator with Ahrefs, Semrush, Moz, and other SEO tools. Find the best SEO tool for your needs with our detailed comparisons.',
  openGraph: {
    title: 'SEO Tool Comparisons | SEOIndicator',
    description:
      'Compare SEOIndicator with other SEO tools. Detailed analysis of features, pricing, and use cases.',
    type: 'website',
  },
};

/**
 * Format a comparison slug into a human-readable title.
 */
function formatComparisonTitle(slug: string): string {
  const parts = slug.split('-vs-');
  if (parts.length !== 2) return slug;

  const toolA = getCompetitorById(parts[0]);
  const toolB = getCompetitorById(parts[1]);

  const nameA = toolA?.name || capitalizeFirst(parts[0]);
  const nameB = toolB?.name || capitalizeFirst(parts[1]);

  return `${nameA} vs ${nameB}`;
}

function capitalizeFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export default function CompareIndexPage() {
  // Group comparisons: primary (seoindicator-vs-*) and cross-competitor
  const primaryComparisons = COMPARISON_SLUGS.filter((s) =>
    s.startsWith('seoindicator-vs-')
  );
  const crossCompetitorComparisons = COMPARISON_SLUGS.filter(
    (s) => !s.startsWith('seoindicator-')
  );

  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
      {/* Breadcrumb */}
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
            <li className="text-white" aria-current="page">
              Compare
            </li>
          </ol>
        </div>
      </nav>

      {/* Content */}
      <div className="mx-auto max-w-5xl px-4 py-10">
        {/* Header */}
        <header className="text-center mb-12">
          <h1 className="text-3xl md:text-4xl font-bold mb-4">
            SEO Tool Comparisons
          </h1>
          <p className="text-lg text-slate-400 max-w-2xl mx-auto">
            Compare SEOIndicator with other popular SEO tools. Find the best fit
            for your needs, budget, and workflow.
          </p>
        </header>

        {/* Primary Comparisons */}
        <section aria-labelledby="primary-heading" className="mb-12">
          <h2
            id="primary-heading"
            className="text-2xl font-bold mb-6 text-white"
          >
            SEOIndicator Comparisons
          </h2>
          <div className="grid gap-4 md:grid-cols-2">
            {primaryComparisons.map((slug) => (
              <Link
                key={slug}
                href={`/compare/${slug}`}
                className="group block p-6 bg-slate-900/50 border border-slate-800 rounded-xl hover:border-emerald-500/50 transition-all"
              >
                <h3 className="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors">
                  {formatComparisonTitle(slug)}
                </h3>
                <p className="text-sm text-slate-400 mt-2">
                  View detailed comparison →
                </p>
              </Link>
            ))}
          </div>
        </section>

        {/* Cross-Competitor Comparisons */}
        {crossCompetitorComparisons.length > 0 && (
          <section aria-labelledby="cross-heading">
            <h2
              id="cross-heading"
              className="text-2xl font-bold mb-6 text-white"
            >
              Other Comparisons
            </h2>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {crossCompetitorComparisons.map((slug) => (
                <Link
                  key={slug}
                  href={`/compare/${slug}`}
                  className="group block p-4 bg-slate-800/30 border border-slate-700 rounded-lg hover:border-blue-500/50 transition-all"
                >
                  <h3 className="font-medium text-white group-hover:text-blue-400 transition-colors">
                    {formatComparisonTitle(slug)}
                  </h3>
                </Link>
              ))}
            </div>
          </section>
        )}
      </div>
    </main>
  );
}
