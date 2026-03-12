/**
 * PWA Guide Page
 * Task: TASK-24-14 - PWA Guide Page (Frontend)
 * 
 * Server component that exports metadata and renders the client component.
 */

import { Metadata } from 'next';
import { Suspense } from 'react';
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

function LoadingSkeleton() {
  return (
    <div className="animate-pulse space-y-4 p-6">
      <div className="h-8 bg-gray-200 rounded w-1/2" />
      <div className="h-4 bg-gray-200 rounded w-3/4" />
      <div className="h-4 bg-gray-200 rounded w-1/2" />
    </div>
  );
}

export default function PWAGuidePage() {
  return (
    <Suspense fallback={<LoadingSkeleton />}>
      <PWAGuideClient />
    </Suspense>
  );
}
