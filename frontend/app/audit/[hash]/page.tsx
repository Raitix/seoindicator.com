/**
 * Public Audit Page Route
 * Next.js App Router route for public SEO audit pages with ISR
 * @module app/audit/[hash]/page
 */

import { notFound } from 'next/navigation';
import type { Metadata } from 'next';

import PublicAuditPage from '@/components/audit/PublicAuditPage';
import type { PublicAuditData } from '@/components/audit/PublicAuditPage';

/**
 * ISR revalidation time in seconds (1 hour)
 */
export const revalidate = 3600;

/**
 * Page props interface
 */
interface PageProps {
  params: Promise<{ hash: string }>;
}

/**
 * Fetch audit data from public API
 * @param hash - Public audit hash
 * @returns Audit data or null if not found
 */
async function getAuditData(hash: string): Promise<PublicAuditData | null> {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';
  
  try {
    const res = await fetch(
      `${apiUrl}/api/v1/public-audit/${hash}`,
      { next: { revalidate: 3600 } }
    );

    if (!res.ok) {
      return null;
    }

    const data = await res.json();
    
    // Check if the response indicates success and has valid data
    if (!data.success || !data.data) {
      return null;
    }

    return data.data as PublicAuditData;
  } catch (error) {
    // Network error or JSON parse error
    console.error('Failed to fetch audit data:', error);
    return null;
  }
}

/**
 * Generate metadata for SEO
 * @param props - Page props
 * @returns Metadata object
 */
export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { hash } = await params;
  const audit = await getAuditData(hash);

  if (!audit) {
    return {
      title: 'Audit Not Found | SEOIndicator',
      description: 'The requested SEO audit could not be found.',
      robots: 'noindex',
    };
  }

  const canonicalUrl = `https://seoindicator.com/audit/${hash}/`;
  const ogImageUrl = `https://seoindicator.com/badge/${hash}/og.png`;

  return {
    title: `${audit.siteUrl} SEO Audit Report | SEOIndicator`,
    description: `SEO audit for ${audit.siteUrl} - Score: ${audit.overallScore}/100. View technical SEO analysis and recommendations.`,
    robots: 'index, follow',
    alternates: {
      canonical: canonicalUrl,
    },
    openGraph: {
      title: `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`,
      description: `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`,
      url: canonicalUrl,
      type: 'website',
      images: [
        {
          url: ogImageUrl,
          width: 1200,
          height: 630,
          alt: `SEO Score: ${audit.overallScore}/100 for ${audit.siteUrl}`,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title: `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`,
      description: `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`,
      images: [ogImageUrl],
    },
  };
}

/**
 * Public Audit Page Component
 * Server component that fetches and renders public audit data
 * @param props - Page props
 * @returns JSX element
 */
export default async function Page({ params }: PageProps) {
  const { hash } = await params;
  const audit = await getAuditData(hash);

  if (!audit) {
    notFound();
  }

  return <PublicAuditPage audit={audit} hash={hash} />;
}
