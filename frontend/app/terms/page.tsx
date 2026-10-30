/**
 * Terms & Privacy Page
 * Task: TASK-30-1 - Terms & Privacy Page
 * Route: /terms
 * 
 * Static page displaying Terms of Service and Privacy Policy content.
 * Includes SEO metadata, WebPage schema, and accessible content structure.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import TermsContent from '@/components/pages/TermsContent';

// ============================================================================
// SEO Metadata
// ============================================================================

export async function generateMetadata(): Promise<Metadata> {
  const title = 'Terms & Privacy | SEOIndicator';
  const description = 'Read our comprehensive Terms of Service and Privacy Policy to understand how SEOIndicator collects, uses, and protects your personal data and privacy.';
  const url = 'https://seoindicator.com/terms/';

  return {
    title,
    description,
    robots: {
      index: true,
      follow: true,
    },
    alternates: {
      canonical: url,
    },
    openGraph: {
      title,
      description,
      url,
      siteName: 'SEOIndicator',
      type: 'website',
    },
    twitter: {
      card: 'summary',
      title,
      description,
    },
  };
}

// ============================================================================
// WebPage Schema
// ============================================================================

const webPageSchema = {
  '@context': 'https://schema.org',
  '@type': 'WebPage',
  name: 'Terms & Privacy',
  description: 'Read our Terms of Service and Privacy Policy to understand how SEOIndicator collects, uses, and protects your data.',
  url: 'https://seoindicator.com/terms/',
  publisher: {
    '@type': 'Organization',
    name: 'SEOIndicator',
    url: 'https://seoindicator.com',
  },
  dateModified: '2026-03-05',
};

// ============================================================================
// Page Component
// ============================================================================

export default function TermsPage() {
  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
      {/* Breadcrumb Navigation */}
      <nav
        aria-label="Breadcrumb"
        className="border-b border-slate-800 bg-slate-900/50"
      >
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
            <li className="text-slate-600" aria-hidden="true">/</li>
            <li className="text-white" aria-current="page">
              Terms & Privacy
            </li>
          </ol>
        </div>
      </nav>

      {/* Page Header */}
      <header className="mx-auto max-w-4xl px-4 py-8">
        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">
          Terms of Service & Privacy Policy
        </h1>
        <p className="text-lg text-slate-400">
          Please read these terms carefully before using SEOIndicator services.
        </p>
      </header>

      {/* Content */}
      <article className="mx-auto max-w-4xl px-4 pb-16">
        <TermsContent />
      </article>

      {/* WebPage Schema */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(webPageSchema) }}
      />
    </main>
  );
}
