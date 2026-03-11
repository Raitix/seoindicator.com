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
