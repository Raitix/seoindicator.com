/**
 * Public Audit Page Route
 * Server component that exports generateMetadata for SEO and generateStaticParams for static export compatibility
 * Renders client component for dynamic audit viewing
 * @module app/audit/[hash]/page
 */

import { Metadata } from 'next';
import AuditPageClient from './AuditPageClient';
import type { PublicAuditData } from '@/components/audit/PublicAuditPage';

/**
 * Enable dynamic params for client-side rendering
 * Required for static export with dynamic routes
 */
export const dynamicParams = true;

/**
 * Generate static params - empty array for fully dynamic rendering
 * All pages will be rendered client-side at runtime
 */
export async function generateStaticParams() {
  return [];
}

/**
 * Fetch audit data from API for metadata generation
 */
async function fetchAuditDataForMetadata(hash: string): Promise<PublicAuditData | null> {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';
  
  try {
    const res = await fetch(
      `${apiUrl}/api/v1/public-audit/${hash}`,
      { 
        next: { revalidate: 3600 }, // Cache for 1 hour
        signal: AbortSignal.timeout(5000) // 5 second timeout
      }
    );

    if (!res.ok) {
      return null;
    }

    const data = await res.json();
    
    if (!data.success || !data.data) {
      return null;
    }

    return data.data as PublicAuditData;
  } catch (error) {
    console.error('Failed to fetch audit data for metadata:', error);
    return null;
  }
}

/**
 * Strip protocol from URL for display
 */
function stripProtocol(url: string): string {
  return url
    .replace(/^https?:\/\//, '')
    .replace(/\/$/, '');
}

/**
 * Truncate URL for title (max 40 chars for display URL, title max 70 chars total)
 */
function truncateUrlForTitle(url: string): string {
  const stripped = stripProtocol(url);
  if (stripped.length <= 40) {
    return stripped;
  }
  return stripped.slice(0, 37) + '...';
}

/**
 * Escape HTML entities for safe display
 */
function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Generate metadata for public audit pages
 * Implements comprehensive SEO meta tags and social sharing tags
 */
export async function generateMetadata({ params }: { params: Promise<{ hash: string }> }): Promise<Metadata> {
  const { hash } = await params;
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://seoindicator.com';
  
  // Fetch audit data
  const audit = await fetchAuditDataForMetadata(hash);

  // Handle not found or error cases
  if (!audit) {
    return {
      title: 'Audit Not Found | SEOIndicator',
      description: 'The requested SEO audit could not be found. The audit may have been removed or the link is invalid.',
      robots: 'noindex',
    };
  }

  // Process URL for display
  const displayUrl = stripProtocol(audit.siteUrl);
  const truncatedUrl = truncateUrlForTitle(audit.siteUrl);
  const escapedDisplayUrl = escapeHtml(displayUrl);
  
  // Generate canonical URL
  const canonicalUrl = `${siteUrl}/audit/${hash}/`;
  
  // Generate OG image URL
  const ogImageUrl = `${siteUrl}/badge/${hash}/og.png`;

  // Generate title
  const title = `${truncatedUrl} SEO Audit Report | SEOIndicator`;
  
  // Generate description (max 160 chars for SEO)
  const description = `SEO audit for ${escapedDisplayUrl} - Score: ${audit.overallScore}/100. View technical SEO analysis and recommendations.`;

  // Generate OG title with score
  const ogTitle = `${displayUrl} SEO Score: ${audit.overallScore}/100`;
  
  // Generate OG description
  const ogDescription = `SEO audit results for ${displayUrl} - Score: ${audit.overallScore}/100`;

  // Determine robots directive
  const robots = audit.isPartial ? 'index, follow' : 'index, follow';

  return {
    title,
    description,
    robots,
    alternates: {
      canonical: canonicalUrl,
    },
    openGraph: {
      title: ogTitle,
      description: ogDescription,
      url: canonicalUrl,
      type: 'website',
      images: [
        {
          url: ogImageUrl,
          width: 1200,
          height: 630,
          alt: `SEO Score: ${audit.overallScore}/100`,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title: ogTitle,
      description: ogDescription,
      images: [ogImageUrl],
    },
  };
}

/**
 * Public Audit Page - Server Component
 * Delegates to client component for dynamic rendering
 * @returns JSX element
 */
export default function Page() {
  return <AuditPageClient />;
}
