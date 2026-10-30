/**
 * TASK-34-21: AI Visibility Alert Detail Page
 * 
 * Server component that renders the alert detail page.
 * Fetches alert data and passes to client component.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-21.md
 */

import { Metadata } from 'next';
import { AlertDetailPageClient } from './page.client';

// Disable dynamic params for static export compatibility
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns a placeholder id to satisfy Next.js static export requirement.
 * The actual id lookup is done client-side via AlertDetailPageClient.
 */
export function generateStaticParams() {
  // Return a placeholder to satisfy Next.js static export
  // The client-side component handles actual id routing
  return [{ id: '_' }];
}

interface AlertDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

export async function generateMetadata({ params }: AlertDetailPageProps): Promise<Metadata> {
  const { id } = await params;
  
  return {
    title: `Alert #${id} | AI Visibility | SEOIndicator`,
    description: 'View AI Visibility alert details',
    robots: {
      index: false,
      follow: true,
    },
  };
}

export default async function AlertDetailPage({ params }: AlertDetailPageProps) {
  const { id } = await params;
  
  return <AlertDetailPageClient alertId={id} />;
}
