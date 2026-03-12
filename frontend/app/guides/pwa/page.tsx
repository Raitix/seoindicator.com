/**
 * PWA Guide Page
 * Task: TASK-24-14 - PWA Guide Page (Frontend)
 * 
 * Server component that exports metadata and renders the client component.
 */

import { Metadata } from 'next';
import { PWAGuideClient } from './PWAGuideClient';

export const metadata: Metadata = {
  title: 'PWA Implementation Guide | SEOIndicator',
  description: 'A comprehensive guide to implementing Progressive Web App features, including manifest files, icons, iOS support, and service workers.',
  alternates: {
    canonical: '/guides/pwa',
  },
  openGraph: {
    title: 'PWA Implementation Guide',
    description: 'A comprehensive guide to implementing Progressive Web App features, including manifest files, icons, iOS support, and service workers.',
    type: 'article',
    url: '/guides/pwa',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'PWA Implementation Guide',
    description: 'A comprehensive guide to implementing Progressive Web App features, including manifest files, icons, iOS support, and service workers.',
  },
};

export default function PWAGuidePage() {
  return <PWAGuideClient />;
}
