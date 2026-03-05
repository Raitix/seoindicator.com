/**
 * Comparison Not Found Page
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * 404 page displayed when an invalid comparison slug is requested.
 */

import Link from 'next/link';
import { Button } from '@/components/ui/button';

export default function CompareNotFound() {
  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
      <div className="mx-auto max-w-2xl px-4 py-20 text-center">
        {/* 404 Header */}
        <div className="mb-8">
          <span className="text-8xl font-bold text-slate-700">404</span>
        </div>

        {/* Heading */}
        <h1 className="text-3xl md:text-4xl font-bold mb-4 text-white">
          Comparison Not Found
        </h1>

        {/* Description */}
        <p className="text-lg text-slate-400 mb-8">
          The comparison you&apos;re looking for doesn&apos;t exist or has been moved.
          Please check the URL or browse our available comparisons.
        </p>

        {/* Actions */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Button
            asChild
            className="bg-emerald-600 hover:bg-emerald-700 text-white"
          >
            <Link href="/compare">
              View All Comparisons
            </Link>
          </Button>
          <Button
            asChild
            variant="outline"
            className="border-slate-600 text-slate-300 hover:text-white"
          >
            <Link href="/">
              Go to Homepage
            </Link>
          </Button>
        </div>

        {/* Popular Comparisons */}
        <div className="mt-12">
          <h2 className="text-xl font-semibold mb-4 text-slate-300">
            Popular Comparisons
          </h2>
          <div className="grid gap-3 sm:grid-cols-2">
            {[
              { slug: 'seoindicator-vs-ahrefs', label: 'SEOIndicator vs Ahrefs' },
              { slug: 'seoindicator-vs-semrush', label: 'SEOIndicator vs Semrush' },
              { slug: 'seoindicator-vs-moz', label: 'SEOIndicator vs Moz' },
              { slug: 'ahrefs-vs-semrush', label: 'Ahrefs vs Semrush' },
            ].map((item) => (
              <Link
                key={item.slug}
                href={`/compare/${item.slug}`}
                className="block p-3 bg-slate-800/50 border border-slate-700 rounded-lg hover:border-emerald-500/50 transition-colors text-slate-300 hover:text-white"
              >
                {item.label}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </main>
  );
}
