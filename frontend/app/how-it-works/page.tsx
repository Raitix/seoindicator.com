/**
 * How It Works Page
 * Task: TASK-30-3 - How It Works Page
 * Route: /how-it-works
 *
 * Static page displaying step-by-step user journey, indicator explanations,
 * scoring methodology, and FAQ section.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import HowItWorksContent from '@/components/pages/HowItWorksContent';

// ============================================================================
// SEO Metadata
// ============================================================================

export async function generateMetadata(): Promise<Metadata> {
  const title = 'How It Works | SEOIndicator SEO Analysis';
  const description =
    'Learn how SEOIndicator analyzes your website across 17 SEO indicators. Understand our scoring methodology, indicator categories, and get actionable insights to improve your search visibility.';
  const url = 'https://seoindicator.com/how-it-works/';

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
      images: [
        {
          url: 'https://seoindicator.com/og-how-it-works.png',
          width: 1200,
          height: 630,
          alt: 'How SEOIndicator Works - 17 SEO Indicators Explained',
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
    },
  };
}

// ============================================================================
// Schema.org Structured Data
// ============================================================================

const webPageSchema = {
  '@context': 'https://schema.org',
  '@type': 'WebPage',
  name: 'How It Works | SEOIndicator SEO Analysis',
  description:
    'Learn how SEOIndicator analyzes your website across 17 SEO indicators. Understand our scoring methodology and get actionable insights.',
  url: 'https://seoindicator.com/how-it-works/',
  publisher: {
    '@type': 'Organization',
    name: 'SEOIndicator',
    url: 'https://seoindicator.com',
  },
  dateModified: '2026-03-05',
};

const faqSchema = {
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  mainEntity: [
    {
      '@type': 'Question',
      name: 'How is my SEO score calculated?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: "Your SEO score is calculated based on 17 different indicators across 4 categories: Technical (30%), Content (40%), Performance (20%), and UX (10%). Each indicator is scored from 0-100, and the final score is a weighted average of all indicators. We use industry-standard benchmarks and Google's official guidelines to determine scoring thresholds.",
      },
    },
    {
      '@type': 'Question',
      name: 'How often should I run an SEO analysis?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'We recommend running an analysis at least once a month or after significant content changes. For high-traffic websites or those in competitive niches, weekly analysis can help you stay ahead of issues. Our automated monitoring feature can alert you to significant changes in your SEO health.',
      },
    },
    {
      '@type': 'Question',
      name: 'What is the difference between quick, standard, and deep analysis?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Quick analysis checks essential indicators in under a minute, perfect for regular monitoring. Standard analysis covers all 17 indicators with moderate depth, taking 2-3 minutes. Deep analysis provides comprehensive insights including competitor comparison and historical data, typically taking 5-10 minutes.',
      },
    },
    {
      '@type': 'Question',
      name: 'Can I analyze multiple pages at once?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Yes! You can add multiple pages to a project and analyze them together. This is especially useful for comprehensive site audits. Project-level analysis also provides aggregated scores and identifies common issues across your site.',
      },
    },
    {
      '@type': 'Question',
      name: 'How accurate are the SEO recommendations?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: "Our recommendations are based on Google's official guidelines and industry best practices. We continuously update our algorithms to reflect the latest SEO standards. While no automated tool can guarantee search rankings, following our recommendations typically leads to measurable improvements.",
      },
    },
    {
      '@type': 'Question',
      name: 'What are Core Web Vitals?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Core Web Vitals are specific metrics that measure user experience: Largest Contentful Paint (LCP) measures loading performance, First Input Delay (FID) measures interactivity, and Cumulative Layout Shift (CLS) measures visual stability. Google uses these as ranking factors, making them crucial for SEO.',
      },
    },
    {
      '@type': 'Question',
      name: 'Do I need technical knowledge to use SEOIndicator?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'No! Our tool is designed for users of all skill levels. While advanced users can dive deep into technical metrics, beginners get plain-language explanations and step-by-step guides for every recommendation. We translate complex SEO concepts into actionable tasks.',
      },
    },
    {
      '@type': 'Question',
      name: 'How do I fix the issues found?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Each issue includes detailed recommendations with step-by-step guides. For common issues, we provide code snippets and implementation examples. You can also export reports to share with your development team. Our AI assistant can answer specific questions about any recommendation.',
      },
    },
    {
      '@type': 'Question',
      name: 'Can I export my analysis results?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Yes, you can export results in PDF for presentations, CSV for data analysis, or JSON for integration with other tools. Scheduled reports can be automatically delivered to your email. Team plans include API access for automated reporting workflows.',
      },
    },
    {
      '@type': 'Question',
      name: 'Is my data secure?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Absolutely. We use industry-standard encryption (TLS 1.3) for all data in transit and AES-256 for data at rest. We never share your analysis data with third parties. Your URLs and reports are private to your account, and you can delete them at any time.',
      },
    },
  ],
};

// ============================================================================
// Page Component
// ============================================================================

export default function HowItWorksPage() {
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
            <li className="text-slate-600" aria-hidden="true">
              /
            </li>
            <li className="text-white" aria-current="page">
              How It Works
            </li>
          </ol>
        </div>
      </nav>

      {/* Content */}
      <HowItWorksContent />

      {/* WebPage Schema */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(webPageSchema) }}
      />

      {/* FAQPage Schema */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }}
      />
    </main>
  );
}
