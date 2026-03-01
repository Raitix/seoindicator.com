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
  },
  twitter: {
    card: 'summary_large_image',
    title: 'SEO Guides - SEOIndicator',
    description: 'Master SEO with our comprehensive guides covering on-page optimization, technical SEO, and content best practices.',
  },
};

export default function GuidesIndexPage() {
  return <GuidesIndexClient />;
}
