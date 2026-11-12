/**
 * Privacy Policy Page
 *
 * The full Privacy Policy lives in a dedicated section of the combined
 * Terms & Privacy page (`/terms`), which keeps the two documents in one
 * place and guarantees they stay in sync.
 *
 * This route exists so that `/privacy/` is a valid, linkable URL - the
 * footer links to it and search engines index it separately.
 *
 * Route: /privacy
 */

import { Metadata } from 'next';
import Link from 'next/link';
import TermsContent from '@/components/pages/TermsContent';

// ============================================================================
// SEO Metadata
// ============================================================================

export async function generateMetadata(): Promise<Metadata> {
  const title = 'Privacy Policy | SEOIndicator';
  const description =
    'How SEOIndicator collects, uses, and protects your personal data. Learn about the information we gather, why we gather it, and the controls you have over it.';
  const url = 'https://seoindicator.com/privacy/';

  return {
    title,
    description,
    alternates: {
      canonical: url,
    },
    openGraph: {
      title,
      description,
      url,
      siteName: 'SEOIndicator',
      type: 'article',
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
    },
  };
}

// ============================================================================
// Page Component
// ============================================================================

export default function PrivacyPage() {
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
            <li className="text-slate-400" aria-hidden="true">/</li>
            <li className="text-white" aria-current="page">
              Privacy Policy
            </li>
          </ol>
        </div>
      </nav>

      {/* Page Header */}
      <header className="mx-auto max-w-4xl px-4 py-8">
        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">
          Privacy Policy
        </h1>
        <p className="text-lg text-slate-400">
          How we collect, use, and protect your personal data.
        </p>
        <p className="mt-4 text-sm text-slate-500">
          This policy is published together with our{' '}
          <Link
            href="/terms/"
            className="text-emerald-400 hover:text-emerald-300 underline underline-offset-4"
          >
            Terms of Service
          </Link>
          .
        </p>
      </header>

      {/* Content */}
      <article className="mx-auto max-w-4xl px-4 pb-16">
        <TermsContent />
      </article>
    </main>
  );
}
