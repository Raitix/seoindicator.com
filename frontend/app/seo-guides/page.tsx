/**
 * Guide Index Page
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Server component that exports metadata and renders the client component.
 */

import { Metadata } from 'next';
import { GuidesIndexClient } from './GuidesIndexClient';

export const metadata: Metadata = {
  title: 'SEO Guides - SEOIndicator',
  description: 'Master SEO with our comprehensive guides covering on-page optimization, technical SEO, and content best practices.',
  alternates: {
    canonical: '/seo-guides',
  },
  openGraph: {
    title: 'SEO Guides - SEOIndicator',
    description: 'Master SEO with our comprehensive guides covering on-page optimization, technical SEO, and content best practices.',
    type: 'website',
    images: [
      {
        url: 'https://seoindicator.com/guides/og/og-index.png',
        width: 1200,
        height: 630,
        alt: 'SEO Guides - SEOIndicator',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'SEO Guides - SEOIndicator',
    description: 'Master SEO with our comprehensive guides covering on-page optimization, technical SEO, and content best practices.',
    images: ['https://seoindicator.com/guides/og/og-index.png'],
  },
};

export default function GuidesIndexPage() {
  return <GuidesIndexClient />;
}
