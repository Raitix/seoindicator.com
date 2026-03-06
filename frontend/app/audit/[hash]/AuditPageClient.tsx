'use client';

import { useParams } from 'next/navigation';
import { useEffect, useState } from 'react';

import PublicAuditPage from '@/components/audit/PublicAuditPage';
import type { PublicAuditData } from '@/components/audit/PublicAuditPage';
import NotFound from './not-found';

/**
 * Loading spinner component
 */
function LoadingSpinner() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="flex flex-col items-center gap-4">
        <div className="h-12 w-12 animate-spin rounded-full border-4 border-emerald-500 border-t-transparent" />
        <p className="text-slate-400">Loading audit...</p>
      </div>
    </div>
  );
}

/**
 * Error component
 */
function ErrorDisplay({ message }: { message: string }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
      <div className="max-w-md text-center">
        <div className="mb-4 text-6xl">⚠️</div>
        <h1 className="mb-2 text-2xl font-bold text-white">Error Loading Audit</h1>
        <p className="text-slate-400">{message}</p>
      </div>
    </div>
  );
}

/**
 * Client-side function to fetch audit data
 */
async function fetchAuditData(hash: string): Promise<PublicAuditData | null> {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';
  
  try {
    const res = await fetch(
      `${apiUrl}/api/v1/public-audit/${hash}`
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
    console.error('Failed to fetch audit data:', error);
    return null;
  }
}

/**
 * Update document metadata for SEO
 */
function updateMetadata(audit: PublicAuditData | null, hash: string) {
  if (!audit) {
    document.title = 'Audit Not Found | SEOIndicator';
    return;
  }

  const canonicalUrl = `https://seoindicator.com/audit/${hash}/`;
  const ogImageUrl = `https://seoindicator.com/badge/${hash}/og.png`;

  // Update title
  document.title = `${audit.siteUrl} SEO Audit Report | SEOIndicator`;

  // Update or create meta tags
  const updateMeta = (name: string, content: string, property = false) => {
    const selector = property ? `meta[property="${name}"]` : `meta[name="${name}"]`;
    let meta = document.querySelector(selector) as HTMLMetaElement;
    
    if (!meta) {
      meta = document.createElement('meta');
      if (property) {
        meta.setAttribute('property', name);
      } else {
        meta.setAttribute('name', name);
      }
      document.head.appendChild(meta);
    }
    meta.setAttribute('content', content);
  };

  // Standard meta tags
  updateMeta('description', `SEO audit for ${audit.siteUrl} - Score: ${audit.overallScore}/100. View technical SEO analysis and recommendations.`);
  updateMeta('robots', 'index, follow');

  // Open Graph tags
  updateMeta('og:title', `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`, true);
  updateMeta('og:description', `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`, true);
  updateMeta('og:url', canonicalUrl, true);
  updateMeta('og:type', 'website', true);
  updateMeta('og:image', ogImageUrl, true);

  // Twitter tags
  updateMeta('twitter:card', 'summary_large_image');
  updateMeta('twitter:title', `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`);
  updateMeta('twitter:description', `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`);
  updateMeta('twitter:image', ogImageUrl);

  // Update canonical link
  let canonical = document.querySelector('link[rel="canonical"]') as HTMLLinkElement;
  if (!canonical) {
    canonical = document.createElement('link');
    canonical.setAttribute('rel', 'canonical');
    document.head.appendChild(canonical);
  }
  canonical.setAttribute('href', canonicalUrl);
}

/**
 * Public Audit Page Client Component
 * Handles client-side rendering for public audit viewing
 * @returns JSX element
 */
export default function AuditPageClient() {
  const params = useParams();
  const hash = params.hash as string;
  
  const [audit, setAudit] = useState<PublicAuditData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!hash) {
      setLoading(false);
      return;
    }

    fetchAuditData(hash)
      .then((data) => {
        if (data) {
          setAudit(data);
          updateMetadata(data, hash);
        } else {
          updateMetadata(null, hash);
        }
      })
      .catch((err) => {
        console.error('Error fetching audit:', err);
        setError('Failed to load audit data. Please try again later.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [hash]);

  // Loading state
  if (loading) {
    return <LoadingSpinner />;
  }

  // Error state
  if (error) {
    return <ErrorDisplay message={error} />;
  }

  // Not found state
  if (!audit) {
    return <NotFound />;
  }

  // Render audit page
  return <PublicAuditPage audit={audit} hash={hash} />;
}
