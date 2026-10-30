/**
 * About Page
 * Task: TASK-30-2 - About Page
 * Route: /about
 * 
 * Static page displaying mission statement, product overview, key features,
 * team/company information, and call-to-action to sign up.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import AboutContent from '@/components/pages/AboutContent';

// ============================================================================
// SEO Metadata
// ============================================================================

export async function generateMetadata(): Promise<Metadata> {
  const title = 'About SEOIndicator | SEO Analysis Platform';
  const description = 'Learn about SEOIndicator - your all-in-one AI-powered SEO analysis and monitoring platform for tracking keyword rankings, competitor analysis, and improving search visibility.';
  const url = 'https://seoindicator.com/about/';

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
// Organization Schema
// ============================================================================

const organizationSchema = {
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: 'SEOIndicator',
  url: 'https://seoindicator.com',
  logo: 'https://seoindicator.com/logo.png',
  description: 'AI-powered SEO analysis and monitoring platform for improving search visibility.',
  sameAs: [
    'https://twitter.com/seoindicator',
    'https://linkedin.com/company/seoindicator',
  ],
  contactInfo: {
    '@type': 'ContactPoint',
    contactType: 'customer support',
    email: 'support@seoindicator.com',
  },
};

const webPageSchema = {
  '@context': 'https://schema.org',
  '@type': 'WebPage',
  name: 'About SEOIndicator',
  description: 'Learn about SEOIndicator - your all-in-one AI-powered SEO analysis and monitoring platform.',
  url: 'https://seoindicator.com/about/',
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

export default function AboutPage() {
  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
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
            <li className="text-slate-600" aria-hidden="true">/</li>
            <li className="text-white" aria-current="page">
              About
            </li>
          </ol>
        </div>
      </nav>

      {/* Content */}
      <AboutContent />

      {/* Organization + WebPage Schema */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(organizationSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(webPageSchema) }}
      />
    </main>
  );
}
